# Tablero Kanban en tiempo real: teoría y diseño

Tablero Kanban interactivo con actualización en tiempo real, basado en servicios distribuidos. Varios usuarios comparten un mismo tablero de cinco columnas y ven los cambios de los demás al instante, sin recargar la página. Construido con Laravel, PostgreSQL, Laravel Reverb (WebSockets) y Docker.

Este documento reúne el diseño completo del sistema y desarrolla los requisitos de [Requisitos Tablero Kanban.docx](<../02-negocio/Requisitos Tablero Kanban.docx>), revisión 02 del 22 de noviembre de 2026. La ERS define qué debe cumplirse y este documento precisa cómo se diseña y verifica. Ambos son la base para programar. [02_implementacion_kanban.md](../04-desarrollo/02_implementacion_kanban.md) describe ahora los módulos 1 y 2 implementados en `kanban-board-api` y `kanban-board-app`, sus comprobaciones realizadas y sus pruebas pendientes. No equivale a aprobar el sistema completo ni las 48 pruebas previstas de la ERS.

## Contenido

1. Descripción
2. Tecnologías y decisiones de diseño
3. Alcance
4. Funcionamiento completo del sistema
5. Módulos y backlog
6. Especificación de los módulos 3, 4 y 5
7. Mejoras futuras
8. Trazabilidad y verificación

## 1. Descripción

El tablero tiene cinco columnas fijas (`CREADO`, `POR HACER`, `EN PROGRESO`, `SUSPENDIDO` y `HECHO`) y sigue las prácticas del método Kanban: visualizar el flujo, limitar el trabajo en curso (WIP), hacer explícitas las políticas y medir el tiempo de entrega (Lead Time). Las reglas se validan en el servidor y cada cambio se reparte por WebSocket a todos los navegadores conectados.

El sistema se construye por módulos:

| Módulo | Alcance |
|---|---|
| 1 | Base Docker y Reverb, login, tablero de 5 columnas, crear y eliminar tarjetas en vivo |
| 2 | Mover de `CREADO` a `POR HACER` (punto de compromiso), versión y bloqueo de fila, comando artisan |
| 3 | `EN PROGRESO` con límite WIP y clases de servicio |
| 4 | `SUSPENDIDO` y bloqueos |
| 5 | `HECHO`, Lead Time, métricas y archivado automático con scheduler |

Las secciones 4 y 5 describen el funcionamiento completo y el backlog, y la sección 6 especifica los módulos 3, 4 y 5. La guía paso a paso, con código, de los módulos 1 y 2 está en el documento de implementación ([02_implementacion_kanban.md](../04-desarrollo/02_implementacion_kanban.md)).

## 2. Tecnologías y decisiones de diseño

- **Framework:** Laravel (`laravel/framework ^13.17`), PHP 8.4 dentro de Docker.
- **Tiempo real:** Laravel Reverb (`laravel/reverb ^1.12`) y Laravel Echo en el navegador.
- **Base de datos:** PostgreSQL 17 en su propio contenedor.
- **Autenticación:** registro, login y logout con sesión. Todos los usuarios logueados comparten un único tablero.
- **Columnas y estados:** cinco columnas fijas con los valores `creado`, `por_hacer`, `en_progreso`, `suspendido` y `hecho`. El **punto de compromiso** es la entrada a `por_hacer` y el **punto de entrega** es la entrada a `hecho`.
- **Canal de Reverb:** privado (`tablero`), obligatorio desde el módulo 1. La suscripción se autoriza con la sesión del usuario en `/broadcasting/auth`; Echo usa `private`. Las rutas HTTP también exigen autenticación. La interfaz desconecta Echo al cerrar sesión.
- **Comunicación del navegador:** las tarjetas se crean, eliminan y mueven con `fetch` y el servidor responde JSON, sin recarga. Las peticiones que modifican datos llevan token CSRF.
- **Contraseñas:** el modelo User utiliza el cast `hashed`; se guardan hashes, no cifrado reversible. El cast detecta los valores ya hasheados, por lo que no es correcto afirmar que usar además `Hash::make` necesariamente rompe el login.
- **Configuración:** conexiones y puertos se obtienen del entorno. `config/kanban.php` expone `KANBAN_LIMITE_WIP` (entero positivo, valor inicial 3) y `KANBAN_ARCHIVAR_MINUTOS` (entero positivo, valor inicial 2). La vista recibe del servidor los valores que necesita; no duplica reglas ni puertos configurables.

### Arquitectura en capas

