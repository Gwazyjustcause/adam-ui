/**
 * Shared interaction helpers for ADAM UI components.
 */
( function ( window, document ) {
	'use strict';

	const api = window.ADAMUI || {};

	function setLoading( element, loading = true, label = '' ) {
		if ( ! element ) {
			return;
		}

		if ( loading ) {
			element.dataset.adamOriginalDisabled = element.disabled ? 'true' : 'false';
			element.disabled = true;
			element.setAttribute( 'aria-busy', 'true' );
			if ( label ) {
				element.dataset.adamOriginalLabel = element.getAttribute( 'aria-label' ) || '';
				element.setAttribute( 'aria-label', label );
			}
			return;
		}

		element.removeAttribute( 'aria-busy' );
		element.disabled = element.dataset.adamOriginalDisabled === 'true';
		if ( Object.prototype.hasOwnProperty.call( element.dataset, 'adamOriginalLabel' ) ) {
			const originalLabel = element.dataset.adamOriginalLabel;
			if ( originalLabel ) {
				element.setAttribute( 'aria-label', originalLabel );
			} else {
				element.removeAttribute( 'aria-label' );
			}
		}
		delete element.dataset.adamOriginalDisabled;
		delete element.dataset.adamOriginalLabel;
	}

	function createConfirmationDialog( options ) {
		const dialog = document.createElement( 'dialog' );
		const titleId = `adam-confirm-title-${ Date.now() }`;
		dialog.className = 'adam-confirmation adam-modal';
		dialog.setAttribute( 'aria-labelledby', titleId );
		dialog.innerHTML = `
			<div class="adam-modal__header">
				<h2 class="adam-modal__title" id="${ titleId }"></h2>
			</div>
			<div class="adam-modal__body"><p></p></div>
			<div class="adam-modal__footer">
				<button class="adam-button adam-button-secondary" type="button" data-adam-confirm-cancel></button>
				<button class="adam-button adam-button-danger" type="button" data-adam-confirm-accept></button>
			</div>`;
		dialog.querySelector( '.adam-modal__title' ).textContent = options.title;
		dialog.querySelector( '.adam-modal__body p' ).textContent = options.message;
		dialog.querySelector( '[data-adam-confirm-cancel]' ).textContent = options.cancelLabel;
		dialog.querySelector( '[data-adam-confirm-accept]' ).textContent = options.confirmLabel;
		document.body.appendChild( dialog );
		return dialog;
	}

	function confirm( options = {} ) {
		const settings = {
			title: options.title || 'Confirmar aÃ§Ã£o',
			message: options.message || '',
			confirmLabel: options.confirmLabel || 'Confirmar',
			cancelLabel: options.cancelLabel || 'Cancelar',
			dialog: options.dialog || null,
		};
		const previousFocus = document.activeElement;
		const dialog = settings.dialog || createConfirmationDialog( settings );
		const cancelButton = dialog.querySelector( '[data-adam-confirm-cancel]' );
		const acceptButton = dialog.querySelector( '[data-adam-confirm-accept]' );
		const generated = ! settings.dialog;

		return new window.Promise( ( resolve ) => {
			let settled = false;
			const finish = ( result ) => {
				if ( settled ) {
					return;
				}
				settled = true;
				if ( dialog.open ) {
					dialog.close();
				}
				if ( generated ) {
					dialog.remove();
				}
				if ( typeof api.emit === 'function' ) {
					api.emit( 'adam:modalClosed', { dialog, confirmed: result } );
				}
				if ( previousFocus && typeof previousFocus.focus === 'function' ) {
					previousFocus.focus();
				}
				resolve( result );
			};

			cancelButton.addEventListener( 'click', () => finish( false ), { once: true } );
			acceptButton.addEventListener( 'click', () => finish( true ), { once: true } );
			dialog.addEventListener( 'cancel', ( event ) => {
				event.preventDefault();
				finish( false );
			}, { once: true } );
			dialog.addEventListener( 'close', () => finish( false ), { once: true } );
			dialog.showModal();
			if ( typeof api.emit === 'function' ) {
				api.emit( 'adam:modalOpened', { dialog } );
			}
			cancelButton.focus();
		} );
	}

	function bindDropdowns( root = document ) {
		root.querySelectorAll( '[data-adam-dropdown-toggle]' ).forEach( ( toggle ) => {
			if ( toggle.dataset.adamDropdownBound ) {
				return;
			}
			const menuId = toggle.getAttribute( 'aria-controls' );
			const menu = menuId ? document.getElementById( menuId ) : null;
			if ( ! menu ) {
				return;
			}
			toggle.dataset.adamDropdownBound = 'true';
			toggle.setAttribute( 'aria-expanded', 'false' );
			toggle.addEventListener( 'click', () => {
				const open = toggle.getAttribute( 'aria-expanded' ) === 'true';
				toggle.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
				menu.hidden = open;
			} );
			menu.addEventListener( 'keydown', ( event ) => {
				if ( event.key === 'Escape' ) {
					menu.hidden = true;
					toggle.setAttribute( 'aria-expanded', 'false' );
					toggle.focus();
				}
			} );
		} );
	}

	api.components = Object.assign( api.components || {}, { bindDropdowns, confirm, setLoading } );
	api.confirm = confirm;
	api.setLoading = setLoading;
	window.ADAMUI = api;

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', () => bindDropdowns(), { once: true } );
	} else {
		bindDropdowns();
	}
} )( window, document );

