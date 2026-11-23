@extends('layouts.app')
@section('titulo', 'Crear una cuenta')
@section('clase-pagina', 'pagina-acceso')
@section('contenido')
<main id="contenido" class="acceso">
    <h1>Un lugar para el equipo</h1>
    <p class="texto-suave">Crea tu cuenta y entra al tablero compartido.</p>
    <form method="post" action="{{ route('registro') }}" class="formulario-acceso">
        @csrf
        @if ($errors->any())
            <div class="mensaje error" role="alert">{{ $errors->first() }}</div>
        @endif
        <label for="name">Nombre</label>
        <input id="name" name="name" value="{{ old('name') }}" maxlength="100" autocomplete="name" required autofocus>
        <label for="email">Correo</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
        <label for="password">Contraseña <span class="texto-suave">(mínimo 6 caracteres)</span></label>
        <input id="password" name="password" type="password" minlength="6" autocomplete="new-password" required>
        <label for="password_confirmation">Repite la contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="6" autocomplete="new-password" required>
        <button class="boton-principal" type="submit">Crear cuenta</button>
    </form>
    <p class="pie-acceso">¿Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesión</a></p>
</main>
@endsection
