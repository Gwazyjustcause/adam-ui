<?php
/**
 * Plugin Name:       ADAM UI
 * Plugin URI:        https://github.com/Gwazyjustcause/adam-ui
 * Description:       Shared UI framework, theme manager and design system for the ADAM ecosystem.
 * Version:           5.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ADAM
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       adam-ui
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

define( 'ADAM_UI_VERSION', '5.0.0' );
define( 'ADAM_UI_FILE', __FILE__ );
define( 'ADAM_UI_PATH', plugin_dir_path( __FILE__ ) );
define( 'ADAM_UI_URL', plugin_dir_url( __FILE__ ) );

require_once ADAM_UI_PATH . 'includes/class-settings.php';
require_once ADAM_UI_PATH . 'includes/class-color-engine.php';
require_once ADAM_UI_PATH . 'includes/class-theme-component-registry.php';
require_once ADAM_UI_PATH . 'includes/class-theme-repository.php';
require_once ADAM_UI_PATH . 'includes/class-asset-registry.php';
require_once ADAM_UI_PATH . 'includes/class-plugin-registry.php';
require_once ADAM_UI_PATH . 'includes/class-theme-manager.php';
require_once ADAM_UI_PATH . 'includes/class-components.php';
require_once ADAM_UI_PATH . 'includes/class-admin.php';
require_once ADAM_UI_PATH . 'includes/class-theme-editor.php';
require_once ADAM_UI_PATH . 'includes/class-ui.php';

/**
 * Returns the main plugin instance.
 *
 * @return ADAM_UI
 */
function adam_ui() {
	return ADAM_UI::instance();
}

/**
 * Returns the central theme manager.
 *
 * @return ADAM_UI_Theme_Manager
 */
function adam_ui_get_theme_manager() {
	return adam_ui()->get_theme_manager();
}

/**
 * Returns the configured theme mode.
 *
 * @return string
 */
function adam_ui_get_theme_mode() {
	return adam_ui_get_theme_manager()->get_theme_mode();
}

/**
 * Returns the server-resolved theme.
 *
 * Browser-specific system preferences are resolved by ui.js.
 *
 * @return string
 */
function adam_ui_get_resolved_theme() {
	return adam_ui_get_theme_manager()->get_resolved_theme();
}

/**
 * Enables ADAM UI assets and theme classes for the current admin page.
 *
 * Plugins should call this from their own admin enqueue callback so WordPress
 * admin remains unchanged everywhere else.
 *
 * @return void
 */
function adam_ui_enable_admin_theme() {
	adam_ui_get_theme_manager()->enable_admin_theme();
}

/**
 * Returns the shared utility stylesheet handle for plugin dependencies.
 *
 * @return string
 */
function adam_ui_get_utility_style_handle() {
	return adam_ui_get_theme_manager()->get_utility_style_handle();
}

/**
 * Returns the shared component renderer.
 *
 * @return ADAM_UI_Components
 */
function adam_ui_get_components() {
	return adam_ui()->get_components();
}

/**
 * Generates an accessible notice.
 *
 * @param string $message Notice text.
 * @param string $type    Notice type: info, success, warning, or error.
 * @param array  $args    Optional element attributes and content settings.
 * @return string
 */
function adam_ui_notice( $message, $type = 'info', $args = array() ) {
	return adam_ui_get_components()->notice( $message, $type, $args );
}

/**
 * Generates a shared button or link.
 *
 * @param string $label Button label.
 * @param string $url   Optional link URL. An empty URL creates a button.
 * @param array  $args  Optional variant, type, icon, and attributes.
 * @return string
 */
function adam_ui_button( $label, $url = '', $args = array() ) {
	return adam_ui_get_components()->button( $label, $url, $args );
}

/**
 * Generates a statistic card.
 *
 * @param string $label Statistic label.
 * @param string $value Statistic value.
 * @param array  $args  Optional icon, trend, and attributes.
 * @return string
 */
function adam_ui_stat_card( $label, $value, $args = array() ) {
	return adam_ui_get_components()->stat_card( $label, $value, $args );
}

/**
 * Generates an empty state.
 *
 * @param string $title       Empty-state title.
 * @param string $description Supporting description.
 * @param array  $args        Optional icon, action HTML, and attributes.
 * @return string
 */
function adam_ui_empty_state( $title, $description = '', $args = array() ) {
	return adam_ui_get_components()->empty_state( $title, $description, $args );
}

/**
 * Generates an accessible loading indicator.
 *
 * @param string $label Screen-reader label.
 * @param array  $args  Optional size and attributes.
 * @return string
 */
