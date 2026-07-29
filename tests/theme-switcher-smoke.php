<?php
/** Reusable Theme Switcher placement and browser contract. */

$root = dirname( __DIR__ );
$js   = file_get_contents( $root . '/assets/js/ui.js' );
$css  = file_get_contents( $root . '/assets/css/theme-switcher.css' );
$manager = file_get_contents( $root . '/includes/class-theme-manager.php' );
$component = file_get_contents( $root . '/includes/class-theme-switcher.php' );
$widget = file_get_contents( $root . '/includes/class-theme-switcher-widget.php' );
$block = file_get_contents( $root . '/assets/js/theme-switcher-block.js' );
$settings = file_get_contents( $root . '/includes/class-settings.php' );
$admin = file_get_contents( $root . '/includes/class-admin.php' );
$bootstrap = file_get_contents( $root . '/adam-ui.php' );

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
adam_ui_switcher_assert( false !== strpos( $js, '[data-adam-theme-value]' ), 'Icon switcher controls must use the same client Theme Manager.' );
adam_ui_switcher_assert( false !== strpos( $js, "button.setAttribute( 'aria-pressed'" ), 'Icon controls must expose their active mode accessibly.' );
adam_ui_switcher_assert( false !== strpos( $component, "add_shortcode( 'adam_theme_switcher'" ), 'The portable shortcode adapter is missing.' );
adam_ui_switcher_assert( false !== strpos( $component, "register_block_type(\n\t\t\t'adam-ui/theme-switcher'" ), 'The dynamic Gutenberg block is missing.' );
adam_ui_switcher_assert( false !== strpos( $component, "register_widget( 'ADAM_UI_Theme_Switcher_Widget' )" ), 'The WordPress widget adapter is missing.' );
adam_ui_switcher_assert( false !== strpos( $component, "'shortcode' !== \$this->settings->get_theme_switcher_placement()" ), 'The shortcode must render only when its placement is selected.' );
adam_ui_switcher_assert( false !== strpos( $component, "'block' !== \$this->settings->get_theme_switcher_placement()" ), 'The block must render only when its placement is selected.' );
adam_ui_switcher_assert( false !== strpos( $widget, "'widget' !== adam_ui()->get_settings()->get_theme_switcher_placement()" ), 'The widget must render only when its placement is selected.' );
adam_ui_switcher_assert( false !== strpos( $block, "blocks.registerBlockType( 'adam-ui/theme-switcher'" ), 'The block editor integration is missing.' );
adam_ui_switcher_assert( false !== strpos( $widget, 'extends WP_Widget' ), 'The Theme Switcher must be available as a standard WordPress widget.' );
adam_ui_switcher_assert( false !== strpos( $bootstrap, 'function adam_ui_theme_switcher(' ), 'The public component helper is missing.' );
adam_ui_switcher_assert( false !== strpos( $component, 'adam-theme-select-' ) && false !== strpos( $component, 'self::$instance_count' ), 'Multiple switchers must receive unique control IDs.' );
adam_ui_switcher_assert( false !== strpos( $component, 'adam-ui-theme-switcher' ), 'The switcher must expose a dedicated ADAM-owned component root.' );
adam_ui_switcher_assert( false !== strpos( $css, '.adam-ui-theme-switcher.adam-theme-switcher' ), 'Switcher rules must be scoped with sufficient component specificity.' );
adam_ui_switcher_assert( false !== strpos( $css, '.adam-theme-switcher--floating' ), 'Floating presentation is missing.' );
foreach ( array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' ) as $position ) {
	adam_ui_switcher_assert( false !== strpos( $css . $settings . $admin, $position ), 'Floating position is missing: ' . $position );
}
foreach ( array( 'icon-only', 'icon-label', 'dropdown' ) as $style ) {
	adam_ui_switcher_assert( false !== strpos( $component . $settings . $admin, $style ), 'Display style is missing: ' . $style );
}
adam_ui_switcher_assert( false !== strpos( $settings, "'theme_switcher_placement' => 'legacy-footer'" ), 'Existing installations must retain the legacy footer default.' );
adam_ui_switcher_assert( false !== strpos( $settings, "array( 'legacy-footer', 'widget', 'block', 'shortcode', 'floating' )" ), 'All supported placement settings must be validated centrally.' );
adam_ui_switcher_assert( false !== strpos( $manager, "'legacy-footer' === \$placement" ), 'Legacy footer hooks must be conditional.' );
adam_ui_switcher_assert( false !== strpos( $manager, "'floating' === \$placement" ), 'Floating placement must be automatic only when selected.' );
adam_ui_switcher_assert( false !== strpos( $manager, 'adam-footer-theme-layout' ), 'The backwards-compatible footer wrapper is missing.' );
adam_ui_switcher_assert( strpos( $manager, "get_theme_switcher_markup( array( 'context' => 'legacy-footer' ) )" ) < strpos( $manager, 'adam-footer-copyright-text' ), 'Legacy selector markup must precede copyright markup.' );
adam_ui_switcher_assert( false !== strpos( $css, '[data-adam-footer-integrated="true"]' ), 'Integrated footer presentation is missing.' );

echo "PASS: reusable Theme Switcher placement contract.\n";
