# Product summary

Resumen operativo derivado de `00 - Canon del producto`, `01 - Registro de decisiones` y `02 - Especificación funcional MVP telecom`.

Este archivo no sustituye a las fuentes maestras.

## Qué es el producto

Un asesor inteligente de gastos recurrentes centrado en telecom en el MVP.

No es un comparador genérico. Debe analizar la situación actual del usuario y recomendar si conviene:

- cambiar (`SWITCH`)
- esperar (`WAIT`)
- no cambiar (`STAY`)

La confianza depende de que pueda recomendar también no cambiar.

## Alcance del MVP

- telecom residencial para particulares
- móvil de contrato
- fibra
- fibra + móvil
- una única situación contractual por recomendación
- catálogo limitado y curado de alternativas

## Fuera del MVP

- cobertura completa del mercado
- multi-residencia optimizada
- múltiples contratos evaluados conjuntamente
- seguimiento automático
- notificaciones proactivas
- lifecycle complejo
- banca, electricidad, seguros u otras verticales
- referidos, matching social o incentivos entre usuarios

## Inputs principales

- proveedor actual
- tipo de producto
- precio mensual aproximado
- número de líneas móviles cuando aplique
- uso móvil o uso de fibra en categorías simples
- permanencia y fecha aproximada si se conoce
- promoción y fecha aproximada si se conoce
- preferencia: ahorro, equilibrio o mantener condiciones
- detección opcional de múltiples residencias

La vía principal de entrada es manual guiada. La factura solo puede ser apoyo opcional.

## Mínimo para evaluar alternativas

Para evaluar catálogo se necesita:

- tipo de producto
- precio aproximado
- nivel de uso

Si falta ese mínimo:

- no se evalúan alternativas
- la salida será `WAIT`
- el motivo será falta de información suficiente

## Outputs mínimos

Todo resultado debe incluir:

- decisión principal
- motivo principal
- ahorro, coste evitado o mejora relevante cuando aplique
- alternativa sugerida si aplica
- explicación clara
- riesgos o trade-offs
- incertidumbre si existe
- limitaciones del análisis si existen
- trigger o momento de revisión si la decisión es `WAIT`
- vía práctica de acción cuando exista alternativa recomendada y canal fiable

## Reglas funcionales clave

- El ahorro no es el único criterio.
- Importan permanencia, fricción, pérdida de condiciones, incertidumbre y preferencia del usuario.
- La mejora insuficiente se resuelve como `STAY`, no como `WAIT`.
- `WAIT` debe ser accionable: motivo + momento/condición de revisión.
- La recomendación no debe parecer objetiva si depende de datos incompletos.
- La neutralidad se garantiza dentro del catálogo evaluado, no respecto a todo el mercado.
- El motor opera con datos normalizados.
- La comunicación usa datos comerciales reales.

## Continuidad

- Mostrar un resultado no implica guardarlo.
- Guardar es una acción explícita del usuario.
- Los tres resultados pueden guardarse.
- `SWITCH` puede activar continuidad manual rica: guardar recomendación, volver más tarde, confirmar si el cambio se realizó y validar nueva situación.
- No hay seguimiento automático en el MVP.
