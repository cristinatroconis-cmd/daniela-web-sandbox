# Proyecto Web — Daniela Montes Psicóloga

Repositorio de trabajo para la optimización técnica, UX y conversión de la web (sandbox/local):

`https://danielamontespsic.com/`

El proyecto **NO** busca reconstruir el sitio desde cero, sino **mejorar la estructura existente**, optimizar la experiencia de usuario y aumentar la conversión hacia productos digitales y programas terapéuticos.

---

## Alcance del repositorio (importante)

Este repositorio versiona **solo código propio** para mantener PRs limpios y evitar incluir WordPress core/plugins/uploads.

Se versiona:
- `wp-content/themes/daniela-child/` (tema-hijo / código principal)
- (opcional) `wp-content/mu-plugins/`

No se versiona:
- WordPress core (`wp-admin`, `wp-includes`)
- uploads (`wp-content/uploads`)
- plugins de terceros
- base de datos

---

## Objetivo del proyecto

Transformar la web en una **plataforma de recursos psicológicos y formación**, donde el foco principal sea:

1. Venta de recursos descargables
2. Venta de cursos
3. Talleres
4. Programas terapéuticos
5. (Secundario) sesiones psicológicas

La arquitectura debe facilitar:
- navegación clara
- CTAs visibles
- flujo rápido hacia checkout
- recurrencia de compra

---

## Stack técnico

- CMS: WordPress  
- Ecommerce: WooCommerce  
- Servidor actual: Rocket.net  

Plugins principales:
- WooCommerce
- WooCommerce Membership
- WooCommerce Subscriptions
- Tutor LMS
- Elementor / Elementor Pro (solo en secciones existentes)
- MailerLite Integration
- CommerceKit

---

## Filosofía de desarrollo

Este proyecto sigue estas reglas:

### 1 — No romper lo existente
El theme actual funciona y **no se debe reconstruir completamente**.

Las mejoras deben ser:
- progresivas
- modulares
- compatibles con el theme actual

### 2 — Evitar dependencia de Elementor
Las nuevas secciones deben desarrollarse preferentemente con:
- PHP templates
- HTML semántico
- CSS ligero
- JS mínimo

### 3 — Arquitectura modular
Las secciones de la home deben cargarse con `get_template_part()`.

Ejemplo:
```php
get_template_part('template-parts/home/section', 'necesitas');
```

Esto permite:
- modularidad
- mantenimiento fácil
- eliminación futura de Elementor

### 4 — UX primero
Cada cambio debe mejorar al menos uno de estos puntos:
- claridad de oferta
- reducción de fricción en compra
- acceso rápido a productos
- escalabilidad del catálogo

---

## Arquitectura actual de la HOME (implementada)

HOME

- Meet Dani
- ¿Qué necesitas? (**bloque editorial + carousel propio**)
- Reviews
- Newsletter

---

## Sección “¿Qué necesitas?”

Objetivo: orientar rápidamente al usuario hacia la oferta correcta.

Implementación actual:
- CSS: `wp-content/themes/daniela-child/assets/css/home-necesitas.css`
- JS: `wp-content/themes/daniela-child/assets/js/home-necesitas-carousel.js`
- tokens de espaciado compartidos con WooCommerce (`--dm-necesitas-pad-y`)

Cada slide puede contener:
- título
- texto breve
- CTA
- enlace al destino correspondiente

---

## Buenas prácticas

Siempre que se agregue código:
1. Explicar qué problema resuelve
2. Indicar en qué archivo se coloca
3. Mantener comentarios en el código
4. Evitar duplicación de lógica
5. Priorizar rendimiento

---

## Estado reciente — WooCommerce (2026-04-10)

Cambios ya aplicados en el child theme:
- `assets/css/woocommerce.css` hereda la estética del child theme (tipografía, botones, formularios, tarjetas y notices)
- WooCommerce reutiliza el padding vertical de Home vía `--dm-necesitas-pad-y`
- `inc/woocommerce-checkout.php` fuerza al español los textos visibles más importantes de WooCommerce
- `inc/newsletter-optin.php` muestra el checkbox GDPR de newsletter en checkout sin duplicarlo
- el cart drawer y el popup de add-to-cart usan el copy unificado **“Seguir comprando”** / **“Finalizar compra”**

---

## Estilo visual

La estética del proyecto debe ser:
- limpia
- calmada
- cercana
- profesional

Evitar:
- elementos pesados
- animaciones innecesarias
- exceso de JS

---

## Roadmap técnico

### Fase 1 — Auditoría técnica completa
- theme
- plugins
- estructura WooCommerce
- LMS

### Fase 2 — Optimización HOME
- nueva sección “¿Qué necesitas?”
- jerarquía visual
- CTAs claros

### Fase 3 — Optimización conversión
- checkout simplificado
- bundles
- productos destacados
- automatizaciones email

