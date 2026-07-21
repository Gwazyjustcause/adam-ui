<?php
/**
 * Minimal component renderer smoke test.
 *
 * Run with: php tests/components-smoke.php
 */

define( 'ABSPATH', __DIR__ );

function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $value ) { return filter_var( (string) $value, FILTER_SANITIZE_URL ); }
function wp_kses_post( $value ) { return strip_tags( (string) $value, '<a><span><strong><em><svg><path>' ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, (array) $args ); }
function sanitize_html_class( $value ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ); }
function wp_unique_id( $prefix = '' ) { static $id = 0; return $prefix . ++$id; }
function __( $value ) { return $value; }

require dirname( __DIR__ ) . '/includes/class-components.php';

$components = new ADAM_UI_Components();
$checks     = array(
	strpos( $components->card( 'Content', array( 'title' => 'Summary' ) ), 'adam-card-header' ) !== false,
	strpos( $components->notice( 'Saved', 'success' ), 'role="status"' ) !== false,
	strpos( $components->notice( 'Failed', 'error' ), 'role="alert"' ) !== false,
	strpos( $components->button( 'Delete', '', array( 'variant' => 'danger' ) ), 'adam-button-danger' ) !== false,
	strpos( $components->button( 'Open', '/admin' ), '<a' ) === 0,
	strpos( $components->stat_card( 'Members', '42' ), 'adam-stat-card__value' ) !== false,
	strpos( $components->empty_state( 'No results' ), 'adam-empty-state' ) !== false,
	strpos( $components->loading_indicator( 'Loading' ), 'adam-sr-only' ) !== false,
	strpos( $components->confirmation_dialog( 'Continue?' ), 'aria-labelledby' ) !== false,
	strpos( $components->button( 'Unsafe', 'javascript:alert(1)', array( 'attributes' => array( 'onclick' => 'alert(1)' ) ) ), 'onclick' ) === false,
);

if ( in_array( false, $checks, true ) ) {
	fwrite( STDERR, "FAIL: component renderer contract.\n" );
	exit( 1 );
}

echo "PASS: component renderer contract.\n";
