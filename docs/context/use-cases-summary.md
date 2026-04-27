# Use cases summary

## Públicos

- `EvaluateAssessment`
- `RegisterUser`
- `LoginUser`

## Autenticados

### Advisor

- `SaveAssessmentResult`
- `SaveSwitchRecommendation`
- `GetSavedAssessmentsOverview`
- `GetSavedAssessmentDetail`
- `StartReevaluationFromSavedBase`
- `ReevaluateAssessment`
- `ConfirmPerformedSwitch`
- `SaveValidatedCurrentSituationBase`

## Operativos internos

### Catalog

- `GetPublishedCatalogForEvaluation`
- `ImportCatalogOffers`
- `CreateOrUpdateCatalogPublicationDraft`
- `PublishCatalogPublication`
- `GetCatalogPublications`

## Reglas transversales importantes

- análisis público, guardado autenticado
- persistencia funcional explícita
- ownership obligatorio sobre assessments guardados
- idempotencia funcional al menos en guardados explícitos
- transacción solo sobre lo estrictamente necesario
- la reevaluación genera un nuevo assessment
- confirmar un cambio no muta el histórico previo
