# Frontend summary

Resumen operativo derivado de `10 - Blueprint UX implementable frontend MVP telecom` y `11 - Guía visual mínima frontend MVP telecom`.

Este archivo no sustituye a las fuentes maestras frontend. Sirve para orientar implementación Twig/mobile-first con bajo coste de contexto.

## Principios frontend

- El producto debe percibirse como asesor, no como comparador.
- Twig server-rendered es la interfaz principal.
- Mobile-first real: pantallas cortas, jerarquía clara y formularios ligeros.
- JavaScript mínimo y progresivo.
- El usuario no debe ver enums, DTOs, códigos internos ni categorías del motor.
- La explicación forma parte central del valor.
- Los datos comerciales reales explican ofertas.
- La incertidumbre se comunica en lenguaje natural.
- El guardado es explícito.
- La continuidad es manual, no automática.

## Inventario de pantallas

Flujo público:

1. Landing
2. Bienvenida al análisis
3. Situación actual básica
4. Uso y condiciones del servicio
5. Permanencia y promoción
6. Prioridad del usuario
7. Ajuste adicional para mantener condiciones
8. Resumen previo editable
9. Resultado principal
10. Explicación ampliada

Identidad:

11. Registro contextual
12. Inicio de sesión contextual

Continuidad autenticada:

13. Entrada de continuidad
14. Detalle de guardado
15. Entrada rápida tras recomendación de cambio guardada
16. Confirmación de cambio realizado
17. Validación de nueva situación
18. Preparar reevaluación desde base previa
19. Resultado de reevaluación

## Plantilla mínima por pantalla

Antes de implementar una pantalla o componente principal, definir:

- objetivo UX
- decisión del usuario
- contenido principal
- jerarquía mobile-first
- CTA principal
- estados relevantes
- riesgo UX
- criterio de aceptación UX

## Jerarquía mobile-first

Orden recomendado:

1. título o pregunta principal
2. explicación breve
3. input o contenido principal
4. ayuda contextual
5. CTA principal
6. acción secundaria si aplica

Evitar:

- formularios densos
- tablas de ofertas
- rankings
- varias decisiones principales por pantalla
- bloques largos antes del CTA

## Reglas de copy

Permitido:

- lenguaje sencillo
- decisiones claras
- explicaciones breves
- mensajes honestos sobre límites
- copy orientado a acción

No permitido:

- enums internos
- códigos del motor
- nombres de DTOs
- categorías normalizadas visibles
- mensajes legalistas para incertidumbre
- copy comercial agresivo
- `No hemos encontrado nada` para `STAY`
- `No podemos recomendarte` cuando corresponde `WAIT` o `STAY`

## Resultados

### `SWITCH`

Debe sentirse como oportunidad razonada.

Mostrar:

- recomendación de cambiar
- oferta sugerida
- precio
- datos comerciales reales
- ahorro o mejora estimada
- explicación de encaje
- trade-offs
- riesgos
- vía práctica de acción si existe
- CTA `Guardar esta recomendación`

No mostrar un segundo CTA paralelo `Guardar resultado`.

### `WAIT`

Debe sentirse como pausa útil y accionable.

Mostrar:

- motivo de espera
- momento o condición de revisión
- qué comprobar después
- incertidumbre si aplica
- CTA `Guardar resultado`

No debe parecer error, bloqueo ni falta de capacidad del sistema.

### `STAY`

Debe sentirse como decisión valiosa.

Mostrar:

- recomendación de no cambiar
- motivo
- explicación de por qué no compensa
- posible revisión futura
- CTA `Guardar resultado`

No usar rojo, iconos de fallo ni copy de “sin resultados”.

## Semántica visual

- Claridad antes que expresividad.
- Confianza antes que agresividad comercial.
- Los estados de resultado tienen tono propio, pero no son éxito/error.
- El color de error se reserva para errores reales de formulario.
- `WAIT` no debe usar apariencia de alerta fuerte.
- `STAY` no debe usar rojo ni parecer fallo.
- La card de oferta recomendada apoya la decisión; no es el centro autónomo de la pantalla.

## Accesibilidad mínima

- contraste suficiente
- tamaños táctiles cómodos
- foco visible
- errores asociados al campo
- no depender solo del color
- iconos siempre acompañados de texto claro

## Autenticación contextual

- No pedir cuenta antes de entregar la recomendación principal.
- Registro/login aparece principalmente cuando el usuario intenta guardar.
- Tras autenticarse, preservar la intención pendiente y volver al resultado o acción relevante.
