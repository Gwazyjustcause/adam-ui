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
	 * Shared component renderer.
	 *
	 * @var ADAM_Interface_Components
	 */
	private $components;

	/** @var ADAM_Interface_Asset_Registry */
	private $assets;

	/** @var ADAM_Interface_Plugin_Registry */
	private $plugins;

	/** @var ADAM_Interface_Admin */
	private $admin;

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
	 * Registers an ecosystem plugin through the stable static API.
	 *
	 * @return bool
	 */
	public static function register_plugin( $slug, $name, $args = array() ) {
		return self::instance()->get_plugin_registry()->register( $slug, $name, $args );
	}

	/**
	 * Creates and starts the frontend services.
	 */
	private function __construct() {
		$this->settings      = new ADAM_Interface_Settings();
		$this->assets        = new ADAM_Interface_Asset_Registry();
		$this->plugins       = new ADAM_Interface_Plugin_Registry();
		$this->theme_manager = new ADAM_Interface_Theme_Manager( $this->settings, $this->assets );
		$this->components    = new ADAM_Interface_Components();
		$this->admin         = new ADAM_Interface_Admin( $this->settings, $this->theme_manager, $this->assets, $this->plugins );

		$this->settings->register_hooks();
		$this->plugins->register_hooks();
		$this->theme_manager->init();
		$this->admin->register_hooks();
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

	/**
	 * Returns the shared component renderer.
	 *
	 * @return ADAM_Interface_Components
	 */
	public function get_components() {
		return $this->components;
	}

	/** @return ADAM_Interface_Asset_Registry */
	public function get_asset_registry() {
		return $this->assets;
	}

	/** @return ADAM_Interface_Plugin_Registry */
	public function get_plugin_registry() {
		return $this->plugins;
	}

	/** Requests one shared component family. */
	public function enqueue_component( $component ) {
		return $this->assets->enqueue_component( $component );
	}
}
