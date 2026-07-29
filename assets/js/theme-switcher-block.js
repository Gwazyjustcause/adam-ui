( function ( blocks, element, i18n, blockEditor, components ) {
	'use strict';

	const el = element.createElement;
	const __ = i18n.__;
	const InspectorControls = blockEditor.InspectorControls;
	const PanelBody = components.PanelBody;
	const SelectControl = components.SelectControl;

	blocks.registerBlockType( 'adam-ui/theme-switcher', {
		apiVersion: 3,
		title: __( 'Theme Switcher', 'adam-ui' ),
		description: __( 'Place the ADAM UI Light, Night, and System selector.', 'adam-ui' ),
		icon: 'admin-appearance',
		category: 'adam-ui',
		attributes: {
			style: {
				type: 'string',
				default: 'dropdown',
			},
		},
		edit( props ) {
			const style = props.attributes.style || 'dropdown';
			const label = 'icon-only' === style ? '☀  ☾  ◐' : 'icon-label' === style ? '☀ Claro   ☾ Noite   ◐ Sistema' : 'Tema: Sistema';

			return el(
				element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Theme Switcher', 'adam-ui' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Display style', 'adam-ui' ),
							value: style,
							options: [
								{ label: __( 'Icon only', 'adam-ui' ), value: 'icon-only' },
								{ label: __( 'Icon + label', 'adam-ui' ), value: 'icon-label' },
								{ label: __( 'Dropdown', 'adam-ui' ), value: 'dropdown' },
							],
							onChange: ( value ) => props.setAttributes( { style: value } ),
						} )
					)
				),
				el(
					'div',
					{
						className: 'adam-ui-theme-switcher-block-preview',
						'data-adam-display-style': style,
					},
					el( 'strong', { className: 'adam-ui-theme-switcher-block-preview__title' }, __( 'ADAM UI Theme Switcher', 'adam-ui' ) ),
					el( 'span', { className: 'adam-ui-theme-switcher-block-preview__control' }, label )
				)
			);
		},
		save() {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.components );
