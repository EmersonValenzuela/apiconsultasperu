# 01 — Alcance

## Visión
Un solo backend que entrega, por API, información diaria y herramientas útiles para Perú. La web (Astro + React) y una app móvil futura consumen la misma API.

## Módulos del MVP

| # | Módulo | Qué entrega | Prioridad |
|---|---|---|---|
| 1 | Dólar / tipo de cambio | Cotización actual, histórico, conversor | Alta |
| 2 | Sismos | Últimos sismos en Perú, filtros por magnitud y fecha | Alta |
| 3 | Calculadoras | IGV, gratificación, CTS | Alta |
| 4 | Feriados | Feriados y días no laborables por año, próximo feriado | Media |
| 5 | Clima | Clima, UV y calidad del aire por ciudad/distrito de Lima | Media |
| 6 | Consulta RUC | Datos públicos del contribuyente | Media |
| 7 | Consulta DNI | Consulta limitada (ver `07`) | Baja, al final |

## Fuera del MVP (no implementar todavía)
- Cuentas de usuario, login público, favoritos o alertas personalizadas.
- Pagos, planes premium, panel de administración (solo un seeder/comando para datos manuales).
- Notificaciones push (se evaluarán con la app).
- Directorio de oficios.
- Módulo de riesgo por El Niño (idea futura).

## Requisitos transversales
- API pública de solo lectura (salvo calculadoras y DNI, que usan POST) con límites por IP.
- Versionada (`/api/v1`), ligera y con formato de respuesta consistente.
- Datos siempre con fecha de actualización y fuente.
- Pensada para cachearse agresivamente (la web depende de esto para ser rápida y para el SEO).
- Preparada para una app: clave de aplicación opcional, sin cambios de contrato posteriores.

## Criterios de éxito
- Cada endpoint responde en menos de 200 ms desde caché.
- Una caída de una API externa no rompe ningún endpoint.
- Cobertura de tests en fórmulas y servicios críticos.