| Capa | Archivo | Responsabilidad |
|---|---|---|
| Controlador | `TareaController`, `TableroController`, `AuthController` | Autenticación y presentación según su función. `TareaController` valida la forma de la entrada, delega reglas al servicio y traduce errores de negocio; no persiste tarjetas ni emite eventos |
| Servicio | `TareaService` | Contiene la lógica del negocio: valida, guarda y dispara el evento de broadcast. Lo reutilizan el controlador y los comandos artisan |
| Modelo | `Tarea`, `User` | Datos y relaciones. `Tarea::datosTablero()` arma la tarjeta |
| Evento | `TareaCreada`, `TareaMovida`, `TareaEliminada` | Mensaje que Reverb reparte por el canal `tablero` |
| Vista | `tablero.blade.php`, `auth/*` | HTML y JavaScript con Echo |

### Organización del repositorio

El backend Laravel está en `kanban-board-api`. Las vistas y recursos del frontend están en `kanban-board-app/resources` y `kanban-board-app/public`. Docker los monta en las rutas internas habituales de Laravel; la separación de carpetas conserva la misma aplicación, servicios y reglas. Los comandos Docker se ejecutan desde `kanban-board-api`.

### Contenedores

| Contenedor | Función |
|---|---|
| `db` | PostgreSQL 17 |
| `app` | Laravel HTTP en el puerto 8000; para probar concurrencia: `php artisan serve --host=0.0.0.0 --port=8000 --no-reload` y `PHP_CLI_SERVER_WORKERS=4` |
| `reverb` | `php artisan reverb:start` en el puerto 8080 |
| `scheduler` | `php artisan schedule:work` para las tareas programadas (módulo 5) |

### Distribución y autoridad de los datos

Los procesos se comunican por red: el navegador envía acciones HTTP a Laravel; Laravel consulta PostgreSQL y envía eventos a Reverb por HTTP interno; Reverb los distribuye a los navegadores por WebSocket. `app` concentra las reglas y PostgreSQL conserva el estado confirmado. Las transacciones protegen la escritura; las versiones permiten detectar que un cliente trabaja con datos antiguos.

La difusión a las pantallas ocurre después de confirmar la base y puede retrasarse o fallar. Por eso la consistencia de las pantallas depende de los eventos recibidos y se recupera desde PostgreSQL mediante recarga. Separar contenedores distribuye responsabilidades y aísla procesos; esta versión utiliza una sola base de datos y no incluye replicación ni alta disponibilidad.

## 3. Alcance

Dentro:

- Registro, login y logout, con suscripción autenticada al canal privado.
- Tablero con 5 columnas y tarjetas (módulo 1).
- Crear y eliminar tarjetas (módulo 1).
- Pasar de `CREADO` a `POR HACER` con registro del punto de compromiso (módulo 2).
- Actualización en vivo para todos los usuarios conectados (módulos 1 y 2).
- Límite WIP en `EN PROGRESO` validado en el servidor, con clases de servicio (módulo 3).
- Bloqueos con `SUSPENDIDO` (módulo 4).
- Entrega en `HECHO`, Lead Time y archivado automático con scheduler (módulo 5).
- Mostrar quién creó y quién movió cada tarjeta.
- Manejo de conflictos con número de versión.
- Comando artisan que reutiliza el servicio.
- Contenedores Docker: db, app, reverb y scheduler.

Fuera de alcance:

- El proceso STATIK (talleres de análisis previos al tablero).
- Cadencias y reuniones (Daily Kanban Meeting, retrospectivas, videollamadas).
- Gráficos estadísticos complejos (diagrama de flujo acumulado, histogramas). Solo contadores numéricos de WIP actual y Lead Time.
- Subcolumnas y carriles (swimlanes) múltiples: un solo tablero con columnas simples.
- Varios tableros, asignar tareas a otros usuarios, comentarios, notificaciones, reordenar manualmente tarjetas dentro de una misma columna, columnas personalizables.

## 4. Funcionamiento completo del sistema

### 4.1 Columnas y puntos de control

| Columna | Estado | Significado |
|---|---|---|
| Creado | `creado` | Ideas o tareas registradas, todavía sin compromiso |
| Por hacer | `por_hacer` | Punto de compromiso: el equipo se comprometió a hacerla. Aquí empieza a contar el Lead Time |
| En progreso | `en_progreso` | Se está trabajando. Tiene límite WIP |
| Suspendido | `suspendido` | Bloqueada por un impedimento, con motivo visible |
| Hecho | `hecho` | Punto de entrega. Aquí termina el Lead Time |

### 4.2 Políticas explícitas (reglas del tablero)

Estas políticas se eligieron para este tablero y viven en el servidor (`TareaService`), no en el navegador. El límite descrito es el de la columna En progreso; no equivale al total de trabajo comprometido entre Por hacer y Hecho. Suspender libera un cupo de esa columna, pero la tarea sigue comprometida y su Lead Time continúa contando:

