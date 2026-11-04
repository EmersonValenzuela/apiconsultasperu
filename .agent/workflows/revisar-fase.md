---
description: Revisa el trabajo de la fase actual contra AGENTS.md y la documentación.
---

# Revisar la fase actual

Revisa lo implementado en esta fase sin modificar nada. Comprueba:

1. Los endpoints cumplen exactamente `docs/03-api-v1.md` (rutas, parámetros, envelope, códigos de error).
2. Las tablas cumplen `docs/04-base-de-datos.md`.
3. Ningún controlador llama a una API externa; todo pasa por Job, Client y Service.
4. Hay stale-while-error y `meta.actualizado_en`, `meta.fuente` y `meta.stale` donde corresponde.
5. No hay valores legales hardcodeados ni `float` en cálculos de dinero.
6. No hay secretos en el repositorio ni datos personales en logs.
7. Los tests cubren los casos de fallo, y ninguno llama a internet.
8. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test` y muestra el resultado.

Entrégame una lista de hallazgos ordenada por gravedad (crítico, importante, menor) y, para cada uno, el archivo y la corrección propuesta. No corrijas nada hasta que yo lo apruebe.
