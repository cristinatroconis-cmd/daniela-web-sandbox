<?php
/**
 * Freebie Delivery — /recursos/recibir/
 *
 * Handles delivery of free (price 0) downloadable resources by email.
 * Renders a form that captures email + newsletter opt-in, validates,
 * tracks downloads per email/resource (max 10), and sends a tokenized
 * download link via wp_mail().
 *
 * Endpoint:  GET/POST  /recursos/recibir/?product_id=<id>
 *
 * Integrates with the existing newsletter opt-in system in newsletter-optin.php:
 * if the user checks opt-in, it reuses dm_newsletter_api_subscribe() (or the
 * MailerLite plugin hook) — same logic as checkout, zero duplication.
 *
 * Download tracking: stored in postmeta on the product:
 *   _dm_freebie_downloads  → serialized array  [ email => count ]
 *
 * Download token: stored in a transient keyed by token:
 *   dm_freebie_token_{token}  →  [ product_id, email, file_urls[] ]  (TTL 48h)
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// Endpoint registration
// =============================================================================

add_action( 'init', 'dm_freebie_add_rewrite_rule' );

/**
 * Register /recursos/recibir/ as a virtual endpoint.
 */
function dm_freebie_add_rewrite_rule() {
	add_rewrite_rule(
		'^recursos/recibir/?$',
		'index.php?dm_freebie_delivery=1',
		'top'
	);
	add_rewrite_tag( '%dm_freebie_delivery%', '([0-9]+)' );
}

add_action( 'template_redirect', 'dm_freebie_handle_request' );

/**
 * Intercept requests to /recursos/recibir/?product_id=X
 * and render the delivery form (GET) or process submission (POST).
 */
function dm_freebie_handle_request() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['product_id'] ) ) {
		return;
	}

	// We're on an ordinary page but with ?product_id=<id>.
	// Also triggers when rewrite rule matches dm_freebie_delivery=1.
	$is_freebie_endpoint = (
		get_query_var( 'dm_freebie_delivery' ) ||
		( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( (string) wp_unslash( $_SERVER['REQUEST_URI'] ), 'recursos/recibir' ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	);

	if ( ! $is_freebie_endpoint ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$product_id = absint( $_GET['product_id'] );
	if ( ! $product_id ) {
		wp_die( esc_html__( 'Recurso no válido.', 'daniela-child' ), '', array( 'response' => 400 ) );
	}

	// Validate product exists and is free.
	if ( ! function_exists( 'wc_get_product' ) ) {
		wp_die( esc_html__( 'WooCommerce no está activo.', 'daniela-child' ) );
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		wp_die( esc_html__( 'El recurso solicitado no existe.', 'daniela-child' ), '', array( 'response' => 404 ) );
	}

	$price = (float) $product->get_price();
	if ( $price > 0.0 ) { // phpcs:ignore WordPress.PHP.StrictComparisons
		// Paid product — redirect to the product page.
		wp_safe_redirect( get_permalink( $product_id ) );
		exit;
	}

	// Handle form submission.
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		dm_freebie_process_form( $product );
		exit;
	}

	// Render form.
	dm_freebie_render_form( $product, '' );
	exit;
}

// =============================================================================
// Token download handler
// =============================================================================

add_action( 'init', 'dm_freebie_handle_token_download' );

/**
 * Handle tokenized download links: /recursos/recibir/?dm_token=<token>
 */
function dm_freebie_handle_token_download() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['dm_token'] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$token = sanitize_key( wp_unslash( $_GET['dm_token'] ) );
	if ( empty( $token ) ) {
		wp_die( esc_html__( 'Enlace no válido.', 'daniela-child' ), '', array( 'response' => 400 ) );
	}

	$data = get_transient( 'dm_freebie_token_' . $token );
	if ( ! $data ) {
		wp_die(
			esc_html__( 'Este enlace de descarga ha expirado o no es válido. Solicita el recurso de nuevo.', 'daniela-child' ),
			esc_html__( 'Enlace expirado', 'daniela-child' ),
			array( 'response' => 410 )
		);
	}

	$product_id = absint( $data['product_id'] );
	$file_urls  = (array) $data['file_urls'];

	// Guard: sólo productos gratuitos. Si el producto ahora tiene precio > 0
	// (cambio posterior al token), invalidar y redirigir al checkout.
	if ( function_exists( 'wc_get_product' ) ) {
		$freebie_product = wc_get_product( $product_id );
		if ( $freebie_product && (float) $freebie_product->get_price() > 0.0 ) {
			delete_transient( 'dm_freebie_token_' . $token );
			wp_safe_redirect( get_permalink( $product_id ) );
			exit;
		}
	}

	if ( empty( $file_urls ) ) {
		wp_die( esc_html__( 'No hay archivos disponibles para este recurso.', 'daniela-child' ), '', array( 'response' => 404 ) );
	}

	// Single file → stream/redirect; multiple files → show list.
	if ( count( $file_urls ) === 1 ) {
		$url = reset( $file_urls );
		// Delete transient after first use (one-time link behaviour).
		delete_transient( 'dm_freebie_token_' . $token );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	// Multiple files: render a simple download page.
	dm_freebie_render_download_list( $file_urls, $token );
	exit;
}

// =============================================================================
// Form processing
// =============================================================================

/**
 * Process the submitted freebie request form.
 *
 * @param WC_Product $product
 */
function dm_freebie_process_form( WC_Product $product ) {
	$product_id = $product->get_id();

	// Verify nonce.
	if (
		! isset( $_POST['dm_freebie_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dm_freebie_nonce'] ) ), 'dm_freebie_' . $product_id )
	) {
		dm_freebie_render_form( $product, __( 'Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.', 'daniela-child' ) );
		return;
	}

	// Validate email.
	$email = isset( $_POST['dm_freebie_email'] )
		? sanitize_email( wp_unslash( $_POST['dm_freebie_email'] ) )
		: '';

	if ( ! is_email( $email ) ) {
		dm_freebie_render_form( $product, __( 'Por favor, introduce un email válido.', 'daniela-child' ) );
		return;
	}

	// Newsletter opt-in choice (GDPR: default off).
	$optin = isset( $_POST['dm_freebie_optin'] ) && '1' === sanitize_key( $_POST['dm_freebie_optin'] )
		? true
		: false;

	// Download limit check.
	$download_count = dm_freebie_get_download_count( $product_id, $email );
	if ( $download_count >= 10 ) {
		dm_freebie_render_form(
			$product,
			__( 'Has superado el límite de descargas (10) para este recurso con este email.', 'daniela-child' )
		);
		return;
	}

	// Increment download count.
	dm_freebie_increment_download_count( $product_id, $email );

	// Generate tokenized download URL.
	$file_urls = dm_freebie_get_product_file_urls( $product );
	$token     = dm_freebie_create_token( $product_id, $email, $file_urls );

	// Send download email.
	$sent = dm_freebie_send_download_email( $email, $product, $token );

	// Newsletter opt-in: reuse existing infrastructure.
	if ( $optin ) {
		dm_freebie_trigger_optin( $email, $product_id );
	}

	if ( $sent ) {
		dm_freebie_render_success( $product, $email );
	} else {
		dm_freebie_render_form(
			$product,
			__( 'El email se envió pero puede haber habido un problema. Revisa tu bandeja de spam.', 'daniela-child' )
		);
	}
}

