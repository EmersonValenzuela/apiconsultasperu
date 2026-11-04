# 03 — Contrato de la API v1

Base: `/api/v1`. Todas las respuestas son JSON en español, con fechas en ISO 8601 (zona `America/Lima`).

## Envelope estándar (éxito)

```json
{
  "data": { },
  "meta": {
    "actualizado_en": "2026-11-04T10:30:00-05:00",
    "fuente": "open.er-api.com",
    "stale": false
  }
}
```

- `meta.actualizado_en` y `meta.fuente`: obligatorios en datos que vienen de una fuente externa.
- Los endpoints de lista agregan `meta.total` y, si hay paginación, `meta.pagina` y `meta.por_pagina`.
- Las calculadoras devuelven `meta.referencial = true`.

## Autenticación y límites

- Pública, sin login. Cabecera opcional `X-App-Key` para la app móvil (límites más altos).
- Límites por IP (ver `07`): general 60/min; RUC 20/min; DNI 5/min; calculadoras 30/min.

---

## Sistema

### `GET /health`
Estado de la API y de cada módulo.
```json
{ "data": { "estado": "ok", "modulos": { "dolar": { "ultima_ok": "2026-11-04T10:30:00-05:00", "error": null } } } }
```

---

## Dólar y monedas

### `GET /dolar`
Cotización actual del dólar frente al sol.
```json
{
  "data": { "moneda": "USD", "compra": 3.70, "venta": 3.72, "fecha": "2026-11-04" },
  "meta": { "actualizado_en": "...", "fuente": "...", "stale": false }
}
```
Si la fuente solo entrega un valor único, `compra` y `venta` se reportan iguales y `meta.tipo = "referencial"`.

### `GET /dolar/historico`
Query: `desde` (YYYY-MM-DD), `hasta` (YYYY-MM-DD), `moneda` (default `USD`).
Máximo 366 días por consulta. Respuesta: arreglo de `{ fecha, compra, venta }`.

### `GET /monedas/convertir`
Query: `de` (ISO 4217), `a` (ISO 4217), `monto` (número > 0).
```json
{ "data": { "de": "USD", "a": "PEN", "monto": 100, "resultado": 372.00, "tasa": 3.72 } }
```
Monedas soportadas: `PEN, USD, EUR, GBP, BRL, CLP, COP, MXN, ARS` (ampliable por configuración).

---

## Sismos

### `GET /sismos`
Query: `min_magnitud` (default 0), `dias` (1–30, default 7), `limite` (1–100, default 50).
```json
{
  "data": [
    {
      "id": 1234,
      "magnitud": 4.8,
      "lugar": "45 km SW de Arequipa",
      "profundidad_km": 35.2,
      "latitud": -16.8,
      "longitud": -72.1,
      "ocurrido_en": "2026-11-04T03:12:00-05:00",
      "fuente": "USGS",
      "url": "https://..."
    }
  ],
  "meta": { "total": 1, "actualizado_en": "...", "fuente": "USGS", "stale": false }
}
```
Orden: más reciente primero.

### `GET /sismos/{id}`
Detalle de un sismo. 404 si no existe.

---

## Feriados

### `GET /feriados`
Query: `anio` (default año actual; rango permitido: año actual −1 a +1).
Respuesta: arreglo de `{ fecha, nombre, tipo }`, con `tipo` en `feriado | dia_no_laborable | feriado_puente`.

### `GET /feriados/proximo`
Devuelve el próximo feriado a partir de hoy y los días que faltan.

---

## Clima

### `GET /clima/lugares`
Lista de lugares soportados (`slug`, `nombre`, `departamento`, `distrito`). Se llena con un seeder (distritos de Lima y capitales de departamento).

### `GET /clima/{slug}`
Clima actual y pronóstico de 7 días: temperatura, sensación térmica, humedad, viento, índice UV, probabilidad de lluvia y calidad del aire (si hay dato). Ejemplo de `slug`: `miraflores`, `arequipa`.

---

## Calculadoras (referenciales)

Detalle de fórmulas en `06-calculadoras.md`.

### `POST /calculadoras/igv`
```json
{ "monto": 1000, "tipo": "base" }
```
`tipo`: `base` (el monto no incluye IGV) o `total` (el monto ya incluye IGV).
Respuesta: `{ base, igv, total, tasa }`.

### `POST /calculadoras/gratificacion`
```json
{ "sueldo": 3000, "asignacion_familiar": false, "meses_trabajados": 6, "promedio_variables": 0, "tipo_seguro": "essalud" }
```
`meses_trabajados`: 1–6 (meses completos del semestre). `tipo_seguro`: `essalud | eps`.
Respuesta: `{ remuneracion_computable, gratificacion, bonificacion_extraordinaria, total }`.

### `POST /calculadoras/cts`
```json
{ "sueldo": 3000, "asignacion_familiar": false, "ultima_gratificacion": 3000, "meses": 6, "dias": 0 }
```
Respuesta: `{ remuneracion_computable, cts_por_meses, cts_por_dias, total }`.

---

## RUC

### `GET /ruc/{ruc}`
`ruc`: 11 dígitos, validados con el dígito verificador.
```json
{
  "data": {
    "ruc": "20100070970",
    "razon_social": "...",
    "estado": "ACTIVO",
    "condicion": "HABIDO",
    "direccion": "...",
    "departamento": "...",
    "provincia": "...",
    "distrito": "..."
  },
  "meta": { "actualizado_en": "...", "fuente": "...", "stale": false }
}
```
Errores: 422 (formato inválido), 404 (no existe), 503 (fuente caída y sin dato guardado).

---

## DNI

### `POST /dni/consulta`
Se usa POST para que el DNI no quede en URLs ni en logs de acceso.
```json
{ "dni": "12345678", "captcha_token": "..." }
```
Respuesta mínima: `{ dni_enmascarado: "1234****", nombres: "...", apellido_paterno: "...", apellido_materno: "..." }`.
Reglas completas de privacidad en `07-seguridad-y-legal.md`. Este endpoint se implementa en la última fase y puede quedar desactivado por configuración (`DNI_ENABLED=false`).

### `POST /dni/validar`
Valida solo el formato (8 dígitos) sin consultar ninguna fuente. Siempre disponible.
