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
$did_actions = array();
$translation_calls = array();
$loaded_textdomains = array();

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

function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

function add_action( $hook, $callback, $priority = 10 ) {
	global $hooks;
	$hooks[ $hook ][ $priority ][] = $callback;
	return true;
}

function add_filter( $hook, $callback, $priority = 10 ) {
	return add_action( $hook, $callback, $priority );
}

function apply_filters( $hook, $value ) {
	return $value;
}

function do_action( $hook, ...$args ) {
	global $hooks, $did_actions;
	$did_actions[ $hook ] = isset( $did_actions[ $hook ] ) ? $did_actions[ $hook ] + 1 : 1;
	if ( empty( $hooks[ $hook ] ) ) {
		return;
	}
	ksort( $hooks[ $hook ] );
	foreach ( $hooks[ $hook ] as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			call_user_func_array( $callback, $args );
		}
	}
}

function did_action( $hook ) {
	global $did_actions;
	return isset( $did_actions[ $hook ] ) ? $did_actions[ $hook ] : 0;
}

function is_admin() {
	return false;
}

function get_option( $key, $default = false ) {
	return $default;
}

function update_option() {
	return true;
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_html_class( $value ) {
	return sanitize_key( $value );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function __( $text, $domain = 'default' ) {
	global $translation_calls;
	if ( 'adam-ui' === $domain && ! did_action( 'init' ) ) {
		throw new RuntimeException( 'ADAM UI translated before init: ' . $text );
	}
	$translation_calls[] = array( 'text' => $text, 'domain' => $domain );
	return $text;
}

function load_plugin_textdomain( $domain, $deprecated = false, $path = '' ) {
	global $loaded_textdomains;
	if ( ! did_action( 'init' ) ) {
		throw new RuntimeException( 'Text domain loaded before init: ' . $domain );
	}
	$loaded_textdomains[] = array( 'domain' => $domain, 'path' => $path );
	return true;
}

define( 'ABSPATH', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );

adam_ui_smoke_assert( 'adam-ui' === basename( dirname( $plugin_file ) ), 'Plugin folder must be adam-ui.' );
adam_ui_smoke_assert( 'adam-ui.php' === basename( $plugin_file ), 'Main plugin file must be adam-ui.php.' );
adam_ui_smoke_assert( 'ADAM UI' === adam_ui_smoke_header( $source, 'Plugin Name' ), 'Plugin Name header is invalid.' );
adam_ui_smoke_assert( 'Shared UI framework, theme manager and design system for the ADAM ecosystem.' === adam_ui_smoke_header( $source, 'Description' ), 'Description header is invalid.' );
adam_ui_smoke_assert( '5.1.0' === adam_ui_smoke_header( $source, 'Version' ), 'Version header is invalid.' );
adam_ui_smoke_assert( 'ADAM' === adam_ui_smoke_header( $source, 'Author' ), 'Author header is invalid.' );
adam_ui_smoke_assert( 'adam-ui' === adam_ui_smoke_header( $source, 'Text Domain' ), 'Text Domain header is invalid.' );

require $plugin_file;

adam_ui_smoke_assert( defined( 'ADAM_UI_VERSION' ) && '5.1.0' === ADAM_UI_VERSION, 'ADAM_UI_VERSION is invalid.' );
adam_ui_smoke_assert( class_exists( 'ADAM_UI' ), 'ADAM_UI coordinator did not load.' );
adam_ui_smoke_assert( function_exists( 'adam_ui' ), 'adam_ui() API did not load.' );
adam_ui_smoke_assert( isset( $hooks['plugins_loaded'][10] ) && in_array( 'adam_ui', $hooks['plugins_loaded'][10], true ), 'WordPress bootstrap hook was not registered.' );
do_action( 'plugins_loaded' );
adam_ui_smoke_assert( adam_ui() instanceof ADAM_UI, 'Plugin could not initialize.' );
adam_ui_smoke_assert( array() === $translation_calls, 'Plugin bootstrap translated before init.' );
adam_ui_smoke_assert( array() === $loaded_textdomains, 'Plugin bootstrap loaded its text domain before init.' );

adam_ui_register_theme_component(
	'legacy-panel',
	array(
		'name'    => 'Legacy Panel',
		'presets' => array(
			'flat' => array( 'label' => 'Flat', 'tokens' => array() ),
		),
	)
);
adam_ui_register_component(
	'adam-bot',
	'early-chat',
	array(
		'name'    => 'Early Chat',
		'presets' => array(
			'compact' => array( 'label' => 'Compact', 'tokens' => array() ),
		),
	)
);
adam_ui_smoke_assert( array() === $translation_calls, 'Pre-init public component registration translated fallback labels.' );

add_action(
	'adam_ui_register_components',
	static function ( $registry ) {
		$registry->register_plugin_component(
			'adam-community',
			'team-card',
			array(
				'name'       => 'Team Card',
				'category'   => 'ADAM Comunidade',
				'preview_template' => '<article>Team</article>',
				'default_styles' => array( 'adam-team-card-padding' => '1rem' ),
				'presets'    => array(),
				'uses'       => array( 'card', 'button' ),
			)
		);
	}
);
do_action( 'init' );
$core_header = adam_ui()->get_theme_component_registry()->get( 'header' );
$team_card = adam_ui()->get_theme_component_registry()->get( 'adam-community--team-card' );
adam_ui_smoke_assert( $core_header && 'Header' === $core_header['label'], 'Core components were not registered on init.' );
adam_ui_smoke_assert( 1 === count( $loaded_textdomains ) && 'adam-ui' === $loaded_textdomains[0]['domain'], 'Text domain was not loaded exactly once on init.' );
adam_ui_smoke_assert( 'adam-ui/languages' === str_replace( '\\', '/', $loaded_textdomains[0]['path'] ), 'Text domain language path is invalid.' );
adam_ui_smoke_assert( $team_card && 'adam-community' === $team_card['owner'], 'Plugin component discovery failed.' );
adam_ui_smoke_assert( isset( adam_ui()->get_plugin_registry()->all()['adam-community'] ), 'Discovered component owner was not registered.' );
adam_ui_smoke_assert( null !== adam_ui()->get_asset_registry()->get_component_definition( 'adam-community--team-card' ), 'Plugin component assets were not registered centrally.' );

restore_error_handler();
echo "WordPress plugin smoke test passed.\n";
