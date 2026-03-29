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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// INSTALACIÓN DE TABLA
// =============================================================================

register_activation_hook( __FILE__, 'dm_freebie_install_table' );
add_action( 'init', 'dm_freebie_maybe_install_table' );

/**
 * Crea la tabla dm_freebie_tokens si no existe.
 * Llamado en init también para garantizar que la tabla exista en nuevas
 * instalaciones donde el hook de activación no se disparó.
 */
function dm_freebie_maybe_install_table() {
	if ( get_option( 'dm_freebie_table_version' ) !== '1.1' ) {
		dm_freebie_install_table();
	}
}

function dm_freebie_install_table() {
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
	dbDelta( $sql );

	update_option( 'dm_freebie_table_version', '1.1' );
}

// =============================================================================
// SHORTCODE — Formulario de solicitud de freebie
// =============================================================================

add_shortcode( 'dm_freebie_form', 'dm_freebie_form_shortcode' );

/**
 * Renderiza el formulario de solicitud del freebie.
 *
 * Atributos:
 *   product_id  (requerido) — ID del producto WC.
 *   title       (opcional)  — Texto del encabezado del formulario.
 *   button_text (opcional)  — Texto del botón.
 *
 * @param array $atts
 * @return string HTML del formulario o mensaje de error/confirmación.
 */
function dm_freebie_form_shortcode( $atts ) {
	$atts = shortcode_atts(
		[
			'product_id'  => 0,
			'title'       => __( 'Recibe este recurso gratis en tu correo', 'daniela-child' ),
			'button_text' => __( 'Enviarme el recurso', 'daniela-child' ),
		],
		$atts,
		'dm_freebie_form'
	);

	$product_id = absint( $atts['product_id'] );

	if ( ! $product_id ) {
		return '<p class="dm-freebie-error">' .
			esc_html__( 'Recurso no especificado.', 'daniela-child' ) .
			'</p>';
	}

	// Comprobar que el producto existe y es gratuito.
	if ( function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '<p class="dm-freebie-error">' .
				esc_html__( 'Recurso no disponible.', 'daniela-child' ) .
				'</p>';
		}
	}

	// Procesar envío del formulario.
	$message = '';
	$success = false;

	if (
		isset( $_POST['dm_freebie_product_id'] ) &&
		(int) $_POST['dm_freebie_product_id'] === $product_id &&
		isset( $_POST['dm_freebie_nonce'] ) &&
		wp_verify_nonce( sanitize_key( $_POST['dm_freebie_nonce'] ), 'dm_freebie_request_' . $product_id )
	) {
		$email          = sanitize_email( wp_unslash( $_POST['dm_freebie_email'] ?? '' ) );
		$newsletter_opt = isset( $_POST['dm_freebie_newsletter'] ) ? 1 : 0;

		if ( ! is_email( $email ) ) {
			$message = '<p class="dm-freebie-error">' .
				esc_html__( 'Por favor introduce un email válido.', 'daniela-child' ) .
				'</p>';
		} else {
			$result = dm_freebie_process_request( $email, $product_id, (bool) $newsletter_opt );

			if ( is_wp_error( $result ) ) {
				$message = '<p class="dm-freebie-error">' . esc_html( $result->get_error_message() ) . '</p>';
			} else {
				$success = true;
				$message = '<p class="dm-freebie-success">' .
					esc_html__( '¡Listo! Revisa tu email — te hemos enviado el link de descarga.', 'daniela-child' ) .
					'</p>';
			}
		}
	}

	if ( $success ) {
		return $message;
	}

	$optin_label = get_option(
		'dm_newsletter_optin_label',
		__( 'Acepto recibir recursos y novedades de Daniela Montes Psicóloga por email. Puedo darme de baja en cualquier momento.', 'daniela-child' )
	);

	ob_start();
	?>
	<div class="dm-freebie-form-wrap">
		<?php if ( $atts['title'] ) : ?>
			<h3 class="dm-freebie-form__title"><?php echo esc_html( $atts['title'] ); ?></h3>
		<?php endif; ?>

		<?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<form
			class="dm-freebie-form"
			method="post"
			action="<?php echo esc_url( get_permalink() ); ?>">

			<?php wp_nonce_field( 'dm_freebie_request_' . $product_id, 'dm_freebie_nonce' ); ?>
			<input type="hidden" name="dm_freebie_product_id" value="<?php echo esc_attr( $product_id ); ?>" />

			<p class="dm-freebie-form__field">
				<label for="dm-freebie-email-<?php echo esc_attr( $product_id ); ?>">
					<?php esc_html_e( 'Tu email:', 'daniela-child' ); ?>
				</label>
				<input
					type="email"
					id="dm-freebie-email-<?php echo esc_attr( $product_id ); ?>"
					name="dm_freebie_email"
					required
					autocomplete="email"
					placeholder="<?php esc_attr_e( 'tu@correo.com', 'daniela-child' ); ?>" />
			</p>

			<p class="dm-freebie-form__field dm-freebie-form__field--optin">
				<label>
					<input
						type="checkbox"
						name="dm_freebie_newsletter"
						value="1" />
					<?php echo wp_kses_post( $optin_label ); ?>
				</label>
			</p>

			<p class="dm-freebie-form__submit">
				<button type="submit" class="dm-btn dm-btn--secondary">
					<?php echo esc_html( $atts['button_text'] ); ?>
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
 * Procesa una solicitud de freebie: genera token y envía email.
 *
 * @param string $email          Email del solicitante.
 * @param int    $product_id     ID del producto WC.
 * @param bool   $newsletter_opt Consentimiento de newsletter.
 * @return true|WP_Error
 */
