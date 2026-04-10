<?php

/**
 * Freebie Delivery — Link tokenizado + límite de descargas.
 *
 * Flujo:
 *   1. Shortcode [dm_freebie_form product_id=X] renderiza formulario
 *      (email + checkbox opt-in newsletter).
 *   2. Al enviar, genera token por (email, product_id), lo persiste en
 *      la tabla custom dm_freebie_tokens con expiración y contador.
 *   3. Envía email al usuario con el link único de descarga.
 *   4. Endpoint ?dm_freebie_token=<token> valida el token, incrementa
 *      contador (máx 10) y entrega el archivo.
 *   5. Integra opt-in con MailerLite hook o API fallback existente.
 *
 * Tabla: {prefix}dm_freebie_tokens
 *   token        VARCHAR(64) PK
 *   email        VARCHAR(200)
 *   product_id   BIGINT
 *   created_at   DATETIME
 *   expires_at   DATETIME  (NULL = sin expiración)
 *   download_count INT  DEFAULT 0
 *   max_downloads  INT  DEFAULT 10
 *   newsletter_optin TINYINT DEFAULT 0
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
	exit;
}

// =============================================================================
// INSTALACIÓN DE TABLA
// =============================================================================

register_activation_hook(__FILE__, 'dm_freebie_install_table');
add_action('init', 'dm_freebie_maybe_install_table');

/**
 * Crea la tabla dm_freebie_tokens si no existe.
 * Llamado en init también para garantizar que la tabla exista en nuevas
 * instalaciones donde el hook de activación no se disparó.
 */
function dm_freebie_maybe_install_table()
{
	if (get_option('dm_freebie_table_version') !== '1.1') {
		dm_freebie_install_table();
	}
}

function dm_freebie_install_table()
{
	global $wpdb;

	$table_name      = $wpdb->prefix . 'dm_freebie_tokens';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
		token         VARCHAR(64)  NOT NULL,
		email         VARCHAR(200) NOT NULL,
		product_id    BIGINT(20)   NOT NULL,
		created_at    DATETIME     NOT NULL,
		expires_at    DATETIME     DEFAULT NULL,
		download_count INT          NOT NULL DEFAULT 0,
		max_downloads  INT          NOT NULL DEFAULT 10,
		newsletter_optin TINYINT(1) NOT NULL DEFAULT 0,
		PRIMARY KEY  (token),
		KEY email (email),
		KEY product_id (product_id)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);

	update_option('dm_freebie_table_version', '1.1');
}

// =============================================================================
// SHORTCODE — Formulario de solicitud de freebie
// =============================================================================

add_shortcode('dm_freebie_form', 'dm_freebie_form_shortcode');

/**
 * Renderiza el formulario de solicitud del freebie.
 *
 * Atributos:
 *   product_id  (requerido) — ID del producto WC.
 *   title       (opcional)  — Texto del encabezado del formulario.
 *   button_text (opcional)  — Texto del botón.
 *   action_url  (opcional)  — URL de action del formulario.
 *
 * @param array $atts
 * @return string HTML del formulario o mensaje de error/confirmación.
 */
