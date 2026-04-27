# AGENTS.md

## Misión del módulo

`Shared` contiene solo elementos transversales reales usados por varios módulos.

Ejemplos aceptables:

- `Clock`
- `IdGenerator`
- tipos base verdaderamente compartidos
- errores técnicos transversales
- utilidades mínimas sin semántica de negocio específica

## Regla principal

`Shared` no es un lugar para evitar decidir a qué módulo pertenece algo.

Antes de mover algo a `Shared`, verifica que:

1. lo usan al menos dos módulos reales
2. no pertenece conceptualmente a `Advisor`, `Catalog` o `Identity`
3. no introduce vocabulario funcional de un módulo concreto
4. no aumenta el acoplamiento entre contextos

## Qué no debe contener

- lógica del asesor
- lógica de catálogo
- lógica de identidad
- DTOs específicos de casos de uso
- repositorios de módulos
- servicios de dominio de un contexto concreto
- helpers genéricos prematuros
- mappers globales

## Estructura esperada

```text
src/Shared/
  Domain/
  Application/
  Infrastructure/
```

## Antipatrones a evitar

- `Shared` como cajón de sastre
- clases `Utils`
- helpers globales sin dueño claro
- tipos compartidos solo por comodidad
- mover código aquí porque “molesta” en un módulo