1. Una tarjeta nueva siempre entra en `creado`.
2. Solo se permiten estas transiciones (cualquier otra se rechaza con un mensaje):

| Desde | Hacia | Regla extra | Módulo |
|---|---|---|---|
| `creado` | `por_hacer` | Se guarda `comprometida_en` | 2 |
| `por_hacer` | `en_progreso` | Límite WIP (las urgentes lo pueden saltar) | 3 |
| `en_progreso` | `suspendido` | Obliga a escribir el motivo del bloqueo | 4 |
| `suspendido` | `en_progreso` | Límite WIP con excepción urgente; se borra el motivo solo si se acepta | 4 |
| `en_progreso` | `hecho` | Se guarda `finalizada_en` y se calcula el Lead Time | 5 |

3. Solo se pueden eliminar tarjetas en `creado` o `por_hacer` (trabajo que todavía no empezó). La comprobación y el borrado se ejecutan con transacción y bloqueo de la fila. La vista ofrece Eliminar solo cuando corresponde; el servidor siempre vuelve a comprobarlo.
4. Las tarjetas `suspendido` no cuentan para el límite WIP; solo cuenta `en_progreso`.
5. Límite WIP inicial de `en_progreso`: 3 tarjetas, configurable. El conteo incluye todas las tarjetas en ese estado, también las urgentes. Si el conteo es mayor o igual al límite, se rechaza una entrada no urgente.
6. Clases de servicio: `estandar`, `urgente` (puede saltarse el límite WIP) y `fecha_fija` (solo etiqueta visual).
7. `hecho` es final. Al alcanzar el tiempo configurado desde `finalizada_en` (inicialmente 2 minutos), las tarjetas son elegibles para archivado. El planificador revisa cada 10 segundos; el retiro ocurre en la siguiente ejecución y conserva la fila en la base.
8. Dentro de cada columna, las tarjetas se ordenan por `created_at` ascendente e `id` como desempate, tanto al cargar como al recibir eventos. No existe reordenación manual.

### 4.3 Modelo de datos final (tabla `tareas`)

| Columna | Tipo | Agregada en |
|---|---|---|
| id | bigint | 1 |
| user_id | FK a users (creador) | 1 |
| movida_por | FK a users, nullable (quien la movió por última vez) | 1 |
| titulo | string(100) | 1 |
| estado | string, default `creado` | 1 |
| version | unsigned int, default 1 | 1 |
| created_at, updated_at | timestamps | 1 |
| comprometida_en | timestamp, nullable | 2 |
| clase_servicio | string, default `estandar` | 3 |
| motivo_bloqueo | string(255), nullable | 4 |
| finalizada_en | timestamp, nullable | 5 |
| archivada | boolean, default false | 5 |

Cada módulo agrega sus columnas con una migración propia (`make:migration ... --table=tareas`), para que el esquema crezca por pasos.

### 4.4 Eventos y contrato de tarjeta

| Evento | Se dispara cuando | Datos |
|---|---|---|
| `TareaCreada` | Se crea una tarjeta | `tarea` (la tarjeta completa) |
| `TareaMovida` | Una tarjeta cambia de columna (incluye bloquear, desbloquear y entregar) | `tarea` (la tarjeta completa) |
| `TareaEliminada` | Se elimina o se archiva una tarjeta | `tareaId` |

Los tres eventos se difunden por el canal privado `tablero`. La "tarjeta completa" sale de `Tarea::datosTablero()`: `id`, `titulo`, `estado`, `version`, `creador`, `movidaPor` (nulo antes del primer movimiento) y `creadaEn` (fecha ISO 8601 para ordenar). Los módulos agregan `claseServicio`, `motivoBloqueo` y `leadTimeMinutos` según corresponda. La carga inicial y los eventos usan las mismas claves y tipos; nunca incluyen correos, contraseñas ni datos de sesión.

El navegador aplica solo versiones más recientes de una tarjeta ya dibujada y no vuelve a insertar ids retirados por `TareaEliminada` durante esa carga. Una recarga obtiene de nuevo el estado de PostgreSQL. La vista usa `textContent`, ofrece las acciones permitidas y mantiene el orden por creación después de cada cambio.

### 4.5 Rutas

| Método y ruta | Acción | Módulo |
|---|---|---|
| GET `/login`, POST `/login`, GET `/registro`, POST `/registro`, POST `/logout` | Autenticación | 1 |
| GET `/` | Tablero, con sesión | 1 |
| POST `/broadcasting/auth` | Autorizar la suscripción al canal privado, con sesión y CSRF | 1 |
| POST `/tareas` | Crear tarjeta | 1 |
| DELETE `/tareas/{tarea}` | Eliminar tarjeta | 1 |
| PATCH `/tareas/{tarea}/mover` | Mover tarjeta | 2 |

