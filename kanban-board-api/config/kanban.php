<?php

return [
    'websocket_host' => env('REVERB_PUBLIC_HOST'),
    'websocket_port' => (int) env('REVERB_PUBLIC_PORT', 8080),
    // Reservados para los módulos 3 y 5.
    'limite_wip' => (int) env('KANBAN_LIMITE_WIP', 3),
    'archivar_minutos' => (int) env('KANBAN_ARCHIVAR_MINUTOS', 2),
];
