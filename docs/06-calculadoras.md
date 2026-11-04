# 06 — Calculadoras

> **Todas las calculadoras son referenciales** (régimen laboral general de la actividad privada). No reemplazan asesoría legal o contable. Cada respuesta incluye `meta.referencial = true`.
> **Un humano debe validar las fórmulas y los parámetros con la normativa vigente antes de publicar.** Los valores numéricos van en la tabla `parametros`, nunca en el código.

## Parámetros (tabla `parametros`)

| Clave | Descripción | Estado |
|---|---|---|
| `igv_tasa` | Tasa total del IGV (incluye IPM) | VERIFICAR (se espera 0.18) |
| `rmv` | Remuneración mínima vital | VERIFICAR valor vigente |
| `asignacion_familiar_pct` | Porcentaje de la RMV | VERIFICAR (se espera 0.10) |
| `bono_extra_essalud_pct` | Bonificación extraordinaria sobre la gratificación (afiliados a EsSalud) | VERIFICAR (se espera 0.09) |
| `bono_extra_eps_pct` | Ídem para afiliados a EPS | VERIFICAR (se espera 0.0675) |
| `uit` | Unidad Impositiva Tributaria | VERIFICAR valor del año |

Cada parámetro guarda `fuente_legal` y `verificado_en`. El Service rechaza cálculos si el parámetro requerido no existe.

## IGV

- `tipo = base`: `igv = base × tasa`, `total = base + igv`.
- `tipo = total`: `base = total / (1 + tasa)`, `igv = total − base`.
- Redondear a 2 decimales (`ROUND_HALF_UP`) solo al presentar; calcular con precisión completa (usar `brick/math` o `bcmath`, no `float`).

## Remuneración computable (RC)

```
RC = sueldo_basico
   + asignacion_familiar        (si aplica: rmv × asignacion_familiar_pct)
   + promedio_variables         (comisiones, horas extra, etc., si el usuario lo indica)
```

## Gratificación (julio y diciembre)

- Corresponde una remuneración por cada semestre completo: enero–junio (se paga en julio) y julio–diciembre (se paga en diciembre).
- Proporcional por meses completos trabajados en el semestre.

```
gratificacion = (RC / 6) × meses_trabajados        (meses_trabajados entre 1 y 6)
bonificacion_extraordinaria = gratificacion × bono_pct   (essalud o eps según tipo_seguro)
total = gratificacion + bonificacion_extraordinaria
```

## CTS (mayo y noviembre)

- Semestres de cálculo: noviembre–abril (depósito en mayo) y mayo–octubre (depósito en noviembre).

```
RC_cts = RC + (ultima_gratificacion / 6)
cts_por_meses = (RC_cts / 12) × meses
cts_por_dias  = (RC_cts / 360) × dias            (dias entre 0 y 29)
total = cts_por_meses + cts_por_dias
```

## Casos de prueba obligatorios (deben pasar como tests unitarios)

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | IGV base 1000, tasa 0.18 | igv 180.00, total 1180.00 |
| 2 | IGV total 1180, tasa 0.18 | base 1000.00, igv 180.00 |
| 3 | Gratificación: sueldo 3000, sin asignación, 6 meses, EsSalud (9%) | gratificación 3000.00, bono 270.00, total 3270.00 |
| 4 | Gratificación: sueldo 3000, 4 meses, EsSalud (9%) | gratificación 2000.00, bono 180.00, total 2180.00 |
| 5 | CTS: sueldo 3000, última gratificación 3000, 6 meses, 0 días | RC_cts 3500.00, total 1750.00 |
| 6 | CTS: sueldo 3000, última gratificación 3000, 3 meses, 15 días | cts_por_meses 875.00, cts_por_dias 145.83, total 1020.83 |

Los casos 3 a 6 usan porcentajes esperados y deben usar los valores de `parametros` sembrados con esos mismos números en el entorno de pruebas.

## Validaciones de entrada

- `sueldo`: número > 0 y ≤ 1 000 000.
- `meses_trabajados`: entero 1–6. `meses` (CTS): entero 0–6. `dias`: entero 0–29.
- Rechazar con 422 y mensaje claro en español.

## Extensiones futuras (no implementar ahora)

Liquidación (vacaciones truncas, indemnización), sueldo neto con descuentos de AFP/ONP y renta de quinta categoría, y regímenes MYPE.
