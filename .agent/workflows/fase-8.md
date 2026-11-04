---
description: Fase 8 — Consulta DNI (solo si yo lo apruebo). Ejecuta solo esta fase.
---

# Fase 8 — Consulta DNI (solo si yo lo apruebo)

## Antes de escribir código
Lee `AGENTS.md` y estos documentos:
- `docs/03-api-v1.md`
- `docs/07-seguridad-y-legal.md`
- `docs/05-fuentes-externas.md`

Luego muéstrame un plan breve (archivos que vas a crear o modificar) y **espera mi aprobación**. Si algo de los documentos es ambiguo o falta, pregúntame; no lo inventes.

## Tareas
1. **Detente y pregúntame si ya consulté con un abogado y si apruebo implementar esta fase.** No sigas sin mi confirmación expresa.
2. `POST /dni/validar`: solo formato, sin consultar fuentes.
3. `POST /dni/consulta` con captcha Turnstile validado en el backend, límite de 5 por minuto y `DNI_ENABLED=false` por defecto (responde 404 si está apagado).
4. Interfaz `DniProvider` y su driver.
5. No guardar DNI ni nombres en la base de datos. Caché solo en el store de caché configurado (nunca en BD) con clave hash y TTL máximo de 1 hora.
6. Respuesta mínima: DNI enmascarado, nombres y apellidos.
7. Un test que compruebe que ningún DNI aparece en logs ni en la base de datos.

## Criterios de aceptación
- Con `DNI_ENABLED=false` el endpoint responde 404.
- El test de privacidad pasa.
- Cumple `docs/07-seguridad-y-legal.md` punto por punto.

## Al terminar esta fase
1. Ejecuta `./vendor/bin/pest` y `./vendor/bin/pint --test`. Corrige hasta que ambos pasen.
2. Entrégame: resumen de lo hecho, comandos para probarlo, decisiones tomadas y pendientes.
3. **No avances a la siguiente fase.** Espera mi confirmación.
