<?php
/**
 * Central theme manager.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects, resolves, and exposes ADAM themes and their assets.
 */
final class ADAM_UI_Theme_Manager {
	/**
	 * Light theme mode.
	 *
	 * @var string
	 */
	const MODE_LIGHT = 'light';

	/**
	 * Night Theme mode. The stable API value remains "dark" for compatibility.
	 *
	 * @var string
	 */
	const MODE_DARK = 'dark';

	/**
	 * System theme mode.
	 *
	 * @var string
	 */
	const MODE_SYSTEM = 'system';

	/**
	 * Settings service.
	 *
	 * @var ADAM_UI_Settings
	 */
	private $settings;

	/**
	 * Central asset registry.
	 *
	 * @var ADAM_UI_Asset_Registry
	 */
	private $assets;
	/** @var ADAM_UI_Theme_Repository */
	private $repository;

	/** @var ADAM_UI_Theme_Switcher */
	private $switcher;

	/**
	 * Whether the public script configuration has been attached.
	 *
	 * @var bool
	 */
	private $script_configured = false;

	/**
	 * Whether a plugin opted the current admin screen into ADAM theming.
	 *
	 * @var bool
	 */
	private $admin_theme_enabled = false;

	/**
	 * Constructor.
	 *
	 * @param ADAM_UI_Settings       $settings Settings service.
	 * @param ADAM_UI_Asset_Registry    $assets     Asset registry.
	 * @param ADAM_UI_Theme_Repository $repository Optional theme repository.
	 */
	public function __construct( ADAM_UI_Settings $settings, ADAM_UI_Asset_Registry $assets, ADAM_UI_Theme_Repository $repository = null ) {
		$this->settings = $settings;
		$this->assets   = $assets;
		$this->repository = $repository ? $repository : new ADAM_UI_Theme_Repository();
		$this->switcher = new ADAM_UI_Theme_Switcher( $this->settings, $this->assets, $this );
	}

	/**
	 * Registers frontend hooks.
	 *
	 * WordPress admin remains opt-in so only ADAM-owned screens are affected.
	 *
	 * @return void
	 */
	public function init() {
		$this->switcher->register_hooks();

		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_core_assets' ), 100 );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// The WordPress login screen does not run the normal frontend hooks.
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_core_assets' ) );
		add_filter( 'login_body_class', array( $this, 'add_body_class' ) );

	}

