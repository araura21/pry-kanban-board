# Tablero Kanban: implementación de los módulos 1 y 2

Implementación realizada en `Proyecto/pry-kanban-board`, con backend en `kanban-board-api` y frontend en `kanban-board-app` y comprobada el 22 de noviembre de 2026 (hora de Ecuador). Esta guía describe el código existente, tomando como base la [ERS](<../02-negocio/Requisitos Tablero Kanban.docx>), revisión 02, y [01_teoria_kanban.md](../03-arquitectura/01_teoria_kanban.md). Sustituye la propuesta anterior que todavía no se había ejecutado.

**Alcance confirmado:** primero los módulos 1 y 2. No es la implementación completa de los cinco módulos.

## 1. Qué se entregó y qué sigue pendiente

| Módulo | Estado de esta entrega |
|---|---|
| 1 | Docker con db/app/reverb; registro, login y logout; cinco columnas; creación y eliminación en vivo; canal privado |
| 2 | Solo Creado → Por hacer; fecha de compromiso, autor del movimiento, versión, bloqueo de fila y comando Artisan |
| 3 | Pendiente: entrada a En progreso, WIP y clases de servicio |
| 4 | Pendiente: suspensión, motivo del bloqueo y reanudación |
| 5 | Pendiente: entrega, Lead Time, métricas, archivado y scheduler |

Los estados posteriores ya tienen columna para conservar el diseño del tablero. La interfaz no ofrece acciones que todavía no existen. `KANBAN_LIMITE_WIP` y `KANBAN_ARCHIVAR_MINUTOS` están reservados en la configuración; sus reglas aún no se ejecutan.

## 2. Entorno real utilizado

| Componente | Versión / configuración |
|---|---|
| Laravel | Framework 13.43.0; restricción `^13.17` |
| PHP | 8.4 en contenedor Linux |
| Reverb | 1.14.0; restricción `^1.12` |
| Base | PostgreSQL 17 |
| Interfaz | Blade, CSS y JavaScript; sin React ni Vue |
| Navegador | Echo 1.19.0 y Pusher JS 8.4.0 por CDN |
| Docker | Docker Desktop, motor 29.9.3; servicios db, app y reverb |
| Pruebas | PHPUnit 12.5.45, PostgreSQL separado `kanban_testing` |

Las versiones exactas de PHP se obtienen con `docker compose exec app php -v`; las dependencias PHP quedan fijadas en `composer.lock`. Las imágenes usan etiquetas de versión mayor y pueden recibir actualizaciones de mantenimiento.

## 3. Instalación y arranque en orden

### 3.1 Lo que ya se hizo en esta carpeta

Se creó Laravel en la carpeta vacía mediante Composer dentro de un contenedor PHP compatible. Se instalaron Reverb y las herramientas de desarrollo; se crearon los archivos de esta guía, se generó la clave y se ejecutaron las migraciones. No hay que volver a crear el proyecto.

Con Docker Desktop iniciado, abre PowerShell en la carpeta que contiene el repositorio clonado:

```powershell
cd pry-kanban-board/kanban-board-api
docker compose up -d
docker compose ps
```

Abre **http://localhost:8000**. El acceso sin sesión te lleva a `/login`. Pulsa **Crear una cuenta**. No hay una cuenta predeterminada.

### 3.2 Preparar una copia nueva del código entregado

Si la copia todavía no tiene `.env`, créalo antes del primer arranque:

```powershell
Copy-Item .env.example .env
docker compose build
docker compose run --rm app composer install --no-interaction
docker compose run --rm app php artisan key:generate --no-interaction
docker compose run --rm app php artisan migrate --force --no-interaction
docker compose up -d
```

No sobrescribas el `.env` ni cambies la clave de una instalación que ya estés utilizando. Esta secuencia prepara el código existente; no se ejecuta `create-project` encima de él. En Linux o macOS utiliza `cp .env.example .env` en lugar de `Copy-Item .env.example .env`. El [README principal](../../README.md) reúne los requisitos y el proceso desde la descarga del repositorio.

### 3.3 Servicios y puertos

