<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaTableroException;
use App\Services\TareaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    public function crear(Request $request, TareaService $servicio): JsonResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:100'],
            'creadaEn' => ['nullable', 'string', 'date'],
        ]);
        try {
            return response()->json($servicio->crear($request->user()->id, $datos['titulo'], $datos['creadaEn'] ?? null), 201);
        } catch (ReglaTableroException $error) {
            return response()->json(['error' => $error->getMessage()], 422);
        }
    }

    public function eliminar(Request $request, int $tarea, TareaService $servicio): JsonResponse
    {
        try {
            return response()->json($servicio->eliminar($tarea, $request->user()->id));
        } catch (ReglaTableroException $error) {
            return response()->json(['error' => $error->getMessage()], 422);
        }
    }

    public function mover(Request $request, int $tarea, TareaService $servicio): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
        ]);
        try {
            return response()->json($servicio->mover($tarea, $datos['estado'], $request->user()->id, (int) $datos['version']));
        } catch (ReglaTableroException $error) {
            return response()->json(['error' => $error->getMessage()], 422);
        }
    }
}
