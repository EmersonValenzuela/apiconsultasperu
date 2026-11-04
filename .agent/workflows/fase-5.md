---
description: Fase 5 — Clima. Ejecuta solo esta fase.
---

# Fase 5 — Clima

## Antes de escribir código
Lee `AGENTS.md` y estos documentos:
- `docs/03-api-v1.md`
- `docs/04-base-de-datos.md`
- `docs/05-fuentes-externas.md`

Luego muéstrame un plan breve (archivos que vas a crear o modificar) y **espera mi aprobación**. Si algo de los documentos es ambiguo o falta, pregúntame; no lo inventes.

## Tareas
1. Lee los términos de uso de Open-Meteo y dime si permiten uso en un sitio con publicidad o exigen plan comercial. **Si no está claro, detente y pregúntame antes de seguir.**
2. Confirma las variables disponibles (UV, probabilidad de lluvia, calidad del aire).
3. Interfaz `WeatherProvider` y el driver `open_meteo`.
4. Migración, modelo y seeder de `lugares` (distritos de Lima y capitales de departamento con coordenadas).
5. Job que refresca el clima por lugar con pausas entre llamadas y guarda en el store de caché configurado con TTL de 60 minutos, más una copia de larga duración (≥ 6 h) para servirla con `meta.stale = true` si la fuente falla.
6. Endpoints `GET /clima/lugares` y `GET /clima/{slug}`.
7. Tests con `Http::fake()`.

## Criterios de aceptación
- Una consulta de usuario nunca llama a Open-Meteo.
- Sin dato en caché se responde con lo último guardado o un 503 claro.
- Los términos de uso quedaron revisados y anotados en `docs/05`.

## Al terminar esta fase
1. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test`. Corrige hasta que ambos pasen.
2. Entrégame: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.
3. **No avances a la siguiente fase.** Espera mi confirmación.
