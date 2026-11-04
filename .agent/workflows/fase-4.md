---
description: Fase 4 — Feriados. Ejecuta solo esta fase.
---

# Fase 4 — Feriados

## Antes de escribir código
Lee `AGENTS.md` y estos documentos:
- `docs/03-api-v1.md`
- `docs/04-base-de-datos.md`
- `docs/05-fuentes-externas.md`

Luego muéstrame un plan breve (archivos que vas a crear o modificar) y **espera mi aprobación**. Si algo de los documentos es ambiguo o falta, pregúntame; no lo inventes.

## Tareas
1. Confirma en la documentación de Nager.Date la ruta para Perú y revisa que los feriados nacionales estén completos. Dime qué encontraste.
2. Interfaz `HolidayProvider` y el driver `nager`.
3. Migración y modelo de `feriados` (único por fecha y nombre).
4. Sincronización diaria que **nunca borra** los registros con `fuente = manual`.
5. Comando `feriados:agregar` para días no laborables y feriados puente.
6. Endpoints `GET /feriados` y `GET /feriados/proximo` (días restantes en zona `America/Lima`).
7. Siembra los feriados del año en curso.

## Criterios de aceptación
- `/feriados/proximo` calcula bien los días restantes.
- La sincronización respeta los registros manuales.
- El parámetro `anio` solo acepta el rango permitido.

## Al terminar esta fase
1. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test`. Corrige hasta que ambos pasen.
2. Entrégame: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.
3. **No avances a la siguiente fase.** Espera mi confirmación.