function dm_freebie_form_shortcode($atts)
{
	$atts = shortcode_atts(
		[
			'product_id'  => 0,
			'title'       => __('Recibe este recurso gratis en tu correo', 'daniela-child'),
			'button_text' => __('Enviarme el recurso', 'daniela-child'),
			'action_url'  => '',
		],
		$atts,
		'dm_freebie_form'
	);

	$product_id = absint($atts['product_id']);

	if (! $product_id) {
		return '<p class="dm-freebie-error">' .
			esc_html__('Recurso no especificado.', 'daniela-child') .
			'</p>';
	}

	// Comprobar que el producto existe y es gratuito (precio = $0).
	// El formulario freebie NO debe mostrarse para productos de pago.
	if (function_exists('wc_get_product')) {
		$product = wc_get_product($product_id);
		if (! $product) {
			return '<p class="dm-freebie-error">' .
				esc_html__('Recurso no disponible.', 'daniela-child') .
				'</p>';
		}
		if ((float) $product->get_price() > 0.0) {
			// Producto de pago → redirigir a la página del producto WooCommerce.
			return '<p class="dm-freebie-error">' .
				sprintf(
					wp_kses(
						/* translators: %s: product permalink */
						__('Este recurso requiere compra. <a href="%s">Ver producto</a>.', 'daniela-child'),
						array('a' => array('href' => array()))
					),
					esc_url(get_permalink($product_id))
				) . '</p>';
		}
	}

	// Procesar envío del formulario.
	$message = '';
	$success = false;

	if (
		isset($_POST['dm_freebie_product_id']) &&
		(int) $_POST['dm_freebie_product_id'] === $product_id &&
		isset($_POST['dm_freebie_nonce']) &&
		wp_verify_nonce(sanitize_key($_POST['dm_freebie_nonce']), 'dm_freebie_request_' . $product_id)
	) {
		$email          = sanitize_email(wp_unslash($_POST['dm_freebie_email'] ?? ''));
		$newsletter_opt = isset($_POST['dm_freebie_newsletter']) ? 1 : 0;

		if (! is_email($email)) {
			$message = '<p class="dm-freebie-error">' .
				esc_html__('Por favor introduce un email válido.', 'daniela-child') .
				'</p>';
		} else {
			$result = dm_freebie_process_request($email, $product_id, (bool) $newsletter_opt);

			if (is_wp_error($result)) {
				$message = '<p class="dm-freebie-error">' . esc_html($result->get_error_message()) . '</p>';
			} else {
				$success = true;
				$message = '<p class="dm-freebie-success">' .
					esc_html__('¡Listo! Revisa tu email — te hemos enviado el link de descarga.', 'daniela-child') .
					'</p>';
			}
		}
	}

	if ($success) {
		return $message;
	}

	$optin_label = get_option(
		'dm_newsletter_optin_label',
		__('Acepto recibir recursos y novedades de Daniela Montes Psicóloga por email. Puedo darme de baja en cualquier momento.', 'daniela-child')
	);

	$form_action_url = ! empty($atts['action_url'])
		? esc_url_raw((string) $atts['action_url'])
		: get_permalink();
	if (empty($form_action_url)) {
		$form_action_url = home_url('/');
	}

	ob_start();
?>
	<div class="dm-freebie-form-wrap">
		<?php if ($atts['title']) : ?>
			<h3 class="dm-freebie-form__title"><?php echo esc_html($atts['title']); ?></h3>
		<?php endif; ?>

		<?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput 
		?>

		<form
			class="dm-freebie-form"
			method="post"
			action="<?php echo esc_url($form_action_url); ?>">

			<?php wp_nonce_field('dm_freebie_request_' . $product_id, 'dm_freebie_nonce'); ?>
			<input type="hidden" name="dm_freebie_product_id" value="<?php echo esc_attr($product_id); ?>" />

			<p class="dm-freebie-form__field">
				<label for="dm-freebie-email-<?php echo esc_attr($product_id); ?>">
					<?php esc_html_e('Tu email:', 'daniela-child'); ?>
				</label>
				<input
					type="email"
					id="dm-freebie-email-<?php echo esc_attr($product_id); ?>"
					name="dm_freebie_email"
					required
					autocomplete="email"
					placeholder="<?php esc_attr_e('tu@correo.com', 'daniela-child'); ?>" />
			</p>

			<p class="dm-freebie-form__field dm-freebie-form__field--optin">
				<label>
					<input
						type="checkbox"
						name="dm_freebie_newsletter"
						value="1" />
					<?php echo wp_kses_post($optin_label); ?>
				</label>
			</p>

			<p class="dm-freebie-form__submit">
				<button type="submit" class="dm-btn dm-btn--secondary">
					<?php echo esc_html($atts['button_text']); ?>
				</button>
			</p>
		</form>
	</div>
<?php
	return ob_get_clean();
}

// =============================================================================
// LÓGICA DE PROCESAMIENTO DE SOLICITUD
// =============================================================================

/**
 * Máximo de descargas permitido por enlace tokenizado.
 * Editable desde WooCommerce > Ajustes > DM Newsletter.
 *
 * @return int
 */
function dm_freebie_get_max_downloads(): int
{
	$raw = (int) get_option('dm_freebie_max_downloads', 10);
	if ($raw < 1) {
		return 1;
	}

	return min($raw, 100);
}

