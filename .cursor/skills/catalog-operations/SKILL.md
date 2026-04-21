# SKILL: catalog-operations

## Objetivo

Trabajar sobre catálogo sin romper su modelo operativo ni contaminarlo con lógica del asesor.

## Aplicable a

- importación
- creación de borradores
- publicación
- resolución de catálogo vigente
- consultas operativas internas

## Reglas clave

- unidad evaluable = `TelecomOfferVersion`
- publicación manda sobre mera vigencia aislada
- `TelecomOffer` no es la unidad evaluable real
- no meter recommendation logic aquí
- no crear workflow editorial rico

## Procedimiento

1. Determina si el cambio afecta a:
   - oferta estable
   - versión evaluable
   - publicación
   - publicación vigente
2. Verifica si el cambio es de importación, publicación o consulta.
3. Mantén separación entre datos comerciales y normalizados.
4. Diseña tests de integración si afecta persistencia o selección.

## Casos sensibles

- publicación vacía
- versión duplicada en una misma publicación
- versión existente no publicada
- resolución incorrecta de publicación vigente
- mezcla accidental de lógica de ranking o recomendación

## Salida esperada

- piezas afectadas
- riesgos operativos detectados
- pruebas necesarias
