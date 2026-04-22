# WooCommerce — Overrides del child theme

**Proyecto:** Daniela Montes Psicóloga  
**Última actualización:** 2026-04-10

---

## 1. Overrides de templates de email

### ¿Qué son y cómo funcionan?

WooCommerce permite sobreescribir (override) sus plantillas HTML copiándolas
en la carpeta del tema activo. El child theme activo es `daniela-child`, por lo
que la ruta base de overrides es:

```
wp-content/themes/daniela-child/woocommerce/
```

Para los emails transaccionales, la sub-carpeta es:

```
wp-content/themes/daniela-child/woocommerce/emails/
```

Cuando WooCommerce necesita renderizar un email, busca primero en esa ruta del
tema/child theme antes de usar su plantilla interna. Si encuentra el archivo,
lo usa; si no, usa el suyo propio.

### Templates incluidos en este PR

| Archivo | Email | Destinatario |
|---|---|---|
| `customer-on-hold-order.php` | Pedido en espera | Cliente |
| `customer-completed-order.php` | Pedido completado | Cliente |
| `customer-refunded-order.php` | Pedido reembolsado (total o parcial) | Cliente |
| `customer-cancelled-order.php` | Pedido cancelado | Cliente |
| `customer-invoice.php` | Factura / reenvío de detalles | Cliente |
| `customer-note.php` | Nota de cliente añadida al pedido | Cliente |
| `admin-new-order.php` | Nuevo pedido | Admin |
| `admin-cancelled-order.php` | Pedido cancelado | Admin |
| `admin-failed-order.php` | Pago fallido | Admin |

### Estructura de cada override

Cada override usa exclusivamente los **hooks estándar de WooCommerce**. No
duplica lógica del plugin; se limita a:

1. `do_action('woocommerce_email_header', $email_heading, $email)` — cabecera.
2. Un párrafo de introducción adecuado al tipo de email.
3. `do_action('woocommerce_email_order_details', ...)` — tabla de pedido.
4. `do_action('woocommerce_email_order_meta', ...)` — metadatos del pedido.
5. `do_action('woocommerce_email_customer_details', ...)` — datos del cliente.
6. `do_action('woocommerce_email_footer', $email)` — pie.

Esta estructura garantiza que:
- Los estilos CSS inyectados por `inc/woocommerce-emails.php` (filtro
  `woocommerce_email_styles`) se aplican correctamente.
- El bloque CTA de descarga (hook `woocommerce_email_after_order_table` en
  `inc/woocommerce-emails.php`) se renderiza dentro de la tabla de pedido.
- Los subject/heading personalizados del email `customer_completed_order`
   siguen funcionando sin cambios.

### Cómo mantener los overrides al actualizar WooCommerce

Cuando WooCommerce publica una nueva versión:

1. **WP Admin → WooCommerce → Estado → Templates** muestra qué overrides
   están "desactualizados" (versión diferente a la del plugin).
2. Compara el template de `woocommerce/emails/<nombre>.php` del plugin
   (instalado en `wp-content/plugins/woocommerce/`) con el override en
   `daniela-child/woocommerce/emails/<nombre>.php`.
3. Como los overrides solo contienen hooks estándar, la mayoría de
   actualizaciones de WooCommerce **no requieren cambios** en los overrides.
   Solo es necesario actualizar si WooCommerce añade nuevas variables o
   acciones relevantes en la versión nueva.
4. Actualiza el comentario `@version` del override para que WooCommerce deje
   de marcarlo como desactualizado.

---

## 2. CSS de WooCommerce del child theme

### Archivo

```
wp-content/themes/daniela-child/style.css
```

### Cómo funciona la carga

El child theme encola `style.css` como `daniela-child-style` desde
`inc/assets.php`, y ahí vive también la capa WooCommerce activa. No hay un
archivo CSS WooCommerce separado en producción.

`style.css` sigue cargando después del CSS base del parent/theme stack del
sitio, por lo que la capa del child puede absorber la apariencia final de Woo,
Shoptimizer y plugins visibles sin abrir un segundo sistema visual.

### Cómo editar estilos

Abre `style.css` y añade tus reglas CSS dentro de la sección WooCommerce o del
scope real del componente (`body.woocommerce-cart`, `body.woocommerce-checkout`,
`#dm-cart-drawer`, etc.). El criterio es trabajar por superficies reales del
producto, no perseguir clases sueltas del ecosistema.

Antes de escribir una regla nueva, aplicar esta jerarquía:
1. Si el cambio es una decisión de marca reutilizable, crear o reutilizar el token en `style.css`.
2. Si el cambio es de superficie WooCommerce, consumir ese token dentro del scope Woo correspondiente en `style.css`.
3. Solo tocar templates WooCommerce o del parent si el problema no es visual sino estructural.

