<?php
/**
 * Main plugin coordinator.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates the ADAM UI services.
 */
final class ADAM_UI {
	/**
	 * Plugin instance.
	 *
	 * @var ADAM_UI|null
	 */
	private static $instance = null;

	/**
	 * Settings service.
	 *
	 * @var ADAM_UI_Settings
	 */
	private $settings;

	/**
	 * Theme manager service.
	 *
	 * @var ADAM_UI_Theme_Manager
	 */
	private $theme_manager;
	/** @var ADAM_UI_Theme_Repository */
	private $theme_repository;
	/** @var ADAM_UI_Theme_Component_Registry */
	private $theme_components;

	/**
	 * Shared component renderer.
	 *
	 * @var ADAM_UI_Components
	 */
	private $components;

	/** @var ADAM_UI_Asset_Registry */
	private $assets;

	/** @var ADAM_UI_Plugin_Registry */
	private $plugins;

	/** @var ADAM_UI_Admin */
	private $admin;
	/** @var ADAM_UI_Theme_Editor */
	private $theme_editor;

	/**
	 * Returns the plugin singleton.
	 *
	 * @return ADAM_UI
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

	/** Registers a Theme Editor component through the stable ecosystem API. */
	public static function register_theme_component( $slug, $args ) {
		if ( is_array( $args ) && empty( $args['owner'] ) ) {
			$legacy_label = 'Legacy Integrations';
			if ( ! function_exists( 'did_action' ) || did_action( 'init' ) ) {
				$legacy_label = __( 'Legacy Integrations', 'adam-ui' );
			}
			$args['owner']      = 'legacy-integrations';
			$args['owner_name'] = $legacy_label;
			$args['category']   = $legacy_label;
		}
		return self::instance()->get_theme_component_registry()->register( $slug, $args );
	}

	/** Registers an isolated component owned by an ADAM plugin. */
	public static function register_component( $plugin_slug, $identifier, $args ) {
		return self::instance()->get_theme_component_registry()->register_plugin_component( $plugin_slug, $identifier, $args );
	}

	/**
	 * Creates and starts the frontend services.
	 */
	private function __construct() {
		$this->settings      = new ADAM_UI_Settings();
		$this->assets        = new ADAM_UI_Asset_Registry();
		$this->plugins       = new ADAM_UI_Plugin_Registry();
		$this->theme_components = new ADAM_UI_Theme_Component_Registry();
		$this->theme_repository = new ADAM_UI_Theme_Repository( $this->theme_components );
		$this->theme_manager = new ADAM_UI_Theme_Manager( $this->settings, $this->assets, $this->theme_repository );
		$this->components    = new ADAM_UI_Components();
		$this->admin         = new ADAM_UI_Admin( $this->settings, $this->theme_manager, $this->assets, $this->plugins );
		$this->theme_editor  = new ADAM_UI_Theme_Editor( $this->theme_repository, $this->assets, $this->theme_components );
		ADAM_UI_System_Page_Protection::instance()->register_hooks();

		add_action( 'adam_ui_theme_component_registered', array( $this, 'register_theme_component_assets' ), 5 );
		$this->settings->register_hooks();
		$this->theme_components->register_hooks();
		$this->theme_repository->register_hooks();
		$this->plugins->register_hooks();
		$this->theme_manager->init();
		$this->admin->register_hooks();
		$this->theme_editor->register_hooks();
	}

	/**
	 * Prevents cloning the singleton.
	 */
	private function __clone() {}

	/**
	 * Returns the settings service.
	 *
	 * @return ADAM_UI_Settings
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Returns the theme manager service.
	 *
	 * @return ADAM_UI_Theme_Manager
	 */
	public function get_theme_manager() {
		return $this->theme_manager;
	}

	/** @return ADAM_UI_Theme_Repository */
	public function get_theme_repository() { return $this->theme_repository; }

	/** @return ADAM_UI_Theme_Component_Registry */
	public function get_theme_component_registry() { return $this->theme_components; }

	/**
	 * Returns the shared component renderer.
	 *
	 * @return ADAM_UI_Components
	 */
	public function get_components() {
		return $this->components;
	}

	/** @return ADAM_UI_Asset_Registry */
	public function get_asset_registry() {
		return $this->assets;
	}

	/** @return ADAM_UI_Plugin_Registry */
	public function get_plugin_registry() {
		return $this->plugins;
	}

	/** Requests one shared component family. */
	public function enqueue_component( $component ) {
		return $this->assets->enqueue_component( $component );
	}

	/** Requests several shared or plugin-owned component families. */
	public function enqueue_components( $components ) {
		return $this->assets->enqueue_components( $components );
	}

	/** Adds a registered theme component to the central request-driven loader. */
	public function register_theme_component_assets( $component ) {
		if ( empty( $component['slug'] ) || 'adam-ui' === $component['owner'] ) {
			return;
		}
		$assets = isset( $component['assets'] ) && is_array( $component['assets'] ) ? $component['assets'] : array();
		$this->assets->register_component(
			$component['slug'],
			array(
				'style_handle'  => isset( $assets['style_handle'] ) ? $assets['style_handle'] : 'adam-ui-utilities',
				'script_handle' => isset( $assets['script_handle'] ) ? $assets['script_handle'] : '',
				'requires'      => isset( $component['uses'] ) ? $component['uses'] : array(),
				'owner'         => $component['owner'],
			)
		);
	}
}
