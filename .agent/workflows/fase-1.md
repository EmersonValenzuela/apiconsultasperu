---
description: Fase 1 — Dólar y monedas. Ejecuta solo esta fase.
---

# Fase 1 — Dólar y monedas

## Antes de escribir código
Lee `AGENTS.md` y estos documentos:
- `docs/03-api-v1.md`
- `docs/04-base-de-datos.md`
- `docs/05-fuentes-externas.md`
- `docs/02-arquitectura.md`

Luego muéstrame un plan breve (archivos que vas a crear o modificar) y **espera mi aprobación**. Si algo de los documentos es ambiguo o falta, pregúntame; no lo inventes.

## Tareas
1. Antes de codificar, lee la documentación oficial vigente de open.er-api.com y confirma URL, campos, límites y términos. Dime qué confirmaste y qué no.
2. Interfaz `ExchangeRateProvider` y el driver `open_er_api` (elegible con `DOLAR_DRIVER`).
3. Migración y modelo de `cotizaciones_moneda`.
4. Job programado cada 30 minutos con 2 reintentos, y registro de éxito o error en `fuentes_estado`.
5. Stale-while-error: si la fuente falla, sirve el último dato con `meta.stale = true`.
6. Endpoints `GET /dolar`, `GET /dolar/historico` y `GET /monedas/convertir` según `docs/03`.
7. Cabeceras `Cache-Control` y `ETag` en los GET públicos.
8. Tests con `Http::fake()` y una respuesta de ejemplo en `tests/Fixtures/`.

## Criterios de aceptación
- Con la API externa caída, `/dolar` responde con el último dato y `stale = true`.
- El conversor valida monedas y montos (422 con mensaje en español).
- Ningún test llama a internet.

## Al terminar esta fase
1. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test`. Corrige hasta que ambos pasen.
2. Entrégame: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.
3. **No avances a la siguiente fase.** Espera mi confirmación.