// =============================================================================
// Email sending
// =============================================================================

/**
 * Send the download link email to the user.
 *
 * @param  string      $email   Recipient email address.
 * @param  WC_Product  $product Product object.
 * @param  string      $token   Download token.
 * @return bool                  Whether wp_mail returned true.
 */
function dm_freebie_send_download_email( $email, WC_Product $product, $token ) {
	$download_url = add_query_arg( 'dm_token', $token, home_url( '/recursos/recibir/' ) );
	$product_name = $product->get_name();
	$site_name    = get_bloginfo( 'name' );

	$subject = sprintf(
		/* translators: %1$s: product name, %2$s: site name */
		__( 'Tu recurso: %1$s — %2$s', 'daniela-child' ),
		$product_name,
		$site_name
	);

	$message  = sprintf(
		/* translators: %s: product name */
		__( 'Hola,\n\nAquí tienes tu recurso gratuito: %s.\n\n', 'daniela-child' ),
		$product_name
	);
	$message .= sprintf(
		/* translators: %s: download URL */
		__( 'Descárgalo desde este enlace (válido 48 horas):\n%s\n\n', 'daniela-child' ),
		$download_url
	);
	$message .= sprintf(
		/* translators: %s: site name */
		__( 'Con cariño,\n%s', 'daniela-child' ),
		$site_name
	);

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	return wp_mail( $email, $subject, $message, $headers );
}

// =============================================================================
// Newsletter opt-in trigger
// =============================================================================

/**
 * Trigger newsletter opt-in using existing infrastructure.
 *
 * Reuses Strategy 1 (MailerLite plugin hook) or Strategy 2 (API fallback)
 * from newsletter-optin.php — zero duplication of subscriber logic.
 *
 * @param string $email      Subscriber email.
 * @param int    $product_id Product ID (used to derive tags).
 */
