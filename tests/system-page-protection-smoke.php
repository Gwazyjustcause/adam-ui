<?php
/** Shared system-page protection contract smoke test. */

$root     = dirname( __DIR__ );
$service  = (string) file_get_contents( $root . '/includes/class-system-page-protection.php' );
$template = (string) file_get_contents( $root . '/templates/protected-system-page.php' );
$plugin   = (string) file_get_contents( $root . '/adam-ui.php' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};

foreach ( array( 'template_redirect', 'pre_get_posts', 'wp_sitemaps_posts_query_args', 'wp_robots', 'X-Robots-Tag', 'status_header( 403 )', "current_user_can( 'manage_options' )" ) as $contract ) {
	$assert( str_contains( $service, $contract ), "Missing protection contract: {$contract}." );
}

$assert( str_contains( $plugin, 'adam_ui_register_system_pages' ) && str_contains( $plugin, 'adam_ui_set_system_page_protected' ), 'Public system-page APIs are missing.' );
$assert( str_contains( $template, 'get_header' ) === false && str_contains( $template, 'Esta página não está disponível de momento.' ), 'The friendly template content is missing.' );
$assert( str_contains( $service, 'get_header();' ) && str_contains( $service, 'get_footer();' ), 'The protected response does not retain the site header and footer.' );

echo "System page protection smoke test passed.\n";
