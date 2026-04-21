# Arquitectura del Producto Digital
Proyecto: Daniela Montes Psicóloga

Este documento define la arquitectura del negocio digital dentro del sitio web.

El objetivo no es solo tener una web informativa, sino una **plataforma de recursos psicológicos escalable**.

---

# 1. Modelo de Producto

La oferta se organiza en una **escalera de valor (Value Ladder)**.

Esto permite:

- aumentar conversión
- aumentar ticket promedio
- generar recurrencia
- preparar a los usuarios para procesos más profundos

La estructura es la siguiente:

Nivel 1 — Recursos
Nivel 2 — Escuela (Cursos · Talleres · Programas)
Nivel 3 — Servicios (Sesiones · Paquetes · Membresías · Supervisiones)

---

# 2. Tipos de Producto

Los productos se agrupan en los tres niveles del modelo:

---

## 2.1 Nivel 1 — Recursos

### 2.1.1 Recursos gratuitos

Tipo de producto:
WooCommerce Simple Product (descargable, precio $0)

Formato:

- PDF descargable gratuito
- ejercicios terapéuticos de entrada
- guías prácticas básicas

Objetivo:

- captación de nuevos usuarios
- primer contacto con el ecosistema

### 2.1.2 Recursos de pago

Tipo de producto:
WooCommerce Simple Product (descargable, precio > $0)

Formato:

- PDF descargable premium
- guías terapéuticas avanzadas
- paquetes de ejercicios

Objetivo:

- entrada económica al ecosistema

---

## 2.2 Nivel 2 — Escuela

### 2.2.1 Cursos

Tipo:

- WooCommerce product
- vinculado a Tutor LMS

Contenido:

- módulos
- videos
- ejercicios
- material descargable

Objetivo:

profundización individual.

### 2.2.2 Talleres

Tipo:
producto WooCommerce

Formato:

- evento en vivo
- online

Incluye:

- sesión grupal
- material adicional

Objetivo:

experiencia en comunidad.

### 2.2.3 Programas

Producto premium.

Formato:

- proceso terapéutico estructurado
- varias sesiones
- material adicional
- posible acceso a curso

Objetivo:

transformación profunda.

---

## 2.3 Nivel 3 — Servicios

### 2.3.1 Sesiones individuales

Servicio profesional de consulta individual.

Objetivo:

intervención directa y personalizada.

### 2.3.2 Paquetes

Conjuntos de sesiones a precio especial.

Objetivo:

compromiso de continuidad a mejor valor.

### 2.3.3 Membresías

Acceso recurrente a contenido y/o servicios.

Objetivo:

recurrencia y acompañamiento continuo.

### 2.3.4 Supervisiones

Servicio de supervisión profesional (para profesionales de la psicología).

Objetivo:

soporte y formación continua para psicólogos y terapeutas.

Debe mostrarse como:

último nivel de intervención / servicio de mayor profundidad.

---

# 3. Estructura WooCommerce recomendada

Categorías principales y subcategorías:

```
Recursos

Escuela
├── cursos
├── talleres
└── programas

Servicios
├── sesiones
├── paquetes
├── membresias
└── supervisiones
```

Cada producto debe pertenecer a una de estas categorías (padre o hija según corresponda).

---

# 4. Navegación principal

La navegación debe reflejar el modelo de producto.

Ejemplo:
```
Recursos
Escuela
  ├── Cursos       → /escuela/?tipo=cursos
  ├── Talleres     → /escuela/?tipo=talleres
  └── Programas    → /escuela/?tipo=programas
Servicios
  ├── Sesiones     → /servicios/?tipo=sesiones
  ├── Paquetes     → /servicios/?tipo=paquetes
  ├── Membresías   → /servicios/?tipo=membresias
  └── Supervisiones → /servicios/?tipo=supervisiones
Sobre Dani
```

Evitar navegación confusa.

El usuario debe entender rápidamente qué comprar.

---

# 5. Arquitectura de la HOME

La home no debe funcionar como blog.

Debe funcionar como **página de orientación de producto**.

Estructura implementada:
- Meet Dani
- ¿Qué necesitas?
- Reviews
- Newsletter

La sección “¿Qué necesitas?” vive como bloque propio del child theme y se apoya en:
- `assets/css/home-necesitas.css`
- `assets/js/home-necesitas-carousel.js`

---

# 6. Sección clave: ¿Qué necesitas?

Esta sección funciona como **sistema de orientación del usuario**.

El visitante puede dirigirse rápidamente a:

- Recursos
- Escuela (Cursos / Talleres / Programas)
- Servicios (Sesiones / Paquetes / Membresías / Supervisiones)

