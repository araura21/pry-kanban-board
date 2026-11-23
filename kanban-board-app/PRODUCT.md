# Producto: tablero Kanban del equipo

<!-- impeccable:product-schema 1 -->

## Hechos confirmados

Aplicación web académica en español para que varios integrantes compartan un tablero y vean cambios sin recargar. Plataforma web, Laravel 13, PHP 8.4, PostgreSQL 17, Reverb y Docker, con Blade y JavaScript sencillo. La base es `../docs/02-negocio/Requisitos Tablero Kanban.docx` y `../docs/03-arquitectura/01_teoria_kanban.md`.

El usuario confirmó implementar primero los módulos 1 y 2: registro, acceso, salida, cinco columnas, creación y eliminación en vivo, movimiento de Creado a Por hacer y comando de terminal. Todos los usuarios autenticados tienen los mismos permisos. No hay tableros individuales, asignación, comentarios ni reordenación manual.

Los módulos 3 a 5 se implementarán después. No presentar WIP, suspensión, entrega, métricas o archivo como funciones disponibles.

## Principios

- Mostrar el estado real y las acciones permitidas.
- Facilitar crear una tarjeta y comprometerla para el trabajo.
- Mantener un código que el estudiante pueda explicar en clase.
- Ante un fallo de difusión, aclarar que la operación quedó guardada y que debe recargarse, sin repetirla.

El flujo y las cinco columnas proceden del diseño entregado por el equipo. La presentación debe ser sobria, legible y consistente con ese tablero.
