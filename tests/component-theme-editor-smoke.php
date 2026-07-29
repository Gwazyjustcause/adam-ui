<?php
/** Component registry and Theme Editor contract smoke test. */

$root       = dirname( __DIR__ );
$registry   = file_get_contents( $root . '/includes/class-theme-component-registry.php' );
$repository = file_get_contents( $root . '/includes/class-theme-repository.php' );
$editor     = file_get_contents( $root . '/includes/class-theme-editor.php' );
$script     = file_get_contents( $root . '/assets/js/theme-editor.js' );
$components = file_get_contents( $root . '/assets/css/components.css' );
$preview    = file_get_contents( $root . '/assets/css/theme-editor.css' );
$bootstrap  = file_get_contents( $root . '/adam-ui.php' );

function adam_ui_editor_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
		exit( 1 );
	}
}

foreach ( array( 'header', 'sections', 'feature-sections', 'hero', 'cards', 'buttons', 'forms', 'footer' ) as $component ) {
	adam_ui_editor_assert( false !== strpos( $registry, "'{$component}'" ), "{$component} is missing from the component registry." );
}

foreach ( array( 'Flat', 'Elevated', 'Soft', 'Minimal', 'Filled', 'Outline', 'Transparent', 'Floating', 'Standard', 'Compact', 'Alternate', 'Highlight' ) as $style ) {
	adam_ui_editor_assert( false !== strpos( $registry, "'{$style}'" ), "{$style} component preset is missing." );
}

adam_ui_editor_assert( false !== strpos( $editor, '$this->components->all()' ), 'Editor navigation is not registry-driven.' );
adam_ui_editor_assert( false === strpos( $editor, 'get_primary_groups' ), 'Legacy hardcoded editor groups still exist.' );
adam_ui_editor_assert( false !== strpos( $editor, 'render_component_preview' ), 'Every registered component must receive a live preview.' );
adam_ui_editor_assert( false !== strpos( $editor, 'data-adam-style-maps' ), 'Preset maps are not exposed to live preview.' );
adam_ui_editor_assert( false !== strpos( $editor, 'data-adam-intelligence' ), 'Semantic colour contracts are not exposed to live preview.' );
adam_ui_editor_assert( false !== strpos( $script, 'applyComponentStyle' ), 'Preset changes do not update the preview immediately.' );
adam_ui_editor_assert( false !== strpos( $script, 'applyIntelligence' ) && false !== strpos( $script, 'ensureContrast' ), 'Live preview is not deriving accessible component states.' );
adam_ui_editor_assert( false !== strpos( $repository, 'apply_styles' ), 'Saved component styles are not resolved into design tokens.' );
adam_ui_editor_assert( false !== strpos( $components . $preview, '--adam-card-surface-opacity' ), 'Card visual presets are not bound to shared CSS.' );
adam_ui_editor_assert( false !== strpos( $components . $preview, '--adam-button-fill-opacity' ), 'Button visual presets are not bound to shared CSS.' );
adam_ui_editor_assert( false !== strpos( $bootstrap, 'function adam_ui_register_theme_component' ), 'Public component registration API is missing.' );
adam_ui_editor_assert( false !== strpos( $registry, "apply_filters( 'adam_ui_theme_components'" ), 'Theme component registration filter is missing.' );
adam_ui_editor_assert( false !== strpos( $script, "'ArrowDown'" ) && false !== strpos( $script, "'Home'" ), 'Component navigation is not keyboard accessible.' );

echo "PASS: registry-driven component Theme Editor contract.\n";
