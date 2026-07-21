<?php
/**
 * Central theme manager.
 *
 * @package ADAM_Interface
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects, resolves, and exposes ADAM themes and their assets.
 */
final class ADAM_Interface_Theme_Manager {
	/**
	 * Light theme mode.
	 *
	 * @var string
	 */
	const MODE_LIGHT = 'light';

	/**
	 * Dark theme mode.
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
	 * @var ADAM_Interface_Settings
	 */
	private $settings;

	/**
	 * Tracks whether the switcher has already been rendered.
	 *
	 * @var bool
	 */
	private $switcher_rendered = false;

	/**
	 * Whether a plugin opted the current admin screen into ADAM theming.
	 *
	 * @var bool
	 */
	private $admin_theme_enabled = false;

	/**
	 * Constructor.
	 *
	 * @param ADAM_Interface_Settings $settings Settings service.
	 */
	public function __construct( ADAM_Interface_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Registers frontend hooks.
	 *
	 * WordPress admin remains opt-in so only ADAM-owned screens are affected.
	 *
	 * @return void
	 */
	public function init() {
		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
		add_action( 'wp_footer', array( $this, 'render_theme_switcher' ) );

		// The WordPress login screen does not run the normal frontend hooks.
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'login_body_class', array( $this, 'add_body_class' ) );
		add_action( 'login_footer', array( $this, 'render_theme_switcher' ) );
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

		$this->enqueue_assets();
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
		$theme_classes = array_map( array( $this, 'get_body_class' ), $this->get_resolved_themes() );
		$class_list    = array_diff( $class_list, $theme_classes, array( '' ) );
		$class_list[]  = $this->get_body_class( $this->get_resolved_theme() );

		return implode( ' ', array_values( array_unique( $class_list ) ) );
	}

