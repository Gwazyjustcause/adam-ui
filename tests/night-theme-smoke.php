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
adam_ui_night_assert( false !== strpos( $repository, "'adam-header-bg' => 'adam-section-standard-bg'" ) && false !== strpos( $repository, "'adam-footer-bg' => 'adam-section-standard-bg'" ), 'Header and footer must inherit the global primary surface by default.' );
adam_ui_night_assert( false !== strpos( $ui_css, 'background: var(--adam-header-bg)' ) && false !== strpos( $ui_css, 'background: var(--adam-footer-bg)' ), 'Night header and footer must consume their inheriting component tokens.' );
adam_ui_night_assert( false !== strpos( $ui_css, '.ct-header [data-row]' ) && false !== strpos( $ui_css, '.ct-footer [data-row]' ), 'Blocksy header and footer row backgrounds are not reset.' );
adam_ui_night_assert( false === strpos( $ui_css, 'linear-gradient(' ) && false === strpos( $ui_css, 'radial-gradient(' ) && false === strpos( $ui_css, 'conic-gradient(' ), 'Night site chrome must not contain gradients.' );
adam_ui_night_assert( false !== strpos( $engine, 'contrast_ratio' ) && false !== strpos( $engine, 'ensure_contrast' ), 'The intelligent WCAG colour engine is missing.' );
adam_ui_night_assert( false !== strpos( $engine, "'hover_background'" ) && false !== strpos( $engine, "'disabled_background'" ), 'Supporting component states are not generated automatically.' );
foreach ( array( '.wp-block-group', '.wp-block-cover', '.wp-block-columns', '.ct-container', 'data-adam-night-background' ) as $selector ) {
	adam_ui_night_assert( false !== strpos( $controller . $ui_css, $selector ), 'Generic background classification is missing for ' . $selector . '.' );
}
foreach ( array( '[class^="adam-"]', '[class*=" adam-"]', 'classifyComponent', 'data-adam-night-component' ) as $selector ) {
	adam_ui_night_assert( false !== strpos( $controller . $ui_css, $selector ), 'ADAM ecosystem semantic classification is missing for ' . $selector . '.' );
}
foreach ( array( '"card"', '"empty"', '"stat"', '"feature"', '"form"', '"hero"' ) as $component_role ) {
	adam_ui_night_assert( false !== strpos( $ui_css, '[data-adam-night-component=' . $component_role . ']' ), 'Shared component bridge is missing for ' . $component_role . '.' );
}
adam_ui_night_assert( false !== strpos( $controller, "return 'transparent'" ), 'Transparent structural wrappers need an explicit non-surface classification.' );
adam_ui_night_assert( false !== strpos( $controller, 'inheritedCollectionComponent' ) && false !== strpos( $controller, 'isRenderedSurface' ), 'Unclassed feature tiles must be discovered through surfaced semantic collections.' );
adam_ui_night_assert( false !== strpos( $ui_css, '[data-adam-night-component="feature"]' ) && false !== strpos( $ui_css, 'background: var(--adam-night-surface-alt) !important' ), 'Feature tiles must inherit the alternate Night surface contract.' );
foreach ( array( '.ct-toggle-dropdown-desktop', '.ct-toggle-dropdown-mobile', '.ct-toggle-dropdown-desktop-ghost', '.ct-sub-menu-parent', 'fill: currentColor' ) as $indicator_contract ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $indicator_contract ), 'Blocksy navigation indicator contract is incomplete: ' . $indicator_contract );
}
adam_ui_night_assert( false === strpos( $ui_css, '.ct-toggle-dropdown-mobile {
	color: var(--adam-header-nav-text) !important' ), 'Blocksy navigation indicators must inherit state instead of receiving a fixed colour.' );
adam_ui_night_assert( false !== strpos( $controller, 'protectedComponentSelector' ), 'Semantic badges, controls, and media must be protected from surface classification.' );
adam_ui_night_assert( false !== strpos( $ui_css, '[data-adam-night-component="form"] :is(' ), 'ADAM-owned form controls do not have an authoritative Night contract.' );
foreach ( array( '.ct-pseudo-input', '[role="combobox"]', '.select2-selection', '.choices__inner', '.ts-control', '.selectize-input' ) as $form_family ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $form_family ), 'Global Night form coverage is missing for ' . $form_family . '.' );
}
foreach ( array( 'color-scheme: dark', 'var(--adam-form-placeholder)', 'var(--adam-form-focus)', 'var(--adam-form-disabled-bg)', 'var(--adam-form-disabled-text)', 'var(--adam-surface-hover)' ) as $form_state ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $form_state ), 'Native Night form state is missing: ' . $form_state . '.' );
}
adam_ui_night_assert( false !== strpos( $ui_css, 'select:is(:invalid, :has(option:checked[value=""]))' ), 'Native select placeholders must inherit the muted Night foreground.' );
adam_ui_night_assert( false !== strpos( $ui_css, '.select2-results__option--highlighted' ) && false !== strpos( $ui_css, '.choices__item--choice.is-highlighted' ), 'Searchable select dropdown states are incomplete.' );
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

