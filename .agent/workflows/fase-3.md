---
description: Fase 3 — Calculadoras. Ejecuta solo esta fase.
---

# Fase 3 — Calculadoras

## Antes de escribir código
Lee `AGENTS.md` y estos documentos:
- `docs/03-api-v1.md`
- `docs/06-calculadoras.md`
- `docs/04-base-de-datos.md`

Luego muéstrame un plan breve (archivos que vas a crear o modificar) y **espera mi aprobación**. Si algo de los documentos es ambiguo o falta, pregúntame; no lo inventes.

## Tareas
1. Migración, modelo y seeder de `parametros` con los valores marcados VERIFICAR. Avísame cuáles son para que yo los confirme.
2. Service que obtiene el parámetro vigente a la fecha de cálculo y rechaza el cálculo si falta uno.
3. Aritmética decimal exacta (`brick/math` o `bcmath`), sin `float` en dinero.
4. Endpoints `POST /calculadoras/igv`, `/gratificacion` y `/cts` según `docs/03` y `docs/06`.
5. Respuestas con `meta.referencial = true`.
6. Implementa como tests los 6 casos de prueba de `docs/06`, más tests de validación de entradas.

> Pendiente humano: las fórmulas y parámetros deben ser validados con la normativa vigente antes de publicar.

## Criterios de aceptación
- Los 6 casos de `docs/06` pasan.
- No hay valores legales hardcodeados en el código.
- Entradas inválidas devuelven 422 con mensajes claros.

## Al terminar esta fase
1. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test`. Corrige hasta que ambos pasen.
2. Entrégame: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.
3. **No avances a la siguiente fase.** Espera mi confirmación.
