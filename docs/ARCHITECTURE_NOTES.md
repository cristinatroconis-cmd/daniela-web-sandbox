# Architecture Notes — Daniela Montes Psicóloga (Sandbox)

**Última actualización:** 2026-04-21  
Este documento complementa `ARCHITECTURE.md` (no lo reemplaza).  
Aquí queda el "qué está implementado", el "por qué" de las decisiones y el backlog inmediato.

---

## 1) Decisión base (confirmada e implementada)
- **Tutor LMS tiene cursos activos** → se mantiene como motor de contenido.
- **WooCommerce** = motor de compra (checkout, productos, categorías).
- **CPTs `dm_escuela`, `dm_recurso`, `dm_servicio`** = capa editorial/UX/SEO.
- Integración Tutor = **solo linkout** (`_dm_tutor_course_url`); el child theme no llama a APIs de Tutor.

> Si buscas el diccionario oficial de términos, está justo abajo en esta misma nota.

---

## 2) Modelo: Tutor + CPT (capa editorial) + WooCommerce (motor compra)

### Qué es cada capa
| Capa | Motor | Responsabilidad |
|---|---|---|
| Tutor LMS | Tutor | Contenido académico (lecciones, progreso, certificados) |
| CPTs (`dm_escuela`, `dm_recurso`, `dm_servicio`) | WordPress nativo | Landing editorial, SEO, UX, CTAs, chips de filtro |
| WooCommerce | WooCommerce | Pagos, checkout, productos, categorías (fuente de verdad clasificación) |
| Memberships/Subscriptions | Woo plugins | Gating comercial (no se duplica en el child theme) |

### Diccionario oficial
| Término | Definición oficial |
|---|---|
| Tema | Concepto de negocio/editorial como ansiedad, autoestima o relaciones. |
| `product_tag` | Fuente de verdad primaria para clasificar productos Woo por tema. |
| `dm_tema` | Espejo editorial sincronizado para CPTs; no se usa como fuente primaria de navegación pública. |
| Chip | Componente visual clicable que representa un filtro o acceso rápido. |
| Hub | Pantalla de entrada o navegación; no implica por sí sola un listado de resultados. |
| Archive/listado | Pantalla que muestra resultados filtrados o agrupados. |
| Producto | Objeto comercial WooCommerce que compra, descarga o agenda el usuario. |
| CPT editorial | Pieza SEO/UX/contenido (`dm_recurso`, `dm_escuela`, `dm_servicio`) vinculada opcionalmente a un producto. |

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
| `wp-content/themes/daniela-child/archive-dm_servicio.php` | Template archive `/servicios/` con chips WooCommerce (Ruta A, estricto) |
| `wp-content/themes/daniela-child/single-dm_escuela.php` | Template single `/escuela/<slug>/` |
| `wp-content/themes/daniela-child/single-dm_recurso.php` | Template single `/recursos/<slug>/` |
| `wp-content/themes/daniela-child/single-dm_servicio.php` | Template single `/servicios/<slug>/` |
| `wp-content/themes/daniela-child/functions.php` | Bootstrap del child theme; carga `inc/cpt.php` e `inc/helpers-cpt.php` |
| `wp-content/themes/daniela-child/inc/email-tokens.php` | Extrae tokens CSS `--dm-*` de `style.css` `:root {}` para emails |
| `wp-content/themes/daniela-child/inc/woocommerce-emails.php` | Personalización de emails WooCommerce (CSS, asuntos, CTA de descarga) |
| `wp-content/themes/daniela-child/inc/cart-drawer.php` | Drawer del carrito; elimina botones WC nativos vía `wp_loaded` |

### CPTs registrados

| CPT | URL archive | Filtro chips |
|---|---|---|
| `dm_escuela` | `/escuela/` | Categorías WooCommerce: cursos / talleres / programas (Ruta A) |
| `dm_recurso` | `/recursos/` | Taxonomía `dm_tema` (temas transversales) — sin categorizar por precio |
| `dm_servicio` | `/servicios/` | Categorías WooCommerce: sesiones / paquetes / membresias / supervisiones (Ruta A, **estricto**) |
| `dm_temas` | `/temas/` | Agrupa productos por `product_tag` |

### Metaboxes implementados

