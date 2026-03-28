# Architecture Notes — Daniela Montes Psicóloga (Sandbox)

**Última actualización:** 2026-03-28  
Este documento complementa `ARCHITECTURE.md` (no lo reemplaza).  
Aquí queda el "qué está implementado", el "por qué" de las decisiones y el backlog inmediato.

---

## 1) Decisión base (confirmada e implementada)
- **Tutor LMS tiene cursos activos** → se mantiene como motor de contenido.
- **WooCommerce** = motor de compra (checkout, productos, categorías).
- **CPTs `dm_escuela`, `dm_recurso`, `dm_servicio`** = capa editorial/UX/SEO.
- Integración Tutor = **solo linkout** (`_dm_tutor_course_url`); el child theme no llama a APIs de Tutor.

---

## 2) Modelo: Tutor + CPT (capa editorial) + WooCommerce (motor compra)

### Qué es cada capa
| Capa | Motor | Responsabilidad |
|---|---|---|
| Tutor LMS | Tutor | Contenido académico (lecciones, progreso, certificados) |
| CPTs (`dm_escuela`, `dm_recurso`, `dm_servicio`) | WordPress nativo | Landing editorial, SEO, UX, CTAs, chips de filtro |
| WooCommerce | WooCommerce | Pagos, checkout, productos, categorías (fuente de verdad clasificación) |
| Memberships/Subscriptions | Woo plugins | Gating comercial (no se duplica en el child theme) |

### Regla de oro
Una sola "fuente de verdad" para gating:
- o Tutor LMS,
- o Memberships/Subscriptions,
- **no los dos a la vez** sin documentar explícitamente cómo conviven.

---

## 3) Implementado ✅

### Archivos clave

| Archivo | Responsabilidad |
|---|---|
| `wp-content/themes/daniela-child/inc/cpt.php` | Registra CPTs y taxonomías; crea términos por defecto en `init` |
| `wp-content/themes/daniela-child/inc/helpers-cpt.php` | Metaboxes, CTA renderer, chips de taxonomía, grid CPT, chips Woo (Ruta A) |
| `wp-content/themes/daniela-child/archive-dm_escuela.php` | Template archive `/escuela/` con chips WooCommerce |
| `wp-content/themes/daniela-child/archive-dm_recurso.php` | Template archive `/recursos/` con chips taxonomía |
| `wp-content/themes/daniela-child/archive-dm_servicio.php` | Template archive `/servicios/` con chips taxonomía |
| `wp-content/themes/daniela-child/single-dm_escuela.php` | Template single `/escuela/<slug>/` |
| `wp-content/themes/daniela-child/single-dm_recurso.php` | Template single `/recursos/<slug>/` |
| `wp-content/themes/daniela-child/single-dm_servicio.php` | Template single `/servicios/<slug>/` |
| `wp-content/themes/daniela-child/functions.php` | Bootstrap del child theme; carga `inc/cpt.php` e `inc/helpers-cpt.php` |

### CPTs registrados

| CPT | URL archive | Filtro chips |
|---|---|---|
| `dm_escuela` | `/escuela/` | Categorías WooCommerce: cursos / talleres / programas (Ruta A) |
| `dm_recurso` | `/recursos/` | Taxonomía `dm_tipo_recurso`: gratis / pagos |
| `dm_servicio` | `/servicios/` | Taxonomía `dm_tipo_servicio`: sesiones / membresias |

### Metaboxes implementados

| Meta key | CPT | Comportamiento |
|---|---|---|
| `_dm_wc_product_id` | `dm_recurso`, `dm_escuela`, `dm_servicio` | Vincula al producto WC; el CTA usa `add_to_cart_url()` + precio |
| `_dm_tutor_course_url` | `dm_escuela` | Path del curso Tutor; imagen y título de tarjeta enlazan a Tutor; botón "Ver curso" |

