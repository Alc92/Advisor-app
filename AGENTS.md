# AGENTS.md

## Misión

Actúa dentro de este repositorio como implementador y revisor técnico de un MVP telecom ya diseñado.

Tu trabajo es **implementar de forma controlada**, no rediseñar producto ni rehacer la arquitectura.

## Fuentes operativas

Usa en este orden:

1. `AGENTS.md` raíz
2. `AGENTS.md` del módulo afectado
3. `tests/AGENTS.md` cuando toque testing
4. `docs/context/`
5. `.cursor/skills/`
6. `.cursor/commands/`
7. prompt puntual del usuario

`docs/context/` contiene resúmenes operativos derivados de las fuentes maestras. No sustituye a las fuentes maestras del proyecto de arquitectura.

Si falta detalle o aparece contradicción con una fuente maestra externa al repo, prevalece la jerarquía de fuentes maestras definida en el proyecto de arquitectura.

Si hay conflicto entre reglas locales del repo, manda el nivel superior de esta lista.

## Qué está cerrado

No reabras estas decisiones:

- monolito modular
- estructura `module-first`
- módulos `Advisor`, `Catalog`, `Identity`, `Shared`
- Symfony 7.4 LTS + PHP 8.4
- Twig como interfaz principal
- REST JSON solo cuando aporte valor real
- PostgreSQL como persistencia final
- persistencia explícita
- sin ORM como eje
- email + password + sesión HTTP
- CQRS ligero
- catálogo curado y publicado
- testing con unitarios fuertes en commands, integración fuerte y aceptación para flujos

No propongas microservicios, SPA, GraphQL, event sourcing, colas, JWT, OAuth social, workflows complejos, backoffice rico ni capas decorativas.

## Reglas estructurales

### Organización

Este proyecto es `module-first`.

No reorganices el código como:

- `src/Domain/...`
- `src/Application/...`
- `src/Infrastructure/...`

mezclando todos los módulos.

### Ubicación del caso de uso

- si produce o continúa un assessment, vive en `Advisor`
- si opera catálogo, vive en `Catalog`
- si registra o autentica cuenta, vive en `Identity`

No cambies de módulo un caso de uso solo porque necesite datos de otro.

### Capas

#### Domain
- entidades
- value objects
- invariantes
- comportamiento de negocio

No contiene framework, HTTP, Twig, SQL ni infraestructura.

#### Application
- commands y queries
- orquestación
- transacción
- autorización de aplicación
- idempotencia
- puertos

No contiene SQL, controllers, Twig ni lógica de infraestructura.

#### Infrastructure
- repositorios concretos
- PostgreSQL
- implementación de puertos
- auth y sesión HTTP
- wiring técnico
- importación de catálogo

#### Interface
- controllers HTTP
- formularios o adaptadores de input
- endpoints JSON auxiliares
- comandos CLI expuestos

Los controladores deben ser finos.

## Regla de puertos

A nivel arquitectónico hablamos de puertos; en PHP normalmente se materializan como `interface`.

Por defecto, el puerto lo define el consumidor.

Por convención del proyecto, las interfaces de puertos y repositorios viven en `Application/Port`, incluso cuando están fuertemente ligadas a un agregado de dominio.

El dominio puede aparecer en las firmas del contrato, pero el contrato pertenece a la capa de aplicación porque lo consumen los casos de uso.

Crea puerto cuando haya:

- cruce entre módulos
- cruce de frontera técnica
- aislamiento útil para testing
- posible cambio de implementación
- traducción de vocabulario o forma de datos

No crees puertos si la abstracción sería decorativa.

## Reglas de implementación

- evita sobreingeniería
- no introduzcas patrones por estética
- no metas lógica de negocio en controllers, formularios ni Twig
- no crees buses, eventos o mappers globales sin necesidad
- no crees una interfaz por cada caso de uso salvo necesidad real
- no uses `Shared` como cajón de sastre
- no cruces módulos accediendo a infraestructura concreta del otro módulo

## Reglas funcionales que no debes romper

- el producto es un asesor, no un comparador genérico
- el sistema puede recomendar `SWITCH`, `WAIT` o `STAY`
- la entrada principal es manual guiada
- el catálogo del MVP es limitado y curado
- el usuario se modela de forma agregada en el MVP
- la recomendación debe ser explicable
- `WAIT` requiere motivo y trigger o momento de revisión
- mostrar un resultado no equivale a guardarlo
- la continuidad del MVP es manual, no automática
- confirmar un cambio no muta el histórico previo
- la recomendación histórica no debe depender del catálogo vivo

## Testing por defecto

- unit tests fuertes para commands y reglas importantes
- integración fuerte para repositorios, PostgreSQL, ownership, auth y catálogo publicado
- aceptación para flujos principales

No infles tests pobres sobre lecturas triviales.

## Si hay duda

1. No inventes.
2. Elige la opción más simple compatible con la arquitectura.
3. Haz cambios pequeños y reversibles.
4. No reabras decisiones cerradas.
5. Si falta contexto crítico, dilo explícitamente.

## Optimización para Cursor

Prefiere:

- modificar archivos existentes
- cambios pequeños
- respuestas breves
- plan antes que reescritura masiva
- el menor número de archivos nuevos compatible con claridad

Evita:

- scaffolding innecesario
- duplicación
- mover código entre módulos sin motivo fuerte
- crear 10 archivos cuando bastan 2 o 3

## Qué hacer antes de tocar código

1. Identifica qué archivo de `docs/context/` manda en esta tarea.
2. Determina el módulo correcto.
3. Determina si afecta a Domain, Application, Infrastructure o Interface.
4. Identifica invariantes que no pueden romperse.
5. Elige el tipo de test por riesgo real.

## Criterio de salida

Prioriza siempre:

- consistencia con contexto y arquitectura
- simplicidad operativa
- límites claros
- bajo acoplamiento real
- testing útil
- mínima ceremonia

## Migraciones y persistencia

No se introduce ORM como eje del modelo.

Las migraciones se añadirán cuando exista el primer corte persistente real. Podrán usarse como herramienta de versionado de esquema, pero no implican adoptar ORM ni entidades activas acopladas a base de datos.

La persistencia debe seguir siendo explícita y alineada con los puertos de aplicación.

## Regla de prompts autocontenidos

Los prompts de implementación deben ser ejecutables de forma independiente.

Si una tarea forma parte de una secuencia, no debe depender de memoria implícita de pasos anteriores. Debe declarar el contexto mínimo necesario, fuentes aplicables, reglas que no debe romper, módulos afectados, archivos permitidos/no permitidos, criterios de aceptación y testing esperado.

Si el prompt usa expresiones como “continúa con lo anterior” sin resumir el contexto necesario, primero debe pedirse aclaración o plan antes de modificar código.