Además, sus tokens de espaciado (`--dm-necesitas-pad-y`) se reutilizan en WooCommerce para mantener continuidad visual entre Home y checkout.

Esto reduce:

- confusión
- abandono

Y mejora:

- conversión.

---

# 7. Funnel de usuario

El flujo ideal es:
Usuario nuevo

↓

Descubre contenido

↓

Compra recurso (PDF gratuito o de pago)

↓

Compra en Escuela (curso / taller / programa)

↓

Contrata Servicio (sesión / paquete / membresía / supervisión)


Este es el modelo de crecimiento del proyecto.

---

# 8. Automatización futura

El sistema debería evolucionar hacia:

- email automation
- recomendaciones de producto
- bundles
- membresía

Herramientas posibles:

MailerLite  
WooCommerce Membership  
WooCommerce Subscriptions

---

# 9. Principios UX del proyecto

1. Claridad
2. Simplicidad
3. Jerarquía visual
4. Conversión

Evitar:

- exceso de información
- navegación compleja
- páginas saturadas.

---

# 10. Objetivo final

Convertir el sitio en una **escuela de recursos psicológicos online**, donde el usuario pueda avanzar progresivamente en su proceso personal.

---

# 11. Documentación operativa (sandbox)
Para evitar perder contexto (por ejemplo, chats que fallan), este repo mantiene:
- `docs/project_status.md` — estado actual del proyecto y backlog inmediato
- `docs/ARCHITECTURE_NOTES.md` — decisiones, estado implementado y checklist de pruebas

# 12. Escuela: Tutor LMS + capa editorial con CPT
- **Tutor LMS** se mantiene como motor de cursos (hay cursos activos).
- La navegación/SEO/UX de la escuela se apoya en CPTs (catálogo editorial) para:
  - mejor jerarquía de oferta
  - CTAs consistentes
  - escalabilidad sin depender de páginas sueltas o solo categorías Woo

Regla: definir una fuente de verdad para el acceso (Tutor vs Memberships/Subs) para evitar doble gating.

---

# 13. Arquitectura técnica implementada

## 13.1 Custom Post Types (CPTs)

Registrados en `wp-content/themes/daniela-child/inc/cpt.php`.

| CPT | Slug URL | Admin label |
|---|---|---|
| `dm_recurso` | `/recursos/` | Recursos CPT |
| `dm_escuela` | `/escuela/` | Escuela CPT |
| `dm_servicio` | `/servicios/` | Servicios CPT |

Todos soportan: `title`, `editor`, `thumbnail`, `excerpt`, `revisions`. REST habilitado.

### Taxonomías internas

| Taxonomía | CPT | Términos predefinidos |
|---|---|---|
| `dm_tipo_recurso` | `dm_recurso` | `gratis`, `pagos` |
| `dm_tipo_escuela` | `dm_escuela` | `cursos`, `talleres`, `programas` |
| `dm_tipo_servicio` | `dm_servicio` | `sesiones`, `membresias` — **LEGACY**: no se usa para chips/UX en `/servicios/`; la clasificación la manda WooCommerce `product_cat` |
| `dm_tema` | los 3 CPTs | _(espejo editorial sincronizado desde `product_tag`; no es la fuente primaria)_ |

Los términos de `dm_tipo_*` se crean automáticamente en el primer `init`.

### Diccionario oficial

| Término | Definición oficial |
|---|---|
| Tema | Concepto de negocio/editorial como ansiedad, autoestima o relaciones. |
| `product_tag` | Fuente de verdad primaria para clasificar productos Woo por tema. |
| `dm_tema` | Espejo editorial sincronizado para CPTs; no gobierna la navegación pública. |
| Chip | Componente visual clicable que representa un filtro o acceso rápido. |
| Hub | Pantalla de entrada o navegación; no implica por sí sola un listado de resultados. |
| Archive/listado | Pantalla que muestra resultados filtrados o agrupados. |
| Producto | Objeto comercial WooCommerce que compra, descarga o agenda el usuario. |
| CPT editorial | Pieza SEO/UX/contenido (`dm_recurso`, `dm_escuela`, `dm_servicio`) vinculada opcionalmente a un producto. |

## 13.2 Templates

Viven en la raíz del tema hijo (convención WordPress):

| Archivo | URL | Función |
|---|---|---|
| `archive-dm_recurso.php` | `/recursos/` | Grid editorial de recursos con chips de tema |
| `single-dm_recurso.php` | `/recursos/<slug>/` | Recurso individual + CTA |
| `archive-dm_escuela.php` | `/escuela/` | Grid de cursos con chips Woo (Ruta A) |
| `single-dm_escuela.php` | `/escuela/<slug>/` | Ítem de escuela individual + CTA |
| `archive-dm_servicio.php` | `/servicios/` | Grid de servicios con chips Woo (`product_cat servicios/*`) — Ruta A (estricto) |
| `single-dm_servicio.php` | `/servicios/<slug>/` | Servicio individual + CTA |