### 4.6 Concurrencia y consistencia

- **Versión:** cada movimiento envía la versión que el navegador conoce. Si no coincide con la de la base, el servidor responde "La tarea cambió mientras la movías".
- **Bloqueo de fila:** `mover()` y `eliminar()` corren dentro de `DB::transaction` con `lockForUpdate` sobre la tarjeta. La lectura del estado y su validación ocurren después de obtener el bloqueo; así también se protege la carrera entre mover y eliminar. Un rechazo no modifica estado, versión, fechas ni motivo.
- **Usuario del comando:** `tareas:mover` exige una tarea y un usuario existentes. El operador de terminal actúa en un entorno de confianza; el usuario recibido registra la autoría. El comando reutiliza las reglas del servicio y devuelve un código de salida distinto de cero al rechazar.
- **Bloqueo global del WIP (módulo 3):** el bloqueo de una fila no protege el conteo del límite, porque dos tarjetas distintas bloquean filas distintas. Para que, con un solo cupo libre, solo entre una de dos tarjetas no urgentes, `mover()` toma un bloqueo consultivo de PostgreSQL (`pg_advisory_xact_lock`) antes de bloquear la fila y contar. Desde el módulo 3, todos los movimientos siguen ese mismo orden de bloqueos.

### 4.7 Flujo de mover una tarjeta

1. El usuario pulsa el botón de la tarjeta y el JavaScript hace un `fetch` PATCH a `/tareas/{id}/mover` con el estado destino y la versión.
2. El middleware `auth` comprueba la sesión y `TareaController` recibe la petición.
3. El controlador llama a `TareaService::mover`, sin tocar eventos.
4. El servicio valida la forma de los datos; dentro de la transacción obtiene los bloqueos en su orden, comprueba existencia, versión y transición, aplica las reglas del módulo y guarda. Después de confirmar la transacción emite `TareaMovida`.
5. Reverb recibe el evento y lo reparte por WebSocket a las sesiones autorizadas y suscritas al canal privado.
6. Con la difusión disponible, Echo recibe `TareaMovida` y actualiza la tarjeta sin recargar, también para quien hizo el movimiento. El resultado HTTP distingue operación confirmada, rechazo de negocio y fallo de infraestructura.

### 4.8 Fallos de difusión y recuperación

PostgreSQL es la fuente del estado confirmado. Una operación guardada no se revierte porque Reverb esté caído. El servicio diferencia sus errores de negocio de las excepciones de broadcasting: el controlador no convierte cualquier `RuntimeException` en un rechazo de negocio.

Si falla solo la difusión después de guardar, la respuesta HTTP sigue siendo exitosa y añade un aviso de que el cambio está guardado pero no se pudo difundir. La vista muestra ese aviso y permite recuperar el estado mediante recarga; no pide repetir la escritura. La prueba de recuperación restablece Reverb y recarga todas las pantallas. No se promete reproducir automáticamente eventos perdidos. Si no se pudo confirmar la escritura, se informa un error de servidor; las reglas o versiones rechazadas devuelven 422 y los errores JSON no exponen consultas ni secretos.

La prueba de conflicto de versión se hace con Reverb activo enviando dos peticiones con la misma versión capturada previamente. Se comprueba por separado la caída de Reverb. Tener contenedores separados aísla sus procesos; no hace disponibles las funciones cuya dependencia está detenida.

## 5. Módulos y backlog

| Módulo | Alcance | Estado |
|---|---|---|
| 1 | Base Docker y Reverb, login, tablero de 5 columnas, crear y eliminar tarjetas en vivo | Implementado; archivos y evidencia en la guía, secciones 4, 5 y 9 |
| 2 | Mover de `CREADO` a `POR HACER`, versión y bloqueo de fila, comando `tareas:mover` | Implementado; archivos y evidencia en la guía, secciones 6 y 9 |
| 3 | `EN PROGRESO` con límite WIP y clases de servicio | Especificado (sección 6.1) |
| 4 | `SUSPENDIDO` y bloqueos | Especificado (sección 6.2) |
| 5 | `HECHO`, Lead Time, métricas y archivado con scheduler | Especificado (sección 6.3) |

Tickets sugeridos (cada uno se puede asignar y probar por separado):

