# SKILL: implement-use-case

## Objetivo

Implementar un caso de uso nuevo o modificar uno existente sin romper la arquitectura cerrada del proyecto.

## Cuándo usar

- nuevo command o query
- nuevo flujo del asesor
- nuevo caso de uso de catálogo
- cambio funcional controlado

## Fuentes obligatorias

1. `docs/context/`
2. `AGENTS.md` raíz
3. `AGENTS.md` del módulo afectado
4. `tests/AGENTS.md` si la tarea implica tests

## Procedimiento

1. Identifica el objetivo funcional real.
2. Determina el módulo correcto.
3. Determina si es command o query.
4. Identifica inputs y outputs mínimos.
5. Detecta puertos necesarios.
6. Implementa solo lo necesario.
7. Añade tests proporcionales al riesgo.
8. Verifica naming y ubicación.

## Reglas

- No crear servicios artificiales.
- No meter lógica en controller.
- No inventar endpoints no pedidos.
- No crear DTOs sin valor real.
- No mover módulos por comodidad.
- No usar `Shared` para evitar decidir ownership.

## Checklist final

- ¿el caso de uso vive en el módulo correcto?
- ¿la capa `Application` orquesta sin absorber infraestructura?
- ¿los puertos están definidos desde el consumidor?
- ¿el cambio respeta persistencia funcional explícita?
- ¿el test elegido es el correcto?

## Salida esperada

- archivos creados o modificados
- explicación breve de decisiones estructurales
- tests añadidos
- riesgos residuales detectados
