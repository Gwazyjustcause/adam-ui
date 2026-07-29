<?php
/** Night-only override architecture contract. */

$root       = dirname( __DIR__ );
$ui_css     = file_get_contents( $root . '/assets/css/ui.css' );
$variables  = file_get_contents( $root . '/assets/css/variables.css' );
$repository = file_get_contents( $root . '/includes/class-theme-repository.php' );
$engine = file_get_contents( $root . '/includes/class-color-engine.php' );
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
adam_ui_night_assert( false !== strpos( $variables, '--adam-night-bg: var(--adam-bg)' ), 'The canonical Night canvas token is missing.' );
adam_ui_night_assert( false !== strpos( $repository, 'apply_automatic_contrast' ) && false !== strpos( $repository, 'contrast_map' ), 'Automatic Night foreground derivation is missing.' );
adam_ui_night_assert( false !== strpos( $repository, '--adam-night-bg:var(--adam-section-standard-bg)' ), 'The Night canvas must resolve from the main page surface.' );
adam_ui_night_assert( false !== strpos( $repository, '--adam-header-bg:var(--adam-night-bg);--adam-header-nav-bg:var(--adam-night-bg);--adam-footer-bg:var(--adam-night-bg)' ), 'Header and footer tokens must resolve to one continuous Night canvas.' );
adam_ui_night_assert( 3 <= substr_count( $ui_css, 'background: var(--adam-night-bg)' ), 'Night header, footer, and Blocksy rows must use the shared canvas.' );
adam_ui_night_assert( false !== strpos( $ui_css, ':is(.ct-header, .ct-footer) [data-row]' ), 'Blocksy header and footer row backgrounds are not reset.' );
adam_ui_night_assert( false === strpos( $ui_css, 'linear-gradient(' ) && false === strpos( $ui_css, 'radial-gradient(' ) && false === strpos( $ui_css, 'conic-gradient(' ), 'Night site chrome must not contain gradients.' );
adam_ui_night_assert( false !== strpos( $engine, 'contrast_ratio' ) && false !== strpos( $engine, 'ensure_contrast' ), 'The intelligent WCAG colour engine is missing.' );
adam_ui_night_assert( false !== strpos( $engine, "'hover_background'" ) && false !== strpos( $engine, "'disabled_background'" ), 'Supporting component states are not generated automatically.' );
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
