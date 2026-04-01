/**
 * Cart Drawer — Off-canvas mini-cart (right side).
 *
 * Opens the slide-in drawer when a product is added to cart via AJAX.
 * Works with WooCommerce fragments (wc-cart-fragments) which automatically
 * refresh the .widget_shopping_cart_content element in the drawer.
 *
 * Replaces add-to-cart-popup.js — do not enqueue both simultaneously.
 *
 * Requires: jQuery, wc-add-to-cart, wc-cart-fragments.
 */
( function ( $ ) {
	'use strict';

	var $drawer  = null;
	var $overlay = null;
	var $close   = null;
	var isOpen   = false;

	/**
	 * Cache DOM references and bind close triggers.
	 */
	function init() {
		$drawer  = $( '#dm-cart-drawer' );
		$overlay = $( '#dm-cart-drawer-overlay' );
		$close   = $( '#dm-cart-drawer-close' );

		if ( ! $drawer.length ) {
			return;
		}

		$overlay.on( 'click', closeDrawer );
		$close.on( 'click', closeDrawer );

		$( document ).on( 'keydown', function ( e ) {
			if ( ( e.key === 'Escape' || e.key === 'Esc' ) && isOpen ) {
				closeDrawer();
			}
		} );
	}

	/**
	 * Slide the drawer in from the right.
	 */
	function openDrawer() {
		if ( ! $drawer || ! $drawer.length ) {
			return;
		}

		isOpen = true;
		$drawer.removeAttr( 'hidden' );

		// Trigger reflow so the CSS transition fires.
		// eslint-disable-next-line no-unused-expressions
		$drawer[0].offsetHeight;

		$drawer.addClass( 'is-open' );
		$( 'body' ).addClass( 'dm-cart-drawer-open' );

		// Move focus to close button for keyboard/screen-reader users.
		setTimeout( function () {
			if ( $close && $close.length ) {
				$close.trigger( 'focus' );
			}
		}, 310 );
	}

	/**
	 * Slide the drawer out and restore focus.
	 */
	function closeDrawer() {
		if ( ! $drawer || ! $drawer.length ) {
			return;
		}

		isOpen = false;
		$drawer.removeClass( 'is-open' );
		$( 'body' ).removeClass( 'dm-cart-drawer-open' );

		// Re-add [hidden] after the CSS transition completes (~350 ms).
		setTimeout( function () {
			if ( ! isOpen ) {
				$drawer.attr( 'hidden', '' );
			}
		}, 360 );
	}

	// Open on WooCommerce AJAX add-to-cart success.
	$( document.body ).on( 'added_to_cart', function () {
		openDrawer();
	} );

	$( document ).ready( function () {
		init();
	} );

}( jQuery ) );
