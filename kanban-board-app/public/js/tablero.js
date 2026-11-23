'use strict';

const config = window.tableroConfig;
const tareas = new Map(config.tareas.map(tarea => [tarea.id, tarea]));
const eliminadas = new Set();
const ocupadas = new Set();
const formulario = document.getElementById('crear-tarea');
const titulo = document.getElementById('titulo');
let arrastre = null;

function mostrarMensaje(texto, tipo = 'error', recargar = false) {
    const mensaje = document.getElementById('mensaje');
    document.getElementById('mensaje-texto').textContent = texto;
    mensaje.className = 'mensaje ' + tipo;
    mensaje.hidden = false;
    document.getElementById('recargar').hidden = !recargar;
}

document.getElementById('recargar').addEventListener('click', () => window.location.reload());

function textoElemento(etiqueta, clase, texto) {
    const elemento = document.createElement(etiqueta);
    elemento.className = clase;
    elemento.textContent = texto;
    return elemento;
}

function dibujarTablero() {
    if (arrastre) return;
    const tarjetaConFoco = document.activeElement?.closest('.tarjeta');
    const idConFoco = tarjetaConFoco?.dataset.id;
    const accionConFoco = document.activeElement?.dataset.accion;
    const ordenadas = [...tareas.values()].sort((a, b) =>
        a.creadaEn.localeCompare(b.creadaEn) || a.id - b.id
    );
    for (const estado of Object.keys(config.columnas)) {
        const contenedor = document.getElementById('tarjetas-' + estado);
        const lista = ordenadas.filter(tarea => tarea.estado === estado);
        contenedor.replaceChildren();
        document.getElementById('cantidad-' + estado).textContent = lista.length;
        for (const tarea of lista) {
            const tarjeta = textoElemento('article', 'tarjeta', '');
            tarjeta.dataset.id = tarea.id;
            tarjeta.tabIndex = -1;
            const cabecera = textoElemento('div', 'cabecera-tarjeta', '');
            cabecera.append(textoElemento('span', 'tarjeta-id', '#' + tarea.id));
            if ((config.transiciones[tarea.estado] || []).length && !ocupadas.has(tarea.id)) {
                prepararArrastre(tarjeta, tarea);
                const agarre = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                agarre.setAttribute('viewBox', '0 0 16 18');
                agarre.setAttribute('aria-hidden', 'true');
                agarre.classList.add('agarre');
                for (const x of [5, 11]) {
                    for (const y of [4, 9, 14]) {
                        const punto = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                        punto.setAttribute('cx', x);
                        punto.setAttribute('cy', y);
                        punto.setAttribute('r', '1.2');
                        punto.setAttribute('fill', 'currentColor');
                        agarre.append(punto);
                    }
                }
                cabecera.append(agarre);
            }
            tarjeta.append(
                cabecera,
                textoElemento('h3', '', tarea.titulo),
                textoElemento('p', 'tarjeta-autor', 'Creada por ' + tarea.creador)
            );
            if (tarea.movidaPor) {
                tarjeta.append(textoElemento('p', 'tarjeta-autor', 'Movida por ' + tarea.movidaPor));
            }
            const acciones = textoElemento('div', 'tarjeta-acciones', '');
            for (const destino of config.transiciones[tarea.estado] || []) {
                const boton = textoElemento('button', 'accion-mover', 'Pasar a ' + config.columnas[destino]);
                boton.type = 'button';
                boton.dataset.accion = 'mover-' + destino;
                boton.disabled = ocupadas.has(tarea.id);
                boton.addEventListener('click', () => moverTarea(tarea, destino));
                acciones.append(boton);
            }
            if (config.eliminables.includes(tarea.estado)) {
                const boton = textoElemento('button', 'accion-eliminar', 'Eliminar');
                boton.type = 'button';
                boton.dataset.accion = 'eliminar';
                boton.disabled = ocupadas.has(tarea.id);
                boton.setAttribute('aria-label', 'Eliminar tarea ' + tarea.id + ': ' + tarea.titulo);
                boton.addEventListener('click', () => operar(tarea, 'DELETE', '/tareas/' + tarea.id));
                acciones.append(boton);
            }
            if (acciones.childElementCount) tarjeta.append(acciones);
            contenedor.append(tarjeta);
        }
        if (lista.length === 0) {
            contenedor.append(textoElemento('p', 'vacio', estado === 'creado' ? 'Las nuevas ideas empiezan aquí.' : 'Sin tareas por ahora.'));
        }
    }
    if (idConFoco) {
        const tarjetaNueva = document.querySelector('.tarjeta[data-id="' + idConFoco + '"]');
        const accionNueva = accionConFoco ? tarjetaNueva?.querySelector('[data-accion="' + accionConFoco + '"]') : null;
        if (accionNueva && !accionNueva.disabled) {
            accionNueva.focus({ preventScroll: true });
        } else if (tarjetaNueva) {
            tarjetaNueva.focus();
        } else {
            titulo.focus();
        }
    }
}

function recibirTarea(tarea) {
    const anterior = tareas.get(tarea.id);
    if (eliminadas.has(tarea.id) || (anterior && anterior.version >= tarea.version)) return;
    tareas.set(tarea.id, tarea);
    dibujarTablero();
    if (!arrastre) document.querySelector('.tarjeta[data-id="' + tarea.id + '"]')?.classList.add('actualizada');
}

function moverTarea(tarea, destino) {
    return operar(tarea, 'PATCH', '/tareas/' + tarea.id + '/mover', { estado: destino, version: tarea.version });
}

function destinoPermitido(destino) {
    return arrastre && (config.transiciones[arrastre.estado] || []).includes(destino);
}

