---
description: Fase 6 — Consulta RUC. Ejecuta solo esta fase.
---

# Fase 6 — Consulta RUC

## Antes de escribir código
Lee `AGENTS.md` y estos documentos:
- `docs/03-api-v1.md`
- `docs/05-fuentes-externas.md`
- `docs/07-seguridad-y-legal.md`
- `docs/04-base-de-datos.md`

Luego muéstrame un plan breve (archivos que vas a crear o modificar) y **espera mi aprobación**. Si algo de los documentos es ambiguo o falta, pregúntame; no lo inventes.

## Tareas
1. Revisa el estado, la licencia y el origen de datos de `giansalex/peru-consult-api`. Dime si es viable y qué riesgos ves.
2. Validación del RUC con dígito verificador **antes** de consultar cualquier fuente.
3. Interfaz `RucProvider`, el driver `peru_consult` (servicio auto-alojado por HTTP interno) y la clase vacía y documentada del driver `padron_sunat`.
4. Orden de drivers configurable (`RUC_DRIVERS`) con failover.
5. Endpoint `GET /ruc/{ruc}` con caché de 7 días y límite de 20 por minuto.
6. Registro en `consultas_log` usando hash (nunca el documento en claro) y comando `consultas:purgar`.
7. Tests con `Http::fake()` y fixtures.

## Criterios de aceptación
- RUC inválido devuelve 422 sin llamar a ninguna fuente.
- 404 y 503 se distinguen correctamente.
- Ningún RUC aparece en claro en logs ni en `consultas_log`.

## Al terminar esta fase
1. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test`. Corrige hasta que ambos pasen.
2. Entrégame: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.
3. **No avances a la siguiente fase.** Espera mi confirmación.
