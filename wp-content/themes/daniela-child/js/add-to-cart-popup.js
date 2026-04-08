/**
 * Add to Cart — Popup de confirmación.
 *
 * Intercepta el evento `added_to_cart` de WooCommerce (AJAX) y muestra
 * un popup con dos acciones:
 *   - "Seguir comprando" → cierra el popup (permanece en la página actual).
 *   - "Proceder al checkout" → navega a la URL de checkout de WooCommerce.
 *
 * Requiere: jQuery, wc-add-to-cart (WooCommerce JS).
 * No depende de librerías externas adicionales.
 */
( function ( $ ) {
	'use strict';

	var checkoutUrl = ( typeof dmCartPopup !== 'undefined' && dmCartPopup.checkout_url )
		? dmCartPopup.checkout_url
		: '/checkout/';

	/**
	 * Crea e inyecta el overlay + modal en el DOM (una sola vez).
	 */
	function buildModal() {
		if ( document.getElementById( 'dm-cart-popup' ) ) {
			return;
		}

		var overlay = document.createElement( 'div' );
		overlay.id        = 'dm-cart-popup-overlay';
		overlay.className = 'dm-cart-popup__overlay';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );
		overlay.setAttribute( 'aria-labelledby', 'dm-cart-popup-title' );

		overlay.innerHTML = [
			'<div id="dm-cart-popup" class="dm-cart-popup">',
			'  <button class="dm-cart-popup__close" aria-label="Cerrar" id="dm-cart-popup-close">&#10005;</button>',
			'  <div class="dm-cart-popup__icon">&#10003;</div>',
			'  <p id="dm-cart-popup-title" class="dm-cart-popup__message"></p>',
			'  <div class="dm-cart-popup__actions">',
			'    <button class="dm-btn dm-btn--ghost dm-cart-popup__btn--keep" id="dm-cart-popup-keep">',
			'      Seguir comprando',
			'    </button>',
			'    <a class="dm-btn dm-btn--primary dm-cart-popup__btn--checkout" id="dm-cart-popup-checkout" href="' + checkoutUrl + '">',
			'      Finalizar compra',
			'    </a>',
			'  </div>',
			'</div>',
		].join( '' );

		document.body.appendChild( overlay );

		// Cerrar al hacer clic en el overlay (fuera del modal).
		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				closeModal();
			}
		} );

		// Botón "×".
		document.getElementById( 'dm-cart-popup-close' ).addEventListener( 'click', closeModal );

		// Botón "Seguir comprando".
		document.getElementById( 'dm-cart-popup-keep' ).addEventListener( 'click', closeModal );

		// Cerrar con Escape.
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' || e.key === 'Esc' ) {
				closeModal();
			}
		} );
	}

	/**
	 * Muestra el popup con el nombre del producto añadido.
	 *
	 * @param {string} productName Nombre del producto.
	 */
	function openModal( productName ) {
		buildModal();
		var overlay = document.getElementById( 'dm-cart-popup-overlay' );
		var msgEl   = document.getElementById( 'dm-cart-popup-title' );

		if ( productName ) {
			msgEl.textContent = '✓ "' + productName + '" se agregó al carrito.';
		} else {
			msgEl.textContent = '✓ El producto se agregó al carrito.';
		}

		overlay.classList.add( 'is-visible' );
		overlay.removeAttribute( 'hidden' );

		// Focus en el botón "Seguir comprando" para accesibilidad.
		var keepBtn = document.getElementById( 'dm-cart-popup-keep' );
		if ( keepBtn ) {
			keepBtn.focus();
		}
	}

	/**
	 * Oculta el popup.
	 */
	function closeModal() {
		var overlay = document.getElementById( 'dm-cart-popup-overlay' );
		if ( overlay ) {
			overlay.classList.remove( 'is-visible' );
		}
	}

	/**
	 * Enlaza el evento `added_to_cart` de WooCommerce.
	 *
	 * WooCommerce dispara este evento jQuery en el body al completar
	 * el AJAX de "add to cart". Pasamos el nombre del producto desde
	 * el atributo data-product_name del botón clickeado.
	 */
	$( document.body ).on( 'added_to_cart', function ( e, fragments, cartHash, $button ) {
		var productName = '';
		if ( $button && $button.length ) {
			productName = $button.data( 'product_name' ) || $button.attr( 'data-product_name' ) || '';
		}
		openModal( productName );
	} );

}( jQuery ) );
