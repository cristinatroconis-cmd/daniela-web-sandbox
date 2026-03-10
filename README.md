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

## Arquitectura de información (IA) — hub pages

### Hub Recursos (`/recursos/`)
Hub padre. Aparece en la navegación del header con flyout a sus hijos.

| Ruta | Shortcode | Categoría WooCommerce |
|---|---|---|
| `/recursos/gratis/` | `[dm_recursos_gratis]` | `recursos-gratis` |
| `/recursos/pagos/`  | `[dm_recursos_pagos]`  | `recursos-pagos` |
| `/recursos/temas/`  | `[dm_recursos_temas]`  | `recursos-gratis` + `recursos-pagos` (filtrado por `product_tag`) |

### Hub Escuela (`/escuela/`)
Hub padre. Aparece en la navegación del header con flyout a sus hijos.

| Ruta | Shortcode | Categoría WooCommerce |
|---|---|---|
| `/escuela/cursos/`   | `[dm_escuela_cursos]`   | `cursos` |
| `/escuela/talleres/` | `[dm_escuela_talleres]` | `talleres` |

---

## Categorías WooCommerce requeridas

Crear las siguientes categorías de producto en **WooCommerce → Productos → Categorías**:

| Nombre | Slug | Descripción |
|---|---|---|
| Recursos Gratis | `recursos-gratis` | PDFs gratuitos (productos a $0) |
| Recursos Pagos  | `recursos-pagos`  | PDFs de pago (productos descargables) |
| Cursos          | `cursos`          | Cursos online evergreen |
| Talleres        | `talleres`        | Talleres con fecha/evento |

---

## Temas (product_tag)

Los "temas" usan **WooCommerce product tags** (`product_tag`), no categorías.

- Dani puede crear/editar tags desde **WooCommerce → Productos → Etiquetas**.
- Los chips en `/recursos/temas/` solo muestran etiquetas que tengan ≥ 1 producto
  en `recursos-gratis` o `recursos-pagos`.
- Orden de chips: por popularidad (número de recursos con esa etiqueta), descendente;
  empate alfabético.
- Filtro sin JavaScript: `?tema=<slug>` en la querystring.

---

## Características implementadas en el child theme

### Shortcodes de listado
Todos renderizan un grid de tarjetas con:
- Imagen, título (enlace al producto), extracto corto, badge de precio.
- CTA: **"Agregar al carrito"** (con soporte AJAX de WooCommerce).

### Enlace "Volver" en página de producto individual
- Aparece automáticamente sobre el producto.
- Prioridad: `?dm_back=` (set por los shortcodes) → referer del navegador → `/recursos/`.
- En `/recursos/temas/` el parámetro `?tema=` se preserva en el enlace de vuelta.

### Redirección carrito → checkout (carrito gratuito)
- Si el carrito no está vacío **y** el total es $0, se redirige automáticamente
  de la página del carrito al checkout, eliminando un paso innecesario.

### Opt-in newsletter en checkout
- Checkbox no pre-marcado bajo las notas del pedido.
- El consentimiento se guarda en el meta `_dm_newsletter_optin` (`yes`/`no`).
- Listo para conectar a MailerLite en una fase posterior.

---

## Pasos de configuración en WordPress

1. **Crear las categorías** de producto listadas arriba.
2. **Crear las etiquetas** (temas) en WooCommerce → Etiquetas.
3. **Crear las páginas** con los slugs correctos:
   - `/recursos/` — página padre, contenido libre (enlace o intro)
   - `/recursos/gratis/` — pegar shortcode `[dm_recursos_gratis]`
   - `/recursos/pagos/` — pegar shortcode `[dm_recursos_pagos]`
   - `/recursos/temas/` — pegar shortcode `[dm_recursos_temas]`
   - `/escuela/` — página padre, contenido libre
   - `/escuela/cursos/` — pegar shortcode `[dm_escuela_cursos]`
   - `/escuela/talleres/` — pegar shortcode `[dm_escuela_talleres]`
4. **Menú del header**: añadir `/recursos/` y `/escuela/` con sus hijos como submenú
   (WordPress → Apariencia → Menús).
5. **Productos**: marcar los PDFs como *Descargable* en WooCommerce, asignarles
   categoría y etiquetas.

---

## Roadmap técnico

### Fase 1 — Auditoría técnica completa ✓ (en curso)
- theme
- plugins
- estructura WooCommerce
- LMS

### Fase 2 — Optimización HOME
- nueva sección "¿Qué necesitas?"
- jerarquía visual
- CTAs claros

### Fase 3 — Optimización conversión ✓ (parcial)
- [x] Checkout opt-in newsletter
- [x] Redirección automática carrito gratuito → checkout
- [ ] Thank-you page personalizada con acceso a descarga
- [ ] Email "Tu descarga está lista"
- [ ] Bundles
- [ ] Automatizaciones MailerLite

### Fase 4 — Hub Recursos + Escuela ✓ (implementado)
- [x] Shortcodes de listado por categoría
- [x] Chips filtro por `product_tag` con cache
- [x] Enlace "Volver" contextual en producto individual
- [x] CTA "Agregar al carrito" en todas las tarjetas

### Fase 5 — Optimización de escuela online
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