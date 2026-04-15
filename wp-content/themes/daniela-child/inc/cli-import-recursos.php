<?php

/**
 * WP-CLI Importer — dm import-recursos
 *
 * Scans Media Library attachments (PDF/MP3/M4A/MOV) and for each creates or
 * updates:
 *  1. A simple, downloadable WooCommerce product (price 0 or 5 USD).
 *  2. A dm_recurso CPT with excerpt and CTA content.
 *  3. A link CPT → product via meta _dm_wc_product_id.
 *
 * Idempotent: uses _dm_source_attachment_id on both the product and the CPT to
 * detect existing entries and update instead of duplicating.
 *
 * Bundle detection: attachments whose title starts with "Afirmaciones" are
 * grouped into a single bundle product + CPT (tagged "bundle").
 *
 * Usage:
 *   wp dm import-recursos
 *   wp dm import-recursos --dry-run
 *   wp dm import-recursos --force-update
 *
 * Admin fallback:
 *   Visiting /wp-admin/?dm_import_recursos=1 triggers a single run for
 *   administrators (useful when WP-CLI is not available on the server).
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
	exit;
}

// =============================================================================
// WP-CLI command registration
// =============================================================================

if (defined('WP_CLI') && WP_CLI) {
	WP_CLI::add_command('dm import-recursos', 'DM_Import_Recursos_Command');
}

// =============================================================================
// Admin fallback: GET /wp-admin/?dm_import_recursos=1
// =============================================================================

add_action('admin_init', 'dm_import_recursos_admin_fallback');

function dm_import_recursos_admin_fallback()
{
	if (
		! isset($_GET['dm_import_recursos']) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		! current_user_can('manage_options')
	) {
		return;
	}

	$dry_run      = isset($_GET['dry_run']);   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$force_update = isset($_GET['force_update']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$importer = new DM_Recursos_Importer($dry_run, $force_update);
	$results  = $importer->run();

	// Show a basic admin notice with results.
	add_action(
		'admin_notices',
		function () use ($results) {
			echo '<div class="notice notice-success is-dismissible"><pre>';
			echo esc_html(implode("\n", $results));
			echo '</pre></div>';
		}
	);
}

// =============================================================================
// WP-CLI command class
// =============================================================================

/**
 * Imports/updates WooCommerce products and dm_recurso CPTs from Media Library.
 */
class DM_Import_Recursos_Command
{

	/**
	 * Import or update recursos from Media Library attachments.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be created/updated without writing anything.
	 *
	 * [--force-update]
	 * : Re-write existing products/CPTs even if they have not changed.
	 *
	 * ## EXAMPLES
	 *
	 *   wp dm import-recursos
	 *   wp dm import-recursos --dry-run
	 *   wp dm import-recursos --force-update
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named flags.
	 */
	public function __invoke($args, $assoc_args)
	{
		$dry_run      = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'dry-run', false);
		$force_update = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'force-update', false);

		if ($dry_run) {
			WP_CLI::log('🔍 DRY RUN — no changes will be saved.');
		}

		$importer = new DM_Recursos_Importer($dry_run, $force_update);
		$results  = $importer->run();

		foreach ($results as $line) {
			WP_CLI::log($line);
		}

		WP_CLI::success('Import complete.');
	}
}

// =============================================================================
// Core importer logic (shared by CLI + admin fallback)
// =============================================================================

/**
 * Handles the actual import logic.
 */
class DM_Recursos_Importer
{

	/** @var bool */
	private $dry_run;

	/** @var bool */
	private $force_update;

	/** @var string[] */
	private $log = array();

	/** Allowed MIME types for downloadable recursos. */
	const ALLOWED_MIME_TYPES = array(
		'application/pdf',
		'audio/mpeg',
		'audio/mp3',
		'audio/mp4',
		'audio/x-m4a',
		'video/mp4',
		'video/quicktime',
	);

	/**
	 * Keyword that signals a free resource.
	 * Per business rule: ONLY the word "gratuito" (case-insensitive) in the
	 * resource title sets the price to $0.
	 */
	const FREE_KEYWORDS = array('gratuito');

