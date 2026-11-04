---
description: Fase 2 — Sismos. Ejecuta solo esta fase.
---

# Fase 2 — Sismos

## Antes de escribir código
Lee `AGENTS.md` y estos documentos:
- `docs/03-api-v1.md`
- `docs/04-base-de-datos.md`
- `docs/05-fuentes-externas.md`

Luego muéstrame un plan breve (archivos que vas a crear o modificar) y **espera mi aprobación**. Si algo de los documentos es ambiguo o falta, pregúntame; no lo inventes.

## Tareas
1. Confirma en la documentación oficial de USGS los parámetros y el formato GeoJSON. Dime qué verificaste.
2. Interfaz `EarthquakeProvider` y el driver `usgs`.
3. Migración y modelo de `sismos` con `upsert` por `id_externo`.
4. Job cada 5 minutos con registro en `fuentes_estado`.
5. Decide y documenta en el código cómo se filtran los eventos fuera de Perú (el recuadro de `docs/05` incluye países vecinos).
6. Comando `sismos:purgar` (retención de 1 año) programado semanalmente.
7. Endpoints `GET /sismos` y `GET /sismos/{id}` con filtros `min_magnitud`, `dias` y `limite` validados.
8. Tests con fixtures de GeoJSON.

## Criterios de aceptación
- Ejecutar el job dos veces no duplica sismos.
- Los filtros se validan y el orden es del más reciente al más antiguo.
- Con la fuente caída se sirve lo guardado.

## Al terminar esta fase
1. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test`. Corrige hasta que ambos pasen.
2. Entrégame: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.
3. **No avances a la siguiente fase.** Espera mi confirmación.