## 13.3 Helpers principales (`inc/helpers-cpt.php`)

| Función | Responsabilidad |
|---|---|
| `dm_cpt_get_linked_product($post_id)` | Devuelve el WC_Product vinculado al CPT (meta `_dm_wc_product_id`) |
| `dm_cpt_render_cta($post_id)` | Renderiza botón "Agregar al carrito" con precio (gratis → botón secundario) |
| `dm_cpt_render_taxonomy_chips($taxonomy, $param, $base_url)` | Chips de filtro por taxonomía interna CPT |
| `dm_cpt_archive_query_args($post_type, $taxonomy, $param)` | WP_Query args con filtro de taxonomía CPT |
| `dm_cpt_render_grid($query)` | Grid de tarjetas CPT (maneja lógica especial para `dm_escuela`) |
| `dm_escuela_render_woo_chips($param, $base_url)` | Chips de filtro /escuela/ usando categorías WooCommerce (Ruta A) |
| `dm_escuela_query_args_by_woo_cat($param)` | WP_Query args filtrando por categoría WC del producto vinculado |
| `dm_servicios_render_woo_chips($param, $base_url)` | Chips de filtro /servicios/ usando categorías WooCommerce hijas de `servicios` (Ruta A, estricto) |
| `dm_servicios_query_args_by_woo_cat_strict($param)` | WP_Query args para `/servicios/`: exige que el producto vinculado esté en `product_cat servicios/*` (modo estricto) |

## 13.4 Metaboxes

Registrados en `inc/helpers-cpt.php`:

| Meta key | CPT | Uso |
|---|---|---|
| `_dm_wc_product_id` | `dm_recurso`, `dm_escuela`, `dm_servicio` | ID del producto WooCommerce relacionado |
| `_dm_tutor_course_url` | `dm_escuela` | Path del curso Tutor (ej. `/courses/tumenteencalma/`) |

---

# 14. Flujo de negocio (funnel)

```
Visitante
    ↓
/escuela/ (archive CPT dm_escuela)
    — chips: Cursos | Talleres | Programas (categorías WooCommerce)
    — grid de tarjetas con imagen + título + excerpt
    ↓
Tarjeta de curso:
    [Ver curso →]  (abre Tutor LMS en nueva pestaña)
    [Agregar al carrito]  (WooCommerce checkout)
    ↓
Si elige "Agregar al carrito":
    → WooCommerce checkout estándar
    → post-compra: usuario obtiene acceso al curso Tutor
Si elige "Ver curso":
    → Va directo a Tutor LMS (linkout, sin gating adicional en ese botón)
```

**Por qué este diseño:**
- Tutor LMS controla el contenido académico (lecciones, progreso, certificados).
- WooCommerce controla el pago y el acceso comercial.
- Los CPTs son la capa editorial/UX: mejor SEO, CTAs claros, chips de navegación.
- Evita duplicar clasificación: los chips de `/escuela/` usan categorías WC (cursos/talleres/programas) para que la fuente de verdad de clasificación sea una sola.

---

# 15. Integraciones

## WooCommerce
- Motor de compra (checkout, pedidos, productos, categorías).
- Categorías de producto (`product_cat`) usadas como fuente de verdad para chips de:
  - `/escuela/` (Ruta A): categorías `cursos`, `talleres`, `programas`.
  - `/servicios/` (Ruta A, **estricto**): árbol `servicios/*` con hijas `sesiones`, `paquetes`, `membresias`, `supervisiones`. Solo aparecen ítems `dm_servicio` cuyo producto vinculado esté dentro de `servicios/*`.
- El helper `dm_cpt_render_cta()` usa `add_to_cart_url()` y `get_price_html()` de WC_Product.
- Si WooCommerce no está activo, los helpers fallan silenciosamente (devuelven cadena vacía).

## Tutor LMS
- Integración de **solo linkout**: el CPT `dm_escuela` almacena el path del curso en el meta `_dm_tutor_course_url`.
- No hay llamadas a la API de Tutor desde el child theme; el tema solo enlaza.
- El botón "Ver curso" abre la URL de Tutor en nueva pestaña (`target="_blank"`).
- Gating de acceso: lo controla Tutor LMS (o Woo Memberships/Subscriptions); el child theme no replica esa lógica.

