<?php

namespace App\Services;

use App\Events\TareaCreada;
use App\Events\TareaEliminada;
use App\Events\TareaMovida;
use App\Exceptions\ReglaTableroException;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TareaService
{
    public const COLUMNAS = [
        'creado' => 'Creado',
        'por_hacer' => 'Por hacer',
        'en_progreso' => 'En progreso',
        'suspendido' => 'Suspendido',
        'hecho' => 'Hecho',
    ];

    public const TRANSICIONES = ['creado' => ['por_hacer']];

    public const ELIMINABLES = ['creado', 'por_hacer'];

    public function crear(int $usuarioId, string $titulo): array
    {
        $titulo = trim($titulo);
        if ($titulo === '' || mb_strlen($titulo) > 100) {
            throw new ReglaTableroException('Escribe un título de entre 1 y 100 caracteres.');
        }
        $this->exigirUsuario($usuarioId);
        $tarea = Tarea::create(['user_id' => $usuarioId, 'titulo' => $titulo]);
        $datos = $tarea->load(['creador', 'movidaPor'])->datosTablero();

        return ['tarea' => $datos, 'aviso' => $this->difundir(new TareaCreada($datos))];
    }

    public function eliminar(int $tareaId, ?int $usuarioId = null): array
    {
        $nombre = $usuarioId ? User::whereKey($usuarioId)->value('name') : null;
        DB::transaction(function () use ($tareaId) {
            $tarea = Tarea::lockForUpdate()->find($tareaId);
            if (! $tarea) {
                throw new ReglaTableroException('La tarea ya no existe. Recarga el tablero.');
            }
            if (! in_array($tarea->estado, self::ELIMINABLES, true)) {
                throw new ReglaTableroException('Solo puedes eliminar tareas de Creado o Por hacer.');
            }
            $tarea->delete();
        });

        return ['tareaId' => $tareaId, 'aviso' => $this->difundir(new TareaEliminada($tareaId, $nombre))];
    }

    public function mover(int $tareaId, string $estado, int $usuarioId, ?int $version = null): array
    {
        if (! array_key_exists($estado, self::COLUMNAS)) {
            throw new ReglaTableroException('La columna indicada no existe.');
        }
        $this->exigirUsuario($usuarioId);
        $tarea = DB::transaction(function () use ($tareaId, $estado, $usuarioId, $version) {
            $tarea = Tarea::lockForUpdate()->find($tareaId);
            if (! $tarea) {
                throw new ReglaTableroException('La tarea ya no existe. Recarga el tablero.');
            }
            if ($version !== null && $tarea->version !== $version) {
                throw new ReglaTableroException('La tarea cambió mientras la movías. Recarga el tablero.');
            }
            $this->exigirTransicion($tarea->estado, $estado);
            $tarea->estado = $estado;
            $tarea->movida_por = $usuarioId;
            $tarea->version++;
            $tarea->comprometida_en = now();
            $tarea->save();

            return $tarea;
        });
        $datos = $tarea->load(['creador', 'movidaPor'])->datosTablero();

        return ['tarea' => $datos, 'aviso' => $this->difundir(new TareaMovida($datos))];
    }

    private function exigirTransicion(string $origen, string $destino): void
    {
        if (! in_array($destino, self::TRANSICIONES[$origen] ?? [], true)) {
            throw new ReglaTableroException('Ese movimiento no está permitido. En esta entrega solo puedes pasar de Creado a Por hacer.');
        }
    }

    private function exigirUsuario(int $usuarioId): void
    {
        if (! User::whereKey($usuarioId)->exists()) {
            throw new ReglaTableroException('El usuario indicado no existe.');
        }
    }

    private function difundir(object $evento): ?string
    {
        try {
            $evento->dontBroadcastToCurrentUser();
            event($evento);

            return null;
        } catch (Throwable $error) {
            Log::warning('Cambio guardado sin difusión en vivo.', ['evento' => $evento::class, 'error' => $error->getMessage()]);

            return 'El cambio quedó guardado, pero no se pudo enviar en vivo. Recarga el tablero cuando vuelva la conexión; no repitas la operación.';
        }
    }
}