/**
 * Procesa una solicitud de freebie: genera token y envía email.
 *
 * @param string $email          Email del solicitante.
 * @param int    $product_id     ID del producto WC.
 * @param bool   $newsletter_opt Consentimiento de newsletter.
 * @return true|WP_Error
 */
function dm_freebie_process_request(string $email, int $product_id, bool $newsletter_opt)
{
	// Guard: the freebie flow is only for free (price = $0) products.
	// Paid products must go through the standard WooCommerce checkout.
	if (function_exists('wc_get_product')) {
		$product = wc_get_product($product_id);
		if ($product && (float) $product->get_price() > 0.0) {
			return new WP_Error(
				'paid_product',
				__('Este recurso requiere compra. Completa el proceso de pago.', 'daniela-child')
			);
		}
	}

	global $wpdb;

	$table = $wpdb->prefix . 'dm_freebie_tokens';
	$max_downloads = dm_freebie_get_max_downloads();

	// Reutilizar token no expirado si ya existe para este (email, product_id).
	$existing = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE email = %s AND product_id = %d AND (expires_at IS NULL OR expires_at > %s) AND download_count < max_downloads LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$email,
			$product_id,
			current_time('mysql')
		)
	);

	if ($existing) {
		$token = $existing->token;
	} else {
		// Generar token criptográficamente seguro.
		$token = bin2hex(random_bytes(32));

		$inserted = $wpdb->insert(
			$table,
			[
				'token'            => $token,
				'email'            => $email,
				'product_id'       => $product_id,
				'created_at'       => current_time('mysql'),
				'expires_at'       => null,
				'download_count'   => 0,
				'max_downloads'    => $max_downloads,
				'newsletter_optin' => $newsletter_opt ? 1 : 0,
			],
			['%s', '%s', '%d', '%s', '%s', '%d', '%d', '%d']
		);

		if (! $inserted) {
			return new WP_Error('db_error', __('Error al guardar la solicitud. Inténtalo de nuevo.', 'daniela-child'));
		}
	}

	// Construir link de descarga.
	$download_url = add_query_arg('dm_freebie_token', rawurlencode($token), home_url('/'));

	// Enviar email.
	$sent = dm_freebie_send_email($email, $product_id, $download_url);

	if (! $sent) {
		return new WP_Error('email_error', __('No se pudo enviar el email. Inténtalo de nuevo.', 'daniela-child'));
	}

	// Opt-in newsletter (si corresponde).
	if ($newsletter_opt) {
		dm_freebie_maybe_subscribe_newsletter($email, $product_id);
	}

	return true;
}

// =============================================================================
// ENVÍO DE EMAIL
// =============================================================================

/**
 * Envía el email con el link de descarga tokenizado.
 *
 * @param string $email        Destinatario.
 * @param int    $product_id   ID del producto.
 * @param string $download_url URL tokenizada de descarga.
 * @return bool
 */