| Servicio | Función | Puerto |
|---|---|---|
| db | Guarda usuarios y tareas | 5432 interno; no se publica en Windows |
| app | Atiende páginas y peticiones HTTP | 8000 de Windows |
| reverb | Reparte eventos WebSocket | 8080 de Windows |

El archivo `docker-compose.yml` espera a que PostgreSQL esté disponible. El volumen `postgres_data` conserva sus datos. El servidor HTTP usa:

```text
php artisan serve --host=0.0.0.0 --port=8000 --no-reload
```

`.env` define `PHP_CLI_SERVER_WORKERS=4`. El indicador `--no-reload` permite que Artisan respete varios trabajadores, necesarios para ensayar peticiones concurrentes. No se agrega scheduler hasta el módulo 5.

Comprobaciones útiles:

```powershell
docker compose exec app php artisan migrate:status
docker compose exec app php artisan route:list --except-vendor
docker compose logs --tail 30 app reverb
```

Para detener sin perder la base:

```powershell
docker compose down
```

## 4. Archivos y responsabilidades

Las rutas de backend que siguen son relativas a `kanban-board-api`. Las vistas y recursos visuales se guardan físicamente en `kanban-board-app`; Docker los monta en `/var/www/html/resources` y en las rutas de recursos de `/var/www/html/public`. El comando de arranque se ejecuta desde `kanban-board-api`.

| Capa | Archivos | Responsabilidad |
|---|---|---|
| Autenticación | `app/Http/Controllers/AuthController.php`, `kanban-board-app/resources/views/auth/*` | Registrar, entrar, regenerar la sesión y salir |
| Controladores | `TableroController.php`, `TareaController.php` | Consultar datos, validar la entrada HTTP y responder; no duplican las reglas |
| Servicio | `app/Services/TareaService.php` | Crear, eliminar y mover; comprobar las reglas, guardar y emitir eventos |
| Excepción | `app/Exceptions/ReglaTableroException.php` | Identificar rechazos de negocio sin confundirlos con fallos de infraestructura |
| Modelo | `app/Models/Tarea.php` | Relaciones con usuarios y contrato público `datosTablero()` |
| Persistencia | `database/migrations/*tareas*.php` | Tabla inicial y migración separada de `comprometida_en` |
| Eventos | `app/Events/TareaCreada.php`, `TareaMovida.php`, `TareaEliminada.php` | Difundir por el canal privado `tablero` |
| Rutas | `routes/web.php`, `routes/channels.php` | Sesión, CSRF y autorización |
| Terminal | `routes/console.php` | Comando `tareas:mover` |
| Interfaz | `kanban-board-app/resources/views/tablero.blade.php`, `kanban-board-app/public/js/*`, `kanban-board-app/public/css/kanban.css` | Dibujar, enviar acciones y recibir cambios |
| Configuración | `.env.example`, `config/broadcasting.php`, `config/reverb.php`, `config/kanban.php` | Conexiones, puertos y valores configurables |
| Pruebas | `tests/Feature/TableroTest.php`, `database/factories/TareaFactory.php` | Preparación y verificación aisladas |

Las contraseñas se guardan con el cast `hashed` del modelo User. No se utiliza cifrado reversible ni se envían hashes en los eventos.

## 5. Módulo 1: acceso y tarjetas compartidas

### Registro y sesión

`AuthController::registrar()` valida nombre, correo único y contraseña confirmada de al menos seis caracteres. Crea el usuario, inicia sesión y regenera su identificador.

`entrar()` comprueba correo y contraseña mediante `Auth::attempt`. `salir()` cierra la sesión, la invalida y regenera el token CSRF. El JavaScript desconecta Echo al enviar el formulario de salida.

Todos los usuarios autenticados ven el mismo tablero y tienen los mismos permisos. No hay tarjetas privadas de cada creador.

### Crear

El formulario envía `POST /tareas` con el título. El servicio recorta espacios, rechaza un título vacío o de más de 100 caracteres y crea la tarjeta con:

```text
estado = creado
version = 1
user_id = usuario de la sesión
movida_por = null
comprometida_en = null
```

Después de guardar, emite `TareaCreada`. El navegador que creó también recibe el evento; no se añade una copia local anticipada.

### Eliminar

