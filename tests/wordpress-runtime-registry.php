<?php
/**
 * Manual runtime registry probe for a bootstrapped WordPress installation.
 *
 * Run with: wp eval-file tests/wordpress-runtime-registry.php
 */

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'did_action' ) ) {
	echo "SKIP: Run this probe through WP-CLI's eval-file command.\n";
	return;
}

global $wp_widget_factory;

if ( ! did_action( 'widgets_init' ) ) {
	do_action( 'widgets_init' );
}

$block = WP_Block_Type_Registry::get_instance()->get_registered( 'adam-ui/theme-switcher' );
$widget_registered = isset( $wp_widget_factory->widgets['ADAM_UI_Theme_Switcher_Widget'] );

echo wp_json_encode(
	array(
		'widget_registered' => $widget_registered,
		'block_registered'  => null !== $block,
		'editor_script'     => $block ? $block->editor_script_handles : array(),
		'editor_style'      => $block ? $block->editor_style_handles : array(),
		'frontend_style'    => $block ? $block->style_handles : array(),
		'block_category'    => has_filter(
			'block_categories_all',
			array( adam_ui_get_theme_manager()->get_theme_switcher(), 'register_block_category' )
		),
	)
);
