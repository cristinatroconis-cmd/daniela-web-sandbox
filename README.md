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

Transformar la web en una **plataforma de recursos psicológicos y formación** donde el usuario pueda:

1. Descubrir y descargar recursos (gratis o de pago)
2. Comprar y acceder a cursos
3. Apuntarse a talleres
4. Avanzar hacia programas terapéuticos
5. (Secundario) Contratar sesiones psicológicas

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

### 1 — No romper lo existente
El theme actual funciona y **no se debe reconstruir completamente**. Las mejoras deben ser progresivas, modulares y compatibles.

### 2 — Evitar dependencia de Elementor
Las nuevas secciones se desarrollan con PHP templates, HTML semántico, CSS ligero y JS mínimo.

### 3 — Arquitectura modular
Las secciones deben poder cargarse con `get_template_part()`, lo que permite mantenimiento fácil y eliminación futura de Elementor.

### 4 — UX primero
Cada cambio debe mejorar: claridad de oferta, reducción de fricción, acceso rápido a productos o escalabilidad del catálogo.

---

## Buenas prácticas de código

Siempre que se agregue código:
1. Explicar qué problema resuelve
2. Indicar en qué archivo se coloca
3. Mantener comentarios en el código
4. Evitar duplicación de lógica
5. Priorizar rendimiento

---

## Estilo visual

La estética del proyecto debe ser limpia, calmada, cercana y profesional. Evitar elementos pesados, animaciones innecesarias y exceso de JS.

---

## Features del tema hijo — Guía de configuración

### A) Hub de Recursos — `[dm_recursos]`

Shortcode para mostrar un grid de recursos (PDFs, guías, etc.) con filtros.

#### Shortcode

```
[dm_recursos per_page="12" columns="3"]
```

Parámetros opcionales:
- `per_page` — número de productos (default: 12)
- `columns` — columnas de la grilla (default: 3)

#### Filtros disponibles

Los filtros funcionan por querystring (sin JS) y por fetch (con JS, sin recarga completa):

| Parámetro | Valores | Ejemplo |
|-----------|---------|---------|
| `dm_type` | `gratis` \| `pagos` | `?dm_type=gratis` |
| `dm_topic` | slug de product_cat | `?dm_topic=ansiedad` |

Ejemplo combinado: `?dm_type=pagos&dm_topic=autoestima`

#### Categorías de WooCommerce necesarias

Crear en **WooCommerce → Productos → Categorías**:

| Slug | Nombre sugerido | Propósito |
|------|-----------------|-----------|
| `recursos-gratis` | Recursos Gratuitos | Productos gratuitos / opt-in email |
| `recursos-pagos` | Recursos de Pago | PDFs, guías, herramientas de pago |
| `ansiedad` | Ansiedad | Tema / tópico |
| `autoestima` | Autoestima | Tema / tópico |
| `mindfulness` | Mindfulness | Tema / tópico |

> **Regla:** cada recurso debe asignarse a `recursos-gratis` **o** `recursos-pagos` (no ambas). Los temas se asignan adicionalmente.

#### URL de landing por email (recursos gratuitos)

El botón "Recíbelo por email" apunta por defecto al permalink del producto. Para usar una URL de landing personalizada, añade el metadato de producto:

- **Meta key:** `_dm_email_landing_url`
- **Valor:** URL completa de la landing page de opt-in

(Editable desde WooCommerce → producto → Custom Fields, o con ACF.)

#### Colocar el hub en una página

1. Crea una página WordPress (ej. "Recursos").
2. Añade el shortcode: `[dm_recursos]`
3. Guarda y publica.

---

### B) Newsletter Opt-In en Checkout

Añade un checkbox de consentimiento **desmarcado por defecto** en el checkout de WooCommerce.

**Qué guarda:** meta del pedido `_dm_newsletter_optin` = `yes` o `no`.

#### Integración con MailerLite

**Modo 1 — Plugin oficial (recomendado):**
Si el plugin "MailerLite - WooCommerce integration" expone el action `mailerlite_woocommerce_subscribe`, el tema hijo lo invoca automáticamente cuando el cliente optó por suscribirse. No se necesita configuración extra.

**Modo 2 — API fallback (si el plugin no provee el hook):**

1. Ve a **WooCommerce → Ajustes → DM Newsletter**.
2. Activa **"Activar API fallback"**.
3. Introduce la **API Key** de MailerLite.
4. Introduce el **ID del grupo** de destino.
5. (Opcional) Configura los IDs de grupo para los tags de segmentación.

#### Tags automáticos (segmentación por compra)

