# WooCommerce DB Snapshot — staging admin changes (2026-04-21)

Propósito: dejar en el repo una referencia explícita de los cambios **DB-backed** hechos en staging vía WP Admin / runtime para WooCommerce, de modo que la promoción a producción no dependa de copiar staging a ciegas ni de memoria.

## Alcance
- Fuente: staging `https://v2vvroh9bv-staging.onrocket.site`
- Fecha de captura: `2026-04-21`
- Enfoque: descargas WooCommerce + email de pedido completado
- No incluye pedidos de prueba, permisos de descarga por pedido ni datos transaccionales temporales.

## Stack relevante en staging
- `woocommerce` `8.9.4`
- `woocommerce-gateway-stripe` `8.3.1`
- `woocommerce-memberships` `1.25.0`
- `woocommerce-subscriptions` `5.4.0`
- `woo-mailerlite` `3.1.13`
- `mailersend-official-smtp-integration` `1.0.3`

## Opciones WooCommerce validadas en staging
- `woocommerce_file_download_method = force`
- `woocommerce_downloads_grant_access_after_payment = yes`
- `woocommerce_downloads_require_login = no`
- `wc_downloads_approved_directories_mode = enabled`
- `woocommerce_customer_completed_order_settings`
  - `enabled = yes`
  - `email_type = html`
  - `additional_content = Gracias!!`

## Tablas / filas DB-backed relevantes

### Approved download directories
Tabla activa observada en runtime de staging:
- `wp_wc_product_download_directories`

Filas activas en staging al momento de la captura:
- `file:///home/v2vvxfo/public_html/wp-content/uploads/woocommerce_uploads/`
- `https://v2vvroh9bv.onrocket.site/wp-content/uploads/woocommerce_uploads/`
- `https://v2vvroh9bv-staging.onrocket.site/wp-content/uploads/2026/04/`
- `file:///home/v2vvxfo/public_html/v2vvroh9bv-staging.onrocket.site/wp-content/uploads/2026/04/`

## Regla de promoción a producción
No copiar estas filas de staging tal cual a producción.

Motivo:
- contienen hostnames/rutas específicas de staging;
- la tabla observada en runtime no coincidió con la tabla histórica asumida (`stgwp_wc_product_download_directories`);
- copiar pedidos/permisos de staging o directorios con URLs de staging puede romper descargas reales.

## Qué sí promover a producción
1. Las **opciones WooCommerce** listadas arriba.
2. Las filas equivalentes de `approved download directories`, pero adaptadas a:
   - hostname real de producción;
   - path real de producción;
   - carpeta actual de `uploads/YYYY/MM/` donde vivan los PDFs activos.
3. El código del child theme asociado a:
   - `inc/woocommerce-checkout.php`
   - `inc/woocommerce-emails.php`

## Qué NO promover desde staging
- Pedidos de prueba (`9395`, `9397`, etc.).
- Filas de `wp_woocommerce_downloadable_product_permissions` creadas en staging.
- Metas de trazabilidad temporales como `_dm_customer_completed_email_sent` en pedidos de prueba.
- URLs `v2vvroh9bv-staging.onrocket.site` o paths `v2vvroh9bv-staging.onrocket.site/wp-content/uploads/...`.

## Checklist mínima antes de tocar producción
1. Backup de producción.
2. Confirmar directorio real de uploads del PDF en producción.
3. Crear / habilitar filas equivalentes en `wp_wc_product_download_directories` para producción.
4. Verificar en producción:
   - `woocommerce_file_download_method = force`
   - email `customer_completed_order` habilitado
   - permisos de descarga generándose con `download_id` válido
5. Ejecutar una compra gratis controlada post-deploy y validar:
   - no aparece descarga en `order received` si esa sigue siendo la decisión UX;
   - sí llega el correo;
   - el link del correo funciona.