# Daniela Montes Psicóloga — Project Status (Sandbox)

**Fecha:** 2026-03-28  
**Repo:** `cristinatroconis-cmd/daniela-web-sandbox`  
**Producción (referencia):** https://danielamontespsic.com/ (rocket.net)  
**Modo de trabajo:** sandbox / aislado — NO tocar producción directo.

---

## 1) Contexto / objetivo negocio
Negocio terapéutico con foco en:
1) **Recursos** (entrada económica)
2) **Cursos / Escuela** (ticket medio)
3) **Sesiones** (último nivel / premium)

Objetivo: compradores recurrentes + CTAs + checkout UX + acceso a cursos “tipo escuela”.

---

## 2) Estado actual (confirmado)
- Web existente en WordPress (no se rompe).
- **Tutor LMS tiene cursos activos** (bloqueante: no se puede retirar sin migración).
- WooCommerce está presente con Memberships + Subscriptions.
- Se busca maximizar WooCommerce y mejorar conversión/UX.
- Hosting actual rocket.net; **no migrar aún**.

---

## 3) Decisión de arquitectura (dirección)
### Escuela: Tutor LMS + Capa CPT (editorial)
- **Tutor LMS** = experiencia de curso (contenido, lecciones, progreso, etc.)
- **CPT `dm_escuela`** = capa editorial / UX / SEO:
  - navegación por “cursos/talleres/programas”
  - páginas de aterrizaje más claras
  - CTAs consistentes hacia compra/acceso
  - estructura más escalable que solo categorías Woo o páginas sueltas

**Regla clave:** evitar “dos fuentes de verdad” para el acceso.
- Definir si el gating final lo controla:
  - Tutor (y su sistema),
  - o Woo Memberships/Subscriptions,
  - o una integración controlada (pero con reglas claras documentadas).

---

## 4) PLAN REALISTA (no fantasía)

### Semana 1 — Base sólida (auditar + decidir)
**Día 3–4: Auditoría técnica (entregables claros)**
Checklist mínimo:
- Inventario de cursos activos en Tutor:
  - cantidad, tipos, si están pagados/gratis, qué roles/accesos usan
- Inventario Woo:
  - productos asociados a cursos (si aplica)
  - categorías: recursos/cursos/talleres/programas/sesiones
- Auditoría de acceso:
  - Memberships: planes activos, reglas de restricción
  - Subscriptions: productos de suscripción activos (si existen)
  - ¿hay doble gating? (Tutor + Memberships a la vez)
- Recorridos reales (con 1 usuario prueba):
  - compra recurso (gratis y pago)
  - compra curso / acceso curso
  - login / acceso “escuela”

**Resultado (fin día 4):**
- Mapa claro de arquitectura actual (quién controla qué)
- Lista de fricciones en checkout + acceso escuela
- Lista de scripts/plugins que más cargan checkout/home

**Día 5: Decisión estratégica**
Con evidencia:
- Tutor se queda (sí) → definir cómo convive con Woo/Memberships.
- ¿Unificamos checkout? ¿Qué campos sobran?
- ¿Cómo se vende “escuela”: curso individual vs membresía vs híbrido?
- Priorización Semana 2 (impacto negocio vs riesgo).

### Semana 2 — Optimización visible y rentable
1) Home reestructurada por escalera de valor:
   - Recursos → Cursos → Membresía (si aplica) → Sesiones
2) Checkout UX:
   - reducir fricción
   - CTAs claros
   - flujo de “escuela” más limpio post-compra
3) Rendimiento:
   - eliminar scripts innecesarios (especialmente en checkout)
   - reducir JS duplicado
   - limpieza de dependencias

---

## 5) Riesgos (no romper)
- Tutor activo: cambios deben ser reversibles.
- Memberships/Subscriptions: tocar reglas sin auditoría puede romper accesos.
- WP File Manager: revisar necesidad/riesgo.
- Rocket.net: no migrar aún.

---

## 6) Próximos pasos inmediatos (acción)
1) Completar auditoría (Semana 1 Día 3–4).
2) Documentar decisión de gating (Tutor vs Memberships) y el “camino oficial” del usuario.
3) Implementar mejoras Semana 2 sin duplicar lógica.
