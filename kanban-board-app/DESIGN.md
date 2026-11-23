---
name: "Tablero Kanban del equipo"
description: "Sistema visual de la interfaz académica compartida en español."
colors:
  fondo: "#f8f9f6"
  papel: "#ffffff"
  columna: "#eef1ec"
  tinta: "#202b25"
  suave: "#56645a"
  linea: "#d5dcd3"
  verde: "#225c43"
  verde-hover: "#174330"
  error: "#923d32"
  aviso: "#77551a"
  campo-borde: "#aab7ac"
  foco: "#357557"
  error-fondo: "#fff6f3"
  error-borde: "#d9b5ae"
  aviso-fondo: "#fffaf0"
  aviso-borde: "#dbcba8"
typography:
  headline:
    fontFamily: "Segoe UI, system-ui, sans-serif"
    fontSize: "30px"
    fontWeight: 650
    lineHeight: 1.2
    letterSpacing: "-.025em"
  body:
    fontFamily: "Segoe UI, system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: 400
    lineHeight: 1.5
  brand:
    fontFamily: "Segoe UI, system-ui, sans-serif"
    fontSize: "20px"
    fontWeight: 700
    lineHeight: 1.5
    letterSpacing: "-.02em"
  column-title:
    fontFamily: "Segoe UI, system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: 650
    lineHeight: 1.5
  card-title:
    fontFamily: "Segoe UI, system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: 600
    lineHeight: 1.4
  label:
    fontFamily: "Segoe UI, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 600
    lineHeight: 1.5
  compact:
    fontFamily: "Segoe UI, system-ui, sans-serif"
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.5
  metadata:
    fontFamily: "Segoe UI, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 400
    lineHeight: 1.5
  action:
    fontFamily: "Segoe UI, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 600
    lineHeight: 1.5
  button:
    fontFamily: "Segoe UI, system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: 600
    lineHeight: 1.5
rounded:
  control: "6px"
  column: "10px"
  card: "12px"
spacing:
  step-4: "4px"
  step-8: "8px"
  step-10: "10px"
  step-12: "12px"
  step-14: "14px"
  step-16: "16px"
  step-20: "20px"
  step-24: "24px"
  step-32: "32px"
  step-40: "40px"
components:
  button-primary:
    backgroundColor: "{colors.verde}"
    textColor: "{colors.papel}"
    typography: "{typography.button}"
    rounded: "{rounded.control}"
    padding: "8px 14px"
  button-primary-hover:
    backgroundColor: "{colors.verde-hover}"
  button-text:
    textColor: "{colors.verde}"
    rounded: "{rounded.control}"
    padding: "8px 4px"
  action-move:
    textColor: "{colors.verde}"
    typography: "{typography.action}"
    rounded: "{rounded.control}"
    padding: "6px 0"
  action-delete:
    textColor: "{colors.error}"
    typography: "{typography.metadata}"
    rounded: "{rounded.control}"
    padding: "6px 0"
  input:
    backgroundColor: "{colors.papel}"
    textColor: "{colors.tinta}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "10px 12px"
    width: "100%"
  card:
    backgroundColor: "{colors.papel}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.card}"
    padding: "14px"
  column:
    backgroundColor: "{colors.columna}"
    rounded: "{rounded.column}"
    padding: "12px"
  navigation:
    backgroundColor: "{colors.papel}"
    textColor: "{colors.tinta}"
    padding: "16px 32px"
  message-error:
    backgroundColor: "{colors.error-fondo}"
    textColor: "{colors.error}"
    rounded: "{rounded.control}"
    padding: "12px 16px"
  message-warning:
    backgroundColor: "{colors.aviso-fondo}"
    textColor: "{colors.aviso}"
    rounded: "{rounded.control}"
    padding: "12px 16px"
---
# Design System: Tablero Kanban del equipo

## Overview

**Creative North Star: "Tablero académico compartido"**

El sistema conserva el tablero de cinco columnas entregado para la clase. La interfaz es sobria, legible y orientada al uso diario: fondo claro, columnas suaves, tarjetas blancas con borde y verde oscuro para las acciones. El acceso y el registro comparten esta misma presentación.

La jerarquía depende del tamaño, el peso, el espacio y el contraste. No existe un comp aprobado ni una identidad visual adicional elegida: este documento registra la implementación de Blade, CSS y JavaScript, sin proponer una marca nueva.

**Key Characteristics:**
- Cinco columnas horizontales y tarjetas de lectura directa.
- Verde oscuro para la acción y la conexión activa; texto para distinguir cada estado.
- Tipografía de interfaz y superficies planas con bordes suaves.
- Estados de espera, error y recuperación explícitos; foco conservado al actualizar tarjetas.

La fuente visual es `public/css/kanban.css`, aplicada desde `resources/views/layouts/app.blade.php` a las vistas de acceso, registro y tablero. Las tarjetas y su conservación del foco se implementan en `public/js/tablero.js`. Este registro reconcilia la descripción inicial de DESIGN.md con esos valores reales. Los tokens del frontmatter son normativos; `.impeccable/design.json` añade estados, fragmentos y comportamiento que ese formato no representa.