function dm_freebie_send_email(string $email, int $product_id, string $download_url): bool
{
	$product_name = '';
	if (function_exists('wc_get_product')) {
		$product = wc_get_product($product_id);
		if ($product) {
			$product_name = $product->get_name();
		}
	}

	$site_name = get_bloginfo('name');
	$subject_template = (string) get_option(
		'dm_freebie_email_subject_text',
		__('Tu recurso "%1$s" de %2$s', 'daniela-child')
	);
	if (false === strpos($subject_template, '%1$s') && false === strpos($subject_template, '%2$s')) {
		$subject_template .= ' %1$s %2$s';
	}
	$subject = sprintf(
		$subject_template,
		$product_name ?: __('descargable', 'daniela-child'),
		$site_name
	);

	$intro_text = (string) get_option(
		'dm_freebie_email_intro_text',
		__('Aquí tienes el link para descargar tu contenido.', 'daniela-child')
	);
	$button_text = (string) get_option(
		'dm_freebie_email_button_text',
		__('Descargar recurso', 'daniela-child')
	);
	$signoff_template = (string) get_option(
		'dm_freebie_email_signoff_text',
		__('Con cariño, %s', 'daniela-child')
	);
	$signoff_text = strpos($signoff_template, '%s') !== false
		? sprintf($signoff_template, $site_name)
		: $signoff_template;
	$max_downloads = dm_freebie_get_max_downloads();

	$plain_message = sprintf(
		/* translators: 1: resource name, 2: download URL, 3: max downloads, 4: site name */
		__(
			"Hola,\n\n%1\$s\n\n%2\$s\n\nEste link tiene un máximo de %3\$d descargas.\n\n%4\$s",
			'daniela-child'
		),
		$intro_text,
		$download_url,
		$max_downloads,
		$signoff_text
	);

	if (function_exists('WC') && WC() && method_exists(WC(), 'mailer')) {
		$mailer = WC()->mailer();
		if ($mailer) {
			$heading = sprintf(
				/* translators: %s: resource name */
				__('Tu recurso "%s" está listo', 'daniela-child'),
				$product_name ?: __('descargable', 'daniela-child')
			);

			$body_html  = '<p>' . esc_html__('Hola,', 'daniela-child') . '</p>';
			$body_html .= '<p>' . esc_html($intro_text) . '</p>';
			$body_html .= '<div class="dm-email-cta"><a class="dm-email-cta__link dm-btn dm-btn--primary" href="' . esc_url($download_url) . '">' . esc_html($button_text) . '</a></div>';
			$body_html .= '<p>' . sprintf(
				/* translators: %d: max downloads */
				esc_html__('Este link tiene un máximo de %d descargas.', 'daniela-child'),
				$max_downloads
			) . '</p>';
			$body_html .= '<p>' . esc_html($signoff_text) . '</p>';

			$wrapped = $mailer->wrap_message($heading, $body_html);

			return $mailer->send(
				$email,
				$subject,
				$wrapped,
				['Content-Type: text/html; charset=UTF-8']
			);
		}
	}

	return wp_mail($email, $subject, $plain_message, ['Content-Type: text/plain; charset=UTF-8']);
}

// =============================================================================
// INTEGRACIÓN NEWSLETTER (OPT-IN)
// =============================================================================

/**
 * Intenta suscribir el email al newsletter usando el mismo flujo
 * que newsletter-optin.php (hook MailerLite o API fallback).
 *
 * @param string $email      Email a suscribir.
 * @param int    $product_id ID del producto (para derivar tags).
 */
function dm_freebie_maybe_subscribe_newsletter(string $email, int $product_id)
{
	// Estrategia 1: hook oficial del plugin MailerLite WooCommerce.
	if (has_action('mailerlite_woocommerce_subscribe')) {
		do_action('mailerlite_woocommerce_subscribe', $email, '', '', 0);
		return;
	}

	// Estrategia 2: API fallback de DM (si está habilitado).
	$fallback_enabled = (bool) get_option('dm_mailerlite_fallback_enabled', false);
	if (! $fallback_enabled) {
		return;
	}

	$api_key  = get_option('dm_mailerlite_api_key', '');
	$group_id = get_option('dm_mailerlite_group_id', '');

	if (empty($api_key) || empty($group_id)) {
		return;
	}

	$endpoint = 'https://api.mailerlite.com/api/v2/groups/' . rawurlencode($group_id) . '/subscribers';

	wp_remote_post(
		$endpoint,
		[
			'timeout' => 10,
			'headers' => [
				'Content-Type'        => 'application/json',
				'X-MailerLite-ApiKey' => $api_key,
			],
			'body'    => wp_json_encode([
				'email'       => $email,
				'resubscribe' => true,
			]),
		]
	);
}

// =============================================================================
// ENDPOINT DE DESCARGA
// =============================================================================

add_action('init', 'dm_freebie_handle_download_request');

add_action('init', 'dm_freebie_handle_legacy_dm_token_download', 9);

/**
 * Legacy compatibility for old links /recursos/recibir/?dm_token=<token>.
 * Reads the previous transient format and preserves one-time behavior.
 */
