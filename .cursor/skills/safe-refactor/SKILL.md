# SKILL: safe-refactor

## Objetivo

Refactorizar sin alterar comportamiento funcional ni romper la arquitectura cerrada.

## Procedimiento

1. Define qué comportamiento actual debe preservarse.
2. Identifica cobertura existente.
3. Añade tests si faltan para blindar el cambio.
4. Refactoriza en pasos pequeños.
5. Revalida módulos, capas y dependencias.

## Permitido

- renombrar para dar claridad
- extraer clases útiles
- reducir duplicación
- mejorar legibilidad
- simplificar flujo de control

## No permitido

- cambiar comportamiento silenciosamente
- mover módulos sin motivo funcional real
- introducir patrones por estética
- crear interfaces decorativas

## Checklist final

- ¿el comportamiento observable sigue igual?
- ¿la cobertura protege el refactor?
- ¿se ha reducido complejidad real?
- ¿se ha mantenido el ownership correcto?

## Salida esperada

- cambios estructurales realizados
- garantías usadas para no romper comportamiento
- riesgos residuales