### Fase 4 — Optimización de escuela online
- estructura cursos
- acceso usuarios
- navegación LMS

---

## Objetivo final

Convertir la web en una **plataforma de recursos psicológicos escalable**, donde el usuario pueda:
1. descubrir contenido
2. comprar recursos
3. profundizar con cursos
4. avanzar a programas terapéuticos

---

## Cambios en el header (Header 4)

### Eliminar búsqueda en escritorio

**Problema:** Aunque en el Customizer se seleccionaba *"Display the search? → Disable"*, el bloque `.site-search` seguía renderizándose en escritorio con el layout Header 4.

**Solución aplicada (`functions.php`):**
Se añadió un hook `after_setup_theme` (prioridad 20) que elimina la acción `shoptimizer_product_search` del hook `shoptimizer_header` únicamente cuando la petición **no es de móvil** (`! wp_is_mobile()`). En móvil el comportamiento de Shoptimizer no se altera.

```php
add_action( 'after_setup_theme', function () {
    if ( ! wp_is_mobile() ) {
        remove_action( 'shoptimizer_header', 'shoptimizer_product_search', 40 );
    }
}, 20 );
```

**Cómo revertir:** eliminar o comentar el bloque `add_action( 'after_setup_theme', … )` del archivo `wp-content/themes/daniela-child/functions.php`.

---

## Shortcodes del tema hijo

Todos los shortcodes están registrados en `wp-content/themes/daniela-child/functions.php`.

### Páginas hub (muestran dos bloques de productos)

| Shortcode | Página | Qué muestra |
|---|---|---|
| `[dm_escuela_home]` | `/escuela/` | **Cursos** (grid) + **Talleres** (grid) |
| `[dm_recursos_home]` | `/recursos/` | Catálogo general de recursos |

Para activarlos, pega el shortcode correspondiente en el contenido de cada página desde el editor de WordPress (WP Admin → Páginas). Las páginas hijas mantienen sus propios shortcodes y no se ven afectadas.

### Páginas hijas (un bloque de productos por página)

| Shortcode | Página | Categoría WooCommerce |
|---|---|---|
| `[dm_escuela_cursos]` | `/escuela/cursos/` | `cursos` |
| `[dm_escuela_talleres]` | `/escuela/talleres/` | `talleres` |
| `[dm_recursos]` | `/recursos/` | `recursos` + filtro público `?tema=` |
| `[dm_recursos_temas]` | `/recursos/temas/` | `recursos` (filtrado por tema) |

### `[dm_recursos_temas]` — Explorar recursos por tema

Renderiza un bloque de chips (pastillas) basados en las etiquetas de producto (`product_tag`) de WooCommerce, seguido de un grid de productos filtrado por el tema seleccionado.

**Cómo funciona:**

| Elemento | Descripción |
|---|---|
| Chips de temas | Se generan automáticamente a partir de las etiquetas (`product_tag`) que tengan al menos un producto publicado en `recursos`. Cada chip muestra el nombre del tema y el número de recursos disponibles. |
| Chip "Todos" | Siempre visible; muestra todos los recursos (sin filtro de tema). |
| Parámetro `?tema=<slug>` | Controla el tema activo. Por ejemplo: `/recursos/temas/?tema=ansiedad`. El chip correspondiente se marca como activo. Si se omite, se muestran todos los recursos. |
| Fallback sin JS | Los chips son enlaces HTML estándar; el filtrado funciona con recarga de página aunque JavaScript no esté disponible. |
| Mejora con JS | Cuando JS está disponible, el chip activo se desplaza automáticamente al campo visible en pantallas pequeñas. |

**Ejemplo de uso en WordPress:**
1. Crea una página con slug `recursos/temas/`.
2. En el editor de contenido, pega el shortcode: `[dm_recursos_temas]`
3. Publica la página.

Los chips y el grid se generan dinámicamente. Los resultados se cachean durante 1 hora (transient) y el caché se invalida automáticamente cuando se crea o actualiza un producto.

---

## Custom Post Types (CPT) — Catálogo editorial

Los CPTs permiten estructurar el contenido editorial (recursos, escuela, servicios) de forma independiente a WooCommerce. WooCommerce **sigue siendo el motor de compra**; los CPTs son la capa de navegación/SEO/UX.

> **Fuente de verdad del diccionario y de las decisiones de naming:** ver `docs/ARCHITECTURE_NOTES.md` → **Diccionario oficial**.

> **Nota:** Las páginas estáticas principales (home, sobre mí, contacto) siguen siendo Pages de WordPress. El catálogo navega por CPTs.

### CPTs registrados

