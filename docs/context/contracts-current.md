# Contracts current

Resumen operativo derivado de `08 - API contracts frontend/backend MVP`.

Este archivo no sustituye al contrato maestro. Sirve para recordar la superficie web/Twig y evitar que la implementación derive a API-first.

## Principios de contratos

- Web-first, no API-first.
- Twig server-rendered es la interfaz principal.
- POST/Redirect/GET para acciones de usuario.
- JSON solo como apoyo puntual y justificado.
- Mostrar resultado no equivale a guardarlo.
- Las rutas web siguen pantallas y acciones, no un REST puro decorativo.
- DTOs y view models deben ser explícitos aunque no exista API pública completa.
- Los datos visibles de oferta salen de datos comerciales reales, no de categorías normalizadas internas.
- El detalle histórico de recomendación se construye desde snapshot persistido, no desde catálogo vivo.

## Web pública

- `GET /`
- `GET /analysis/start`
- `POST /analysis`
- `GET /analysis/result/{assessment_id}`
- `GET /register`
- `POST /register`
- `GET /login`
- `POST /login`
- `POST /logout`

## Web autenticada

- `POST /analysis/{assessment_id}/save-result`
- `POST /analysis/{assessment_id}/save-switch-recommendation`
- `GET /account/saved-assessments`
- `GET /account/saved-assessments/{assessment_id}`
- `GET /account/reevaluation/base`
- `POST /account/reevaluation`
- `GET /account/switch-recommendations/{assessment_id}/confirm`
- `POST /account/switch-recommendations/{assessment_id}/confirm`
- `POST /account/validated-current-situation-base`

## Operativa de catálogo

Primera opción: Symfony Console.

Casos esperados:

- importar ofertas
- preparar publicación de catálogo
- publicar nueva `CatalogPublication`
- consultar publicaciones
- resolver publicación vigente para evaluación

No implementar panel administrativo rico en esta fase.

## Reglas de mapeo UX → aplicación

- El formulario web puede agrupar señales para reducir fricción.
- El backend debe recibir DTOs claros, no señales UX ambiguas.
- `tv_current_and_preference` debe resolverse al menos a:
  - `current_situation.tv_included`
  - `additional_condition_profile.tv_importance`, cuando aplique
- El resultado efímero puede tener `assessment_id` técnico, pero eso no lo convierte en guardado funcional.
- Guardar requiere acción explícita y sesión autenticada.

## Reglas de guardado

- En `SWITCH`, la acción visible es `Guardar esta recomendación`.
- En `WAIT` y `STAY`, la acción visible es `Guardar resultado`.
- No mostrar dos CTAs paralelos de persistencia para el mismo resultado.
- Las acciones de guardado deben ser idempotentes funcionalmente.
- Las escrituras deben usar CSRF e idempotency key cuando aplique.

## View models

Los view models Twig deben exponer lenguaje y estructura de pantalla, no objetos de dominio crudos.

Deben ocultar:

- enums internos
- códigos técnicos
- DTOs de aplicación
- categorías normalizadas del motor

Deben exponer:

- decisión visible
- motivo comprensible
- impacto estimado
- riesgos o trade-offs
- incertidumbre en lenguaje natural
- límites del análisis cuando apliquen
- acciones visibles permitidas
