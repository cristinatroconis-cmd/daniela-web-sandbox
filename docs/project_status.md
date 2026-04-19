# Daniela Montes Psicóloga — Project Status (Sandbox)

**Última actualización:** 2026-04-14  
**Repo:** `cristinatroconis-cmd/daniela-web-sandbox`  
**Producción (referencia):** https://danielamontespsic.com/ (rocket.net)  
**Modo de trabajo:** staging-first — cambios técnicos en staging y promoción controlada a producción.

---

## 1) Contexto / objetivo de negocio
Negocio terapéutico con foco en:
1. **Recursos** (entrada económica — PDFs gratuitos y de pago)
2. **Escuela** (cursos / talleres / programas — ticket medio)
3. **Servicios** (sesiones / paquetes / membresías / supervisiones — último nivel / premium)

Objetivo: plataforma de formación psicológica escalable con conversión optimizada (CTAs claros → checkout WooCommerce → acceso Tutor LMS).

---

## 2) Stack técnico activo
- **CMS:** WordPress con tema hijo `daniela-child` (parent: Shoptimizer)
- **Ecommerce:** WooCommerce + Memberships + Subscriptions
- **LMS:** Tutor LMS (cursos activos — no retirar sin migración)
- **Hosting producción:** Rocket.net (no migrar aún)
- **Entorno local:** LocalWP (`dani-backup`) con symlink del theme al repo

---

## 3) Estado actual — Implementado ✅

### 3.1 CPTs (Custom Post Types)
Registrados en `wp-content/themes/daniela-child/inc/cpt.php`:

| CPT | URL | Estado |
|---|---|---|
| `dm_escuela` | `/escuela/` | ✅ Implementado |
| `dm_recurso` | `/recursos/` | ✅ Implementado |
| `dm_servicio` | `/servicios/` | ✅ Implementado |

Con taxonomías internas: `dm_tipo_escuela`, `dm_tipo_recurso`, `dm_tipo_servicio` (**legacy** en `/servicios/`), `dm_tema`.

### 3.2 Templates
Todos en `wp-content/themes/daniela-child/`:

| Archivo | Estado |
|---|---|
| `archive-dm_escuela.php` | ✅ Chips WooCommerce (Ruta A) + grid |
| `archive-dm_recurso.php` | ✅ Chips `dm_tema` (temas transversales) + grid |
| `archive-dm_servicio.php` | ✅ Chips WooCommerce (Ruta A, estricto) + grid |
| `single-dm_escuela.php` | ✅ Imagen + tipo + contenido + CTA Woo |
| `single-dm_recurso.php` | ✅ Implementado |
| `single-dm_servicio.php` | ✅ Implementado |

### 3.3 Helpers (`wp-content/themes/daniela-child/inc/helpers-cpt.php`)
- ✅ `dm_cpt_render_cta()` — botón "Agregar al carrito" con precio
- ✅ `dm_cpt_render_grid()` — tarjetas con lógica dual de enlaces y footer de CTAs
- ✅ `dm_cpt_render_taxonomy_chips()` — chips genéricos para CPTs
- ✅ `dm_escuela_render_woo_chips()` — chips de `/escuela/` basados en categorías WC
- ✅ `dm_escuela_query_args_by_woo_cat()` — filtrado por categoría WC del producto vinculado
- ✅ `dm_servicios_render_woo_chips()` — chips de `/servicios/` basados en categorías WC (`servicios/*`)
- ✅ `dm_servicios_query_args_by_woo_cat_strict()` — filtrado estricto: solo muestra ítems con producto en `servicios/*`

### 3.4 Metaboxes
- ✅ `_dm_wc_product_id` — vincula CPT a producto WooCommerce (en los 3 CPTs)
- ✅ `_dm_tutor_course_url` — URL del curso Tutor (solo `dm_escuela`)

### 3.5 Comportamiento `/escuela/`
- Chips: Todos / Cursos / Talleres / Programas (categorías WooCommerce)
- Tarjeta: imagen + título enlazan a Tutor si existe URL; si no, al single CPT
- Footer de tarjeta: "Ver curso" (Tutor, nueva pestaña) + "Agregar al carrito" (WooCommerce)
- Sin CTAs → footer no se renderiza

