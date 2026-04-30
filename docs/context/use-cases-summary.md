# Use cases summary

Resumen operativo derivado de `07 - Casos de uso de aplicación MVP telecom`.

Este archivo no sustituye a la fuente maestra de casos de uso.

## Principios de aplicación

- La capa de aplicación orquesta; no reimplementa reglas del dominio.
- Análisis público, guardado autenticado.
- Persistencia funcional explícita.
- Ownership obligatorio sobre assessments guardados.
- Reevaluar genera un nuevo assessment.
- Confirmar un cambio no muta el histórico previo.
- Transacción solo sobre lo estrictamente necesario para preservar consistencia local.
- Idempotencia funcional en guardados y continuidad donde pueda haber reintentos.

## Públicos

### `EvaluateAssessment`

Objetivo: ejecutar un análisis desde onboarding y devolver resultado no guardado funcionalmente por defecto.

Reglas:

- no requiere autenticación
- construye `AssessmentInputSnapshot`
- decide si se cumple mínimo de evaluación
- si aplica, resuelve publicación vigente de catálogo
- devuelve resultado para frontend
- no guarda funcionalmente salvo acción posterior explícita

### `RegisterUser`

Objetivo: crear cuenta mínima para guardar y recuperar.

Reglas:

- email único
- credencial segura
- consentimientos mínimos
- no mezclar datos del asesor en registro

### `LoginUser`

Objetivo: autenticar y abrir acceso a guardado/continuidad.

Reglas:

- no usar JWT en esta fase
- sesión HTTP basada en cookie Symfony
- actualizar `lastLoginAt` solo si se decide persistirlo

## Autenticados - Advisor

### `SaveAssessmentResult`

Guarda explícitamente un resultado recuperable para `WAIT` o `STAY`, y también puede cubrir guardado genérico cuando aplique.

Reglas:

- requiere usuario autenticado
- vincula assessment a `userId`
- idempotente funcionalmente
- no duplica guardados por reintentos

### `SaveSwitchRecommendation`

Guarda recomendación de cambio y continuidad manual rica.

Reglas:

- requiere `decision = SWITCH`
- requiere snapshot de oferta sugerida
- idempotente funcionalmente
- no depende del catálogo vivo para mostrar histórico

### `GetSavedAssessmentsOverview`

Devuelve entrada de continuidad y lista resumida.

Reglas:

- priorizar continuidad relevante, no historial plano sin criterio
- solo assessments del usuario autenticado

### `GetSavedAssessmentDetail`

Recupera detalle de un assessment guardado.

Reglas:

- ownership obligatorio
- oferta histórica desde snapshot persistido

### `StartReevaluationFromSavedBase`

Prepara base precargada y editable para una nueva evaluación.

Reglas:

- no evalúa por sí mismo
- separa preparación de ejecución

### `ReevaluateAssessment`

Ejecuta una nueva evaluación desde base previa editada.

Reglas:

- genera nuevo assessment
- no muta el assessment origen

### `ConfirmPerformedSwitch`

Registra confirmación manual de que el usuario realizó un cambio recomendado.

Reglas:

- no muta recomendación ni assessment previo
- prepara o habilita validación de nueva situación

### `SaveValidatedCurrentSituationBase`

Guarda nueva situación base validada para futuras reevaluaciones.

Reglas:

- idempotente funcionalmente
- vinculada a usuario y assessment origen
- no sustituye histórico anterior

## Operativos internos - Catalog

### `GetPublishedCatalogForEvaluation`

Resuelve publicación vigente para evaluación.

### `ImportCatalogOffers`

Importa o actualiza material de catálogo curado.

### `CreateOrUpdateCatalogPublicationDraft`

Prepara una publicación no publicada como borrador operativo del conjunto futuro.

### `PublishCatalogPublication`

Publica el conjunto exacto evaluable.

Regla clave: la publicación publicada, no la oferta aislada, define lo evaluable.

### `GetCatalogPublications`

Consulta publicaciones con fines operativos.

## Puertos esperables

Advisor:

- `AssessmentRepository`
- `SavedAssessmentQueryRepository`
- `ValidatedSituationBaseRepository`
- `PublishedCatalogQueryPort`

Identity:

- `UserAccountRepository`
- `CredentialVerifierPort`
- `PasswordHasherPort`
- `AuthenticatedUserProvider`

Catalog:

- `CatalogPublicationRepository`
- `TelecomOfferRepository`
- `TelecomOfferVersionRepository`
- `CatalogImportPort`
- `OfferNormalizationPort`

Shared o transversal:

- `UnitOfWork`
- `Clock`
- `IdGenerator`
