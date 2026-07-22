<?php
/**
 * Verifies the canonical WordPress plugin entry point and bootstrap lifecycle.
 *
 * Run with: php tests/wordpress-plugin-smoke.php
 *
 * @package ADAM_UI
 */

error_reporting( E_ALL );
set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

$plugin_file = dirname( __DIR__ ) . '/adam-ui.php';
$source      = file_get_contents( $plugin_file );
$hooks       = array();

function adam_ui_smoke_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function adam_ui_smoke_header( $source, $field ) {
	$pattern = '/^[\t ]*\*[\t ]+' . preg_quote( $field, '/' ) . ':[\t ]*(.+)$/mi';
	return preg_match( $pattern, $source, $matches ) ? trim( $matches[1] ) : '';
}

function plugin_dir_path( $file ) {
	return dirname( $file ) . DIRECTORY_SEPARATOR;
}

function plugin_dir_url( $file ) {
	return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

function add_action( $hook, $callback, $priority = 10 ) {
	global $hooks;
	$hooks[ $hook ][ $priority ][] = $callback;
	return true;
}

function add_filter( $hook, $callback, $priority = 10 ) {
	return add_action( $hook, $callback, $priority );
}

function is_admin() {
	return false;
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

define( 'ABSPATH', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );

adam_ui_smoke_assert( 'adam-ui' === basename( dirname( $plugin_file ) ), 'Plugin folder must be adam-ui.' );
adam_ui_smoke_assert( 'adam-ui.php' === basename( $plugin_file ), 'Main plugin file must be adam-ui.php.' );
adam_ui_smoke_assert( 'ADAM UI' === adam_ui_smoke_header( $source, 'Plugin Name' ), 'Plugin Name header is invalid.' );
adam_ui_smoke_assert( 'Shared UI framework, theme manager and design system for the ADAM ecosystem.' === adam_ui_smoke_header( $source, 'Description' ), 'Description header is invalid.' );
adam_ui_smoke_assert( '2.0.3' === adam_ui_smoke_header( $source, 'Version' ), 'Version header is invalid.' );
adam_ui_smoke_assert( 'ADAM' === adam_ui_smoke_header( $source, 'Author' ), 'Author header is invalid.' );
adam_ui_smoke_assert( 'adam-ui' === adam_ui_smoke_header( $source, 'Text Domain' ), 'Text Domain header is invalid.' );

require $plugin_file;

adam_ui_smoke_assert( defined( 'ADAM_UI_VERSION' ) && '2.0.3' === ADAM_UI_VERSION, 'ADAM_UI_VERSION is invalid.' );
adam_ui_smoke_assert( class_exists( 'ADAM_UI' ), 'ADAM_UI coordinator did not load.' );
adam_ui_smoke_assert( function_exists( 'adam_ui' ), 'adam_ui() API did not load.' );
adam_ui_smoke_assert( isset( $hooks['plugins_loaded'][10] ) && in_array( 'adam_ui', $hooks['plugins_loaded'][10], true ), 'WordPress bootstrap hook was not registered.' );
adam_ui_smoke_assert( adam_ui() instanceof ADAM_UI, 'Plugin could not initialize.' );

restore_error_handler();
echo "WordPress plugin smoke test passed.\n";
