<?php
/** Night-only override architecture contract. */

$root       = dirname( __DIR__ );
$ui_css     = file_get_contents( $root . '/assets/css/ui.css' );
$variables  = file_get_contents( $root . '/assets/css/variables.css' );
$repository = file_get_contents( $root . '/includes/class-theme-repository.php' );
$assets     = file_get_contents( $root . '/includes/class-asset-registry.php' );
$editor     = file_get_contents( $root . '/includes/class-theme-editor.php' );
$all_css    = implode( "\n", array_map( 'file_get_contents', glob( $root . '/assets/css/*.css' ) ) );

function adam_ui_night_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
		exit( 1 );
	}
}

adam_ui_night_assert( ! file_exists( $root . '/assets/css/light.css' ), 'A separate Light Theme stylesheet must not exist.' );
adam_ui_night_assert( ! file_exists( $root . '/assets/css/dark.css' ), 'Night token defaults must not be duplicated in a static stylesheet.' );
adam_ui_night_assert( false === strpos( $assets, 'adam-ui-light' ) && false === strpos( $assets, 'adam-ui-dark' ), 'Legacy theme stylesheet handles must not be registered.' );
adam_ui_night_assert( false === strpos( $ui_css, 'adam-theme-light' ), 'Global UI overrides must never target Light mode.' );
adam_ui_night_assert( 0 === preg_match( '/page-id-|body\.home|single-post/', $ui_css ), 'Night architecture must not require page-specific maintenance.' );
adam_ui_night_assert( false !== strpos( $variables, 'Light-mode interoperability bridge' ), 'Blocksy-backed Light interoperability tokens are missing.' );
adam_ui_night_assert( false !== strpos( $repository, 'apply_automatic_contrast' ) && false !== strpos( $repository, 'contrast_map' ), 'Automatic Night foreground derivation is missing.' );
adam_ui_night_assert( false !== strpos( $editor, 'night_themes()' ) && false === strpos( $editor, "active_id( 'light' )" ), 'Theme Editor must expose Night presets only.' );

foreach ( array( 'header', 'footer', 'card', 'form', 'table', 'notice' ) as $component ) {
	adam_ui_night_assert( false !== strpos( $ui_css, '--adam-' . $component ), 'Generic Night overrides are missing for ' . $component . '.' );
}

foreach ( array( 'feature', 'cta', 'overlay', 'alternate', 'standard' ) as $role ) {
	adam_ui_night_assert( false !== strpos( $ui_css, '--adam-section-' . $role . '-text' ), 'Background-aware content contrast is missing for ' . $role . '.' );
}

adam_ui_night_assert( 0 === preg_match( '/(?:img|picture)[^{]*\{[^}]*(?:filter|mix-blend-mode|opacity)\s*:/is', $all_css ), 'Theme CSS must not alter photographs or graphics.' );
adam_ui_night_assert( false !== strpos( $ui_css, 'body.adam-theme-dark .adam-member-area' ), 'ADAM Socios public pages must inherit Night overrides.' );
adam_ui_night_assert( false !== strpos( $ui_css, 'body.adam-theme-dark .adam-bot' ), 'ADAM BOT must inherit Night overrides.' );

echo "PASS: Night-only override architecture.\n";