La interfaz ofrece Eliminar en Creado y Por hacer. El servidor siempre vuelve a comprobarlo. `eliminar()` abre una transacción, obtiene la fila con `lockForUpdate()`, comprueba existencia y estado y realiza el borrado. Después de confirmar, emite `TareaEliminada`.

El bloqueo evita que la comprobación se haga sobre un estado que otro movimiento cambie al mismo tiempo. Los estados iniciados se rechazan aunque alguien fabrique una petición HTTP.

### Canal privado y página de comprobación

`routes/channels.php` autoriza `tablero` para un usuario autenticado. Echo utiliza `private('tablero')`, que en la conexión se identifica como `private-tablero`.

Se definió explícitamente `POST /broadcasting/auth` dentro de las rutas web protegidas por sesión. Así conserva CSRF, además de la autenticación. No se registra otra ruta automática de autorización que pueda duplicarla o excluir CSRF.

**http://localhost:8000/sala** es la página de comprobación solicitada por la guía. Inicia sesión y ábrela: debe indicar **En vivo** tras autorizar la suscripción privada. Esto comprueba conexión y autorización; para comprobar el contenido de los eventos utiliza dos ventanas del tablero.

## 6. Módulo 2: compromiso y concurrencia

La única transición activa es:

```text
creado → por_hacer
```

`mover()` comprueba el destino y el usuario. Dentro de una transacción bloquea la tarjeta, comprueba la versión recibida y valida la transición. Si se permite:

1. Cambia `estado` a `por_hacer`.
2. Registra `movida_por`.
3. Aumenta `version` en uno.
4. Guarda `comprometida_en = now()`.
5. Guarda la fila y confirma la transacción.
6. Emite `TareaMovida` con los datos confirmados.

Dos solicitudes con la misma versión no consiguen aplicar dos movimientos: la segunda obtiene la fila después del bloqueo y encuentra una versión diferente. Se devuelve **422** sin cambiar autor, fecha, estado ni versión.

### Comando Artisan

```powershell
docker compose exec app php artisan tareas:mover 1 por_hacer 2
```

El primer número es el id de tarea, el último el id de un usuario existente. Puedes consultarlos así:

```powershell
docker compose exec db psql -U kanban -d kanban -c "SELECT id, name FROM users ORDER BY id;"
docker compose exec db psql -U kanban -d kanban -c "SELECT id, titulo, estado, version, comprometida_en FROM tareas ORDER BY id;"
```

El comando utiliza el mismo servicio. No recibe una versión de navegador: lee y valida el estado actual bajo bloqueo. Su usuario se suministra desde una terminal de confianza para registrar autoría; no es un mecanismo para iniciar sesión. Devuelve salida **0** al aceptar y **1** si el id, usuario o movimiento no son válidos.

## 7. Contratos HTTP y WebSocket

### Peticiones

Todas las acciones de tareas exigen sesión, `Accept: application/json` y `X-CSRF-TOKEN`. Los cuerpos JSON tienen estas formas:

| Método y ruta | Cuerpo | Éxito |
|---|---|---|
| POST `/tareas` | `{"titulo":"Preparar clase"}` | 201: `{"tarea":{...},"aviso":null}` |
| PATCH `/tareas/1/mover` | `{"estado":"por_hacer","version":1}` | 200: `{"tarea":{...},"aviso":null}` |
| DELETE `/tareas/1` | `{}` | 200: `{"tareaId":1,"aviso":null}` |

El tablero inicial usa los mismos campos que los eventos:

```json
{
  "id": 1,
  "titulo": "Preparar clase",
  "estado": "por_hacer",
  "version": 2,
  "creador": "Ariel",
  "movidaPor": "Ana",
  "creadaEn": "2026-11-22T18:00:00.000000Z"
}
```

La fecha es un ejemplo. `movidaPor` comienza en `null`. Nunca se incluyen correos, contraseñas o datos de sesión.

| Evento | Contenido público |
|---|---|
| `TareaCreada` | `tarea` con los campos anteriores |
| `TareaMovida` | `tarea` con los campos anteriores |
| `TareaEliminada` | `tareaId` entero |

Los eventos implementan `ShouldBroadcastNow`: no hace falta un trabajador de colas para esta entrega.

