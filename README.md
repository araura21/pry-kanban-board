# Kanban Board · Aplicaciones Distribuidas

Tablero compartido construido con Laravel 13, PHP 8.4, PostgreSQL 17 y Laravel Reverb. Incluye los módulos 1 y 2: autenticación, creación y eliminación en vivo, y movimiento de **Creado a Por hacer** con control de versión.

## Organización

```text
pry-kanban-board/
├── kanban-board-api/        Laravel, reglas, eventos, base de datos y Docker
├── kanban-board-app/        Vistas Blade, JavaScript, CSS y fuentes
└── docs/
    ├── 01-proyecto/         Presentación y alcance
    ├── 02-negocio/          Requisitos Tablero Kanban.docx
    ├── 03-arquitectura/     01_teoria_kanban.md
    └── 04-desarrollo/      02_implementacion_kanban.md
```

La interfaz se renderiza con Blade desde Laravel. Docker monta los archivos de `kanban-board-app` en las rutas de vistas y recursos públicos de Laravel. Se mantienen los servicios **db, app y reverb** y un único tablero compartido.

## Requisitos del equipo

- Docker Desktop instalado e iniciado, con contenedores Linux.
- Git para clonar el repositorio; también puedes descargarlo como ZIP y extraerlo.
- Puertos 8000 y 8080 disponibles.
- Acceso a Internet para descargar imágenes, dependencias y las bibliotecas Echo/Pusher del navegador.

PHP, Composer y PostgreSQL se ejecutan en Docker. Esta entrega no requiere instalar Node ni compilar el frontend.

## Abrir esta copia ya preparada

Con Docker Desktop iniciado, desde la raíz del repositorio:

```powershell
cd kanban-board-api
docker compose up -d
```

Abre **http://localhost:8000**. Puedes crear una cuenta y usar otra ventana de incógnito para comprobar el tiempo real.

## Preparar una copia nueva

```powershell
git clone https://github.com/araura21/pry-kanban-board.git
cd pry-kanban-board/kanban-board-api
Copy-Item .env.example .env
docker compose build
docker compose run --rm app composer install --no-interaction
docker compose run --rm app php artisan key:generate --no-interaction
docker compose run --rm app php artisan migrate --force --no-interaction
docker compose up -d
```

La copia local conserva su `.env` y sus dependencias. Git excluye esos archivos; los demás integrantes los preparan con la secuencia anterior. PostgreSQL conserva los datos en el volumen `kanban_postgres_data`.

Los comandos están escritos para PowerShell. En una terminal de Linux o macOS, sustituye `Copy-Item .env.example .env` por `cp .env.example .env`; los comandos Docker son los mismos. Si descargaste un ZIP, empieza entrando a `pry-kanban-board/kanban-board-api` y continúa desde la copia de `.env.example`.

## Pruebas

Desde `kanban-board-api`, crea la base de pruebas una sola vez y ejecuta la suite:

```powershell
docker compose up -d db
docker compose exec db psql -U kanban -d postgres -c "CREATE DATABASE kanban_testing;"
docker compose run --rm app composer test -- --compact tests/Feature/TableroTest.php
```

Si la base de pruebas ya existe, omite su creación. La suite utiliza `kanban_testing`, separada de la base de la aplicación.

## Documentos

- [Especificación de requisitos](<docs/02-negocio/Requisitos Tablero Kanban.docx>).
- [Teoría y arquitectura](docs/03-arquitectura/01_teoria_kanban.md).
- [Implementación y comandos](docs/04-desarrollo/02_implementacion_kanban.md).
- [Backend](kanban-board-api/README.md) y [frontend](kanban-board-app/README.md).

Los módulos 3 a 5 continúan pendientes: WIP y clases de servicio, suspensión, entrega, métricas y archivado.
