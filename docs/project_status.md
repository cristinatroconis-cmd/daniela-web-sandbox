# Daniela Montes Psicóloga — Project Status (Sandbox)

**Última actualización:** 2026-04-21  
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
- Chips: Todos + temas por `product_tag` (fuente primaria de temas)
- Querystring: `?tema=<slug>` (ej. `/recursos/?tema=ansiedad`)
- Tarjeta: excerpt limpio (sin HTML) + precio si es pago, o "Gratis" si precio=0
- CTA: "Agregar al carrito" o waitlist según reglas vigentes del producto; gratis/pago se comunica por precio, no por categoría pública
- Importer: asigna `product_cat=recursos` como categoría pública estable
- Nota: taxonomía interna `dm_tipo_recurso` es **legacy** (sigue registrada en cpt.php, no se usa en chips ni UX); `dm_tema` queda como espejo editorial sincronizado desde `product_tag`

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

### 3.10 Navegación principal en staging (2026-04-20)
- ✅ Se confirmó que el header principal visible sale del menú asignado en WordPress (DB-driven), no de un archivo hardcodeado del child theme.
- ✅ Se sincronizó en staging la estructura base del menú desde el estado más nuevo de LocalWP.
- ✅ Se eliminaron dos items duplicados directamente en la base de datos de staging y luego se verificó el resultado en frontend.
- ✅ Producción quedó intacta; el ajuste fue solo en staging.
- Estado visible validado en staging tras la limpieza: **Inicio / Recursos / Escuela / Servicios / Sobre Mi / Blog / Newsletter / [Acceso]**.
- Dirección UX aprobada para la siguiente jerarquía del menú: `Recursos` sin hijos; `Escuela` con dropdown por tipos; `Servicios` con dropdown por tipos; `Sobre Mi` agrupando `Blog` y `Newsletter` como hijos.
- ✅ Regla de header/cart aprobada: el item `nav-menu-item-9366` sale del menú primario y se reusa en el bloque de header como **Iniciar sesion**, conservando el destino original del item (`/escritorio/` en staging).
- ✅ Regla global de carrito: el header nunca debe abrir el cart drawer nativo de Shoptimizer ni el de plugins terceros; siempre debe abrir el drawer del child theme (`#dm-cart-drawer`).
- ✅ Regla de integración visual en `header-4`: el link **Iniciar sesion** del bloque custom de header no debe depender del estilo heredado del parent/Kirki; su apariencia final se fuerza desde el child theme sobre el selector real de `col-full-nav > .site-header-cart.menu`.
- ✅ Regla operativa de validación en staging: después de cambios en header/CSS/JS, verificar con querystring única si Rocket/Cloudflare sigue sirviendo HTML viejo desde edge cache antes de concluir que el deploy falló.

### 3.10b Agenda cerrada / lista de espera (2026-04-21)
- ✅ Regla central implementada: cuando la agenda de sesiones está cerrada, los productos de la categoría WooCommerce `sesiones` no se pueden comprar ni agregar al carrito.
- ✅ En ese estado, cualquier CTA relevante de sesiones pasa a la **lista de espera** en vez de checkout/carrito/agendado.
- ✅ La URL de espera activa es: `https://docs.google.com/forms/d/e/1FAIpQLSez3rvnIR6LBL0oPVyHq1yBa6xXNt8nMGj3a87SbpNYuqVVzw/viewform`
- ✅ La regla se aplica de forma centralizada en:
  - cards/grids de producto,
  - single editorial `dm_servicio`,
  - loop nativo WooCommerce,
  - single nativo de producto,
  - intentos directos de `?add-to-cart=`.
- ✅ Administración para cliente/terapeuta: `WP Admin > Ajustes > Generales`.
  - Campo: **Agenda de sesiones abierta**.
  - Campo: **URL lista de espera**.
- ✅ Procedimiento para reabrir agenda:
  1. entrar a `Ajustes > Generales`;
  2. activar **Agenda de sesiones abierta**;
  3. guardar cambios.
- ✅ Procedimiento para volver a cerrar agenda:
  1. desactivar **Agenda de sesiones abierta**;
  2. confirmar que la URL de lista de espera siga correcta;
  3. guardar cambios.

### 3.11 Home “¿Qué necesitas?” — stretch/alineación interna (2026-04-20)
- ✅ `.dm-necesitas__left` ya estira a la misma altura del contenedor padre usando layout flex con `align-items: stretch` en la grilla y `height: 100%`/`min-height` consistentes.
- ✅ `.dm-necesitas__copy` ahora ocupa verticalmente su columna (`flex: 1 1 auto`, `align-self: stretch`, `height: 100%`).
- ✅ El contenido interno de `.dm-necesitas__copy` se distribuye con `justify-content: space-between`, de modo que los bloques queden espaciados de forma pareja dentro del panel izquierdo.
- ✅ Ajuste verificado en staging.
- ✅ Regla de interacción visual: `dm-carousel` hereda el hover premium de `.dm-card` para mantener consistencia de catálogo/Home. El contenedor aplica `lift + shadow` y la imagen hero interna usa zoom suave (`scale(1.03)`).
- ✅ Fuente única de esta interacción: `assets/css/home-necesitas.css`.

