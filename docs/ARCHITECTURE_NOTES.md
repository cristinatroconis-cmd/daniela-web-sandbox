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

---

## 8) Implementación Enfoque 1 — dm_escuela + Tutor LMS (2026-03-28)

### Qué se implementó
1. **Metabox Tutor LMS en dm_escuela** (`inc/helpers-cpt.php`):
   - `_dm_tutor_course_id` — ID numérico del curso Tutor LMS vinculado.
   - `_dm_tutor_course_url` — URL manual del curso (anula el permalink automático).
   - Guardado con nonce, sanitización y verificación de permisos.

2. **Helpers de acceso y CTA Tutor** (`inc/helpers-cpt.php`):
   - `dm_tutor_user_has_access($post_id)`: verifica inscripción. Prioriza `tutor_utils()->is_enrolled()`; fallback a consulta de posts `tutor_enrolled`.
   - `dm_tutor_get_course_url($post_id)`: URL del curso (manual > permalink del post Tutor).
   - `dm_cpt_render_tutor_cta($post_id)`: renderiza botón "Ir al curso".

3. **CTA condicional en single-dm_escuela.php**:
   - Si usuario inscrito → `dm_cpt_render_tutor_cta()`.
   - Si no inscrito / no logueado → `dm_cpt_render_cta()` (WooCommerce).

4. **Auto-clasificación en save** (`inc/cpt.php`):
   - Hook `save_post_dm_escuela` (prioridad 20).
   - Solo actúa si `dm_tipo_escuela` no tiene términos asignados.
   - Reglas: "taller" → talleres, "programa" → programas, else → cursos.

5. **Bulk action de backfill** (`inc/cpt.php`):
   - Acción masiva "Auto-clasificar tipo (backfill)" en WP Admin > Escuela CPT.
   - Solo clasifica posts sin término asignado.
   - Muestra aviso con el número de posts actualizados.

### Por qué estas decisiones
- Tutor LMS se mantiene como "source of truth" para cursos activos.
- CPT `dm_escuela` sirve de landing editorial/SEO, no reemplaza el LMS.
- El gating de acceso usa `tutor_utils()->is_enrolled()` como primera opción para no depender de Memberships/Subscriptions (evitar doble gating).
- El fallback via posts `tutor_enrolled` asegura que el check funciona aunque Tutor LMS no esté cargado en ese contexto.
- La auto-clasificación reduce trabajo manual al crear/editar posts.
