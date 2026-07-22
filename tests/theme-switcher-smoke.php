<?php
/** Theme switcher browser and footer integration contract. */

$root = dirname( __DIR__ );
$js   = file_get_contents( $root . '/assets/js/ui.js' );
$css  = file_get_contents( $root . '/assets/css/theme-switcher.css' );

function adam_ui_switcher_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
		exit( 1 );
	}
}

adam_ui_switcher_assert( false !== strpos( $js, "window.localStorage.setItem( key, value )" ), 'Selections must persist in localStorage.' );
adam_ui_switcher_assert( false !== strpos( $js, "mode === config.systemMode" ), 'System mode must resolve through matchMedia.' );
adam_ui_switcher_assert( false !== strpos( $js, "mediaQuery.addEventListener( 'change'" ), 'System mode must react while the page is open.' );
adam_ui_switcher_assert( false !== strpos( $js, 'function placeThemeSwitcher()' ), 'The switcher must integrate with the footer.' );
adam_ui_switcher_assert( false !== strpos( $js, "copyright.insertBefore( switcher, copyright.firstChild )" ), 'The switcher must precede copyright content.' );
adam_ui_switcher_assert( false !== strpos( $css, '[data-adam-footer-integrated="true"]' ), 'Integrated footer presentation is missing.' );
adam_ui_switcher_assert( false !== strpos( $css, 'justify-content: center' ), 'The footer switcher must be centered.' );

echo "PASS: Theme switcher browser and footer contract.\n";