function dm_freebie_handle_legacy_dm_token_download()
{
	if (! isset($_GET['dm_token'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$token = sanitize_key(wp_unslash($_GET['dm_token'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if (empty($token)) {
		wp_die(esc_html__('Enlace no válido.', 'daniela-child'), '', ['response' => 400]);
	}

	$data = get_transient('dm_freebie_token_' . $token);
	if (! $data || empty($data['file_urls'])) {
		wp_die(
			esc_html__('Este enlace de descarga ha expirado o no es válido. Solicita el recurso de nuevo.', 'daniela-child'),
			esc_html__('Enlace expirado', 'daniela-child'),
			['response' => 410]
		);
	}

	$file_urls = (array) $data['file_urls'];
	if (count($file_urls) === 1) {
		delete_transient('dm_freebie_token_' . $token);
		wp_safe_redirect(esc_url_raw(reset($file_urls)));
		exit;
	}

	dm_freebie_render_legacy_download_list($file_urls, $token);
	exit;
}

add_action('template_redirect', 'dm_freebie_handle_legacy_delivery_endpoint', 9);

/**
 * Legacy compatibility for /recursos/recibir/?product_id=<id> endpoint.
 * Renders the canonical [dm_freebie_form] flow using the old URL.
 */
function dm_freebie_handle_legacy_delivery_endpoint()
{
	if (is_admin()) {
		return;
	}

	if (isset($_GET['dm_token']) || isset($_GET['dm_freebie_token'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$request_uri = isset($_SERVER['REQUEST_URI']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		? (string) wp_unslash($_SERVER['REQUEST_URI']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		: '';

	if (false === strpos($request_uri, 'recursos/recibir')) {
		return;
	}

	if (! isset($_REQUEST['product_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$product_id = absint($_REQUEST['product_id']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if (! $product_id) {
		wp_die(esc_html__('Recurso no válido.', 'daniela-child'), '', ['response' => 400]);
	}

	$action_url = add_query_arg('product_id', $product_id, home_url('/recursos/recibir/'));

	get_header();
	echo '<main id="main" class="site-main dm-freebie dm-freebie--legacy">';
	echo do_shortcode(
		sprintf(
			'[dm_freebie_form product_id="%d" action_url="%s"]',
			$product_id,
			esc_url_raw($action_url)
		)
	);
	echo '</main>';
	get_footer();
	exit;
}

/**
 * Legacy view for multi-file legacy tokens.
 *
 * @param string[] $file_urls
 * @param string   $token
 */
function dm_freebie_render_legacy_download_list(array $file_urls, string $token)
{
	get_header();
?>
	<main id="main" class="site-main dm-freebie dm-freebie--download-list">
		<div class="dm-freebie__container">
			<h1 class="dm-freebie__title"><?php esc_html_e('Tus archivos descargables', 'daniela-child'); ?></h1>
			<ul class="dm-freebie__file-list">
				<?php foreach ($file_urls as $url) : ?>
					<li>
						<a href="<?php echo esc_url($url); ?>" download class="dm-btn dm-btn--secondary">
							<?php echo esc_html(basename((string) wp_parse_url($url, PHP_URL_PATH))); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="dm-freebie__note"><?php esc_html_e('Este enlace es de uso único y expira en 48 horas.', 'daniela-child'); ?></p>
		</div>
	</main>
<?php
	delete_transient('dm_freebie_token_' . $token);
	get_footer();
}

/**
 * Intercepta ?dm_freebie_token=<token> en cualquier URL del front-end,
 * valida el token y entrega el archivo.
 */
function dm_freebie_handle_download_request()
{
	if (! isset($_GET['dm_freebie_token'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$raw_token = sanitize_text_field(wp_unslash($_GET['dm_freebie_token'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$token     = preg_replace('/[^a-f0-9]/i', '', $raw_token);

	if (strlen($token) !== 64) {
		wp_die(
			esc_html__('Link de descarga inválido.', 'daniela-child'),
			esc_html__('Error', 'daniela-child'),
			['response' => 400]
		);
	}

	global $wpdb;
	$table = $wpdb->prefix . 'dm_freebie_tokens';

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE token = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$token
		)
	);

	if (! $row) {
		wp_die(
			esc_html__('Link de descarga no encontrado o expirado.', 'daniela-child'),
			esc_html__('Error', 'daniela-child'),
			['response' => 404]
		);
	}

	// Validar expiración.
	if (! empty($row->expires_at) && strtotime($row->expires_at) < time()) {
		wp_die(
			esc_html__('Este link ha expirado. Solicita uno nuevo.', 'daniela-child'),
			esc_html__('Link expirado', 'daniela-child'),
			['response' => 410]
		);
	}

	// Validar límite de descargas.
	$max_downloads = (int) $row->max_downloads;
	if ($max_downloads <= 0) {
		$max_downloads = dm_freebie_get_max_downloads();
	}

	if ((int) $row->download_count >= $max_downloads) {
		wp_die(
			esc_html__('Este link ha alcanzado el límite de descargas. Solicita uno nuevo.', 'daniela-child'),
			esc_html__('Límite alcanzado', 'daniela-child'),
			['response' => 403]
		);
	}

	// Guard: sólo productos gratuitos. Si el producto ahora tiene precio > 0
	// (cambio posterior), invalidar el token y redirigir al checkout.
	if (function_exists('wc_get_product')) {
		$freebie_product = wc_get_product((int) $row->product_id);
		if ($freebie_product && (float) $freebie_product->get_price() > 0.0) {
			wp_die(
				esc_html__('Este recurso requiere compra. Completa el proceso de pago.', 'daniela-child'),
				esc_html__('Recurso de pago', 'daniela-child'),
				['response' => 403]
			);
		}
	}

	// Obtener URL del archivo del producto WC.
	$file_url = dm_freebie_get_product_file_url((int) $row->product_id);

	if (! $file_url) {
		wp_die(
			esc_html__('Archivo no disponible. Contacta al equipo.', 'daniela-child'),
			esc_html__('Archivo no encontrado', 'daniela-child'),
			['response' => 404]
		);
	}

	// Incrementar contador ANTES de entregar.
	$wpdb->update(
		$table,
		['download_count' => (int) $row->download_count + 1],
		['token' => $token],
		['%d'],
		['%s']
	);

	// Estrategia de entrega: redirect seguro al archivo (WC gestiona permisos).
	// Si el archivo está en uploads, redirigir directamente.
	// Fallback: intentar proxy del archivo.
	dm_freebie_deliver_file($file_url);
}

/**
 * Obtiene la URL del primer archivo descargable del producto WC.
 *
 * @param int $product_id
 * @return string|null
 */
function dm_freebie_get_product_file_url(int $product_id): ?string
{
	if (! function_exists('wc_get_product')) {
		return null;
	}

	$product = wc_get_product($product_id);
	if (! $product || ! $product->is_downloadable()) {
		// Intentar fallback: buscar el attachment ligado al producto.
		$attachment_id = (int) get_post_meta($product_id, '_dm_source_attachment_id', true);
		if ($attachment_id) {
			return wp_get_attachment_url($attachment_id) ?: null;
		}
		return null;
	}

	$downloads = $product->get_downloads();
	if (empty($downloads)) {
		return null;
	}

	$first = reset($downloads);
	return $first->get_file() ?: null;
}

/**
 * Entrega el archivo al usuario.
 *
 * Preferencia:
 *   1. Redirect al archivo si está en el dominio propio (no expone path privado).
 *   2. Proxy básico para archivos pequeños (<= 20MB).
 *
 * @param string $file_url URL del archivo.
 */
function dm_freebie_deliver_file(string $file_url)
{
	// Sanitizar y verificar que la URL es del mismo dominio o de un origen confiable.
	$site_host  = wp_parse_url(home_url(), PHP_URL_HOST);
	$file_host  = wp_parse_url($file_url, PHP_URL_HOST);

	if ($site_host === $file_host) {
		// Archivo en el mismo dominio → redirect directo.
		wp_redirect(esc_url_raw($file_url));
		exit;
	}

	// Archivo externo: proxy para no exponer la URL directa.
	$response = wp_remote_get($file_url, [
		'timeout'  => 30,
		'stream'   => false,
		'filename' => '', // No guardar en disco.
	]);

	if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
		// Fallback: redirigir directamente si no se puede hacer proxy.
		wp_redirect(esc_url_raw($file_url));
		exit;
	}

	$body         = wp_remote_retrieve_body($response);
	$content_type = wp_remote_retrieve_header($response, 'content-type') ?: 'application/octet-stream';
	$file_name    = basename(wp_parse_url($file_url, PHP_URL_PATH));

	nocache_headers();
	header('Content-Type: ' . sanitize_mime_type($content_type));
	header('Content-Disposition: attachment; filename="' . sanitize_file_name($file_name) . '"');
	header('Content-Length: ' . strlen($body));
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo $body;
	exit;
}