function dm_freebie_trigger_optin( $email, $product_id ) {
	// Strategy 1: MailerLite WooCommerce plugin hook.
	if ( has_action( 'mailerlite_woocommerce_subscribe' ) ) {
		do_action( 'mailerlite_woocommerce_subscribe', $email, '', '', 0 );
		return;
	}

	// Strategy 2: Direct API call (if enabled in DM settings).
	$fallback_enabled = (bool) get_option( 'dm_mailerlite_fallback_enabled', false );
	if ( ! $fallback_enabled ) {
		return;
	}

	$api_key  = get_option( 'dm_mailerlite_api_key', '' );
	$group_id = get_option( 'dm_mailerlite_group_id', '' );

	if ( empty( $api_key ) || empty( $group_id ) ) {
		return;
	}

	// Derive resource-buyer tag if configured.
	$tag_ids          = array();
	$resource_tag_id  = get_option( 'dm_mailerlite_tag_resource_buyer', '' );
	if ( ! empty( $resource_tag_id ) ) {
		$tag_ids[] = $resource_tag_id;
	}

	$payload = array(
		'email'       => $email,
		'resubscribe' => true,
	);

	if ( ! empty( $tag_ids ) ) {
		$payload['groups'] = array_filter( $tag_ids );
	}

	$endpoint = 'https://api.mailerlite.com/api/v2/groups/' . rawurlencode( $group_id ) . '/subscribers';

	wp_remote_post(
		$endpoint,
		array(
			'timeout' => 10,
			'headers' => array(
				'Content-Type'       => 'application/json',
				'X-MailerLite-ApiKey' => $api_key,
			),
			'body'    => wp_json_encode( $payload ),
		)
	);
}

// =============================================================================
// Download tracking
// =============================================================================

/**
 * Get the download count for a given email + product pair.
 *
 * @param  int    $product_id
 * @param  string $email
 * @return int
 */
function dm_freebie_get_download_count( $product_id, $email ) {
	$data = (array) get_post_meta( $product_id, '_dm_freebie_downloads', true );
	return isset( $data[ $email ] ) ? (int) $data[ $email ] : 0;
}

/**
 * Increment the download count for a given email + product pair.
 *
 * @param int    $product_id
 * @param string $email
 */
function dm_freebie_increment_download_count( $product_id, $email ) {
	$data          = (array) get_post_meta( $product_id, '_dm_freebie_downloads', true );
	$current_count = isset( $data[ $email ] ) ? (int) $data[ $email ] : 0;
	$data[ $email ] = $current_count + 1;
	update_post_meta( $product_id, '_dm_freebie_downloads', $data );
}

// =============================================================================
// Token management
// =============================================================================

/**
 * Create a one-time download token valid for 48 hours.
 *
 * @param  int      $product_id
 * @param  string   $email
 * @param  string[] $file_urls
 * @return string   Token string.
 */
function dm_freebie_create_token( $product_id, $email, array $file_urls ) {
	$token = wp_generate_password( 32, false );

	set_transient(
		'dm_freebie_token_' . $token,
		array(
			'product_id' => $product_id,
			'email'      => $email,
			'file_urls'  => $file_urls,
		),
		48 * HOUR_IN_SECONDS
	);

	return $token;
}

// =============================================================================
// File URL helper
// =============================================================================

/**
 * Get an array of file URLs from a product's downloadable files.
 *
 * @param  WC_Product $product
 * @return string[]
 */
function dm_freebie_get_product_file_urls( WC_Product $product ) {
	$urls  = array();
	$files = $product->get_downloads();
	foreach ( $files as $file ) {
		$url = $file->get_file();
		if ( $url ) {
			$urls[] = $url;
		}
	}
	return $urls;
}

// =============================================================================
// HTML rendering helpers
// =============================================================================

/**
 * Render the freebie request form.
 *
 * @param WC_Product $product
 * @param string     $error_message  Optional error message to display.
 */