	/**
	 * Default price for paid resources (USD).
	 */
	const DEFAULT_PAID_PRICE = 5.00;

	/**
	 * Bundle price when 3+ attachments share a family (USD).
	 */
	const BUNDLE_PRICE = 9.00;

	/**
	 * Prefix that triggers bundle detection.
	 */
	const BUNDLE_FAMILY_PREFIX = 'afirmaciones';

	/**
	 * Tag slug to apply to bundle products/CPTs.
	 */
	const BUNDLE_TAG_SLUG = 'bundle';

	/**
	 * Topic keyword → tag slug mapping for auto-tagging.
	 */
	const TOPIC_KEYWORDS = array(
		'autoestima'   => 'autoestima',
		'ansiedad'     => 'ansiedad',
		'respiraci'    => 'respiracion',   // respiración, respiraciones
		'meditaci'     => 'meditacion',    // meditación, meditaciones
		'afirmaci'     => 'afirmaciones',  // afirmación, afirmaciones
		'mindfulness'  => 'mindfulness',
		'depresi'      => 'depresion',     // depresión, etc.
		'duelo'        => 'duelo',
		'trauma'       => 'trauma',
		'relajaci'     => 'relajacion',    // relajación
		'emociones'    => 'emociones',
		'emocion'      => 'emociones',
		'critica'      => 'autocritica',   // autocrítica
		'estres'       => 'estres',        // estrés
		'pareja'       => 'pareja',
		'limites'      => 'limites',       // límites
		'habitos'      => 'habitos',       // hábitos
	);