### Estado actual del CSS WooCommerce (2026-04-10)

El archivo ya cubre estas decisiones base:

| Área | Comportamiento actual |
|---|---|
| Fuente de verdad visual | `style.css` define tokens, primitives y superficies Woo reales del proyecto |
| Tipografía / color | hereda tokens del child theme (`--dm-color-*`) |
| Espaciado vertical | reutiliza `--dm-necesitas-pad-y` desde Home para que WooCommerce no se vea “pegado” |
| Botones / inputs | usan el mismo lenguaje visual del child theme |
| Carrito / checkout / mi cuenta | cajas con padding interno consistente (`--dm-woo-box-pad`) |
| Newsletter opt-in / nota de descarga | `.dm-newsletter-optin` y `.dm-checkout-download-note` se estilizan dentro del checkout |
| Drawer / mini-cart | `#dm-cart-drawer` absorbe el markup nativo de WooCommerce con scope propio |

### Política de overrides visuales

- Shoptimizer y WooCommerce pueden seguir cargando su CSS base, pero la apariencia final aprobada debe salir del child theme.
- No copiar templates del parent o de WooCommerce por razones solo cosméticas.
- El CSS del child debe ganar por dependencia de carga, tokens `--dm-*` y selectores específicos del layout real.
- Si aparece un valor visual hardcodeado que ya representa marca, moverlo a `style.css` antes de reutilizarlo.

### Mapa medio de superficies reales

| Superficie | Scope recomendado | Fuente de markup permitida |
|---|---|---|
| Drawer / mini-cart | `#dm-cart-drawer` | WooCommerce mini-cart + fragments |
| Carrito | `body.woocommerce-cart` | WooCommerce |
| Checkout | `body.woocommerce-checkout` | WooCommerce + plugins de pago/fields |
| Mi cuenta | `body.woocommerce-account` | WooCommerce |
| Notices globales | `.woocommerce-message`, `.woocommerce-info`, `.woocommerce-error` | WooCommerce / plugins |
| Formularios Woo | `.woocommerce form`, `.select2-container...` bajo scopes Woo | WooCommerce / plugins |
| Cards/listados de producto | `.woocommerce ul.products li.product` y wrappers DM | WooCommerce + child |
| Producto individual | `.woocommerce div.product` | WooCommerce |
| Header/cart triggers | `body.header-4 ... .site-header-cart`, `#dm-cart-drawer` | Shoptimizer + child |
| CTAs add-to-cart / added_to_cart | `.woocommerce a.button`, `.added_to_cart`, wrappers DM | WooCommerce |

### Checkout, localización y opt-in

Además del CSS, la UX WooCommerce del child theme hoy depende de estos archivos:

| Archivo | Función |
|---|---|
| `inc/woocommerce-checkout.php` | fuerza al español textos visibles clave de WooCommerce (`Checkout`, `Place order`, `Billing details`, etc.) |
| `inc/newsletter-optin.php` | añade el checkbox GDPR de newsletter en checkout y guarda el consentimiento en el pedido |
| `inc/cart-drawer.php` | unifica CTAs del mini-cart a **“Seguir comprando”** y **“Finalizar compra”** |
| `js/add-to-cart-popup.js` | mantiene el mismo copy “Finalizar compra” en el popup fallback |

---

## 3. Relación entre los overrides de email y `inc/woocommerce-emails.php`

El archivo `inc/woocommerce-emails.php` usa **filtros y acciones de WooCommerce**
(no templates PHP directos), por lo que funciona independientemente de si hay
overrides o no. Los overrides del child theme NO anulan la lógica de ese archivo:

| Componente | Mecanismo | Afectado por override |
|---|---|---|
| CSS email-safe | `woocommerce_email_styles` filter | No (filtro global) |
| Subject personalizado | `woocommerce_email_subject_customer_completed_order` filter | No |
| Heading personalizado | `woocommerce_email_heading_customer_completed_order` filter | No |
| Bloque CTA descarga | `woocommerce_email_after_order_table` action | **Sí** — los overrides llaman `do_action('woocommerce_email_order_details',...)` que incluye el hook internamente |

### Decisión actual

- El email de entrega de descargables vive en `customer-completed-order.php`.
- `customer_processing_order` puede seguir existiendo en WooCommerce como email de estado, pero ya no forma parte de la personalización de entrega del child theme.

El bloque CTA requiere que `woocommerce_email_after_order_table` se dispare
dentro del renderizado del pedido. Los overrides garantizan esto llamando
`woocommerce_email_order_details`, que es quien dispara ese hook interno.
