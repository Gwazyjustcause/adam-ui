<?php
/** Night-only override architecture contract. */

$root       = dirname( __DIR__ );
$ui_css     = file_get_contents( $root . '/assets/css/ui.css' );
$variables  = file_get_contents( $root . '/assets/css/variables.css' );
$repository = file_get_contents( $root . '/includes/class-theme-repository.php' );
$engine = file_get_contents( $root . '/includes/class-color-engine.php' );
$controller = file_get_contents( $root . '/assets/js/ui.js' );
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
foreach ( array( '--adam-night-surface', '--adam-night-surface-alt', '--adam-night-accent-surface', '--adam-night-overlay' ) as $token ) {
	adam_ui_night_assert( false !== strpos( $variables . $repository, $token ), 'Background classifier token is missing: ' . $token );
}
adam_ui_night_assert( false !== strpos( $repository, 'apply_automatic_contrast' ) && false !== strpos( $repository, 'contrast_map' ), 'Automatic Night foreground derivation is missing.' );
adam_ui_night_assert( false !== strpos( $repository, '--adam-night-bg:var(--adam-section-standard-bg)' ), 'The Night canvas must resolve from the main page surface.' );
adam_ui_night_assert( false !== strpos( $repository, '--adam-header-bg:var(--adam-night-bg);--adam-header-nav-bg:var(--adam-night-bg);--adam-footer-bg:var(--adam-night-bg)' ), 'Header and footer tokens must resolve to one continuous Night canvas.' );
adam_ui_night_assert( 3 <= substr_count( $ui_css, 'background: var(--adam-night-bg)' ), 'Night header, footer, and Blocksy rows must use the shared canvas.' );
adam_ui_night_assert( false !== strpos( $ui_css, ':is(.ct-header, .ct-footer) [data-row]' ), 'Blocksy header and footer row backgrounds are not reset.' );
adam_ui_night_assert( false === strpos( $ui_css, 'linear-gradient(' ) && false === strpos( $ui_css, 'radial-gradient(' ) && false === strpos( $ui_css, 'conic-gradient(' ), 'Night site chrome must not contain gradients.' );
adam_ui_night_assert( false !== strpos( $engine, 'contrast_ratio' ) && false !== strpos( $engine, 'ensure_contrast' ), 'The intelligent WCAG colour engine is missing.' );
adam_ui_night_assert( false !== strpos( $engine, "'hover_background'" ) && false !== strpos( $engine, "'disabled_background'" ), 'Supporting component states are not generated automatically.' );
foreach ( array( '.wp-block-group', '.wp-block-cover', '.wp-block-columns', '.ct-container', 'data-adam-night-background' ) as $selector ) {
	adam_ui_night_assert( false !== strpos( $controller . $ui_css, $selector ), 'Generic background classification is missing for ' . $selector . '.' );
}
adam_ui_night_assert( false !== strpos( $controller, "backgroundImage.includes( 'url(' )" ), 'Background images are not protected by the classifier.' );
adam_ui_night_assert( false !== strpos( $controller, "return 'footer'" ), 'Nested footer containers do not receive the terminal Night surface classification.' );
adam_ui_night_assert( false !== strpos( $controller, "return 'accent'" ) && false !== strpos( $controller, 'colourLuminance' ), 'Gradient and light-surface detection are missing.' );
adam_ui_night_assert( false !== strpos( $ui_css, ':not(.wp-block-cover):not(.has-background-image):not([style*="url(" i])' ), 'Immediate CSS classification does not exclude image-backed blocks.' );
adam_ui_night_assert( false === strpos( $ui_css, '[data-adam-night-background="image"]' ), 'Protected images must not receive a Night background override.' );
foreach ( array( '.ct-container', '[data-row] > div', '[data-column]', '.footer-widgets', '.footer-bottom', '.site-info', '[data-adam-night-background="footer"]' ) as $footer_layer ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $footer_layer ), 'Nested footer surface coverage is missing for ' . $footer_layer . '.' );
}
adam_ui_night_assert( false !== strpos( $editor, 'night_themes()' ) && false === strpos( $editor, "active_id( 'light' )" ), 'Theme Editor must expose Night presets only.' );

foreach ( array( 'header', 'footer', 'card', 'form', 'table', 'notice' ) as $component ) {
	adam_ui_night_assert( false !== strpos( $ui_css, '--adam-' . $component ), 'Generic Night overrides are missing for ' . $component . '.' );
}

foreach ( array( 'feature', 'cta', 'overlay', 'alternate', 'standard' ) as $role ) {
	adam_ui_night_assert( false !== strpos( $ui_css, '--adam-section-' . $role . '-text' ), 'Background-aware content contrast is missing for ' . $role . '.' );
}

foreach ( array( '--adam-night-surface-heading', '--adam-night-surface-text', '--adam-night-surface-link', '--theme-heading-color', '--theme-text-color', '--theme-link-initial-color' ) as $foreground_token ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $foreground_token ), 'Classified surfaces do not own typography token ' . $foreground_token . '.' );
}
foreach ( array( 'h1, h2, h3, h4, h5, h6', 'p, li, dt, dd, figcaption, caption, .wp-element-caption, label, legend, blockquote', 'a:not(.adam-button):not(.wp-element-button)', 'button, input[type="button"], input[type="submit"]' ) as $content_selector ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $content_selector ), 'Classified-surface typography coverage is missing for ' . $content_selector . '.' );
}
foreach ( array( '.adam-badge *', '.adam-status *', '.adam-notice *', '[role="status"] *', '[role="alert"] *' ) as $semantic_exception ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $semantic_exception ), 'Semantic component foreground protection is missing for ' . $semantic_exception . '.' );
}

adam_ui_night_assert( 0 === preg_match( '/(?:img|picture)[^{]*\{[^}]*(?:filter|mix-blend-mode|opacity)\s*:/is', $all_css ), 'Theme CSS must not alter photographs or graphics.' );
adam_ui_night_assert( false !== strpos( $ui_css, 'body.adam-theme-dark .adam-member-area' ), 'ADAM Socios public pages must inherit Night overrides.' );
adam_ui_night_assert( false !== strpos( $ui_css, 'body.adam-theme-dark .adam-bot' ), 'ADAM BOT must inherit Night overrides.' );

echo "PASS: Night-only override architecture.\n";