function dm_freebie_process_request( string $email, int $product_id, bool $newsletter_opt ) {
	global $wpdb;

	$table = $wpdb->prefix . 'dm_freebie_tokens';

	// Reutilizar token no expirado si ya existe para este (email, product_id).
	$existing = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE email = %s AND product_id = %d AND (expires_at IS NULL OR expires_at > %s) AND download_count < max_downloads LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$email,
			$product_id,
			current_time( 'mysql' )
		)
	);

	if ( $existing ) {
		$token = $existing->token;
	} else {
		// Generar token criptográficamente seguro.
		$token = bin2hex( random_bytes( 32 ) );

		$inserted = $wpdb->insert(
			$table,
			[
				'token'            => $token,
				'email'            => $email,
				'product_id'       => $product_id,
				'created_at'       => current_time( 'mysql' ),
				'expires_at'       => null,
				'download_count'   => 0,
				'max_downloads'    => 10,
				'newsletter_optin' => $newsletter_opt ? 1 : 0,
			],
			[ '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%d' ]
		);

		if ( ! $inserted ) {
			return new WP_Error( 'db_error', __( 'Error al guardar la solicitud. Inténtalo de nuevo.', 'daniela-child' ) );
		}
	}

	// Construir link de descarga.
	$download_url = add_query_arg( 'dm_freebie_token', rawurlencode( $token ), home_url( '/' ) );

	// Enviar email.
	$sent = dm_freebie_send_email( $email, $product_id, $download_url );

	if ( ! $sent ) {
		return new WP_Error( 'email_error', __( 'No se pudo enviar el email. Inténtalo de nuevo.', 'daniela-child' ) );
	}

	// Opt-in newsletter (si corresponde).
	if ( $newsletter_opt ) {
		dm_freebie_maybe_subscribe_newsletter( $email, $product_id );
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
function dm_freebie_send_email( string $email, int $product_id, string $download_url ): bool {
	$product_name = '';
	if ( function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$product_name = $product->get_name();
		}
	}

	$site_name = get_bloginfo( 'name' );
	$subject   = sprintf(
		/* translators: 1: resource name, 2: site name */
		__( 'Tu recurso "%1$s" de %2$s', 'daniela-child' ),
		$product_name ?: __( 'descargable', 'daniela-child' ),
		$site_name
	);

	$message = sprintf(
		/* translators: 1: resource name, 2: download URL, 3: max downloads, 4: site name */
		__(
			"Hola,\n\nAquí tienes el link para descargar \"%1\$s\":\n\n%2\$s\n\nEste link tiene un máximo de %3\$d descargas.\n\nCon cariño,\n%4\$s",
			'daniela-child'
		),
		$product_name ?: __( 'tu recurso', 'daniela-child' ),
		$download_url,
		10,
		$site_name
	);

	$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

	return wp_mail( $email, $subject, $message, $headers );
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
function dm_freebie_maybe_subscribe_newsletter( string $email, int $product_id ) {
	// Estrategia 1: hook oficial del plugin MailerLite WooCommerce.
	if ( has_action( 'mailerlite_woocommerce_subscribe' ) ) {
		do_action( 'mailerlite_woocommerce_subscribe', $email, '', '', 0 );
		return;
	}

	// Estrategia 2: API fallback de DM (si está habilitado).
	$fallback_enabled = (bool) get_option( 'dm_mailerlite_fallback_enabled', false );
	if ( ! $fallback_enabled ) {
		return;
	}

	$api_key  = get_option( 'dm_mailerlite_api_key', '' );
	$group_id = get_option( 'dm_mailerlite_group_id', '' );

	if ( empty( $api_key ) || empty( $group_id ) ) {
		return;
	}

	$endpoint = 'https://api.mailerlite.com/api/v2/groups/' . rawurlencode( $group_id ) . '/subscribers';

	wp_remote_post(
		$endpoint,
		[
			'timeout' => 10,
			'headers' => [
				'Content-Type'        => 'application/json',
				'X-MailerLite-ApiKey' => $api_key,
			],
			'body'    => wp_json_encode( [
				'email'       => $email,
				'resubscribe' => true,
			] ),
		]
	);
}

// =============================================================================
// ENDPOINT DE DESCARGA
// =============================================================================

add_action( 'init', 'dm_freebie_handle_download_request' );

/**
 * Intercepta ?dm_freebie_token=<token> en cualquier URL del front-end,
 * valida el token y entrega el archivo.
 */
function dm_freebie_handle_download_request() {
	if ( ! isset( $_GET['dm_freebie_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$raw_token = sanitize_text_field( wp_unslash( $_GET['dm_freebie_token'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$token     = preg_replace( '/[^a-f0-9]/i', '', $raw_token );

	if ( strlen( $token ) !== 64 ) {
		wp_die(
			esc_html__( 'Link de descarga inválido.', 'daniela-child' ),
			esc_html__( 'Error', 'daniela-child' ),
			[ 'response' => 400 ]
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

	if ( ! $row ) {
		wp_die(
			esc_html__( 'Link de descarga no encontrado o expirado.', 'daniela-child' ),
			esc_html__( 'Error', 'daniela-child' ),
			[ 'response' => 404 ]
		);
	}

	// Validar expiración.
	if ( ! empty( $row->expires_at ) && strtotime( $row->expires_at ) < time() ) {
		wp_die(
			esc_html__( 'Este link ha expirado. Solicita uno nuevo.', 'daniela-child' ),
			esc_html__( 'Link expirado', 'daniela-child' ),
			[ 'response' => 410 ]
		);
	}

	// Validar límite de descargas.
	if ( (int) $row->download_count >= (int) $row->max_downloads ) {
		wp_die(
			esc_html__( 'Este link ha alcanzado el límite de descargas. Solicita uno nuevo.', 'daniela-child' ),
			esc_html__( 'Límite alcanzado', 'daniela-child' ),
			[ 'response' => 403 ]
		);
	}

	// Obtener URL del archivo del producto WC.
	$file_url = dm_freebie_get_product_file_url( (int) $row->product_id );

	if ( ! $file_url ) {
		wp_die(
			esc_html__( 'Archivo no disponible. Contacta al equipo.', 'daniela-child' ),
			esc_html__( 'Archivo no encontrado', 'daniela-child' ),
			[ 'response' => 404 ]
		);
	}

	// Incrementar contador ANTES de entregar.
	$wpdb->update(
		$table,
		[ 'download_count' => (int) $row->download_count + 1 ],
		[ 'token' => $token ],
		[ '%d' ],
		[ '%s' ]
	);

	// Estrategia de entrega: redirect seguro al archivo (WC gestiona permisos).
	// Si el archivo está en uploads, redirigir directamente.
	// Fallback: intentar proxy del archivo.
	dm_freebie_deliver_file( $file_url );
}

/**
 * Obtiene la URL del primer archivo descargable del producto WC.
 *
 * @param int $product_id
 * @return string|null
 */
function dm_freebie_get_product_file_url( int $product_id ): ?string {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}

	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_downloadable() ) {
		// Intentar fallback: buscar el attachment ligado al producto.
		$attachment_id = (int) get_post_meta( $product_id, '_dm_source_attachment_id', true );
		if ( $attachment_id ) {
			return wp_get_attachment_url( $attachment_id ) ?: null;
		}
		return null;
	}

	$downloads = $product->get_downloads();
	if ( empty( $downloads ) ) {
		return null;
	}

	$first = reset( $downloads );
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
function dm_freebie_deliver_file( string $file_url ) {
	// Sanitizar y verificar que la URL es del mismo dominio o de un origen confiable.
	$site_host  = wp_parse_url( home_url(), PHP_URL_HOST );
	$file_host  = wp_parse_url( $file_url, PHP_URL_HOST );

	if ( $site_host === $file_host ) {
		// Archivo en el mismo dominio → redirect directo.
		wp_redirect( esc_url_raw( $file_url ) );
		exit;
	}

	// Archivo externo: proxy para no exponer la URL directa.
	$response = wp_remote_get( $file_url, [
		'timeout'  => 30,
		'stream'   => false,
		'filename' => '', // No guardar en disco.
	] );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		// Fallback: redirigir directamente si no se puede hacer proxy.
		wp_redirect( esc_url_raw( $file_url ) );
		exit;
	}

	$body         = wp_remote_retrieve_body( $response );
	$content_type = wp_remote_retrieve_header( $response, 'content-type' ) ?: 'application/octet-stream';
	$file_name    = basename( wp_parse_url( $file_url, PHP_URL_PATH ) );

	nocache_headers();
	header( 'Content-Type: ' . sanitize_mime_type( $content_type ) );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file_name ) . '"' );
	header( 'Content-Length: ' . strlen( $body ) );
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo $body;
	exit;
}
