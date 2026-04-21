# advisor-app

Bootstrap documental y de gobernanza para arrancar el repositorio de implementación en Cursor.

## Qué contiene

- `AGENTS.md` raíz con reglas globales del proyecto
- `src/*/AGENTS.md` por módulo
- `tests/AGENTS.md` con estrategia de testing
- `.cursor/skills/` con workflows operativos reutilizables
- `.cursor/commands/` con atajos para invocar esos workflows
- `docs/context/` con resúmenes operativos para implementación

## Qué no contiene aún

- código Symfony real
- composer.json
- configuración de framework
- migraciones
- CI/CD

## Uso recomendado

1. Carga este repositorio en Cursor.
2. Mantén los prompts manuales en español.
3. Usa los `AGENTS.md` como capa de control.
4. Usa las `skills` para tareas repetibles.
5. Mantén `docs/context/` cortos y alineados con la fuente maestra de arquitectura/producto.

## Convención de idioma

- código, carpetas y nombres técnicos: inglés
- instrucciones operativas y prompts: español
