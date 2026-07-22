( function ( document, window ) {
	'use strict';

	const root = document.querySelector( '[data-adam-theme-editor]' );

	if ( ! root ) {
		return;
	}

	function isValidColor( value ) {
		return Boolean( value ) && window.CSS && window.CSS.supports( 'color', value );
	}

	function syncTokenControls( source, value ) {
		root.querySelectorAll( '[data-adam-token]' ).forEach( ( peer ) => {
			if ( peer === source || peer.dataset.adamToken !== source.dataset.adamToken ) {
				return;
			}

			peer.value = value;
			if ( peer.classList.contains( 'adam-css-color-value' ) ) {
				const valid = isValidColor( value );
				peer.setAttribute( 'aria-invalid', String( ! valid ) );
				peer.setCustomValidity( valid ? '' : peer.dataset.invalidMessage );
				peer.closest( '.adam-css-color-control' ).style.setProperty( '--adam-picker-color', valid ? value : 'transparent' );
				const picker = peer.closest( '.adam-css-color-control' ).querySelector( '.adam-css-color-picker' );
				if ( /^#[0-9a-f]{6}$/i.test( value ) ) {
					picker.value = value;
				}
			}

			const output = peer.parentNode.querySelector( 'output' );
			if ( output ) {
				output.value = value + ( peer.dataset.unit || '' );
			}
		} );
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

		syncTokenControls( input, value );

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

	const tabs = Array.from( root.querySelectorAll( '[data-adam-editor-tab]' ) );
	const panels = Array.from( root.querySelectorAll( '[data-adam-editor-panel]' ) );

	function activatePanel( tab, moveFocus ) {
		tabs.forEach( ( item ) => {
			const active = item === tab;
			item.setAttribute( 'aria-selected', String( active ) );
			item.tabIndex = active ? 0 : -1;
		} );
		panels.forEach( ( panel ) => {
			panel.hidden = panel.dataset.adamEditorPanel !== tab.dataset.adamEditorTab;
		} );
		if ( moveFocus ) {
			tab.focus();
		}
	}

	tabs.forEach( ( tab, index ) => {
		tab.addEventListener( 'click', () => activatePanel( tab, false ) );
		tab.addEventListener( 'keydown', ( event ) => {
			let nextIndex = null;
			if ( 'ArrowDown' === event.key || 'ArrowRight' === event.key ) {
				nextIndex = ( index + 1 ) % tabs.length;
			} else if ( 'ArrowUp' === event.key || 'ArrowLeft' === event.key ) {
				nextIndex = ( index - 1 + tabs.length ) % tabs.length;
			} else if ( 'Home' === event.key ) {
				nextIndex = 0;
			} else if ( 'End' === event.key ) {
				nextIndex = tabs.length - 1;
			}
			if ( null !== nextIndex ) {
				event.preventDefault();
				activatePanel( tabs[ nextIndex ], true );
			}
		} );
	} );

	const editorForm = root.querySelector( '.adam-theme-editor__settings' );
	if ( editorForm ) {
		editorForm.addEventListener( 'invalid', ( event ) => {
			const panel = event.target.closest( '[data-adam-editor-panel]' );
			if ( ! panel ) {
				return;
			}
			const tab = tabs.find( ( item ) => item.dataset.adamEditorTab === panel.dataset.adamEditorPanel );
			if ( tab ) {
				activatePanel( tab, false );
			}
		}, true );
	}

	root.querySelectorAll( '[data-adam-token]' ).forEach( ( input ) => applyToken( input ) );
}( document, window ) );
