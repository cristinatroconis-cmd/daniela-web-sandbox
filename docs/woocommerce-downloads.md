# Flujo de Descargas — Guía de Referencia

**Proyecto:** Daniela Montes Psicóloga  
**Última actualización:** 2026-04-22

---

## Resumen ejecutivo

El sitio usa **un solo flujo de descarga** para productos gratuitos y de pago:

| Flujo | Productos | Sistema | Archivos |
|---|---|---|---|
| **WooCommerce nativo** | Productos descargables con precio = $0 o > $0 | Pedidos, permisos de descarga, email `customer_completed_order`, límites y expiración | `inc/woocommerce-checkout.php`, `inc/woocommerce-emails.php`, `woocommerce/emails/customer-completed-order.php` |

El flujo legacy `dm_freebie` fue retirado del child theme. Los recursos gratuitos **ya no usan** formularios tokenizados ni emails paralelos: pasan por carrito + checkout WooCommerce igual que el resto.

---

## 1. Flujo único — WooCommerce nativo

### Cómo funciona

1. El cliente añade el producto descargable al carrito y completa el checkout.
2. Si el pedido requiere pago, WooCommerce lo mueve hasta `completed` tras la confirmación del gateway.
3. Si el pedido es completamente gratuito, el child theme lo completa automáticamente al finalizar el checkout.
4. WooCommerce concede permisos de descarga al email del comprador.
5. WooCommerce envía el email **Pedido completado** (`customer_completed_order`) con el bloque CTA de descarga del child theme.
6. El cliente descarga el archivo desde el email o desde **Mi cuenta → Descargas**.

### Decisiones implementadas

- **Email de entrega único:** `customer_completed_order`.
- **Plantilla override activa:** `wp-content/themes/daniela-child/woocommerce/emails/customer-completed-order.php`.
- **Personalización visual:** `wp-content/themes/daniela-child/inc/email-tokens.php` + `wp-content/themes/daniela-child/inc/woocommerce-emails.php`.
- **Pedidos gratis:** se completan automáticamente para disparar el mismo correo de entrega.
- **Checkout gratis:** no solicita pago si todo el carrito es gratuito.
- **Thank-you page:** no muestra descargas inmediatas; la entrega se hace por correo.

### Qué ya no existe

- No hay formulario `[dm_freebie_form]` activo.
- No hay endpoint `?dm_freebie_token=` activo en el child theme.
- No hay email paralelo fuera de WooCommerce para recursos gratis.
- No hay settings funcionales de `dm_freebie_*` en el flujo actual.

---

## 2. Configuración recomendada en WP Admin

### WooCommerce → Ajustes → Productos → Productos descargables

| Opción | Valor recomendado | Por qué |
|---|---|---|
| **Método de descarga** | `Force downloads` | WooCommerce sirve el archivo y valida permisos sin exponer la URL real. |
| **Conceder acceso a productos descargables tras el pago** | `yes` | Los permisos deben generarse automáticamente. |
| **Requerir inicio de sesión** | `no` | El cliente debe poder descargar desde el email sin login. |

### En cada producto descargable

| Campo | Valor recomendado |
|---|---|
| **Virtual** | ✅ Activado |
| **Downloadable** | ✅ Activado |
| **Archivo(s)** | Subir a `woocommerce_uploads/` o a un directorio aprobado por WooCommerce |
| **Download limit** | `3` |
| **Download expiry** | `14` días |

### WooCommerce → Ajustes → Emails

- El email **Pedido completado** debe estar activo.
- El tipo de email debe seguir en `html`.
- El CTA de descarga se personaliza desde la sección **Emails de Descargables (General)** del settings page del child theme.

---

## 3. Archivos fuente de verdad

| Archivo | Responsabilidad |
|---|---|
| `wp-content/themes/daniela-child/inc/woocommerce-checkout.php` | Checkout gratis, auto-complete de pedidos gratis, ocultar descargas en thank-you, copy de entrega por correo |
| `wp-content/themes/daniela-child/inc/woocommerce-emails.php` | Defaults visuales, asunto/heading de completed, CTA de descarga, trazabilidad del envío automático/manual |
| `wp-content/themes/daniela-child/inc/email-tokens.php` | Tokens visuales compartidos entre frontend y emails |
| `wp-content/themes/daniela-child/woocommerce/emails/customer-completed-order.php` | Override HTML del email de entrega |
| `wp-content/themes/daniela-child/inc/admin-settings.php` | Ajustes de copy para el CTA de descarga en emails WooCommerce |

---

## 4. Cómo verificar que todo funciona

### A. Producto de pago

- [ ] Comprar un producto descargable de pago con el gateway configurado.
- [ ] Verificar que el pedido termina en `completed`.
- [ ] Verificar que llega el email **Pedido completado**.
- [ ] Verificar que el botón de descarga del email funciona.
- [ ] Verificar que el archivo aparece también en **Mi cuenta → Descargas**.

### B. Producto gratuito

- [ ] Añadir un producto descargable gratuito al carrito.
- [ ] Confirmar que checkout no pide pago.
- [ ] Confirmar que el pedido termina en `completed` automáticamente.
- [ ] Verificar que llega el mismo email **Pedido completado** con descarga.
- [ ] Verificar que no aparece descarga inmediata en la página de gracias.

### C. Trazabilidad

- [ ] Revisar en el pedido `_dm_customer_completed_email_sent`.
- [ ] Revisar en el pedido `_dm_customer_completed_email_last_source` (`automatic` o `manual`).
- [ ] Confirmar que el CTA del email usa la plantilla override del child theme.

---

## 5. Dónde NO tocar

- `inc/shortcodes-escuela.php` — Tutor LMS / Escuela.
- Lógica de membresías / suscripciones WooCommerce.
- Plugins de pago o Stripe a nivel de código, salvo correcciones explícitas del flujo de estados.