## Colors

La paleta combina verdes apagados, neutros claros y texto oscuro. Los nombres de los diez colores base conservan las propiedades CSS existentes; los colores auxiliares describen valores literales de los mismos componentes.

### Primary

- **Verde de acción** (`verde`): botones principales, enlaces, acción de compromiso, marca y conexión activa.
- **Verde de interacción** (`verde-hover`): respuesta al pasar el puntero por botones principales y enlaces de acción.
- **Verde de foco** (`foco`): contorno de teclado compartido por controles, enlaces y el tablero desplazable.

### Secondary

- **Rojo de error** (`error`): mensajes de error, conexión fallida y acción de eliminar. `error-fondo` y `error-borde` delimitan el mensaje escrito.
- **Ocre de aviso** (`aviso`): recuperación y resultado guardado que necesita recarga; `aviso-fondo` y `aviso-borde` acompañan el aviso.

### Neutral

- **Fondo claro** (`fondo`): lienzo exterior.
- **Papel blanco** (`papel`): encabezado, campos, tarjetas y mensajes neutros.
- **Columna suave** (`columna`): superficie agrupadora de cada estado.
- **Tinta** (`tinta`): títulos y contenido principal.
- **Texto secundario** (`suave`): ayuda, autor, identificador, contadores y estados vacíos.
- **Línea suave** (`linea`): borde de tarjeta, separación del encabezado y acciones.
- **Borde de campo** (`campo-borde`): delimitación del input en reposo; al pasar el puntero usa `suave`.

**The Action and State Rule.** El verde identifica acciones disponibles y conexión activa. El rojo identifica error o eliminación; el aviso usa ocre. Acompañar siempre el color con texto.

Las rampas de color del sidecar se sintetizan en OKLCH para previsualizar la paleta; no son una escala usada en la interfaz ni autorizan colores adicionales en producción. La selección de texto, el scrollbar, el punto de espera y el destello de entrada conservan sus valores puntuales en la hoja de estilo; no se convierten en nuevas familias reutilizables.

## Typography

**Body Font:** Segoe UI, con `system-ui` y `sans-serif` como alternativas. No se carga una fuente externa. No hay un rol display independiente.

**Character:** tipografía de interfaz compartida, con títulos cortos y metadatos discretos. Los números de cantidad e identificador usan cifras tabulares.

### Hierarchy

- **Headline**: título de página; el tamaño reducido a (26px) se aplica en el breakpoint estrecho.
- **Brand**: nombre Kanban del encabezado.
- **Column title**: nombre de cada columna.
- **Card title**: contenido de tarea, con saltos de línea conservados.
- **Body / Button**: texto general y botones principales; el botón usa peso seminegrita.
- **Label**: etiqueta del campo de nueva tarea. Las etiquetas de acceso heredan el cuerpo.
- **Compact**: conexión, política, contador y estado vacío.
- **Metadata / Action**: identificador, autor, ayuda y acciones de tarjeta. Eliminar conserva peso normal.

Los valores exactos de tamaño, peso, altura y espaciado de letras están en el frontmatter. Los roles son una extracción descriptiva de las reglas actuales, no clases nuevas añadidas al código.

**The Shared Type Rule.** Acceso, registro y tablero usan la misma familia de interfaz. La jerarquía se obtiene con los roles registrados, sin introducir una voz de exhibición.

## Layout

El encabezado es una fila flexible con marca a la izquierda y sesión a la derecha, altura mínima (72px), separación (16px) y borde inferior. El contenido del tablero se centra con ancho máximo (1640px) y padding superior/lateral/inferior (40px 32px 24px). El formulario coloca un campo de hasta (600px) junto al botón; la ayuda ocupa ambas columnas.

El tablero es una cuadrícula de cinco columnas iguales con ancho mínimo (220px) y separación (12px). Solo el tablero habilita desplazamiento horizontal. Cada columna tiene altura mínima (370px), padding (12px), cabecera con contador y una lista de tarjetas separadas por (10px). No se transforman en cinco filas al reducir el ancho.

El acceso y el registro comparten un contenedor de ancho `min(420px, calc(100% - 40px))`, margen superior/inferior (72px), campos en cuadrícula y separación (8px). La alternativa de acceso o registro sigue al formulario.

En el breakpoint de ancho máximo (700px), el encabezado puede envolver, el contenido usa padding (28px 20px 20px), el título pasa al tamaño reducido, la conexión queda debajo del título y el formulario ocupa una columna. El tablero usa `repeat(5, minmax(250px, 78vw))`, con ajuste de desplazamiento por proximidad al inicio de las columnas. El margen superior del acceso se reduce a (40px).

