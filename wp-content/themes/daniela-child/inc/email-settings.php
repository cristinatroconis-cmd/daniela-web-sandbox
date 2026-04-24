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

/**
 * IDs de emails cliente donde inyectamos ajustes DM.
 *
 * @return array<int, string>
 */
function dm_get_customer_email_setting_ids(): array
{
    return array(
        'customer_completed_order',
        'customer_invoice',
        'customer_on_hold_order',
        'customer_cancelled_order',
        'customer_refunded_order',
        'customer_note',
    );
}

foreach (dm_get_customer_email_setting_ids() as $email_id) {
    add_filter(
        'woocommerce_settings_api_form_fields_' . $email_id,
        'dm_add_email_form_fields_to_customer_email',
        20,
        1
    );
}

/**
 * Inserta un grupo de campos después de una clave existente.
 *
 * @param array<string, array<string, mixed>> $fields   Campos actuales.
 * @param string                              $after    Clave de referencia.
 * @param array<string, array<string, mixed>> $inserted Campos a insertar.
 * @return array<string, array<string, mixed>>
 */
function dm_insert_email_fields_after(array $fields, string $after, array $inserted): array
{
    $result = array();

    foreach ($fields as $key => $field) {
        $result[$key] = $field;

        if ($after === $key) {
            foreach ($inserted as $inserted_key => $inserted_field) {
                $result[$inserted_key] = $inserted_field;
            }
        }
    }

    if (! isset($fields[$after])) {
        foreach ($inserted as $inserted_key => $inserted_field) {
            $result[$inserted_key] = $inserted_field;
        }
    }

    return $result;
}

/**
 * Agrega ajustes DM dentro de la edición de cada email cliente.
 *
 * @param array<string, array<string, mixed>> $fields Campos del email activo.
 * @return array<string, array<string, mixed>>
 */
function dm_add_email_form_fields_to_customer_email(array $fields): array
{
    $custom_fields = array(
        'dm_downloads_section_title' => array(
            'title'       => __('Descargables — Personalización', 'daniela-child'),
            'type'        => 'title',
            'description' => __('Personaliza el bloque de descargas que aparece dentro de este email.', 'daniela-child'),
        ),
        'dm_downloads_email_cta_note' => array(
            'title'       => __('Nota bajo los enlaces de descarga', 'daniela-child'),
            'type'        => 'textarea',
            'description' => __('Texto pequeño informativo, por ejemplo sobre límite de descargas.', 'daniela-child'),
            'css'         => 'width:400px; height: 75px;',
            'default'     => (string) get_option('dm_downloads_email_cta_note', __('Los enlaces de descarga tienen un límite de usos y tiempo de validez.', 'daniela-child')),
            'desc_tip'    => true,
        ),
        'dm_downloads_email_button_text' => array(
            'title'       => __('Texto del botón descargar', 'daniela-child'),
            'type'        => 'text',
            'description' => __('Ej: "Descargar recurso" o "Descargar %s".', 'daniela-child'),
            'default'     => (string) get_option('dm_downloads_email_button_text', __('Descargar recurso', 'daniela-child')),
            'desc_tip'    => true,
        ),
        'dm_downloads_section_end' => array(
            'type' => 'sectionend',
        ),
        'dm_newsletter_section_title' => array(
            'title'       => __('Newsletter — Bloque dentro de este email', 'daniela-child'),
            'type'        => 'title',
            'description' => __('Configura el bloque de newsletter que aparece en este tipo de email.', 'daniela-child'),
        ),
        'dm_newsletter_email_enabled' => array(
            'title'   => __('Activar bloque de newsletter', 'daniela-child'),
            'type'    => 'checkbox',
            'label'   => __('Mostrar el bloque de suscripción en este email', 'daniela-child'),
            'default' => 'yes',
        ),
        'dm_newsletter_email_title' => array(
            'title'       => __('Título del bloque', 'daniela-child'),
            'type'        => 'text',
            'description' => __('Ej: "¿Quieres recibir más recursos?"', 'daniela-child'),
            'default'     => (string) get_option('dm_newsletter_email_title', __('¿Quieres recibir más recursos?', 'daniela-child')),
            'desc_tip'    => true,
        ),
        'dm_newsletter_email_description' => array(
            'title'       => __('Descripción del bloque', 'daniela-child'),
            'type'        => 'textarea',
            'description' => __('Texto que aparece bajo el título.', 'daniela-child'),
            'css'         => 'width:400px; height: 75px;',
            'default'     => (string) get_option('dm_newsletter_email_description', __('Suscríbete a mi newsletter y recibe actualizaciones, tips exclusivos y nuevos recursos directamente en tu inbox.', 'daniela-child')),
            'desc_tip'    => true,
        ),
        'dm_newsletter_email_button_text' => array(
            'title'       => __('Texto del botón', 'daniela-child'),
            'type'        => 'text',
            'description' => __('Ej: "Suscribirme"', 'daniela-child'),
            'default'     => (string) get_option('dm_newsletter_email_button_text', __('Suscribirme', 'daniela-child')),
            'desc_tip'    => true,
        ),
        'dm_newsletter_section_end' => array(
            'type' => 'sectionend',
        ),
    );

    return dm_insert_email_fields_after($fields, 'additional_content', $custom_fields);
}