foreach ( array( '--adam-night-button-bg', '--adam-night-button-text', '--adam-night-accent', '--adam-night-on-accent' ) as $button_token ) {
	adam_ui_night_assert( false !== strpos( $variables . $repository . $ui_css, $button_token ), 'Night button token is missing: ' . $button_token );
}
foreach ( array( '.ct-button', '.wp-element-button', '.wp-block-button__link', '[role="button"]', 'a[class*="button"]' ) as $button_family ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $button_family ), 'Shared Night button coverage is missing for ' . $button_family . '.' );
}
foreach ( array( ':hover, :focus-visible, :active', '[aria-disabled="true"]', '.adam-button--outline', '.adam-button--ghost', '.adam-button--accent', 'svg:not([fill="none"])', 'svg[stroke]:not([stroke="none"])' ) as $button_state ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $button_state ), 'Night button state or icon coverage is missing for ' . $button_state . '.' );
}

foreach ( array( 'feature', 'cta', 'overlay', 'alternate', 'standard' ) as $role ) {
	adam_ui_night_assert( false !== strpos( $ui_css, '--adam-section-' . $role . '-text' ), 'Background-aware content contrast is missing for ' . $role . '.' );
}

foreach ( array( '--adam-night-surface-heading', '--adam-night-surface-text', '--adam-night-surface-link', '--theme-heading-color', '--theme-text-color', '--theme-link-initial-color' ) as $foreground_token ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $foreground_token ), 'Classified surfaces do not own typography token ' . $foreground_token . '.' );
}
adam_ui_night_assert( false !== strpos( $ui_css, '--theme-heading-color: var(--adam-heading)' ), 'Blocksy must resolve its heading variable through the global Night heading token.' );
adam_ui_night_assert( false !== strpos( $ui_css, 'body.adam-theme-dark:not(.wp-admin) :is(h1, h2, h3, h4, h5, h6)' ), 'Global Night headings need enough specificity to outrank a theme bare-element rule.' );
adam_ui_night_assert( false === strpos( $ui_css, ':where(h1, h2, h3, h4, h5, h6, p, li, dt, dd, label, legend)' ), 'Headings must not share the zero-specificity body-copy rule.' );
foreach ( array( 'h1, h2, h3, h4, h5, h6', 'p, li, dt, dd, figcaption, caption, .wp-element-caption, label, legend, blockquote', 'a:not(.adam-button):not(.wp-element-button)' ) as $content_selector ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $content_selector ), 'Classified-surface typography coverage is missing for ' . $content_selector . '.' );
}
foreach ( array( '.adam-badge *', '.adam-status *', '.adam-notice *', '[role="status"] *', '[role="alert"] *' ) as $semantic_exception ) {
	adam_ui_night_assert( false !== strpos( $ui_css, $semantic_exception ), 'Semantic component foreground protection is missing for ' . $semantic_exception . '.' );
}

adam_ui_night_assert( 0 === preg_match( '/(?:img|picture)[^{]*\{[^}]*(?:filter|mix-blend-mode|opacity)\s*:/is', $all_css ), 'Theme CSS must not alter photographs or graphics.' );
adam_ui_night_assert( false !== strpos( $ui_css, 'body.adam-theme-dark .adam-member-area' ), 'ADAM Socios public pages must inherit Night overrides.' );
adam_ui_night_assert( false !== strpos( $ui_css, 'body.adam-theme-dark .adam-bot' ), 'ADAM BOT must inherit Night overrides.' );

echo "PASS: Night-only override architecture.\n";
