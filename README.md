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

## Arquitectura actual de la HOME (dirección deseada)

HOME

- Meet Dani (se mantiene igual)
- ¿Qué necesitas? (nueva sección de orientación)
  - PDF Handouts
  - Cursos
  - Talleres
  - Programas
- Reviews (se mantiene igual)
- Newsletter (se mantiene igual)

---

## Nueva sección: ¿Qué necesitas?

Objetivo: guiar rápidamente al usuario al tipo de recurso que busca.

Se implementará como un **grid de 4 tarjetas con CTA**.

Cada tarjeta contendrá:
- título
- descripción corta
- botón CTA
- enlace a categoría WooCommerce

---

## Buenas prácticas

Siempre que se agregue código:
1. Explicar qué problema resuelve
2. Indicar en qué archivo se coloca
3. Mantener comentarios en el código
4. Evitar duplicación de lógica
5. Priorizar rendimiento

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
| `[dm_recursos_home]` | `/recursos/` | **Gratis** (grid) + **Pagos** (grid) |

Para activarlos, pega el shortcode correspondiente en el contenido de cada página desde el editor de WordPress (WP Admin → Páginas). Las páginas hijas mantienen sus propios shortcodes y no se ven afectadas.

### Páginas hijas (un bloque de productos por página)

| Shortcode | Página | Categoría WooCommerce |
|---|---|---|
| `[dm_escuela_cursos]` | `/escuela/cursos/` | `cursos` |
| `[dm_escuela_talleres]` | `/escuela/talleres/` | `talleres` |
| `[dm_recursos_gratis]` | `/recursos/gratis/` | `recursos-gratis` |
| `[dm_recursos_pagos]` | `/recursos/pagos/` | `recursos-pagos` |
| `[dm_recursos_temas]` | `/recursos/temas/` | `recursos-gratis` + `recursos-pagos` (filtrado por tema) |

### `[dm_recursos_temas]` — Explorar recursos por tema

Renderiza un bloque de chips (pastillas) basados en las etiquetas de producto (`product_tag`) de WooCommerce, seguido de un grid de productos filtrado por el tema seleccionado.

**Cómo funciona:**

| Elemento | Descripción |
|---|---|
| Chips de temas | Se generan automáticamente a partir de las etiquetas (`product_tag`) que tengan al menos un producto publicado en `recursos-gratis` o `recursos-pagos`. Cada chip muestra el nombre del tema y el número de recursos disponibles. |
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

> **Nota:** Las páginas estáticas principales (home, sobre mí, contacto) siguen siendo Pages de WordPress. El catálogo navega por CPTs.

### CPTs registrados

| CPT slug | Nombre admin | URL archive | Descripción |
|---|---|---|---|
| `dm_recurso` | Recursos CPT | `/recursos/` | Materiales descargables, guías, ejercicios |
| `dm_escuela` | Escuela CPT | `/escuela/` | Cursos, talleres y programas formativos |
| `dm_servicio` | Servicios CPT | `/servicios/` | Sesiones individuales y membresías |

Todos los CPTs soportan: `title`, `editor`, `thumbnail`, `excerpt`, `revisions`.  
Todos aparecen en el editor de bloques (REST habilitado).

### Taxonomías internas

| Taxonomía | CPT | Términos predefinidos | Uso |
|---|---|---|---|
| `dm_tipo_recurso` | `dm_recurso` | `gratis`, `pagos` | Chips de filtro en `/recursos/` |
| `dm_tipo_escuela` | `dm_escuela` | `cursos`, `talleres`, `programas` | Chips de filtro en `/escuela/` |
| `dm_tipo_servicio` | `dm_servicio` | `sesiones`, `membresias` | Chips de filtro en `/servicios/` |
| `dm_tema` | los 3 CPTs | _(el admin los crea libremente)_ | Temas transversales para chips y cross-navegación |

Los términos de `dm_tipo_*` se crean automáticamente en el primer `init` si no existen. Los términos de `dm_tema` los añade el admin manualmente.

### Cómo crear un ítem y vincularlo con un producto WooCommerce

1. **Crear el post CPT:**  
   WP Admin → (Recursos CPT / Escuela CPT / Servicios CPT) → Añadir nuevo.  
   Rellena título, contenido, imagen destacada y excerpt.

2. **Asignar tipo/taxonomía:**  
   En la barra lateral del editor, elige el tipo correspondiente:  
   - Recursos: selecciona `gratis` o `pagos` en **Tipos de recurso**.  
   - Escuela: selecciona `cursos`, `talleres` o `programas` en **Tipos de Escuela**.  
   - Servicios: selecciona `sesiones` o `membresias` en **Tipos de servicio**.  
   - Opcionalmente, añade temas transversales en **Temas**.

   > **Auto-clasificación (dm_escuela):** si no seleccionas ningún tipo al guardar, el sistema infiere el término automáticamente a partir del título:  
   > - título contiene `taller` → `talleres`  
   > - título contiene `programa` → `programas`  
   > - en otro caso → `cursos`  
   > Una vez asignado manualmente, el sistema no sobreescribe tu selección.

