# Flujo de Descargas — Guía de Referencia

**Proyecto:** Daniela Montes Psicóloga  
**Última actualización:** 2025

---

## Resumen ejecutivo

El sitio tiene **dos flujos de descarga completamente separados e independientes**:

| Flujo | Productos | Sistema | Archivos |
|---|---|---|---|
| **WooCommerce nativo** | Productos de pago (precio > $0) | Permisos de descarga WC, límites, expiración | `inc/woocommerce-checkout.php` |
| **Freebie tokenizado** | Productos gratuitos (precio = $0) | Token hex + tabla `dm_freebie_tokens` | `inc/freebie-download.php`, `inc/freebie-delivery.php` |

Ninguno de los dos flujos interfiere con el otro. El flujo freebie **no se ejecuta en el proceso de compra normal** (checkout, pago, emails de WooCommerce).

---

## 1. Flujo de pago — WooCommerce nativo

### Cómo funciona

1. El cliente añade el producto al carrito y completa el checkout (Stripe).
2. Stripe confirma el pago → WooCommerce marca el pedido como `processing` → `completed`.
3. WooCommerce concede automáticamente permisos de descarga al email del comprador.
4. WooCommerce envía el email "Pedido completado" con el **link de descarga protegido**.
5. El cliente descarga el archivo desde "Mi cuenta → Descargas" o desde el enlace del email.
6. WooCommerce valida: permisos activos, límite de descargas no superado, fecha de expiración no vencida.

### Configuración recomendada en WP Admin

#### WooCommerce → Ajustes → Productos → Productos descargables

| Opción | Valor recomendado | Por qué |
|---|---|---|
| **Método de descarga** | `Force downloads` | Más compatible. WC intermedia la descarga y valida permisos antes de servir el archivo. No expone la URL real. |
| **Acceso a descargas** | `Solo clientes que han accedido` | El cliente debe haber completado el pago. |
| **Requerir inicio de sesión** | Desactivado (sin cuentas para no-Escuela) | El cliente recibe el link por email. |

> **Nota para NGINX/Rocket.net:** Si cambias a `X-Accel-Redirect`, aplica primero las reglas de servidor descritas en la guía oficial de WooCommerce. Sin esas reglas, las descargas pueden fallar silenciosamente.

#### En cada producto descargable (WP Admin → Productos → editar)

| Campo | Valor recomendado |
|---|---|
| **Virtual** | ✅ Activado |
| **Downloadable** | ✅ Activado |
| **Archivo(s)** | Subir a `woocommerce_uploads/` (protegido de acceso público) |
| **Download limit** | `3` (equilibrio: permite re-descargar en otro dispositivo, limita abuso) |
| **Download expiry** | `14` días (o `7` si quieres más control) |

> **Por qué 3 descargas:** Un límite de 1 descarga genera muchos tickets de soporte ("cambié de móvil", "perdí el archivo"). Con 3, reduces abuso y mantienes buena UX.

#### WooCommerce → Ajustes → Cuentas y privacidad

| Opción | Valor |
|---|---|
| **Permitir pedidos sin cuenta** | ✅ Activado (para productos normales) |
| **Crear cuenta automáticamente** | Solo para ESCUELA (Tutor LMS) — no tocar en este PR |

#### WooCommerce → Ajustes → Emails

- El email **"Pedido completado"** debe estar activo. Es el que incluye el bloque de descargas.
- Verifica en "Personalizar" que el bloque de descargas aparece en el cuerpo del email.

---

## 2. Flujo freebie — Solo productos gratuitos (precio = $0)

### Cómo funciona

1. En la página del recurso gratuito aparece el shortcode `[dm_freebie_form product_id="X"]`.
2. El usuario introduce su email (y opcionalmente acepta el newsletter).
3. Se genera un **token hex de 64 caracteres** y se persiste en la tabla `{prefix}dm_freebie_tokens`.
4. Se envía un email con el link de descarga: `/?dm_freebie_token=<token>`.
5. El endpoint `?dm_freebie_token=` valida el token, incrementa el contador y entrega el archivo.
6. Límite: 10 descargas por token. Sin expiración por defecto.

