<?php
/**
 * Central ADAM Interface asset registry.
 *
 * @package ADAM_Interface
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers shared assets once and tracks requested component families.
 */
final class ADAM_Interface_Asset_Registry {
	/**
	 * Registered component metadata.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private $components = array();

	/**
	 * Components requested on the current page.
	 *
	 * @var string[]
	 */
	private $loaded_components = array();

	/**
	 * Whether WordPress handles have been registered.
	 *
	 * @var bool
	 */
	private $registered = false;

	/** Creates the built-in component registry. */
	public function __construct() {
		$interactive = array( 'dropdown', 'loading', 'modal', 'confirmation' );
		$names       = array( 'card', 'button', 'forms', 'table', 'tabs', 'modal', 'notice', 'badge', 'breadcrumbs', 'empty-state', 'loading', 'pagination', 'toolbar', 'search', 'dropdown', 'confirmation', 'stat-card', 'section-header', 'admin-layout' );

		foreach ( $names as $name ) {
			$this->register_component(
				$name,
				array(
					'style_handle'  => 'adam-interface-utilities',
					'script_handle' => in_array( $name, $interactive, true ) ? 'adam-interface-components' : '',
				)
			);
		}
	}

	/**
	 * Registers a component family or future visual extension.
	 *
	 * Custom handles must be registered by the extension before enqueue time.
	 *
	 * @param string $name Component identifier.
	 * @param array  $args Registered style and script handles.
	 * @return bool
	 */
	public function register_component( $name, $args = array() ) {
		$name = sanitize_key( $name );

		if ( '' === $name ) {
			return false;
		}

		$args                      = wp_parse_args(
			$args,
			array(
				'style_handle'  => 'adam-interface-utilities',
				'script_handle' => '',
			)
		);
		$this->components[ $name ] = array(
			'style_handle'  => sanitize_key( $args['style_handle'] ),
			'script_handle' => sanitize_key( $args['script_handle'] ),
		);

		return true;
	}

	/** Registers WordPress asset handles without enqueueing them. */
	public function register_assets() {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;
		wp_register_style( 'adam-interface-variables', ADAM_INTERFACE_URL . 'assets/css/variables.css', array(), ADAM_INTERFACE_VERSION );
		wp_register_style( 'adam-interface-light', ADAM_INTERFACE_URL . 'assets/css/light.css', array( 'adam-interface-variables' ), ADAM_INTERFACE_VERSION );
		wp_register_style( 'adam-interface-dark', ADAM_INTERFACE_URL . 'assets/css/dark.css', array( 'adam-interface-variables' ), ADAM_INTERFACE_VERSION );
		wp_register_style( 'adam-interface', ADAM_INTERFACE_URL . 'assets/css/interface.css', array( 'adam-interface-light', 'adam-interface-dark' ), ADAM_INTERFACE_VERSION );
		wp_register_style( 'adam-interface-utility-primitives', ADAM_INTERFACE_URL . 'assets/css/utilities.css', array( 'adam-interface' ), ADAM_INTERFACE_VERSION );
		wp_register_style( 'adam-interface-utilities', ADAM_INTERFACE_URL . 'assets/css/components.css', array( 'adam-interface-utility-primitives' ), ADAM_INTERFACE_VERSION );
		wp_register_style( 'adam-interface-theme-switcher', ADAM_INTERFACE_URL . 'assets/css/theme-switcher.css', array( 'adam-interface' ), ADAM_INTERFACE_VERSION );
		wp_register_style( 'adam-interface-admin', ADAM_INTERFACE_URL . 'assets/css/admin.css', array( 'adam-interface-utilities' ), ADAM_INTERFACE_VERSION );
		wp_register_script( 'adam-interface', ADAM_INTERFACE_URL . 'assets/js/interface.js', array(), ADAM_INTERFACE_VERSION, false );
		wp_register_script( 'adam-interface-components', ADAM_INTERFACE_URL . 'assets/js/components.js', array( 'adam-interface' ), ADAM_INTERFACE_VERSION, true );
	}

	/** Enqueues only the theme foundation. */
	public function enqueue_core() {
		$this->register_assets();
		wp_enqueue_style( 'adam-interface' );
		wp_enqueue_script( 'adam-interface' );
	}

	/** Enqueues the compact public switcher stylesheet. */
	public function enqueue_switcher() {
		$this->enqueue_core();
		wp_enqueue_style( 'adam-interface-theme-switcher' );
	}

	/**
	 * Requests a shared component family.
	 *
	 * Components share one cacheable stylesheet; interactive families also
	 * request the single shared controller. WordPress prevents duplicate loads.
	 *
	 * @param string $name Component identifier.
	 * @return bool
	 */
	public function enqueue_component( $name ) {
		$name = sanitize_key( $name );

		if ( ! isset( $this->components[ $name ] ) ) {
			return false;
		}

		$this->enqueue_core();
		if ( '' !== $this->components[ $name ]['style_handle'] ) {
			wp_enqueue_style( $this->components[ $name ]['style_handle'] );
		}

		if ( '' !== $this->components[ $name ]['script_handle'] ) {
			wp_enqueue_script( $this->components[ $name ]['script_handle'] );
		}

		if ( ! in_array( $name, $this->loaded_components, true ) ) {
			$this->loaded_components[] = $name;
		}

		wp_localize_script(
			'adam-interface',
			'adamInterfaceAssetConfig',
			array( 'components' => $this->loaded_components )
		);

		return true;
	}

	/** Requests all components for legacy integrations. */
	public function enqueue_all_components() {
		foreach ( array_keys( $this->components ) as $name ) {
			$this->enqueue_component( $name );
		}
	}

	/** Enqueues the settings/diagnostics presentation. */
	public function enqueue_admin() {
		$this->enqueue_component( 'admin-layout' );
		wp_enqueue_style( 'adam-interface-admin' );
	}

	/**
	 * Returns component families requested on this page.
	 *
	 * @return string[]
	 */
	public function get_loaded_components() {
		return $this->loaded_components;
	}

	/**
	 * Returns every registered component family.
	 *
	 * @return string[]
	 */
	public function get_registered_components() {
		return array_keys( $this->components );
	}

	/**
	 * Returns a public asset URL by registry key.
	 *
	 * @param string $asset Asset key.
	 * @return string
	 */
	public function get_url( $asset ) {
		$assets = array(
			'variables'    => 'assets/css/variables.css',
			'light'        => 'assets/css/light.css',
			'dark'         => 'assets/css/dark.css',
			'interface'    => 'assets/css/interface.css',
			'utilities'    => 'assets/css/utilities.css',
			'components'   => 'assets/css/components.css',
			'theme'        => 'assets/js/interface.js',
			'interactions' => 'assets/js/components.js',
		);

		$asset = sanitize_key( $asset );

		return isset( $assets[ $asset ] ) ? ADAM_INTERFACE_URL . $assets[ $asset ] : '';
	}
}
