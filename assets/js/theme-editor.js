( function ( document, window ) {
	'use strict';

	const root = document.querySelector( '[data-adam-theme-editor]' );

	if ( ! root ) {
		return;
	}

	function isValidColor( value ) {
		return Boolean( value ) && window.CSS && window.CSS.supports( 'color', value );
	}

	function applyToken( input, suppliedValue ) {
		if ( ! input.dataset.adamToken ) {
			return;
		}

		let value = undefined === suppliedValue ? input.value.trim() : suppliedValue;

		if ( input.classList.contains( 'adam-css-color-value' ) ) {
			const valid = isValidColor( value );
			input.setAttribute( 'aria-invalid', String( ! valid ) );
			input.setCustomValidity( valid ? '' : input.dataset.invalidMessage );
			input.closest( '.adam-css-color-control' ).style.setProperty( '--adam-picker-color', valid ? value : 'transparent' );

			if ( ! valid ) {
				return;
			}
		}

		if ( input.dataset.unit && ! String( value ).endsWith( input.dataset.unit ) ) {
			value += input.dataset.unit;
		}

		root.style.setProperty( input.dataset.adamToken, value );
		const output = input.parentNode.querySelector( 'output' );
		if ( output ) {
			output.value = value;
		}
	}

	root.addEventListener( 'input', ( event ) => {
		const input = event.target;

		if ( input.classList.contains( 'adam-css-color-picker' ) ) {
			const textInput = input.closest( '.adam-css-color-control' ).querySelector( '.adam-css-color-value' );
			textInput.value = input.value;
			applyToken( textInput );
			return;
		}

		applyToken( input );
	} );

	root.querySelectorAll( '[data-adam-token]' ).forEach( ( input ) => applyToken( input ) );
}( document, window ) );
