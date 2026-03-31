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
├── recursos-gratis
└── recursos-pagos

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

La home no debe ser un blog.

Debe funcionar como **página de orientación de producto**.

Estructura:
Meet Dani

¿Qué necesitas?

Reviews

Newsletter

---

# 6. Sección clave: ¿Qué necesitas?

Esta sección funciona como **sistema de orientación del usuario**.

El visitante elige entre:

- Recursos
- Escuela (Cursos / Talleres / Programas)
- Servicios (Sesiones / Paquetes / Membresías / Supervisiones)

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
| `dm_tema` | los 3 CPTs | _(admin los crea libremente)_ |

Los términos de `dm_tipo_*` se crean automáticamente en el primer `init`.

## 13.2 Templates

Viven en la raíz del tema hijo (convención WordPress):

| Archivo | URL | Función |
|---|---|---|
| `archive-dm_recurso.php` | `/recursos/` | Grid de recursos con chips de tipo |
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

- CTA secundario neutro: **"Ver detalles"** (no usar copy específico de sección como "Ver curso" en contextos genéricos).
  - Excepción documentada: el grid de `/escuela/` muestra **"Ver curso"** como primer CTA cuando existe `_dm_tutor_course_url`, porque el contexto es explícitamente un curso en Tutor LMS. Fuera de ese contexto específico, usar "Ver detalles".
- CTA primario de compra: **"Agregar al carrito"** (texto unificado; generado por `dm_cpt_render_cta()`).
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
- En las tarjetas de `/escuela/`: se muestran máximo dos botones en el footer:
  1. "Ver curso" (solo si `_dm_tutor_course_url` tiene valor) — abre Tutor en nueva pestaña.
  2. "Agregar al carrito" (solo si hay `_dm_wc_product_id` vinculado) — WooCommerce.
- Si ninguno de los dos existe, el footer de la tarjeta no se renderiza (no aparece vacío).
- En singles (`/escuela/<slug>/`): solo se muestra el CTA de WooCommerce.

## Excerpt limpio
- **Problema detectado:** algunos excerpts de posts CPT traen HTML/CTAs de versiones antiguas.
- **Pendiente:** sanitizar el excerpt antes de renderizarlo en el grid.
- Solución planificada: en `dm_cpt_render_grid()`, aplicar `wp_strip_all_tags()` o `wp_trim_words()` con strip_tags al excerpt antes de mostrarlo.
- Afecta al archivo: `wp-content/themes/daniela-child/inc/helpers-cpt.php` → función `dm_cpt_render_grid()`.

## Botones consistentes
- Clase `dm-btn dm-btn--ghost` → "Ver curso" (acción secundaria / linkout).
- Clase `dm-btn dm-btn--primary` → "Agregar al carrito" (producto de pago).
- Clase `dm-btn dm-btn--secondary` → "Agregar al carrito" (producto gratis).
- No mezclar estilos entre secciones (Recursos, Escuela, Servicios usan el mismo sistema).

## Menú / navegación (backlog)
- Pendiente: subitems hover para Escuela, Recursos, Servicios en el menú principal.
  - Subitem Escuela: Cursos / Talleres / Programas.
  - Subitem Recursos: Por tema (dm_tema slugs).
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
wp dm import-recursos --force    # Fuerza actualización de existentes
```

## Archivo

`wp-content/themes/daniela-child/inc/cli-import.php`

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
- Asigna `product_tag` y `dm_tema` con los mismos slugs derivados de keywords del título.
- Bundles (familia "Afirmaciones"): tag `bundle`, precio $9.

## Metas de trazabilidad

| Meta key | Post type | Valor |
|---|---|---|
| `_dm_source_attachment_id` | `product`, `dm_recurso` | ID del attachment origen |
| `_dm_wc_product_id` | `dm_recurso` | ID del producto WC vinculado |

---

# 20. Freebies por email con link tokenizado

## Archivo

`wp-content/themes/daniela-child/inc/freebie-download.php`

## Tabla de base de datos

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

## Shortcode

```
[dm_freebie_form product_id="123"]
[dm_freebie_form product_id="123" title="Recíbelo gratis" button_text="Enviarme el PDF"]
```

Muestra: campo email + checkbox opt-in newsletter (no pre-marcado, GDPR-compliant).

## Endpoint de descarga

`?dm_freebie_token=<hex64>` en cualquier URL del sitio.

Valida: token existe, no expirado, no superó `max_downloads`. Entrega el archivo y actualiza el contador.

## Integración con single-dm_recurso.php

Para recursos con precio $0 (vinculados por `_dm_wc_product_id`), `single-dm_recurso.php` muestra automáticamente el `[dm_freebie_form]` en lugar del botón "Agregar al carrito".

## Integración newsletter

Mismo flujo que `newsletter-optin.php`:
1. Si el hook `mailerlite_woocommerce_subscribe` existe → delega al plugin oficial.
2. Si no, y el fallback API está habilitado en DM Settings → llama a la API de MailerLite directamente.
