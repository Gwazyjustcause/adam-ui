( function ( document, window ) {
	'use strict';

	const root = document.querySelector( '[data-adam-theme-editor]' );

	if ( ! root ) {
		return;
	}

	let styleMaps = {};
	let intelligenceContracts = [];
	let inheritanceMap = {};
	let intelligenceFrame = 0;
	try {
		styleMaps = JSON.parse( root.dataset.adamStyleMaps || '{}' );
	} catch ( error ) {
		styleMaps = {};
	}
	try {
		intelligenceContracts = JSON.parse( root.dataset.adamIntelligence || '[]' );
	} catch ( error ) {
		intelligenceContracts = [];
	}
	try {
		inheritanceMap = JSON.parse( root.dataset.adamInheritance || '{}' );
	} catch ( error ) {
		inheritanceMap = {};
	}

	function isValidColor( value ) {
		return Boolean( value ) && window.CSS && window.CSS.supports( 'color', value );
	}

	function readableText( color ) {
		const probe = document.createElement( 'span' );
		probe.style.color = color;
		probe.hidden = true;
		root.appendChild( probe );
		const resolved = window.getComputedStyle( probe ).color;
		probe.remove();
		const channels = resolved.match( /[\d.]+/g );
		if ( ! channels || channels.length < 3 ) {
			return '#f2f4ee';
		}
		const luminance = [ 0.2126, 0.7152, 0.0722 ].reduce( ( total, weight, index ) => {
			let channel = Number( channels[ index ] ) / 255;
			channel = channel <= 0.04045 ? channel / 12.92 : Math.pow( ( channel + 0.055 ) / 1.055, 2.4 );
			return total + ( channel * weight );
		}, 0 );
		const lightRatio = ( Math.max( 0.904, luminance ) + 0.05 ) / ( Math.min( 0.904, luminance ) + 0.05 );
		const darkRatio = ( Math.max( 0.014, luminance ) + 0.05 ) / ( Math.min( 0.014, luminance ) + 0.05 );
		if ( Math.max( lightRatio, darkRatio ) < 4.5 ) {
			return luminance <= 0.179 ? '#ffffff' : '#000000';
		}
		return lightRatio >= darkRatio ? '#f2f4ee' : '#172107';
	}

	function resolvedRgb( color ) {
		const probe = document.createElement( 'span' );
		probe.style.color = color;
		probe.hidden = true;
		root.appendChild( probe );
		const channels = window.getComputedStyle( probe ).color.match( /[\d.]+/g );
		probe.remove();
		return channels && channels.length >= 3 ? channels.slice( 0, 3 ).map( Number ) : null;
	}

	function luminance( color ) {
		return [ 0.2126, 0.7152, 0.0722 ].reduce( ( total, weight, index ) => {
			let channel = color[ index ] / 255;
			channel = channel <= 0.04045 ? channel / 12.92 : Math.pow( ( channel + 0.055 ) / 1.055, 2.4 );
			return total + ( channel * weight );
		}, 0 );
	}

	function contrastRatio( foreground, background ) {
		const first = luminance( foreground );
		const second = luminance( background );
		return ( Math.max( first, second ) + 0.05 ) / ( Math.min( first, second ) + 0.05 );
	}

	function mixColor( from, to, amount ) {
		return from.map( ( channel, index ) => channel + ( ( to[ index ] - channel ) * amount ) );
	}

	function colorHex( color ) {
		return '#' + color.map( ( channel ) => Math.max( 0, Math.min( 255, Math.round( channel ) ) ).toString( 16 ).padStart( 2, '0' ) ).join( '' );
	}

	function ensureContrast( color, background, minimum, toward ) {
		for ( let amount = 0; amount <= 1.001; amount += 0.04 ) {
			const candidate = mixColor( color || toward, toward, amount );
			if ( contrastRatio( candidate, background ) >= minimum ) {
				return candidate;
			}
		}
		return toward;
	}

	function tokenColor( token ) {
		return resolvedRgb( window.getComputedStyle( root ).getPropertyValue( '--' + token ).trim() );
	}

	function setRoleTokens( contract, role, color ) {
		( contract[ role ] || [] ).forEach( ( token ) => root.style.setProperty( '--' + token, colorHex( color ) ) );
	}

	function applyIntelligence() {
		intelligenceContracts.forEach( ( contract ) => {
			const background = tokenColor( contract.background );
			if ( ! background ) {
				return;
			}
			const heading = resolvedRgb( readableText( colorHex( background ) ) );
			const body = ensureContrast( mixColor( background, heading, 0.76 ), background, 4.5, heading );
			const muted = ensureContrast( mixColor( background, heading, 0.58 ), background, 4.5, heading );
			const accent = contract.accent ? tokenColor( contract.accent ) : tokenColor( 'adam-btn-primary-bg' );
			const safeAccent = accent || heading;
			const link = ensureContrast( safeAccent, background, 4.5, heading );
			const icon = ensureContrast( safeAccent, background, 3, heading );
			const border = mixColor( background, heading, 0.18 );
			const hover = mixColor( background, heading, 0.12 );
			const hoverText = resolvedRgb( readableText( colorHex( hover ) ) );
			const focus = ensureContrast( safeAccent, background, 3, heading );
			const surface = mixColor( background, heading, 0.075 );
			const surfaceText = resolvedRgb( readableText( colorHex( surface ) ) );
			const disabledBackground = mixColor( background, heading, 0.08 );
			const disabledText = ensureContrast( mixColor( disabledBackground, heading, 0.48 ), disabledBackground, 4.5, heading );

			setRoleTokens( contract, 'heading', heading );
			setRoleTokens( contract, 'text', body );
			setRoleTokens( contract, 'muted', muted );
			setRoleTokens( contract, 'link', link );
			setRoleTokens( contract, 'icon', icon );
			setRoleTokens( contract, 'border', border );
			setRoleTokens( contract, 'hover_background', hover );
			setRoleTokens( contract, 'hover_text', hoverText );
			setRoleTokens( contract, 'focus', focus );
			setRoleTokens( contract, 'surface', surface );
			setRoleTokens( contract, 'surface_text', surfaceText );
			setRoleTokens( contract, 'disabled_background', disabledBackground );
			setRoleTokens( contract, 'disabled_text', disabledText );

			( contract.shadow || [] ).forEach( ( token ) => {
				root.style.setProperty( '--' + token, luminance( background ) < 0.25 ? 'rgb(0 0 0 / 0.42)' : 'rgb(0 0 0 / 0.2)' );
			} );
		} );
		applyInheritance();
	}

	function applyInheritance() {
		Object.keys( inheritanceMap ).forEach( ( target ) => {
			const toggle = root.querySelector( '[data-adam-override-toggle="' + target + '"]' );
			if ( toggle && toggle.checked ) {
				return;
			}
			const value = window.getComputedStyle( root ).getPropertyValue( '--' + inheritanceMap[ target ] ).trim();
			if ( value ) {
				root.style.setProperty( '--' + target, value );
			}
		} );
	}

	function scheduleIntelligence() {
		if ( intelligenceFrame ) {
			window.cancelAnimationFrame( intelligenceFrame );
		}
		intelligenceFrame = window.requestAnimationFrame( () => {
			intelligenceFrame = 0;
			applyIntelligence();
		} );
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
		if ( input.dataset.adamStyleComponent ) {
			applyComponentStyle( input.dataset.adamStyleComponent, value );
		}
		scheduleIntelligence();
		const output = input.parentNode.querySelector( 'output' );
		if ( output ) {
			output.value = value;
		}
	}

	function applyComponentStyle( component, style ) {
		const tokens = styleMaps[ component ] && styleMaps[ component ][ style ];
		if ( ! tokens ) {
			return;
		}
		Object.keys( tokens ).forEach( ( token ) => {
			root.style.setProperty( '--' + token.replace( /^--/, '' ), tokens[ token ] );
		} );
		root.dataset.adamPreviewStyle = component + ':' + style;
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

	root.querySelectorAll( '[data-adam-override-toggle]' ).forEach( ( toggle ) => {
		toggle.addEventListener( 'change', () => {
			const wrapper = toggle.closest( '[data-adam-override]' );
			const controls = wrapper.querySelectorAll( '[data-adam-override-control] input, [data-adam-override-control] select' );
			controls.forEach( ( control ) => {
				control.disabled = ! toggle.checked;
			} );
			if ( toggle.checked ) {
				const control = wrapper.querySelector( '[data-adam-token]' );
				if ( control ) {
					applyToken( control );
				}
			} else {
				root.style.setProperty( '--' + toggle.dataset.adamOverrideToggle, wrapper.dataset.adamBaseValue );
				applyInheritance();
				scheduleIntelligence();
			}
		} );
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

	root.querySelectorAll( '[data-adam-token]' ).forEach( ( input ) => {
		if ( ! input.disabled ) {
			applyToken( input );
		}
	} );
}( document, window ) );
