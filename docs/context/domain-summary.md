# Domain summary

Resumen operativo derivado de `05 - Modelo de dominio definitivo MVP telecom`, `06 - Contexto de identidad y cuenta` y `09 - Modelo de datos persistente inicial`.

Este archivo no sustituye a las fuentes maestras. Sirve para orientar implementación sin cargar el corpus completo.

## Contextos principales

- `Advisor`: núcleo del asesor.
- `Catalog`: catálogo curado evaluable.
- `Identity`: identidad y cuenta mínimas.
- `Shared`: solo transversal real.

## Núcleo de dominio del asesor

El centro del sistema es `Assessment`.

Debe distinguir claramente:

- `AssessmentInputSnapshot`
- `AssessmentResult`
- `Recommendation`
- `EvaluationTrace`

El producto asesora sobre una situación concreta, no sobre una oferta aislada.

## Assessment

Reglas clave:

- Puede ser efímero mientras no exista guardado funcional.
- Si está guardado y recuperable, debe tener `userId`.
- Si existe resultado, debe existir `evaluationMode`, `ruleSetVersion` y `evaluatedAt`.
- Si `evaluationMode = NOT_EVALUATED_MINIMUM_NOT_MET`, no debe existir evaluación real de alternativas.
- `catalogVersion` o referencia equivalente identifica la `CatalogPublication` usada.

## Input

`CurrentSituation` representa la situación actual declarada del usuario.

Reglas:

- No depende de que la oferta actual exista en catálogo.
- Se modela de forma agregada en el MVP.
- `MOBILE` no usa necesidades de fibra.
- `FIBER` no usa líneas ni uso móvil.
- `FIBER_MOBILE` puede usar ambos grupos.
- La calidad del input debe reflejar completitud e incertidumbre.

`AdditionalConditionProfile` es opcional y solo afina escenarios donde el usuario prioriza mantener condiciones.

## Evaluación

Modos esperados:

- `NOT_EVALUATED_MINIMUM_NOT_MET`
- `EVALUATED_NORMAL`
- `EVALUATED_DEGRADED`

Reglas:

- Si no hay mínimo funcional, no se evalúa catálogo.
- Si hay mínimo, puede evaluarse normal o degradado.
- La incertidumbre puede degradar `SWITCH` a `WAIT`.

## Recommendation

Decisiones:

- `SWITCH`
- `WAIT`
- `STAY`

Invariantes:

- Si `decision = SWITCH`, deben existir `suggestedOfferVersionId` y `suggestedOfferSnapshot`.
- Si `decision = WAIT`, debe existir `waitKind`.
- Si `decision = WAIT`, debe existir `reviewTrigger`, salvo decisión explícita futura.
- Las limitaciones del análisis forman parte del resultado, no solo de la traza.
- Los textos visibles deben derivarse de datos comerciales y explicación, no de enums internos.

## Trazabilidad

La traza debe permitir reconstruir:

- si se evaluó catálogo
- qué alternativas pasaron el filtro mínimo
- qué alternativas fueron descartadas por filtros duros
- qué alternativas quedaron fuera por ranking
- qué reglas participaron
- si hubo degradación de decisión
- qué limitaciones estructuradas aplicaron

No depender solo de texto libre para reconstruir la decisión.

## Catálogo

- `TelecomOffer` = identidad estable de oferta.
- `TelecomOfferVersion` = versión evaluable.
- `CatalogPublication` = conjunto exacto evaluable.
- `CatalogPublicationItem` = pertenencia publicación-versión.

Reglas:

- La publicación manda sobre la mera existencia de una versión.
- Solo una publicación publicada/vigente debe alimentar nuevas evaluaciones.
- La recomendación histórica conserva snapshot mínimo de la oferta sugerida.
- El catálogo no contiene lógica de recomendación.

## Identidad

- `UserAccount` = identidad autenticable.
- `AccountProfile` = perfil mínimo y consentimientos.

Identity no absorbe:

- situación actual telecom
- preferencias de assessment
- outputs del motor
- trazabilidad
- lifecycle comercial

## Persistencia funcional

- Mostrar resultado no equivale a guardarlo.
- `advisor_saved_assessment` o pieza equivalente expresa guardado funcional explícito.
- Confirmar un cambio crea una nueva situación base validada.
- No se muta el assessment histórico previo.
