<?php
/** Plugin Integration Framework contract smoke test. */

$root      = dirname( __DIR__ );
$registry  = file_get_contents( $root . '/includes/class-theme-component-registry.php' );
$plugins   = file_get_contents( $root . '/includes/class-plugin-registry.php' );
$assets    = file_get_contents( $root . '/includes/class-asset-registry.php' );
$editor    = file_get_contents( $root . '/includes/class-theme-editor.php' );
$bootstrap = file_get_contents( $root . '/adam-ui.php' );
$css       = file_get_contents( $root . '/assets/css/components.css' );
$script    = file_get_contents( $root . '/assets/js/components.js' );
$docs      = file_get_contents( $root . '/docs/plugin-integration.md' );

function adam_ui_integration_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
		exit( 1 );
	}
}

adam_ui_integration_assert( false !== strpos( $registry, "add_action( 'init', array( \$this, 'discover_components' ), 1 )" ), 'Automatic component discovery lifecycle is missing.' );
adam_ui_integration_assert( false !== strpos( $registry, "do_action( 'adam_ui_register_components', \$this )" ), 'Plugin discovery action is missing.' );
adam_ui_integration_assert( false !== strpos( $registry, 'register_plugin_component' ), 'Owned plugin registration API is missing.' );
adam_ui_integration_assert( false !== strpos( $registry, "\$plugin_slug . '--' . \$identifier" ), 'Plugin component identifiers are not namespaced.' );
adam_ui_integration_assert( false !== strpos( $registry, "['owner'] !== \$owner" ), 'Cross-plugin collision protection is missing.' );
adam_ui_integration_assert( false !== strpos( $registry, 'public function grouped()' ) && false !== strpos( $editor, '$this->components->grouped()' ), 'Theme Editor does not group discovered plugin components.' );
adam_ui_integration_assert( false !== strpos( $plugins, 'record_theme_component' ), 'Plugin registry does not record component ownership.' );
adam_ui_integration_assert( false !== strpos( $assets, "'requires'" ) && false !== strpos( $assets, 'enqueue_components' ), 'Central loader does not resolve shared component dependencies.' );
adam_ui_integration_assert( false !== strpos( $assets, 'enqueue_component_styles' ) && false !== strpos( $editor, 'enqueue_component_styles' ), 'Plugin preview styles are not loaded safely in the Theme Editor.' );
adam_ui_integration_assert( false !== strpos( $bootstrap, 'function adam_ui_register_component' ), 'Canonical procedural registration API is missing.' );
adam_ui_integration_assert( false !== strpos( $bootstrap, 'function adam_ui_component_class' ), 'Isolated markup class helper is missing.' );

foreach ( array( '.adam-card', '.adam-button', '.adam-table', '.adam-modal', '.adam-side-panel', '.adam-tabs', '.adam-search-bar', '.adam-status-badge', '.adam-pagination', '.adam-empty-state', '.adam-loading' ) as $selector ) {
	adam_ui_integration_assert( false !== strpos( $css, $selector ), "{$selector} is missing from the shared library." );
}
adam_ui_integration_assert( false !== strpos( $script, 'bindSidePanels' ), 'Shared side-panel interaction is missing.' );
adam_ui_integration_assert( false !== strpos( $docs, 'Do not hardcode colours' ) && false !== strpos( $docs, 'Fallback requirement' ), 'Developer integration guidelines are incomplete.' );

echo "PASS: plugin integration framework contract.\n";
