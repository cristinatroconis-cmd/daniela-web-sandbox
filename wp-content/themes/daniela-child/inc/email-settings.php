<?php

/**
 * Email Settings — Agrega opciones editables en WP Admin
 *
 * Registra opciones para personalizar los emails de WooCommerce directamente
 * desde WP Admin → WooCommerce → Ajustes → Correos electrónicos.
 *
 * Las opciones editables son:
 *   - Descargables: título, descripción, texto botón
 *   - Newsletter: habilitado, título, descripción, texto botón, URL
 *
 * @package daniela-child
 */

if (! defined('ABSPATH')) {
    exit;
}

// Hook into WooCommerce email settings to add our custom options.
add_filter('woocommerce_email_settings', 'dm_add_email_settings', 10, 1);

/**
 * Agrega opciones personalizadas de DM al tab de correos de WooCommerce.
 *
 * @param array $settings Opciones existentes de WooCommerce.
 * @return array Opciones extendidas.
 */
function dm_add_email_settings(array $settings): array
{
    $custom_settings = array(

        // === SECCIÓN: DESCARGAS ===
        array(
            'id'    => 'dm_section_downloads',
            'type'  => 'title',
            'title' => __('Descargables — Personalización', 'daniela-child'),
            'desc'  => __('Personaliza el bloque de descargas que aparece en los emails de pedidos completados.', 'daniela-child'),
        ),

        array(
            'id'       => 'dm_downloads_email_cta_title',
            'type'     => 'text',
            'title'    => __('Título del bloque de descarga', 'daniela-child'),
            'desc'     => __('Ej: "⬇️ Accede a tu descarga"', 'daniela-child'),
            'default'  => __('⬇️ Accede a tu descarga', 'daniela-child'),
            'css'      => 'width: 100%;',
            'desc_tip' => true,
        ),

        array(
            'id'       => 'dm_downloads_email_cta_note',
            'type'     => 'textarea',
            'title'    => __('Nota bajo los enlaces de descarga', 'daniela-child'),
            'desc'     => __('Texto pequeño informativo (ej: sobre límite de descargas)', 'daniela-child'),
            'default'  => __('Los enlaces de descarga tienen un límite de usos y tiempo de validez.', 'daniela-child'),
            'css'      => 'width: 100%; height: 80px;',
            'desc_tip' => true,
        ),

        array(
            'id'       => 'dm_downloads_email_button_text',
            'type'     => 'text',
            'title'    => __('Texto del botón descargar', 'daniela-child'),
            'desc'     => __('Ej: "Descargar recurso" o "Descargar %s" (usa %s para el nombre del producto)', 'daniela-child'),
            'default'  => __('Descargar recurso', 'daniela-child'),
            'css'      => 'width: 100%;',
            'desc_tip' => true,
        ),

        array(
            'id'   => 'dm_section_downloads_end',
            'type' => 'sectionend',
        ),

        // === SECCIÓN: NEWSLETTER ===
        array(
            'id'    => 'dm_section_newsletter',
            'type'  => 'title',
            'title' => __('Newsletter — Bloque en emails completados', 'daniela-child'),
            'desc'  => __('Configura el bloque de newsletter que aparece en los emails después de las descargas.', 'daniela-child'),
        ),

        array(
            'id'       => 'dm_newsletter_email_enabled',
            'type'     => 'checkbox',
            'title'    => __('Activar bloque de newsletter', 'daniela-child'),
            'label'    => __('Mostrar el bloque de suscripción en emails de pedidos completados', 'daniela-child'),
            'default'  => 'yes',
            'desc_tip' => false,
        ),

        array(
            'id'       => 'dm_newsletter_email_title',
            'type'     => 'text',
            'title'    => __('Título del bloque', 'daniela-child'),
            'desc'     => __('Ej: "¿Quieres recibir más recursos?"', 'daniela-child'),
            'default'  => __('¿Quieres recibir más recursos?', 'daniela-child'),
            'css'      => 'width: 100%;',
            'desc_tip' => true,
        ),

        array(
            'id'       => 'dm_newsletter_email_description',
            'type'     => 'textarea',
            'title'    => __('Descripción del bloque', 'daniela-child'),
            'desc'     => __('Texto que aparece bajo el título', 'daniela-child'),
            'default'  => __('Suscríbete a mi newsletter y recibe actualizaciones, tips exclusivos y nuevos recursos directamente en tu inbox.', 'daniela-child'),
            'css'      => 'width: 100%; height: 80px;',
            'desc_tip' => true,
        ),

        array(
            'id'       => 'dm_newsletter_email_button_text',
            'type'     => 'text',
            'title'    => __('Texto del botón', 'daniela-child'),
            'desc'     => __('Ej: "Suscribirme"', 'daniela-child'),
            'default'  => __('Suscribirme', 'daniela-child'),
            'css'      => 'width: 100%;',
            'desc_tip' => true,
        ),

        array(
            'id'          => 'dm_newsletter_email_link_url',
            'type'        => 'text',
            'title'       => __('URL del enlace / formulario', 'daniela-child'),
            'desc'        => __('URL a MailerLite form, newsletter page, o anchor (#dm-newsletter). Si está vacío, enlaza a #dm-newsletter en la home.', 'daniela-child'),
            'default'     => '',
            'css'         => 'width: 100%;',
            'placeholder' => 'https://... o #anchor',
            'desc_tip'    => true,
        ),

        array(
            'id'   => 'dm_section_newsletter_end',
            'type' => 'sectionend',
        ),
    );

    return array_merge($settings, $custom_settings);
}

/**
 * Sanitiza y valida las opciones de descargables y newsletter.
 *
 * Hook automático de WooCommerce: sanitize_option_{option_name}
 */
add_filter('sanitize_option_dm_downloads_email_cta_title', 'wp_kses_post');
add_filter('sanitize_option_dm_downloads_email_cta_note', 'wp_kses_post');
add_filter('sanitize_option_dm_downloads_email_button_text', 'sanitize_text_field');
add_filter('sanitize_option_dm_newsletter_email_enabled', 'wc_bool_to_string');
add_filter('sanitize_option_dm_newsletter_email_title', 'sanitize_text_field');
add_filter('sanitize_option_dm_newsletter_email_description', 'wp_kses_post');
add_filter('sanitize_option_dm_newsletter_email_button_text', 'sanitize_text_field');
add_filter('sanitize_option_dm_newsletter_email_link_url', 'esc_url_raw');
