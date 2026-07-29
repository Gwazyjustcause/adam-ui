<?php
/**
 * Verifies that every ADAM UI admin registration supplies string titles even
 * when a translation filter returns an invalid value.
 */

define( 'ABSPATH', __DIR__ . '/' );

$registered_titles = array();

function __( $text, $domain = 'default' ) {
	return null;
}

function add_menu_page( $page_title, $menu_title ) {
	global $registered_titles;
	$registered_titles[] = array( $page_title, $menu_title );
}

function add_submenu_page( $parent_slug, $page_title, $menu_title ) {
	global $registered_titles;
	$registered_titles[] = array( $page_title, $menu_title );
}

require dirname( __DIR__ ) . '/includes/class-admin.php';
require dirname( __DIR__ ) . '/includes/class-theme-editor.php';

$admin = new ADAM_UI_Admin( null, null, null, null );
$admin->register_menu();

$editor = new ADAM_UI_Theme_Editor( null, null, new stdClass() );
$editor->register_menu();

foreach ( $registered_titles as $titles ) {
	foreach ( $titles as $title ) {
		if ( ! is_string( $title ) || '' === $title ) {
			fwrite( STDERR, "FAIL: ADAM UI registered a null or empty admin title.\n" );
			exit( 1 );
		}
	}
}

if ( 4 !== count( $registered_titles ) ) {
	fwrite( STDERR, "FAIL: Expected four ADAM UI admin page registrations.\n" );
	exit( 1 );
}

echo "PASS: ADAM UI admin titles are always non-empty strings.\n";
