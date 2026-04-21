# AGENTS.md

## Misión

El directorio `tests/` debe verificar comportamiento relevante del sistema sin caer en tests decorativos ni duplicación inútil.

## Estructura esperada

```text
tests/
  Unit/
  Integration/
  Acceptance/
```

## Regla general

Cada test debe existir por una razón clara:

- proteger una invariante
- proteger una frontera arquitectónica
- proteger transacción o idempotencia relevante
- proteger una integración real
- proteger un flujo crítico del MVP

## Qué va en `Unit`

- casos de uso de command
- reglas de aplicación relevantes
- validaciones de aplicación
- idempotencia funcional básica
- comportamiento de dominio con invariantes reales

## Qué va en `Integration`

- repositorios concretos
- PostgreSQL real
- ownership
- auth
- publicación vigente de catálogo
- persistencia histórica de recommendation snapshot
- persistencia de nueva situación base validada

## Qué va en `Acceptance`

- análisis efímero
- guardar resultado
- guardar recomendación
- detalle y overview de guardados
- reevaluación
- confirmación de cambio
- publicación de catálogo

## Casos especialmente sensibles

- intento de guardar sin autenticación
- acceso a guardados de otro usuario
- repetición de guardado por reintento técnico
- guardar recomendación sobre assessment no `SWITCH`
- histórico independiente del catálogo vivo
- publicación vacía o con versiones duplicadas

## Antipatrones a evitar

- testear getters/setters sin valor
- duplicar implementación en el test
- queries triviales con exceso de mocks
- sustituir integración crítica por unit tests falsamente rápidos

## Criterio de revisión

Antes de añadir un test, pregúntate:

1. ¿qué riesgo real protege?
2. ¿este riesgo es unit, integration o acceptance?
3. ¿estoy probando comportamiento o implementación?
4. ¿esta prueba ayuda a mantener la arquitectura cerrada?