**Módulo 1**
- M1-01: Proyecto Laravel nuevo, dockerfile, docker-compose (db y app) y .env.
- M1-02: Instalar Reverb, contenedor reverb y página de prueba `/sala`; agregar su ruta en este mismo paso. Proteger tablero y suscripción con sesión y canal privado.
- M1-03: Migración y modelo `Tarea` (con `datosTablero()`).
- M1-04: Registro, login y logout (`AuthController` y vistas).
- M1-05: `TareaService::crear` y `eliminar` con sus validaciones; eliminación atómica frente a movimientos y tratamiento separado de fallos de difusión.
- M1-06: Eventos `TareaCreada` y `TareaEliminada`.
- M1-07: `TareaController` y `TableroController` delgados, y rutas.
- M1-08: Vista del tablero con 5 columnas y Echo.

**Módulo 2**
- M2-01: Migración `comprometida_en` y tabla de transiciones permitidas.
- M2-02: `TareaService::mover` (transacción, bloqueo de fila, versión).
- M2-03: Evento `TareaMovida`.
- M2-04: Ruta y acción `mover` en el controlador.
- M2-05: Botón de mover y listener de `TareaMovida` en la vista.
- M2-06: Comando artisan `tareas:mover`.
- M2-07: Pruebas del módulo (dos navegadores, conflicto, fallas).
- M2-08: Regresión de autorización del canal privado configurado en el módulo 1.

**Módulo 3**
- M3-01: Migración `clase_servicio` y selector al crear la tarjeta.
- M3-02: Transición `por_hacer` a `en_progreso`.
- M3-03: Límite WIP validado en el servidor con bloqueo consultivo.
- M3-04: Excepción para clase `urgente`.
- M3-05: Contador WIP en la vista y alerta de capacidad máxima.
- M3-06: Estilos por clase de servicio.
- M3-07: Pruebas del módulo, incluida la concurrencia por el último cupo.

**Módulo 4**
- M4-01: Migración `motivo_bloqueo`.
- M4-02: Transiciones `en_progreso` a `suspendido` y de vuelta.
- M4-03: Motivo obligatorio al suspender y limpieza al desbloquear.
- M4-04: Estilo de tarjeta bloqueada y motivo visible.
- M4-05: Aviso en vivo a todos los clientes.
- M4-06: Pruebas del módulo.

**Módulo 5**
- M5-01: Migración `finalizada_en` y `archivada`.
- M5-02: Transición `en_progreso` a `hecho` con cálculo del Lead Time.
- M5-03: Lead Time en la tarjeta y barra de métricas.
- M5-04: Comando `tareas:archivar`.
- M5-05: Programación con `Schedule` y contenedor `scheduler`.
- M5-06: El tablero lista solo tarjetas no archivadas.
- M5-07: Pruebas del módulo.

## 6. Especificación de los módulos 3, 4 y 5

Los tres conservan las capas de los módulos 1 y 2: migraciones adicionales, extensión de reglas en `TareaService`, ampliación de `datosTablero()` y ajustes de la vista. Los eventos mantienen sus nombres y estructura exterior. Los controladores y el comando se amplían cuando deban recibir nuevos datos; la lógica de negocio permanece en el servicio. La compatibilidad se comprueba repitiendo las pruebas de los módulos anteriores.

### 6.1 Módulo 3: EN PROGRESO, límite WIP y clases de servicio

**Objetivo:** mostrar una regla de negocio validada de forma centralizada en el servidor y manejo de concurrencia.

**Datos:** migración `agregar_clase_servicio_a_tareas` con `clase_servicio` string, default `estandar` (valores `estandar`, `urgente`, `fecha_fija`). Agregar `clase_servicio` a `$fillable` y al arreglo de `datosTablero()`.

**Reglas:**
- Se agrega la transición `'por_hacer' => ['en_progreso']` a `TRANSICIONES`.
- Leer el límite desde `config/kanban.php`: `KANBAN_LIMITE_WIP`, con valor inicial 3. No codificar el umbral en el servicio ni duplicarlo en JavaScript.
- Al mover hacia `en_progreso` una tarjeta que no sea `urgente`, si el conteo total es mayor o igual al límite configurado, se devuelve el rechazo de negocio `Capacidad máxima alcanzada (límite WIP: 3)` para el valor inicial; el número del mensaje se obtiene de la configuración. El controlador lo convierte en 422 y la vista lo muestra en rojo.
- Una tarjeta `urgente` se puede mover aunque el límite esté lleno (el contador puede mostrar 4/3).

**Concurrencia:** dentro de la transacción de `mover`, antes de contar, se toma el bloqueo consultivo de PostgreSQL. Así, si dos usuarios mueven dos tarjetas distintas cuando queda un solo cupo, el segundo espera al primero, cuenta de nuevo y es rechazado si ambas son no urgentes. Se define `BLOQUEO_WIP = 42001` como clave técnica común de este único tablero:

