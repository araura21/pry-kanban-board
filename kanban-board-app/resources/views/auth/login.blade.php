@extends('layouts.app')
@section('titulo', 'Iniciar sesión')
@section('clase-pagina', 'pagina-acceso')
@section('contenido')
<main id="contenido" class="acceso">
    <h1>Vuelve a tu tablero</h1>
    <p class="texto-suave">Inicia sesión para trabajar con tu equipo.</p>
    <form method="post" action="{{ route('login') }}" class="formulario-acceso">
        @csrf
        @if ($errors->any())
            <x-mensaje tipo="error">{{ $errors->first() }}</x-mensaje>
        @endif
        <label for="email">Correo</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="boton-principal" type="submit">Iniciar sesión</button>
    </form>
    <p class="pie-acceso">¿Primera vez aquí? <a href="{{ route('registro') }}">Crear una cuenta</a></p>
</main>
@endsection