## Metabox `_dm_tutor_course_url`
- Aparece en el editor de WP Admin al editar un ítem `dm_escuela`.
- Campo: pega el path del curso (ej. `/courses/tumenteencalma/`).
- Si está vacío, el enlace de imagen/título apunta al single CPT en vez del curso Tutor.
- Si está presente, imagen y título de la tarjeta enlazan al curso Tutor.

---

# 16. Entorno local (LocalWP + symlink)

## Variables de entorno (`~/.zshrc`)

```bash
export DM_REPO="/Users/cristinatroconis/Desktop/daniela-web-sandbox"
export DM_WP="/Users/cristinatroconis/Local Sites/dani-backup/app/public"
```

## Setup del symlink

El theme `daniela-child` en LocalWP es un **symlink** al directorio del repo:

```
$DM_WP/wp-content/themes/daniela-child
    → $DM_REPO/wp-content/themes/daniela-child
```

Esto elimina la necesidad de sincronizar (`rsync`) después de cada pull.
El repo es la única fuente de verdad del código del theme.

Verificar que el symlink existe:
```bash
ls -la "$DM_WP/wp-content/themes" | grep daniela-child
```

## Flujo de actualización

### A) Actualizar código desde GitHub
```bash
cd "$DM_REPO"
git checkout main
git pull --no-rebase origin main
```

### B) Ver cambios en LocalWP
No hay paso extra. Gracias al symlink, LocalWP ya usa el código actualizado.
Solo refrescar el navegador (hard reload si hay caché: `Cmd+Shift+R`).

### C) Verificar el symlink sigue activo (si algo no carga)
```bash
ls -la "$DM_WP/wp-content/themes" | grep daniela-child
```

### D) Buscar en el código del theme
```bash
grep -r "dm_escuela" "$DM_REPO/wp-content/themes/daniela-child/"
```

---

# 18. UI System — Catálogo uniforme (cards + grids)

## Decisión de producto

El sitio usa **un solo sistema** de cards + grids para todos los catálogos.  
**No se crean variantes por sección ni por CPT.** Razón: proyecto low budget — el costo/beneficio de mantener variantes específicas no justifica la complejidad adicional. Un sistema único mejora conversión (consistencia visual) y reduce mantenimiento.

## Clases base

| Clase CSS | Descripción |
|---|---|
| `.dm-grid` | Grid de tarjetas CPT (archives de `/escuela/`, `/recursos/`, `/servicios/`) |
| `.dm-card` | Tarjeta individual dentro de `.dm-grid` |
| `.dm-products-grid` | Grid de productos WooCommerce (shortcodes, páginas de producto) |

## Responsabilidades

| Capa | Responsabilidad |
|---|---|
| CSS (`style.css`) | Layout del grid y estilos visuales de las tarjetas. Define columnas, espaciado, breakpoints. |
| PHP — renderers CPT (`inc/helpers-cpt.php`) | Genera el HTML de `.dm-grid` + `.dm-card` para archives CPT. Función principal: `dm_cpt_render_grid()`. |
| PHP — productos (`inc/dm-products.php`) | Genera el HTML de `.dm-products-grid` para listados WooCommerce (shortcodes, grids). |

## Regla de consistencia: layout

- **Desktop (≥ 1024 px):** objetivo 3 columnas.
- **Tablet / mobile:** responsivo (definido en `style.css`; no duplicar breakpoints en PHP).
- **Regla de oro:** si cambias el número de columnas o el espaciado, cambia en `style.css` **en un solo lugar**. No hardcodear valores de layout en PHP ni en plantillas individuales.
- **No crear variantes por CPT:** `/escuela/`, `/recursos/` y `/servicios/` usan exactamente las mismas clases `.dm-grid` y `.dm-card`.

## Regla de consistencia: copy / CTAs

- CTA primario público de compra: **"Agregar al carrito"** (texto unificado en catálogo y singles editoriales, tanto para productos gratis como de pago).
- CTA secundario público de catálogo: **"Ver detalles"**. Este es el único copy secundario permitido en cards/listados.
- En catálogo, los freebies se diferencian visualmente con badge **"Gratis"**; no se recarga el botón con copy extra.
- La aclaración de que un recurso no requiere pago debe vivir como microcopy en single, drawer o checkout, no en el CTA principal de la card.
- En singles editoriales, el CTA secundario **no** es "Ver detalles": debe ser un regreso contextual tipo **"Volver a categoría / subcategoría / tag"**, y usarse con moderación como apoyo de navegación.
- **No usar "Ver curso" como CTA público precompra.** El acceso externo tipo **"Iniciar curso"** solo debe aparecer después de la compra (por ejemplo, en thank-you page, cuenta del usuario o acceso ya autorizado).
- Si el producto no está disponible, no es comprable o no aplica al flujo, el CTA debe desaparecer (sin mostrar botones rotos o estados ambiguos).
- Si el producto ya está en el carrito, el usuario debe permanecer en la misma página, reabrir el drawer y ver un notice breve tipo **"Ya está en tu carrito"**. No se permiten duplicados en este stage.
- No inventar textos nuevos por sección sin actualizar este documento.