```php
DB::select('select pg_advisory_xact_lock(?)', [self::BLOQUEO_WIP]);
$enProgreso = Tarea::where('estado', 'en_progreso')->count();
```

Se toma este bloqueo antes del `lockForUpdate` de la fila, siempre en ese orden, para evitar bloqueos cruzados. Se libera solo al terminar la transacción.

**Vista:** selector de clase de servicio en el formulario de crear (se envía a `crear`); color o etiqueta por clase en la tarjeta; contador `WIP: n/3` en el encabezado de `En progreso`, recalculado cada vez que se dibuja o mueve una tarjeta.

**Pruebas:**
- Mover 3 tarjetas a `en_progreso` y probar con una cuarta estándar: debe rechazarla con el mensaje.
- La misma prueba con una tarjeta `urgente`: debe pasar.
- Concurrencia: dejar un solo cupo y enviar dos movimientos realmente concurrentes de tarjetas no urgentes distintas; solo una entra. Configurar `PHP_CLI_SERVER_WORKERS=4` y arrancar `app` con `php artisan serve --host=0.0.0.0 --port=8000 --no-reload`; verificar los trabajadores antes de probar. Se usan contenedores Linux también desde Windows. La prueba debe solapar peticiones y no limitarse a dos clics consecutivos.

### 6.2 Módulo 4: SUSPENDIDO y bloqueos

**Objetivo:** representar un impedimento y avisarlo a todos los clientes por broadcast.

**Datos:** migración `agregar_motivo_bloqueo_a_tareas` con `motivo_bloqueo` string nullable. Agregar `motivo_bloqueo` a `datosTablero()`.

**Reglas:**
- Se agregan `'en_progreso' => ['suspendido', ...]` y `'suspendido' => ['en_progreso']` a `TRANSICIONES`.
- Para suspender, se quitan los espacios de los extremos del motivo y se exige entre 1 y 255 caracteres. Si está vacío se rechaza con `Escribe el motivo del bloqueo`; si supera el máximo también se rechaza sin alterar la tarjeta. El motivo válido se guarda en `motivo_bloqueo`.
- Para desbloquear, se vuelve a `en_progreso` y está sujeto al límite WIP del módulo 3 (las urgentes lo pueden saltar). Se limpia `motivo_bloqueo`.
- `mover` recibe un parámetro opcional más, `?string $motivo = null`; el controlador lo toma de `$request->input('motivo')`. El comando `tareas:mover` agrega una opción de motivo para poder suspender mediante las mismas reglas.

**Vista:** en las tarjetas de `en_progreso` aparece un campo de texto para el motivo junto al botón "→ Suspendido"; la tarjeta suspendida se pinta en rojo y muestra el motivo; el aviso a los demás es el propio evento `TareaMovida`, que mueve y pinta la tarjeta en todos los navegadores y puede mostrar una línea "X suspendió la tarea Y" usando `movidaPor`.

**Pruebas:** suspender sin motivo (rechazado), suspender con motivo (se ve en los dos navegadores), desbloquear con el WIP lleno (rechazado), desbloquear con cupo (vuelve a `En progreso` sin motivo).

### 6.3 Módulo 5: HECHO, Lead Time, métricas y archivado con scheduler

**Objetivo:** cerrar el flujo, medir el tiempo de entrega y cubrir el tema de tareas programadas.

**Datos:** migración `agregar_finalizacion_a_tareas` con `finalizada_en` timestamp nullable y `archivada` boolean default false. Casts `finalizada_en` datetime y `archivada` boolean. En `datosTablero()` se agrega `leadTimeMinutos`: segundos transcurridos entre `comprometida_en` y `finalizada_en`, divididos entre 60 y redondeados a dos decimales, nulo antes de la entrega. Las fechas se guardan con la misma referencia horaria; el tiempo suspendido forma parte del Lead Time.

**Reglas:**
- Se agrega `'en_progreso' => [..., 'hecho']` a `TRANSICIONES`. `hecho` no tiene transiciones de salida.
- Al pasar a `hecho` se guarda `finalizada_en = now()`.
- El tablero (`TableroController::index`) lista solo las tarjetas con `archivada = false`.

**Métricas básicas (solo contadores):** una barra sobre el tablero con WIP actual (cantidad en `en_progreso`), cantidad de entregadas visibles (`hecho`, no archivadas) y promedio de su Lead Time. El cálculo usa solo las tarjetas en pantalla y se repite al crear, mover, eliminar o archivar. El promedio se muestra en minutos con dos decimales; sin entregas se muestra sin valor. No es una métrica histórica. La tarjeta en `hecho` muestra su Lead Time.

