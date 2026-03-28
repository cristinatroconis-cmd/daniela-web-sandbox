# Architecture Notes — Daniela Montes Psicóloga (Sandbox)

**Fecha:** 2026-03-28  
Este documento complementa `ARCHITECTURE.md` (no lo reemplaza).  
Aquí queda el “por qué” de decisiones y el plan para no romper Tutor/Accesos.

---

## 1) Decisión base (confirmada)
- **Tutor LMS tiene cursos activos** → se mantiene.
- “Escuela” se entrega en Tutor, pero la navegación/SEO/UX se apoya en **CPTs**.

---

## 2) Modelo: Tutor + CPT (capa editorial) + WooCommerce (motor compra)
### Qué es cada capa
- **Tutor LMS:** contenido académico (curso/lecciones/progreso).
- **CPT `dm_escuela` (WordPress):** páginas editoriales (landing) para:
  - clarificar oferta (curso/taller/programa)
  - mejorar SEO (contenido rico + estructura)
  - mejorar UX (CTAs, chips, grid, recomendación)
- **WooCommerce:** pagos, checkout, pedidos, productos.
- **Memberships/Subscriptions:** si se usan, controlan acceso comercial (pero evitar duplicación).

### Regla de oro
Definir UNA “fuente de verdad” para gating de acceso:
- o Tutor LMS,
- o Memberships/Subscriptions,
- o integración explícita documentada (sin reglas duplicadas).

---

## 3) Auditoría Semana 1 (lo que debemos confirmar con evidencia)
- ¿Qué flujo usan los cursos Tutor hoy?
  - ¿curso gratuito?
  - ¿curso pagado?
- ¿Cómo se concede el acceso?
  - ¿por compra Woo?
  - ¿por rol?
  - ¿por membership?
- ¿Qué pasa después de comprar?
  - ¿thank you page guía correctamente al curso?
  - ¿hay “pérdida” del usuario (no encuentra el curso)?

---

## 4) UX School (Semana 2) — criterio
Objetivo: que el usuario sienta “escuela clara” sin páginas confusas.
- Navegación por CPT (cursos/talleres/programas)
- CTA consistente:
  - si tiene producto asociado: “Comprar / Agregar al carrito”
  - si ya tiene acceso: “Ir al curso” (esto depende del gating)
- Post-compra:
  - confirmación + acceso inmediato + siguiente paso recomendado

---

## 5) Rendimiento / mantenimiento
- Evitar dependencia de Elementor en nuevas secciones (preferir PHP/CSS/JS ligero).
- Minimizar scripts en checkout (especialmente si hay Slider Revolution / extras cargando global).
- Mantener módulos del child theme separados (sin duplicar lógica).

---

## 6) Riesgos
- Doble gating (Tutor + Memberships) = origen típico de bugs de acceso.
- WP File Manager = revisar necesidad.
- Cambios en slugs `/recursos/` `/escuela/`:
  - si existen Pages con esos slugs, WordPress puede priorizarlas sobre archives CPT.

---

## 7) Decisiones pendientes (Día 5)
- Definir gating:
  - Tutor-only vs Memberships-only vs híbrido controlado
- Definir “producto escuela”:
  - cursos individuales vs membresía vs híbrido (y cómo se presenta en Home)