	/**
	 * @param bool $dry_run
	 * @param bool $force_update
	 */
	public function __construct($dry_run = false, $force_update = false)
	{
		$this->dry_run      = $dry_run;
		$this->force_update = $force_update;
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Run the full import.
	 *
	 * @return string[] Log lines.
	 */
	public function run()
	{
		if (! function_exists('wc_get_product')) {
			$this->log('ERROR: WooCommerce is not active. Aborting.');
			return $this->log;
		}

		$attachments = $this->get_downloadable_attachments();
		$this->log(sprintf('Found %d downloadable attachment(s).', count($attachments)));

		// Group attachments by bundle family before processing.
		$bundles  = array();
		$singles  = array();

		foreach ($attachments as $attachment) {
			$family = $this->detect_bundle_family($attachment);
			if ($family) {
				$bundles[$family][] = $attachment;
			} else {
				$singles[] = $attachment;
			}
		}

		// Process individual attachments.
		foreach ($singles as $attachment) {
			$this->process_single($attachment);
		}

		// Process bundle groups (only create bundle if 2+ attachments in family).
		foreach ($bundles as $family => $family_attachments) {
			if (count($family_attachments) >= 2) {
				$this->process_bundle($family, $family_attachments);
			} else {
				// Single member — treat as individual.
				$this->process_single($family_attachments[0]);
			}
		}

		return $this->log;
	}

	// -------------------------------------------------------------------------
	// Attachment retrieval
	// -------------------------------------------------------------------------

	/**
	 * @return WP_Post[]
	 */
	private function get_downloadable_attachments()
	{
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'post_mime_type' => self::ALLOWED_MIME_TYPES,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		return get_posts($args);
	}

	// -------------------------------------------------------------------------
	// Single attachment processing
	// -------------------------------------------------------------------------

	/**
	 * @param WP_Post $attachment
	 */
	private function process_single(WP_Post $attachment)
	{
		$data = $this->derive_attachment_data($attachment);
		$this->log(sprintf('  → %s | free:%s | tags:%s', $data['title'], $data['is_free'] ? 'yes' : 'no', implode(', ', $data['tags'])));

		if ($this->dry_run) {
			return;
		}

		$product_id = $this->upsert_product($attachment, $data, array($attachment));
		$cpt_id     = $this->upsert_cpt($attachment, $data, $product_id);
		$this->log(sprintf('    Product ID: %d | CPT ID: %d', $product_id, $cpt_id));
	}

	// -------------------------------------------------------------------------
	// Bundle processing
	// -------------------------------------------------------------------------

	/**
	 * @param string    $family      Bundle family slug (e.g. 'afirmaciones').
	 * @param WP_Post[] $attachments All attachments in this family.
	 */
	private function process_bundle($family, array $attachments)
	{
		$title = ucfirst($family) . ' — Pack completo';
		$tags  = array(self::BUNDLE_TAG_SLUG);

		// Merge tags from all members.
		foreach ($attachments as $a) {
			$d    = $this->derive_attachment_data($a);
			$tags = array_unique(array_merge($tags, $d['tags']));
		}

		$data = array(
			'title'   => $title,
			'is_free' => false,
			'price'   => self::BUNDLE_PRICE,
			'excerpt' => sprintf(
				/* translators: %d: number of items */
				__('Pack de %d afirmaciones. [needs_review]', 'daniela-child'),
				count($attachments)
			),
			'tags'    => $tags,
		);

		$this->log(sprintf('  [BUNDLE] %s | %d files | tags:%s', $title, count($attachments), implode(', ', $tags)));

		if ($this->dry_run) {
			return;
		}

		// Use first attachment as canonical attachment for idempotency key.
		$canonical   = $attachments[0];
		$product_id  = $this->upsert_bundle_product($family, $data, $attachments);
		$cpt_id      = $this->upsert_cpt($canonical, $data, $product_id, 'bundle_' . $family);
		$this->log(sprintf('    Bundle product ID: %d | CPT ID: %d', $product_id, $cpt_id));
	}

	// -------------------------------------------------------------------------
	// Data derivation
	// -------------------------------------------------------------------------

	/**
	 * Derive all data from an attachment post.
	 *
	 * @param  WP_Post $attachment
	 * @return array { title, is_free, price, excerpt, tags }
	 */
	private function derive_attachment_data(WP_Post $attachment)
	{
		// Title: use attachment title if meaningful, else clean filename.
		$title = trim($attachment->post_title);
		if (empty($title)) {
			$filename = pathinfo(get_attached_file($attachment->ID), PATHINFO_FILENAME);
			$title    = $this->prettify_filename($filename);
		}

		// is_free detection: check title only for the "gratuito" keyword (per business rule).
		$haystack = strtolower($title);
		$is_free  = false;
		foreach (self::FREE_KEYWORDS as $kw) {
			if (false !== strpos($haystack, $kw)) {
				$is_free = true;
				break;
			}
		}

		$price = $is_free ? 0.00 : self::DEFAULT_PAID_PRICE;

		// Excerpt: use attachment description/caption if available, else placeholder.
		$excerpt = trim($attachment->post_content);
		if (empty($excerpt)) {
			$excerpt = trim($attachment->post_excerpt);
		}
		if (empty($excerpt)) {
			$excerpt = $title . '. [needs_review]';
		} else {
			$excerpt = wp_trim_words($excerpt, 30);
		}

		// Tags: derived from title keywords.
		$tags = $this->derive_tags($title);

		return compact('title', 'is_free', 'price', 'excerpt', 'tags');
	}

	/**
	 * Convert a filename slug to a human-readable title.
	 *
	 * @param  string $filename  Filename without extension.
	 * @return string
	 */
	private function prettify_filename($filename)
	{
		$name = str_replace(array('-', '_'), ' ', $filename);
		$name = preg_replace('/\s+/', ' ', $name);
		$name = trim($name);
		return ucwords($name);
	}

	/**
	 * Derive topic tags from the attachment title.
	 *
	 * @param  string $title
	 * @return string[]  Tag slugs.
	 */
	private function derive_tags($title)
	{
		$lower = strtolower(remove_accents($title));
		$tags  = array();
		foreach (self::TOPIC_KEYWORDS as $keyword => $tag_slug) {
			if (false !== strpos($lower, $keyword)) {
				$tags[] = $tag_slug;
			}
		}
		return array_unique($tags);
	}

	/**
	 * Detect whether an attachment belongs to a bundle family.
	 *
	 * Returns the family slug (lowercased) or empty string.
	 *
	 * @param  WP_Post $attachment
	 * @return string
	 */
	private function detect_bundle_family(WP_Post $attachment)
	{
		$lower = strtolower(remove_accents($attachment->post_title . ' ' . basename((string) get_attached_file($attachment->ID))));
		if (false !== strpos($lower, self::BUNDLE_FAMILY_PREFIX)) {
			return self::BUNDLE_FAMILY_PREFIX;
		}
		return '';
	}

	// -------------------------------------------------------------------------
	// WooCommerce product upsert
	// -------------------------------------------------------------------------

	/**
	 * Create or update a simple downloadable WooCommerce product.
	 *
	 * @param  WP_Post  $attachment  Source attachment (for meta + download file).
	 * @param  array    $data        Derived data.
	 * @param  WP_Post[] $sources    Attachments to include as downloadable files.
	 * @return int                   Product post ID.
	 */
	private function upsert_product(WP_Post $attachment, array $data, array $sources)
	{
		$existing_id = $this->find_product_by_source($attachment->ID);

		if ($existing_id && ! $this->force_update) {
			$this->log(sprintf('    [SKIP] Product %d already exists for attachment %d.', $existing_id, $attachment->ID));
			return $existing_id;
		}

		if ($existing_id) {
			$product = wc_get_product($existing_id);
			$this->log(sprintf('    [UPDATE] Product %d for attachment %d.', $existing_id, $attachment->ID));
		} else {
			$product = new WC_Product_Simple();
			$this->log(sprintf('    [CREATE] New product for attachment %d.', $attachment->ID));
		}

		$product->set_name($data['title']);
		$product->set_status('publish');
		$product->set_catalog_visibility('visible');
		$product->set_downloadable(true);
		$product->set_virtual(true);
		$product->set_price($data['price']);
		$product->set_regular_price($data['price']);
		$product->set_short_description($data['excerpt']);

		// Downloadable files.
		$download_files = array();
		foreach ($sources as $src) {
			$file_url = wp_get_attachment_url($src->ID);
			if ($file_url) {
				$download_id              = md5($file_url);
				$download_files[$download_id] = array(
					'id'   => $download_id,
					'name' => $src->post_title ?: basename($file_url),
					'file' => $file_url,
				);
			}
		}
		$product->set_downloads($download_files);
		$product->set_download_limit(10);
		$product->set_download_expiry(-1);

		$product_id = $product->save();

		// Store source attachment ID for idempotency.
		update_post_meta($product_id, '_dm_source_attachment_id', $attachment->ID);

		// Assign product_tag terms for topic navigation.
		$this->assign_product_tags($product_id, $data['tags']);

		// Assign product_cat base category for recursos catalog visibility.
		$this->assign_product_categories($product_id);

		return $product_id;
	}

	/**
	 * Create or update a bundle product with multiple downloadable files.
	 *
	 * @param  string    $family_slug Canonical family slug (e.g. 'afirmaciones').
	 * @param  array     $data        Derived data for the bundle.
	 * @param  WP_Post[] $attachments All attachments in the bundle.
	 * @return int                    Product post ID.
	 */
	private function upsert_bundle_product($family_slug, array $data, array $attachments)
	{
		// For bundles, use first attachment ID + family slug as idempotency key.
		$canonical_id = $attachments[0]->ID;
		$meta_key     = '_dm_source_bundle_family';

		// Find existing bundle product.
		$existing = get_posts(array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_key'       => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $family_slug, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'         => 'ids',
		));

		$existing_id = ! empty($existing) ? (int) $existing[0] : 0;

		if ($existing_id && ! $this->force_update) {
			$this->log(sprintf('    [SKIP] Bundle product %d already exists for family "%s".', $existing_id, $family_slug));
			return $existing_id;
		}

		if ($existing_id) {
			$product = wc_get_product($existing_id);
			$this->log(sprintf('    [UPDATE] Bundle product %d for family "%s".', $existing_id, $family_slug));
		} else {
			$product = new WC_Product_Simple();
			$this->log(sprintf('    [CREATE] New bundle product for family "%s".', $family_slug));
		}

		$product->set_name($data['title']);
		$product->set_status('publish');
		$product->set_catalog_visibility('visible');
		$product->set_downloadable(true);
		$product->set_virtual(true);
		$product->set_price($data['price']);
		$product->set_regular_price($data['price']);
		$product->set_short_description($data['excerpt']);

		// Add all attachment files as downloads.
		$download_files = array();
		foreach ($attachments as $src) {
			$file_url = wp_get_attachment_url($src->ID);
			if ($file_url) {
				$download_id                    = md5($file_url);
				$download_files[$download_id] = array(
					'id'   => $download_id,
					'name' => $src->post_title ?: basename($file_url),
					'file' => $file_url,
				);
			}
		}
		$product->set_downloads($download_files);
		$product->set_download_limit(10);
		$product->set_download_expiry(-1);

		$product_id = $product->save();

		update_post_meta($product_id, $meta_key, $family_slug);
		update_post_meta($product_id, '_dm_source_attachment_id', $canonical_id);

		$this->assign_product_tags($product_id, $data['tags']);

		// Assign product_cat base category for recursos catalog visibility.
		$this->assign_product_categories($product_id);

		return $product_id;
	}