### Archivos involucrados

| Archivo | Responsabilidad |
|---|---|
| `inc/freebie-download.php` | Shortcode `[dm_freebie_form]`, tabla DB, endpoint `?dm_freebie_token=`, email |
| `inc/freebie-delivery.php` | Endpoint alternativo `/recursos/recibir/?product_id=X`, token transient `?dm_token=` |
| `single-dm_recurso.php` | Muestra el formulario freebie para recursos con precio $0 |

### Aislamiento (garantías implementadas)

El flujo freebie **no puede ejecutarse para productos de pago**. Los siguientes guardas están en su lugar:

- `dm_freebie_form_shortcode()`: Si `price > 0`, muestra enlace al producto WC en lugar del formulario.
- `dm_freebie_process_request()`: Si `price > 0`, retorna `WP_Error` e impide generar el token.
- `dm_freebie_handle_download_request()`: Si el producto del token tiene `price > 0`, devuelve HTTP 403.
- `dm_freebie_handle_token_download()` (delivery.php): Si `price > 0`, invalida el transient y redirige al producto.
- `single-dm_recurso.php`: Muestra el formulario freebie solo si `price <= 0`; de lo contrario muestra el CTA de compra WooCommerce.

### El flujo freebie NO modifica

- El checkout de WooCommerce.
- Los emails nativos de WooCommerce (procesando, completado, descarga disponible).
- Los permisos de descarga (`woocommerce_downloadable_product_permissions`).
- Ningún hook `woocommerce_download_*`.

---

## 3. Cómo verificar que todo funciona (Testing checklist)

### A. Producto de pago — WooCommerce nativo

- [ ] Comprar un producto de pago con Stripe (modo test).
- [ ] Verificar que el pedido pasa a "Completado" automáticamente (o manualmente si falta configuración de Stripe).
- [ ] Verificar que llega el email "Pedido completado" con enlace de descarga.
- [ ] Clicar el enlace de descarga → archivo se descarga sin mostrar la URL de `/wp-content/uploads/`.
- [ ] Intentar descargar más veces que el límite configurado → WooCommerce debe bloquear con mensaje.
- [ ] Verificar en "Mi cuenta → Descargas" que aparece el archivo.

### B. Producto gratuito — flujo freebie

- [ ] En la página del recurso gratuito, verificar que aparece el formulario de email (no botón "Añadir al carrito").
- [ ] Enviar el formulario con un email válido → confirmar que llega el email con el link `?dm_freebie_token=`.
- [ ] Clicar el link → archivo se descarga.
- [ ] Intentar usar el mismo link más de 10 veces → mensaje de "límite alcanzado".

### C. Aislamiento (garantía de no-interferencia)

- [ ] Acceder a `/?dm_freebie_token=<token_válido_de_producto_gratuito>` → descarga funciona.
- [ ] Intentar poner `[dm_freebie_form product_id=X]` con un producto de pago → debe mostrar "Requiere compra" (no formulario).
- [ ] El endpoint de WooCommerce (`?download_file=...&order=...`) funciona independientemente.

---

## 4. Flujo de Stripe → WooCommerce (referencia)

Para que las descargas se liberen automáticamente tras el pago:

1. **WP Admin → WooCommerce → Ajustes → Pagos → Stripe**: Activado.
2. Stripe Webhook configurado para enviar `payment_intent.succeeded` al site.
3. WooCommerce Stripe cambia el pedido de `pending` → `processing` → (si virtual/downloadable) → `completed`.
4. Al llegar a `completed`, WooCommerce envía el email "Completado" y activa los links de descarga.

> Si el pedido se queda en `processing` y nunca pasa a `completed`, verificar en **WooCommerce → Ajustes → Productos** que "Completar pedidos virtuales automáticamente" está activado, o configurarlo desde el plugin de Stripe.

---

## 5. Dónde NO tocar

- `inc/shortcodes-escuela.php` — Tutor LMS / Escuela.
- Lógica de membresías / suscripciones WooCommerce.
- El plugin de Stripe (sin cambios de código, solo configuración).
