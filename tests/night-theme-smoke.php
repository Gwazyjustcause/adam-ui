<?php
/**
 * Night Theme palette and image-integrity contract.
 *
 * Run with: php tests/night-theme-smoke.php
 *
 * @package ADAM_UI
 */

$root       = dirname( __DIR__ );
$light_css  = file_get_contents( $root . '/assets/css/light.css' );
$night_css  = file_get_contents( $root . '/assets/css/dark.css' );
$all_css    = implode( "\n", array_map( 'file_get_contents', glob( $root . '/assets/css/*.css' ) ) );
$components = file_get_contents( $root . '/assets/css/components.css' );

function adam_ui_night_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
		exit( 1 );
	}
}

function adam_ui_css_hex_token( $css, $token ) {
	$pattern = '/--' . preg_quote( $token, '/' ) . ':\s*(#[0-9a-f]{6})\s*;/i';
	return preg_match( $pattern, $css, $matches ) ? strtolower( $matches[1] ) : '';
}

function adam_ui_relative_luminance( $hex ) {
	$channels = array_map(
		static function ( $offset ) use ( $hex ) {
			$value = hexdec( substr( $hex, $offset, 2 ) ) / 255;
			return $value <= 0.03928 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
		},
		array( 1, 3, 5 )
	);

	return ( 0.2126 * $channels[0] ) + ( 0.7152 * $channels[1] ) + ( 0.0722 * $channels[2] );
}

function adam_ui_contrast_ratio( $foreground, $background ) {
	$first  = adam_ui_relative_luminance( $foreground );
	$second = adam_ui_relative_luminance( $background );
	return ( max( $first, $second ) + 0.05 ) / ( min( $first, $second ) + 0.05 );
}

$section_roles = array( 'base', 'muted', 'soft', 'pale', 'feature', 'accent', 'deep' );
$night_values  = array();

foreach ( $section_roles as $role ) {
	$token = 'adam-section-' . $role;
	adam_ui_night_assert( false !== strpos( $light_css, '--' . $token . ':' ), 'Light Theme is missing ' . $token . '.' );
	adam_ui_night_assert( false !== strpos( $night_css, '--' . $token . ':' ), 'Night Theme is missing ' . $token . '.' );
	$night_values[] = adam_ui_css_hex_token( $night_css, $token );
}

adam_ui_night_assert( count( $section_roles ) === count( array_unique( $night_values ) ), 'Night section roles must use visibly distinct palette values.' );

foreach ( array( 'feature', 'soft', 'neutral' ) as $gradient ) {
	adam_ui_night_assert( false !== strpos( $night_css, '--adam-section-gradient-' . $gradient . ': linear-gradient(' ), 'Night Theme is missing the ' . $gradient . ' gradient.' );
	adam_ui_night_assert( false !== strpos( $light_css, '--adam-section-gradient-' . $gradient . ': linear-gradient(' ), 'Light Theme is missing the ' . $gradient . ' gradient.' );
}

$contrast_pairs = array(
	array( 'adam-on-section-base', 'adam-section-base' ),
	array( 'adam-on-section-soft', 'adam-section-soft' ),
	array( 'adam-on-section-soft', 'adam-section-pale' ),
	array( 'adam-on-section-feature', 'adam-section-feature' ),
	array( 'adam-on-section-accent', 'adam-section-accent' ),
	array( 'adam-on-section-deep', 'adam-section-deep' ),
);

foreach ( $contrast_pairs as $pair ) {
	$foreground = adam_ui_css_hex_token( $night_css, $pair[0] );
	$background = adam_ui_css_hex_token( $night_css, $pair[1] );
	adam_ui_night_assert( $foreground && $background, 'Missing contrast tokens for ' . implode( ' / ', $pair ) . '.' );
	adam_ui_night_assert( adam_ui_contrast_ratio( $foreground, $background ) >= 4.5, 'Night Theme contrast is below WCAG AA for ' . implode( ' / ', $pair ) . '.' );
}

adam_ui_night_assert( 0 === preg_match( '/(?:img|picture)[^{]*\{[^}]*(?:filter|mix-blend-mode|opacity)\s*:/is', $all_css ), 'Theme CSS must not alter photographs or graphics.' );
adam_ui_night_assert( false !== strpos( $night_css, '--theme-palette-color-8: var(--adam-section-base);' ), 'Website palette slots must inherit the Night Theme section system.' );
adam_ui_night_assert( false !== strpos( $components, '.adam-section--gradient-feature' ), 'Shared semantic section utilities are missing.' );

echo "PASS: Night Theme design contract.\n";
