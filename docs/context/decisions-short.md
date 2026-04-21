# Decisions short

Resumen operativo de decisiones cerradas para implementación.

- Producto = advisor, no comparador genérico.
- Outputs principales = `SWITCH`, `WAIT`, `STAY`.
- Entrada principal = manual guiada; factura solo como apoyo opcional.
- Vertical MVP = telecom residencial para particulares.
- Alcance MVP = móvil, fibra y fibra + móvil.
- Catálogo = limitado y curado; no cubre todo el mercado.
- Neutralidad = dentro del catálogo disponible, no del mercado completo.
- El sistema opera con incertidumbre; no bloquea por falta de datos salvo mínimo de evaluación.
- `WAIT` exige motivo y trigger o momento de revisión.
- Mejoras insuficientes se resuelven como `STAY`, no como `WAIT`.
- El usuario se modela de forma agregada en el MVP.
- La recomendación histórica no debe depender del catálogo vivo.
- Mostrar resultado no equivale a guardarlo.
- La continuidad del MVP es manual, no automática.
- Confirmar un cambio no muta el histórico previo; genera nueva base validada.
- Interfaz principal = web mobile-first con Twig, no SPA.