function dm_freebie_render_form( WC_Product $product, $error_message = '' ) {
	get_header();
	$product_id   = $product->get_id();
	$product_name = esc_html( $product->get_name() );
	$nonce        = wp_create_nonce( 'dm_freebie_' . $product_id );
	$action_url   = add_query_arg( 'product_id', $product_id, home_url( '/recursos/recibir/' ) );
	$optin_label  = get_option(
		'dm_newsletter_optin_label',
		__( 'Acepto recibir recursos y novedades de Daniela Montes Psicóloga por email. Puedo darme de baja en cualquier momento.', 'daniela-child' )
	);
	?>
	<main id="main" class="site-main dm-freebie">
		<div class="dm-freebie__container">
			<h1 class="dm-freebie__title">
				<?php
				echo sprintf(
					/* translators: %s: resource name */
					esc_html__( 'Recibe gratis: %s', 'daniela-child' ),
					$product_name
				);
				?>
			</h1>

			<?php if ( ! empty( $error_message ) ) : ?>
			<div class="dm-freebie__error" role="alert">
				<?php echo esc_html( $error_message ); ?>
			</div>
			<?php endif; ?>

			<p class="dm-freebie__intro">
				<?php esc_html_e( 'Introduce tu email y te lo enviamos ahora mismo.', 'daniela-child' ); ?>
			</p>

			<form
				class="dm-freebie__form"
				method="post"
				action="<?php echo esc_url( $action_url ); ?>"
				novalidate>

				<input type="hidden" name="dm_freebie_nonce" value="<?php echo esc_attr( $nonce ); ?>" />

				<div class="dm-freebie__field">
					<label for="dm_freebie_email">
						<?php esc_html_e( 'Tu email', 'daniela-child' ); ?>
						<span aria-hidden="true">*</span>
					</label>
					<input
						type="email"
						id="dm_freebie_email"
						name="dm_freebie_email"
						required
						autocomplete="email"
						placeholder="<?php esc_attr_e( 'tucorreo@ejemplo.com', 'daniela-child' ); ?>" />
				</div>

				<div class="dm-freebie__field dm-freebie__field--optin">
					<label class="dm-freebie__optin-label">
						<input
							type="checkbox"
							name="dm_freebie_optin"
							value="1" />
						<?php echo wp_kses_post( $optin_label ); ?>
					</label>
				</div>

				<button type="submit" class="dm-btn dm-btn--primary dm-freebie__submit">
					<?php esc_html_e( 'Envíame el recurso', 'daniela-child' ); ?>
				</button>

			</form>

			<p class="dm-freebie__privacy">
				<?php
				printf(
					/* translators: %s: privacy policy URL */
					wp_kses(
						__( 'Tus datos están seguros. Consulta nuestra <a href="%s">política de privacidad</a>.', 'daniela-child' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( get_privacy_policy_url() )
				);
				?>
			</p>
		</div>
	</main>
	<?php
	get_footer();
}

/**
 * Render the success confirmation page after a freebie is requested.
 *
 * @param WC_Product $product
 * @param string     $email   The email address the resource was sent to.
 */
function dm_freebie_render_success( WC_Product $product, $email ) {
	get_header();
	?>
	<main id="main" class="site-main dm-freebie dm-freebie--success">
		<div class="dm-freebie__container">
			<h1 class="dm-freebie__title">
				<?php esc_html_e( '¡Listo! Revisa tu email 📬', 'daniela-child' ); ?>
			</h1>
			<p>
				<?php
				printf(
					/* translators: 1: email address, 2: product name */
					esc_html__( 'Hemos enviado "%2$s" a %1$s. Si no lo ves en tu bandeja de entrada, revisa la carpeta de spam.', 'daniela-child' ),
					'<strong>' . esc_html( $email ) . '</strong>',
					esc_html( $product->get_name() )
				);
				?>
			</p>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'dm_recurso' ) ); ?>" class="dm-btn dm-btn--ghost">
				<?php esc_html_e( 'Ver más recursos', 'daniela-child' ); ?>
			</a>
		</div>
	</main>
	<?php
	get_footer();
}

/**
 * Render a simple download list page (for multi-file freebies).
 *
 * @param string[] $file_urls
 * @param string   $token     Token (deleted after user starts downloading).
 */
function dm_freebie_render_download_list( array $file_urls, $token ) {
	get_header();
	?>
	<main id="main" class="site-main dm-freebie dm-freebie--download-list">
		<div class="dm-freebie__container">
			<h1 class="dm-freebie__title">
				<?php esc_html_e( 'Tus archivos descargables', 'daniela-child' ); ?>
			</h1>
			<ul class="dm-freebie__file-list">
				<?php foreach ( $file_urls as $url ) : ?>
				<li>
					<a href="<?php echo esc_url( $url ); ?>" download class="dm-btn dm-btn--secondary">
						<?php echo esc_html( basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ); ?>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
			<p class="dm-freebie__note">
				<?php esc_html_e( 'Este enlace es de uso único y expira en 48 horas.', 'daniela-child' ); ?>
			</p>
			<?php
			// Invalidate token after rendering the list so it can't be reused.
			delete_transient( 'dm_freebie_token_' . $token );
			?>
		</div>
	</main>
	<?php
	get_footer();
}
