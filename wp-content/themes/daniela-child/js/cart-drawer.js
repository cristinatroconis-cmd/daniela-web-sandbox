/**
 * Cart Drawer — Off-canvas mini-cart (right side).
 *
 * Opens the slide-in drawer when a product is added to cart via AJAX.
 * Works with WooCommerce fragments and also intercepts repeated clicks for
 * products already in the cart so the user stays on the same page.
 */
( function ( $ ) {
	'use strict';

	var $drawer  = null;
	var $overlay = null;
	var $close   = null;
	var isOpen   = false;
	var noticeTimer = null;
	var drawerConfig = ( typeof window.dmCartDrawer !== 'undefined' && window.dmCartDrawer ) ? window.dmCartDrawer : {};
	var inCartIds = {};
	var alreadyInCartMessage = drawerConfig.alreadyInCartMessage || 'Ya está en tu carrito';

	( drawerConfig.inCartIds || [] ).forEach( function ( productId ) {
		inCartIds[ String( productId ) ] = true;
	} );

	function markProductInCart( productId, isInCart ) {
		if ( ! productId ) {
			return;
		}

		if ( isInCart ) {
			inCartIds[ String( productId ) ] = true;
		} else {
			delete inCartIds[ String( productId ) ];
		}
	}

	function syncInCartIdsFromDrawer() {
		var nextState = {};
		$( '#dm-cart-drawer .remove[data-product_id]' ).each( function () {
			var productId = String( $( this ).data( 'product_id' ) || '' );
			if ( productId ) {
				nextState[ productId ] = true;
			}
		} );

		inCartIds = nextState;
	}

	function dmCardsRemoveViewCartLink() {
		$( '.dm-card a.added_to_cart.wc-forward, .dm-cta a.added_to_cart.wc-forward, .dm-single__actions a.added_to_cart.wc-forward, .dm-topic-card__cta a.added_to_cart.wc-forward, .dm-product-card a.added_to_cart.wc-forward' ).remove();
	}

	function showDrawerNotice( message ) {
		var $body = $drawer.find( '.dm-cart-drawer__body' );
		var $notice;

		if ( ! $body.length || ! message ) {
			return;
		}

		$notice = $body.find( '.dm-cart-drawer__notice' );
		if ( ! $notice.length ) {
			$notice = $( '<div class="dm-cart-drawer__notice" aria-live="polite"></div>' );
			$body.prepend( $notice );
		}

		$notice.stop( true, true ).text( message ).fadeIn( 150 );

		if ( noticeTimer ) {
			window.clearTimeout( noticeTimer );
		}

		noticeTimer = window.setTimeout( function () {
			$notice.fadeOut( 180 );
		}, 2200 );
	}

	function isHeader4MobileMenuContext() {
		return window.matchMedia( '(max-width: 992px)' ).matches && document.body.classList.contains( 'header-4' );
	}

	function setMobileMenuOpen( shouldOpen ) {
		var body = document.body;
		var menuToggle = document.querySelector( '.menu-toggle' );

		if ( ! body ) {
			return;
		}

		body.classList.toggle( 'mobile-toggled', !! shouldOpen );

		if ( menuToggle ) {
			menuToggle.setAttribute( 'aria-expanded', shouldOpen ? 'true' : 'false' );
		}
	}

	function initMobileHeaderMenuFallback() {
		document.addEventListener( 'click', function ( event ) {
			var toggle = event.target.closest( '.menu-toggle' );

			if ( ! toggle || ! isHeader4MobileMenuContext() ) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			if ( typeof event.stopImmediatePropagation === 'function' ) {
				event.stopImmediatePropagation();
			}

			setMobileMenuOpen( ! document.body.classList.contains( 'mobile-toggled' ) );
		}, true );

		document.addEventListener( 'click', function ( event ) {
			var caret = event.target;
			
			// Check if clicked element is the caret or is inside the caret
			while ( caret && caret !== document ) {
				if ( caret.classList && caret.classList.contains( 'caret' ) ) {
					break;
				}
				caret = caret.parentElement;
			}

			if ( ! caret || ! caret.classList.contains( 'caret' ) ) {
				return;
			}

			var parentLi = caret.closest( '.menu-item-has-children' );
			if ( parentLi ) {
				event.preventDefault();
				event.stopPropagation();
				if ( typeof event.stopImmediatePropagation === 'function' ) {
					event.stopImmediatePropagation();
				}
				parentLi.classList.toggle( 'expanded' );
			}
		}, true );

		document.addEventListener( 'click', function ( event ) {
			var clickInNav = event.target.closest( '#site-navigation' );
			var clickOnToggle = event.target.closest( '.menu-toggle' );
			var clickInNavLayer = event.target.closest( '.col-full-nav' );

			if ( ! isHeader4MobileMenuContext() || ! document.body.classList.contains( 'mobile-toggled' ) ) {
				return;
			}

			if ( clickInNav || clickOnToggle || ! clickInNavLayer ) {
				return;
			}

			setMobileMenuOpen( false );
		}, true );
	}

	function init() {
		$drawer  = $( '#dm-cart-drawer' );
		$overlay = $( '#dm-cart-drawer-overlay' );
		$close   = $( '#dm-cart-drawer-close' );

		if ( ! $drawer.length ) {
			return;
		}

		$overlay.on( 'click', closeDrawer );
		$close.on( 'click', closeDrawer );
		$( '#dm-cart-drawer-continue' ).on( 'click', closeDrawer );
		initMobileHeaderMenuFallback();

		$( document ).on( 'keydown', function ( e ) {
			if ( ( e.key === 'Escape' || e.key === 'Esc' ) && document.body.classList.contains( 'mobile-toggled' ) ) {
				setMobileMenuOpen( false );
			}

			if ( ( e.key === 'Escape' || e.key === 'Esc' ) && isOpen ) {
				closeDrawer();
			}
		} );
	}

	function openDrawer() {
		if ( ! $drawer || ! $drawer.length ) {
			return;
		}

		isOpen = true;
		$drawer.removeAttr( 'hidden' );
		$drawer[0].offsetHeight;
		$drawer.addClass( 'is-open' );
		$( 'body' ).addClass( 'dm-cart-drawer-open' );

		setTimeout( function () {
			if ( $close && $close.length ) {
				$close.trigger( 'focus' );
			}
		}, 310 );
	}

	function closeDrawer() {
		if ( ! $drawer || ! $drawer.length ) {
			return;
		}

		isOpen = false;
		$drawer.removeClass( 'is-open' );
		$( 'body' ).removeClass( 'dm-cart-drawer-open' );

		setTimeout( function () {
			if ( ! isOpen ) {
				$drawer.attr( 'hidden', '' );
			}
		}, 360 );
	}

	$( document.body ).on( 'dm_cart_drawer_open', function () {
		openDrawer();
	} );

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '.site-header-cart [data-dm-cart-trigger="header"]' );

		if ( ! trigger ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();
		if ( typeof event.stopImmediatePropagation === 'function' ) {
			event.stopImmediatePropagation();
		}

		$( 'body' ).removeClass( 'drawer-open' );
		openDrawer();
	}, true );

	$( document ).on( 'click', '.dm-cta .add_to_cart_button, .dm-card .add_to_cart_button, .dm-topic-card .add_to_cart_button, .dm-product-card .add_to_cart_button', function ( e ) {
		var productId = String( $( this ).data( 'product_id' ) || '' );

		if ( productId && inCartIds[ productId ] ) {
			e.preventDefault();
			e.stopImmediatePropagation();
			openDrawer();
			showDrawerNotice( alreadyInCartMessage );
			window.setTimeout( dmCardsRemoveViewCartLink, 0 );
		}
	} );

	$( document.body ).on( 'added_to_cart', function ( event, fragments, cartHash, $button ) {
		openDrawer();

		if ( $button && $button.length ) {
			markProductInCart( $button.data( 'product_id' ), true );
		}

		window.setTimeout( dmCardsRemoveViewCartLink, 0 );
	} );

	$( document ).on( 'click', '#dm-cart-drawer .remove[data-product_id]', function () {
		markProductInCart( $( this ).data( 'product_id' ), false );
	} );

	$( document.body ).on( 'wc_fragments_loaded wc_fragments_refreshed', function () {
		syncInCartIdsFromDrawer();
		dmCardsRemoveViewCartLink();
	} );

	$( document ).ready( function () {
		init();
		syncInCartIdsFromDrawer();
		dmCardsRemoveViewCartLink();
	} );

}( jQuery ) );