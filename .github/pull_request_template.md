## Objetivo

Describe el cambio en una frase.

## Tipo de cambio

- [ ] Tooling / bootstrap
- [ ] Dominio
- [ ] Aplicación
- [ ] Infraestructura
- [ ] Interfaz web
- [ ] Testing
- [ ] Documentación operativa

## Módulos afectados

- [ ] Advisor
- [ ] Catalog
- [ ] Identity
- [ ] Shared
- [ ] Tests
- [ ] Tooling / CI

## Checklist

- [ ] Respeta `AGENTS.md` raíz
- [ ] Respeta el `AGENTS.md` del módulo afectado
- [ ] No introduce arquitectura nueva sin justificación
- [ ] No mueve lógica de dominio a controllers/Twig
- [ ] No usa `Shared` como cajón de sastre
- [ ] Incluye o actualiza tests cuando aplica
- [ ] `make quality` pasa en local

## Notas de diseño

Indica trade-offs, límites o decisiones relevantes.