	// -------------------------------------------------------------------------
	// CPT dm_recurso upsert
	// -------------------------------------------------------------------------

	/**
	 * Create or update a dm_recurso CPT linked to the given product.
	 *
	 * @param  WP_Post $attachment  Source attachment.
	 * @param  array   $data        Derived data.
	 * @param  int     $product_id  WooCommerce product ID to link.
	 * @param  string  $source_key  Override idempotency key (for bundles).
	 * @return int                  CPT post ID.
	 */
	private function upsert_cpt(WP_Post $attachment, array $data, $product_id, $source_key = '')
	{
		$source_id  = $source_key ?: (string) $attachment->ID;
		$existing   = $this->find_cpt_by_source($source_id);

		// Build content: minimal description + CTA placeholder.
		$content = sprintf(
			"<p>%s</p>\n<!-- CTA handled by dm_cpt_render_cta() -->",
			wp_kses_post($data['excerpt'])
		);

		if ($existing && ! $this->force_update) {
			$this->log(sprintf('    [SKIP] CPT %d already exists for source "%s".', $existing, $source_id));
			// Ensure product link is always current.
			update_post_meta($existing, '_dm_wc_product_id', $product_id);
			return $existing;
		}

		$post_data = array(
			'post_type'    => 'dm_recurso',
			'post_status'  => 'publish',
			'post_title'   => $data['title'],
			'post_excerpt' => wp_strip_all_tags($data['excerpt']),
			'post_content' => $content,
		);

		if ($existing) {
			$post_data['ID'] = $existing;
			$cpt_id          = wp_update_post($post_data);
			$this->log(sprintf('    [UPDATE] CPT %d for source "%s".', $cpt_id, $source_id));
		} else {
			$cpt_id = wp_insert_post($post_data);
			$this->log(sprintf('    [CREATE] New CPT %d for source "%s".', $cpt_id, $source_id));
		}

		if (is_wp_error($cpt_id) || ! $cpt_id) {
			$this->log('    ERROR: Failed to save dm_recurso CPT.');
			return 0;
		}

		// Store source key + link to product.
		update_post_meta($cpt_id, '_dm_source_attachment_id', $source_id);
		update_post_meta($cpt_id, '_dm_wc_product_id', $product_id);

		// Assign dm_tema taxonomy terms from tags.
		$this->assign_cpt_temas($cpt_id, $data['tags']);

		return $cpt_id;
	}

