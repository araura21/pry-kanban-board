'use strict';

/**
 * Componente compartido de notificaciones. Se carga en el layout, así que
 * cualquier página o script puede usarlo sin repetir código ni estilos.
 * Todas las notificaciones salen como tarjetas arriba a la derecha.
 *
 * 1) Respuesta a lo que hizo el propio usuario. Solo hay una a la vez:
 *    cada mensaje nuevo reemplaza al anterior.
 *      Notificaciones.exito('Tarjeta creada.');            // se cierra sola
 *      Notificaciones.error('No se pudo guardar.', { accion: Notificaciones.recargar('Recargar tablero') });
 *      Notificaciones.aviso('Se guardó, pero sin conexión en vivo.');
 *    Los errores y avisos se quedan hasta que el usuario los cierra o llega otro mensaje.
 *
 * 2) Cambios hechos por otras personas. Se apilan y se cierran solos.
 *      Notificaciones.flotante('Alex creó «Tarea X»');
 *
 * Para que los textos tengan siempre la misma forma, usa Notificaciones.accion():
 *      Notificaciones.accion('Alex', 'movió', 'Tarea X', 'a Por hacer')
 *      → "Alex movió «Tarea X» a Por hacer"
 *
 * Tipos: 'exito', 'error', 'aviso' e 'info'. Pasar el ratón por encima pausa el cierre.
 */
window.Notificaciones = (() => {
    const TIPOS = {
        exito: { titulo: 'Listo', icono: 'M5 12.5l4.5 4.5L19 7.5', duracion: 3500 },
        error: { titulo: 'No se pudo completar', icono: 'M7 7l10 10M17 7L7 17', duracion: null },
        aviso: { titulo: 'Atención', icono: 'M12 6v8M12 17.5v.5', duracion: null },
        info: { titulo: 'Actividad del equipo', icono: 'M4 7h16M4 12h10M4 17h13', duracion: 5000 },
    };
    let propia = null;

    function elemento(etiqueta, clase, texto = '') {
        const nodo = document.createElement(etiqueta);
        nodo.className = clase;
        nodo.textContent = texto;
        return nodo;
    }

    function pila() {
        let contenedor = document.getElementById('notificaciones');
        if (!contenedor) {
            contenedor = elemento('section', 'notificaciones');
            contenedor.id = 'notificaciones';
            contenedor.setAttribute('aria-label', 'Notificaciones');
            document.body.append(contenedor);
        }
        return contenedor;
    }

    function icono(trazo) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('aria-hidden', 'true');
        const ruta = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        ruta.setAttribute('d', trazo);
        svg.append(ruta);
        const marco = elemento('span', 'notificacion-icono');
        marco.append(svg);
        return marco;
    }

    function cerrar(tarjeta) {
        if (!tarjeta.isConnected || tarjeta.classList.contains('saliendo')) return;
        if (tarjeta === propia) propia = null;
        tarjeta.classList.add('saliendo');
        // Si no hay animación (movimiento reducido), se quita de inmediato.
        const quitar = () => tarjeta.remove();
        tarjeta.addEventListener('animationend', quitar, { once: true });
        setTimeout(quitar, 400);
    }

    /** Crea y muestra una tarjeta. Devuelve el elemento por si se quiere cerrar antes. */
    function mostrar(texto, tipo = 'info', { accion = null, duracion } = {}) {
        const config = TIPOS[tipo] ?? TIPOS.info;
        const tiempo = duracion === undefined ? config.duracion : duracion;

        const tarjeta = elemento('div', 'notificacion ' + tipo);
        tarjeta.setAttribute('role', tipo === 'error' ? 'alert' : 'status');

        const cuerpo = elemento('div', 'notificacion-cuerpo');
        cuerpo.append(elemento('p', 'notificacion-titulo', config.titulo), elemento('p', 'notificacion-texto', texto));
        if (accion) {
            const boton = elemento('button', 'notificacion-accion', accion.texto);
            boton.type = 'button';
            boton.addEventListener('click', accion.alPulsar);
            cuerpo.append(boton);
        }

        const botonCerrar = elemento('button', 'notificacion-cerrar', '×');
        botonCerrar.type = 'button';
        botonCerrar.setAttribute('aria-label', 'Cerrar notificación');
        botonCerrar.addEventListener('click', () => cerrar(tarjeta));

        tarjeta.append(icono(config.icono), cuerpo, botonCerrar);

        if (tiempo) {
            // La barra se vacía en `tiempo` ms; al terminar, la tarjeta se cierra.
            // Pasar el ratón por encima pausa la animación y, con ella, el cierre.
            const progreso = elemento('span', 'notificacion-progreso');
            progreso.style.animationDuration = tiempo + 'ms';
            progreso.addEventListener('animationend', () => cerrar(tarjeta));
            tarjeta.append(progreso);
        }

        pila().prepend(tarjeta);
        return tarjeta;
    }

    /** Respuesta a una acción propia: reemplaza la anterior. */
    function mensaje(texto, tipo = 'error', opciones = {}) {
        if (propia) cerrar(propia);
        propia = mostrar(texto, tipo, opciones);
        return propia;
    }

    /** Aviso de un cambio hecho por otra persona: se apila. */
    function flotante(texto, tipo = 'info', opciones = {}) {
        return mostrar(texto, tipo, opciones);
    }

    /** Texto estándar para contar quién hizo qué: "Nombre verbo «título» complemento". */
    function accion(actor, verbo, titulo, complemento = '') {
        return [(actor || 'Alguien'), verbo, '«' + titulo + '»', complemento].filter(Boolean).join(' ');
    }

    /** Acción lista para usar: un botón que recarga la página. */
    function recargar(texto = 'Recargar página') {
        return { texto, alPulsar: () => window.location.reload() };
    }

    return {
        mensaje,
        flotante,
        accion,
        recargar,
        cerrar,
        exito: (texto, opciones) => mensaje(texto, 'exito', opciones),
        error: (texto, opciones) => mensaje(texto, 'error', opciones),
        aviso: (texto, opciones) => mensaje(texto, 'aviso', opciones),
    };
})();
