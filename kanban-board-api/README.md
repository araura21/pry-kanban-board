# Kanban API · módulos 1 y 2

Proyecto académico con Laravel 13, PHP 8.4, PostgreSQL 17 y Laravel Reverb. Las reglas están en `TareaService`; las pantallas se actualizan por un canal privado de WebSocket.

## Qué funciona

- Registro, inicio y cierre de sesión.
- Un tablero compartido con cinco columnas.
- Crear tarjetas con un título de hasta 100 caracteres.
- Eliminar tarjetas de Creado o Por hacer.
- Pasar de Creado a Por hacer; registrar quién la movió, su versión y el momento del compromiso.
- Recibir creaciones, movimientos y eliminaciones en vivo.
- Rechazar movimientos con una versión antigua y movimientos no permitidos.
- Mover también desde Artisan, reutilizando el mismo servicio.

Los módulos 3, 4 y 5 continúan pendientes. En progreso, Suspendido y Hecho se muestran como columnas, sin acciones para entrar en ellas.

## Abrir lo que ya está instalado

Con Docker Desktop iniciado, abre PowerShell en esta carpeta (`pry-kanban-board/kanban-board-api`):

```powershell
docker compose up -d
```

Abre **http://localhost:8000**, pulsa **Crear una cuenta** y regístrate. Para comprobar el tiempo real, abre una ventana de incógnito y registra otra cuenta. Ambas comparten las tarjetas.

Una instalación nueva comienza sin usuarios ni tarjetas. La copia local reorganizada conserva su configuración, sesiones y base de datos existentes.

## Preparar otra copia

Solo en una copia nueva sin `.env`:

```powershell
Copy-Item .env.example .env
docker compose build
docker compose run --rm app composer install --no-interaction
docker compose run --rm app php artisan key:generate --no-interaction
docker compose run --rm app php artisan migrate --force --no-interaction
docker compose up -d
```

No necesitas PHP, Composer o Node instalados en Windows para ejecutar la aplicación. Echo y Pusher se cargan desde CDN, igual que en la guía de clase; el navegador necesita acceso a Internet para descargarlos.

## Comandos habituales

```powershell
docker compose ps
docker compose logs --tail 30 app reverb
docker compose exec app php artisan route:list --except-vendor
docker compose exec app php artisan migrate:status
docker compose down
```

`down` detiene y retira los contenedores, conservando el volumen de PostgreSQL. Evita agregar `-v` si quieres conservar los datos.

Mover una tarjeta: sustituye `1` por el identificador de la tarjeta y `2` por un usuario existente:

```powershell
docker compose exec app php artisan tareas:mover 1 por_hacer 2
```

El identificador de la tarjeta aparece como `#1` en pantalla. Para consultar usuarios:

```powershell
docker compose exec db psql -U kanban -d kanban -c "SELECT id, name FROM users ORDER BY id;"
```

## Pruebas

Las pruebas usan **kanban_testing**, una base distinta de la aplicación. Créala una sola vez:

```powershell
docker compose exec db psql -U kanban -d postgres -c "CREATE DATABASE kanban_testing;"
docker compose exec app php artisan test --compact tests/Feature/TableroTest.php
```

Resultado verificado: **20 pruebas, 137 comprobaciones**. Además se comprobaron dos sesiones HTTP/WebSocket reales, CSRF, conflicto simultáneo de versión, persistencia y caída/recuperación de Reverb. Esto no sustituye las pruebas de carga o compatibilidad pendientes de la ERS.

## Archivos para estudiar

| Archivo | Para qué sirve |
|---|---|
| `app/Services/TareaService.php` | Reglas y persistencia; emite eventos después de guardar |
| `app/Http/Controllers/*` | Reciben peticiones y preparan respuestas |
| `app/Models/Tarea.php` | Relaciones y datos públicos de cada tarjeta |
| `app/Events/*` | Mensajes privados enviados por Reverb |
| `routes/web.php`, `routes/channels.php` | Acceso por sesión y autorización del canal |
| `routes/console.php` | Comando de movimiento |
| `../kanban-board-app/resources/views/*` | Páginas Blade |
| `../kanban-board-app/public/js/tablero.js` | Acciones HTTP y actualización de tarjetas |
| `../kanban-board-app/public/js/conexion.js` | Conexión privada y estado de la conexión |
| `../kanban-board-app/public/css/kanban.css` | Presentación |
| `tests/Feature/TableroTest.php` | Pruebas automáticas de los módulos 1 y 2 |

Docker monta las vistas de `../kanban-board-app/resources` en `/var/www/html/resources` y sus recursos estáticos en `/var/www/html/public`. Por eso Laravel mantiene sus rutas internas habituales, aunque el código esté separado en el repositorio.

La explicación, los contratos y la trazabilidad están en [la guía de implementación](../docs/04-desarrollo/02_implementacion_kanban.md). La [teoría](../docs/03-arquitectura/01_teoria_kanban.md) y la [ERS](<../docs/02-negocio/Requisitos Tablero Kanban.docx>) definen también los módulos posteriores.