	/**
	 * Returns the public handle for the reusable utility stylesheet.
	 *
	 * @return string
	 */
	public function get_utility_style_handle() {
		return 'adam-interface-utilities';
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
	 * Visitor preferences are restored from localStorage in interface.js because
	 * localStorage is not available to PHP.
	 *
	 * @return string
	 */
	public function get_theme_mode() {
		$mode = $this->settings->get_default_theme_mode( self::MODE_SYSTEM );

		/**
		 * Filters the current server-side theme mode.
		 *
		 * @param string                       $mode    Current mode.
		 * @param ADAM_Interface_Theme_Manager $manager Theme manager.
		 */
		$mode = (string) apply_filters( 'adam_interface_theme_mode', $mode, $this );

		return $this->is_supported_mode( $mode ) ? $mode : self::MODE_SYSTEM;
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
		if ( ! in_array( $theme, $this->get_resolved_themes(), true ) ) {
			$theme = $this->get_resolved_theme( $theme );
		}

		return 'adam-theme-' . sanitize_html_class( $theme );
	}

	/**
	 * Adds the initial resolved theme class to the frontend body.
	 *
	 * @param string[] $classes Existing body classes.
	 * @return string[]
	 */
	public function add_body_class( $classes ) {
		$theme_classes = array_map( array( $this, 'get_body_class' ), $this->get_resolved_themes() );
		$classes       = array_diff( $classes, $theme_classes );
		$classes[] = $this->get_body_class( $this->get_resolved_theme() );

		return array_values( array_unique( $classes ) );
	}

	/**
	 * Enqueues the shared tokens, themes, base styles, and theme controller.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'adam-interface-variables',
			ADAM_INTERFACE_URL . 'assets/css/variables.css',
			array(),
			ADAM_INTERFACE_VERSION
		);

		wp_enqueue_style(
			'adam-interface-light',
			ADAM_INTERFACE_URL . 'assets/css/light.css',
			array( 'adam-interface-variables' ),
			ADAM_INTERFACE_VERSION
		);

		wp_enqueue_style(
			'adam-interface-dark',
			ADAM_INTERFACE_URL . 'assets/css/dark.css',
			array( 'adam-interface-variables' ),
			ADAM_INTERFACE_VERSION
		);

		wp_enqueue_style(
			'adam-interface',
			ADAM_INTERFACE_URL . 'assets/css/interface.css',
			array( 'adam-interface-light', 'adam-interface-dark' ),
			ADAM_INTERFACE_VERSION
		);

		wp_enqueue_style(
			$this->get_utility_style_handle(),
			ADAM_INTERFACE_URL . 'assets/css/utilities.css',
			array( 'adam-interface' ),
			ADAM_INTERFACE_VERSION
		);

		wp_enqueue_style(
			'adam-interface-theme-switcher',
			ADAM_INTERFACE_URL . 'assets/css/theme-switcher.css',
			array( 'adam-interface-utilities' ),
			ADAM_INTERFACE_VERSION
		);

		wp_enqueue_script(
			'adam-interface',
			ADAM_INTERFACE_URL . 'assets/js/interface.js',
			array(),
			ADAM_INTERFACE_VERSION,
			false
		);

		wp_enqueue_script(
			'adam-interface-components',
			ADAM_INTERFACE_URL . 'assets/js/components.js',
			array( 'adam-interface' ),
			ADAM_INTERFACE_VERSION,
			true
		);

		wp_localize_script(
			'adam-interface',
			'adamInterfaceConfig',
			$this->get_script_config()
		);
	}

	/**
	 * Renders the public theme selector.
	 *
	 * The control is deliberately a native select for keyboard and assistive
	 * technology support without requiring a custom interaction model.
	 *
	 * @return void
	 */
	public function render_theme_switcher() {
		if ( $this->switcher_rendered ) {
			return;
		}

		$this->switcher_rendered = true;
		$current_mode            = $this->get_theme_mode();
		?>
		<div class="adam-theme-switcher adam-interface" data-adam-theme-switcher>
			<label class="adam-theme-switcher__label" for="adam-theme-select">
				<?php echo esc_html__( 'Tema', 'adam-interface' ); ?>
			</label>
			<select
				class="adam-theme-switcher__select"
				id="adam-theme-select"
				data-adam-theme-select
			>
				<option value="<?php echo esc_attr( self::MODE_LIGHT ); ?>" <?php selected( $current_mode, self::MODE_LIGHT ); ?>>
					<?php echo esc_html__( 'Claro', 'adam-interface' ); ?>
				</option>
				<option value="<?php echo esc_attr( self::MODE_DARK ); ?>" <?php selected( $current_mode, self::MODE_DARK ); ?>>
					<?php echo esc_html__( 'Escuro', 'adam-interface' ); ?>
				</option>
				<option value="<?php echo esc_attr( self::MODE_SYSTEM ); ?>" <?php selected( $current_mode, self::MODE_SYSTEM ); ?>>
					<?php echo esc_html__( 'Sistema', 'adam-interface' ); ?>
				</option>
			</select>
			<noscript>
				<span class="adam-theme-switcher__notice">
					<?php echo esc_html__( 'Ative o JavaScript para alterar o tema.', 'adam-interface' ); ?>
				</span>
			</noscript>
		</div>
		<?php
	}

	/**
	 * Returns the client configuration generated from the central registry.
	 *
	 * @return array<string, mixed>
	 */
	private function get_script_config() {
		$class_map = array();

		foreach ( $this->get_resolved_themes() as $theme ) {
			$class_map[ $theme ] = $this->get_body_class( $theme );
		}

		return array(
			'mode'           => $this->get_theme_mode(),
			'modes'          => $this->get_supported_modes(),
			'resolvedThemes' => $this->get_resolved_themes(),
			'classMap'       => $class_map,
			'systemMode'     => self::MODE_SYSTEM,
			'systemQuery'    => '(prefers-color-scheme: dark)',
			'systemDark'     => self::MODE_DARK,
			'systemFallback' => $this->get_resolved_theme( self::MODE_SYSTEM ),
			'storage'        => $this->settings->get_storage_config(),
		);
	}
}