| CPT slug | Nombre admin | URL archive | Descripción |
|---|---|---|---|
| `dm_recurso` | Recursos CPT | `/recursos/` | Materiales descargables, guías, ejercicios |
| `dm_escuela` | Escuela CPT | `/escuela/` | Cursos, talleres y programas formativos |
| `dm_servicio` | Servicios CPT | `/servicios/` | Sesiones individuales y membresías |
| `dm_temas` | Temas | `/temas/` | Archive temático que agrupa productos por `product_tag` |

`dm_recurso`, `dm_escuela` y `dm_servicio` soportan: `title`, `editor`, `thumbnail`, `excerpt`, `revisions`.  
`dm_temas` es un CPT técnico para el archive `/temas/` y solo soporta `title`.  
Todos aparecen en el editor de bloques (REST habilitado).

### Taxonomías internas

| Taxonomía | CPT | Términos predefinidos | Uso |
|---|---|---|---|
| `dm_tipo_recurso` | `dm_recurso` | `gratis`, `pagos` | Legacy técnico; no gobierna la navegación pública |
| `dm_tipo_escuela` | `dm_escuela` | `cursos`, `talleres`, `programas` | Legacy técnico; la navegación pública usa WooCommerce |
| `dm_tipo_servicio` | `dm_servicio` | `sesiones`, `membresias` | Legacy técnico; la navegación pública usa WooCommerce |
| `dm_tema` | los 3 CPTs | _(se sincronizan desde WC product_tag)_ | Espejo editorial del tema; no es la fuente primaria |

Los términos de `dm_tipo_*` se crean automáticamente en el primer `init` si no existen.  
Los términos de `dm_tema` **se crean y sincronizan automáticamente** desde los `product_tag` del
producto WC vinculado al guardar el CPT (ver sección "Cómo crear un ítem" más abajo).

### Diccionario oficial

| Término | Definición oficial |
|---|---|
| Tema | Concepto de negocio/editorial como ansiedad, autoestima o relaciones. |
| `product_tag` | Fuente de verdad primaria para clasificar productos Woo por tema. |
| `dm_tema` | Espejo editorial sincronizado para CPTs; no se edita manualmente ni gobierna la navegación pública. |
| Chip | Componente visual clicable que representa un filtro o acceso rápido. |
| Hub | Pantalla de entrada o navegación, no necesariamente un listado de resultados. |
| Archive/listado | Pantalla que muestra resultados filtrados o agrupados. |
| Producto | Objeto comercial WooCommerce que compra, descarga o agenda el usuario. |
| CPT editorial | Pieza SEO/UX/contenido (`dm_recurso`, `dm_escuela`, `dm_servicio`) vinculada opcionalmente a un producto. |

### Diccionario core de temas de marketing (`dm_tema` / `product_tag`)

Usa siempre estos slugs en minúscula para mantener consistencia entre WooCommerce y los CPTs:

| Slug | Uso / Temática |
|---|---|
| `ansiedad` | Recursos para gestionar ansiedad, estrés, activación nerviosa |
| `autoestima` | Autoconcepto, autoaceptación, autocompasión |
| `autoconocimiento` | Journaling, identidad, valores, exploración personal |
| `gestion-emocional` | Regulación emocional, CBT, registros, herramientas |
| `mindfulness` | Respiración, observación de la mente, presencia |
| `relaciones` | Pareja, vínculos, comunicación, límites |
| `sanacion` | Niña interior, perdón, sanar pasado |
| `abundancia` | Mentalidad de crecimiento, éxito, dinero |

> **Regla:** máximo 3 tags por producto/recurso. Si el producto tiene más de 3 `product_tag`,
> solo se sincronizan los 3 primeros (orden: `term_id` ASC).

### Cómo crear un ítem y vincularlo con un producto WooCommerce

> **Regla de gobernanza: WooCommerce es la fuente de verdad para los tags de marketing.**
> Los tags (`product_tag`) del producto WC se copian automáticamente a `dm_tema` del CPT
> al guardar el post. **No edites `dm_tema` manualmente**; edita los tags del producto Woo.

**Procedimiento para Dani (paso a paso):**

1. **Crear el producto en WooCommerce** (si aún no existe):  
   WP Admin → Productos → Añadir nuevo.  
   - Asigna nombre, precio y **categorías WooCommerce** correctas (`recursos`, `cursos`, `talleres`, `programas`, `sesiones`, `membresias`, etc.).  
   - **Asigna máximo 3 tags** de marketing (`product_tag`) usando los slugs del diccionario core (ver más abajo).  
   - Si quieres controlar cómo se ve la card del archive/editorial, completa en el producto su imagen y short description: el catálogo editorial usa esos datos del producto vinculado cuando existen.  
   - Publica el producto y copia su **ID** (número que aparece en la URL al editarlo).