| Meta key | CPT | Comportamiento |
|---|---|---|
| `_dm_wc_product_id` | `dm_recurso`, `dm_escuela`, `dm_servicio` | Vincula al producto WC; el CTA usa `add_to_cart_url()` + precio |
| `_dm_tutor_course_url` | `dm_escuela` | Puente técnico hacia Tutor para productos de Escuela; no gobierna el CTA público "Ver detalles" del catálogo |

### Comportamiento del grid `/escuela/` (`dm_cpt_render_grid`)
- Imagen, título y CTA **"Ver detalles"** apuntan siempre al single editorial propio.
- La tarjeta muestra el precio del producto vinculado cuando aplica y los temas del producto como texto informativo no clickeable.
- El CTA comercial es **"Agregar al carrito"** cuando existe `_dm_wc_product_id` y el producto es comprable.
- `_dm_tutor_course_url` sigue existiendo como puente técnico para la capa Escuela/Tutor, pero no debe dirigir el CTA público del catálogo.

### Filtro/chips de `/escuela/` — Ruta A
- Función: `dm_escuela_render_woo_chips()` en `inc/helpers-cpt.php`.
- Usa **categorías de producto WooCommerce** (`product_cat`: cursos/talleres/programas) como fuente de filtro.
- **Por qué Ruta A (no taxonomía interna):** evita duplicar la clasificación entre `dm_tipo_escuela` y `product_cat` de WooCommerce. Una sola fuente de verdad para "qué tipo de formación es".
- Filtrado PHP: `dm_escuela_query_args_by_woo_cat()` resuelve qué posts `dm_escuela` pertenecen a la categoría WC seleccionada cruzando `_dm_wc_product_id` + `has_term()`.

---

### 3.8 Personalización de emails (`inc/email-tokens.php` + `inc/woocommerce-emails.php`)

- ✅ Tokens de diseño extraídos automáticamente de `style.css` `:root {}` (transient 12 h).
- ✅ CSS email-safe (filtro `woocommerce_email_styles`, priority 20).
- ✅ Asunto y heading personalizados para emails Processing y Completed.
- ✅ Bloque CTA de descarga directa (guest-friendly) inyectado en `woocommerce_email_after_order_table`.
- **Referencia:** `ARCHITECTURE.md` §21.

### 3.9 Cart Drawer (`inc/cart-drawer.php`)

- ✅ Botones WooCommerce nativos del mini-cart eliminados vía `wp_loaded` (cubre peticiones AJAX de fragmentos).
- ✅ CSS safety-net: `#dm-cart-drawer .woocommerce-mini-cart__buttons { display: none !important; }`.
- ✅ CTAs unificados: **"Seguir comprando"** (cierra drawer, permanece en página actual) + **"Finalizar compra"** (checkout).
- **Referencia:** `ARCHITECTURE.md` §22.

### 3.9b Reglas CTA aprobadas (2026-04-16) — siguiente ajuste UX

- **Catálogo:** CTA principal = **"Agregar al carrito"**; CTA secundario = **"Ver detalles"**.
- **Freebies en catálogo:** mantener badge **"Gratis"** y no modificar el copy del botón; la explicación de "sin pago" vive en single/drawer/checkout.
- **Single editorial:** CTA principal = **"Agregar al carrito"**; CTA secundario = solo regreso contextual tipo **"Volver a categoría / subcategoría / tag"**.
- **Copy público precompra:** no usar **"Ver curso"**; ese acceso externo queda reservado al momento posterior a la compra.
- **Producto ya en carrito:** el clic debe dejar al usuario en la misma página, reabrir el drawer y mostrar un notice breve tipo **"Ya está en tu carrito"**.
- **Duplicados:** bloqueados para todos los productos en este stage.
- **Guard UX:** no debe aparecer **"Ver carrito"** pegado al CTA del bloque ni mostrarse errores/redirects raros de WooCommerce.
- **Estado no disponible / no comprable:** el CTA desaparece.

### 3.10 WooCommerce front-end / checkout polish (2026-04-10)

