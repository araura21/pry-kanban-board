'use strict';

function iniciarConexion(alRecuperar = () => {}) {
    const config = window.tableroConfig;
    const indicador = document.getElementById('conexion');
    const texto = document.getElementById('conexion-texto');
    function estado(mensaje, valor) {
        texto.textContent = mensaje;
        indicador.dataset.estado = valor;
    }

    if (!window.Echo || !window.Pusher) {
        estado('No se pudo cargar la conexión en vivo. Recarga la página.', 'error');
        return null;
    }

    const echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.host,
        wsPort: config.port,
        wssPort: config.port,
        forceTLS: window.location.protocol === 'https:',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } },
    });
    window.echoTablero = echo;

    let estuvoConectado = false;
    let necesitaRecarga = false;
    echo.connector.pusher.connection.bind('state_change', ({ current }) => {
        if (current === 'connected') {
            estado('Autorizando acceso…', 'esperando');
        } else {
            if (estuvoConectado) necesitaRecarga = true;
            estado('Sin conexión en vivo. Intentando reconectar…', 'error');
        }
    });

    const canal = echo.private('tablero')
        .subscribed(() => {
            estuvoConectado = true;
            estado('En vivo', 'conectado');
            if (necesitaRecarga) {
                alRecuperar();
                necesitaRecarga = false;
            }
        })
        .error(() => estado('No se pudo autorizar el acceso. Vuelve a iniciar sesión.', 'error'));

    document.getElementById('cerrar-sesion')?.addEventListener('submit', () => echo.disconnect());
    window.addEventListener('pagehide', () => echo.disconnect());

    return canal;
}
