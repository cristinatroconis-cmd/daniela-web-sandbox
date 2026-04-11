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
| `customer-processing-order.php` | Pedido en proceso | Cliente |
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
- Los subject/heading personalizados (filtros
  `woocommerce_email_subject_*` y `woocommerce_email_heading_*`) siguen
  funcionando sin cambios.

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
wp-content/themes/daniela-child/assets/css/woocommerce.css
```

### Cómo funciona el enqueue

El enqueue está en `inc/assets.php` y se ejecuta en `wp_enqueue_scripts`
(prioridad 25). Se carga **solo en páginas WooCommerce**:

```php
if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
    wp_enqueue_style( 'daniela-child-woocommerce', ... );
}
```

La condición cubre:
- `is_woocommerce()` — tienda, archivo de productos, página de producto individual.
- `is_cart()` — carrito (incluyendo páginas con shortcode `[woocommerce_cart]`).
- `is_checkout()` — checkout.
- `is_account_page()` — Mi cuenta.

### Dependencia de carga

El handle `daniela-child-woocommerce` declara `daniela-child-style` como
dependencia, garantizando que el CSS de WooCommerce del child theme se carga
**después** del CSS base del child theme y **después** del CSS de Shoptimizer,
permitiendo sobrescribir cualquier estilo del tema padre.

### Cómo editar estilos

Abre `assets/css/woocommerce.css` y añade tus reglas CSS dentro de la sección
correspondiente (carrito, checkout, tienda, etc.). El archivo tiene comentarios
de sección para orientarte. Usa selectores con prefijo `.woocommerce` o
`body.woocommerce-cart` etc. para limitar el alcance y evitar conflictos.

### Estado actual del CSS WooCommerce (2026-04-10)

El archivo ya cubre estas decisiones base:

| Área | Comportamiento actual |
|---|---|
| Tipografía / color | hereda tokens del child theme (`--dm-color-*`) |
| Espaciado vertical | reutiliza `--dm-necesitas-pad-y` desde Home para que WooCommerce no se vea “pegado” |
| Botones / inputs | usan el mismo lenguaje visual del child theme |
| Carrito / checkout / mi cuenta | cajas con padding interno consistente (`--dm-woo-box-pad`) |
| Newsletter opt-in | `.dm-newsletter-optin` se estiliza dentro del checkout |

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
| Subject personalizado | `woocommerce_email_subject_*` filter | No |
| Heading personalizado | `woocommerce_email_heading_*` filter | No |
| Bloque CTA descarga | `woocommerce_email_after_order_table` action | **Sí** — los overrides llaman `do_action('woocommerce_email_order_details',...)` que incluye el hook internamente |

El bloque CTA requiere que `woocommerce_email_after_order_table` se dispare
dentro del renderizado del pedido. Los overrides garantizan esto llamando
`woocommerce_email_order_details`, que es quien dispara ese hook interno.
