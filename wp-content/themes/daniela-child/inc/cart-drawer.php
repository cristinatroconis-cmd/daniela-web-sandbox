<?php
/**
 * Cart Drawer — Off-canvas mini-cart (right side).
 *
 * Replaces the old add-to-cart-popup.js modal overlay with a slide-in drawer.
 * Opens automatically when a product is added to cart via AJAX.
 *
 * The drawer HTML is injected into the footer on every front-end page (when
 * WooCommerce is active).  WooCommerce's own fragment refresh keeps the
 * mini-cart contents up-to-date without extra AJAX calls.
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disable Shoptimizer's built-in cart drawer so it doesn't open alongside ours.
 *
 * Shoptimizer registers shoptimizer_header_cart_drawer on shoptimizer_before_site
 * (priority 5) at file-include time. We remove it here — after the parent theme
 * has already run — to prevent two separate drawers from opening on added_to_cart.
 */
remove_action( 'shoptimizer_before_site', 'shoptimizer_header_cart_drawer', 5 );

/**
 * Inject the cart drawer HTML into the footer.
 *
 * Runs late (priority 100) so it lands after the theme's own footer markup.
 * The .widget_shopping_cart_content wrapper class is required by WooCommerce's
 * fragment refresh mechanism (wc-cart-fragments.js) to update the cart contents
 * after each add-to-cart AJAX request.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		?>
		<div id="dm-cart-drawer"
		     class="dm-cart-drawer"
		     role="dialog"
		     aria-modal="true"
		     aria-label="<?php esc_attr_e( 'Carrito', 'daniela-child' ); ?>"
		     hidden>

			<div class="dm-cart-drawer__overlay" id="dm-cart-drawer-overlay" aria-hidden="true"></div>

			<aside class="dm-cart-drawer__panel">

				<header class="dm-cart-drawer__header">
					<h2 class="dm-cart-drawer__title">
						<?php esc_html_e( 'Tu carrito', 'daniela-child' ); ?>
					</h2>
					<button class="dm-cart-drawer__close"
					        id="dm-cart-drawer-close"
					        aria-label="<?php esc_attr_e( 'Cerrar carrito', 'daniela-child' ); ?>">
						&#10005;
					</button>
				</header>

				<div class="dm-cart-drawer__body">
					<?php
					/*
					 * .widget_shopping_cart_content is required by wc-cart-fragments to refresh
					 * the mini-cart after AJAX add-to-cart. We suppress WooCommerce's own button
					 * hooks here so they don't duplicate the CTAs rendered below in the footer.
					 */
					remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
					remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );
					?>
					<div class="widget_shopping_cart_content">
						<?php woocommerce_mini_cart(); ?>
					</div>
				</div>

				<footer class="dm-cart-drawer__footer">
					<button type="button"
					        id="dm-cart-drawer-continue"
					        class="dm-btn dm-btn--ghost dm-cart-drawer__btn--continue">
						<?php esc_html_e( 'Seguir comprando', 'daniela-child' ); ?>
					</button>
					<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>"
					   class="dm-btn dm-btn--primary dm-cart-drawer__btn--checkout">
						<?php esc_html_e( 'Checkout', 'daniela-child' ); ?>
					</a>
				</footer>

			</aside>
		</div>
		<?php
	},
	100
);
