# 05 — Fuentes externas

> **Regla**: antes de implementar cada cliente, lee la documentación oficial vigente del proveedor y confirma URL, parámetros, límites y términos de uso. Todo lo marcado **VERIFICAR** no está confirmado. No asumas campos que no hayas visto en una respuesta real o en la documentación.

Cada fuente se implementa como un driver detrás de una interfaz, configurable por `.env` (ej. `DOLAR_DRIVER=open_er_api`).

## Dólar / monedas

| Opción | Uso | Notas |
|---|---|---|
| open.er-api.com (`/v6/latest/USD`) | Driver principal inicial | **Confirmado** (documentación oficial https://www.exchangerate-api.com/docs/free). Gratis, sin clave. Incluye PEN. Se actualiza cada 24 h. Límites: responde 429 tras exceso de uso (desbloqueo ~20 min). Requiere atribución ("Rates By Exchange Rate API"). Entrega un valor referencial, no compra/venta. |
| API estadística del BCRP | Driver alternativo | Series oficiales de tipo de cambio. **VERIFICAR** disponibilidad, formato y términos. |
| SUNAT (tipo de cambio publicado) | Referencia de compra/venta oficial | **VERIFICAR** si existe un endpoint utilizable sin captcha. |

Si el driver devuelve un solo valor, guardar `compra = venta` y `tipo = referencial`.

## Sismos

- **USGS Earthquake Catalog**: `https://earthquake.usgs.gov/fdsnws/event/1/query` con `format=geojson`.
- Filtro geográfico inicial (recuadro que cubre Perú; **VERIFICAR** y ajustar): `minlatitude=-19`, `maxlatitude=0.5`, `minlongitude=-82`, `maxlongitude=-68`. Este recuadro incluye zonas de países vecinos: filtrar por el campo `place` o dejar la decisión documentada.
- Parámetros útiles: `starttime`, `orderby=time`, `minmagnitude`.
- Mapear `properties.mag`, `properties.place`, `properties.time` (milisegundos UNIX), `properties.url`, `geometry.coordinates` (`[lon, lat, profundidad]`).
- Alternativa futura: datos del IGP (Instituto Geofísico del Perú). **VERIFICAR** si hay una fuente programática y sus términos.

## Feriados

- **Nager.Date**: `https://date.nager.at/api/v3/PublicHolidays/{anio}/PE`. Gratis. **VERIFICAR** que los feriados nacionales de Perú estén completos.
- Los **días no laborables y feriados puente** se cargan manualmente según decretos oficiales (ver `04`).
- Sincronizar una vez al día; nunca borrar feriados manuales al sincronizar.

## Clima

- **Open-Meteo** (gratis, sin clave): pronóstico en `https://api.open-meteo.com/v1/forecast` y calidad del aire en `https://air-quality-api.open-meteo.com/v1/air-quality`. **VERIFICAR** términos de uso para sitios con publicidad (puede exigir plan comercial) y variables disponibles (UV, probabilidad de lluvia, etc.).
- Una consulta por lugar (`lugares.latitud/longitud`); refrescar con un job que recorra los lugares activos con pausas para respetar límites.

## RUC

Interfaz `RucProvider` con estos drivers, elegibles por `RUC_DRIVER`:

1. `peru_consult` (recomendado al inicio): servicio de código abierto **giansalex/peru-consult-api**, que se despliega con Docker y se autentica con un token. Se auto-aloja como un servicio aparte (por ejemplo, en Coolify) y la API Laravel lo consume por HTTP interno. **VERIFICAR** el estado actual del proyecto, su licencia y de dónde obtiene los datos (puede depender de portales que cambien).
2. `apiconsulta` (opcional): servicio de terceros apiconsulta.dev. **VERIFICAR** plan gratuito, límites y términos.
3. `padron_sunat` (plan B, estable): importar el padrón reducido de RUC que SUNAT publica para descarga, a la tabla `contribuyentes` mediante un comando programado. **VERIFICAR** formato, frecuencia y términos de uso en el sitio de SUNAT.

Si el driver principal falla, el Service intenta el siguiente en el orden configurado (`RUC_DRIVERS=peru_consult,padron_sunat`).

## DNI

Interfaz `DniProvider`. Mismo servicio auto-alojado `peru-consult-api` u otro proveedor con licencia. **No hay API oficial gratuita de RENIEC para público general.** Ver las restricciones legales y de privacidad en `07-seguridad-y-legal.md`: es el módulo más sensible y se implementa al final, desactivado por defecto.

## Reglas comunes de clientes HTTP

- Timeout de 5 s, 2 reintentos con espera creciente y circuit breaker simple (si falla N veces seguidas, no insistir durante X minutos).
- Cabecera `User-Agent: ConsultaHoyPeru/1.0 (+contacto)`.
- Registrar éxito o error en `fuentes_estado`.
- Cada cliente tiene un test con `Http::fake()` y una respuesta de ejemplo guardada en `tests/Fixtures/`.
