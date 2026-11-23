# Kanban App

Carpeta dedicada al frontend del tablero: vistas Blade, CSS, JavaScript y fuentes locales.

| Ruta | Contenido |
|---|---|
| `resources/views/` | Acceso, registro, tablero, prueba de conexión y diseño compartido |
| `public/js/tablero.js` | Tarjetas, botones, arrastre y eventos en vivo |
| `public/js/conexion.js` | Conexión con Reverb |
| `public/css/kanban.css` | Diseño y adaptación a pantallas pequeñas |
| `public/fonts/` | Tipografía local y su licencia |

Para ejecutarlo, inicia los servicios desde `../kanban-board-api`:

```powershell
cd ../kanban-board-api
docker compose up -d
```

Docker monta estas vistas y recursos dentro de Laravel. Los cambios se realizan en esta carpeta y se ven en **http://localhost:8000**; no hace falta un servidor frontend adicional ni compilar los recursos estáticos de esta entrega.