## Archivos relevantes

| Archivo | Qué toca |
|---|---|
| `wp-content/themes/daniela-child/style.css` | Estilos de `.dm-grid`, `.dm-card`, `.dm-products-grid`, `.dm-btn`, `.dm-chips` |
| `wp-content/themes/daniela-child/inc/helpers-cpt.php` | Renderer del grid CPT (`dm_cpt_render_grid()`), CTA (`dm_cpt_render_cta()`), chips |
| `wp-content/themes/daniela-child/inc/dm-products.php` | Renderer del grid de productos WooCommerce (`.dm-products-grid`) |

## Guías para cambios futuros

1. **¿Necesitas un nuevo grid?** → Reutiliza `.dm-grid` / `.dm-cards` (CPT) o `.dm-products-grid` (Woo). **No crees una clase nueva de grid.**
2. **¿Quieres cambiar el layout del grid?** → Edita `style.css`. El cambio aplica a todas las secciones automáticamente.
3. **¿Necesitas un CTA diferente?** → Actualiza `dm_cpt_render_cta()` en `inc/helpers-cpt.php`. No dupliques lógica de CTA en los templates PHP.
4. **¿Vas a agregar un nuevo CPT?** → Usa `dm_cpt_render_grid()` como renderer. No escribas un loop de tarjetas ad-hoc.
5. **Evitar duplicar lógica:** toda la lógica de renderizado de tarjetas vive en `inc/helpers-cpt.php`. Los templates archive solo llaman a las funciones helpers; no contienen HTML de tarjetas directamente.

---

# 17. Consideraciones UX

## Evitar CTAs duplicados
- En cards de catálogo, el footer debe mostrar **máximo dos acciones**:
  1. **"Agregar al carrito"** — acción principal.
  2. **"Ver detalles"** — acción secundaria opcional.
- En singles editoriales, la acción principal sigue siendo **"Agregar al carrito"** y la secundaria, cuando exista, debe ser solo un enlace contextual de regreso (**"Volver a ..."**).
- Si el producto ya está en el carrito, **no** debe inyectarse un nuevo botón **"Ver carrito"** junto al CTA del bloque. El clic debe actuar como reentrada limpia al drawer, con notice breve y sin duplicar la compra.
- Los accesos externos tipo **"Iniciar curso"** quedan reservados al contexto post-compra; no forman parte del CTA público previo a la compra.
- Si ningún CTA aplica, el footer de la tarjeta no se renderiza (no aparece vacío).

## Excerpt limpio
- **Problema detectado:** algunos excerpts de posts CPT traen HTML/CTAs de versiones antiguas.
- **Pendiente:** sanitizar el excerpt antes de renderizarlo en el grid.
- Solución planificada: en `dm_cpt_render_grid()`, aplicar `wp_strip_all_tags()` o `wp_trim_words()` con strip_tags al excerpt antes de mostrarlo.
- Afecta al archivo: `wp-content/themes/daniela-child/inc/helpers-cpt.php` → función `dm_cpt_render_grid()`.

## Botones consistentes
- Clase `dm-btn dm-btn--ghost` → "Ver detalles" (acción secundaria / linkout).
- Clase `dm-btn dm-btn--primary` → "Agregar al carrito" (producto de pago).
- Clase `dm-btn dm-btn--secondary` → "Agregar al carrito" (producto gratis).
- No mezclar estilos entre secciones (Recursos, Escuela, Servicios usan el mismo sistema).

## Menú / navegación (backlog)
- Pendiente: subitems hover para Escuela, Recursos, Servicios en el menú principal.
  - Subitem Escuela: Cursos / Talleres / Programas.
  - Subitem Recursos: Por tema (`product_tag` slugs públicos).
  - Subitem Servicios: Sesiones / Paquetes / Membresías / Supervisiones (Woo categories hijas de `servicios`).
- Implementar en WP Admin → Apariencia → Menús (no requiere código, solo configuración).
- URLs a usar para Servicios:
  - `/servicios/?tipo=sesiones`
  - `/servicios/?tipo=paquetes`
  - `/servicios/?tipo=membresias`
  - `/servicios/?tipo=supervisiones`

---

