# Architecture summary

## Stack cerrado

- PHP 8.4
- Symfony 7.4 LTS
- Twig como interfaz principal
- PostgreSQL como persistencia final
- PHPUnit para testing
- Symfony Console para CLI

## Arquitectura cerrada

- monolito modular
- estructura `module-first`
- hexagonal pragmática
- CQRS ligero
- persistencia explícita
- sin ORM como eje

## Estructura esperada

```text
src/
  Advisor/
  Catalog/
  Identity/
  Shared/

templates/
  advisor/
  catalog/
  identity/

tests/
  Unit/
  Integration/
  Acceptance/
```

## Reglas operativas importantes

- el módulo dueño del objetivo funcional decide dónde vive el caso de uso
- los puertos se definen por defecto desde el consumidor
- controllers finos
- Twig sin lógica de negocio
- JSON solo cuando aporte valor real
