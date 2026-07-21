<?php
/**
 * Plugin Name:       ADAM Interface
 * Plugin URI:        https://github.com/Gwazyjustcause/adam-ui
 * Description:       Provides the shared visual foundation and theme infrastructure for ADAM plugins.
 * Version:           0.5.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ADAM
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       adam-interface
 *
 * @package ADAM_Interface
 */

defined( 'ABSPATH' ) || exit;

define( 'ADAM_INTERFACE_VERSION', '0.5.0' );
define( 'ADAM_INTERFACE_FILE', __FILE__ );
define( 'ADAM_INTERFACE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ADAM_INTERFACE_URL', plugin_dir_url( __FILE__ ) );

require_once ADAM_INTERFACE_PATH . 'includes/class-settings.php';
require_once ADAM_INTERFACE_PATH . 'includes/class-theme-manager.php';
require_once ADAM_INTERFACE_PATH . 'includes/class-components.php';
require_once ADAM_INTERFACE_PATH . 'includes/class-interface.php';

/**
 * Returns the main plugin instance.
 *
 * @return ADAM_Interface
 */
function adam_interface() {
	return ADAM_Interface::instance();
}

/**
 * Returns the central theme manager.
 *
 * @return ADAM_Interface_Theme_Manager
 */
function adam_interface_get_theme_manager() {
	return adam_interface()->get_theme_manager();
}

/**
 * Returns the configured theme mode.
 *
 * @return string
 */
function adam_interface_get_theme_mode() {
	return adam_interface_get_theme_manager()->get_theme_mode();
}

/**
 * Returns the server-resolved theme.
 *
 * Browser-specific system preferences are resolved by interface.js.
 *
 * @return string
 */
function adam_interface_get_resolved_theme() {
	return adam_interface_get_theme_manager()->get_resolved_theme();
}

/**
 * Enables ADAM Interface assets and theme classes for the current admin page.
 *
 * Plugins should call this from their own admin enqueue callback so WordPress
 * admin remains unchanged everywhere else.
 *
 * @return void
 */
function adam_interface_enable_admin_theme() {
	adam_interface_get_theme_manager()->enable_admin_theme();
}

/**
 * Returns the shared utility stylesheet handle for plugin dependencies.
 *
 * @return string
 */
function adam_interface_get_utility_style_handle() {
	return adam_interface_get_theme_manager()->get_utility_style_handle();
}

/**
 * Returns the shared component renderer.
 *
 * @return ADAM_Interface_Components
 */
function adam_interface_get_components() {
	return adam_interface()->get_components();
}

/**
 * Generates an accessible notice.
 *
 * @param string $message Notice text.
 * @param string $type    Notice type: info, success, warning, or error.
 * @param array  $args    Optional element attributes and content settings.
 * @return string
 */
function adam_interface_notice( $message, $type = 'info', $args = array() ) {
	return adam_interface_get_components()->notice( $message, $type, $args );
}

/**
 * Generates a shared button or link.
 *
 * @param string $label Button label.
 * @param string $url   Optional link URL. An empty URL creates a button.
 * @param array  $args  Optional variant, type, icon, and attributes.
 * @return string
 */
function adam_interface_button( $label, $url = '', $args = array() ) {
	return adam_interface_get_components()->button( $label, $url, $args );
}

/**
 * Generates a statistic card.
 *
 * @param string $label Statistic label.
 * @param string $value Statistic value.
 * @param array  $args  Optional icon, trend, and attributes.
 * @return string
 */
function adam_interface_stat_card( $label, $value, $args = array() ) {
	return adam_interface_get_components()->stat_card( $label, $value, $args );
}

/**
 * Generates an empty state.
 *
 * @param string $title       Empty-state title.
 * @param string $description Supporting description.
 * @param array  $args        Optional icon, action HTML, and attributes.
 * @return string
 */
function adam_interface_empty_state( $title, $description = '', $args = array() ) {
	return adam_interface_get_components()->empty_state( $title, $description, $args );
}

/**
 * Generates an accessible loading indicator.
 *
 * @param string $label Screen-reader label.
 * @param array  $args  Optional size and attributes.
 * @return string
 */
function adam_interface_loading_indicator( $label = '', $args = array() ) {
	return adam_interface_get_components()->loading_indicator( $label, $args );
}

/**
 * Generates a confirmation dialog for ADAMInterface.confirm().
 *
 * @param string $message Confirmation message.
 * @param array  $args    Optional title, labels, and attributes.
 * @return string
 */
function adam_interface_confirmation_dialog( $message, $args = array() ) {
	return adam_interface_get_components()->confirmation_dialog( $message, $args );
}

add_action( 'plugins_loaded', 'adam_interface' );