- ✅ `assets/css/woocommerce.css` hereda tokens del child theme y reutiliza `--dm-necesitas-pad-y` para dar continuidad visual entre Home y WooCommerce.
- ✅ Botones, formularios, tarjetas y notices de WooCommerce ya usan el lenguaje visual de `style.css`.
- ✅ `inc/woocommerce-checkout.php` fuerza al español los strings visibles más comunes (`Checkout`, `Place order`, `Billing details`, etc.) vía `gettext`.
- ✅ `inc/newsletter-optin.php` renderiza el opt-in GDPR en checkout con guard anti-duplicado (`static $rendered`).
- ✅ Checkout / carrito / mi cuenta usan padding interno consistente mediante `--dm-woo-box-pad`.

### 3.11 Navegación principal en staging (2026-04-20)

- ✅ El menú principal visible se confirmó como **DB-driven**: depende del menú de WordPress asignado a `primary`, no de un template hardcodeado del child theme.
- ✅ La limpieza en staging eliminó únicamente dos registros duplicados del menú y luego se verificó el header en frontend.
- ✅ Producción no fue tocada en esta iteración.
- ✅ El item `nav-menu-item-9366` puede reutilizarse fuera del menú primario, pero debe conservar su destino original resolviéndolo desde el propio `nav_menu_item` y no hardcodeando la URL.
- ✅ Regla de interacción: cualquier trigger de carrito en el header debe abrir siempre el drawer del child theme (`#dm-cart-drawer`); no se admite fallback al drawer nativo de Shoptimizer ni a drawers de plugins.
- ✅ Regla de estilo en `header-4`: el bloque custom `Iniciar sesion` debe resolverse visualmente desde el child theme con selectores específicos del layout real (`body.header-4 #page .col-full-nav .site-header-cart.menu ...`), porque Kirki y el parent reescriben tipografía/color del header.
- ✅ Regla de debugging en staging: si el HTML servido no refleja el deploy pero el archivo remoto sí cambió, comprobar primero edge cache de Rocket/Cloudflare con una querystring única antes de reabrir código.

### 3.11b Gate de agenda / waitlist (2026-04-21)

- ✅ Se implementó un gate central para productos de sesiones usando la categoría WooCommerce `product_cat=sesiones` como fuente de verdad inicial.
- ✅ Si la agenda está cerrada:
  - el producto deja de ser comprable;
  - los CTAs pasan a **Unirme a la lista de espera**;
  - el destino único es la URL configurada de Google Forms;
  - intentos directos de `add-to-cart` se bloquean o redirigen.
- ✅ Si la agenda está abierta:
  - se restaura el flujo normal de compra/agendado sin cambiar templates uno por uno.
- ✅ Los ajustes viven en `WP Admin > Ajustes > Generales` para evitar cambios de código cuando la terapeuta abra o cierre agenda.
- ✅ La implementación se apoya en un módulo central del child theme y no en parches aislados por template.

#### Estado visible validado tras la limpieza

```text
Inicio
Recursos
Escuela
Servicios
Sobre Mi
Blog
Newsletter
[Acceso]
```

#### Dirección aprobada para la siguiente jerarquía del menú

- `Recursos` queda sin hijos por ahora.
- `Escuela` debe desplegar `Cursos`, `Talleres` y `Programas` usando las URLs públicas con `?tipo=`.
- `Servicios` debe desplegar `Sesiones`, `Paquetes`, `Membresías` y `Supervisiones` usando las URLs públicas con `?tipo=`.
- `Sobre Mi` debe agrupar `Blog` y `Newsletter` como hijos.

### 3.12 Home “¿Qué necesitas?” — regla de stretch vertical (2026-04-20)

- ✅ El panel izquierdo debe estirarse a la altura completa del contenedor de sección; la grilla usa `align-items: stretch` y la columna izquierda conserva `min-height`/`height: 100%`.
- ✅ `.dm-necesitas__copy` es el contenedor elástico interno del panel izquierdo: usa `flex: 1 1 auto`, `align-self: stretch` y `height: 100%`.
- ✅ La distribución vertical de sus hijos se resuelve con `justify-content: space-between`; esto evita que el copy quede “pegado” arriba cuando el panel izquierdo tiene más altura disponible.
- ✅ La fuente única de estos estilos sigue siendo `assets/css/home-necesitas.css`.
- ✅ El `dm-carousel` de esta sección comparte el mismo patrón de hover premium que `.dm-card`: elevación del contenedor (`box-shadow` + `translateY`) y zoom suave de la imagen hero.
- ✅ La implementación de ese hover también vive en `assets/css/home-necesitas.css`; no duplicarlo en `style.css`.

