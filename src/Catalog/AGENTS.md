# AGENTS.md

## Misión del módulo

`Catalog` implementa el catálogo curado y evaluable del MVP.

Su responsabilidad es soportar:

- ofertas curadas
- versiones de oferta
- publicaciones
- importación operativa
- publicación de catálogo
- resolución de publicación vigente para evaluación

No decide recomendaciones.

## Fuentes locales de referencia

- `docs/context/domain-summary.md`
- `docs/context/use-cases-summary.md`
- `docs/context/contracts-current.md`
- `AGENTS.md` raíz

## Qué está cerrado en este módulo

- el catálogo del MVP es limitado y curado
- la unidad evaluable real es `TelecomOfferVersion`
- la identidad estable vive en `TelecomOffer`
- la frontera operativa principal es `CatalogPublication`
- una oferta no se considera evaluable por sí sola
- la publicación vigente manda sobre la vigencia aislada
- la capa comercial es fuente primaria de los datos visibles
- la capa normalizada es derivada y se usa para evaluación
- no hay workflow editorial rico por oferta

## Casos de uso que viven aquí

- `GetPublishedCatalogForEvaluation`
- `ImportCatalogOffers`
- `CreateOrUpdateCatalogPublicationDraft`
- `PublishCatalogPublication`
- `GetCatalogPublications`

## Reglas de implementación

### 1. Oferta estable ≠ versión evaluable
No metas precio, GB, Mbps ni atributos versionables en `TelecomOffer`.

### 2. Publicación como frontera evaluable
Una `TelecomOfferVersion` solo es evaluable cuando forma parte de una `CatalogPublication` publicada.

### 3. El borrador operativo vive en `CatalogPublication`
No implementes estado borrador/publicada en la oferta como eje editorial principal.

### 4. Datos comerciales y normalizados separados
- comercial = visible para usuario e histórico explicable
- normalizada = derivada para evaluación interna

### 5. Sin workflow editorial rico
No introduzcas aprobación multinivel, YAML workflows ni backoffice rico.

### 6. Importación pragmática
Puede usar backend + CLI.

No la conviertas en una arquitectura de pipelines o colas.

## Fronteras con otros módulos

### Con `Advisor`
`Catalog` no decide la recomendación.

### Con `Identity`
Sin responsabilidad funcional de acceso de usuario final.

## Estructura esperada

```text
src/Catalog/
  Domain/
  Application/
    Command/
    Query/
    Port/
  Infrastructure/
  Interface/
    Http/
    Cli/
```

## Testing esperado

### Unit
- reglas locales de publicación
- adaptación entre entrada operativa y dominio

### Integración
- persistencia de ofertas y versiones
- persistencia de publicaciones
- resolución de publicación vigente
- protección ante publicación vacía o duplicados

### Aceptación
- importar catálogo
- crear/actualizar borrador
- publicar catálogo

## Antipatrones a evitar

- meter recommendation logic aquí
- tratar `TelecomOffer` como unidad evaluable real
- usar fechas de versión como único mecanismo de selección
- inflar el catálogo con workflows editoriales ricos