### Comportamiento del grid `/escuela/` (`dm_cpt_render_grid`)
- Imagen y título de tarjeta enlazan al curso Tutor si `_dm_tutor_course_url` está presente; si no, al single CPT.
- Footer de tarjeta muestra hasta 2 CTAs:
  1. **"Ver curso"** — solo si existe `_dm_tutor_course_url`; abre en nueva pestaña (`target="_blank" rel="noopener"`).
  2. **"Agregar al carrito"** — solo si existe `_dm_wc_product_id`; incluye precio si el producto es de pago.
- Si ningún CTA aplica, el footer no se renderiza.

### Filtro/chips de `/escuela/` — Ruta A
- Función: `dm_escuela_render_woo_chips()` en `inc/helpers-cpt.php`.
- Usa **categorías de producto WooCommerce** (`product_cat`: cursos/talleres/programas) como fuente de filtro.
- **Por qué Ruta A (no taxonomía interna):** evita duplicar la clasificación entre `dm_tipo_escuela` y `product_cat` de WooCommerce. Una sola fuente de verdad para "qué tipo de formación es".
- Filtrado PHP: `dm_escuela_query_args_by_woo_cat()` resuelve qué posts `dm_escuela` pertenecen a la categoría WC seleccionada cruzando `_dm_wc_product_id` + `has_term()`.

---

## 4) Backlog inmediato 🔲

### 4.1 Sanitizar excerpt en el grid (prioritario)
- **Problema:** algunos excerpts de posts CPT traen HTML/CTAs de versiones antiguas del theme.
- **Síntoma:** en el grid se ven botones o markup HTML dentro del texto del excerpt.
- **Solución:** en `dm_cpt_render_grid()` (`inc/helpers-cpt.php`), cambiar:
  ```php
  // Actual:
  '<p class="dm-card__excerpt">' . wp_kses_post(wp_trim_words($excerpt, 20)) . '</p>'
  
  // Propuesta:
  '<p class="dm-card__excerpt">' . esc_html(wp_trim_words(wp_strip_all_tags($excerpt), 20)) . '</p>'
  ```
- Afecta solo el render del grid; el contenido guardado en DB no cambia.

### 4.2 Menú principal — subitems hover
- **Pendiente:** agregar subitems al menú para Escuela, Recursos y Servicios.
  - Escuela → Cursos / Talleres / Programas
  - Recursos → Gratis / Pagos / Por tema
  - Servicios → Sesiones / Membresías
- **Cómo:** WP Admin → Apariencia → Menús (no requiere código nuevo; solo configurar los items de menú con URLs correctas).
- **URLs a usar:**
  - `/escuela/?tipo=cursos`, `/escuela/?tipo=talleres`, `/escuela/?tipo=programas`
  - `/recursos/?tipo=gratis`, `/recursos/?tipo=pagos`
  - `/servicios/?tipo=sesiones`, `/servicios/?tipo=membresias`

### 4.3 Optimización checkout
- Revisar scripts que cargan en `/checkout/` (Elementor, Slider Revolution, etc.).
- Objetivo: reducir tiempo de carga y fricción en la página de pago.
- No tocar WooCommerce core; solo deshabilitar assets innecesarios vía hooks en `functions.php`.

### 4.4 Auditar gating Tutor vs Memberships
- Confirmar si el acceso post-compra lo controla Tutor o Woo Memberships/Subscriptions.
- Documentar el flujo oficial para evitar "doble gating" (usuario paga → no puede acceder).
- Decidir si se añade un link "Ir al curso" en la thank-you page.

### 4.5 Flush de rewrite rules (recordatorio)
- Después de cualquier cambio en slugs de CPT o activar el plugin por primera vez:
  ```bash
  # Con WP-CLI desde el LocalWP
  cd "$DM_WP" && wp rewrite flush
  # O desde WP Admin → Ajustes → Enlaces permanentes → Guardar cambios
  ```

---

## 5) Riesgos conocidos

