---
description: Fase 7 — Preparación para la app móvil. Ejecuta solo esta fase.
---

# Fase 7 — Preparación para la app móvil

## Antes de escribir código
Lee `AGENTS.md` y estos documentos:
- `docs/03-api-v1.md`
- `docs/04-base-de-datos.md`
- `docs/07-seguridad-y-legal.md`

Luego muéstrame un plan breve (archivos que vas a crear o modificar) y **espera mi aprobación**. Si algo de los documentos es ambiguo o falta, pregúntame; no lo inventes.

## Tareas
1. Migración y modelo de `clientes_api`.
2. Middleware `X-App-Key` que valida el hash de la clave y aplica el límite propio del cliente.
3. Comando `clientes:crear` que muestra la clave una sola vez.
4. Genera la documentación OpenAPI final con Scramble y compárala con `docs/03`.
5. No cambies ningún contrato existente de `/api/v1`.

## Criterios de aceptación
- La OpenAPI generada coincide con `docs/03`.
- Sin `X-App-Key` la API sigue funcionando con los límites por IP.

## Al terminar esta fase
1. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test`. Corrige hasta que ambos pasen.
2. Entrégame: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.
3. **No avances a la siguiente fase.** Espera mi confirmación.