# 19. Importador idempotente de recursos (WP-CLI)

## Comando

```bash
wp dm import-recursos          # Importa nuevos attachments
wp dm import-recursos --dry-run  # Solo simula (no escribe)
wp dm import-recursos --force-update  # Fuerza actualización de existentes
```

## Archivo

`wp-content/themes/daniela-child/inc/cli-import-recursos.php`

Solo se carga cuando `WP_CLI` está definido (sin overhead en peticiones web).

## Lógica

| Condición | Precio |
|---|---|
| Título contiene **gratuito** (case-insensitive) | $0 |
| Familia "Afirmaciones" (bundle) | $9 |
| Cualquier otro | $5 |

- Archivos aceptados: PDF, MP3, M4A.
- Idempotente: detecta importaciones previas por meta `_dm_source_attachment_id`.
- Por cada attachment crea/actualiza:
  - Producto WooCommerce (simple, descargable) en categoría `recursos`.
  - CPT `dm_recurso` con excerpt y contenido.
- Asigna `product_tag` como fuente primaria de tema y sincroniza `dm_tema` como espejo editorial con los mismos slugs derivados de keywords del título.
- Bundles (familia "Afirmaciones"): tag `bundle`, precio $9.

## Metas de trazabilidad

| Meta key | Post type | Valor |
|---|---|---|
| `_dm_source_attachment_id` | `product`, `dm_recurso` | ID del attachment origen |
| `_dm_wc_product_id` | `dm_recurso` | ID del producto WC vinculado |

---

# 20. Descargas de productos

## Resumen: dos flujos completamente separados

| Flujo | Activación | Archivos |
|---|---|---|
| **WooCommerce nativo** | Productos con precio > $0, tras completar el pago con Stripe | Sistema nativo de WC (permisos, límites, expiración) |
| **Freebie tokenizado** | Productos con precio = $0, formulario de email | `inc/freebie-download.php`, `inc/freebie-delivery.php` |

Ninguno de los dos flujos interfiere con el otro.
Ver detalles completos en [`docs/woocommerce-downloads.md`](docs/woocommerce-downloads.md).

---

## 20.1 Productos pagados — WooCommerce nativo

Los productos descargables de pago usan **exclusivamente el sistema nativo de WooCommerce**:

- **Permisos de descarga** gestionados por WooCommerce (tabla `woocommerce_downloadable_product_permissions`).
- **Límite de descargas** y **expiración** configurables en cada producto.
- **Email automático** "Pedido completado" enviado por WooCommerce con link protegido.
- **Método de entrega:** `Force downloads` (por defecto). El archivo nunca se expone directamente en `/wp-content/uploads/`.

El child theme **no sobreescribe** ningún hook `woocommerce_download_*` ni modifica la lógica de entrega de WooCommerce.

---

## 20.2 Freebies por email con link tokenizado

**Solo para productos con precio = $0.**

### Archivo principal

`wp-content/themes/daniela-child/inc/freebie-download.php`

### Tabla de base de datos

`{prefix}dm_freebie_tokens` (creada automáticamente en `init` si no existe):

| Columna | Tipo | Descripción |
|---|---|---|
| `token` | VARCHAR(64) PK | Token hex de 64 chars |
| `email` | VARCHAR(200) | Email del solicitante |
| `product_id` | BIGINT | Producto WC |
| `created_at` | DATETIME | Fecha de creación |
| `expires_at` | DATETIME | Expiración (NULL = sin límite) |
| `download_count` | INT | Descargas realizadas |
| `max_downloads` | INT DEFAULT 10 | Límite de descargas |
| `newsletter_optin` | TINYINT | Consentimiento newsletter |

### Shortcode

```
[dm_freebie_form product_id="123"]
[dm_freebie_form product_id="123" title="Recíbelo gratis" button_text="Enviarme el PDF"]
```

Muestra: campo email + checkbox opt-in newsletter (no pre-marcado, GDPR-compliant).

### Endpoint de descarga

`?dm_freebie_token=<hex64>` en cualquier URL del sitio.

Valida: token existe, no expirado, no superó `max_downloads`, producto tiene precio = $0. Entrega el archivo y actualiza el contador.

### Aislamiento (garantías)

El flujo freebie **no puede ejecutarse para productos de pago**:

- Si `price > 0`, el shortcode muestra un enlace al producto WooCommerce (no el formulario).
- Si `price > 0`, `dm_freebie_process_request()` rechaza la solicitud con `WP_Error`.
- Si `price > 0`, el endpoint `?dm_freebie_token=` devuelve HTTP 403.
- El flujo freebie no modifica emails ni hooks de WooCommerce.

### Integración con single-dm_recurso.php

