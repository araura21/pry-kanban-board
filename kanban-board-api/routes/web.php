<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TableroController;
use App\Http\Controllers\TareaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

require __DIR__.'/channels.php';

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'entrar'])->middleware('throttle:10,1');
    Route::view('/registro', 'auth.registro')->name('registro');
    Route::post('/registro', [AuthController::class, 'registrar'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [TableroController::class, 'index'])->name('tablero');
    Route::post('/logout', [AuthController::class, 'salir'])->name('logout');
    Route::post('/tareas', [TareaController::class, 'crear']);
    Route::delete('/tareas/{tarea}', [TareaController::class, 'eliminar'])->whereNumber('tarea');
    Route::patch('/tareas/{tarea}/mover', [TareaController::class, 'mover'])->whereNumber('tarea');
    Route::post('/broadcasting/auth', fn (Request $request) => Broadcast::auth($request));
    Route::view('/sala', 'sala')->name('sala');
});
