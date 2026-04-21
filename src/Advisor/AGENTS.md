# AGENTS.md

## Misión del módulo

`Advisor` implementa el núcleo del asesor.

Aquí viven los casos de uso y piezas del dominio que producen o continúan un assessment:

- evaluación
- resultado efímero
- guardado funcional
- guardado de recomendación de cambio
- recuperación de guardados
- reevaluación
- confirmación manual de cambio
- nueva situación base validada

## Fuentes locales de referencia

- `docs/context/domain-summary.md`
- `docs/context/use-cases-summary.md`
- `docs/context/contracts-current.md`
- `AGENTS.md` raíz

## Qué está cerrado en este módulo

- `Assessment` es el centro del dominio del asesor
- el usuario se modela de forma agregada
- la evaluación distingue input, resultado y traza
- mostrar un resultado no equivale a guardarlo
- `SaveAssessmentResult` y `SaveSwitchRecommendation` son distintos
- una reevaluación genera un nuevo assessment
- confirmar un cambio no muta el assessment previo
- la nueva situación base validada es persistencia separada
- la recomendación histórica debe conservar snapshot mínimo de oferta
- `WAIT` tiene semántica interna reforzada

## Casos de uso que viven aquí

### Públicos
- `EvaluateAssessment`

### Autenticados
- `SaveAssessmentResult`
- `SaveSwitchRecommendation`
- `GetSavedAssessmentsOverview`
- `GetSavedAssessmentDetail`
- `StartReevaluationFromSavedBase`
- `ReevaluateAssessment`
- `ConfirmPerformedSwitch`
- `SaveValidatedCurrentSituationBase`

## Reglas de implementación

### 1. El caso de uso orquesta
La capa `Application` coordina:

- validación básica de entrada
- carga de contexto
- resolución de puertos
- transacción
- autorización de aplicación
- idempotencia
- ensamblado de salida

No repliques aquí lógica que ya pertenece al dominio.

### 2. Persistencia funcional explícita
Un assessment solo es guardado funcionalmente cuando se ejecuta una acción explícita de guardado coherente con producto y UX.

### 3. Distinción obligatoria entre guardar resultado y guardar recomendación
No colapses:

- `SaveAssessmentResult`
- `SaveSwitchRecommendation`

### 4. Reevaluación = nuevo assessment
Nunca modeles la reevaluación como edición del assessment anterior.

### 5. Confirmación manual de cambio sin mutar histórico
`ConfirmPerformedSwitch` y `SaveValidatedCurrentSituationBase` no modifican el histórico previo.

### 6. La recomendación histórica no depende del catálogo vivo
Si existe `SWITCH`, conserva snapshot mínimo e inmutable de la oferta recomendada.

### 7. `WAIT` debe quedar estructurado
Preserva, cuando aplique:

- `waitKind`
- `reasonCode`
- `reviewTrigger`
- `recommendedReviewMoment`
- limitaciones estructuradas
- degradaciones relevantes

### 8. Trazabilidad estructurada
No sustituyas la traza por un simple texto.

## Fronteras con otros módulos

### Con `Catalog`
`Advisor` consume catálogo publicado mediante puertos mínimos definidos desde `Advisor`.

No dependas de clases internas de infraestructura de `Catalog`.

### Con `Identity`
`Advisor` no autentica.

Consume identidad autenticada o ownership mediante puertos mínimos.

## Estructura esperada

```text
src/Advisor/
  Domain/
  Application/
    Command/
    Query/
    Port/
  Infrastructure/
  Interface/
    Http/
    Cli/
```

## Testing esperado

### Unit
- commands
- reglas de aplicación
- idempotencia funcional básica
- autorización local
- comportamiento de dominio relevante

### Integración
- persistencia de assessments guardados
- ownership
- guardado idempotente
- histórico de recomendación
- continuidad tras confirmación de cambio

### Aceptación
- análisis efímero
- guardar resultado
- guardar recomendación
- reevaluación
- confirmación de cambio

## Antipatrones a evitar

- controller con lógica de decisión
- service god
- query que muta estado
- flags ambiguos para distinguir guardado funcional
- edición del assessment anterior para representar reevaluación
- resolver histórico desde catálogo vivo