Para recursos con precio $0 (vinculados por `_dm_wc_product_id`), `single-dm_recurso.php` muestra automáticamente el `[dm_freebie_form]` en lugar del botón "Agregar al carrito".

### Integración newsletter

Mismo flujo que `newsletter-optin.php`:
1. Si el hook `mailerlite_woocommerce_subscribe` existe → delega al plugin oficial.
2. Si no, y el fallback API está habilitado en DM Settings → llama a la API de MailerLite directamente.

---

# 21. Personalización de emails WooCommerce

## Archivos

| Archivo | Responsabilidad |
|---|---|
| `wp-content/themes/daniela-child/inc/email-tokens.php` | Extrae tokens de diseño de `style.css` `:root {}` |
| `wp-content/themes/daniela-child/inc/woocommerce-emails.php` | Aplica diseño + CTAs a emails transaccionales de WooCommerce |

Cargados desde `functions.php` (antes del bloque de feature modules).

## email-tokens.php

- **`dm_get_email_tokens(): array`** — devuelve tokens cacheados en transient `dm_email_tokens_v1` (TTL 12 h). Claves: `color_primary`, `color_primary_dark`, `color_accent`, `color_text`, `color_text_muted`, `color_bg`, `color_bg_card`, `color_border`, `radius`, `shadow`.
- **`dm_parse_email_tokens(): array`** — lee `style.css`, extrae el bloque `:root {}` con regex, parsea todas las variables `--dm-*` y las mapea a las claves anteriores.
- **`dm_email_tokens_fallback(): array`** — valores hardcodeados idénticos a las variables CSS del tema, usados si `style.css` no se puede leer (tests, rutas no estándar).

**Decisión de diseño:** los estilos de email se derivan automáticamente del sistema de diseño del tema (variables CSS). Cualquier cambio en `style.css` `:root {}` se refleja en los emails sin duplicación manual.

## woocommerce-emails.php

### 1) Defaults de opciones (no destructivo)

`dm_set_woo_email_defaults()` en `init`: establece `woocommerce_email_base_color`, `woocommerce_email_background_color`, etc. usando tokens del tema, **solo si la opción no existe todavía** (`get_option($k, null) === null`). No sobreescribe configuración guardada por el admin.

### 2) CSS email-safe

`dm_woo_email_styles()` en filtro `woocommerce_email_styles` (priority 20): añade CSS email-safe al final de los estilos base de WooCommerce. Cubre: wrapper, cabecera, cuerpo, títulos, tabla de pedido, pie, botones WooCommerce, y bloque `.dm-email-cta`.

### 3) Asunto y encabezado personalizados

Cuatro filtros activos:

| Filtro | Resultado |
|---|---|
| `woocommerce_email_subject_customer_processing_order` | `"✅ Recibimos tu pedido #X — ya lo estamos procesando"` |
| `woocommerce_email_heading_customer_processing_order` | `"¡Gracias por tu compra! 🌿"` |
| `woocommerce_email_subject_customer_completed_order` | `"🎉 Tu pedido #X está listo — descarga tu recurso"` |
| `woocommerce_email_heading_customer_completed_order` | `"¡Tu recurso está listo! 🎉"` |

### 4) Bloque CTA de descarga (guest-friendly)

`dm_email_cta_block()` en acción `woocommerce_email_after_order_table` (priority 20):

- Solo se inyecta en emails de cliente (no admin) en formato HTML, para pedidos Processing o Completed.
- Llama a `dm_get_order_download_links($order)`, que usa `WC_Order_Item_Product::get_item_downloads()` — genera URLs de descarga únicas por pedido que no requieren login del cliente.
- Muestra un botón de descarga por producto descargable del pedido.
- Si no hay descargas disponibles (ej. email Processing sin archivos listos), no muestra el bloque.
- Siempre incluye enlace "Ver detalles del pedido" (`$order->get_view_order_url()`).

---

# 22. Cart Drawer (`inc/cart-drawer.php`)

## Archivo principal

`wp-content/themes/daniela-child/inc/cart-drawer.php`

También involucra: `js/cart-drawer.js`, `style.css`.

## Eliminación de botones WooCommerce nativos

WooCommerce registra por defecto dos acciones en `woocommerce_widget_shopping_cart_buttons`:
- `woocommerce_widget_shopping_cart_button_view_cart` (priority 10)
- `woocommerce_widget_shopping_cart_proceed_to_checkout` (priority 20)

Estas acciones se eliminan en un callback de `wp_loaded`:

```php
add_action( 'wp_loaded', function () {
    remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
    remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );
} );
```

