<?php
/** Phase 6 service contract smoke test. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'ADAM_UI_VERSION', '2.1.0' );
define( 'ADAM_UI_URL', 'https://example.test/adam-ui/' );

$test_options = array();
$test_meta = array();
$test_logged_in = false;
$test_admin = false;
$test_styles = array();
$test_scripts = array();
$test_localized = array();

function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_hex_color( $value ) { return preg_match('/^#[0-9a-f]{6}$/i',(string)$value) ? $value : null; }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function wp_unslash( $value ) { return $value; }
function sanitize_html_class( $value ) { return sanitize_key( $value ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, (array) $args ); }
function apply_filters( $hook, $value ) { return $value; }
function do_action() {}
function add_action() {}
function add_filter() {}
function is_admin() { global $test_admin; return $test_admin; }
function is_user_logged_in() { global $test_logged_in; return $test_logged_in; }
function get_current_user_id() { return 7; }
function get_option( $key, $default = false ) { global $test_options; return array_key_exists( $key, $test_options ) ? $test_options[ $key ] : $default; }
function update_option( $key, $value ) { global $test_options; $test_options[ $key ] = $value; return true; }
function get_user_meta( $user_id, $key ) { global $test_meta; return $test_meta[ $user_id ][ $key ] ?? ''; }
function update_user_meta( $user_id, $key, $value ) { global $test_meta; $test_meta[ $user_id ][ $key ] = $value; return true; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function wp_create_nonce() { return 'nonce'; }
function __( $value ) { return $value; }
function wp_style_is( $handle, $status ) { return 'ct-main-styles' === $handle && 'registered' === $status; }
function wp_register_style( $handle, $src = '', $dependencies = array() ) { global $test_styles; $test_styles['registered'][] = $handle; $test_styles['dependencies'][ $handle ] = $dependencies; }
function wp_enqueue_style( $handle ) { global $test_styles; $test_styles['enqueued'][] = $handle; }
function wp_register_script( $handle ) { global $test_scripts; $test_scripts['registered'][] = $handle; }
function wp_enqueue_script( $handle ) { global $test_scripts; $test_scripts['enqueued'][] = $handle; }
function wp_localize_script( $handle, $name, $data ) { global $test_localized; $test_localized[ $name ] = $data; }
function wp_add_inline_style() { return true; }

require dirname( __DIR__ ) . '/includes/class-settings.php';
require dirname( __DIR__ ) . '/includes/class-theme-repository.php';
require dirname( __DIR__ ) . '/includes/class-asset-registry.php';
require dirname( __DIR__ ) . '/includes/class-plugin-registry.php';
require dirname( __DIR__ ) . '/includes/class-theme-manager.php';

function assert_contract( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
		exit( 1 );
	}
}

$settings = new ADAM_UI_Settings();
$assets   = new ADAM_UI_Asset_Registry();
$repository = new ADAM_UI_Theme_Repository();
$themes   = new ADAM_UI_Theme_Manager( $settings, $assets, $repository );
$plugins  = new ADAM_UI_Plugin_Registry();

$repository->ensure_storage();
assert_contract( isset( $test_options[ ADAM_UI_Theme_Repository::OPTION_KEY ]['themes']['adam-night'] ), 'built-in Night preset is persisted' );
assert_contract( isset( $repository->schema()['adam-card-radius'] ), 'component token schema is available' );
assert_contract( false !== strpos( $repository->generated_css(), 'body.adam-theme-dark' ), 'saved tokens generate scoped runtime CSS' );
assert_contract( '#416900' === $repository->token( 'adam-btn-primary-bg', 'light' ), 'component token API returns the active Light value' );
assert_contract( isset( $repository->schema()['adam-header-bg'], $repository->schema()['adam-footer-bg'], $repository->schema()['adam-section-overlay-bg'], $repository->schema()['adam-btn-outline-hover-border'] ), 'component-oriented editor schema is complete' );

$stored = $test_options[ ADAM_UI_Theme_Repository::OPTION_KEY ];
$stored['themes']['adam-light']['tokens']['adam-header-bg'] = '#000';
$stored['themes']['adam-light']['tokens']['adam-footer-bg'] = 'rgb(255 255 255 / 85%)';
$stored['themes']['adam-light']['tokens']['adam-section-overlay-bg'] = 'hsl(120 20% 10% / 0.8)';
$stored['themes']['adam-light']['tokens']['adam-header-logo-bg'] = 'transparent';
$stored['themes']['adam-light']['tokens']['adam-card-bg'] = '#FFFFFF';
$test_options[ ADAM_UI_Theme_Repository::OPTION_KEY ] = $stored;
$repository = new ADAM_UI_Theme_Repository();
assert_contract( '#000' === $repository->token( 'adam-header-bg', 'light' ), 'three-digit HEX colours are accepted' );
assert_contract( 'rgb(255 255 255 / 85%)' === $repository->token( 'adam-footer-bg', 'light' ), 'modern RGB colours are accepted' );
assert_contract( 'hsl(120 20% 10% / 0.8)' === $repository->token( 'adam-section-overlay-bg', 'light' ), 'HSL alpha colours are accepted' );
assert_contract( 'transparent' === $repository->token( 'adam-header-logo-bg', 'light' ), 'transparent colours are accepted' );
assert_contract( '#FFFFFF' === $repository->token( 'adam-card-bg', 'light' ), 'pure white is accepted without palette restrictions' );

$test_options[ 'adam_' . 'inter' . 'face_settings' ] = array( 'default_theme' => 'dark' );
$settings->migrate_saved_settings();
assert_contract( 'dark' === $test_options[ ADAM_UI_Settings::OPTION_KEY ]['default_theme'], 'saved settings migrate to the ADAM UI option' );
$test_options = array();

assert_contract( 'light' === $themes->get_theme_mode(), 'website default initializes new browsers' );
assert_contract( 'website-default' === $themes->get_theme_source(), 'website default source reported' );

$test_logged_in = true;
$test_meta[7][ 'adam_' . 'inter' . 'face_theme' ] = 'dark';
assert_contract( 'light' === $themes->get_theme_mode(), 'server user meta does not override the browser preference' );
assert_contract( 'light' === $themes->get_fallback_theme_mode(), 'website default remains the browser fallback' );
assert_contract( 'localStorage' === $settings->get_storage_config()['adapter'], 'logged-in members use browser storage' );

$test_logged_in = false;
$test_options[ ADAM_UI_Settings::OPTION_KEY ] = array(
	'default_theme'          => 'light',
	'allow_visitor_switcher' => false,
	'allow_user_preferences' => true,
	'enable_system_mode'     => false,
	'enable_transitions'     => false,
);
assert_contract( 'light' === $themes->get_theme_mode(), 'website default used when system is disabled' );
assert_contract( 'light' === $themes->get_fallback_theme_mode(), 'disabled system falls back to website default' );
assert_contract( in_array( 'system', $themes->get_supported_modes(), true ), 'System mode remains available to every visitor' );
assert_contract( 'localStorage' === $settings->get_storage_config()['adapter'], 'visitor storage remains client-side' );

$assets->enqueue_core();
assert_contract( in_array( 'adam-ui', $test_styles['enqueued'], true ), 'core style enqueued' );
assert_contract( array( 'ct-main-styles' ) === $test_styles['dependencies']['adam-ui-variables'], 'ADAM styles load after Blocksy when its handle is registered' );
assert_contract( ! in_array( 'adam-ui-utilities', $test_styles['enqueued'], true ), 'component bundle omitted from core' );
$assets->enqueue_component( 'table' );
assert_contract( in_array( 'adam-ui-utilities', $test_styles['enqueued'], true ), 'component bundle requested centrally' );
assert_contract( ! in_array( 'adam-ui-components', $test_scripts['enqueued'] ?? array(), true ), 'static component does not load interaction JavaScript' );
$assets->enqueue_component( 'modal' );
assert_contract( in_array( 'adam-ui-components', $test_scripts['enqueued'], true ), 'interactive component loads one controller' );
assert_contract( array( 'table', 'modal' ) === $assets->get_loaded_components(), 'loaded component diagnostics are deterministic' );

$plugins->register( 'future-plugin', 'ADAM Future', array( 'version' => '1.0.0', 'requires_ui' => '9.0.0' ) );
assert_contract( 1 === count( $plugins->get_warnings() ), 'incompatible versions produce warnings' );

$test_admin = true;
$themes->enable_admin_theme();
$classes = $themes->add_admin_body_class( 'wp-admin adam-theme-light adam-theme-dark adam-transitions-enabled' );
assert_contract( 1 === preg_match_all( '/adam-theme-(light|dark)/', $classes ), 'admin receives exactly one theme class' );
assert_contract( false !== strpos( $classes, 'adam-transitions-disabled' ), 'transition setting reaches server body class' );

echo "PASS: Phase 6 production service contract.\n";
