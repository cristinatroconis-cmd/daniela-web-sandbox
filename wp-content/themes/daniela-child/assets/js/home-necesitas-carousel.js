/**
 * Home — Carousel "¿Qué necesitas?" (sin Bootstrap)
 *
 * Comportamiento:
 *  - El autoplay comienza cuando el puntero entra en `.dm-necesitas` (sección completa).
 *  - Se detiene cuando el puntero sale.
 *  - Controles prev / next y dots funcionan siempre.
 *  - El intervalo (ms) se lee del atributo `data-autoplay` del wrapper `.dm-carousel`.
 *
 * @package Daniela_Child
 */

( function () {
	'use strict';

	/**
	 * Inicializa un carousel individual.
	 *
	 * @param {HTMLElement} carousel  El elemento .dm-carousel
	 * @param {HTMLElement} section   El elemento .dm-necesitas (hover target)
	 */
	function initCarousel( carousel, section ) {
		var track    = carousel.querySelector( '.dm-carousel__track' );
		var slides   = carousel.querySelectorAll( '.dm-carousel__slide' );
		var btnPrev  = carousel.querySelector( '.dm-carousel__btn--prev' );
		var btnNext  = carousel.querySelector( '.dm-carousel__btn--next' );
		var dotsWrap = carousel.querySelector( '.dm-carousel__dots' );

		if ( ! track || slides.length === 0 ) {
			return;
		}

		var total      = slides.length;
		var current    = 0;
		var autoplayMs = parseInt( carousel.getAttribute( 'data-autoplay' ), 10 ) || 4000;
		var timer      = null;

		/* ---- dots ------------------------------------------------- */
		var dots = [];
		if ( dotsWrap ) {
			for ( var i = 0; i < total; i++ ) {
				var dot = document.createElement( 'button' );
				dot.className  = 'dm-carousel__dot';
				dot.type       = 'button';
				dot.setAttribute( 'aria-label', 'Ir a la diapositiva ' + ( i + 1 ) );
				dotsWrap.appendChild( dot );
				dots.push( dot );
				/* IIFE para capturar i */
				( function ( idx ) {
					dot.addEventListener( 'click', function () {
						goTo( idx );
					} );
				}( i ) );
			}
		}

		/* ---- goTo -------------------------------------------------- */
		function goTo( idx ) {
			current = ( idx + total ) % total;
			track.style.transform = 'translateX(-' + ( current * 100 ) + '%)';
			dots.forEach( function ( d, di ) {
				d.classList.toggle( 'is-active', di === current );
			} );
		}

		/* ---- prev / next ------------------------------------------ */
		if ( btnPrev ) {
			btnPrev.addEventListener( 'click', function () {
				goTo( current - 1 );
			} );
		}
		if ( btnNext ) {
			btnNext.addEventListener( 'click', function () {
				goTo( current + 1 );
			} );
		}

		/* ---- autoplay (hover en la sección completa) -------------- */
		function startAutoplay() {
			if ( timer ) {
				return;
			}
			timer = setInterval( function () {
				goTo( current + 1 );
			}, autoplayMs );
		}

		function stopAutoplay() {
			clearInterval( timer );
			timer = null;
		}

		var hoverTarget = section || carousel;

		hoverTarget.addEventListener( 'mouseenter', startAutoplay );
		hoverTarget.addEventListener( 'mouseleave', stopAutoplay );

		/* accesibilidad: detener en foco dentro del carousel */
		carousel.addEventListener( 'focusin', stopAutoplay );
		carousel.addEventListener( 'focusout', function () {
			if ( ! carousel.contains( document.activeElement ) ) {
				/* no reiniciar aquí; solo se reinicia con hover */
			}
		} );

		/* ---- touch swipe ------------------------------------------ */
		var touchStartX = 0;
		carousel.addEventListener( 'touchstart', function ( e ) {
			touchStartX = e.changedTouches[ 0 ].clientX;
		}, { passive: true } );
		carousel.addEventListener( 'touchend', function ( e ) {
			var diff = touchStartX - e.changedTouches[ 0 ].clientX;
			if ( Math.abs( diff ) > 40 ) {
				goTo( diff > 0 ? current + 1 : current - 1 );
			}
		}, { passive: true } );

		/* ---- init ------------------------------------------------- */
		goTo( 0 );
	}

	/* ================================================================
	   Bootstrap: esperar DOM listo
	   ================================================================ */
	function bootstrap() {
		var sections = document.querySelectorAll( '.dm-necesitas' );
		sections.forEach( function ( section ) {
			var carousel = section.querySelector( '.dm-carousel' );
			if ( carousel ) {
				initCarousel( carousel, section );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', bootstrap );
	} else {
		bootstrap();
	}
}() );
