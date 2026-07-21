<?php
/**
 * ADAM ecosystem plugin registry and compatibility checks.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tracks ADAM plugins without introducing hard dependencies between them.
 */
final class ADAM_UI_Plugin_Registry {
	/**
	 * Registered plugin metadata.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private $plugins = array();

	/** Registers automatic discovery after all plugins have loaded. */
	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'discover_active_plugins' ), 1 );
	}

	/**
	 * Discovers active plugins whose WordPress name begins with ADAM.
	 *
	 * Explicit registrations take precedence because they provide component and
	 * compatibility metadata that plugin headers cannot express.
	 */
	public function discover_active_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$active = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$catalog = get_plugins();

		foreach ( array_unique( $active ) as $file ) {
			if ( ! isset( $catalog[ $file ] ) || empty( $catalog[ $file ]['Name'] ) || ! preg_match( '/^ADAM\b/i', $catalog[ $file ]['Name'] ) ) {
				continue;
			}

			$slug = sanitize_key( dirname( $file ) );

			if ( '.' === dirname( $file ) ) {
				$slug = sanitize_key( basename( $file, '.php' ) );
			}

			$explicit_files = array_column( $this->plugins, 'plugin_file' );

			if ( 'adam-ui' === $slug || isset( $this->plugins[ $slug ] ) || in_array( $file, $explicit_files, true ) ) {
				continue;
			}

			$this->register(
				$slug,
				$catalog[ $file ]['Name'],
				array( 'version' => isset( $catalog[ $file ]['Version'] ) ? $catalog[ $file ]['Version'] : '' )
			);
		}
	}

	/**
	 * Registers or updates an ADAM plugin.
	 *
	 * @param string $slug Plugin identifier.
	 * @param string $name Human-readable name.
	 * @param array  $args Version and compatibility metadata.
	 * @return bool
	 */
	public function register( $slug, $name, $args = array() ) {
		$slug = sanitize_key( $slug );

		if ( '' === $slug || '' === trim( (string) $name ) ) {
			return false;
		}

		$args = wp_parse_args(
			$args,
			array(
				'version'            => '',
				'requires_ui' => '0.1.0',
				'components'         => array(),
				'plugin_file'        => '',
			)
		);

		$this->plugins[ $slug ] = array(
			'slug'               => $slug,
			'name'               => sanitize_text_field( (string) $name ),
			'version'            => sanitize_text_field( (string) $args['version'] ),
			'requires_ui' => sanitize_text_field( (string) $args['requires_ui'] ),
			'components'         => array_values( array_unique( array_map( 'sanitize_key', (array) $args['components'] ) ) ),
			'plugin_file'        => sanitize_text_field( (string) $args['plugin_file'] ),
		);

		/**
		 * Fires after an ADAM plugin registers with the shared UI.
		 *
		 * @param array<string, mixed> $plugin Registered plugin metadata.
		 */
		do_action( 'adam_ui_plugin_registered', $this->plugins[ $slug ] );

		return true;
	}

	/**
	 * Returns all registered ADAM plugins.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function all() {
		return $this->plugins;
	}

	/**
	 * Returns non-fatal compatibility warnings.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_warnings() {
		$warnings = array();

		foreach ( $this->plugins as $plugin ) {
			$required = $plugin['requires_ui'];

			if ( '' !== $required && version_compare( ADAM_UI_VERSION, $required, '<' ) ) {
				$warnings[] = array(
					'plugin'   => $plugin['slug'],
					'required' => $required,
					'current'  => ADAM_UI_VERSION,
					'message'  => sprintf(
						/* translators: 1: plugin name, 2: required version, 3: installed version. */
						__( '%1$s requires ADAM UI %2$s or newer; version %3$s is installed.', 'adam-ui' ),
						$plugin['name'],
						$required,
						ADAM_UI_VERSION
					),
				);
			}
		}

		return $warnings;
	}
}