function adam_ui_loading_indicator( $label = '', $args = array() ) {
	return adam_ui_get_components()->loading_indicator( $label, $args );
}

/**
 * Generates a confirmation dialog for ADAMUI.confirm().
 *
 * @param string $message Confirmation message.
 * @param array  $args    Optional title, labels, and attributes.
 * @return string
 */
function adam_ui_confirmation_dialog( $message, $args = array() ) {
	return adam_ui_get_components()->confirmation_dialog( $message, $args );
}

/** Stable shorthand for the Theme Manager. */
function adam_theme() {
	return adam_ui_get_theme_manager();
}

/** Returns the persistent theme repository. */
function adam_ui_themes() { return adam_ui()->get_theme_repository(); }

/** Returns active Night design tokens. Light mode intentionally has no ADAM palette. */
function adam_tokens( $mode = 'dark' ) { return adam_ui_themes()->tokens( $mode ); }

/** Returns one stable design token value. */
function adam_token( $name, $fallback = '', $mode = 'dark' ) { return adam_ui_themes()->token( $name, $mode, $fallback ); }

/**
 * Returns the central asset registry or a registered asset URL.
 *
 * @param string $asset Optional asset key.
 * @return ADAM_UI_Asset_Registry|string
 */
function adam_asset( $asset = '' ) {
	$registry = adam_ui()->get_asset_registry();
	return '' === $asset ? $registry : $registry->get_url( $asset );
}

/**
 * Stable notice helper alias.
 *
 * @param string $message Notice text.
 * @param string $type    Notice type.
 * @param array  $args    Optional renderer arguments.
 * @return string
 */
function adam_notice( $message, $type = 'info', $args = array() ) {
	return adam_ui_notice( $message, $type, $args );
}

/**
 * Stable button helper alias.
 *
 * @param string $label Button label.
 * @param string $url   Optional URL.
 * @param array  $args  Optional renderer arguments.
 * @return string
 */
function adam_button( $label, $url = '', $args = array() ) {
	return adam_ui_button( $label, $url, $args );
}

/**
 * Generates shared card markup.
 *
 * @param string $content Card content.
 * @param array  $args    Optional renderer arguments.
 * @return string
 */
function adam_card( $content, $args = array() ) {
	return adam_ui_get_components()->card( $content, $args );
}

/**
 * Registers an ADAM ecosystem plugin and its compatibility metadata.
 *
 * @param string $slug Plugin identifier.
 * @param string $name Human-readable plugin name.
 * @param array  $args Version, minimum ADAM UI version, and components.
 * @return bool
 */
function adam_ui_register_plugin( $slug, $name, $args = array() ) {
	return ADAM_UI::register_plugin( $slug, $name, $args );
}

/**
 * Registers an extensible Night Theme component.
 *
 * Registered components automatically receive an editor tab, controls, preview,
 * persistent tokens, preset effects, and optional automatic contrast mapping.
 *
 * @param string $slug Component identifier.
 * @param array  $args Component schema.
 * @return bool
 */
function adam_ui_register_theme_component( $slug, $args ) {
	return ADAM_UI::register_theme_component( $slug, $args );
}

/**
 * Registers a component owned by an ADAM ecosystem plugin.
 *
 * @param string $plugin_slug Plugin identifier.
 * @param string $component_id Identifier unique inside that plugin.
 * @param array  $definition Component name, category, preview, defaults, presets, tokens, and assets.
 * @return bool
 */
function adam_ui_register_component( $plugin_slug, $component_id, $definition ) {
	return ADAM_UI::register_component( $plugin_slug, $component_id, $definition );
}

/** Requests several shared or plugin-owned components. */
function adam_ui_enqueue_components( $components ) {
	return adam_ui()->enqueue_components( $components );
}

/** Returns isolated CSS classes for plugin component root markup. */
function adam_ui_component_class( $plugin_slug, $component_id, $extra = '' ) {
	return adam_ui()->get_theme_component_registry()->component_class( $plugin_slug, $component_id, $extra );
}

/**
 * Loads translations at WordPress' supported lifecycle point.
 *
 * WordPress 6.7 reports just-in-time text-domain loading before init, so the
 * bootstrap and service constructors must remain translation-free.
 */
function adam_ui_load_textdomain() {
	if ( ! did_action( 'init' ) ) {
		return false;
	}

	load_plugin_textdomain(
		'adam-ui',
		false,
		dirname( plugin_basename( ADAM_UI_FILE ) ) . '/languages'
	);

	return true;
}

add_action( 'init', 'adam_ui_load_textdomain', -1 );
add_action( 'plugins_loaded', 'adam_ui' );
