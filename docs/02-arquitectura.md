# 02 — Arquitectura

## Visión general

```
APIs externas ──► Client (driver) ──► Service ──► Base de datos / Caché
                       ▲                              │
              Scheduler / Jobs                        ▼
                                       Controller ──► Resource ──► JSON
                                                         ▲
                                  Web (Astro) y App móvil consumen /api/v1
```

Principio: **los usuarios nunca provocan llamadas a APIs externas** (salvo RUC/DNI bajo demanda con caché y límites). Los datos se actualizan por tareas programadas y la API sirve lo guardado.

## Capas por módulo

- **Contract**: interfaz del proveedor externo (ej. `ExchangeRateProvider`).
- **Client / Driver**: implementación concreta que habla con la API externa. Se elige por configuración (`config/services.php` + `.env`).
- **Service**: lógica de negocio, normalización de datos, escritura en base de datos y caché.
- **Job / Command**: lo ejecuta el scheduler. Reintentos con backoff y registro en `fuentes_estado`.
- **Controller + Resource**: validan entrada, llaman al Service y devuelven el envelope estándar.

## Scheduler y caché

| Módulo | Frecuencia de actualización | TTL de caché | Datos viejos aceptables si falla la fuente |
|---|---|---|---|
| Dólar | cada 30 min | 35 min | hasta 24 h |
| Sismos | cada 5 min | 6 min | hasta 24 h |
| Feriados | 1 vez al día (verifica) | 24 h | indefinido (están en BD) |
| Clima | cada 30–60 min por lugar | 60 min (+ copia de larga duración ≥ 6 h para stale) | hasta 6 h |
| RUC | bajo demanda | 7 días | indefinido (último dato guardado) |
| DNI | bajo demanda | máx. 1 h, sin guardar en BD | no aplica |

Regla **stale-while-error**: si el refresco falla, se mantiene el último dato y la respuesta incluye `meta.stale = true`. Registrar el error en `fuentes_estado`.

## Cabeceras HTTP (clave para el SEO y la app)

- `Cache-Control: public, max-age=<TTL>, stale-while-revalidate=<TTL*2>` en endpoints GET públicos.
- `ETag` en respuestas GET para permitir `304 Not Modified`.
- CORS con lista blanca (dominio de la web y orígenes de desarrollo), configurable por `.env`.
- Siempre `Content-Type: application/json; charset=utf-8`.

## Formato de errores

```json
{
  "error": {
    "codigo": "validacion_fallida",
    "mensaje": "El campo ruc debe tener 11 dígitos.",
    "detalles": { "ruc": ["El campo ruc debe tener 11 dígitos."] }
  }
}
```

Códigos HTTP: 200, 304, 404 (no encontrado), 422 (validación), 429 (límite), 503 (fuente no disponible y sin dato guardado), 500 (error interno).
Nunca exponer trazas ni detalles internos fuera de `APP_DEBUG=true`.

### Catálogo de `error.codigo`

| `codigo` | HTTP | Cuándo |
|---|---|---|
| `validacion_fallida` | 422 | Entrada inválida (incluye `detalles` por campo) |
| `no_encontrado` | 404 | Ruta o recurso inexistente |
| `limite_excedido` | 429 | Se superó el límite de uso (incluye cabecera `Retry-After`) |
| `fuente_no_disponible` | 503 | La fuente externa o la base de datos no responde y no hay dato guardado |
| `error_interno` | 500 | Cualquier otro error no controlado |

Otros códigos HTTP de error del framework (405, 401, etc.) se devuelven con el mismo formato.

## Observabilidad

- Endpoint `GET /api/v1/health` con estado de BD, caché y de cada módulo (`fuentes_estado`).
  - `estado`: `ok` (todo bien), `degradado` (caché caída o algún módulo con error) o `error` (base de datos caída).
  - HTTP 200 con `ok` o `degradado`; HTTP 503 si cae la base de datos.
- Logs estructurados (JSON) sin datos personales.
- Preparado para Sentry o similar (variable `.env`, desactivado por defecto).

## Versionado

- Prefijo `/api/v1`. Los cambios que rompen contrato van a `/api/v2`.
- No quitar ni renombrar campos en `v1`; solo agregar.

## Despliegue

- Imagen Docker (PHP-FPM + Nginx), con worker de colas y proceso de scheduler (`schedule:work` o cron cada minuto).
- Servicios: app, worker, scheduler, PostgreSQL, Redis. Desplegable en Coolify.
- Variables de entorno documentadas en `.env.example`.

## Entorno de desarrollo vs producción

| Aspecto | Desarrollo (local) | Producción (Coolify) |
|---|---|---|
| Base de datos | SQLite | PostgreSQL 16 |
| Caché (datos, TTL, stale-while-error) | `CACHE_STORE=file` | `CACHE_STORE=redis` |
| Rate limiting | store de caché configurado (`file`) | store de caché configurado (`redis`) |
| Colas | `QUEUE_CONNECTION=sync` | `QUEUE_CONNECTION=redis` + worker |
| Scheduler | `php artisan schedule:work` manual | proceso `scheduler` en Coolify |
| Docker | No | Sí (Fase 9) |

Donde este documento o los demás dicen "Redis", léase "el store de caché configurado". El código usa las fachadas `Cache` y `RateLimiter`, nunca `Redis::`. Las migraciones son portables entre SQLite y PostgreSQL (`json()` en vez de `jsonb()`, sin SQL específico de un motor). Se cambia de entorno solo con variables de `.env`.
