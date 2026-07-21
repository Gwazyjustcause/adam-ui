<?php
/**
 * Main plugin coordinator.
 *
 * @package ADAM_Interface
 */

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates the ADAM Interface services.
 */
final class ADAM_Interface {
	/**
	 * Plugin instance.
	 *
	 * @var ADAM_Interface|null
	 */
	private static $instance = null;

	/**
	 * Settings service.
	 *
	 * @var ADAM_Interface_Settings
	 */
	private $settings;

	/**
	 * Theme manager service.
	 *
	 * @var ADAM_Interface_Theme_Manager
	 */
	private $theme_manager;

	/**
	 * Returns the plugin singleton.
	 *
	 * @return ADAM_Interface
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Creates and starts the frontend services.
	 */
	private function __construct() {
		$this->settings      = new ADAM_Interface_Settings();
		$this->theme_manager = new ADAM_Interface_Theme_Manager( $this->settings );

		$this->theme_manager->init();
	}

	/**
	 * Prevents cloning the singleton.
	 */
	private function __clone() {}

	/**
	 * Returns the settings service.
	 *
	 * @return ADAM_Interface_Settings
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Returns the theme manager service.
	 *
	 * @return ADAM_Interface_Theme_Manager
	 */
	public function get_theme_manager() {
		return $this->theme_manager;
	}
}