**Por qué `wp_loaded` y no inline en el template:** el drawer usa `.widget_shopping_cart_content`, que es el contenedor que WooCommerce refresca vía AJAX (`wc-cart-fragments`). En una respuesta de fragmento AJAX, el template del drawer no se ejecuta, pero los hooks de WooCommerce sí. Si los `remove_action` estuvieran solo en el template, los botones duplicados reaparecerían en el refresh del mini-cart tras un add-to-cart.

**CSS safety-net** (`style.css`): como segunda línea de defensa ante plugins que re-registren esos hooks después de `wp_loaded`:

```css
#dm-cart-drawer .woocommerce-mini-cart__buttons { display: none !important; }
```

## Botón "Seguir comprando"

El footer del drawer tiene dos CTAs:
1. `<button id="dm-cart-drawer-continue" class="dm-btn dm-btn--ghost">Seguir comprando</button>` — cierra el drawer, permanece en la página actual.
2. `<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>">Finalizar compra</a>` — navega al checkout.

**Decisión UX:** se reemplazó el anterior enlace "Ver carrito" (que llevaba a `/carrito/`) por "Seguir comprando", y el CTA final se normalizó a **"Finalizar compra"**. El objetivo es reducir interrupciones en el funnel de compra: el usuario puede seguir añadiendo productos al carrito sin abandonar la página en la que está.

**JS** (`js/cart-drawer.js`): `$('#dm-cart-drawer-continue').on('click', closeDrawer)`.

---

# 23. `helpers-products.php` — Tarjetas de producto WooCommerce

## Archivo

`wp-content/themes/daniela-child/inc/helpers-products.php`

## `dm_render_product_card( $product, $back_url = '' )`

Renderiza una tarjeta de producto WooCommerce (`.dm-product-card` o equivalente).

### Detección de producto gratuito (`$is_free`)

```php
$price_raw = $product->get_price(); // '' = sin precio configurado; '0' = gratis explícito
$is_free   = ( $price_raw !== '' && (float) $price_raw <= 0.0 );
```

**Invariante crítica:** un producto sin precio configurado (`get_price() === ''`) **no** es tratado como gratuito. Solo los productos con precio explícitamente en `$0` activan el flujo de freebie.

### Clase del botón

| Condición | Clase |
|---|---|
| `$is_free === true` | `dm-btn dm-btn--secondary` |
| `$is_free === false` | `dm-btn dm-btn--primary` |

### Guard `$show_cta`

El botón "Agregar al carrito" solo se renderiza si:

```php
$show_cta = $is_free || ( $product->is_purchasable() && $product->is_in_stock() );
```

- Productos gratuitos: siempre muestran el CTA (precio = $0 → siempre disponible).
- Productos de pago: solo muestran el CTA si son comprables **y** están en stock.
- Productos no comprables y sin precio (ej. borradores, productos descontinuados): no renderizan botón.

---

# 24. Capa UX de WooCommerce (2026-04-10)

## Archivos implicados

| Archivo | Responsabilidad |
|---|---|
| `wp-content/themes/daniela-child/assets/css/woocommerce.css` | Hereda la estética del child theme en páginas WooCommerce (botones, formularios, tarjetas, notices, carrito, checkout y mi cuenta) |
| `wp-content/themes/daniela-child/inc/woocommerce-checkout.php` | Traducción forzada al español de los textos visibles más comunes de WooCommerce (`gettext`) y lógica auxiliar del checkout |
| `wp-content/themes/daniela-child/inc/newsletter-optin.php` | Checkbox GDPR de newsletter en checkout + persistencia del consentimiento |
| `wp-content/themes/daniela-child/inc/cart-drawer.php` | Drawer lateral con CTAs “Seguir comprando” / “Finalizar compra” |

## Decisiones implementadas

- El CSS de WooCommerce reutiliza `--dm-necesitas-pad-y` (definido en Home) para mantener un espaciado vertical consistente entre Home, carrito y checkout.
- Los contenedores de carrito / checkout / mi cuenta usan `--dm-woo-box-pad` para dar aire interno sin tocar el layout base del parent theme.
- Los strings visibles más importantes (`Checkout`, `Place order`, `Billing details`, etc.) se fuerzan al español desde el child theme para evitar mezcla de idiomas en frontend y emails.
- El opt-in de newsletter se renderiza en checkout con guard anti-duplicado (`static $rendered = false`) para asegurar visibilidad sin repetir el campo.

## Regla de mantenimiento

Cualquier ajuste futuro de la UX WooCommerce debe documentarse primero en:
- `docs/project_status.md`
- `docs/ARCHITECTURE_NOTES.md`
- `docs/woocommerce-overrides.md`
