<?php
/**
 * Reusable Theme Switcher component.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Owns Theme Switcher markup and portable WordPress placement adapters.
 */
final class ADAM_UI_Theme_Switcher {
	/** @var ADAM_UI_Settings */
	private $settings;

	/** @var ADAM_UI_Asset_Registry */
	private $assets;

	/** @var ADAM_UI_Theme_Manager */
	private $themes;

	/** @var int */
	private static $instance_count = 0;

	/**
	 * Constructor.
	 *
	 * @param ADAM_UI_Settings       $settings Settings service.
	 * @param ADAM_UI_Asset_Registry $assets   Asset registry.
	 * @param ADAM_UI_Theme_Manager  $themes   Theme manager.
	 */
	public function __construct( ADAM_UI_Settings $settings, ADAM_UI_Asset_Registry $assets, ADAM_UI_Theme_Manager $themes ) {
		$this->settings = $settings;
		$this->assets   = $assets;
		$this->themes   = $themes;
	}

	/** Registers portable placement integrations. */
	public function register_hooks() {
		add_action( 'init', array( $this, 'register_content_integrations' ), 20 );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
	}

	/** Registers the shortcode and dynamic Gutenberg block. */
	public function register_content_integrations() {
		if ( function_exists( 'add_shortcode' ) ) {
			add_shortcode( 'adam_theme_switcher', array( $this, 'render_shortcode' ) );
		}

		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$this->assets->register_assets();

		wp_register_script(
			'adam-ui-theme-switcher-block',
			ADAM_UI_URL . 'assets/js/theme-switcher-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components' ),
			ADAM_UI_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'adam-ui-theme-switcher-block', 'adam-ui', ADAM_UI_PATH . 'languages' );
		}

		register_block_type(
			'adam-ui/theme-switcher',
			array(
				'api_version'     => 3,
				'editor_script'   => 'adam-ui-theme-switcher-block',
				'editor_style'    => 'adam-ui-theme-switcher-editor',
				'style'           => 'adam-ui-theme-switcher',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'style' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * Adds the ADAM UI block inserter category.
	 *
	 * @param array $categories     Existing categories.
	 * @param mixed $editor_context Current editor context.
	 * @return array
	 */
	public function register_block_category( $categories, $editor_context = null ) {
		foreach ( (array) $categories as $category ) {
			if ( isset( $category['slug'] ) && 'adam-ui' === $category['slug'] ) {
				return $categories;
			}
		}

		$categories[] = array(
			'slug'  => 'adam-ui',
			'title' => __( 'ADAM UI', 'adam-ui' ),
			'icon'  => 'art',
		);

		return $categories;
	}

	/** Registers the standard WordPress widget adapter. */
	public function register_widget() {
		if ( ! class_exists( 'WP_Widget' ) || ! function_exists( 'register_widget' ) ) {
			return;
		}

		require_once ADAM_UI_PATH . 'includes/class-theme-switcher-widget.php';
		register_widget( 'ADAM_UI_Theme_Switcher_Widget' );
	}

	/**
	 * Renders the shortcode.
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $attributes = array() ) {
		if ( 'shortcode' !== $this->settings->get_theme_switcher_placement() ) {
			return '';
		}

		$attributes = shortcode_atts(
			array(
				'style' => '',
				'class' => '',
			),
			(array) $attributes,
			'adam_theme_switcher'
		);

		return $this->render(
			array(
				'style'   => $attributes['style'],
				'class'   => $attributes['class'],
				'context' => 'shortcode',
			)
		);
	}

	/**
	 * Renders the dynamic block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes = array() ) {
		if ( 'block' !== $this->settings->get_theme_switcher_placement() ) {
			return '';
		}

		return $this->render(
			array(
				'style'   => isset( $attributes['style'] ) ? $attributes['style'] : '',
				'context' => 'block',
			)
		);
	}

	/**
	 * Returns one accessible Theme Switcher instance.
	 *
	 * @param array $args Style, context, position, and extra class.
	 * @return string
	 */
	public function render( $args = array() ) {
		if ( ! $this->settings->is_theme_switcher_enabled() ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'style'    => '',
				'context'  => 'component',
				'position' => '',
				'class'    => '',
			)
		);

		$style = sanitize_key( $args['style'] );
		if ( ! in_array( $style, $this->settings->get_theme_switcher_styles(), true ) ) {
			$style = $this->settings->get_theme_switcher_style();
		}

		$context = sanitize_key( $args['context'] );
		$context = '' !== $context ? $context : 'component';
		$position = sanitize_key( $args['position'] );
		if ( ! in_array( $position, $this->settings->get_theme_switcher_positions(), true ) ) {
			$position = $this->settings->get_theme_switcher_position();
		}

		$this->assets->enqueue_switcher();
		++self::$instance_count;

		$current_mode = $this->themes->get_theme_mode();
		$control_id   = 'adam-theme-select-' . self::$instance_count;
		$classes      = array(
			'adam-ui',
			'adam-ui-theme-switcher',
			'adam-theme-switcher',
			'adam-theme-switcher--' . $style,
			'adam-theme-switcher--' . $context,
		);
		if ( '' !== trim( (string) $args['class'] ) ) {
			$classes[] = sanitize_html_class( $args['class'] );
		}

		$footer_integrated = 'legacy-footer' === $context;
		$floating          = 'floating' === $context;

		ob_start();
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>"
			data-adam-theme-switcher
			data-adam-display-style="<?php echo esc_attr( $style ); ?>"
			<?php echo $footer_integrated ? 'data-adam-footer-integrated="true"' : ''; ?>
			<?php echo $floating ? 'data-adam-floating-position="' . esc_attr( $position ) . '"' : ''; ?>
		>
			<?php if ( 'dropdown' === $style ) : ?>
				<label class="adam-theme-switcher__label" for="<?php echo esc_attr( $control_id ); ?>">
					<?php echo esc_html__( 'Tema', 'adam-ui' ); ?>
				</label>
				<select
					class="adam-theme-switcher__select"
					id="<?php echo esc_attr( $control_id ); ?>"
					data-adam-theme-select
				>
					<?php $this->render_options( $current_mode ); ?>
				</select>
			<?php else : ?>
				<span class="adam-theme-switcher__label<?php echo 'icon-only' === $style ? ' screen-reader-text' : ''; ?>">
					<?php echo esc_html__( 'Tema', 'adam-ui' ); ?>
				</span>
				<div class="adam-theme-switcher__choices" role="group" aria-label="<?php echo esc_attr__( 'Tema', 'adam-ui' ); ?>">
					<?php $this->render_mode_button( ADAM_UI_Theme_Manager::MODE_LIGHT, '☀', __( 'Claro', 'adam-ui' ), $current_mode, $style ); ?>
					<?php $this->render_mode_button( ADAM_UI_Theme_Manager::MODE_DARK, '☾', __( 'Noite', 'adam-ui' ), $current_mode, $style ); ?>
					<?php $this->render_mode_button( ADAM_UI_Theme_Manager::MODE_SYSTEM, '◐', __( 'Sistema', 'adam-ui' ), $current_mode, $style ); ?>
				</div>
			<?php endif; ?>
			<noscript><span class="adam-theme-switcher__notice"><?php echo esc_html__( 'Ative o JavaScript para alterar o tema.', 'adam-ui' ); ?></span></noscript>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/** Renders the native select options. */
	private function render_options( $current_mode ) {
		$options = array(
			ADAM_UI_Theme_Manager::MODE_LIGHT  => __( 'Claro', 'adam-ui' ),
			ADAM_UI_Theme_Manager::MODE_DARK   => __( 'Noite', 'adam-ui' ),
			ADAM_UI_Theme_Manager::MODE_SYSTEM => __( 'Sistema', 'adam-ui' ),
		);

		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $current_mode, $value, false ),
				esc_html( $label )
			);
		}
	}

	/** Renders one mode button for icon-based display styles. */
	private function render_mode_button( $mode, $icon, $label, $current_mode, $style ) {
		?>
		<button
			class="adam-theme-switcher__choice"
			type="button"
			data-adam-theme-value="<?php echo esc_attr( $mode ); ?>"
			aria-label="<?php echo esc_attr( $label ); ?>"
			aria-pressed="<?php echo $current_mode === $mode ? 'true' : 'false'; ?>"
			title="<?php echo esc_attr( $label ); ?>"
		>
			<span class="adam-theme-switcher__icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
			<?php if ( 'icon-label' === $style ) : ?><span><?php echo esc_html( $label ); ?></span><?php endif; ?>
		</button>
		<?php
	}
}