**Archivado con scheduler:**
- Comando `tareas:archivar` en `routes/console.php`, busca las tarjetas en `hecho` no archivadas con `finalizada_en <= now() - N minutos`, donde N se obtiene de `KANBAN_ARCHIVAR_MINUTOS` y su valor inicial es 2. Marca `archivada = true` mediante el servicio, con transacción y comprobación de que siga siendo elegible, y emite `TareaEliminada` después de confirmar. Ejecutarlo de nuevo no cambia ni vuelve a emitir por filas ya archivadas. La fila se conserva; su retiro también recalcula las métricas visibles.
- Programación: `Schedule::command('tareas:archivar')->everyTenSeconds();`
- Contenedor nuevo en el compose:

```yaml
  scheduler:
    build: .
    volumes:
      - .:/var/www/html
    command: php artisan schedule:work
    depends_on:
      - db
      - reverb
```

- Comprobar con `docker compose exec app php artisan schedule:list` y `docker compose logs scheduler --tail 10`.
- Limitación: al archivar, la tarjeta sale del tablero y de las métricas en pantalla.

**Pruebas:** mover una tarjeta hasta `hecho` y comprobar el Lead Time; dejarla ahí y esperar a que desaparezca; revisar que el scheduler dejó registro en sus logs.

## 7. Mejoras futuras

- **Migrar la interfaz a Vue.** La implementación de los módulos 1 y 2 utiliza Blade, con CSS y JavaScript en archivos separados. Una mejora futura es pasar la interfaz a Vue cargado por CDN dentro de Blade, de modo que Vue pinte las columnas con `v-for`, en lugar de crear y mover elementos del DOM a mano. El servidor ya responde JSON, los eventos llevan la tarjeta completa (`Tarea::datosTablero()`) y las reglas viven en el servicio: el cambio se concentra en la vista y el JavaScript, conservando controladores, servicio, eventos y rutas.


## 8. Trazabilidad y verificación

### 8.1 Relación entre requisitos diseño y pruebas

Los identificadores de la ERS se conservan. `CP-RF` y `CP-RNF` identifican pruebas de aceptación previstas, no pruebas ya ejecutadas. Los escenarios y sus resultados esperados están en la sección de trazabilidad de la ERS. Antes de aprobar una entrega se registra módulo, versión del código, entorno, fecha, evidencia y resultado de cada prueba aplicable.

| Requisito | Módulo | Sección de diseño | Componente previsto | Prueba |
|---|---|---|---|---|
| RF-01 | 1 | 2 y 3 | `AuthController; auth/registro` | CP-RF-01 |
| RF-02 | 1 | 2 y 3 | `AuthController; middleware auth` | CP-RF-02 |
| RF-03 | 1 y 5 | 4.1 y 6.3 | `TableroController; vista` | CP-RF-03 |
| RF-04 | 1 | 4.2 | `TareaService::crear` | CP-RF-04 |
| RF-05 | 1 | 4.3 y 4.4 | `Tarea::datosTablero` | CP-RF-05 |
| RF-06 | 1 | 4.2 y 4.6 | `TareaService::eliminar` | CP-RF-06 |
| RF-07 | 1 | 4.4 | `Eventos; vista` | CP-RF-07 |
| RF-08 | 2 | 4.1 y 4.7 | `TareaService::mover` | CP-RF-08 |
| RF-09 | 2 a 5 | 4.2 | `TareaService::exigirTransicion` | CP-RF-09 |
| RF-10 | 2 | 4.4 y 4.7 | `TareaMovida; vista` | CP-RF-10 |
| RF-11 | 2 | 4.6 | `TareaService::mover` | CP-RF-11 |
| RF-12 | 2 | 4.5 y 4.6 | `Comando tareas:mover` | CP-RF-12 |
| RF-13 | 3 | 6.1 | `TareaService::mover` | CP-RF-13 |
| RF-14 | 3 | 6.1 | `TareaService::mover` | CP-RF-14 |
| RF-15 | 3 | 6.1 | `TareaService::crear; vista` | CP-RF-15 |
| RF-16 | 3 | 6.1 | `TareaService::mover` | CP-RF-16 |
| RF-17 | 3 | 4.6 y 6.1 | `Transacción; bloqueo WIP` | CP-RF-17 |
| RF-18 | 3 | 6.1 | `Vista del tablero` | CP-RF-18 |
| RF-19 | 4 | 6.2 | `TareaService::mover` | CP-RF-19 |
| RF-20 | 4 | 6.2 | `TareaMovida; vista` | CP-RF-20 |
| RF-21 | 4 | 6.2 | `TareaService::mover` | CP-RF-21 |
| RF-22 | 4 | 4.2 y 6.2 | `Conteo WIP` | CP-RF-22 |
| RF-23 | 5 | 6.3 | `TareaService::mover` | CP-RF-23 |
| RF-24 | 5 | 6.3 | `Tarea::datosTablero` | CP-RF-24 |
| RF-25 | 5 | 6.3 | `Métricas en la vista` | CP-RF-25 |
| RF-26 | 5 | 6.3 | `tareas:archivar; scheduler` | CP-RF-26 |

