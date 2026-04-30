# advisor-app

Aplicación Symfony del MVP telecom, organizada como monolito modular y preparada para desarrollo asistido con Cursor.

## Stack base

- PHP 8.4
- Symfony 7.4 LTS
- Twig como interfaz principal
- PostgreSQL como persistencia final
- PHPUnit para testing
- Arquitectura `module-first`

## Estructura

- `AGENTS.md`: reglas globales de implementación
- `src/Advisor/AGENTS.md`: reglas del módulo Advisor
- `src/Catalog/AGENTS.md`: reglas del módulo Catalog
- `src/Identity/AGENTS.md`: reglas del módulo Identity
- `tests/AGENTS.md`: estrategia de testing
- `.cursor/PROJECT.md`: guía rápida de uso de Cursor
- `.cursor/skills/`: workflows reutilizables
- `.cursor/commands/`: comandos operativos de Cursor
- `docs/context/`: resúmenes operativos derivados de las fuentes maestras

## Convención de idioma

- Código, clases, carpetas y nombres técnicos: inglés.
- Instrucciones operativas, prompts, `AGENTS.md`, skills, commands y documentación de control: español.

## Uso recomendado con Cursor

1. Leer `.cursor/PROJECT.md`.
2. Respetar `AGENTS.md` raíz.
3. Consultar el `AGENTS.md` del módulo afectado.
4. Usar `/plan-first` antes de cambios ambiguos, multi-módulo o que toquen persistencia.
5. Usar skills para workflows repetibles.

## Comandos locales

Levantar entorno:

```bash
make build
make up
make install
```

Ejecutar consola Symfony:

```bash
make console
```

Ejecutar tests:

```bash
make test
make test-unit
make test-integration
make test-acceptance
```

Smoke check:

```bash
make smoke
```

Parar entorno:

```bash
make down
```

## Reglas importantes

- No implementar casos de uso funcionales sin plan previo.
- No introducir ORM como eje de persistencia.
- No mover reglas de dominio a controllers, Twig o formularios.
- No usar `Shared` como cajón de sastre.
- No duplicar las fuentes maestras `00`–`09` dentro de este repo.
- `docs/context/` contiene resúmenes derivados y debe mantenerse corto.

## Variables locales

El proyecto incluye `.env.local.example` como plantilla de configuración local.

Para sobrescribir valores locales:

```bash
cp .env.local.example .env.local
```

Nunca versionar secretos reales en `.env.local`.

`.env.local` y `.env.*.local` deben permanecer ignorados por Git.

## Calidad local

Comprobar estilo:

```bash
make cs-check
```

Corregir estilo:

```bash
make cs-fix
```

Ejecutar tests:

```bash
make test
```

Ejecutar checks completos:

```bash
make quality
```

Instalar hook local de pre-commit:

```bash
make install-git-hooks
```

El hook ejecuta dentro de Docker:

- validación básica de Composer
- PHP-CS-Fixer en modo dry-run
- tests unitarios

## Convención de commits

Usar mensajes breves en inglés, en imperativo o descripción directa.

Ejemplos:

```text
Prepare technical bootstrap
Add local and CI quality gates
Add repository governance basics
Implement assessment input snapshot
Add catalog publication persistence
```

Evitar commits que mezclen:

- feature + refactor
- tooling + dominio
- persistencia + UI
- cambios masivos sin plan


## Generar un zip limpio del repositorio

Para generar un zip limpio del estado versionado del repositorio, sin `vendor/`, `.git/`, cachés ni archivos locales:

```bash
git archive --format=zip --prefix=advisor-app/ -o advisor-app-clean.zip HEAD
```

Nota: este comando solo incluye lo que está en el commit actual (`HEAD`). No incluye cambios sin commit, aunque estén modificados en la carpeta local.
