# 07 — Seguridad, privacidad y aspectos legales

## Seguridad general

- HTTPS obligatorio en producción; HSTS activado.
- `APP_DEBUG=false` en producción. Nunca exponer trazas.
- Secretos solo en `.env` (o en el gestor de secretos de Coolify). `.env` fuera del repositorio; `.env.example` sin valores reales.
- CORS con lista blanca configurable (`CORS_ALLOWED_ORIGINS`).
- Validar toda entrada con Form Requests. Consultas con Eloquent o parámetros enlazados (sin SQL concatenado).
- Cabeceras de seguridad: `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`.
- Dependencias: `composer audit` en CI; actualizaciones periódicas.

## Límites de uso (rate limiting)

| Grupo | Límite por IP | Notas |
|---|---|---|
| General (GET públicos) | 60 / min | |
| Calculadoras | 30 / min | |
| RUC | 20 / min | |
| DNI | 5 / min | además de captcha |
| App móvil con `X-App-Key` válida | según `clientes_api.limite_por_minuto` | |

Usar el store de caché configurado como almacén (`file` en desarrollo, Redis en producción). Responder 429 con cabecera `Retry-After`.

## Protección anti-abuso

- Captcha (Cloudflare Turnstile, plan gratuito) obligatorio en `POST /dni/consulta`. Validar el token en el backend.
- Sin endpoints de búsqueda masiva ni por lotes.
- Bloqueo temporal de IPs con comportamiento abusivo (middleware simple sobre el store de caché).

## Datos personales (Ley N.º 29733 de Protección de Datos Personales)

Esto es una guía técnica, no asesoría legal. **El dueño del proyecto debe consultar a un abogado antes de activar la consulta de DNI en producción.**

Reglas técnicas obligatorias:

1. **No guardar DNI ni nombres en la base de datos.** Si se usa caché, solo en el store de caché (nunca en BD), con clave basada en hash y TTL máximo de 1 hora.
2. **No registrar DNI en texto plano** en logs, excepciones ni herramientas de monitoreo. Usar `documento_hash` (HMAC-SHA256 con una clave secreta de la app) en `consultas_log`.
3. **POST, no GET**, para que el DNI no aparezca en URLs ni en logs de acceso del servidor.
4. **Minimización**: devolver solo nombres y apellidos. Sin dirección, fecha de nacimiento ni foto.
5. **Interruptor de apagado**: variable `DNI_ENABLED`. Por defecto `false`.
6. Texto de aviso y política de privacidad en el frontend. La API solo expone `GET /legal/aviso` con el texto vigente, si se decide incluirlo.

## RUC

- Los datos del RUC son de consulta pública, pero igualmente: límites por IP, caché y sin consultas masivas.
- Validar el dígito verificador antes de consultar cualquier fuente (evita llamadas inútiles).
- Revisar y respetar los términos de uso de cada proveedor o portal del que se obtengan los datos.

## Servicios auto-alojados (peru-consult-api u otros)

- Desplegarlos en red interna, sin exposición pública.
- Token fuerte en variable de entorno y comunicación solo desde la API Laravel.
- Revisar la licencia y el origen de los datos antes de usarlos en un sitio con publicidad.

## Cumplimiento con redes de anuncios

- El frontend no debe mostrar datos personales en páginas con anuncios.
- Cada módulo debe poder desactivarse por configuración (`MODULO_<NOMBRE>_ENABLED`) sin desplegar código nuevo.
