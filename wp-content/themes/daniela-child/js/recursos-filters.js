/**
 * Recursos Hub — Progressive Enhancement Filters
 *
 * When JS is available, filter clicks update the URL querystring and re-render
 * the grid via fetch() without a full page reload. Falls back to normal links
 * (querystring navigation) when JS is unavailable or fetch fails.
 *
 * No external libraries. Vanilla JS only.
 */
( function () {
	'use strict';

	/**
	 * Return the .dm-recursos wrapper element, or null if not on page.
	 * @returns {HTMLElement|null}
	 */
	function getWrapper() {
		return document.querySelector( '.dm-recursos' );
	}

	/**
	 * Parse the response HTML and extract the new .dm-recursos innerHTML.
	 * @param {string} html Full page HTML string.
	 * @returns {string|null} Inner HTML of .dm-recursos, or null if not found.
	 */
	function extractRecursosHTML( html ) {
		const parser = new DOMParser();
		const doc    = parser.parseFromString( html, 'text/html' );
		const el     = doc.querySelector( '.dm-recursos' );
		return el ? el.outerHTML : null;
	}

	/**
	 * Replace the wrapper element with new HTML.
	 * @param {HTMLElement} wrapper  Current wrapper.
	 * @param {string}      newHTML  New outer HTML for the wrapper.
	 */
	function replaceWrapper( wrapper, newHTML ) {
		const temp = document.createElement( 'div' );
		temp.innerHTML = newHTML;
		const newWrapper = temp.firstElementChild;
		if ( newWrapper ) {
			wrapper.parentNode.replaceChild( newWrapper, wrapper );
			// Re-bind events on the newly inserted wrapper.
			bindFilterEvents( newWrapper );
		}
	}

	/**
	 * Show a loading state on the wrapper.
	 * @param {HTMLElement} wrapper
	 */
	function setLoading( wrapper ) {
		wrapper.setAttribute( 'aria-busy', 'true' );
		wrapper.style.opacity = '0.6';
	}

	/**
	 * Remove loading state.
	 * @param {HTMLElement} wrapper
	 */
	function clearLoading( wrapper ) {
		wrapper.removeAttribute( 'aria-busy' );
		wrapper.style.opacity = '';
	}

	/**
	 * Fetch the new page HTML for a given URL and swap the grid.
	 * @param {string}      url     Target URL with filter querystring.
	 * @param {HTMLElement} wrapper Current .dm-recursos wrapper.
	 */
	function fetchAndSwap( url, wrapper ) {
		setLoading( wrapper );

		fetch( url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } } )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Network response was not ok: ' + response.status );
				}
				return response.text();
			} )
			.then( function ( html ) {
				const newHTML = extractRecursosHTML( html );
				if ( newHTML ) {
					replaceWrapper( wrapper, newHTML );
					// Update browser URL without reload.
					history.pushState( { url: url }, '', url );
				} else {
					// Fallback: let the browser navigate normally.
					window.location.href = url;
				}
			} )
			.catch( function () {
				// On any error, fall back to normal navigation.
				window.location.href = url;
			} );
	}

	/**
	 * Bind click handlers to all .dm-filter-pill links inside wrapper.
	 * @param {HTMLElement} wrapper
	 */
	function bindFilterEvents( wrapper ) {
		const pills = wrapper.querySelectorAll( '.dm-filter-pill' );
		pills.forEach( function ( pill ) {
			pill.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				const url = pill.href;
				if ( ! url ) { return; }
				fetchAndSwap( url, wrapper );
			} );
		} );
	}

	/**
	 * Handle browser back/forward navigation.
	 */
	window.addEventListener( 'popstate', function () {
		const wrapper = getWrapper();
		if ( wrapper ) {
			fetchAndSwap( window.location.href, wrapper );
		}
	} );

	/**
	 * Bootstrap on DOMContentLoaded.
	 */
	document.addEventListener( 'DOMContentLoaded', function () {
		const wrapper = getWrapper();
		if ( wrapper ) {
			bindFilterEvents( wrapper );
		}
	} );
}() );