El espaciado real incluye pasos de (4, 8, 10, 12, 14, 16, 20, 24, 32 y 40px). No es una escala estricta de múltiplos de cuatro. Las capturas válidas de revisión corresponden a escritorio de (1265px) y ventana de (905px); el comportamiento por debajo de (700px) se describe desde CSS y no se declara visualmente verificado.

## Elevation & Depth

El sistema es plano: no tiene `box-shadow`. La profundidad se entiende por el cambio entre lienzo, columna y tarjeta, más bordes finos. Los mensajes usan fondos semánticos claros, sin elevarse sobre el tablero. El foco es un contorno de interacción, no una sombra.

**The Flat Surface Rule.** Las superficies se separan por fondo y borde. No añadir sombras para jerarquizar componentes del tablero.

## Shapes

Controles y mensajes comparten la esquina `control`; las columnas usan `column` y las tarjetas `card`. Las esquinas son suaves y las superficies están delimitadas con borde fino (1px) cuando lo necesita su función. El punto de conexión es un círculo (8px). La marca utiliza un SVG lineal de tablero, sin imagen o fuente de iconos.

## Components

### Buttons

Botones directos, con texto de la acción.

- **Primary:** verde con texto blanco, peso seminegrita, padding del token y altura mínima (40px). Crear tarjeta tiene altura mínima (44px).
- **Text:** fondo transparente, texto verde subrayado y padding horizontal reducido. Se usa para cerrar sesión y recargar.
- **Card actions:** mover en verde seminegrita; eliminar en rojo con peso normal. Altura mínima real (36px), texto pequeño y subrayado al pasar el puntero.
- **Focus:** contorno sólido (3px) y separación (3px). El estado deshabilitado reduce la opacidad a (0.6) y usa cursor de espera; las operaciones no se duplican mientras están ocupadas.

### Cards / Containers

Tarjetas blancas y columnas suaves, sin sombras. La tarjeta usa borde `linea`, padding del token y ajuste de palabras largas. El identificador precede al título; autor y posible autor del movimiento siguen al contenido. Las acciones se separan con un borde superior, margen (14px), padding superior (8px) y fila que puede envolver.

La cabecera de columna mantiene nombre y cantidad en los extremos. El estado vacío es texto secundario centrado, con padding (40px 8px). No hay un componente chip o etiqueta independiente.

### Inputs / Fields

Campos blancos de ancho completo con borde `campo-borde`, texto `tinta`, cursor `verde` y padding del token. El borde cambia a `suave` al pasar el puntero. El foco usa el contorno compartido reducido a (2px), con separación (1px). Las etiquetas permanecen visibles; los placeholders son ejemplos, no sustitutos de la etiqueta. Los errores de servidor aparecen en mensajes escritos; no existe un estilo independiente de borde inválido por campo.

### Navigation

Encabezado compartido con marca enlazada al tablero, usuario y botón de salida cuando hay sesión. La marca combina SVG (26px) y texto. El enlace «Ir al contenido» permanece fuera de vista hasta recibir foco; lleva al contenido principal. La sesión ajusta su separación y tamaño en el breakpoint estrecho, sin menú móvil nuevo.

### Connection and messages

La conexión combina punto circular y texto: espera en tono neutro, activo en verde y error en rojo. Los mensajes están dentro del flujo, con borde y esquina de control. Error y aviso usan sus familias semánticas; el éxito conserva el mensaje neutro. El botón de recarga aparece solo cuando hay recuperación que realizar. Los estados usan `role="status"`; los errores de acceso usan `role="alert"`.

### Live board

Las tarjetas se ordenan por creación. La actualización conserva el foco en la misma acción cuando sigue disponible; si cambió, pasa a la tarjeta y, si desapareció, al campo de nueva tarea. Crear devuelve el foco al campo. El tablero tiene foco propio para permitir desplazamiento con teclado.

La única animación actual es un cambio de fondo de tarjeta de (180ms) con `ease-out`, condicionado a `prefers-reduced-motion: no-preference`. No hay transiciones de hover ni elevación animada.

## Do's and Don'ts

### Do:
- **Do** conservar la estructura de cinco columnas horizontales y el desplazamiento dentro del tablero.
- **Do** usar etiquetas visibles, texto de estado y el foco de teclado del sistema.
- **Do** mantener acceso, registro y tablero dentro de la misma paleta y jerarquía tipográfica.
- **Do** mostrar únicamente acciones disponibles; conservar vacías y reconocibles las columnas posteriores.
- **Do** conservar el foco en la acción o tarjeta equivalente tras cambios en vivo; si desaparece, devolverlo al campo de nueva tarea.

### Don't:
- **Don't** presentar controles de módulos futuros como si estuvieran disponibles.
- **Don't** comunicar conexión, error o eliminación únicamente mediante color.
- **Don't** sustituir las columnas por tarjetas de panel independientes o añadir decoración que dificulte explicar el tablero en clase.
- **Don't** considerar verificado el diseño móvil a partir de capturas de escritorio o de una ventana de 905 px.
