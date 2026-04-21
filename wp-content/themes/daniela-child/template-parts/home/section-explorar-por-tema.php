<?php

/**
 * Explorar por tema — ¿Qué tipo de ayuda buscas?
 *
 * Destino del Slide 4 ("Explorar por tema") en la sección "¿Qué necesitas?".
 * Sin JS obligatorio (links normales). Usa product_tag como fuente de verdad
 * de los temas para mantener consistencia entre catálogos y archivo /temas/.
 *
 * Se puede incluir directamente o via shortcode [dm_temas_hub].
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
	exit;
}

/* -------------------------------------------------------------------------
   Tema activo desde URL (?tema=slug) — permite filtrar el hub por tema
   ---------------------------------------------------------------------- */
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$active_tema = isset($_GET['tema']) ? sanitize_title(wp_unslash($_GET['tema'])) : '';
$tema_label  = '';
if ($active_tema) {
	$tema_term  = get_term_by('slug', $active_tema, 'product_tag');
	$tema_label = $tema_term ? $tema_term->name : $active_tema;
}

/* -------------------------------------------------------------------------
	Secciones del hub con sus destinos.
	Cuando hay un tema activo, los links propagan el mismo parámetro público
	`tema` para conservar consistencia semántica entre pantallas.
   ---------------------------------------------------------------------- */
$temas_hub_url = home_url('/temas/');

$build_type_url = function (string $base_url) use ($active_tema): string {
	if (! $active_tema) {
		return $base_url;
	}
	return add_query_arg('tema', $active_tema, $base_url);
};

$hub_sections = [
	[
		'id'    => 'recursos',
		'label' => __('Recursos', 'daniela-child'),
		'desc'  => __('PDFs, guías y registros para trabajar a tu ritmo.', 'daniela-child'),
		'icon'  => '📄',
		'url'   => $build_type_url(home_url('/recursos/')),
	],
	[
		'id'    => 'cursos',
		'label' => __('Cursos', 'daniela-child'),
		'desc'  => __('Aprendizaje online paso a paso, cuando tú quieras.', 'daniela-child'),
		'icon'  => '🎓',
		'url'   => $build_type_url(home_url('/escuela/?tipo=cursos')),
	],
	[
		'id'    => 'talleres',
		'label' => __('Talleres', 'daniela-child'),
		'desc'  => __('Experiencias en vivo para trabajar en comunidad.', 'daniela-child'),
		'icon'  => '🤝',
		'url'   => $build_type_url(home_url('/escuela/?tipo=talleres')),
	],
	[
		'id'    => 'programas',
		'label' => __('Programas', 'daniela-child'),
		'desc'  => __('Procesos más profundos y acompañados.', 'daniela-child'),
		'icon'  => '🌱',
		'url'   => $build_type_url(home_url('/escuela/?tipo=programas')),
	],
	[
		'id'    => 'sesiones',
		'label' => __('Sesiones', 'daniela-child'),
		'desc'  => __('Apoyo profesional directo y personalizado.', 'daniela-child'),
		'icon'  => '💬',
		'url'   => $build_type_url(home_url('/servicios/')),
	],
];

/* -------------------------------------------------------------------------
	Temas disponibles desde WooCommerce product_tag.
	---------------------------------------------------------------------- */
$topic_tags = [];