### 3.5b Comportamiento `/recursos/`
- Chips: Todos + temas por `dm_tema` (temas transversales como ansiedad, autoestima, etc.)
- Querystring: `?tema=<slug>` (ej. `/recursos/?tema=ansiedad`)
- Tarjeta: excerpt limpio (sin HTML) + precio si es pago, o "Gratis" si precio=0
- CTA: "Recíbelo por email" para freebies (endpoint `/recursos/recibir/`) | "Agregar al carrito" para pagos
- Importer: asigna `product_cat` `recursos` (padre) + `recursos-gratis`/`recursos-pagos` (hija según precio) — compatible con el hub `[dm_recursos]`
- Nota: taxonomía interna `dm_tipo_recurso` es **legacy** (sigue registrada en cpt.php, no se usa en chips ni UX)

### 3.5c Comportamiento `/servicios/` — Ruta A (estricto)
- Chips: Todos / Sesiones / Paquetes / Membresías / Supervisiones (categorías WooCommerce hijas de `servicios`)
- Modo **estricto**: solo aparecen ítems `dm_servicio` cuyo producto vinculado esté en `product_cat servicios/*`
- Sin producto en `servicios/*` → el ítem no aparece en el archive (aunque esté publicado)
- Querystring: `?tipo=<slug>` (ej. `/servicios/?tipo=sesiones`)
- Nota: taxonomía interna `dm_tipo_servicio` es **legacy** (existe en cpt.php, no se usa en UX/chips)

### 3.6 Entorno local
- ✅ Symlink: `$DM_WP/wp-content/themes/daniela-child` → `$DM_REPO/wp-content/themes/daniela-child`
- ✅ Variables en `~/.zshrc`: `DM_REPO` y `DM_WP`
- Flujo: `git pull` en `$DM_REPO` → refrescar navegador (sin rsync)

### 3.7 Sistema UI — Catálogo uniforme (DECISIÓN CERRADA ✅)
- ✅ Se usa **un solo sistema** de cards + grids para todos los catálogos (no hay variantes por CPT).
- ✅ Archives CPT (escuela, recursos, servicios) → `.dm-grid` + `.dm-card` via `dm_cpt_render_grid()`.
- ✅ Grids de producto WooCommerce → `.dm-products-grid` via `inc/dm-products.php`.
- ✅ Estilos centralizados en `style.css`; PHP renderers no llevan layout inline.
- ✅ CTA público neutro en catálogo: "Ver detalles".
- ✅ CTA público primario de compra: "Agregar al carrito".
- ✅ Regla de freebies: el badge "Gratis" comunica el estado en catálogo; la aclaración de que no pagará vive en single, drawer o checkout.
- 🟡 Regla UX aprobada para siguiente ajuste: si el producto ya está en el carrito, el clic debe reabrir el drawer y mostrar "Ya está en tu carrito", sin duplicados ni botón "Ver carrito" junto al CTA del bloque.
- ✅ Regla: 3 columnas en desktop (≥1024px), responsivo en tablet/mobile.
- ✅ **Thumb unificado 16:9** — todas las card-thumbs (CPT grids + recurso hub + product grids) usan `aspect-ratio: 16/9` (`--dm-card-thumb-ratio`) con `object-fit: cover`, alineado con el hero del carousel de la Home.
- ✅ **Cuerpo y footer flex** — `.dm-card__body` usa flex-column con `flex-grow` en el excerpt para que el footer de CTAs quede siempre al fondo; las cards no "saltan" de altura por diferencias en imagen o descripción.
- **Motivo:** proyecto low budget — consistencia visual mejora conversión; sistema único reduce mantenimiento.
- **Referencia:** `ARCHITECTURE.md` § 18 + `docs/ARCHITECTURE_NOTES.md` § 3c.