function terminarArrastre() {
    if (!arrastre) return;
    arrastre = null;
    document.body.classList.remove('arrastrando');
    document.querySelectorAll('.columna').forEach(columna => {
        columna.classList.remove('destino-permitido', 'destino-activo');
    });
    dibujarTablero();
}

function prepararArrastre(tarjeta, tarea) {
    tarjeta.draggable = false;
    tarjeta.classList.add('arrastrable');
    tarjeta.setAttribute('aria-describedby', 'ayuda-arrastre');
    let inicio = null;
    tarjeta.addEventListener('pointerdown', evento => {
        if (!evento.isPrimary || arrastre) return;
        if (evento.button !== 0 || evento.target.closest('button, a, input') || ocupadas.has(tarea.id)) return;
        if (evento.pointerType === 'touch' && !evento.target.closest('.agarre')) return;
        inicio = { x: evento.clientX, y: evento.clientY, puntero: evento.pointerId };
        tarjeta.setPointerCapture(evento.pointerId);
    });
    tarjeta.addEventListener('pointermove', evento => {
        if (!inicio) return;
        const x = evento.clientX - inicio.x;
        const y = evento.clientY - inicio.y;
        if (!arrastre && Math.hypot(x, y) < 6) return;
        evento.preventDefault();
        if (!arrastre) {
            arrastre = { ...tarea };
            tarjeta.classList.add('en-arrastre');
            document.body.classList.add('arrastrando');
        }
        tarjeta.style.transform = 'translate(' + x + 'px, ' + y + 'px) rotate(-1deg)';
        const destino = document.elementFromPoint(evento.clientX, evento.clientY)?.closest('.columna');
        document.querySelectorAll('.columna').forEach(columna => {
            const permitido = Boolean(destinoPermitido(columna.dataset.estado));
            columna.classList.toggle('destino-permitido', permitido);
            columna.classList.toggle('destino-activo', permitido && columna === destino);
        });
    });
    tarjeta.addEventListener('pointerup', evento => {
        if (!inicio) return;
        inicio = null;
        tarjeta.releasePointerCapture(evento.pointerId);
        tarjeta.style.transform = '';
        const destino = document.elementFromPoint(evento.clientX, evento.clientY)?.closest('.columna')?.dataset.estado;
        const permitido = destinoPermitido(destino);
        const tareaArrastrada = arrastre;
        terminarArrastre();
        if (permitido) moverTarea(tareaArrastrada, destino);
    });
    function cancelar() {
        if (!inicio) return;
        inicio = null;
        tarjeta.style.transform = '';
        terminarArrastre();
    }
    tarjeta.addEventListener('pointercancel', cancelar);
    tarjeta.addEventListener('lostpointercapture', cancelar);
}

document.addEventListener('keydown', evento => {
    if (evento.key === 'Escape') terminarArrastre();
});

async function pedir(metodo, ruta, datos = {}) {
    let respuesta;
    try {
        respuesta = await fetch(ruta, {
            method: metodo,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(datos),
        });
    } catch {
        throw new Error('No se pudo confirmar el resultado. Recarga el tablero para comprobar si el cambio se guardó.');
    }
    if (respuesta.status === 401) {
        window.location.assign('/login');
        throw new Error('Tu sesión terminó. Inicia sesión otra vez.');
    }
    if (respuesta.status === 419) throw new Error('La sesión caducó. Recarga la página antes de continuar.');
    if (respuesta.status >= 500) throw new Error('No se pudo confirmar el cambio. Recarga el tablero para comprobar su estado.');
    const resultado = await respuesta.json();
    if (!respuesta.ok) {
        const validacion = resultado.errors ? Object.values(resultado.errors).flat()[0] : null;
        throw new Error(resultado.error || validacion || 'No se pudo realizar la operación.');
    }
    return resultado;
}

async function operar(tarea, metodo, ruta, datos = {}) {
    if (ocupadas.has(tarea.id)) return;
    ocupadas.add(tarea.id);
    dibujarTablero();
    try {
        const resultado = await pedir(metodo, ruta, datos);
        if (resultado.aviso) mostrarMensaje(resultado.aviso, 'aviso', true);
        else mostrarMensaje(metodo === 'DELETE' ? 'Tarjeta eliminada.' : 'Tarjeta comprometida: ahora está en Por hacer.', 'exito');
    } catch (error) {
        mostrarMensaje(error.message, 'error', true);
    } finally {
        ocupadas.delete(tarea.id);
        dibujarTablero();
    }
}

formulario.addEventListener('submit', async evento => {
    evento.preventDefault();
    const boton = formulario.querySelector('button');
    if (boton.disabled) return;
    if (!titulo.value.trim()) {
        mostrarMensaje('Escribe un título para la tarea.');
        titulo.focus();
        return;
    }
    boton.disabled = true;
    boton.textContent = 'Guardando…';
    try {
        const resultado = await pedir('POST', '/tareas', { titulo: titulo.value.trim() });
        titulo.value = '';
        if (resultado.aviso) mostrarMensaje(resultado.aviso, 'aviso', true);
        else mostrarMensaje('Tarjeta creada.', 'exito');
        titulo.focus();
    } catch (error) {
        mostrarMensaje(error.message, 'error', true);
    } finally {
        boton.disabled = false;
        boton.textContent = 'Crear tarjeta';
    }
});

dibujarTablero();
const canal = iniciarConexion(() => mostrarMensaje('La conexión volvió. Recarga el tablero para recuperar los cambios que pudiste perder.', 'aviso', true));
canal?.listen('TareaCreada', evento => recibirTarea(evento.tarea))
    .listen('TareaMovida', evento => recibirTarea(evento.tarea))
    .listen('TareaEliminada', evento => {
        eliminadas.add(evento.tareaId);
        tareas.delete(evento.tareaId);
        dibujarTablero();
    });
