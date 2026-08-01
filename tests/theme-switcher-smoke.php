<?php
/** Reusable Theme Switcher native WordPress placement and browser contract. */

$root = dirname( __DIR__ );
$js   = file_get_contents( $root . '/assets/js/ui.js' );
$css  = file_get_contents( $root . '/assets/css/theme-switcher.css' );
$base_css = file_get_contents( $root . '/assets/css/ui.css' );
$editor_css = file_get_contents( $root . '/assets/css/theme-switcher-editor.css' );
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
adam_ui_switcher_assert( false !== strpos( $component, "'editor_style'    => 'adam-ui-theme-switcher-editor'" ), 'The Gutenberg editor stylesheet is not registered.' );
adam_ui_switcher_assert( false !== strpos( $component, "'style'           => 'adam-ui-theme-switcher'" ), 'The block frontend stylesheet is not registered.' );
adam_ui_switcher_assert( false !== strpos( $editor_css, '.adam-ui-theme-switcher-block-preview' ), 'The Gutenberg block preview stylesheet is missing.' );
adam_ui_switcher_assert( false !== strpos( $component, "register_widget( 'ADAM_UI_Theme_Switcher_Widget' )" ), 'The WordPress widget adapter is missing.' );
adam_ui_switcher_assert( false === strpos( $component, "'shortcode' !== \$this->settings->get_theme_switcher_placement()" ), 'An explicitly inserted shortcode must not be suppressed by the automatic placement setting.' );
adam_ui_switcher_assert( false === strpos( $component, "'block' !== \$this->settings->get_theme_switcher_placement()" ), 'An explicitly inserted block must not be suppressed by the automatic placement setting.' );
adam_ui_switcher_assert( false === strpos( $widget, "'widget' !== adam_ui()->get_settings()->get_theme_switcher_placement()" ), 'An explicitly placed widget must not be suppressed by the automatic placement setting.' );
adam_ui_switcher_assert( false !== strpos( $widget, '$markup = adam_ui_get_theme_manager()->get_theme_switcher_markup(' ), 'The widget must render through the shared Theme Switcher component.' );
adam_ui_switcher_assert( false !== strpos( $block, "blocks.registerBlockType( 'adam-ui/theme-switcher'" ), 'The block editor integration is missing.' );
adam_ui_switcher_assert( false !== strpos( $widget, 'extends WP_Widget' ), 'The Theme Switcher must be available as a standard WordPress widget.' );
adam_ui_switcher_assert( false !== strpos( $widget, "'style'   => \$style" ), 'Widget instances must pass their own display style to the shared renderer.' );
adam_ui_switcher_assert( false !== strpos( $widget, "get_field_id( 'style' )" ), 'Widget instances must expose a display style control.' );
adam_ui_switcher_assert( false === strpos( $block, 'Use global setting' ), 'Blocks must not depend on a removed global display style.' );
adam_ui_switcher_assert( false !== strpos( $bootstrap, 'function adam_ui_theme_switcher(' ), 'The public component helper is missing.' );
adam_ui_switcher_assert( false !== strpos( $component, 'adam-theme-select-' ) && false !== strpos( $component, 'self::$instance_count' ), 'Multiple switchers must receive unique control IDs.' );
adam_ui_switcher_assert( false !== strpos( $component, 'adam-ui-theme-switcher' ), 'The switcher must expose a dedicated ADAM-owned component root.' );
adam_ui_switcher_assert( false !== strpos( $css, '.adam-ui-theme-switcher.adam-theme-switcher' ), 'Switcher rules must be scoped with sufficient component specificity.' );
adam_ui_switcher_assert( false !== strpos( $css, 'box-sizing: border-box' ), 'The component boundary must include its padding and border.' );
adam_ui_switcher_assert( false !== strpos( $css, 'flex: 0 0 auto' ) && false !== strpos( $css, 'width: auto !important' ) && false !== strpos( $css, 'min-width: max-content' ), 'The dropdown must preserve its intrinsic text and indicator width.' );
adam_ui_switcher_assert( false !== strpos( $css, '.adam-theme-switcher__select:focus-visible' ) && false !== strpos( $css, 'outline-offset: -3px' ), 'Dropdown focus must render inside the control boundary.' );
adam_ui_switcher_assert( false !== strpos( $css, '.adam-theme-switcher__choice:focus-visible' ) && false !== strpos( $css, 'box-shadow: inset 0 0 0 1px var(--adam-primary)' ), 'Button focus must render inside the control boundary.' );
adam_ui_switcher_assert( false !== strpos( $base_css, '):not(.adam-theme-switcher__choice):not(.ct-toggle-dropdown-mobile):not(.ct-toggle-dropdown-desktop-ghost):not([role="combobox"]):focus-visible' ), 'Global button focus must defer to owned Theme Switcher, dropdown, and Blocksy navigation controls.' );
foreach ( array( 'icon-only', 'icon-label', 'dropdown' ) as $style ) {
	adam_ui_switcher_assert( false !== strpos( $component . $widget . $block, $style ), 'Instance display style is missing: ' . $style );
}
adam_ui_switcher_assert( false === strpos( $settings . $admin, 'theme_switcher_placement' ), 'Global Theme Switcher placement must not be stored or displayed.' );
adam_ui_switcher_assert( false === strpos( $settings . $admin, 'theme_switcher_position' ), 'Global floating position must not be stored or displayed.' );
adam_ui_switcher_assert( false === strpos( $settings . $admin, 'theme_switcher_style' ), 'Global display style must not be stored or displayed.' );
adam_ui_switcher_assert( false === strpos( $manager . $css, 'legacy-footer' ), 'Legacy automatic footer placement must be removed.' );
adam_ui_switcher_assert( false === strpos( $manager . $css, 'floating' ), 'Global floating placement must be removed.' );
adam_ui_switcher_assert( false === strpos( $admin, '[adam_theme_switcher]' ), 'The settings page must not display the shortcode.' );

echo "PASS: reusable Theme Switcher native placement contract.\n";