| Riesgo | Mitigación |
|---|---|
| Página estática con slug `/escuela/` o `/recursos/` existe como Page | WordPress la prioriza sobre el archive CPT → cambiar slug de la Page o convertirla a borrador |
| Doble gating (Tutor + Memberships) | Auditar y documentar una sola fuente de acceso |
| WP File Manager instalado | Revisar necesidad; puede ser vector de seguridad |
| Excerpts con HTML antiguo | Sanitizar en `dm_cpt_render_grid()` (ver 4.1) |
| Symlink `daniela-child` en LocalWP | Si se re-crea el site en Local, el symlink se pierde → recrear con `ln -s` |

---

## 6) Entorno local

### Variables en `~/.zshrc`
```bash
export DM_REPO="/Users/cristinatroconis/Desktop/daniela-web-sandbox"
export DM_WP="/Users/cristinatroconis/Local Sites/dani-backup/app/public"

alias dmrepo='cd "$DM_REPO"'
alias dmwp='cd "$DM_WP"'
```

### Symlink del theme
```bash
# Verificar que el symlink existe y apunta al repo
ls -la "$DM_WP/wp-content/themes" | grep daniela-child
# Esperado: daniela-child -> /Users/cristinatroconis/Desktop/daniela-web-sandbox/wp-content/themes/daniela-child

# Si el symlink se perdió, recrearlo:
ln -s "$DM_REPO/wp-content/themes/daniela-child" "$DM_WP/wp-content/themes/daniela-child"
```

### Flujo estándar de actualización
```bash
# A) Bajar código nuevo
cd "$DM_REPO"
git checkout main
git pull --no-rebase origin main

# B) Ver cambios en LocalWP
# No se necesita rsync. El symlink hace que LocalWP ya use el código nuevo.
# Solo recargar el navegador (Cmd+Shift+R para hard reload).

# C) Si el archive CPT no carga (404):
# Ir a WP Admin → Ajustes → Enlaces permanentes → Guardar cambios
```

---

## 7) Checklist de pruebas manuales

### Archive `/escuela/`
- [ ] La página carga sin errores PHP.
- [ ] Los chips "Todos / Cursos / Talleres / Programas" se muestran.
- [ ] Chip "Todos" activo por defecto (sin querystring).
- [ ] Al hacer click en "Cursos", la URL cambia a `/escuela/?tipo=cursos` y el grid filtra.
- [ ] Chip activo tiene clase `dm-chip--active` y `aria-current="true"`.
- [ ] Las tarjetas muestran imagen, título y excerpt (sin HTML en el excerpt).
- [ ] Si el ítem tiene `_dm_tutor_course_url`: imagen y título enlazan al curso Tutor.
- [ ] Botón "Ver curso" abre en nueva pestaña.
- [ ] Botón "Agregar al carrito" añade el producto al carrito WooCommerce.
- [ ] Si no hay `_dm_wc_product_id`: no aparece el botón de carrito.
- [ ] Si no hay `_dm_tutor_course_url`: no aparece "Ver curso"; imagen/título enlazan al single CPT.

### Single `/escuela/<slug>/`
- [ ] La página carga sin errores PHP.
- [ ] Se muestra imagen destacada, título, tipo (chip), contenido y CTA WooCommerce.
- [ ] El botón "Volver a Escuela" enlaza correctamente al archive.
- [ ] Si no hay `_dm_wc_product_id`: no aparece el CTA (sin sección vacía).

### Archive `/recursos/`
- [ ] La página carga con chips "Todos / Gratis / Pagos".
- [ ] Filtro por tipo funciona con querystring `?tipo=gratis`.
- [ ] Las tarjetas muestran CTA correcto según precio del producto.

### Archive `/servicios/`
- [ ] La página carga con chips "Todos / Sesiones / Membresías".
- [ ] Grid muestra los servicios publicados.

### Metabox en WP Admin
- [ ] Al editar un `dm_escuela`, aparece el metabox "Producto WooCommerce relacionado".
- [ ] Al editar un `dm_escuela`, aparece el metabox "Curso Tutor (URL)".
- [ ] Guardar y recargar conserva los valores correctamente.
- [ ] Dejar vacío el campo borra el meta (no guarda `0` ni cadena vacía).
