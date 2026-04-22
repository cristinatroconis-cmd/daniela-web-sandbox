<?php

/**
 * Admin Settings — MailerLite Integration & Newsletter Opt-In
 *
 * Defines DM_Settings_Page, a WooCommerce settings sub-page that allows
 * configuring:
 *   - Enable/disable MailerLite API fallback
 *   - MailerLite API key (masked)
 *   - Default subscriber group ID
 *   - Optional tag group IDs: buyer, resource-buyer, course-buyer
 *   - Opt-in checkbox label (shown to customer at checkout)
 *
 * This file is loaded from functions.php inside the
 * `woocommerce_get_settings_pages` filter, ensuring WC_Settings_Page is
 * already defined when this class is declared.
 *
 * @package daniela-child
 */

if (! defined('ABSPATH')) {
	exit;
}

// Guard: only define once.
if (! class_exists('DM_Settings_Page')) :

	/**
	 * WooCommerce Settings API sub-page for DM Newsletter / MailerLite config.
	 */
	class DM_Settings_Page extends WC_Settings_Page
	{ // phpcs:ignore

		/**
		 * Constructor — set id and label.
		 */
		public function __construct()
		{
			$this->id    = 'dm_newsletter';
			$this->label = __('DM Newsletter', 'daniela-child');
			parent::__construct();
		}

		/**
		 * Return settings fields.
		 *
		 * @return array
		 */
		public function get_settings()
		{
			return apply_filters(
				'woocommerce_get_settings_' . $this->id,
				array(
					// ---- Section: Opt-In ----
					array(
						'title' => __('Checkout Opt-In', 'daniela-child'),
						'type'  => 'title',
						'id'    => 'dm_newsletter_optin_section',
					),
					array(
						'title'       => __('Texto del checkbox', 'daniela-child'),
						'desc'        => __('Mensaje de consentimiento mostrado al cliente en el checkout.', 'daniela-child'),
						'id'          => 'dm_newsletter_optin_label',
						'type'        => 'textarea',
						'css'         => 'width:100%; height:80px;',
						'default'     => __('Acepto recibir recursos y novedades de Daniela Montes Psicóloga por email. Puedo darme de baja en cualquier momento.', 'daniela-child'),
						'desc_tip'    => true,
						'autoload'    => false,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'dm_newsletter_optin_section',
					),

					// ---- Section: Downloadable Email Copy (Woo completed order) ----
					array(
						'title' => __('Emails de Descargables (General)', 'daniela-child'),
						'type'  => 'title',
						'id'    => 'dm_downloads_email_copy_section',
					),
					array(
						'title'       => __('Texto del botón', 'daniela-child'),
						'desc'        => __('Etiqueta principal de descarga mostrada en el email Pedido completado.', 'daniela-child'),
						'id'          => 'dm_downloads_email_button_text',
						'type'        => 'text',
						'css'         => 'width:300px;',
						'default'     => __('Descargar recurso', 'daniela-child'),
						'placeholder' => __('Descargar recurso', 'daniela-child'),
						'desc_tip'    => true,
						'autoload'    => false,
					),
					array(
						'title'       => __('Título CTA en correos WooCommerce', 'daniela-child'),
						'desc'        => __('Texto mostrado encima de los botones de descarga en el email Pedido completado.', 'daniela-child'),
						'id'          => 'dm_downloads_email_cta_title',
						'type'        => 'text',
						'css'         => 'width:420px;',
						'default'     => __('⬇️ Accede a tu descarga', 'daniela-child'),
						'placeholder' => __('⬇️ Accede a tu descarga', 'daniela-child'),
						'desc_tip'    => true,
						'autoload'    => false,
					),
					array(
						'title'       => __('Nota CTA en correos WooCommerce', 'daniela-child'),
						'desc'        => __('Texto pequeño mostrado debajo de los botones de descarga.', 'daniela-child'),
						'id'          => 'dm_downloads_email_cta_note',
						'type'        => 'text',
						'css'         => 'width:420px;',
						'default'     => __('Los enlaces de descarga tienen un límite de usos y tiempo de validez.', 'daniela-child'),
						'placeholder' => __('Los enlaces de descarga tienen un límite de usos y tiempo de validez.', 'daniela-child'),
						'desc_tip'    => true,
						'autoload'    => false,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'dm_downloads_email_copy_section',
					),

					// ---- Section: MailerLite API Fallback ----
					array(
						'title' => __('MailerLite API (fallback)', 'daniela-child'),
						'desc'  => __('Usar solo si el plugin oficial de MailerLite para WooCommerce no gestiona el opt-in desde este checkbox. Si el plugin oficial ya lo hace, dejar desactivado.', 'daniela-child'),
						'type'  => 'title',
						'id'    => 'dm_mailerlite_section',
					),
					array(
						'title'   => __('Activar API fallback', 'daniela-child'),
						'desc'    => __('Habilitar integración directa con la API de MailerLite desde el tema hijo.', 'daniela-child'),
						'id'      => 'dm_mailerlite_fallback_enabled',
						'type'    => 'checkbox',
						'default' => 'no',
					),
					array(
						'title'       => __('API Key de MailerLite', 'daniela-child'),
						'desc'        => __('Se guarda encriptada. No compartas esta clave.', 'daniela-child'),
						'id'          => 'dm_mailerlite_api_key',
						'type'        => 'password',
						'css'         => 'width:400px;',
						'desc_tip'    => true,
						'autoload'    => false,
					),
					array(
						'title'    => __('ID del grupo por defecto', 'daniela-child'),
						'desc'     => __('ID numérico del grupo de MailerLite donde se suscribirán los compradores.', 'daniela-child'),
						'id'       => 'dm_mailerlite_group_id',
						'type'     => 'text',
						'css'      => 'width:200px;',
						'desc_tip' => true,
						'autoload' => false,
					),

					// ---- Tags sub-section ----
					array(
						'title' => __('Tags opcionales (IDs de grupos en MailerLite)', 'daniela-child'),
						'type'  => 'title',
						'id'    => 'dm_mailerlite_tags_section',
					),
					array(
						'title'    => __('Tag: buyer', 'daniela-child'),
						'desc'     => __('ID de grupo para todos los compradores.', 'daniela-child'),
						'id'       => 'dm_mailerlite_tag_buyer',
						'type'     => 'text',
						'css'      => 'width:200px;',
						'desc_tip' => true,
						'autoload' => false,
					),
					array(
						'title'    => __('Tag: resource-buyer', 'daniela-child'),
						'desc'     => __('ID de grupo para compradores de recursos.', 'daniela-child'),
						'id'       => 'dm_mailerlite_tag_resource_buyer',
						'type'     => 'text',
						'css'      => 'width:200px;',
						'desc_tip' => true,
						'autoload' => false,
					),
					array(
						'title'    => __('Tag: course-buyer', 'daniela-child'),
						'desc'     => __('ID de grupo para compradores de cursos/talleres.', 'daniela-child'),
						'id'       => 'dm_mailerlite_tag_course_buyer',
						'type'     => 'text',
						'css'      => 'width:200px;',
						'desc_tip' => true,
						'autoload' => false,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'dm_mailerlite_tags_section',
					),
				)
			);
		}

		/**
		 * Save settings — delegate to WC Settings API.
		 */
		public function save()
		{
			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields($settings);
		}
	}

endif; // class_exists
