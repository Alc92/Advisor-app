# Proyecto Cursor — advisor-app

## Para qué sirve este archivo

Este archivo es una guía rápida para trabajar con Cursor en este repositorio.

No sustituye a `AGENTS.md`.  
Las reglas obligatorias del proyecto viven en:

1. `AGENTS.md` raíz
2. `AGENTS.md` del módulo afectado
3. `tests/AGENTS.md` cuando toque testing
4. `.cursor/skills/` según la tarea

## Cómo usar este repo con Cursor

Antes de pedir cambios:

1. Identifica el módulo afectado:
    - `Advisor`
    - `Catalog`
    - `Identity`
    - `Shared` solo transversal real

2. Consulta el `AGENTS.md` correspondiente.

3. Usa `docs/context/` como resumen operativo, no como sustituto de las fuentes maestras.

4. Usa `.cursor/skills/` para workflows repetibles.

5. Usa `.cursor/commands/` para tareas frecuentes.

## Flujo recomendado

Para features o cambios ambiguos:

```text
/plan-first
/implement-use-case
/write-tests
/review
```

Para refactors:

```text
/refactor-safe
/review
```

Para catálogo:

```text
/catalog-task
```

## Cuándo pedir plan primero

Usa `/plan-first` si el cambio:

- afecta varios módulos
- toca persistencia
- implica autenticación
- cambia contratos
- crea más de 3 archivos
- tiene ambigüedad funcional

## Idioma

- Código, clases, namespaces, métodos y carpetas: inglés.
- Prompts, `AGENTS.md`, skills, commands y documentación operativa: español.

## Optimización de coste / tokens

Prefiere:

- una tarea cada vez
- prompts concretos
- contexto del módulo afectado
- cambios pequeños
- plan antes que reescritura

Evita:

- scaffolding masivo
- abrir demasiados archivos sin necesidad
- mezclar feature + refactor + tests en una sola petición
- crear muchos archivos cuando bastan pocos

## Señales de mala propuesta

Desconfía si Cursor propone:

- demasiadas clases nuevas
- interfaces vacías
- patrones por moda
- lógica en controllers
- `Shared` como cajón desastre
- refactor masivo sin motivo
- cambios de módulo sin justificación
- ORM como centro del diseño

## Regla final

`PROJECT.md` orienta el uso de Cursor.  
`AGENTS.md` manda sobre arquitectura e implementación.
