# SKILL: web-screen

## Objetivo

Implementar o modificar una pantalla web/Twig del MVP sin romper la arquitectura ni convertir el proyecto en API-first.

## Cuándo usar

- nuevas pantallas del wizard
- páginas de resultado
- pantallas autenticadas de continuidad
- formularios simples ligados a casos de uso

## Fuentes obligatorias

1. `docs/context/contracts-current.md`
2. `docs/context/use-cases-summary.md`
3. `AGENTS.md` raíz
4. `AGENTS.md` del módulo afectado

## Procedimiento

1. Identifica la pantalla o acción real.
2. Determina si es lectura `GET` o escritura `POST`.
3. Mapea la pantalla a un caso de uso existente.
4. Mantén controller fino.
5. Usa POST/Redirect/GET por defecto en escrituras.
6. Construye un view model simple si aporta claridad.
7. Deja Twig sin lógica de negocio.
8. Añade tests proporcionales.

## Reglas

- no metas lógica de negocio en controller
- no metas lógica de negocio en Twig
- no conviertas una pantalla web en endpoint JSON por simetría
- no inventes endpoints públicos innecesarios
- si el flujo principal es web, el contrato principal debe seguir siendo web
- usa JSON solo cuando aporte valor real y claro

## Checklist rápido

- ¿el controller solo adapta entrada y salida?
- ¿la escritura usa PRG?
- ¿el caso de uso vive en el módulo correcto?
- ¿el shape de salida es coherente con `contracts-current.md`?
- ¿la UI sigue siendo web-first y no API-first?

## Salida esperada

- archivos creados o modificados
- ruta afectada
- caso de uso invocado
- tests añadidos
- riesgos detectados
