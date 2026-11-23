@extends('layouts.app')
@section('titulo', 'Comprobar conexión')
@section('contenido')
<main id="contenido" class="acceso">
    <h1>Conexión en vivo</h1>
    <p class="texto-suave">Esta página comprueba la conexión con Reverb y la autorización al canal privado del equipo.</p>
    <p id="conexion" class="conexion" role="status" data-estado="esperando"><span class="punto" aria-hidden="true"></span><span id="conexion-texto">Conectando…</span></p>
    <p><a href="{{ route('tablero') }}">Volver al tablero</a></p>
</main>
@endsection
@push('scripts')
<script>
    window.tableroConfig = {{ Illuminate\Support\Js::from([
        'key' => config('broadcasting.connections.reverb.key'),
        'host' => config('kanban.websocket_host') ?: request()->getHost(),
        'port' => config('kanban.websocket_port'),
    ]) }};
</script>
@include('partials.websocket')
<script>iniciarConexion();</script>
@endpush