### 3.8 Email customization (`inc/email-tokens.php` + `inc/woocommerce-emails.php`)
- ✅ **`dm_get_email_tokens()`** — tokens de diseño cacheados (transient `dm_email_tokens_v1`, 12 h), derivados de `style.css` `:root {}`.
- ✅ **`dm_woo_email_styles()`** — CSS email-safe aplicado vía filtro `woocommerce_email_styles` (priority 20).
- ✅ Asunto y heading personalizados para emails "Pedido en proceso" y "Pedido completado".
- ✅ **`dm_email_cta_block()`** — bloque CTA de descarga directa (guest-friendly) en `woocommerce_email_after_order_table`.
- ✅ Defaults de opciones WooCommerce email no destructivos (respeta configuración admin existente).

### 3.9 WooCommerce front-end / checkout polish (2026-04-10)
- ✅ `assets/css/woocommerce.css` ya hereda el sistema visual del child theme (tipografía, botones, formularios, notices, cards de producto).
- ✅ Espaciado vertical sincronizado con Home vía `--dm-necesitas-pad-y` + padding interno `--dm-woo-box-pad` para carrito, checkout y mi cuenta.
- ✅ `inc/woocommerce-checkout.php` fuerza al español los textos visibles clave de WooCommerce mediante filtro `gettext`.
- ✅ `inc/newsletter-optin.php` renderiza el checkbox GDPR de newsletter en checkout con guard anti-duplicado.
- ✅ Copy unificado en CTAs del drawer/popup: **“Seguir comprando”** y **“Finalizar compra”**.

---

## 4) Backlog inmediato 🔲

### Prioridad alta
- [ ] **Sanitizar excerpt en el grid** (`dm_cpt_render_grid` en `inc/helpers-cpt.php`)  
  Algunos excerpts traen HTML/CTAs antiguos. Usar `wp_strip_all_tags()` antes de `wp_trim_words()`.

- [ ] **Saneamiento canónico de Tutor/Tutor Pro (post-incidente)**  
  Mantener Tutor funcionando como está por ahora, pero planificar ventana técnica para:
  1. reemplazar `tutor` y `tutor-pro` por paquetes oficiales/verificados,
  2. comparar checksums/archivos locales modificados,
  3. retirar cualquier parche temporal de bootstrap si ya no es necesario,
  4. validar QA completo (front, admin, checkout, cursos, lecciones, quizzes).

### Prioridad media
- [ ] **Subitems hover en menú principal**  
  Agregar subitems en WP Admin → Apariencia → Menús:
  - Escuela → Cursos (`/escuela/?tipo=cursos`) / Talleres / Programas
  - Recursos → Por tema (`/recursos/?tema=<slug>` con slugs de `dm_tema`)
  - Servicios → Sesiones / Paquetes / Membresías / Supervisiones (Woo categories hijas de `servicios`)

- [ ] **Definir estado final de plugins de login/pagos en producción (post-incidente)**
  Validar en staging y luego decidir en producción:
  1. si `wps-hide-login` y/o `loginpress` se reactivan sin romper acceso (`wp-login.php`, `/wp-admin`, redirects),
  2. si `woocommerce-paypal-payments` se reactiva o se retira definitivamente,
  3. si `dm-fix-order-received-key` (MU workaround) puede eliminarse tras QA de checkout/thank-you.
  
- [ ] **Auditar gating de acceso** (Tutor vs Memberships/Subscriptions)  
  Confirmar quién controla el acceso post-compra y documentarlo.  
  Decidir si se agrega "Ir al curso" en la thank-you page de WooCommerce.

### Prioridad baja / futuro
- [ ] **UX checkout — fase 2 (performance)** — la capa visual, la localización al español y el newsletter opt-in ya están implementados; pendiente auditar y deshabilitar scripts innecesarios (Elementor, Slider Revolution, etc.) vía hooks en `functions.php`
- [ ] **Home “¿Qué necesitas?” — fase 2** — revisar copy, orden de slides y QA responsive del bloque/carousel ya implementado
- [ ] **Email automation** — integración MailerLite post-compra
- [ ] **Flush de rewrite rules** tras activar CPTs en entorno nuevo  
  `WP Admin → Ajustes → Enlaces permanentes → Guardar cambios` (o `wp rewrite flush` con WP-CLI)