### 3.13 Regla de imágenes por contexto (2026-04-20)

- ✅ El sistema ya no asume que una sola imagen sirve para card, single y títulos editoriales.
- ✅ Cada contenedor tiene una proporción objetivo y una expectativa de asset distinta.

#### Contrato por contenedor

- `dm-card__thumb` y hero del carousel Home:
  - objetivo visual: imagen horizontal.
  - proporción recomendada: `16:9`.
  - comportamiento CSS: la caja manda el ratio; la imagen rellena el área de catálogo.

- `dm-single__thumbnail` / `dm-single__thumbnail--inline`:
  - objetivo visual: hero editorial del single, preferentemente vertical.
  - proporción recomendada: vertical cercana a `4:5`.
  - prioridad de fuente: `single hero meta` → `featured image del CPT` → `imagen del producto`.
  - si el single cae a la imagen del producto (pensada para catálogo), el render aplica fallback seguro para evitar recortes agresivos dentro de una caja distinta.

- `dm-editorial__title-media--section`:
  - caja fija: `462x100`.
  - la imagen interior debe verse completa.
  - comportamiento CSS: `object-fit: contain`, centrada, sin recorte.

#### Regla práctica para cliente

- Catálogo/card: subir imagen horizontal `1600x900 px` o `1280x720 px`.
- Single/hero: subir imagen propia vertical `1200x1500 px` o similar.
- Título de sección editorial: exportar `462x100 px` o `924x200 px` para pantallas retina.

#### Buenas prácticas operativas

- No reutilizar por defecto la misma imagen horizontal del catálogo como hero principal del single.
- Si la imagen lleva texto, lettering o logo, dejar aire interno y no pegar contenido a los bordes.
- Usar `jpg` para fotografía y `png` solo cuando se necesite transparencia.
- Evitar assets extremos (panorámicas muy largas o verticales demasiado angostas).
- Mantener archivos livianos cuando sea posible para no penalizar la Home ni los singles.

---

## 3b) Servicios (`/servicios/`) — Filtro por Woo Categories (Ruta A, estricto)

### Estructura de categorías WooCommerce
```
servicios              ← padre (product_cat slug: "servicios")
├── sesiones           ← hija
├── paquetes           ← hija
├── membresias         ← hija
└── supervisiones      ← hija
```

### Modo estricto (Ruta A)
- El archive `/servicios/` muestra **únicamente** ítems `dm_servicio` cuyo producto vinculado (`_dm_wc_product_id`) esté categorizado dentro del árbol `servicios/*` en WooCommerce.
- Si el producto vinculado no pertenece a `product_cat servicios` (ni ninguna hija), el ítem **no aparece**, aunque esté publicado.
- Cuando el usuario selecciona un chip hijo (ej. `?tipo=sesiones`), además exige que el producto esté en esa subcategoría exacta.

### Archivos relacionados
| Archivo | Función |
|---|---|
| `wp-content/themes/daniela-child/archive-dm_servicio.php` | Template archive `/servicios/`; llama a `dm_servicios_render_woo_chips()` y `dm_servicios_query_args_by_woo_cat_strict()` |
| `wp-content/themes/daniela-child/inc/helpers-cpt.php` | `dm_servicios_render_woo_chips()` + `dm_servicios_query_args_by_woo_cat_strict()` |
| `wp-content/themes/daniela-child/inc/cpt.php` | Registra el CPT `dm_servicio` y la taxonomía `dm_tipo_servicio` (legacy, ver nota abajo) |

### Nota: `dm_tipo_servicio` es **LEGACY**
- La taxonomía interna `dm_tipo_servicio` (términos: `sesiones`, `membresias`) sigue registrada en `inc/cpt.php` para no romper datos históricos.
- **No se usa para chips ni UX en `/servicios/`**: la clasificación la manda WooCommerce `product_cat`.
- No asignar términos de `dm_tipo_servicio` a nuevos posts; usar categorías WooCommerce bajo `servicios/*`.

### Chips renderizados en `/servicios/`
```
Todos | Sesiones | Paquetes | Membresías | Supervisiones
```
- Querystring: `?tipo=<slug>` (ej. `/servicios/?tipo=sesiones`)
- Chip "Todos": muestra todo lo que esté en `servicios/*` (sin filtro de subcategoría).