3. **Vincular producto WooCommerce:**  
   En la barra lateral del editor verás el metabox **"Producto WooCommerce relacionado"**.  
   - Introduce el **ID del producto** de WooCommerce (número entero).  
   - Si el producto es gratis (precio = 0), el botón CTA dirá **"Recíbelo gratis"**.  
   - Si el producto es de pago, el CTA dirá **"Comprar"** y mostrará el precio.  
   - Si dejas el campo vacío, no se mostrará ningún CTA (útil para posts informativos).

4. **(Solo Escuela) Vincular curso Tutor LMS:**  
   En la barra lateral verás también el metabox **"Curso Tutor LMS relacionado"**.  
   - **ID del curso en Tutor LMS:** introduce el ID numérico del curso de Tutor LMS.  
     El sistema usará este ID para verificar si el usuario está inscrito y para construir la URL del curso.  
   - **URL directa al curso (opcional):** si prefieres forzar una URL concreta en lugar de usar el permalink del curso, introdúcela aquí.  
   - Cuando el usuario **está inscrito**, el CTA mostrará **"Ir al curso"** en lugar del botón de compra.  
   - Cuando **no está inscrito** (o no está logueado), se muestra el CTA de WooCommerce habitual.

5. **Publicar.** La URL estará disponible en:
   - Recurso: `/recursos/<slug>/`
   - Escuela: `/escuela/<slug>/`
   - Servicio: `/servicios/<slug>/`

> **Importante:** después de registrar los CPTs por primera vez, ve a **WP Admin → Ajustes → Enlaces permanentes** y haz clic en **Guardar cambios** (aunque no cambies nada). Esto vacía el caché de rewrite rules y activa las nuevas URLs.

### Lógica del CTA en single-dm_escuela (Tutor + WooCommerce)

| Condición | CTA mostrado |
|---|---|
| Usuario inscrito en el curso Tutor vinculado | **"Ir al curso"** (enlaza al curso de Tutor) |
| Usuario NO inscrito (o no logueado) + producto WC vinculado | CTA de compra WooCommerce (**"Agregar al carrito"**) |
| Sin producto WC ni curso Tutor vinculado | Sin CTA (post informativo) |

La verificación de inscripción usa `tutor_utils()->is_enrolled()` si Tutor LMS está activo; en caso contrario recurre a consultar los registros internos `tutor_enrolled` de la base de datos.

Los administradores (`manage_options`) siempre ven el CTA "Ir al curso" cuando hay un curso vinculado.

### Backfill de clasificación (admin)

Para clasificar automáticamente ítems de Escuela ya existentes que no tienen tipo asignado:

1. Ve a **WP Admin → Escuela CPT**.
2. Selecciona los posts que quieres clasificar (o todos).
3. En el desplegable **"Acciones en lote"**, elige **"Auto-clasificar tipo (backfill)"**.
4. Haz clic en **Aplicar**.

Solo se modifican posts sin término `dm_tipo_escuela` asignado. Los que ya tienen tipo no se tocan.

### URLs esperadas

| URL | Qué muestra |
|---|---|
| `/recursos/` | Archive CPT dm_recurso — grid con chips de tipo |
| `/recursos/<slug>/` | Single dm_recurso — contenido + CTA |
| `/escuela/` | Archive CPT dm_escuela — grid con chips de tipo |
| `/escuela/<slug>/` | Single dm_escuela — contenido + CTA (Tutor o WC según acceso) |
| `/servicios/` | Archive CPT dm_servicio — grid con chips de tipo |
| `/servicios/<slug>/` | Single dm_servicio — contenido + CTA |

> **Nota:** las páginas estáticas existentes (`/recursos/`, `/escuela/`) no deben tener el mismo slug que el archive del CPT, o WordPress priorizará la página estática. Si ya existen como Pages, cámbiales el slug o conviértelas a borradores para que el archive del CPT tome el control de esa URL.

### Templates

Los templates viven en la raíz del tema hijo (como manda WordPress):

| Archivo | Para |
|---|---|
| `archive-dm_recurso.php` | Archive de Recursos CPT |
| `single-dm_recurso.php` | Single de Recurso |
| `archive-dm_escuela.php` | Archive de Escuela CPT |
| `single-dm_escuela.php` | Single de Escuela (CTA condicional Tutor / WC) |
| `archive-dm_servicio.php` | Archive de Servicios CPT |
| `single-dm_servicio.php` | Single de Servicio |

### Módulos del child theme (CPT)

| Archivo | Responsabilidad |
|---|---|
| `inc/cpt.php` | Registra CPTs y taxonomías; crea términos por defecto; auto-clasificación y bulk action |
| `inc/helpers-cpt.php` | Metabox WC, metabox Tutor LMS, CTA renderer (WC + Tutor), chips de taxonomía, grid CPT |

### Relación con los shortcodes existentes (WooCommerce Pages)

Los shortcodes de WooCommerce (Páginas clásicas) y los CPTs **coexisten sin conflicto**:

| Capa | Motor | URL | Casos de uso |
|---|---|---|---|
| Pages + Shortcodes | WooCommerce | `/recursos/gratis/`, `/escuela/cursos/`… | Catálogo WC existente |
| CPT Archives/Singles | WordPress nativo | `/recursos/`, `/escuela/`, `/servicios/` | Nueva capa editorial |
| Checkout, Membresías, Suscripciones | WooCommerce | `/checkout/`, etc. | Motor de compra (no se toca) |
| Cursos / lecciones / progreso | Tutor LMS | `/courses/<slug>/` | Experiencia académica (source of truth) |
