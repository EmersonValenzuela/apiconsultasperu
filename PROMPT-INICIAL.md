# Prompt inicial (Fase 0)

Abre la carpeta del backend en Antigravity y pega esto en el chat del agente:

```
Lee AGENTS.md y todos los archivos de la carpeta docs/.

Antes de modificar nada, respóndeme con:
1. Un resumen de lo que entendiste del proyecto (máximo 10 líneas).
2. Las dudas o ambigüedades que encuentres en los documentos.
3. Qué versión de Laravel y qué paquetes encontraste ya instalados en el proyecto.

Después ejecuta el workflow /fase-0. El proyecto Laravel ya está creado:
no lo vuelvas a crear. No avances a la Fase 1.
```

## Orden recomendado

1. Pega el prompt de arriba (Fase 0).
2. Cuando termine y lo hayas revisado: `/revisar-fase`.
3. Antes de la Fase 1: `/verificar-apis` (confirma las APIs externas con su documentación oficial).
4. Luego `/fase-1`, `/revisar-fase`, `/fase-2`, y así sucesivamente.
5. Haz un commit de Git al cerrar cada fase.
