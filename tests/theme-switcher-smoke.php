<?php
/** Theme switcher browser and footer integration contract. */

$root = dirname( __DIR__ );
$js   = file_get_contents( $root . '/assets/js/ui.js' );
$css  = file_get_contents( $root . '/assets/css/theme-switcher.css' );
$php  = file_get_contents( $root . '/includes/class-theme-manager.php' );

function adam_ui_switcher_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
		exit( 1 );
	}
}

adam_ui_switcher_assert( false !== strpos( $js, "window.localStorage.setItem( key, value )" ), 'Selections must persist in localStorage.' );
adam_ui_switcher_assert( false === strpos( $js, 'delete window.adamUIConfig' ), 'WordPress localized globals are non-configurable and must not be deleted.' );
adam_ui_switcher_assert( false !== strpos( $js, "mode === config.systemMode" ), 'System mode must resolve through matchMedia.' );
adam_ui_switcher_assert( false !== strpos( $js, "mediaQuery.addEventListener( 'change'" ), 'System mode must react while the page is open.' );
adam_ui_switcher_assert( false === strpos( $js, 'function placeThemeSwitcher()' ), 'Footer placement must not rely on client-side DOM relocation.' );
adam_ui_switcher_assert( false !== strpos( $php, "add_filter( 'blocksy:footer:copyright:value'" ), 'Blocksy copyright markup integration is missing.' );
adam_ui_switcher_assert( false !== strpos( $php, 'adam-footer-theme-layout' ), 'The structural footer layout wrapper is missing.' );
adam_ui_switcher_assert( strpos( $php, 'get_theme_switcher_markup( true )' ) < strpos( $php, 'adam-footer-copyright-text' ), 'Theme selector markup must precede copyright markup.' );
adam_ui_switcher_assert( false !== strpos( $css, '[data-adam-footer-integrated="true"]' ), 'Integrated footer presentation is missing.' );
adam_ui_switcher_assert( false !== strpos( $css, 'justify-content: center' ), 'The footer switcher must be centered.' );

echo "PASS: Theme switcher browser and footer contract.\n";
