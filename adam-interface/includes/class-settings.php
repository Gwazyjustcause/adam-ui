<?php
/**
 * Theme foundation settings.
 *
 * This service intentionally has no admin UI or database persistence in Phase 1.
 *
 * @package ADAM_Interface
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides filterable defaults for the theme manager.
 */
final class ADAM_Interface_Settings {
	/**
	 * Returns the default theme mode.
	 *
	 * @param string $default Default supplied by the theme manager.
	 * @return string
	 */
	public function get_default_theme_mode( $default ) {
		/**
		 * Filters the default theme mode.
		 *
		 * @param string $mode Default mode.
		 */
		return (string) apply_filters( 'adam_interface_default_theme_mode', $default );
	}

	/**
	 * Returns the server fallback used when a system preference is unavailable.
	 *
	 * @param string $default Default supplied by the theme manager.
	 * @return string
	 */
	public function get_system_fallback( $default ) {
		/**
		 * Filters the fallback for system theme mode.
		 *
		 * @param string $theme Fallback resolved theme.
		 */
		return (string) apply_filters( 'adam_interface_system_fallback', $default );
	}

	/**
	 * Returns browser theme storage configuration.
	 *
	 * The filter allows a future account-backed adapter to replace localStorage
	 * without changing the public JavaScript API.
	 *
	 * @return array<string, string>
	 */
	public function get_storage_config() {
		$config = array(
			'adapter' => 'localStorage',
			'key'     => 'adam-theme',
		);

		/**
		 * Filters the browser storage configuration.
		 *
		 * @param array<string, string> $config Storage adapter and key.
		 */
		return (array) apply_filters( 'adam_interface_theme_storage_config', $config );
	}
}
