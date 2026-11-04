# Cómo colocar estos archivos

Copia el contenido de esta carpeta en la raíz de tu proyecto Laravel (la carpeta `api/`):

```
api/
├── AGENTS.md
├── PROMPT-INICIAL.md
├── COMO-USAR.md
├── docs/
│   ├── 01-alcance.md
│   ├── 02-arquitectura.md
│   ├── 03-api-v1.md
│   ├── 04-base-de-datos.md
│   ├── 05-fuentes-externas.md
│   ├── 06-calculadoras.md
│   ├── 07-seguridad-y-legal.md
│   └── 08-plan-por-fases.md
└── .agent/
    ├── workflows/
    │   ├── fase-0.md ... fase-9.md
    │   ├── verificar-apis.md
    │   └── revisar-fase.md
    └── skills/          (se llena al instalar los skills)
```

Notas:
- Según la versión de Antigravity, la carpeta puede llamarse `.agent` o `.agents`. Revisa en su panel de personalización cuál lee y renómbrala si hace falta.
- Cada archivo de reglas o workflow tiene un límite de 12,000 caracteres.
- Si Antigravity no muestra los workflows como comandos `/fase-0`, pega el contenido del archivo en el chat.
