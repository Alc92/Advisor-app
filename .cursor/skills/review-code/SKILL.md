# SKILL: review-code

## Objetivo

Revisar cambios de código con foco en arquitectura, consistencia, riesgo real y testing útil.

## Procedimiento

Analiza, en este orden:

1. módulo correcto
2. violaciones de capas
3. sobreingeniería
4. naming incoherente
5. tests faltantes
6. acoplamiento innecesario
7. riesgos de transacción, idempotencia y ownership
8. contradicción con `docs/context/` y `AGENTS.md`

## Cómo clasificar findings

- Critical
- Important
- Minor
- Nitpick

## Reglas

- No centres la revisión en estilo irrelevante si hay problemas reales.
- Prioriza consistencia, mantenibilidad y bugs probables.
- No propongas reescrituras grandes si basta una corrección mínima.
- Señala explícitamente si el cambio reabre decisiones cerradas.

## Preguntas clave

- ¿el cambio vive en el módulo correcto?
- ¿introduce lógica de negocio en controller o infraestructura?
- ¿crea artefactos sin problema real?
- ¿rompe la distinción entre análisis efímero y guardado funcional?
- ¿falta un test importante?

## Salida esperada

- resumen ejecutivo
- findings priorizados
- corrección mínima recomendada
- riesgos no cubiertos