- [ ] **Verificar consistencia visual de cards** en `/recursos/`, `/escuela/`, `/servicios/` y cualquier grid de producto Woo: confirmar que el thumb 16:9 y el footer flex se aplican correctamente en todos los contextos (incluyendo imágenes verticales y tarjetas sin imagen destacada).

---

## 5) Riesgos activos

| Riesgo | Estado |
|---|---|
| Página estática con slug `/escuela/` o `/recursos/` | ⚠️ Verificar que no existe como Page (bloquea el archive CPT) |
| Doble gating Tutor + Memberships | ⚠️ Pendiente auditoría |
| Excerpts con HTML antiguo | ⚠️ Pendiente sanitización (ver backlog 4.1) |
| WP File Manager instalado | ⚠️ Revisar necesidad/riesgo de seguridad |
| Symlink `daniela-child` en LocalWP | ✅ Activo — si se pierde, recrear con `ln -s` |

---

## 6) Flujo de trabajo estándar

### Actualizar código desde GitHub
```bash
# A) En el repo
cd "$DM_REPO"
git checkout main
git pull --no-rebase origin main

# B) Ver en LocalWP
# Solo refrescar navegador (Cmd+Shift+R)
# El symlink hace que LocalWP ya use el código actualizado.
```

### Si el archive CPT da 404 después de cambiar slugs
```bash
# Opción 1: WP Admin → Ajustes → Enlaces permanentes → Guardar cambios
# Opción 2: WP-CLI
cd "$DM_WP" && wp rewrite flush
```

### Verificar que el symlink existe
```bash
ls -la "$DM_WP/wp-content/themes" | grep daniela-child
```

---

## 7) Archivos del child theme (mapa rápido)

```
wp-content/themes/daniela-child/
├── functions.php                   # Bootstrap: carga inc/cpt.php, inc/helpers-cpt.php, etc.
├── style.css                       # Declaración del child theme + estilos dm-card/dm-btn/dm-chips
├── inc/
│   ├── cpt.php                     # Registra CPTs y taxonomías
│   ├── helpers-cpt.php             # Metaboxes, CTA, chips, grid
│   ├── shortcodes-escuela.php      # Shortcodes WooCommerce para páginas de Escuela
│   ├── shortcodes-servicios.php    # Shortcodes para páginas de Servicios
│   ├── email-tokens.php            # Extrae tokens CSS para emails
│   ├── woocommerce-emails.php      # Personalización emails WooCommerce (CSS, asuntos, CTA)
│   ├── woocommerce-checkout.php    # Redirects / UX del checkout + traducciones gettext
│   ├── newsletter-optin.php        # Checkbox GDPR en checkout + integración MailerLite
│   ├── cart-drawer.php             # Drawer del carrito; elimina botones WC nativos vía wp_loaded
│   └── ...                         # Otros módulos (assets, header, checkout, etc.)
├── archive-dm_escuela.php          # Template archive /escuela/
├── archive-dm_recurso.php          # Template archive /recursos/
├── archive-dm_servicio.php         # Template archive /servicios/
├── single-dm_escuela.php           # Template single /escuela/<slug>/
├── single-dm_recurso.php           # Template single /recursos/<slug>/
└── single-dm_servicio.php          # Template single /servicios/<slug>/
```

---

## 8) Guardrails de producción (activo)

Estado aplicado en producción (`https://danielamontespsic.com`) desde 2026-04-14:

- `WP_ENVIRONMENT_TYPE=production`
- `DISALLOW_FILE_EDIT=true`
- `DISALLOW_FILE_MODS=true`
- `AUTOMATIC_UPDATER_DISABLED=true`
- `WP_AUTO_UPDATE_CORE=false`

Implicaciones operativas:

1. No instalar/actualizar/eliminar plugins o temas directamente en producción.
2. No editar archivos desde el admin de producción.
3. Todo cambio técnico (código/plugins/tema/config) se valida primero en staging.
4. Solo se promueve a producción después de QA y backup.

Nota de estabilidad post-restauración (2026-04-14):

- Se normalizó acceso de admin en producción corrigiendo permisos de core (`wp-admin`, `wp-includes`) y detección SSL detrás de proxy en `wp-config.php`.
