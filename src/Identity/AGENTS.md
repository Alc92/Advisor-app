# AGENTS.md

## Misión del módulo

`Identity` implementa identidad y cuenta mínimas del MVP.

Soporta:

- registro
- login
- sesión autenticada
- estado básico de cuenta
- consentimientos mínimos
- relación técnica entre cuenta y assessments persistidos

No es el dominio del asesor.

## Fuentes locales de referencia

- `docs/context/domain-summary.md`
- `docs/context/use-cases-summary.md`
- `docs/context/contracts-current.md`
- `AGENTS.md` raíz

## Qué está cerrado en este módulo

- `Identity` es un contexto estrecho
- `UserAccount` representa identidad autenticable
- `AccountProfile` representa perfil mínimo no funcional y consentimientos
- la autenticación del MVP es email + password + sesión HTTP
- no hay JWT
- no hay OAuth social
- la cuenta no debe absorber preferencias ni situación del asesor

## Casos de uso que viven aquí

- `RegisterUser`
- `LoginUser`

## Reglas de implementación

### 1. Contexto estrecho
No conviertas `Identity` en contenedor de cualquier dato “del usuario”.

Si un dato describe evaluación, recomendación o continuidad del asesor, no pertenece aquí.

### 2. `UserAccount` y `AccountProfile` no se fusionan por comodidad
Mantén separada identidad autenticable y perfil mínimo.

### 3. Autenticación mínima y segura
En el MVP:

- email único
- password hasheado
- sesión HTTP segura
- cuenta deshabilitada no autentica

### 4. Nada de lifecycle rico
No implementes aquí campañas, notificaciones, scoring, automation flows ni CRM.

### 5. Relación con el asesor por referencia
`Assessment.userId` referencia `UserAccount.id`.

No metas aggregates del asesor dentro de `Identity`.

## Fronteras con otros módulos

### Con `Advisor`
`Identity` no decide recommendation logic ni continuidad funcional del asesor.

### Con `Catalog`
No tiene relación funcional directa salvo wiring técnico excepcional.

## Estructura esperada

```text
src/Identity/
  Domain/
  Application/
    Command/
    Query/
    Port/
  Infrastructure/
  Interface/
    Http/
```

## Testing esperado

### Unit
- validación básica de registro
- reglas locales de autenticación

### Integración
- persistencia de cuenta
- unicidad de email canónico
- login real contra infraestructura configurada

### Aceptación
- registro web
- login y redirect al target path

## Antipatrones a evitar

- meter preferencias del asesor en `AccountProfile`
- usar `Identity` como lugar para “cosas del usuario” sin criterio
- añadir JWT u OAuth sin necesidad real
