# SKILL: debug-production-minded

## Objetivo

Diagnosticar bugs pensando en sistemas reales, no solo en código local.

## Procedimiento

1. Define el síntoma observable.
2. Determina si es reproducible.
3. Identifica inputs reales involucrados.
4. Revisa estado persistido, auth, sesión, idempotencia y transacción.
5. Formula hipótesis simples primero.
6. Prioriza fixes mínimos y verificables.

## Hipótesis típicas a revisar

- input inválido no filtrado en borde
- ownership incorrecto
- estado persistido inconsistente
- dependencia cruzada entre módulos
- transacción insuficiente
- idempotencia ausente
- bug de sesión o autenticación
- mapeo incorrecto entre capas

## Reglas

- No asumir que “en local funciona” invalida el bug.
- No empezar por reescribir media arquitectura.
- No ocultar incertidumbre: separa hechos, hipótesis y pruebas necesarias.

## Salida esperada

- hipótesis ordenadas por probabilidad
- pasos de verificación
- fix mínimo sugerido
- test o validación para evitar regresión