### 3.12 Reglas de imágenes por contenedor (2026-04-20)
- ✅ Regla operativa: no reutilizar una sola imagen para todos los contextos; cada bloque tiene su proporción ideal.
- ✅ `dm-card__thumb` y hero del carousel Home: imagen horizontal en `16:9`.
- ✅ `dm-single__thumbnail` / `dm-single__thumbnail--inline`: ideal imagen propia vertical del single; si falta, el sistema prioriza imagen destacada del CPT y luego imagen del producto como fallback seguro.
- ✅ `dm-editorial__title-media--section`: caja fija `462x100`; la imagen interna entra completa con `object-fit: contain`.
- ✅ Recomendación para cliente:
  - Catálogo/card: `1600x900 px` o `1280x720 px`.
  - Single/hero: `1200x1500 px` o proporción vertical cercana a `4:5`.
  - Título de sección editorial: `462x100 px` o `924x200 px` para retina.
- ✅ Buenas prácticas de carga:
  - evitar usar la misma imagen horizontal de catálogo como hero principal del single;
  - dejar aire interno si la imagen lleva texto o logo;
  - usar `jpg` para fotos y `png` solo cuando haga falta transparencia;
  - mantener pesos moderados (idealmente `<300 KB` para títulos y `<500 KB` para imágenes grandes).

---

## 4) Backlog inmediato 🔲

### Prioridad alta
- [ ] **Sanitizar excerpt en el grid** (`dm_cpt_render_grid` en `inc/helpers-cpt.php`)  
  Algunos excerpts traen HTML/CTAs antiguos. Usar `wp_strip_all_tags()` antes de `wp_trim_words()`.

- [ ] **Promoción de cambios DB-backed de WooCommerce a producción**  
  En staging ya hubo ajustes vía WP Admin / runtime sobre descargas y emails de WooCommerce. Antes de promover a producción, aplicar esos cambios de forma controlada y no copiando staging a ciegas.
  Referencia operativa: `docs/db-promotions/2026-04-21-woocommerce-staging-db-snapshot.md`
  Incluye:
  1. opciones WooCommerce de descargas/email validadas en staging;
  2. directorios aprobados de descarga que deben recrearse con host/path de producción;
  3. lista explícita de qué NO promover (pedidos/permisos de staging, URLs de staging, metadatos temporales).

- [ ] **Saneamiento canónico de Tutor/Tutor Pro (post-incidente)**  
  Mantener Tutor funcionando como está por ahora, pero planificar ventana técnica para:
  1. reemplazar `tutor` y `tutor-pro` por paquetes oficiales/verificados,
  2. comparar checksums/archivos locales modificados,
  3. retirar cualquier parche temporal de bootstrap si ya no es necesario,
  4. validar QA completo (front, admin, checkout, cursos, lecciones, quizzes).

### Prioridad media
- [ ] **QA final de navegación principal en staging (desktop + mobile)**  
  Confirmar después de la iteración de jerarquía que:
  - `Recursos` siga sin dropdown.
  - `Escuela` abra `Cursos / Talleres / Programas` por hover/tap.
  - `Servicios` abra `Sesiones / Paquetes / Membresías / Supervisiones` por hover/tap.
  - `Sobre Mi` agrupe `Blog / Newsletter` como hijos sin duplicarlos arriba.
  - El drawer/menu mobile conserve el mismo árbol sin items repetidos.

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

## Pendiente técnico
- Evaluar eliminación de `[dm_recursos]` si ya no existe ninguna página que lo use.
- Antes de borrarlo:
  - confirmar en WP Admin si alguna página sigue insertando el shortcode
  - validar que `/recursos/` ya depende solo del archive editorial + `?tema=`
  - si no hay uso real, quitar shortcode, registro de assets asociado y documentación residual

## Pendiente técnico
WooCommerce freebies — estabilizar envío automático del email de descarga
Estado actual en staging: el pedido completado sí genera links válidos y el correo puede enviarse manualmente, pero el flujo automático todavía no está confirmado como confiable. En la prueba del pedido 9397, el correo recibido coincidió con el reenvío manual de las 16:07:45, así que falta aislar y corregir la capa de delivery/disparo automático.
  - Pendiente:
    - distinguir con trazabilidad si el email llegó por trigger automático o por reenvío manual;
    - revisar integración SMTP/MailerSend en staging;
    - validar con un pedido nuevo sin intervención manual;
    - solo después, promover la solución a producción.