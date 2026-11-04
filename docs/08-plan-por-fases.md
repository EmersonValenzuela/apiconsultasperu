# 08 — Plan de trabajo por fases

Cómo usar este plan (también están como workflows en `.agent/workflows/`, que se ejecutan con `/fase-0`, `/fase-1`, etc.): entrega a la IA `AGENTS.md` y el archivo de la fase actual. Al terminar cada fase, revisa el resultado, ejecuta los tests y recién entonces pasa a la siguiente. Cada fase indica un **prompt sugerido**, **entregables** y **criterios de aceptación**.

---

## Fase 0 — Base del proyecto

**Prompt sugerido**
> Lee `AGENTS.md` y todos los archivos en `docs/`. El proyecto Laravel ya está creado en esta carpeta (no lo recrees). Configura SQLite (desarrollo), Pest, Pint y Scramble sobre él. Implementa la Fase 0 de `docs/08-plan-por-fases.md`: estructura de módulos, `ApiResponse` (envelope estándar), manejo global de errores en JSON, `GET /api/v1/health`, CORS configurable, límite general de 60/min y `.env.example`. No implementes ningún módulo de datos todavía. Docker queda para la Fase 9.

**Entregables**: proyecto arrancando con `php artisan serve`, `/api/v1/health` funcionando, estructura `app/Modules/*`, tabla `fuentes_estado`, CI con Pest y Pint (SQLite).
**Aceptación**: tests verdes, errores siempre en el formato de `docs/02`.

---

## Fase 1 — Dólar y monedas

**Prompt sugerido**
> Implementa el módulo Dólar según `docs/03`, `docs/04` y `docs/05`: interfaz `ExchangeRateProvider`, driver `open_er_api`, job programado cada 30 min, tabla `cotizaciones_moneda`, endpoints `/dolar`, `/dolar/historico` y `/monedas/convertir`, caché con stale-while-error. Antes de codificar, verifica la respuesta real de la API en su documentación. Incluye tests con `Http::fake()`.

**Aceptación**: si la API externa falla, `/dolar` devuelve el último dato con `meta.stale = true`; el conversor valida monedas y montos.

---

## Fase 2 — Sismos

**Prompt sugerido**
> Implementa el módulo Sismos con el driver USGS (GeoJSON): job cada 5 min con `upsert` por `id_externo`, tabla `sismos`, comando `sismos:purgar`, endpoints `/sismos` y `/sismos/{id}`. Documenta en el código cómo se filtran los eventos fuera de Perú.

**Aceptación**: sin duplicados al repetir el job; filtros `min_magnitud`, `dias` y `limite` validados.

---

## Fase 3 — Calculadoras

**Prompt sugerido**
> Implementa `/calculadoras/igv`, `/gratificacion` y `/cts` según `docs/06`. Crea la tabla `parametros` con su seeder (valores marcados VERIFICAR) y un Service que toma el parámetro vigente. Usa aritmética decimal exacta. Implementa como tests los 6 casos de `docs/06`.

**Aceptación**: los 6 casos pasan; sin `float` en cálculos monetarios; respuestas con `meta.referencial`.
**Pendiente humano**: validar fórmulas y parámetros con la normativa vigente.

---

## Fase 4 — Feriados

**Prompt sugerido**
> Implementa el módulo Feriados: driver Nager.Date, sincronización diaria sin borrar los registros manuales, comando `feriados:agregar` para días no laborables y puentes, endpoints `/feriados` y `/feriados/proximo`. Siembra los feriados del año en curso.

**Aceptación**: `/feriados/proximo` calcula bien los días restantes en zona `America/Lima`.

---

## Fase 5 — Clima

**Prompt sugerido**
> Implementa el módulo Clima con Open-Meteo: tabla `lugares` con seeder (distritos de Lima y capitales de departamento), job que refresca el clima por lugar con pausas entre llamadas, caché de 60 min en el store configurado más una copia de larga duración (≥ 6 h) para servirla como stale si la fuente falla, endpoints `/clima/lugares` y `/clima/{slug}`.

**Aceptación**: una consulta de usuario nunca llama a Open-Meteo; si no hay dato en caché, se responde con el último guardado o 503 claro.

---

## Fase 6 — Consulta RUC

**Prompt sugerido**
> Implementa `GET /ruc/{ruc}` con validación del dígito verificador, interfaz `RucProvider` y el driver `peru_consult` (servicio auto-alojado consumido por HTTP interno). Caché de 7 días, límite de 20/min y registro en `consultas_log` con hash. Deja preparado el driver `padron_sunat` como clase vacía documentada.

**Aceptación**: RUC inválido responde 422 sin llamar a ninguna fuente; 404 y 503 bien diferenciados.

---

## Fase 7 — App móvil: preparación

**Prompt sugerido**
> Implementa `clientes_api` y el middleware `X-App-Key` con límites propios, el comando `clientes:crear` y la documentación OpenAPI final. No cambies ningún contrato existente de `/api/v1`.

**Aceptación**: la OpenAPI generada coincide con `docs/03`.

---

## Fase 8 — Consulta DNI (solo si el humano lo aprueba)

**Prompt sugerido**
> Implementa `POST /dni/validar` y `POST /dni/consulta` cumpliendo estrictamente `docs/07`: Turnstile, límite 5/min, sin guardar datos personales, hash en logs, `DNI_ENABLED=false` por defecto.

**Aceptación**: con `DNI_ENABLED=false` el endpoint responde 404; ningún DNI aparece en logs ni en la BD (hay un test que lo comprueba).

---

## Fase 9 — Endurecimiento y despliegue

**Prompt sugerido**
> Prepara el despliegue en Coolify: imagen Docker de producción y `docker-compose.yml`, servicios separados (app, worker, scheduler) con PostgreSQL 16 y Redis (`DB_CONNECTION=pgsql`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`), variables de entorno documentadas, migraciones automáticas, cabeceras de caché y ETag en todos los GET públicos, cabeceras de seguridad, y la revisión de seguridad de `docs/07`.

**Aceptación**: checklist de `docs/07` completo; `composer audit` sin vulnerabilidades críticas.

---

## Pendientes anotados para fases futuras

- **`GET /legal/aviso`** (mencionado en `docs/07`): agregarlo a `docs/03` cuando toque (Fase 8, si se decide incluirlo).
- **`MODULO_<NOMBRE>_ENABLED`** (mencionado en `docs/07`): definir en `docs/03` el comportamiento cuando un módulo está apagado (se sugiere 404 `no_encontrado`) al implementar cada módulo.
- **Clima**: además de la caché de 60 min, guardar una copia de larga duración (≥ 6 h) para servirla con `meta.stale = true` si la fuente falla (Fase 5).
- **Producción**: Docker, PostgreSQL 16 y Redis se configuran en la Fase 9; en desarrollo se usa SQLite + caché `file` (ver `AGENTS.md` §2.2).