| Requisito de calidad | Sección de diseño | Componente previsto | Prueba |
|---|---|---|---|
| RNF-01 | 4.4 y 8.2 | `Reverb; Echo` | CP-RNF-01 |
| RNF-02 | 8.2 | `TableroController; vista` | CP-RNF-02 |
| RNF-03 | 8.2 | `app; reverb` | CP-RNF-03 |
| RNF-04 | 2 y 4.5 | `auth; canal privado` | CP-RNF-04 |
| RNF-05 | 2 | `User; cast hashed` | CP-RNF-05 |
| RNF-06 | 4.5 | `Middleware CSRF` | CP-RNF-06 |
| RNF-07 | 4.4 | `Vista; textContent` | CP-RNF-07 |
| RNF-08 | 4.2 y 4.6 | `TareaService` | CP-RNF-08 |
| RNF-09 | 2 | `AuthController` | CP-RNF-09 |
| RNF-10 | 4.4 | `Tarea::datosTablero; eventos` | CP-RNF-10 |
| RNF-11 | 2 y 4.8 | `Docker Compose` | CP-RNF-11 |
| RNF-12 | 4.8 | `Persistencia; difusión` | CP-RNF-12 |
| RNF-13 | 2 | `Volumen postgres_data` | CP-RNF-13 |
| RNF-14 | 2 y 8.2 | `Docker Compose` | CP-RNF-14 |
| RNF-15 | 2 | `TareaService; comandos` | CP-RNF-15 |
| RNF-16 | 2 | `TareaController` | CP-RNF-16 |
| RNF-17 | 4.3 | `Migraciones` | CP-RNF-17 |
| RNF-18 | 4.4 | `Tarea::datosTablero` | CP-RNF-18 |
| RNF-19 | 5 y 6 | `Capas; migraciones` | CP-RNF-19 |
| RNF-20 | 2 y 8.2 | `Docker; variables de entorno` | CP-RNF-20 |
| RNF-21 | 8.2 | `Vistas; Echo` | CP-RNF-21 |
| RNF-22 | 2 y 6 | `config/kanban.php; .env` | CP-RNF-22 |

### 8.2 Condiciones de aceptación

Las pruebas funcionales se ejecutan sobre una base de prueba con los módulos y migraciones de la entrega. Se usan al menos dos sesiones diferentes y se comprueba tanto la interfaz como el estado de PostgreSQL. Una prueba de rechazo verifica también que no haya cambios de estado, conteo, versión ni fechas.

Para RNF-01, RNF-02 y RNF-03 se registra el hardware del servidor, versiones de Docker/PHP/navegadores, cantidad de tarjetas, clientes y condiciones de red. Se usa red local sin pérdidas y con acceso al CDN. RNF-01 mide desde el envío de la petición hasta la actualización visible en los demás clientes suscritos, con 20 acciones válidas y cada observación menor de 2 segundos. RNF-02 mide tres cargas con 200 tarjetas desde la navegación hasta su dibujo completo y exige menos de 3 segundos en cada una. RNF-03 mantiene 20 sesiones autenticadas conectadas durante esas acciones. Son objetivos de aceptación pendientes de medición, no resultados garantizados por usar Docker o Reverb.

La preparación incluye dependencias y versiones fijadas en los archivos de bloqueo de Composer, variables de entorno y migraciones. El arranque ordinario se hace con `docker compose up -d`; las dependencias deben estar listas antes de aceptar operaciones. La portabilidad se verifica en Docker para contenedores Linux en los sistemas previstos, usando imágenes compatibles con la arquitectura de cada equipo.

### 8.3 Secuencia de implementación y documentación

Los módulos 1 y 2 se implementaron tomando la ERS y este diseño como base. [02_implementacion_kanban.md](../04-desarrollo/02_implementacion_kanban.md) recoge los comandos, archivos, decisiones y pruebas utilizados, incluyendo `/sala`, canal privado desde M1, eliminación atómica, fallos de difusión, cast `hashed`, orden y versiones, acciones permitidas, trabajadores con `--no-reload` y pruebas separadas de versión y caída de Reverb. Sus resultados se limitan a la evidencia declarada; siguen pendientes las mediciones de carga y compatibilidad indicadas allí. La ERS conserva su papel de especificación base. Los módulos 3 a 5 continúan pendientes de desarrollo.
