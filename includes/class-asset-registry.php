<?php
/**
 * Central ADAM UI asset registry.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers shared assets once and tracks requested component families.
 */
final class ADAM_UI_Asset_Registry {
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
					'style_handle'  => 'adam-ui-utilities',
					'script_handle' => in_array( $name, $interactive, true ) ? 'adam-ui-components' : '',
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
				'style_handle'  => 'adam-ui-utilities',
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
		wp_register_style( 'adam-ui-variables', ADAM_UI_URL . 'assets/css/variables.css', array(), ADAM_UI_VERSION );
		wp_register_style( 'adam-ui-light', ADAM_UI_URL . 'assets/css/light.css', array( 'adam-ui-variables' ), ADAM_UI_VERSION );
		wp_register_style( 'adam-ui-dark', ADAM_UI_URL . 'assets/css/dark.css', array( 'adam-ui-variables' ), ADAM_UI_VERSION );
		wp_register_style( 'adam-ui', ADAM_UI_URL . 'assets/css/ui.css', array( 'adam-ui-light', 'adam-ui-dark' ), ADAM_UI_VERSION );
		wp_register_style( 'adam-ui-utility-primitives', ADAM_UI_URL . 'assets/css/utilities.css', array( 'adam-ui' ), ADAM_UI_VERSION );
		wp_register_style( 'adam-ui-utilities', ADAM_UI_URL . 'assets/css/components.css', array( 'adam-ui-utility-primitives' ), ADAM_UI_VERSION );
		wp_register_style( 'adam-ui-theme-switcher', ADAM_UI_URL . 'assets/css/theme-switcher.css', array( 'adam-ui' ), ADAM_UI_VERSION );
		wp_register_style( 'adam-ui-admin', ADAM_UI_URL . 'assets/css/admin.css', array( 'adam-ui-utilities' ), ADAM_UI_VERSION );
		wp_register_style( 'adam-ui-inspector', ADAM_UI_URL . 'assets/css/inspector.css', array( 'adam-ui' ), ADAM_UI_VERSION );
		wp_register_script( 'adam-ui', ADAM_UI_URL . 'assets/js/ui.js', array(), ADAM_UI_VERSION, false );
		wp_register_script( 'adam-ui-components', ADAM_UI_URL . 'assets/js/components.js', array( 'adam-ui' ), ADAM_UI_VERSION, true );
		wp_register_script( 'adam-ui-inspector', ADAM_UI_URL . 'assets/js/inspector.js', array( 'adam-ui' ), ADAM_UI_VERSION, true );
	}

	/** Enqueues only the theme foundation. */
	public function enqueue_core() {
		$this->register_assets();
		wp_enqueue_style( 'adam-ui' );
		wp_enqueue_script( 'adam-ui' );
	}

	/** Enqueues the compact public switcher stylesheet. */
	public function enqueue_switcher() {
		$this->enqueue_core();
		wp_enqueue_style( 'adam-ui-theme-switcher' );
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
			'adam-ui',
			'adamUIAssetConfig',
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
		wp_enqueue_style( 'adam-ui-admin' );
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
			'ui'           => 'assets/css/ui.css',
			'utilities'    => 'assets/css/utilities.css',
			'components'   => 'assets/css/components.css',
			'theme'        => 'assets/js/ui.js',
			'interactions' => 'assets/js/components.js',
		);

		$asset = sanitize_key( $asset );

		return isset( $assets[ $asset ] ) ? ADAM_UI_URL . $assets[ $asset ] : '';
	}
}
