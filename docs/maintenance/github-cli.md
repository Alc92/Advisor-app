# GitHub CLI local

Este repo puede usar GitHub CLI (`gh`) para crear Pull Requests desde consola.

## Instalación en macOS

```bash
brew install gh
```

## Autenticación

```bash
gh auth login
```

## Comprobación

```bash
gh auth status
gh --version
```

## Nota

Si `gh` no está disponible en el entorno de Cursor/OpenCode, la PR debe crearse manualmente desde GitHub usando la rama indicada por la tarea.
