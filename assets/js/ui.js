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
	delete window.adamUIConfig;
	delete window.adamUIAssetConfig;
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
		Object.values( classMap ).forEach( ( className ) => {
			document.documentElement.classList.remove( className );
		} );

		if ( classMap[ theme ] ) {
			document.documentElement.classList.add( classMap[ theme ] );
		}

		if ( ! document.body ) {
			return;
		}

		Object.values( classMap ).forEach( ( className ) => {
			document.body.classList.remove( className );
		} );

		if ( classMap[ theme ] ) {
			document.body.classList.add( classMap[ theme ] );
		}

		document.body.dataset.adamTheme = theme;
		document.body.dataset.adamThemeMode = mode;

		// The root class is only an early-paint bridge. Once body exists, it is
		// the single source of truth used by ADAM styles and integrations.
		Object.values( classMap ).forEach( ( className ) => {
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
	}

	function applyTheme( mode, options = {} ) {
		const nextMode = modes.includes( mode ) ? mode : config.systemMode;
		const theme = resolveTheme( nextMode );

		currentMode = nextMode;
		updateBodyClass( theme, nextMode );
		syncThemeSwitchers();

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

		syncThemeSwitchers();
	}

	function placeThemeSwitcher() {
		const switcher = document.querySelector( '[data-adam-theme-switcher]' );
		const footer = document.querySelector( '.ct-footer, #colophon, .site-footer, footer.wp-block-template-part, body > footer' );

		if ( ! switcher || ! footer ) {
			return;
		}

		let copyright = footer.querySelector( '[data-id="copyright"], .ct-footer-copyright, .footer-copyright, .site-info, .copyright' );
		const copyrightContainer = Boolean( copyright );

		if ( ! copyright ) {
			copyright = Array.from( footer.querySelectorAll( 'p, div, span' ) ).find( ( element ) => {
				const text = element.textContent.trim();
				return /(?:©|&copy;|copyright)/i.test( text ) && element.children.length < 3;
			} );
		}

		if ( copyrightContainer ) {
			copyright.insertBefore( switcher, copyright.firstChild );
		} else if ( copyright && copyright.parentNode ) {
			copyright.parentNode.insertBefore( switcher, copyright );
		} else {
			footer.appendChild( switcher );
		}

		switcher.dataset.adamFooterIntegrated = 'true';
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
		placeThemeSwitcher();
		bindThemeSwitchers();
		watchSystemTheme();
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
