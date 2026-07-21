<?php
/**
 * Plugin Name:       ADAM Interface
 * Plugin URI:        https://github.com/Gwazyjustcause/adam-ui
 * Description:       Provides the shared visual foundation and theme infrastructure for ADAM plugins.
 * Version:           0.2.0
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

define( 'ADAM_INTERFACE_VERSION', '0.2.0' );
define( 'ADAM_INTERFACE_FILE', __FILE__ );
define( 'ADAM_INTERFACE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ADAM_INTERFACE_URL', plugin_dir_url( __FILE__ ) );

require_once ADAM_INTERFACE_PATH . 'includes/class-settings.php';
require_once ADAM_INTERFACE_PATH . 'includes/class-theme-manager.php';
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

add_action( 'plugins_loaded', 'adam_interface' );