| Tag | Cuándo se asigna |
|-----|-----------------|
| `buyer` | Todos los compradores |
| `resource-buyer` | Compra de recursos (gratis/pagos) |
| `course-buyer` | Compra de cursos o talleres |

Para activar un tag, introduce su ID de grupo de MailerLite en la configuración.

---

### C) Shortcode de productos por categoría — `[dm_products]`

Shortcode para listar productos de WooCommerce filtrados por `product_cat`. Útil para las páginas "Cursos", "Talleres", "Sesiones", etc.

#### Uso

```
[dm_products category="cursos"]
[dm_products category="talleres" per_page="6" columns="2"]
[dm_products category="sesiones" per_page="4" columns="2"]
```

Parámetros:

| Atributo | Descripción | Default |
|----------|-------------|---------|
| `category` | Slug de product_cat (requerido) | — |
| `per_page` | Número de productos | 12 |
| `columns` | Columnas de la grilla | 3 |
| `orderby` | Criterio de orden | `menu_order` |
| `order` | `ASC` o `DESC` | `ASC` |

---

## Arquitectura de páginas y menú recomendada

### Estructura de páginas

```
Cursos y Talleres  (página padre — sin shortcode, sirve de índice)
├── Cursos         → [dm_products category="cursos"]
└── Talleres       → [dm_products category="talleres"]

Recursos           → [dm_recursos]
Sesiones           → [dm_products category="sesiones"]
```

### Pasos de configuración completa

#### 1. Crear categorías de producto

En **WooCommerce → Productos → Categorías**, crear:

| Slug | Nombre | Uso |
|------|--------|-----|
| `recursos-gratis` | Recursos Gratuitos | Hub de recursos |
| `recursos-pagos` | Recursos de Pago | Hub de recursos |
| `cursos` | Cursos | Página Cursos |
| `talleres` | Talleres | Página Talleres |
| `sesiones` | Sesiones | Página Sesiones |
| `ansiedad` | Ansiedad | Tema / filtro |
| `autoestima` | Autoestima | Tema / filtro |
| `mindfulness` | Mindfulness | Tema / filtro |

#### 2. Crear páginas

En **Páginas → Nueva**:

| Título | Página padre | Contenido |
|--------|-------------|-----------|
| Cursos y Talleres | — | (texto introductorio o enlaces a hijas) |
| Cursos | Cursos y Talleres | `[dm_products category="cursos"]` |
| Talleres | Cursos y Talleres | `[dm_products category="talleres"]` |
| Recursos | — | `[dm_recursos]` |
| Sesiones | — | `[dm_products category="sesiones"]` |

#### 3. Añadir páginas al menú

En **Apariencia → Menús**:
- Agrega las páginas al menú principal.
- Para submenú: arrastra "Cursos" y "Talleres" debajo de "Cursos y Talleres" (o configura como items hijo en el menú).

#### 4. Asignar productos a categorías

Edita cada producto en WooCommerce y asigna:
- La categoría de tipo (`cursos`, `talleres`, `recursos-gratis`, `recursos-pagos`).
- El tema si corresponde (ej. `ansiedad`).

---

## Arquitectura de la HOME (dirección deseada — pendiente de implementar)

La home debe guiar al usuario hacia su necesidad. Estructura objetivo:

```
HOME
├── Meet Dani (se mantiene igual)
├── ¿Qué necesitas? (nueva sección — grid de 4 tarjetas con CTA)
│   ├── Recursos (→ /recursos)
│   ├── Cursos   (→ /cursos)
│   ├── Talleres (→ /talleres)
│   └── Sesiones (→ /sesiones)
├── Reviews (se mantiene igual)
└── Newsletter (se mantiene igual)
```

La sección "¿Qué necesitas?" se implementará en la Fase 2 como template part:
```php
get_template_part('template-parts/home/section', 'que-necesitas');
```

---

## Roadmap técnico

### Fase 1 — Auditoría técnica completa ✅
- Theme, plugins, estructura WooCommerce, LMS

### Fase 2 — Optimización HOME (pendiente)
- Nueva sección "¿Qué necesitas?"
- Jerarquía visual y CTAs claros

### Fase 3 — Optimización conversión ✅ (parcial)
- Hub de recursos con filtros: **implementado** (`[dm_recursos]`)
- Shortcodes de categorías: **implementado** (`[dm_products]`)
- Newsletter opt-in en checkout: **implementado**
- Estructura de páginas / IA de navegación: **documentado** (ver sección anterior)

### Fase 4 — Optimización de escuela online (pendiente)
- Estructura de cursos con Tutor LMS
- Acceso de usuarios y membresías
- Navegación LMS
