/**
 * ADAM UI theme controller.
 *
 * Restores, applies, and persists the visitor's theme preference. The storage
 * adapter can be replaced later without changing the public theme API.
 */
( function ( window, document ) {
	'use strict';

	const config = window.adamUIConfig || {};
	const assetConfig = window.adamUIAssetConfig || {};
	const modes = Array.isArray( config.modes ) ? config.modes : [];
	const resolvedThemes = Array.isArray( config.resolvedThemes )
		? config.resolvedThemes
		: [];
	const classMap = config.classMap || {};
	const storageConfig = config.storage || {};
	const storageAdapters = {};
	let activeStorageAdapter = storageConfig.adapter || '';
	let currentMode = modes.includes( config.mode ) ? config.mode : config.systemMode;
	let mediaQuery = null;
	let lastEventMode = null;
	let lastEventTheme = null;
	const observedRichTextFrames = new WeakSet();
	const observedRichTextDocuments = new WeakSet();
	const richTextEditorFrameSelector = [
		'.wp-editor-wrap iframe',
		'.mce-edit-area iframe',
		'.tox-edit-area iframe',
	].join( ',' );

	storageAdapters.localStorage = {
		load( key ) {
			return window.localStorage.getItem( key );
		},
		save( key, value ) {
			window.localStorage.setItem( key, value );
		},
		remove( key ) {
			window.localStorage.removeItem( key );
		},
	};

	function saveUserPreference( value ) {
		if ( ! storageConfig.saveUrl || ! window.fetch ) {
			return;
		}

		const body = new window.URLSearchParams();
		body.set( 'action', storageConfig.action );
		body.set( 'nonce', storageConfig.nonce );
		body.set( 'theme', value );
		window.fetch( storageConfig.saveUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		} ).catch( () => {} );
	}

	storageAdapters.userMeta = {
		load() {
			return storageConfig.initial || null;
		},
		save( key, value ) {
			saveUserPreference( value );
		},
		remove() {
			saveUserPreference( '' );
		},
	};

	function emit( name, detail = {} ) {
		document.dispatchEvent( new window.CustomEvent( name, { detail } ) );
	}

	function on( name, listener, options ) {
		document.addEventListener( name, listener, options );
		return () => document.removeEventListener( name, listener, options );
	}

	function off( name, listener, options ) {
		document.removeEventListener( name, listener, options );
	}

	function getStorageAdapter() {
		return storageAdapters[ activeStorageAdapter ] || null;
	}

	function safelyUseStorage( operation, fallback = null ) {
		const adapter = getStorageAdapter();

		if ( ! adapter || typeof adapter[ operation ] !== 'function' ) {
			return fallback;
		}

		try {
			return adapter[ operation ]( storageConfig.key );
		} catch ( error ) {
			return fallback;
		}
	}

	function getSystemTheme() {
		if ( ! window.matchMedia || ! config.systemQuery ) {
			return config.systemFallback;
		}

		mediaQuery = mediaQuery || window.matchMedia( config.systemQuery );

		return mediaQuery.matches ? config.systemDark : config.systemFallback;
	}

	function resolveTheme( mode ) {
		if ( resolvedThemes.includes( mode ) ) {
			return mode;
		}

		if ( mode === config.systemMode ) {
			return getSystemTheme();
		}

		return config.systemFallback;
	}

	function updateBodyClass( theme, mode ) {
		const removableClasses = Array.from( new Set( Object.values( classMap ).concat( [ 'adam-theme-light' ] ) ) );
		removableClasses.forEach( ( className ) => {
			document.documentElement.classList.remove( className );
		} );

		if ( classMap[ theme ] ) {
			document.documentElement.classList.add( classMap[ theme ] );
		}

		if ( ! document.body ) {
			return;
		}

		removableClasses.forEach( ( className ) => {
			document.body.classList.remove( className );
		} );

		if ( classMap[ theme ] ) {
			document.body.classList.add( classMap[ theme ] );
		}

		document.body.dataset.adamTheme = theme;
		document.body.dataset.adamThemeMode = mode;

		// The root class is only an early-paint bridge. Once body exists, it is
		// the single source of truth used by ADAM styles and integrations.
		removableClasses.forEach( ( className ) => {
			document.documentElement.classList.remove( className );
		} );
	}

	function dispatchThemeChange( mode, theme ) {
		if ( mode === lastEventMode && theme === lastEventTheme ) {
			return;
		}

		lastEventMode = mode;
		lastEventTheme = theme;

		const detail = { mode, theme, resolvedTheme: theme };

		emit( 'adam:themeChanged', detail );

		// Retained for consumers built against the Phase 1 development API.
		emit( 'adam-ui:theme-change', detail );
	}

	function syncThemeSwitchers() {
		document.querySelectorAll( '[data-adam-theme-select]' ).forEach( ( select ) => {
			select.value = currentMode;
		} );
		document.querySelectorAll( '[data-adam-theme-value]' ).forEach( ( button ) => {
			button.setAttribute( 'aria-pressed', String( button.dataset.adamThemeValue === currentMode ) );
		} );
	}

	function editorToken( style, name, fallback ) {
		const value = style && typeof style.getPropertyValue === 'function'
			? style.getPropertyValue( name ).trim()
			: '';

		return value || fallback;
	}

	/**
	 * Applies the Night palette inside a same-origin WordPress editor iframe.
	 * The style lives in the iframe head, outside TinyMCE's serialised body, so
	 * it can never become part of the content saved by wp_editor().
	 */
	function syncRichTextEditorFrame( frame, isNight ) {
		let editorDocument = null;

		try {
			editorDocument = frame.contentDocument || ( frame.contentWindow && frame.contentWindow.document );
		} catch ( error ) {
			// Third-party or cross-origin editor frames are outside ADAM UI's scope.
			return;
		}

		if ( ! editorDocument || ! editorDocument.documentElement || ! editorDocument.head ) {
			return;
		}

		const editorWrapper = typeof frame.closest === 'function' ? frame.closest( '.wp-editor-wrap' ) : null;
		if ( ! observedRichTextDocuments.has( editorDocument ) && typeof editorDocument.addEventListener === 'function' ) {
			observedRichTextDocuments.add( editorDocument );
			editorDocument.addEventListener( 'focusin', () => {
				if ( editorWrapper ) {
					editorWrapper.classList.add( 'adam-ui-editor-focus' );
				}
			} );
			editorDocument.addEventListener( 'focusout', () => {
				window.setTimeout( () => {
					if ( editorWrapper && ( typeof editorDocument.hasFocus !== 'function' || ! editorDocument.hasFocus() ) ) {
						editorWrapper.classList.remove( 'adam-ui-editor-focus' );
					}
				}, 0 );
			} );
		}

		const styleId = 'adam-ui-rich-text-editor-theme';
		const existingStyle = typeof editorDocument.head.querySelector === 'function'
			? editorDocument.head.querySelector( 'style[data-adam-ui-editor-theme]' )
			: null;
		const propertyNames = [
			'--adam-editor-bg',
			'--adam-editor-text',
			'--adam-editor-placeholder',
			'--adam-editor-heading',
			'--adam-editor-link',
			'--adam-editor-selection-bg',
			'--adam-editor-selection-text',
		];

		if ( ! isNight ) {
			if ( existingStyle ) {
				existingStyle.remove();
			}
			propertyNames.forEach( ( property ) => editorDocument.documentElement.style.removeProperty( property ) );
			return;
		}

		const sourceStyle = window.getComputedStyle( document.body || document.documentElement );
		const properties = {
			'--adam-editor-bg': editorToken( sourceStyle, '--adam-form-input-bg', 'Canvas' ),
			'--adam-editor-text': editorToken( sourceStyle, '--adam-form-input-text', 'CanvasText' ),
			'--adam-editor-placeholder': editorToken( sourceStyle, '--adam-form-placeholder', 'GrayText' ),
			'--adam-editor-heading': editorToken( sourceStyle, '--adam-global-heading', 'CanvasText' ),
			'--adam-editor-link': editorToken( sourceStyle, '--adam-global-link', 'LinkText' ),
			'--adam-editor-selection-bg': editorToken( sourceStyle, '--adam-selection-bg', 'Highlight' ),
			'--adam-editor-selection-text': editorToken( sourceStyle, '--adam-selection-text', 'HighlightText' ),
		};

		Object.entries( properties ).forEach( ( entry ) => {
			editorDocument.documentElement.style.setProperty( entry[ 0 ], entry[ 1 ] );
		} );

		let editorStyle = existingStyle;
		if ( ! editorStyle ) {
			editorStyle = editorDocument.createElement( 'style' );
			editorStyle.id = styleId;
			editorStyle.setAttribute( 'data-adam-ui-editor-theme', '' );
			editorDocument.head.appendChild( editorStyle );
		}

		editorStyle.textContent = [
			':root{color-scheme:dark;background:var(--adam-editor-bg);}',
			'html body#tinymce,html body.mce-content-body,html body[contenteditable="true"]{background:var(--adam-editor-bg)!important;color:var(--adam-editor-text)!important;caret-color:var(--adam-editor-text);}',
			'body :where(h1,h2,h3,h4,h5,h6){color:var(--adam-editor-heading);}',
			'body :where(a){color:var(--adam-editor-link);}',
			'body :where([data-mce-placeholder]:empty)::before,body.mce-content-body[data-placeholder]:empty::before,body :where(.mce-placeholder){color:var(--adam-editor-placeholder)!important;opacity:1;}',
			'body ::selection{background:var(--adam-editor-selection-bg);color:var(--adam-editor-selection-text);}',
		].join( '' );
	}

	function syncRichTextEditors() {
		if ( ! document.body ) {
			return;
		}

		const nightClass = classMap[ config.systemDark ];
		const isNight = Boolean( nightClass && document.body.classList.contains( nightClass ) );

		document.querySelectorAll( richTextEditorFrameSelector ).forEach( ( frame ) => {
			if ( ! observedRichTextFrames.has( frame ) ) {
				observedRichTextFrames.add( frame );
				if ( typeof frame.addEventListener === 'function' ) {
					frame.addEventListener( 'load', syncRichTextEditors );
				}
			}
			syncRichTextEditorFrame( frame, isNight );
		} );
	}

	function applyTheme( mode, options = {} ) {
		const nextMode = modes.includes( mode ) ? mode : config.systemMode;
		const theme = resolveTheme( nextMode );

		currentMode = nextMode;
		updateBodyClass( theme, nextMode );
		syncThemeSwitchers();
		refreshBackgrounds();
		syncRichTextEditors();

		if ( options.persist ) {
			const adapter = getStorageAdapter();

			if ( adapter && typeof adapter.save === 'function' ) {
				try {
					adapter.save( storageConfig.key, nextMode );
				} catch ( error ) {
					// Storage can be unavailable in privacy-restricted browsers.
				}
			}
		}

		dispatchThemeChange( nextMode, theme );

		return theme;
	}

	function restoreTheme() {
		const storedMode = safelyUseStorage( 'load' );

		return applyTheme( modes.includes( storedMode ) ? storedMode : currentMode );
	}

	function resetTheme() {
		safelyUseStorage( 'remove' );

		return applyTheme( modes.includes( config.fallbackMode ) ? config.fallbackMode : config.mode );
	}

	function handleSystemThemeChange() {
		if ( currentMode === config.systemMode ) {
			applyTheme( currentMode );
		}
	}

	function watchSystemTheme() {
		if ( ! window.matchMedia || ! config.systemQuery ) {
			return;
		}

		mediaQuery = mediaQuery || window.matchMedia( config.systemQuery );

		if ( typeof mediaQuery.addEventListener === 'function' ) {
			mediaQuery.addEventListener( 'change', handleSystemThemeChange );
		} else if ( typeof mediaQuery.addListener === 'function' ) {
			mediaQuery.addListener( handleSystemThemeChange );
		}
	}

	function bindThemeSwitchers() {
		document.querySelectorAll( '[data-adam-theme-select]' ).forEach( ( select ) => {
			if ( select.dataset.adamThemeBound ) {
				return;
			}

			select.dataset.adamThemeBound = 'true';
			select.addEventListener( 'change', () => {
				api.setTheme( select.value );
			} );
		} );
		document.querySelectorAll( '[data-adam-theme-value]' ).forEach( ( button ) => {
			if ( button.dataset.adamThemeBound ) {
				return;
			}

			button.dataset.adamThemeBound = 'true';
			button.addEventListener( 'click', () => {
				api.setTheme( button.dataset.adamThemeValue );
			} );
		} );

		syncThemeSwitchers();
	}

	const backgroundCandidates = [
		'#main-container',
		'main',
		'.site-main',
		'.content-area',
		'.entry-content',
		'.entry-content > *',
		'.ct-container',
		'.ct-container-full',
		'.wp-block-group',
		'.wp-block-columns',
		'.wp-block-column',
		'.wp-block-cover',
		'.wp-block-cover__background',
		'details',
		'details > summary',
		'[class*="-panel"]',
		'[class*="-section"]',
		'.has-background',
		'[class*="-background-color"]',
		'[class*="-gradient-background"]',
		'[class^="adam-"]',
		'[class*=" adam-"]',
		'[class^="adam-"] > *',
		'[class*=" adam-"] > *',
	].join( ',' );

	const protectedComponentSelector = [
		'img',
		'picture',
		'video',
		'canvas',
		'svg',
		'button',
		'input',
		'select',
		'textarea',
		'option',
		'.adam-button',
		'.adam-badge',
		'.adam-status',
		'.adam-notice',
		'.adam-alert',
		'[class*="button"]',
		'[class*="-badge"]',
		'[class*="-status"]',
		'[class*="-notice"]',
		'[class*="-alert"]',
		'[role="button"]',
		'[role="status"]',
		'[role="alert"]',
	].join( ',' );

	/*
	 * Some components are complete visual compositions, not Night surfaces.
	 * Their artwork, gradients, inline cosmetics and internal colour system
	 * must remain authored by the owning plugin.
	 */
	const preservedComponentSelector = [
		'[data-adam-ui-preserve="true"]',
		'[data-adam-preserve-styling="true"]',
		'.adam-preserve-component',
		'.adam-digital-card',
	].join( ',' );

	const typographyElementSelector = [
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'p',
		'li',
		'dt',
		'dd',
		'figcaption',
		'caption',
		'label',
		'legend',
		'blockquote',
	].join( ',' );

	function isPreservedComponent( element ) {
		if ( element.matches( preservedComponentSelector ) ) {
			return true;
		}

		const ancestor = element.closest( preservedComponentSelector );
		return Boolean( ancestor && typeof ancestor.matches === 'function' && ancestor.matches( preservedComponentSelector ) );
	}

	function componentClassNames( element ) {
		if ( typeof element.className !== 'string' ) {
			return [];
		}

		return element.className.split( /\s+/ ).filter( Boolean );
	}

	function hasSemanticClass( classNames, pattern ) {
		return classNames.some( ( className ) => pattern.test( className ) );
	}

	function isRenderedSurface( element ) {
		const style = window.getComputedStyle( element );
		const luminance = colourLuminance( style.backgroundColor );
		const padding = String( style.padding || '' ).match( /[\d.]+/g ) || [];
		const borderWidth = String( style.borderWidth || '' ).match( /[\d.]+/g ) || [];
		const borderRadius = String( style.borderRadius || '' ).match( /[\d.]+/g ) || [];
		const hasBoxTreatment = padding.some( ( value ) => Number( value ) > 0 )
			|| borderWidth.some( ( value ) => Number( value ) > 0 )
			|| borderRadius.some( ( value ) => Number( value ) > 0 );

		return null !== luminance && hasBoxTreatment;
	}

	function inheritedCollectionComponent( element ) {
		if ( ! element.parentElement || ! isRenderedSurface( element ) ) {
			return '';
		}

		const parentClasses = componentClassNames( element.parentElement );

		if ( hasSemanticClass( parentClasses, /(?:^|-)(?:facilities|facility|amenities|features|benefits|capabilities)(?:$|--|-)/ ) ) {
			return 'feature';
		}

		if ( hasSemanticClass( parentClasses, /(?:^|-)(?:stats|statistics)(?:$|--|-)/ ) ) {
			return 'stat';
		}

		if ( hasSemanticClass( parentClasses, /(?:^|-)(?:cards|directory|results|teams|partners|news|information)(?:$|--|-)/ ) ) {
			return 'card';
		}

		return '';
	}

	function classifyComponent( element ) {
		if ( isPreservedComponent( element ) ) {
			return '';
		}

		const allClassNames = componentClassNames( element );
		const classNames = allClassNames.filter( ( className ) => ! className.includes( '__' ) );

		if ( element.matches( protectedComponentSelector ) || element.matches( typographyElementSelector ) ) {
			return '';
		}

		const inheritedComponent = inheritedCollectionComponent( element );

		if ( inheritedComponent ) {
			return inheritedComponent;
		}

		if ( element.matches( 'form' ) || classNames.some( ( className ) => /(?:filter|toolbar|universal-search|search-bar|search-box)(?:$|--|-)/.test( className ) ) ) {
			return 'form';
		}

		if ( hasSemanticClass( classNames, /(?:^|-)(?:empty|empty-state|blank-state)(?:$|--)/ )
			|| hasSemanticClass( allClassNames, /__(?:empty|empty-state|blank-state)(?:$|--)/ ) ) {
			return 'empty';
		}

		if ( hasSemanticClass( classNames, /(?:^|-)(?:stat|stat-card|statistic-card)(?:$|--)/ )
			|| hasSemanticClass( allClassNames, /__(?:stat|stat-card|statistic-card)(?:$|--)/ ) ) {
			return 'stat';
		}

		if ( hasSemanticClass( classNames, /(?:^|-)card(?:$|--)/ )
			|| hasSemanticClass( allClassNames, /__card(?:$|--)/ ) ) {
			return 'card';
		}

		if ( isRenderedSurface( element ) && (
			element.matches( 'details' )
			|| hasSemanticClass( classNames, /(?:^|-)(?:panel|content-panel|content-section|section)(?:$|--)/ )
			|| hasSemanticClass( allClassNames, /__(?:panel|content-panel|content-section|section)(?:$|--)/ )
		) ) {
			return 'panel';
		}

		if ( ( hasSemanticClass( classNames, /(?:^|-)hero(?:$|--)/ )
			|| hasSemanticClass( allClassNames, /__hero(?:$|--)/ ) )
			&& ! hasSemanticClass( classNames, /--hero(?:$|-)/ ) ) {
			return 'hero';
		}

		return '';
	}

	function colourLuminance( colour ) {
		const values = String( colour ).match( /[\d.]+/g );

		if ( ! values || values.length < 3 || ( values.length > 3 && Number( values[ 3 ] ) === 0 ) ) {
			return null;
		}

		const channels = values.slice( 0, 3 ).map( ( value ) => {
			const channel = Number( value ) / 255;
			return channel <= 0.04045 ? channel / 12.92 : ( ( channel + 0.055 ) / 1.055 ) ** 2.4;
		} );

		return ( 0.2126 * channels[ 0 ] ) + ( 0.7152 * channels[ 1 ] ) + ( 0.0722 * channels[ 2 ] );
	}

	function isPaleGreen( colour ) {
		const values = String( colour ).match( /[\d.]+/g );

		if ( ! values || values.length < 3 ) {
			return false;
		}

		const red = Number( values[ 0 ] );
		const green = Number( values[ 1 ] );
		const blue = Number( values[ 2 ] );

		return green >= red + 5 && green >= blue + 5;
	}

	function hasContentImage( element, backgroundImage ) {
		if ( backgroundImage.includes( 'url(' ) ) {
			return true;
		}

		return element.matches( '.wp-block-cover' )
			&& Boolean( element.querySelector( ':scope > .wp-block-cover__image-background, :scope > .wp-block-cover__video-background' ) );
	}

	function hasExplicitBackgroundIntent( element ) {
		const inlineStyle = String( element.getAttribute && element.getAttribute( 'style' ) || '' );

		return /(?:^|;)\s*background(?:-color|-image)?\s*:/i.test( inlineStyle )
			|| element.matches( '.has-background, [class*="-background-color"], [class*="-gradient-background"]' );
	}

	function isPanelDisclosureHeader( element ) {
		if ( ! element.matches( 'summary' ) || ! element.parentElement ) {
			return false;
		}

		const parentClasses = componentClassNames( element.parentElement );

		return element.parentElement.dataset.adamNightComponent === 'panel'
			|| ( isRenderedSurface( element.parentElement ) && (
				element.parentElement.matches( 'details' )
				|| hasSemanticClass( parentClasses, /(?:^|-)(?:panel|content-panel|content-section|section)(?:$|--)/ )
			) );
	}

	function classifyBackground( element ) {
		if ( isPreservedComponent( element ) ) {
			return '';
		}

		const style = window.getComputedStyle( element );
		const backgroundImage = style.backgroundImage || 'none';

		if ( element.matches( typographyElementSelector ) && ! hasExplicitBackgroundIntent( element ) ) {
			return 'typography';
		}

		if ( isPanelDisclosureHeader( element ) ) {
			return 'transparent';
		}

		if ( typeof element.closest === 'function' && element.closest( 'footer, .ct-footer, .site-footer, #colophon' ) ) {
			return 'footer';
		}

		if ( element.matches( protectedComponentSelector ) ) {
			return '';
		}

		if ( hasContentImage( element, backgroundImage ) ) {
			return 'image';
		}

		if ( element.matches( '.wp-block-cover__background, .has-background-dim, [class*="overlay"]' ) ) {
			return 'overlay';
		}

		if ( backgroundImage !== 'none' ) {
			return 'accent';
		}

		if ( element.matches( '.has-palette-color-1-background-color, .has-palette-color-2-background-color, .has-palette-color-5-background-color, [class*="-gradient-background"]' ) ) {
			return 'accent';
		}

		if ( element.matches( '.has-palette-color-6-background-color, .has-palette-color-7-background-color, .is-style-alternate, .is-style-muted' ) ) {
			return 'alternate';
		}

		const luminance = colourLuminance( style.backgroundColor );

		if ( null === luminance ) {
			return 'transparent';
		}

		if ( luminance >= 0.82 ) {
			return isPaleGreen( style.backgroundColor ) ? 'alternate' : 'content';
		}

		if ( luminance >= 0.55 ) {
			return 'alternate';
		}

		return '';
	}

	function refreshBackgrounds() {
		const nightClass = classMap[ config.systemDark ];
		const isNight = document.body && nightClass && document.body.classList.contains( nightClass );

		document.querySelectorAll( '[data-adam-night-background]' ).forEach( ( element ) => {
			delete element.dataset.adamNightBackground;
		} );
		document.querySelectorAll( '[data-adam-night-component]' ).forEach( ( element ) => {
			delete element.dataset.adamNightComponent;
		} );

		if ( ! isNight ) {
			return;
		}

		document.querySelectorAll( backgroundCandidates ).forEach( ( element ) => {
			const component = classifyComponent( element );
			const classification = classifyBackground( element );

			if ( component ) {
				element.dataset.adamNightComponent = component;
			}

			if ( classification ) {
				element.dataset.adamNightBackground = classification;
			}
		} );
	}

	function watchBackgrounds() {
		if ( ! window.MutationObserver || ! document.body ) {
			return;
		}

		let scheduled = false;
		const observer = new window.MutationObserver( () => {
			if ( scheduled ) {
				return;
			}

			scheduled = true;
			window.requestAnimationFrame( () => {
				scheduled = false;
				bindThemeSwitchers();
				refreshBackgrounds();
				syncRichTextEditors();
			} );
		} );

		observer.observe( document.body, { childList: true, subtree: true } );
	}

	const api = {
		applyTheme,
		emit,
		getMode: () => currentMode,
		getResolvedTheme: () => resolveTheme( currentMode ),
		getTheme: () => currentMode,
		getToken( name, theme ) {
			const tokens = config.tokens || {};
			const selected = tokens[ theme || resolveTheme( currentMode ) ] || {};
			return selected[ String( name ).replace( /^--/, '' ) ];
		},
		getTokens: ( theme ) => Object.assign( {}, ( config.tokens || {} )[ theme || resolveTheme( currentMode ) ] || {} ),
		off,
		refreshBackgrounds,
		refreshRichTextEditors: syncRichTextEditors,
		on,
		registerStorageAdapter( name, adapter ) {
			if ( name && adapter ) {
				storageAdapters[ name ] = adapter;
			}
		},
		resetTheme,
		restoreTheme,
		setStorageAdapter( name ) {
			if ( storageAdapters[ name ] ) {
				activeStorageAdapter = name;
			}
		},
		setTheme: ( mode ) => applyTheme( mode, { persist: true } ),
	};

	window.ADAMUI = api;

	function init() {
		applyTheme( currentMode );
		bindThemeSwitchers();
		watchSystemTheme();
		refreshBackgrounds();
		watchBackgrounds();
		( Array.isArray( assetConfig.components ) ? assetConfig.components : [] ).forEach( ( component ) => {
			emit( 'adam:componentLoaded', { component } );
		} );
	}

	// The script is loaded in <head>; applying to <html> here prevents a flash
	// before <body> exists. init() copies the one resolved class to <body>.
	restoreTheme();

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init, { once: true } );
	} else {
		init();
	}
} )( window, document );
