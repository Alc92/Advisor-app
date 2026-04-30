# Architecture summary

Resumen operativo derivado de `arquitectura_base_e_implementacion_mvp_telecom.md`.

Este archivo no sustituye a las fuentes maestras. Sirve para orientar implementación en `advisor-app` con bajo coste de contexto.

## Stack cerrado

- PHP 8.4
- Symfony 7.4 LTS
- Twig como interfaz principal
- PostgreSQL como persistencia final
- PHPUnit para testing
- Symfony Console para CLI operativa
- Docker para desarrollo local
- GitHub Actions para CI

## Arquitectura cerrada

- monolito modular
- estructura `module-first`
- hexagonal pragmática
- CQRS ligero en capa de aplicación
- persistencia explícita
- sin ORM como eje del modelo
- REST JSON solo cuando aporte valor real

## Módulos

- `Advisor`: assessment, evaluación, guardado funcional, recomendación, reevaluación y continuidad manual.
- `Catalog`: catálogo curado, ofertas, versiones y publicaciones evaluables.
- `Identity`: cuenta, autenticación, sesión y consentimientos mínimos.
- `Shared`: solo elementos transversales reales.

## Estructura esperada

```text
src/
  Advisor/
    Domain/
    Application/
      Command/
      Query/
      Port/
    Infrastructure/
    Interface/
      Http/
      Cli/

  Catalog/
    Domain/
    Application/
      Command/
      Query/
      Port/
    Infrastructure/
    Interface/
      Http/
      Cli/

  Identity/
    Domain/
    Application/
      Command/
      Query/
      Port/
    Infrastructure/
    Interface/
      Http/

  Shared/
    Domain/
    Application/
    Infrastructure/

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

- El módulo dueño del objetivo funcional decide dónde vive el caso de uso.
- Si el caso produce o continúa un assessment, vive en `Advisor`.
- Si opera catálogo, vive en `Catalog`.
- Si registra, autentica o resuelve cuenta, vive en `Identity`.
- Los puertos se definen por defecto desde el consumidor.
- Las interfaces de puertos y repositorios viven por defecto en `Application/Port`.
- Controllers finos: adaptan request, llaman a aplicación y renderizan o redirigen.
- Twig no contiene lógica de negocio.
- JSON no se introduce por simetría; solo como apoyo real.
- No crear buses, eventos, factories, mappers o servicios si no resuelven un problema real.

## Persistencia

- PostgreSQL será la persistencia final.
- La persistencia debe ser explícita: SQL/acceso a datos controlado y mapeo claro.
- No usar ORM como eje del modelo ni entidades activas acopladas a base de datos.
- Las migraciones se añadirán cuando exista el primer corte persistente real.
- La infraestructura temporal de testing no redefine la arquitectura final.

## Testing

- Unit: dominio, value objects, reglas y casos de uso con dobles simples.
- Integration: repositorios, transacciones, wiring Symfony y persistencia PostgreSQL.
- Acceptance: flujos funcionales relevantes de extremo a extremo controlado.
- No crear tests decorativos ni asserts triviales salvo smoke técnico inicial.
