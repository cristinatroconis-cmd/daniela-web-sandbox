# Mapa de Campos de Email (Completed Order)

Este archivo resume exactamente de donde sale cada campo que puede aparecer en el email.

## 1) Campos nativos de WooCommerce (arriba del admin)

Fuente: pantalla de edicion del email `customer_completed_order` (Woo core).

- ID: `subject`
  - Nombre en admin: Subject
  - Uso: asunto del correo enviado.

- ID: `heading`
  - Nombre en admin: Email heading
  - Uso: titulo principal del email en el header de Woo.

- ID: `additional_content`
  - Nombre en admin: Additional content
  - Uso: texto adicional de Woo (segun template/footer de Woo).

## 2) Campos inyectados por el child theme (metabox Descargables)

Fuente de definicion de campos:
- `wp-content/themes/daniela-child/inc/email-settings.php`
- Funcion: `dm_add_email_form_fields_to_customer_email()`

IDs + nombres en admin:

- ID: `dm_downloads_email_cta_note`
  - Nombre en admin: Nota bajo los enlaces de descarga
  - Se imprime en: texto pequeno debajo del boton CTA.

- ID: `dm_downloads_email_button_text`
  - Nombre en admin: Texto del boton descargar
  - Se imprime en: texto del boton CTA de descarga.

Nota: los campos `dm_downloads_email_paragraph` y `dm_downloads_email_farewell` ya no existen en el admin.
Nota: el campo de titulo de descarga (`dm_downloads_email_cta_title`) ya no existe en el admin.
Nota: el texto de despedida ahora debe ir en el campo nativo de Woo `additional_content`.

## 3) Campos inyectados por el child theme (metabox Newsletter)

Fuente de definicion de campos:
- `wp-content/themes/daniela-child/inc/email-settings.php`
- Funcion: `dm_add_email_form_fields_to_customer_email()`

IDs + nombres en admin:

- ID: `dm_newsletter_email_enabled`
  - Nombre en admin: Activar bloque de newsletter
  - Uso: mostrar/ocultar bloque newsletter.

- ID: `dm_newsletter_email_title`
  - Nombre en admin: Titulo del bloque
  - Se imprime en: titulo del bloque newsletter.

- ID: `dm_newsletter_email_description`
  - Nombre en admin: Descripcion del bloque
  - Se imprime en: texto del bloque newsletter.

- ID: `dm_newsletter_email_button_text`
  - Nombre en admin: Texto del boton
  - Se imprime en: boton CTA newsletter.

Nota: `dm_newsletter_email_link_url` ya no existe en el admin.
El boton de newsletter usa un endpoint one-click del sitio que intenta suscribir al cliente en MailerLite y muestra resultado.

## 4) Donde se renderiza cada bloque en el email completed

- Template que arma el email completed:
  - `wp-content/themes/daniela-child/woocommerce/emails/customer-completed-order.php`
  - Imprime `additional_content` arriba del bloque de Descargables.

- Render de Descarga:
  - `wp-content/themes/daniela-child/inc/woocommerce-emails.php`
  - Funcion: `dm_render_cta_block()`

- Render de Newsletter:
  - `wp-content/themes/daniela-child/inc/woocommerce-emails.php`
  - Funcion: `dm_render_newsletter_block()`

- Endpoint one-click de newsletter (suscripcion + mensaje):
  - `wp-content/themes/daniela-child/inc/newsletter-optin.php`
  - Funciones: `dm_newsletter_get_email_subscribe_url()`, `dm_newsletter_email_subscribe_endpoint()`

## 5) Estilos del email (tokens de root)

Fuente principal de estilos email-safe:
- `wp-content/themes/daniela-child/inc/woocommerce-emails.php`
- Filtro: `woocommerce_email_styles`

Fuente de tokens (extraidos de `:root` en `style.css`):
- `wp-content/themes/daniela-child/inc/email-tokens.php`
- Funcion: `dm_get_email_tokens()`

Reglas activas:
- Contenedor principal del email usa estilo card:
  - `background: var(--dm-color-bg-card)`
  - `border: 1px solid var(--dm-color-border)`
  - `border-radius: var(--dm-radius)`
  - `box-shadow: var(--dm-shadow)`
- Header usa color primario de marca (`--dm-brand-primary`).
- Fuente de titulo del header y headings de bloque: `--dm-brand-font-heading-family` (Abril Fatface).
- Texto de `additional_content`, `dm_downloads_email_cta_note` y textos secundarios: `--dm-brand-text-muted`.
- Botones (CTA descarga/newsletter y `.button` de Woo): token de boton primario (`--dm-btn-primary-bg`).

Nota tecnica importante:
- Se corrigio cache de tokens para evitar valores `var(--dm-...)` sin resolver en email.
- Cache key actual: `dm_email_tokens_v2`.
- Validacion de cache: si faltan llaves requeridas o hay `var(...)` sin resolver, se regenera.

## 6) IDs de email donde se inyectan estos campos

Fuente:
- `wp-content/themes/daniela-child/inc/email-settings.php`
- Funcion: `dm_get_customer_email_setting_ids()`

Lista:
- `customer_completed_order`
- `customer_invoice`
- `customer_on_hold_order`
- `customer_cancelled_order`
- `customer_refunded_order`
- `customer_note`
