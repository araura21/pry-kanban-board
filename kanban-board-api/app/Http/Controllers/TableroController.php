<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Services\TareaService;
use Illuminate\View\View;

class TableroController extends Controller
{
    public function index(): View
    {
        return view('tablero', [
            'tareas' => Tarea::with(['creador', 'movidaPor'])->orderBy('created_at')->orderBy('id')->get()->map->datosTablero(),
            'columnas' => TareaService::COLUMNAS,
            'transiciones' => TareaService::TRANSICIONES,
            'eliminables' => TareaService::ELIMINABLES,
        ]);
    }
}
