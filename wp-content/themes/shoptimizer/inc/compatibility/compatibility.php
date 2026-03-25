<?php

function shoptimizer_compatability_styles() {

	$compatibility_uri = get_template_directory_uri() . '/inc/compatibility';

	if ( class_exists( 'Addify_Request_For_Quote' ) ) {
		wp_enqueue_style( 'shoptimizer-wc-quote-style', $compatibility_uri . '/wc-quote/wc-quote.css' );
	}
	if ( class_exists( 'Addify_Product_Videos' ) ) {
		wp_enqueue_style( 'shoptimizer-product-videos-style', $compatibility_uri . '/woocommerce-product-videos/woocommerce-product-videos.css' );
	}
	if ( class_exists( 'Commercegurus_WC_Product_Filters' ) ) {
		wp_enqueue_style( 'shoptimizer-cgurus-product-filters-style', $compatibility_uri . '/commercegurus-product-filters/commercegurus-product-filters.css' );
	}
	if ( class_exists( 'WC_Quick_View' ) ) {
		wp_enqueue_style( 'shoptimizer-wc-quickview-style', $compatibility_uri . '/wc-quick-views/wc-quick-view.css' );
	}
	if ( class_exists( 'YITH_WCQV' ) ) {
		wp_enqueue_style( 'shoptimizer-yith-quickview-style', $compatibility_uri . '/yith-quick-view/yith-quick-view.css' );
	}
	if ( class_exists( 'WC_Composite_Products' ) ) {
		wp_enqueue_style( 'shoptimizer-composite-products-style', $compatibility_uri . '/woocommerce-composite-products/woocommerce-composite-products.css' );
	}
	if ( class_exists( 'WC_360_Image_Display' ) ) {
		wp_enqueue_style( 'shoptimizer-wc-360-style', $compatibility_uri . '/woocommerce-360-image/woocommerce-360-image.css' );
	}
	if ( class_exists( 'WeDevs_Dokan' ) ) {
		wp_enqueue_style( 'shoptimizer-dokan-style', $compatibility_uri . '/dokan/dokan.css' );
	}
	if ( class_exists( 'YITH_WCWL_Shortcode' ) ) {
		wp_enqueue_style( 'shoptimizer-yith-wishlist-style', $compatibility_uri . '/yith-wishlist/yith-wishlist.css' );
	}
}
add_action( 'wp_enqueue_scripts', 'shoptimizer_compatability_styles', 90 );

// CommerceGurus Product Filters support.
if ( class_exists( 'Commercegurus_WC_Product_Filters' ) && 'horizontal' === Commercegurus_WC_Product_Filters::get_layout() ) {
	require get_template_directory() . '/inc/compatibility/commercegurus-product-filters/commercegurus-product-filters.php';
}

// Dokan support.
if ( class_exists( 'WeDevs_Dokan' ) ) {
	require get_template_directory() . '/inc/compatibility/dokan/dokan.php';
}

// WC Quick View support.
if ( class_exists( 'WC_Quick_View' ) ) {
	require get_template_directory() . '/inc/compatibility/wc-quick-view/wc-quick-view.php';
}

// WooFunnels support.
if ( class_exists( 'WFFN_Core' ) ) {
	require get_template_directory() . '/inc/compatibility/woofunnels/woofunnels.php';
}

// YITH Wishlist support.
if ( class_exists( 'YITH_WCWL_Shortcode' ) ) {
	require get_template_directory() . '/inc/compatibility/yith-wishlist/yith-wishlist.php';
}

// YITH Quick View support.
if ( class_exists( 'YITH_WCQV' ) ) {
	require get_template_directory() . '/inc/compatibility/yith-quick-view/yith-quick-view.php';
}