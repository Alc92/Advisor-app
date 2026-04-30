# Decisions short

Resumen operativo de decisiones cerradas para implementación.

Este archivo no sustituye a `01 - Registro de decisiones.md`.

## Producto y alcance

- Producto = advisor, no comparador genérico.
- Outputs principales = `SWITCH`, `WAIT`, `STAY`.
- Vertical MVP = telecom residencial para particulares.
- Alcance MVP = móvil, fibra y fibra + móvil.
- Entrada principal = manual guiada; factura solo como apoyo opcional.
- Catálogo = limitado y curado; no cubre todo el mercado.
- Neutralidad = dentro del catálogo disponible, no del mercado completo.
- Interfaz principal = web mobile-first con Twig, no SPA.

## Recomendación

- El sistema puede recomendar no cambiar.
- El sistema opera con incertidumbre.
- No bloquea por falta de datos salvo mínimo de evaluación.
- Si falta el mínimo de evaluación, no se evalúan alternativas.
- `WAIT` exige motivo y trigger o momento de revisión.
- Mejoras insuficientes se resuelven como `STAY`, no como `WAIT`.
- Preferencias del usuario modulan, no sustituyen, la lógica de decisión.
- No se recomiendan alternativas con fit `LOW`.
- La fricción `HIGH` bloquea `SWITCH` y conduce a `WAIT`.

## Modelo

- El usuario se modela de forma agregada en el MVP.
- No se modelan líneas individuales del usuario en el onboarding base.
- Las ofertas asimétricas sí pueden conservar detalle real por línea.
- El motor opera sobre datos normalizados.
- La comunicación usa datos comerciales reales.
- La recomendación histórica no debe depender del catálogo vivo.
- `CatalogPublication` publicada define el conjunto evaluable.
- `TelecomOfferVersion` existe, pero no es evaluable por sí sola si no pertenece a publicación publicada.

## Continuidad

- Mostrar resultado no equivale a guardarlo.
- Guardar resultado y guardar recomendación de cambio son acciones explícitas.
- La continuidad del MVP es manual, no automática.
- No hay seguimiento automático ni notificaciones proactivas en el MVP.
- Confirmar un cambio no muta el histórico previo; genera nueva base validada.
- Reevaluar genera un nuevo assessment.

## Arquitectura e implementación

- Monolito modular Symfony.
- Estructura `module-first`.
- Puertos y repositorios viven por defecto en `Application/Port`.
- El consumidor define el puerto mínimo que necesita.
- Twig es interfaz principal; JSON solo si aporta valor real.
- Persistencia explícita sobre PostgreSQL.
- Sin ORM como eje del sistema.
- `Shared` solo transversal real, nunca cajón de sastre.