2. **Crear el post CPT:**  
   WP Admin → (Recursos CPT / Escuela CPT / Servicios CPT) → Añadir nuevo.  
   - Rellena el **título** del ítem editorial.  
   - Completa el metabox **"Secciones del single tipo landing"**: ese es el flujo principal para construir el single.  
   - El editor/excerpt/featured image nativos quedan como apoyo o fallback, no como la fuente principal del layout.

3. **Vincular producto WooCommerce:**  
   En la barra lateral del editor verás el metabox **"Producto WooCommerce relacionado"**.  
   - Introduce el **ID del producto** de WooCommerce (número entero).  
   - El metabox mostrará el nombre del producto, sus `product_tag` y una alerta si tiene más de 3.  
   - Al guardar, esos `product_tag` se sincronizan automáticamente hacia `dm_tema`.  
   - Si dejas el campo vacío, el CPT se publica como pieza editorial sin CTA de compra.

4. **Solo para Escuela, si el contenido comprado vive en Tutor:**  
   Completa el metabox **"Curso Tutor (URL)"** con el path o URL del curso.  
   Ese dato se usa como puente técnico hacia Tutor para el producto/curso, pero la navegación pública de catálogo usa **"Ver detalles"** al single editorial y **"Agregar al carrito"** como CTA comercial.

5. **Publicar / Actualizar** el post CPT.  
   Al guardar, el sistema copia automáticamente los `product_tag` del producto vinculado
   a la taxonomía `dm_tema` del CPT (máximo 3, ordenados por `term_id` ASC).

6. **Revisar el resultado público.** La URL estará disponible en:
   - Recurso: `/recursos/<slug>/`
   - Escuela: `/escuela/<slug>/`
   - Servicio: `/servicios/<slug>/`

7. **Validar el archive correspondiente** (`/recursos/`, `/escuela/`, `/servicios/`):  
   - la card debe tomar imagen/excerpt del producto vinculado cuando existan,  
   - `Ver detalles` debe ir al single editorial,  
   - y los temas visibles de la card salen de `product_tag` como texto informativo.

> **Importante:** después de registrar los CPTs por primera vez, ve a **WP Admin → Ajustes → Enlaces permanentes** y haz clic en **Guardar cambios** (aunque no cambies nada). Esto vacía el caché de rewrite rules y activa las nuevas URLs.

### URLs esperadas

| URL | Qué muestra |
|---|---|
| `/recursos/` | Archive CPT dm_recurso — grid editorial con chips de tema |
| `/recursos/<slug>/` | Single dm_recurso — contenido + CTA |
| `/escuela/` | Archive CPT dm_escuela — grid editorial con chips WooCommerce |
| `/escuela/<slug>/` | Single dm_escuela — contenido + CTA |
| `/servicios/` | Archive CPT dm_servicio — grid editorial con chips WooCommerce |
| `/servicios/<slug>/` | Single dm_servicio — contenido + CTA |

> **Nota:** las páginas estáticas existentes (`/recursos/`, `/escuela/`) no deben tener el mismo slug que el archive del CPT, o WordPress priorizará la página estática. Si ya existen como Pages, cámbiales el slug o conviértelas a borradores para que el archive del CPT tome el control de esa URL.

### Templates

Los templates viven en la raíz del tema hijo (como manda WordPress):

| Archivo | Para |
|---|---|
| `archive-dm_recurso.php` | Archive de Recursos CPT |
| `single-dm_recurso.php` | Single de Recurso |
| `archive-dm_escuela.php` | Archive de Escuela CPT |
| `single-dm_escuela.php` | Single de Escuela |
| `archive-dm_servicio.php` | Archive de Servicios CPT |
| `single-dm_servicio.php` | Single de Servicio |

### Módulos del child theme (CPT)

| Archivo | Responsabilidad |
|---|---|
| `inc/cpt.php` | Registra CPTs y taxonomías; crea términos por defecto |
| `inc/helpers-cpt.php` | Metaboxes, CTA renderer, chips/filtros, grid CPT, bridge de Escuela |
| `inc/sync-tags.php` | Sincroniza `product_tag` WC → `dm_tema` al guardar el CPT |

### Relación con los shortcodes existentes (WooCommerce Pages)

Los shortcodes de WooCommerce (Páginas clásicas) y los CPTs **coexisten sin conflicto**:

| Capa | Motor | URL | Casos de uso |
|---|---|---|---|
| Pages + Shortcodes | WooCommerce | `/recursos/temas/`, listados/hubs específicos | Catálogo WC específico / exploración por shortcode |
| CPT Archives/Singles | WordPress nativo | `/recursos/`, `/escuela/`, `/servicios/` | Nueva capa editorial |
| Archive temático | WordPress + WooCommerce | `/temas/` | Productos agrupados por `product_tag` |
| Checkout, Membresías, Suscripciones | WooCommerce | `/checkout/`, etc. | Motor de compra (no se toca) |
