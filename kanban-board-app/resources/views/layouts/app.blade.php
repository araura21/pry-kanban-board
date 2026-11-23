<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Tablero') · Kanban</title>
    <link rel="preload" href="{{ asset('fonts/space-grotesk-regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/kanban.css') }}">
</head>
<body class="@yield('clase-pagina')">
    <a class="saltar" href="#contenido">Ir al contenido</a>
    <header class="cabecera">
        <a class="marca" href="{{ route('tablero') }}" aria-label="Kanban, ir al tablero">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="3"/><path d="M9 3v18M15 3v18M5.5 7h1M11.5 11h1M17.5 7h1"/>
            </svg>
            Kanban
        </a>
        @auth
            <div class="sesion">
                <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="nombre-usuario">{{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('logout') }}" id="cerrar-sesion">
                    @csrf
                    <button class="boton-texto" type="submit">Cerrar sesión</button>
                </form>
            </div>
        @endauth
    </header>
    @yield('contenido')
    @stack('scripts')
</body>
</html>
