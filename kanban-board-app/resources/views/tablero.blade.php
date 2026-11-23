@extends('layouts.app')
@section('titulo', 'Tablero del equipo')
@section('contenido')
<main id="contenido" class="pagina-tablero">
    <div class="encabezado-tablero">
        <div>
            <h1>Tablero del equipo</h1>
            <p class="texto-suave">De la idea al compromiso, una tarjeta a la vez.</p>
        </div>
        <p class="conexion" id="conexion" role="status" data-estado="esperando">
            <span class="punto" aria-hidden="true"></span><span id="conexion-texto">Conectando…</span>
        </p>
    </div>
    <form id="crear-tarea" class="crear-tarea">
        <div class="campo-titulo">
            <label for="titulo">Nueva tarea</label>
            <input id="titulo" name="titulo" maxlength="100" required placeholder="Por ejemplo: preparar la demostración" autocomplete="off" aria-describedby="ayuda-titulo">
        </div>
        <button class="boton-principal" type="submit">Crear tarjeta</button>
        <p id="ayuda-titulo" class="ayuda">Hasta 100 caracteres. Se añadirá a Creado.</p>
    </form>
    <noscript><p class="mensaje error">Activa JavaScript para ver y usar el tablero.</p></noscript>
    <p class="politica" id="ayuda-arrastre">Arrastra de <strong>Creado</strong> a <strong>Por hacer</strong> o usa el botón de la tarjeta. Puedes eliminarla mientras no haya comenzado.</p>
    <section class="tablero" aria-label="Columnas del tablero" tabindex="0">
        @foreach ($columnas as $estado => $nombre)
            <section class="columna" data-estado="{{ $estado }}" aria-labelledby="columna-{{ $estado }}">
                <header class="columna-cabecera">
                    <h2 id="columna-{{ $estado }}">{{ $nombre }}</h2>
                    <span class="cantidad" id="cantidad-{{ $estado }}" aria-label="Cantidad de tareas">0</span>
                </header>
                <div class="tarjetas" id="tarjetas-{{ $estado }}"></div>
            </section>
        @endforeach
    </section>
    <p class="pie-tablero">Las tarjetas se muestran en orden de creación.</p>
</main>
@endsection
@push('scripts')
<script>
    window.tableroConfig = {{ Illuminate\Support\Js::from([
        'tareas' => $tareas,
        'columnas' => $columnas,
        'transiciones' => $transiciones,
        'eliminables' => $eliminables,
        'key' => config('broadcasting.connections.reverb.key'),
        'host' => config('kanban.websocket_host') ?: request()->getHost(),
        'port' => config('kanban.websocket_port'),
    ]) }};
</script>
@include('partials.websocket')
<script src="{{ asset('js/tablero.js') }}"></script>
@endpush