if (function_exists('wc_get_product_tag_tax_class') || taxonomy_exists('product_tag')) {
	$cached = get_transient('dm_temas_hub_topics');

	if (false === $cached) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			"SELECT t.term_id, t.name, t.slug, COUNT(DISTINCT tr.object_id) AS cnt
			 FROM {$wpdb->terms} t
			 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
			 INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
			 INNER JOIN {$wpdb->term_relationships} tr2 ON tr2.object_id = tr.object_id
			 INNER JOIN {$wpdb->term_taxonomy} tt2 ON tt2.term_taxonomy_id = tr2.term_taxonomy_id
			 INNER JOIN {$wpdb->terms} t2 ON t2.term_id = tt2.term_id
			 WHERE tt.taxonomy = 'product_tag'
			   AND tt2.taxonomy = 'product_cat'
			   AND t2.slug IN ( 'recursos', 'escuela', 'servicios' )
			 GROUP BY t.term_id
			 HAVING cnt >= 1
			 ORDER BY t.name ASC"
		);
		$topic_tags = is_array($rows) ? $rows : [];
		set_transient('dm_temas_hub_topics', $topic_tags, HOUR_IN_SECONDS);
	} else {
		$topic_tags = $cached;
	}
}
?>
<div class="dm-temas-hub" id="dm-temas-hub">

	<!-- ── Encabezado ──────────────────────────────────────────────────── -->
	<header class="dm-temas-hub__header">
		<h2 class="dm-temas-hub__title">
			<?php esc_html_e('¿Qué estás buscando?', 'daniela-child'); ?>
		</h2>
		<p class="dm-temas-hub__subtitle">
			<?php esc_html_e('Explora por tipo de ayuda o por el tema que más te resuene.', 'daniela-child'); ?>
		</p>
		<?php if ($tema_label) : ?>
			<p class="dm-temas-hub__active-tema">
				<?php
				printf(
					/* translators: %s: topic label */
					esc_html__('Filtrando por: %s', 'daniela-child'),
					'<strong>' . esc_html($tema_label) . '</strong>'
				);
				?>
				<a class="dm-temas-hub__clear-tema" href="<?php echo esc_url($temas_hub_url); ?>">
					<?php esc_html_e('✕ Ver todo', 'daniela-child'); ?>
				</a>
			</p>
		<?php endif; ?>
	</header>

	<!-- ── Por tipo ─────────────────────────────────────────────────────── -->
	<section class="dm-temas-hub__section" aria-label="<?php esc_attr_e('Por tipo', 'daniela-child'); ?>">
		<h3 class="dm-temas-hub__section-title">
			<?php esc_html_e('Por tipo de formato', 'daniela-child'); ?>
		</h3>
		<ul class="dm-temas-hub__types">
			<?php foreach ($hub_sections as $sec) : ?>
				<li>
					<a
						class="dm-temas-hub__type-card"
						href="<?php echo esc_url($sec['url']); ?>">
						<span class="dm-temas-hub__type-icon" aria-hidden="true"><?php echo $sec['icon']; ?></span>
						<span class="dm-temas-hub__type-label"><?php echo esc_html($sec['label']); ?></span>
						<span class="dm-temas-hub__type-desc"><?php echo esc_html($sec['desc']); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<!-- ── Por tema ─────────────────────────────────────────────────────── -->
	<?php if (! empty($topic_tags)) : ?>
		<section class="dm-temas-hub__section" aria-label="<?php esc_attr_e('Por tema', 'daniela-child'); ?>">
			<h3 class="dm-temas-hub__section-title">
				<?php esc_html_e('O elige el tema que más te resuene', 'daniela-child'); ?>
			</h3>
			<ul class="dm-temas-hub__chips">
				<?php foreach ($topic_tags as $tag) :
					$is_active_chip = ($active_tema === $tag->slug);
					// Chip links filter the hub itself; a click shows all types for that tema.
					$chip_url = $is_active_chip
						? $temas_hub_url
						: add_query_arg('tema', $tag->slug, $temas_hub_url);
				?>
					<li>
						<a
							class="dm-temas-hub__chip<?php echo $is_active_chip ? ' dm-temas-hub__chip--active' : ''; ?>"
							href="<?php echo esc_url($chip_url); ?>"
							<?php echo $is_active_chip ? 'aria-current="true"' : ''; ?>>
							<?php echo esc_html($tag->name); ?>
							<span class="dm-temas-hub__chip-count"><?php echo absint($tag->cnt); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

</div><!-- /.dm-temas-hub -->