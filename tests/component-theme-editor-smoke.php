<?php
/** Component-oriented Theme Editor contract smoke test. */

$root       = dirname( __DIR__ );
$repository = file_get_contents( $root . '/includes/class-theme-repository.php' );
$editor     = file_get_contents( $root . '/includes/class-theme-editor.php' );
$script     = file_get_contents( $root . '/assets/js/theme-editor.js' );
$components = file_get_contents( $root . '/assets/css/components.css' );
$preview_css = file_get_contents( $root . '/assets/css/theme-editor.css' );

function adam_ui_editor_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
		exit( 1 );
	}
}

foreach ( array( 'Header', 'Footer', 'Hero', 'Sections', 'Cards', 'Buttons', 'Forms', 'Tables', 'Notifications' ) as $section ) {
	adam_ui_editor_assert( false !== strpos( $editor, "'{$section}'" ), "{$section} editor section is missing." );
}

foreach ( array( 'adam-header-bg', 'adam-footer-switcher-bg', 'adam-hero-heading', 'adam-section-overlay-bg', 'adam-card-bg', 'adam-form-focus', 'adam-table-alt-row-bg' ) as $token ) {
	adam_ui_editor_assert( false !== strpos( $repository, "'{$token}'" ), "{$token} is missing from the theme schema." );
	adam_ui_editor_assert( false !== strpos( $components . $preview_css, "--{$token}" ), "{$token} is not bound to a component or preview." );
}
adam_ui_editor_assert( false !== strpos( $repository, "'outline'=>array" ) && false !== strpos( $repository, "'hover-border'=>'Hover Border'" ), 'Complete Outline button state tokens are not generated.' );
adam_ui_editor_assert( false !== strpos( $repository, "'error'=>array" ) && false !== strpos( $repository, "'adam-notice-'.\$type.'-bg'" ), 'Notification state tokens are not generated.' );

adam_ui_editor_assert( false !== strpos( $repository, 'sanitize_css_color' ), 'Free-form CSS colours are not validated server-side.' );
adam_ui_editor_assert( false !== strpos( $script, "CSS.supports( 'color', value )" ), 'Live CSS colour validation is missing.' );
adam_ui_editor_assert( false !== strpos( $editor, 'adam-css-color-picker' ) && false !== strpos( $editor, 'adam-css-color-value' ), 'The visual picker and free-form colour field must both be available.' );
adam_ui_editor_assert( false !== strpos( $editor, 'render_component_preview' ), 'Per-component previews are missing.' );
foreach ( array( 'header', 'sections', 'cards', 'buttons', 'forms', 'footer' ) as $group ) {
	adam_ui_editor_assert( false !== strpos( $editor, "'{$group}' => array(" ), "{$group} is missing from simplified navigation." );
}
adam_ui_editor_assert( false !== strpos( $editor, 'data-adam-editor-tab="advanced"' ), 'Advanced is missing from simplified navigation.' );
adam_ui_editor_assert( false === strpos( $editor, 'data-adam-editor-tab="hero"' ), 'Hero must not be a first-level destination.' );
adam_ui_editor_assert( false === strpos( $editor, 'data-adam-editor-tab="tables"' ), 'Tables must not be a first-level destination.' );
adam_ui_editor_assert( false === strpos( $editor, 'data-adam-editor-tab="notifications"' ), 'Notifications must not be a first-level destination.' );
adam_ui_editor_assert( false !== strpos( $editor, 'adam-theme-editor__advanced-section' ), 'Detailed controls must remain available under Advanced.' );
adam_ui_editor_assert( false !== strpos( $script, 'syncTokenControls' ), 'Simple and Advanced controls are not synchronized.' );
adam_ui_editor_assert( false !== strpos( $script, "'ArrowDown'" ) && false !== strpos( $script, "'Home'" ), 'Component navigation is not keyboard accessible.' );

echo "PASS: component-oriented Theme Editor contract.\n";
