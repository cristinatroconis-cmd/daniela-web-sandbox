<?php

/**
 * Admin editable content for Home section “¿Qué necesitas?”
 *
 * Keeps the visual system in code/CSS, while exposing the copy in simple
 * metaboxes so the client can edit it safely from WP Admin.
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
    exit;
}

function dm_home_necesitas_front_defaults()
{
    return [
        'kicker' => '',
        'title'  => '¿Dónde estás parada hoy?',
        'title_image' => '',
        'lead'   => 'Elige el camino que mejor encaja con lo que estás viviendo ahora mismo.',
        'note'   => '',
        'image'  => '',
    ];
}

function dm_home_necesitas_front_fields_config()
{
    return [
        '_dm_home_necesitas_kicker' => [
            'label' => __('Texto pequeño superior', 'daniela-child'),
            'type'  => 'text',
            'placeholder' => 'Un espacio para ubicarte con calma',
        ],
        '_dm_home_necesitas_title' => [
            'label' => __('Título principal', 'daniela-child'),
            'type'  => 'text',
            'placeholder' => '¿Dónde estás parada hoy?',
        ],
        '_dm_home_necesitas_title_image' => [
            'label' => __('Imagen del título principal', 'daniela-child'),
            'type'  => 'media',
            'help'  => __('Opcional. Si eliges una imagen desde Medios, reemplaza el título escrito en el frontend. Si la dejas vacía, se usa el título de texto.', 'daniela-child'),
        ],
        '_dm_home_necesitas_lead' => [
            'label' => __('Bajada / descripción', 'daniela-child'),
            'type'  => 'textarea',
            'placeholder' => 'Elige el camino que mejor encaja con lo que estás viviendo ahora mismo.',
        ],
        '_dm_home_necesitas_note' => [
            'label' => __('Texto destacado inferior', 'daniela-child'),
            'type'  => 'textarea',
            'placeholder' => 'Opcional. Una frase corta que refuerce el mensaje de la izquierda.',
        ],
        '_dm_home_necesitas_image' => [
            'label' => __('Imagen decorativa opcional de la izquierda', 'daniela-child'),
            'type'  => 'media',
            'help'  => __('Opcional. Puedes elegir una ilustración o foto desde Medios para reforzar visualmente la columna izquierda.', 'daniela-child'),
        ],
        '__dm_home_heading_recursos' => [
            'label' => __('Tarjeta Home — Recursos', 'daniela-child'),
            'type'  => 'heading',
        ],
        '_dm_home_card_recursos_title' => [
            'label' => __('Título', 'daniela-child'),
            'type'  => 'text',
            'placeholder' => 'Quiero herramientas prácticas',
        ],
        '_dm_home_card_recursos_text' => [
            'label' => __('Texto corto', 'daniela-child'),
            'type'  => 'textarea',
            'placeholder' => 'PDFs, guías y registros para trabajar hoy mismo, a tu ritmo.',
        ],
        '_dm_home_card_recursos_image' => [
            'label' => __('Imagen de la tarjeta', 'daniela-child'),
            'type'  => 'media',
            'help'  => __('Selecciona una imagen desde Medios para esta tarjeta.', 'daniela-child'),
        ],
        '_dm_home_card_recursos_bg' => [
            'label' => __('Color de fondo de la tarjeta', 'daniela-child'),
            'type'  => 'color',
            'default' => '#ead2ac',
            'help'  => __('Elige el color de fondo para esta tarjeta.', 'daniela-child'),
        ],
        '__dm_home_heading_escuela' => [
            'label' => __('Tarjeta Home — Escuela', 'daniela-child'),
            'type'  => 'heading',
        ],
        '_dm_home_card_escuela_title' => [
            'label' => __('Título', 'daniela-child'),
            'type'  => 'text',
            'placeholder' => 'Quiero aprender de forma guiada',
        ],
        '_dm_home_card_escuela_text' => [
            'label' => __('Texto corto', 'daniela-child'),
            'type'  => 'textarea',
            'placeholder' => 'Formación online a tu ritmo o en vivo, en comunidad.',
        ],
        '_dm_home_card_escuela_image' => [
            'label' => __('Imagen de la tarjeta', 'daniela-child'),
            'type'  => 'media',
            'help'  => __('Selecciona una imagen desde Medios para esta tarjeta.', 'daniela-child'),
        ],
        '_dm_home_card_escuela_bg' => [
            'label' => __('Color de fondo de la tarjeta', 'daniela-child'),
            'type'  => 'color',
            'default' => '#ad8fb7',
            'help'  => __('Elige el color de fondo para esta tarjeta.', 'daniela-child'),
        ],
        '__dm_home_heading_servicios' => [
            'label' => __('Tarjeta Home — Servicios', 'daniela-child'),
            'type'  => 'heading',
        ],
        '_dm_home_card_servicios_title' => [
            'label' => __('Título', 'daniela-child'),
            'type'  => 'text',
            'placeholder' => 'Quiero acompañamiento profesional',
        ],
        '_dm_home_card_servicios_text' => [
            'label' => __('Texto corto', 'daniela-child'),
            'type'  => 'textarea',
            'placeholder' => 'Te ofrezco mis servicios de terapia.',
        ],
        '_dm_home_card_servicios_image' => [
            'label' => __('Imagen de la tarjeta', 'daniela-child'),
            'type'  => 'media',
            'help'  => __('Selecciona una imagen desde Medios para esta tarjeta.', 'daniela-child'),
        ],
        '_dm_home_card_servicios_bg' => [
            'label' => __('Color de fondo de la tarjeta', 'daniela-child'),
            'type'  => 'color',
            'default' => '#eaefbd',
            'help'  => __('Elige el color de fondo para esta tarjeta.', 'daniela-child'),
        ],
        '__dm_home_heading_temas' => [
            'label' => __('Tarjeta Home — Temas', 'daniela-child'),
            'type'  => 'heading',
        ],
        '_dm_home_card_temas_title' => [
            'label' => __('Título', 'daniela-child'),
            'type'  => 'text',
            'placeholder' => 'No sé bien qué necesito',
        ],
        '_dm_home_card_temas_text' => [
            'label' => __('Texto corto', 'daniela-child'),
            'type'  => 'textarea',
            'placeholder' => 'Cuéntame qué estás sintiendo y encuentra lo que mejor encaja.',
        ],
        '_dm_home_card_temas_image' => [
            'label' => __('Imagen de la tarjeta', 'daniela-child'),
            'type'  => 'media',
            'help'  => __('Selecciona una imagen desde Medios para esta tarjeta.', 'daniela-child'),
        ],
        '_dm_home_card_temas_bg' => [
            'label' => __('Color de fondo de la tarjeta', 'daniela-child'),
            'type'  => 'color',
            'default' => '#c97f72',
            'help'  => __('Elige el color de fondo para esta tarjeta.', 'daniela-child'),
        ],
    ];
}

function dm_home_necesitas_card_fields_config()
{
    return [
        '_dm_home_card_kicker' => [
            'label' => __('Texto pequeño de la tarjeta', 'daniela-child'),
            'type'  => 'text',
            'placeholder' => 'Opcional',
        ],
        '_dm_home_card_title' => [
            'label' => __('Título de la tarjeta en Home', 'daniela-child'),
            'type'  => 'text',
            'placeholder' => 'Quiero herramientas prácticas',
        ],
        '_dm_home_card_text' => [
            'label' => __('Texto corto de la tarjeta en Home', 'daniela-child'),
            'type'  => 'textarea',
            'placeholder' => 'Describe en 1–2 líneas qué encuentra la persona en esta sección.',
        ],
        '_dm_home_card_image' => [
            'label' => __('Imagen de la tarjeta en Home', 'daniela-child'),
            'type'  => 'media',
            'help'  => __('Opcional. Si eliges una imagen aquí, reemplaza la imagen por defecto en la tarjeta de Home.', 'daniela-child'),
        ],
        '_dm_home_card_bg' => [
            'label' => __('Color de fondo de la tarjeta en Home', 'daniela-child'),
            'type'  => 'color',
            'default' => '#f4f0eb',
            'help'  => __('Opcional. Si eliges un color aquí, reemplaza el color de fondo por defecto de la tarjeta.', 'daniela-child'),
        ],
    ];
}

function dm_home_necesitas_metabox_context($post)
{
    if (! ($post instanceof WP_Post) || $post->post_type !== 'page') {
        return '';
    }

    $front_id = (int) get_option('page_on_front');
    if ($front_id > 0 && (int) $post->ID === $front_id) {
        return 'front';
    }

    if (in_array((string) $post->post_name, ['recursos', 'escuela', 'servicios', 'temas'], true)) {
        return 'card';
    }

    return '';
}

add_action('add_meta_boxes_page', 'dm_home_necesitas_register_metabox');

function dm_home_necesitas_register_metabox($post)
{
    $context = dm_home_necesitas_metabox_context($post);
    if ($context === '') {
        return;
    }

    add_meta_box(
        'dm_home_necesitas_content',
        __('Home — sección “¿Qué necesitas?”', 'daniela-child'),
        'dm_home_necesitas_metabox_html',
        'page',
        'normal',
        'high'
    );
}

function dm_home_necesitas_metabox_html($post)
{
    $context = dm_home_necesitas_metabox_context($post);
    if ($context === '') {
        echo '<p class="description">' . esc_html__('Este bloque solo aparece en la Home y en las páginas destino del carrusel.', 'daniela-child') . '</p>';
        return;
    }

    wp_nonce_field('dm_home_necesitas_save', 'dm_home_necesitas_nonce');

    if ($context === 'front') {
        echo '<p class="description" style="margin-bottom:12px;">';
        echo esc_html__('Estos campos controlan la columna izquierda de la sección “¿Qué necesitas?” en la Home. El diseño sigue fijo en código para mantener consistencia visual.', 'daniela-child');
        echo '</p>';
        $fields = dm_home_necesitas_front_fields_config();
    } else {
        echo '<p class="description" style="margin-bottom:12px;">';
        echo esc_html__('Estos campos controlan cómo aparece esta página dentro del carrusel de la Home. Si los dejas vacíos, el theme usa el copy por defecto.', 'daniela-child');
        echo '</p>';
        $fields = dm_home_necesitas_card_fields_config();
    }

    foreach ($fields as $key => $field) {
        if ($field['type'] === 'heading') {
            echo '<hr style="margin:18px 0 12px;">';
            echo '<h3 style="margin:0 0 10px;">' . esc_html($field['label']) . '</h3>';
            continue;
        }

        $value = (string) get_post_meta($post->ID, $key, true);
        echo '<div style="margin-bottom:14px;">';
        echo '<label for="' . esc_attr($key) . '" style="font-weight:600;display:block;margin-bottom:4px;">' . esc_html($field['label']) . '</label>';

        if ($field['type'] === 'textarea') {
            echo '<textarea id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" rows="4" style="width:100%;">' . esc_textarea($value) . '</textarea>';
        } elseif ($field['type'] === 'media') {
            if (function_exists('dm_render_media_picker_field')) {
                dm_render_media_picker_field($key, $value, isset($field['help']) ? (string) $field['help'] : '');
            } else {
                echo '<input type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" style="width:100%;" />';
            }
        } elseif ($field['type'] === 'color') {
            $color_value = $value !== '' ? $value : (isset($field['default']) ? (string) $field['default'] : '#ffffff');
            echo '<input type="color" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($color_value) . '" style="width:72px;height:42px;padding:2px;border-radius:8px;" />';
            if (! empty($field['help'])) {
                echo '<span class="description" style="display:block;margin-top:6px;">' . esc_html($field['help']) . '</span>';
            }
        } else {
            echo '<input type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($field['placeholder']) . '" style="width:100%;" />';
        }
        echo '</div>';
    }
}

add_action('save_post_page', 'dm_home_necesitas_save_metabox', 10, 2);

function dm_home_necesitas_save_metabox($post_id, $post)
{
    if (
        ! isset($_POST['dm_home_necesitas_nonce']) ||
        ! wp_verify_nonce(sanitize_key($_POST['dm_home_necesitas_nonce']), 'dm_home_necesitas_save')
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $context = dm_home_necesitas_metabox_context($post);
    if ($context === '') {
        return;
    }

    $fields = $context === 'front'
        ? dm_home_necesitas_front_fields_config()
        : dm_home_necesitas_card_fields_config();

    foreach ($fields as $key => $field) {
        if ($field['type'] === 'heading') {
            continue;
        }

        $raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
        if ($field['type'] === 'textarea') {
            $value = sanitize_textarea_field((string) $raw);
        } elseif ($field['type'] === 'media') {
            $value = esc_url_raw(trim((string) $raw));
        } elseif ($field['type'] === 'color') {
            $value = sanitize_hex_color((string) $raw);
        } else {
            $value = sanitize_text_field((string) $raw);
        }

        if ($value !== '') {
            update_post_meta($post_id, $key, $value);
        } else {
            delete_post_meta($post_id, $key);
        }
    }
}

function dm_home_necesitas_get_front_content()
{
    $defaults = dm_home_necesitas_front_defaults();
    $front_id = (int) get_option('page_on_front');

    if ($front_id <= 0) {
        return $defaults;
    }

    return [
        'kicker' => (string) get_post_meta($front_id, '_dm_home_necesitas_kicker', true) ?: $defaults['kicker'],
        'title'  => (string) get_post_meta($front_id, '_dm_home_necesitas_title', true) ?: $defaults['title'],
        'title_image' => (string) get_post_meta($front_id, '_dm_home_necesitas_title_image', true) ?: $defaults['title_image'],
        'lead'   => (string) get_post_meta($front_id, '_dm_home_necesitas_lead', true) ?: $defaults['lead'],
        'note'   => (string) get_post_meta($front_id, '_dm_home_necesitas_note', true) ?: $defaults['note'],
        'image'  => (string) get_post_meta($front_id, '_dm_home_necesitas_image', true) ?: $defaults['image'],
    ];
}

function dm_home_necesitas_get_card_content($page_slug, array $defaults)
{
    $card = $defaults;
    $front_id = (int) get_option('page_on_front');

    if ($front_id > 0) {
        $card['title'] = (string) get_post_meta($front_id, '_dm_home_card_' . $page_slug . '_title', true) ?: ($card['title'] ?? '');
        $card['text']  = (string) get_post_meta($front_id, '_dm_home_card_' . $page_slug . '_text', true) ?: ($card['text'] ?? '');
        $card['image'] = (string) get_post_meta($front_id, '_dm_home_card_' . $page_slug . '_image', true) ?: ($card['image'] ?? '');
        $card['bg']    = (string) get_post_meta($front_id, '_dm_home_card_' . $page_slug . '_bg', true) ?: ($card['bg'] ?? '');
    }

    $page = get_page_by_path($page_slug, OBJECT, 'page');
    if ($page instanceof WP_Post) {
        $card['kicker'] = (string) get_post_meta($page->ID, '_dm_home_card_kicker', true) ?: ($card['kicker'] ?? '');
        $card['title']  = (string) get_post_meta($page->ID, '_dm_home_card_title', true) ?: ($card['title'] ?? get_the_title($page));
        $card['text']   = (string) get_post_meta($page->ID, '_dm_home_card_text', true) ?: ($card['text'] ?? '');
        $card['image']  = (string) get_post_meta($page->ID, '_dm_home_card_image', true) ?: ($card['image'] ?? '');
        $card['bg']     = (string) get_post_meta($page->ID, '_dm_home_card_bg', true) ?: ($card['bg'] ?? '');
        $card['url']    = get_permalink($page);
    }

    return $card;
}
