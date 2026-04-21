# Contracts current

## Superficies principales

### Web pública
- `GET /`
- `GET /analysis/start`
- `POST /analysis`
- `GET /analysis/result/{assessment_id}`
- `GET /register`
- `POST /register`
- `GET /login`
- `POST /login`

### Web autenticada
- `POST /analysis/{assessment_id}/save-result`
- `POST /analysis/{assessment_id}/save-switch-recommendation`
- `GET /account/saved-assessments`
- `GET /account/saved-assessments/{assessment_id}`
- `GET /account/reevaluation/base`
- `POST /account/reevaluation`
- `GET /account/switch-recommendations/{assessment_id}/confirm`

## Reglas de contratos

- web-first, no API-first
- POST/Redirect/GET para acciones de usuario
- JSON solo como apoyo puntual
- mostrar resultado no equivale a guardarlo
- detalle histórico de recomendación desde snapshot persistido, no desde catálogo vivo
