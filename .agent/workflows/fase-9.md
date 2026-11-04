---
description: Fase 9 — Endurecimiento y despliegue. Ejecuta solo esta fase.
---

# Fase 9 — Endurecimiento y despliegue

## Antes de escribir código
Lee `AGENTS.md` y estos documentos:
- `docs/02-arquitectura.md`
- `docs/07-seguridad-y-legal.md`

Luego muéstrame un plan breve (archivos que vas a crear o modificar) y **espera mi aprobación**. Si algo de los documentos es ambiguo o falta, pregúntame; no lo inventes.

## Tareas
1. Imagen Docker de producción (PHP-FPM + Nginx) y `docker-compose.yml` / carpeta `docker/` (no existen hasta esta fase).
2. Servicios separados para despliegue en Coolify: app, worker de colas y scheduler, más PostgreSQL 16 y Redis. Cambio de entorno solo por variables (`DB_CONNECTION=pgsql`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`); instalar `predis/predis` o la extensión `redis` en la imagen. Verificar que las migraciones corren en PostgreSQL.
3. Migraciones automáticas en el despliegue y variables de entorno documentadas.
4. `Cache-Control` y `ETag` en todos los GET públicos.
5. Revisión de seguridad según `docs/07` (CORS, cabeceras de seguridad `X-Content-Type-Options`/`Referrer-Policy`/HSTS, límites, `APP_DEBUG=false`).
6. Ejecuta `composer audit` y reporta los hallazgos.

## Criterios de aceptación
- Checklist de `docs/07` completo.
- `composer audit` sin vulnerabilidades críticas.
- Despliegue reproducible desde cero con la documentación del repositorio.

## Al terminar esta fase
1. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test`. Corrige hasta que ambos pasen.
2. Entrégame: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.
3. **No avances a la siguiente fase.** Espera mi confirmación.
