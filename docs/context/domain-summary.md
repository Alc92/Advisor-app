# Domain summary

## Contextos principales

- `Advisor`: núcleo del asesor
- `Catalog`: catálogo curado evaluable
- `Identity`: identidad y cuenta mínimas
- `Shared`: solo transversal real

## Núcleo de dominio del asesor

El centro del sistema es `Assessment`.

Debe distinguir claramente:

- snapshot de entrada
- resultado
- traza estructurada

## Reglas clave del asesor

- mostrar resultado no equivale a guardarlo
- guardar resultado y guardar recomendación no son lo mismo
- reevaluar genera un nuevo assessment
- confirmar un cambio no muta histórico previo
- la recomendación histórica debe incluir snapshot mínimo de la oferta sugerida
- `WAIT` requiere semántica interna estructurada

## Catálogo

- `TelecomOffer` = identidad estable
- `TelecomOfferVersion` = unidad evaluable real
- `CatalogPublication` = conjunto exacto evaluable

La publicación manda sobre la mera existencia de la versión.

## Identidad

- `UserAccount` = identidad autenticable
- `AccountProfile` = perfil mínimo y consentimientos

Identity no absorbe datos del asesor.
