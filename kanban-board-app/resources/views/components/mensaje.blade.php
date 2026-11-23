@props(['tipo' => null])
{{--
    Mensaje dentro de la página que genera el servidor, por ejemplo los errores del login:
        <x-mensaje tipo="error">{{ $errors->first() }}</x-mensaje>
    Para los avisos desde JavaScript usa Notificaciones (public/js/notificaciones.js),
    que los muestra como tarjetas arriba a la derecha.
--}}
<div {{ $attributes->class(['mensaje', $tipo]) }} role="{{ $tipo === 'error' ? 'alert' : 'status' }}">{{ $slot }}</div>