	/**
	 * Enables the theme system for a plugin-owned WordPress admin screen.
	 *
	 * This is intentionally opt-in. Calling plugins remain responsible for
	 * limiting the call to their own screens.
	 *
	 * @return void
	 */
	public function enable_admin_theme() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! $this->admin_theme_enabled ) {
			add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
			$this->admin_theme_enabled = true;
		}

		$this->enqueue_core_assets();
	}

	/**
	 * Adds one resolved ADAM theme class to the WordPress admin body.
	 *
	 * @param string $classes Space-separated admin body classes.
	 * @return string
	 */
	public function add_admin_body_class( $classes ) {
		$class_list    = preg_split( '/\s+/', trim( (string) $classes ) );
		$class_list    = is_array( $class_list ) ? $class_list : array();
		$class_list    = array_diff( $class_list, array( 'adam-theme-light', 'adam-theme-dark', 'adam-transitions-enabled', 'adam-transitions-disabled', '' ) );
		if ( self::MODE_DARK === $this->get_resolved_theme() ) { $class_list[] = $this->get_body_class( self::MODE_DARK ); }
		$class_list[]  = $this->get_transition_class();

		return implode( ' ', array_values( array_unique( $class_list ) ) );
	}

	/**
	 * Returns the public handle for the reusable utility stylesheet.
	 *
	 * @return string
	 */
	public function get_utility_style_handle() {
		return 'adam-ui-utilities';
	}

	/**
	 * Returns all accepted preference modes.
	 *
	 * @return string[]
	 */
	public function get_supported_modes() {
		return array(
			self::MODE_LIGHT,
			self::MODE_DARK,
			self::MODE_SYSTEM,
		);
	}

	/**
	 * Returns themes that can be applied to the document.
	 *
	 * @return string[]
	 */
	public function get_resolved_themes() {
		return array(
			self::MODE_LIGHT,
			self::MODE_DARK,
		);
	}

	/**
	 * Returns the configured theme mode.
	 *
	 * Browser preferences are restored from localStorage in ui.js because
	 * localStorage is not available to PHP.
	 *
	 * @return string
	 */
	public function get_theme_mode() {
		$mode = $this->settings->get_default_theme_mode( self::MODE_LIGHT );

		/**
		 * Filters the current server-side theme mode.
		 *
		 * @param string                       $mode    Current mode.
		 * @param ADAM_UI_Theme_Manager $manager Theme manager.
		 */
		$mode = (string) apply_filters( 'adam_ui_theme_mode', $mode, $this );

		$fallback = $this->settings->get_default_theme_mode( self::MODE_LIGHT );

		return $this->is_supported_mode( $mode ) ? $mode : $fallback;
	}

	/** Returns the System-or-website fallback used after clearing a preference. */
	public function get_fallback_theme_mode() {
		return $this->settings->get_default_theme_mode( self::MODE_LIGHT );
	}

	/** Returns whether the active mode came from user, system, or website default. */
	public function get_theme_source() {
		return 'website-default';
	}

	/**
	 * Resolves a mode to a concrete theme.
	 *
	 * @param string|null $mode Optional mode. Defaults to the current mode.
	 * @return string
	 */
	public function get_resolved_theme( $mode = null ) {
		$mode = null === $mode ? $this->get_theme_mode() : (string) $mode;

		if ( in_array( $mode, $this->get_resolved_themes(), true ) ) {
			return $mode;
		}

		$fallback = $this->settings->get_system_fallback( self::MODE_LIGHT );

		return in_array( $fallback, $this->get_resolved_themes(), true )
			? $fallback
			: self::MODE_LIGHT;
	}

	/**
	 * Checks whether a preference mode is valid.
	 *
	 * @param string $mode Theme mode.
	 * @return bool
	 */
	public function is_supported_mode( $mode ) {
		return in_array( $mode, $this->get_supported_modes(), true );
	}

	/**
	 * Returns the body class for a resolved theme.
	 *
	 * @param string $theme Resolved theme.
	 * @return string
	 */
	public function get_body_class( $theme ) {
		return self::MODE_DARK === $this->get_resolved_theme( $theme ) ? 'adam-theme-dark' : '';
	}

	/**
	 * Adds the initial resolved theme class to the frontend body.
	 *
	 * @param string[] $classes Existing body classes.
	 * @return string[]
	 */
	public function add_body_class( $classes ) {
		$classes       = array_diff( $classes, array( 'adam-theme-light', 'adam-theme-dark', 'adam-transitions-enabled', 'adam-transitions-disabled' ) );
		if ( self::MODE_DARK === $this->get_resolved_theme() ) { $classes[] = 'adam-theme-dark'; }
		$classes[]     = $this->get_transition_class();

		return array_values( array_unique( $classes ) );
	}

	/**
	 * Enqueues the shared tokens, themes, base styles, and theme controller.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$this->enqueue_core_assets();
		$this->assets->enqueue_all_components();
	}

	/** Enqueues the minimal global theme foundation. */
	public function enqueue_core_assets() {
		$this->assets->enqueue_core();
		wp_add_inline_style( 'adam-ui', $this->repository->generated_css() );

		if ( $this->settings->is_theme_switcher_enabled() ) {
			$this->assets->enqueue_switcher();
		}
		if ( $this->settings->is_enabled( 'enable_inspector' ) && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			wp_enqueue_style( 'adam-ui-inspector' );
			wp_enqueue_script( 'adam-ui-inspector' );
		}

		if ( ! $this->script_configured ) {
			wp_localize_script( 'adam-ui', 'adamUIConfig', $this->get_script_config() );
			$this->script_configured = true;
		}
	}

	/**
	 * Returns a reusable Theme Switcher instance for plugins and templates.
	 *
	 * @param array $args Component rendering arguments.
	 * @return string
	 */
	public function get_theme_switcher_markup( $args = array() ) {
		return $this->switcher->render( $args );
	}

	/** Returns the reusable Theme Switcher service. */
	public function get_theme_switcher() {
		return $this->switcher;
	}

	/**
	 * Returns the client configuration generated from the central registry.
	 *
	 * @return array<string, mixed>
	 */
	public function get_script_config() {
		$class_map = array( self::MODE_DARK => 'adam-theme-dark' );

		return array(
			'mode'           => $this->get_theme_mode(),
			'fallbackMode'   => $this->get_fallback_theme_mode(),
			'modes'          => $this->get_supported_modes(),
			'resolvedThemes' => $this->get_resolved_themes(),
			'classMap'       => $class_map,
			'systemMode'     => self::MODE_SYSTEM,
			'systemQuery'    => '(prefers-color-scheme: dark)',
			'systemDark'     => self::MODE_DARK,
			'systemFallback' => $this->get_resolved_theme( self::MODE_SYSTEM ),
			'storage'        => $this->settings->get_storage_config(),
			'themeSource'    => $this->get_theme_source(),
			'transitions'    => $this->settings->is_enabled( 'enable_transitions' ),
			'components'     => $this->assets->get_loaded_components(),
			'presets'        => array( 'dark' => $this->repository->active_id( 'dark' ) ),
			'tokens'         => array( 'dark' => $this->repository->tokens( 'dark' ) ),
		);
	}

	/** Returns the server-rendered transition preference class. */
	private function get_transition_class() {
		return $this->settings->is_enabled( 'enable_transitions' ) ? 'adam-transitions-enabled' : 'adam-transitions-disabled';
	}
}
