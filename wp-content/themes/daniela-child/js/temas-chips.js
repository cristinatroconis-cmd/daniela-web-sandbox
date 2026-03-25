/**
 * Temas Chips — Progressive Enhancement
 *
 * On page load, smoothly scrolls the active chip into view so it is visible
 * without manual horizontal scrolling on small screens.
 *
 * No external libraries. Vanilla JS only.
 * Full non-JS fallback: chips work as plain links via ?tema= querystring.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var activeChip = document.querySelector( '.dm-chips .dm-chip--active' );
		if ( activeChip ) {
			activeChip.scrollIntoView( { behavior: 'smooth', block: 'nearest', inline: 'center' } );
		}
	} );
}() );
