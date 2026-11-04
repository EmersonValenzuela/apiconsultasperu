---
description: Fase 0 — Base del proyecto. Ejecuta solo esta fase.
---

# Fase 0 — Base del proyecto

## Antes de escribir código
Lee `AGENTS.md` y estos documentos:
- `docs/02-arquitectura.md`
- `docs/03-api-v1.md`
- `docs/04-base-de-datos.md`
- `docs/08-plan-por-fases.md`

Luego muéstrame un plan breve (archivos que vas a crear o modificar) y **espera mi aprobación**. Si algo de los documentos es ambiguo o falta, pregúntame; no lo inventes.

## Tareas
1. El proyecto Laravel ya existe: no lo recrees. Revisa su versión y estructura actuales y dime qué encontraste.
2. Desarrollo con SQLite, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=file` (sin Docker ni Redis; ver "Entorno de desarrollo vs producción" en `AGENTS.md`). Deja `.env.example` con todas las variables necesarias (sin valores reales), incluidas las de producción comentadas.
3. Instala y configura Pest, Laravel Pint y Scramble (OpenAPI). Verifica que Sanctum quedó instalado por `install:api`.
4. Zona horaria `America/Lima` e idioma `es`.
5. Crea la estructura `app/Modules/{Dolar,Sismos,Feriados,Clima,Calculadoras,Ruc,Dni}` y `app/Support`, según `AGENTS.md`.
6. Implementa `ApiResponse` (envelope estándar de `docs/03`) y el manejo global de errores en JSON (formato de `docs/02`, códigos 404, 422, 429, 503). Sin trazas fuera de `APP_DEBUG=true`.
7. Crea la migración y el modelo de `fuentes_estado` (ver `docs/04`).
8. Implementa `GET /api/v1/health` (estado de BD, caché y módulos; `ok | degradado | error`; 503 si cae la BD).
9. CORS configurable por `CORS_ALLOWED_ORIGINS`. Límite general de 60/min por IP.
10. Workflow de CI (GitHub Actions) que ejecute Pest y Pint con SQLite, sin servicios adicionales.
11. No implementes ningún módulo de datos todavía.

## Criterios de aceptación
- `php artisan serve` levanta la API con SQLite.
- `/api/v1/health` responde con el envelope estándar.
- Cualquier error devuelve JSON con el formato definido, nunca HTML.
- Pest y Pint pasan.

## Al terminar esta fase
1. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test`. Corrige hasta que ambos pasen.
2. Entrégame: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.
3. **No avances a la siguiente fase.** Espera mi confirmación.