### Rechazos y recuperación

| Situación | Respuesta / comportamiento |
|---|---|
| Título vacío, demasiado largo o versión inválida | 422 con errores de validación |
| Tarjeta inexistente, versión antigua o movimiento prohibido | 422 con `error` y un mensaje de negocio |
| Petición JSON sin sesión, con CSRF vigente | 401; el cliente vuelve al login |
| Token CSRF ausente o caducado | 419; se pide recargar |
| Canal privado no autorizado | 403 |
| Base o servidor no permiten confirmar la escritura | Error de servidor; la interfaz pide recargar para comprobar el estado |
| Escritura confirmada y fallo posterior al difundir | Éxito con `aviso`: el dato está guardado y no se debe repetir la operación |

El middleware CSRF se ejecuta antes que la autenticación de la ruta: tras salir, una petición con el token anterior puede recibir 419 en lugar de 401. Ambos casos se manejan en el cliente.

Los fallos técnicos se registran en `storage/logs/laravel.log`. `APP_DEBUG=false` evita mostrar detalles internos al usuario. El controlador captura únicamente `ReglaTableroException`, no cualquier `RuntimeException`.

Al reconectar, la interfaz recomienda recargar para recuperar eventos perdidos desde PostgreSQL. No se promete reproducir automáticamente el historial de eventos.

## 8. Cómo mantiene el navegador el tablero

`tablero.js` conserva las tarjetas en un `Map`. En cada actualización:

- Solo aplica un evento si su versión supera la ya conocida.
- Conserva los ids eliminados en un `Set` durante esa carga, para impedir que un evento atrasado los reinserte.
- Ordena por `creadaEn` ascendente e id como desempate.
- Construye textos con `textContent`, sin interpretar HTML enviado como título.
- Muestra únicamente las acciones permitidas recibidas del servidor.
- Deshabilita una acción mientras espera la respuesta para evitar dobles envíos.
- Conserva el foco de teclado sobre la misma tarjeta y acción cuando llega un evento. Si la acción deja de existir, enfoca la tarjeta; si se eliminó la tarjeta, vuelve al campo de creación.

La vista no decide qué movimiento es válido: presenta las opciones y el servicio vuelve a comprobar todo. El CSS permite desplazarse horizontalmente por las columnas cuando no caben en la ventana.

## 9. Verificaciones realizadas y límites de la evidencia

### 9.1 Pruebas automáticas

Creación de la base de pruebas, una sola vez:

```powershell
docker compose exec db psql -U kanban -d postgres -c "CREATE DATABASE kanban_testing;"
```

Ejecución:

```powershell
docker compose exec app php artisan test --compact tests/Feature/TableroTest.php
```

**Resultado:** 20 pruebas aprobadas, 137 comprobaciones. `phpunit.xml` fuerza la base `kanban_testing`; las pruebas no vacían la base de la aplicación.

Incluyen autenticación, hash de contraseña, correo duplicado, sesión, canal privado, contrato, orden inicial, títulos, autoría, compromiso, versión antigua, transiciones prohibidas, versión obligatoria, eliminación restringida, tarea inexistente, fallos simulados de difusión y el comando válido/inválido.

También se ejecutó el formateador Laravel Pint.

### 9.2 Integración real

Se comprobó el servidor HTTP y Reverb con dos sesiones distintas, sin simular los eventos:

| Escenario | Resultado observado |
|---|---|
| Suscripción privada de dos sesiones | Ambas autorizadas |
| Escritura y autorización sin CSRF | Ambas rechazadas con 419 |
| Crear desde una sesión | Evento idéntico recibido por ambas |
| Dos movimientos simultáneos con versión 1 | Un 200 y un 422; versión final 2 |
| Intentar entrar a En progreso | 422 |
| Crear desde la otra sesión | Evento recibido por la primera |
| Recargar | Datos compartidos conservados |
| Eliminar | Evento recibido por ambas |
| Salir y volver a intentar una acción con CSRF actualizado | 401 |

Una creación de esa ejecución se recibió en ambos clientes de protocolo a los **1328 ms** desde el envío. Es una observación puntual, no la prueba completa de rendimiento de la ERS, ni una medición del dibujo en otras pantallas.