---

## 3c) Patrón recurrente: catálogo uniforme (cards + grids) ✅ DECISIÓN CERRADA

> **Contexto:** decisión de producto tomada explícitamente para mantener bajo el costo de mantenimiento en un proyecto low budget.

### El patrón

El sitio usa **un solo sistema visual** de cards + grids para todos los catálogos, sin variantes por sección ni por CPT:

- **Archives CPT** (`/escuela/`, `/recursos/`, `/servicios/`) → clases `.dm-grid` + `.dm-card`, renderizadas por `dm_cpt_render_grid()` en `inc/helpers-cpt.php`.
- **Grids de producto WooCommerce** (shortcodes, páginas) → clase `.dm-products-grid`, gestionada en `inc/dm-products.php`.
- **Estilos** → todo en `style.css` (layout y visuales). Los renderers PHP solo emiten HTML semántico; no llevan CSS inline ni valores de layout.

### Por qué este diseño

- **Consistencia visual → mejor conversión:** el usuario no tiene que reaprender la interfaz entre secciones.
- **Mantenimiento reducido:** un cambio en el grid o en la tarjeta aplica a todas las secciones simultáneamente.
- **Bajo presupuesto:** no hay ROI en mantener grids diferenciados por sección.

### Actualización 2026-03-31 — Thumb unificado + Hero "¿Qué necesitas?"

#### Decisión: proporción 16:9 unificada para todas las card-thumbs
- Las card-thumbs de todos los catálogos (CPT grids + Woo/producto/recurso cards) usan ahora **`aspect-ratio: 16/9`** (variable `--dm-card-thumb-ratio`), alineado con el hero del carousel de la Home.
- `object-fit: cover` + `overflow: hidden` en el wrapper garantizan recorte homogéneo sin deformación, independientemente del tamaño original de la imagen.
- Hover: escala suave (`transform: scale(...)` + `transition`) aplicada sobre la imagen interior.

#### Contrato de markup para thumbs (referencia rápida)

| Familia de card | Wrapper (ratio + overflow) | Elemento imagen |
|---|---|---|
| `.dm-card` (CPT grids: escuela, servicios…) | `a.dm-card__image-link` | `div.dm-card__thumb > img` (de `get_the_post_thumbnail()`) |
| `.dm-topic-card` (grids temáticos de productos) | `a.dm-topic-card__thumb-link` | `img.dm-topic-card__thumb` |
| `.dm-product-card` (grids de producto Woo) | `a.dm-product-card__thumb-link` | `img.dm-product-card__thumb` |

- Para `.dm-card`, el ratio 16:9 se aplica sobre `a.dm-card__image-link`; el `div.dm-card__thumb` interior hereda `width/height: 100%`; el `<img>` (clase WordPress `wp-post-image`) se fuerza a `width/height: 100%` con `object-fit: cover`.
- Para los dos casos Woo (`__thumb-link` / `__thumb`), el ratio y `overflow: hidden` van en el link wrapper; la imagen `<img>` rellena el box.
- El sistema CSS en `style.css` trata los tres casos de forma consistente mediante selectores agrupados.

#### Carousel Home "¿Qué necesitas?" (sección `section-necesitas`)
- El hero de cada ítem del carousel usa **`aspect-ratio` fijo + `object-fit: cover`** (mismo principio que las card-thumbs).
- Hover con escala (`transform: scale`) implementado en `assets/css/home-necesitas.css`.
- Template: `template-parts/home/section-necesitas.php`.

### Checklist para cambios futuros

Antes de tocar cualquier grid o tarjeta, responde estas preguntas:

- [ ] **¿Estás por crear otro grid?** → Para. Reutiliza `.dm-grid` (CPT) o `.dm-products-grid` (Woo). Si realmente necesitas algo distinto, documenta el motivo aquí antes de escribir código.
- [ ] **¿Estás por cambiar el layout (columnas, espaciado)?** → Edita solo `style.css`. Verifica que el cambio no rompe las 3 secciones de archive (escuela, recursos, servicios).
- [ ] **¿Estás por agregar un CTA nuevo?** → Actualiza `dm_cpt_render_cta()` en `inc/helpers-cpt.php`. No pongas HTML de botón directamente en un template.
- [ ] **¿Estás por agregar un nuevo CPT?** → Llama a `dm_cpt_render_grid()` en el template archive. No copies el loop de tarjetas.
- [ ] **¿El texto del CTA secundario es neutro?** → Usa "Ver detalles" por defecto en catálogo. No usar "Ver curso" como CTA público precompra.
- [ ] **¿Actualizaste `ARCHITECTURE.md` sección 18?** → Si tomaste una nueva decisión sobre el sistema de UI, documéntala ahí.

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

### 4.2 Menú principal — QA de jerarquía hover/tap
- **Pendiente:** validar visualmente en staging la jerarquía final del menú después de la iteración en WP Admin.
  - `Recursos` sin hijos.
  - `Escuela` → Cursos / Talleres / Programas.
  - `Servicios` → Sesiones / Paquetes / Membresías / Supervisiones.
  - `Sobre Mi` → Blog / Newsletter.
- **Cómo:** revisar en desktop (hover) y mobile (tap/drawer) que no reaparezcan duplicados ni queden hijos fuera del árbol correcto.
- **URLs públicas a usar:**
  - `/escuela/?tipo=cursos`, `/escuela/?tipo=talleres`, `/escuela/?tipo=programas`
  - `/servicios/?tipo=sesiones`, `/servicios/?tipo=paquetes`, `/servicios/?tipo=membresias`, `/servicios/?tipo=supervisiones`

### 4.3 Optimización checkout
- **Ya implementado:** capa visual base, traducción al español y checkbox de newsletter en checkout.
- **Pendiente:** revisar scripts que cargan en `/checkout/` (Elementor, Slider Revolution, etc.) para reducir peso/performance.
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
- [ ] Imagen, título y "Ver detalles" enlazan al single editorial propio.
- [ ] Si el producto vinculado tiene temas (`product_tag`), se muestran como texto informativo no clickeable.
- [ ] Botón "Agregar al carrito" añade el producto al carrito WooCommerce.
- [ ] Si no hay `_dm_wc_product_id`: no aparece el botón de carrito.
- [ ] La presencia de `_dm_tutor_course_url` no cambia el CTA público del catálogo.

### Single `/escuela/<slug>/`
- [ ] La página carga sin errores PHP.
- [ ] Se muestra imagen destacada, título, tipo (chip), contenido y CTA WooCommerce.
- [ ] El botón "Volver a Escuela" enlaza correctamente al archive.
- [ ] Si no hay `_dm_wc_product_id`: no aparece el CTA (sin sección vacía).

### Archive `/recursos/`
- [ ] La página carga con chips "Todos" + los temas `dm_tema` que existan asignados a recursos.
- [ ] Filtro por tema funciona con querystring `?tema=<slug>` (ej. `/recursos/?tema=ansiedad`).
- [ ] Las tarjetas muestran CTA correcto según precio del producto vinculado.
- [ ] Excerpt de tarjeta no contiene HTML (limpio, sin botones ni markup).

### Archive `/servicios/`
- [ ] La página carga sin errores PHP.
- [ ] Los chips "Todos / Sesiones / Paquetes / Membresías / Supervisiones" se muestran.
- [ ] Chip "Todos" activo por defecto; muestra solo ítems cuyo producto esté en `servicios/*`.
- [ ] Al hacer click en "Sesiones", la URL cambia a `/servicios/?tipo=sesiones` y el grid filtra.
- [ ] Chip activo tiene clase `dm-chip--active` y `aria-current="true"`.
- [ ] Si un ítem `dm_servicio` no tiene producto vinculado en `servicios/*`, no aparece en el archive.
- [ ] Probar: `/servicios/?tipo=sesiones`, `/servicios/?tipo=paquetes`, `/servicios/?tipo=membresias`, `/servicios/?tipo=supervisiones`.

### Metabox en WP Admin
- [ ] Al editar un `dm_escuela`, aparece el metabox "Producto WooCommerce relacionado".
- [ ] Al editar un `dm_escuela`, aparece el metabox "Curso Tutor (URL)".
- [ ] Guardar y recargar conserva los valores correctamente.
- [ ] Dejar vacío el campo borra el meta (no guarda `0` ni cadena vacía).