	// -------------------------------------------------------------------------
	// Taxonomy helpers
	// -------------------------------------------------------------------------

	/**
	 * Ensure product_tag terms exist and assign them to a product.
	 *
	 * @param int      $product_id Product post ID.
	 * @param string[] $tag_slugs  Array of tag slugs.
	 */
	private function assign_product_tags($product_id, array $tag_slugs)
	{
		if (empty($tag_slugs)) {
			return;
		}

		$term_ids = array();
		foreach ($tag_slugs as $slug) {
			$term = get_term_by('slug', $slug, 'product_tag');
			if (! $term) {
				$result = wp_insert_term(ucwords(str_replace('-', ' ', $slug)), 'product_tag', array('slug' => $slug));
				if (! is_wp_error($result)) {
					$term_ids[] = $result['term_id'];
				}
			} else {
				$term_ids[] = $term->term_id;
			}
		}

		wp_set_object_terms($product_id, $term_ids, 'product_tag', true);
	}

	/**
	 * Ensure dm_tema terms exist and assign them to a dm_recurso CPT.
	 *
	 * @param int      $cpt_id    CPT post ID.
	 * @param string[] $tag_slugs Array of tag slugs.
	 */
	private function assign_cpt_temas($cpt_id, array $tag_slugs)
	{
		if (empty($tag_slugs)) {
			return;
		}

		$term_ids = array();
		foreach ($tag_slugs as $slug) {
			// Skip the bundle tag — it's a WooCommerce product_tag, not a tema.
			if (self::BUNDLE_TAG_SLUG === $slug) {
				continue;
			}

			$term = get_term_by('slug', $slug, 'dm_tema');
			if (! $term) {
				$result = wp_insert_term(ucwords(str_replace('-', ' ', $slug)), 'dm_tema', array('slug' => $slug));
				if (! is_wp_error($result)) {
					$term_ids[] = $result['term_id'];
				}
			} else {
				$term_ids[] = $term->term_id;
			}
		}

		wp_set_object_terms($cpt_id, $term_ids, 'dm_tema', true);
	}

