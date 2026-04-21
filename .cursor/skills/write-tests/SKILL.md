# SKILL: write-tests

## Objetivo

Diseñar y escribir tests útiles, no decorativos.

## Procedimiento

1. Identifica el riesgo real.
2. Decide nivel de prueba:
   - Unit
   - Integration
   - Acceptance
3. Diseña el menor conjunto de escenarios que proteja el comportamiento importante.
4. Evita duplicar la implementación en el test.

## Regla por nivel

### Unit
Usar para:

- commands
- reglas de aplicación
- dominio con invariantes reales
- validaciones importantes
- idempotencia funcional básica

### Integration
Usar para:

- PostgreSQL real
- repositorios
- ownership
- auth
- publicación vigente de catálogo
- snapshots históricos

### Acceptance
Usar para:

- flujos completos del MVP
- interacción entre módulos
- navegación principal y acciones visibles del usuario

## Antipatrones

- testear getters o setters
- usar mocks en cascada sin necesidad
- escribir unit tests pobres sobre queries triviales
- sustituir integración crítica por simulación débil

## Checklist final

- ¿qué riesgo protege este test?
- ¿el nivel elegido es el correcto?
- ¿el test cubre comportamiento observable?
- ¿hay ya otra prueba cubriendo lo mismo?

## Salida esperada

- tipo de test elegido
- escenarios cubiertos
- huecos detectados