### 9.3 Navegadores y fallo real de Reverb

Se verificaron dos sesiones en el navegador integrado, usando `localhost` y `127.0.0.1` como dominios de cookies separados:

1. Registro de dos usuarios.
2. Crear desde la primera sesión y observar la tarjeta en la segunda sin recargar.
3. Mover desde la segunda y observar la nueva columna y autor en ambas.
4. Detener Reverb; crear una tarjeta y obtener éxito con aviso de escritura guardada.
5. Iniciar Reverb y recargar ambas: el dato persistido se recupera.
6. Crear nuevamente desde la segunda sesión y recibirlo en la primera.
7. Comprobar que `<b>Revisar acceso</b>` se muestra literalmente como texto.
8. Mover una tarjeta con Artisan y observarla en los navegadores sin recargar.
9. Mantener el foco en un botón mientras otro usuario crea una tarjeta: el foco se conserva. Al mover la tarjeta enfocada, el foco queda en ella en Por hacer.

Las cuentas y tarjetas temporales de estas pruebas se retiraron al finalizar. La aplicación queda sin usuarios ni tarjetas de ejemplo, con los servicios levantados para el registro del equipo.

Las capturas de revisión se guardaron en `kanban-board-app/.impeccable/review/` (conservadas localmente y excluidas de Git). Se revisó a anchos reales de 1265 y 905 px. La herramienta no aplicó el ancho móvil solicitado; no se declara una comprobación móvil realizada.

### 9.4 Pendiente de medir

No se presentan como aprobadas las 48 pruebas previstas del sistema completo. Faltan los módulos 3 a 5 y los escenarios que dependen de ellos. También quedan pendientes:

- RNF-01: 20 acciones, todas con actualización visible menor de dos segundos.
- RNF-02: tres cargas con 200 tarjetas, cada una menor de tres segundos.
- RNF-03: 20 sesiones autenticadas simultáneas durante las acciones.
- RNF-14: comprobación en otros sistemas y arquitecturas Docker.
- RNF-21: revisión explícita en Chrome, Firefox y Edge.
- Verificación móvil real y auditoría completa de accesibilidad.

## 10. Trazabilidad de esta entrega

| Requisito | Código principal | Evidencia / alcance |
|---|---|---|
| RF-01, RF-02 | AuthController, User, middleware auth | Registro, hash, login/logout y rechazos comprobados |
| RF-03 | TableroController, vista | Cinco columnas; la parte de métricas del módulo 5 queda pendiente |
| RF-04, RF-05 | TareaService::crear, Tarea::datosTablero | Título, estado inicial, autor y contrato comprobados |
| RF-06 | TareaService::eliminar | Borrado en estados permitidos y rechazo en iniciados |
| RF-07 | Eventos de creación/eliminación, Echo | Dos clientes reales y pruebas de contrato |
| RF-08 | TareaService::mover | Fecha de compromiso y entrada a Por hacer |
| RF-09 | exigirTransicion | Solo la transición del módulo 2; se ampliará por módulos |
| RF-10 | TareaMovida, tablero.js | Movimiento en vivo y autoría |
| RF-11 | Transacción, lockForUpdate, version | Conflicto simultáneo y conservación ante rechazo |
| RF-12 | routes/console.php | Comando válido e inválido; mismo servicio |
| RNF-04 a RNF-10 | Sesión, CSRF, hashed, payload y validaciones | Pruebas automáticas y verificaciones reales descritas arriba |
| RNF-11, RNF-12, RNF-13 | Compose, servicio, PostgreSQL | Separación de procesos, fallo real de Reverb y persistencia |
| RNF-15 a RNF-20 | Capas, contratos, migraciones, configuración | Implementados en el alcance actual; portabilidad y rendimiento no se infieren de la estructura |
| RNF-22 | config/kanban.php | Configuración reservada; comportamiento WIP/archivo pendiente |

Esta tabla identifica evidencias y límites; no equivale a aprobar todos los criterios de la ERS. Las extensiones futuras conservan los mismos nombres de eventos y agregan sus reglas y migraciones en el módulo correspondiente.
