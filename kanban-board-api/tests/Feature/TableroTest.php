<?php

namespace Tests\Feature;

use App\Events\TareaCreada;
use App\Events\TareaEliminada;
use App\Events\TareaMovida;
use App\Models\Tarea;
use App\Models\User;
use App\Services\TareaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TableroTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitados_no_pueden_ver_el_tablero_ni_modificarlo(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/sala')->assertRedirect('/login');
        $this->postJson('/tareas', ['titulo' => 'Prueba'])->assertUnauthorized();
        $this->patchJson('/tareas/1/mover', ['estado' => 'por_hacer', 'version' => 1])->assertUnauthorized();
        $this->deleteJson('/tareas/1')->assertUnauthorized();
        $this->postJson('/broadcasting/auth', ['socket_id' => '1.1', 'channel_name' => 'private-tablero'])->assertUnauthorized();
        $this->assertDatabaseCount('tareas', 0);
    }

    public function test_registro_autentica_y_guarda_un_hash(): void
    {
        $this->post('/registro', [
            'name' => 'Ariel', 'email' => 'ariel@example.test',
            'password' => 'clave123', 'password_confirmation' => 'clave123',
        ])->assertRedirect('/');
        $usuario = User::first();
        $this->assertAuthenticatedAs($usuario);
        $this->assertNotSame('clave123', $usuario->password);
        $this->assertTrue(Hash::check('clave123', $usuario->password));
    }

    public function test_registro_rechaza_correo_duplicado_y_contrasenas_distintas(): void
    {
        $usuario = User::factory()->create();
        $this->postJson('/registro', [
            'name' => 'Otro', 'email' => $usuario->email,
            'password' => 'clave123', 'password_confirmation' => 'otra123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_valida_la_clave_y_logout_cierra_la_sesion(): void
    {
        $usuario = User::factory()->create(['password' => 'clave123']);
        $this->postJson('/login', ['email' => $usuario->email, 'password' => 'incorrecta'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $usuario->email, 'password' => 'clave123'])->assertRedirect('/');
        $this->assertAuthenticatedAs($usuario);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->postJson('/tareas', ['titulo' => 'Prueba'])->assertUnauthorized();
    }

    public function test_tablero_comparte_las_tarjetas_y_las_ordena_por_creacion_e_id(): void
    {
        $autor = User::factory()->create();
        $lector = User::factory()->create();
        $fecha = now()->subDay();
        $primera = Tarea::factory()->create(['user_id' => $autor->id, 'created_at' => $fecha]);
        $segunda = Tarea::factory()->create(['user_id' => $autor->id, 'created_at' => $fecha]);
        $this->actingAs($lector)->get('/')->assertOk()
            ->assertSee('Tablero del equipo')
            ->assertViewHas('columnas', TareaService::COLUMNAS)
            ->assertViewHas('tareas', function ($tareas) use ($primera, $segunda) {
                $this->assertSame([$primera->id, $segunda->id], $tareas->pluck('id')->all());
                $this->assertSame(['id', 'titulo', 'estado', 'version', 'creador', 'movidaPor', 'creadaEn'], array_keys($tareas[0]));
                $this->assertIsInt($tareas[0]['version']);
                $this->assertNull($tareas[0]['movidaPor']);

                return true;
            });
    }

    public function test_crear_recorta_el_titulo_y_emite_solo_datos_del_tablero(): void
    {
        Event::fake([TareaCreada::class]);
        $usuario = User::factory()->create();
        $respuesta = $this->actingAs($usuario)->postJson('/tareas', ['titulo' => '  Preparar clase  '])
            ->assertCreated()->assertJsonPath('tarea.titulo', 'Preparar clase')
            ->assertJsonPath('tarea.estado', 'creado')->assertJsonPath('tarea.version', 1)
            ->assertJsonPath('tarea.creador', $usuario->name)->assertJsonPath('aviso', null);
        $this->assertDatabaseHas('tareas', ['id' => $respuesta->json('tarea.id'), 'titulo' => 'Preparar clase', 'user_id' => $usuario->id]);
        Event::assertDispatched(TareaCreada::class, function ($evento) use ($usuario) {
            $this->assertSame('private-tablero', $evento->broadcastOn()->name);
            $this->assertArrayNotHasKey('email', $evento->tarea);
            $this->assertStringNotContainsString($usuario->email, json_encode($evento->tarea));

            return true;
        });
    }

    public function test_titulos_vacios_y_mayores_de_cien_se_rechazan(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['', '   ', str_repeat('a', 101)] as $titulo) {
            $this->postJson('/tareas', ['titulo' => $titulo])->assertUnprocessable();
        }
        $this->assertDatabaseCount('tareas', 0);
        $this->postJson('/tareas', ['titulo' => str_repeat('á', 100)])->assertCreated();
    }

    public function test_mover_registra_compromiso_version_y_autor_y_emite_evento(): void
    {
        Event::fake([TareaMovida::class]);
        $tarea = Tarea::factory()->create();
        $actor = User::factory()->create();
        $this->actingAs($actor)->patchJson('/tareas/'.$tarea->id.'/mover', ['estado' => 'por_hacer', 'version' => 1])
            ->assertOk()->assertJsonPath('tarea.estado', 'por_hacer')
            ->assertJsonPath('tarea.version', 2)->assertJsonPath('tarea.movidaPor', $actor->name);
        $tarea->refresh();
        $this->assertSame('por_hacer', $tarea->estado);
        $this->assertSame($actor->id, $tarea->movida_por);
        $this->assertNotNull($tarea->comprometida_en);
        Event::assertDispatched(TareaMovida::class);
    }

    public function test_version_antigua_no_cambia_fechas_estado_ni_autor(): void
    {
        $tarea = Tarea::factory()->create();
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $ruta = '/tareas/'.$tarea->id.'/mover';
        $this->patchJson($ruta, ['estado' => 'por_hacer', 'version' => 1])->assertOk();
        $antes = $tarea->fresh()->getAttributes();
        $this->patchJson($ruta, ['estado' => 'por_hacer', 'version' => 1])
            ->assertUnprocessable()->assertJsonPath('error', 'La tarea cambió mientras la movías. Recarga el tablero.');
        $this->assertSame($antes, $tarea->fresh()->getAttributes());
    }

    public function test_no_se_pueden_saltar_columnas_ni_usar_un_destino_inexistente(): void
    {
        Event::fake([TareaMovida::class]);
        $tarea = Tarea::factory()->create();
        $this->actingAs(User::factory()->create());
        foreach (['creado', 'en_progreso', 'suspendido', 'hecho', 'inventado'] as $destino) {
            $this->patchJson('/tareas/'.$tarea->id.'/mover', ['estado' => $destino, 'version' => 1])->assertUnprocessable();
        }
        $this->assertSame('creado', $tarea->fresh()->estado);
        $this->assertSame(1, $tarea->fresh()->version);
        $this->assertNull($tarea->fresh()->comprometida_en);
        Event::assertNotDispatched(TareaMovida::class);
    }

    public function test_http_exige_una_version_entera_positiva(): void
    {
        $tarea = Tarea::factory()->create();
        $this->actingAs(User::factory()->create());
        foreach ([null, 0, -1, 1.5, 'abc'] as $version) {
            $this->patchJson('/tareas/'.$tarea->id.'/mover', ['estado' => 'por_hacer', 'version' => $version])
                ->assertUnprocessable()->assertJsonValidationErrors('version');
        }
        $this->assertSame(1, $tarea->fresh()->version);
    }

    public function test_no_se_puede_regresar_desde_por_hacer(): void
    {
        $tarea = Tarea::factory()->create(['estado' => 'por_hacer', 'version' => 2, 'comprometida_en' => now()]);
        $antes = $tarea->fresh()->getAttributes();
        $this->actingAs(User::factory()->create())->patchJson('/tareas/'.$tarea->id.'/mover', ['estado' => 'creado', 'version' => 2])->assertUnprocessable();
        $this->assertSame($antes, $tarea->fresh()->getAttributes());
    }

    public function test_eliminar_en_los_dos_estados_permitidos_emite_el_id(): void
    {
        Event::fake([TareaEliminada::class]);
        $this->actingAs(User::factory()->create());
        foreach (['creado', 'por_hacer'] as $estado) {
            $tarea = Tarea::factory()->create(['estado' => $estado]);
            $this->deleteJson('/tareas/'.$tarea->id)->assertOk()->assertJsonPath('tareaId', $tarea->id);
            $this->assertDatabaseMissing('tareas', ['id' => $tarea->id]);
        }
        Event::assertDispatchedTimes(TareaEliminada::class, 2);
    }

    public function test_servidor_impide_borrar_trabajo_iniciado_aunque_se_force_la_peticion(): void
    {
        Event::fake([TareaEliminada::class]);
        $this->actingAs(User::factory()->create());
        foreach (['en_progreso', 'suspendido', 'hecho'] as $estado) {
            $tarea = Tarea::factory()->create(['estado' => $estado]);
            $this->deleteJson('/tareas/'.$tarea->id)->assertUnprocessable();
            $this->assertDatabaseHas('tareas', ['id' => $tarea->id, 'estado' => $estado]);
        }
        Event::assertNotDispatched(TareaEliminada::class);
    }

    public function test_tarjetas_que_ya_no_existen_devuelven_un_rechazo_claro(): void
    {
        $this->actingAs(User::factory()->create());
        $this->deleteJson('/tareas/999999')->assertUnprocessable();
        $this->patchJson('/tareas/999999/mover', ['estado' => 'por_hacer', 'version' => 1])->assertUnprocessable();
    }

    public function test_canal_privado_solo_autoriza_tablero_a_usuarios_autenticados(): void
    {
        config(['broadcasting.default' => 'reverb']);
        Broadcast::channel('tablero', fn (User $user): bool => true);
        $this->actingAs(User::factory()->create());
        $this->postJson('/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-tablero'])
            ->assertOk()->assertJsonStructure(['auth']);
        $this->postJson('/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-otro'])->assertForbidden();
    }

    public function test_fallo_de_difusion_al_crear_devuelve_exito_con_aviso_y_conserva_la_tarjeta(): void
    {
        Event::listen(TareaCreada::class, function () {
            throw new \RuntimeException('Fallo de difusión simulado');
        });
        $respuesta = $this->actingAs(User::factory()->create())->postJson('/tareas', ['titulo' => 'Guardada'])
            ->assertCreated()->assertJsonPath('tarea.titulo', 'Guardada');
        $this->assertStringContainsString('quedó guardado', $respuesta->json('aviso'));
        $this->assertDatabaseHas('tareas', ['titulo' => 'Guardada']);
    }

    public function test_fallo_de_difusion_no_revierte_un_movimiento_o_borrado_confirmado(): void
    {
        Event::listen(TareaMovida::class, function () {
            throw new \RuntimeException('Fallo simulado');
        });
        Event::listen(TareaEliminada::class, function () {
            throw new \RuntimeException('Fallo simulado');
        });
        $tarea = Tarea::factory()->create();
        $this->actingAs(User::factory()->create());
        $movida = $this->patchJson('/tareas/'.$tarea->id.'/mover', ['estado' => 'por_hacer', 'version' => 1])->assertOk();
        $this->assertNotNull($movida->json('aviso'));
        $this->assertSame('por_hacer', $tarea->fresh()->estado);
        $borrada = $this->deleteJson('/tareas/'.$tarea->id)->assertOk();
        $this->assertNotNull($borrada->json('aviso'));
        $this->assertDatabaseMissing('tareas', ['id' => $tarea->id]);
    }

    public function test_comando_reutiliza_el_servicio_registra_autor_y_emite_evento(): void
    {
        Event::fake([TareaMovida::class]);
        $tarea = Tarea::factory()->create();
        $actor = User::factory()->create();
        $this->artisan('tareas:mover', ['tarea' => (string) $tarea->id, 'estado' => 'por_hacer', 'usuario' => (string) $actor->id])
            ->expectsOutput('Tarea movida.')->assertSuccessful();
        $this->assertSame('por_hacer', $tarea->fresh()->estado);
        $this->assertSame($actor->id, $tarea->fresh()->movida_por);
        Event::assertDispatched(TareaMovida::class);
    }

    public function test_comando_rechaza_ids_destinos_y_usuarios_invalidos_con_salida_uno(): void
    {
        $tarea = Tarea::factory()->create();
        $actor = User::factory()->create();
        foreach ([
            ['abc', 'por_hacer', $actor->id],
            [$tarea->id, 'por_hacer', 0],
            [$tarea->id, 'por_hacer', 999999],
            [999999, 'por_hacer', $actor->id],
            [$tarea->id, 'hecho', $actor->id],
        ] as [$id, $estado, $usuario]) {
            $this->artisan('tareas:mover', ['tarea' => (string) $id, 'estado' => $estado, 'usuario' => (string) $usuario])->assertExitCode(1);
        }
        $this->assertSame(1, $tarea->fresh()->version);
    }
}
