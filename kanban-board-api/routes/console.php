<?php

use App\Exceptions\ReglaTableroException;
use App\Services\TareaService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('tareas:mover {tarea} {estado} {usuario}', function () {
    $tarea = $this->argument('tarea');
    $usuario = $this->argument('usuario');
    if (! ctype_digit($tarea) || (int) $tarea < 1 || ! ctype_digit($usuario) || (int) $usuario < 1) {
        $this->error('La tarea y el usuario deben ser números enteros mayores que cero.');

        return 1;
    }
    try {
        $resultado = app(TareaService::class)->mover((int) $tarea, $this->argument('estado'), (int) $usuario);
    } catch (ReglaTableroException $error) {
        $this->error($error->getMessage());

        return 1;
    }
    $this->info('Tarea movida.');
    if ($resultado['aviso']) {
        $this->warn($resultado['aviso']);
    }

    return 0;
})->purpose('Mueve una tarea con las reglas del tablero y registra al usuario indicado.');