	/**
	 * Assign product_cat category to a product.
	 *
	 * Assigns only the base category 'recursos' (no sub-categories).
	 *
	 * @param int $product_id Product post ID.
	 */
	private function assign_product_categories($product_id)
	{
		$parent_id = $this->ensure_product_cat('recursos', 'Recursos', 0);
		$term_ids = array_filter(array($parent_id));
		if (! empty($term_ids)) {
			wp_set_object_terms($product_id, $term_ids, 'product_cat', false);
		}
	}

	/**
	 * Ensure a product_cat term exists and return its term ID.
	 *
	 * @param string $slug      Category slug.
	 * @param string $name      Category display name.
	 * @param int    $parent_id Parent term ID (0 = top level).
	 * @return int              Term ID, or 0 on failure.
	 */
	private function ensure_product_cat($slug, $name, $parent_id = 0)
	{
		$term = get_term_by('slug', $slug, 'product_cat');
		if ($term) {
			return (int) $term->term_id;
		}

		$args = array('slug' => $slug);
		if ($parent_id > 0) {
			$args['parent'] = $parent_id;
		}
		$result = wp_insert_term($name, 'product_cat', $args);
		if (is_wp_error($result)) {
			$this->log(sprintf('    ERROR: Could not create product_cat "%s": %s', $slug, $result->get_error_message()));
			return 0;
		}
		return (int) $result['term_id'];
	}

	// -------------------------------------------------------------------------
	// Lookup helpers
	// -------------------------------------------------------------------------

	/**
	 * Find an existing product by its source attachment ID.
	 *
	 * @param  int $attachment_id
	 * @return int  Product ID, or 0 if not found.
	 */
	private function find_product_by_source($attachment_id)
	{
		$posts = get_posts(array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_key'       => '_dm_source_attachment_id', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => (string) $attachment_id,     // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'         => 'ids',
		));
		return ! empty($posts) ? (int) $posts[0] : 0;
	}

	/**
	 * Find an existing dm_recurso CPT by its source key.
	 *
	 * @param  string $source_key  Attachment ID or bundle key.
	 * @return int                 CPT ID, or 0 if not found.
	 */
	private function find_cpt_by_source($source_key)
	{
		$posts = get_posts(array(
			'post_type'      => 'dm_recurso',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_key'       => '_dm_source_attachment_id', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $source_key,                 // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'         => 'ids',
		));
		return ! empty($posts) ? (int) $posts[0] : 0;
	}

	// -------------------------------------------------------------------------
	// Logging
	// -------------------------------------------------------------------------

	/**
	 * @param string $message
	 */
	private function log($message)
	{
		$this->log[] = $message;
	}
}
