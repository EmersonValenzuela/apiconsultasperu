# AGENTS.md — Backend de Consulta Hoy Perú

Instrucciones para la IA que desarrolla este proyecto. Léelo completo antes de escribir código.
(Si usas Claude Code, copia este archivo como `CLAUDE.md`.)

## 0. Estado inicial del repositorio

- El proyecto Laravel **ya está creado** en esta carpeta. **No lo vuelvas a crear** ni lo regeneres.
- `AGENTS.md` y `docs/` están en la raíz. Los workflows de cada fase están en `.agent/workflows/` (se ejecutan escribiendo `/fase-0`, `/fase-1`, etc.).
- Los skills instalados están en `.agent/skills/`. Úsalos, pero si contradicen este archivo, manda este archivo.
- Este es un repositorio **solo de backend (API)**. El frontend (Astro + React) es otro repositorio.

## 1. Qué es este proyecto

**Consulta Hoy Perú** es un portal de utilidades e información diaria para Perú:
tipo de cambio, sismos, feriados, clima, calculadoras (IGV, CTS, gratificación), consulta RUC y consulta DNI.
Se monetiza con anuncios en el frontend y debe posicionar bien en Google.

Este repositorio es **solo el backend: una API REST en Laravel**. No sirve HTML.
La consumen:
1. La web (proyecto aparte: Astro + React).
2. Una app móvil futura.

Por eso: toda la lógica de negocio vive aquí, y la API debe ser estable, versionada y ligera.

## 2. Documentación (léela en este orden)

| Archivo | Contenido |
|---|---|
| `docs/01-alcance.md` | Qué entra en el MVP y qué no |
| `docs/02-arquitectura.md` | Capas, flujo de datos, caché, scheduler, errores |
| `docs/03-api-v1.md` | Contrato de todos los endpoints |
| `docs/04-base-de-datos.md` | Tablas y migraciones |
| `docs/05-fuentes-externas.md` | APIs externas por módulo, límites y fallbacks |
| `docs/06-calculadoras.md` | Fórmulas laborales y tributarias |
| `docs/07-seguridad-y-legal.md` | Seguridad, privacidad, DNI/RUC |
| `docs/08-plan-por-fases.md` | Plan de trabajo y prompts por fase |

## 2.1 Stack

- PHP 8.3+ y Laravel 13 (o la última versión estable; verifica compatibilidad de paquetes).
- Base de datos: SQLite en desarrollo, PostgreSQL 16 en producción.
- Caché, colas y rate limiting sobre el **store de caché configurado** (`file` en desarrollo, Redis en producción). El código nunca usa Redis directamente.
- Laravel Scheduler para actualizar datos externos.
- Laravel Sanctum instalado y listo (sin login público por ahora).
- Pest para tests, Laravel Pint para estilo.
- Scramble (o similar) para generar OpenAPI automáticamente.
- Laravel Boost como dependencia de desarrollo (MCP para el agente).
- Despliegue con Docker en Coolify (Fase 9).

## 2.2 Entorno de desarrollo vs producción

| Aspecto | Desarrollo (local, Laragon) | Producción (Coolify) |
|---|---|---|
| Base de datos | SQLite (`DB_CONNECTION=sqlite`) | PostgreSQL 16 (`DB_CONNECTION=pgsql`) |
| Caché / rate limiting | `CACHE_STORE=file` | `CACHE_STORE=redis` |
| Colas | `QUEUE_CONNECTION=sync` | `QUEUE_CONNECTION=redis` |
| Sesiones | `SESSION_DRIVER=file` | `SESSION_DRIVER=redis` o `file` |
| Docker | No se usa | Imagen PHP-FPM + Nginx (Fase 9) |

Reglas para que ambos entornos funcionen igual:
- Se cambia de motor **solo con variables de entorno**, sin tocar código.
- Migraciones portables: `json()` en vez de `jsonb()`, nada de SQL propio de un motor; usa Eloquent / Query Builder.
- Usa siempre las fachadas `Cache` y `RateLimiter` (store configurado), nunca `Redis::` directamente.
- Los tests corren con SQLite en memoria y caché `array`.

## 3. Reglas de trabajo (obligatorias)

1. **Trabaja por fases** según `docs/08-plan-por-fases.md`. No avances a la siguiente fase sin tests en verde y sin que el humano lo confirme.
2. **No inventes endpoints, campos ni tablas** fuera de la documentación. Si algo falta o es ambiguo, pregunta.
3. **Verifica el contrato real de cada API externa** leyendo su documentación oficial antes de codificar. Si no puedes verificarlo, dilo y no asumas campos.
4. **Los controladores no llaman a APIs externas.** Flujo: Scheduler/Job → Client externo → Service → Base de datos/Caché → Controller → Resource.
5. **Cada integración externa va detrás de una interfaz** (`Contracts`) con drivers intercambiables (ver `docs/05`).
6. **Nada de valores legales hardcodeados** (UIT, RMV, tasas). Van en la tabla `parametros` con fuente y fecha de verificación.
7. **Respuestas siempre con el formato estándar** (envelope) de `docs/03`. Todo dato externo incluye `meta.actualizado_en` y `meta.fuente`.
8. **Resiliencia**: si la API externa falla, sirve el último dato guardado marcado como `desactualizado` (`meta.stale = true`). Nunca devuelvas un 500 por una API externa caída.
9. **Seguridad y privacidad**: sin secretos en el repo (usa `.env` y mantén `.env.example` al día). Nunca guardes ni registres DNI en texto plano. Sigue `docs/07`.
10. **Tests**: cada módulo con tests de feature (endpoints) y unitarios (servicios y fórmulas). Las APIs externas se simulan con `Http::fake()`. Nunca llames a internet en los tests.
11. **Convenciones**: código (clases, métodos, variables) en inglés; textos al usuario, mensajes de error y documentación en español. PSR-12 con Pint.
12. **Commits pequeños** con Conventional Commits (`feat:`, `fix:`, `test:`, `docs:`, `chore:`).
13. Al terminar cada fase entrega: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.

## 4. Estructura de carpetas objetivo

```
app/
  Modules/
    Dolar/        { Contracts, Clients, Services, Jobs, Models, Http/{Controllers,Resources,Requests} }
    Sismos/       (igual)
    Feriados/     (igual)
    Clima/        (igual)
    Calculadoras/ { Services, Http/... }   // sin cliente externo
    Ruc/          (igual, con drivers)
    Dni/          (igual, con drivers)
  Support/
    ApiResponse.php        // envelope estándar
    Cache/                 // helpers de caché con stale-while-error
    Http/Middleware/       // ApiKey opcional, ForceJson
routes/
  api.php                  // grupo /v1 que incluye los módulos
database/migrations/
tests/Feature/ tests/Unit/
docs/
```

## 5. Definición de "terminado" para cualquier tarea

- Cumple lo descrito en `docs/03-api-v1.md` (rutas, parámetros, respuesta).
- Tiene tests y `./vendor/bin/pest` pasa.
- `./vendor/bin/pint` sin cambios pendientes.
- Falla de forma controlada (validación 422, límite 429, fuente caída con `stale`).
- Documentado (OpenAPI generado y docs actualizadas si cambió algo).
