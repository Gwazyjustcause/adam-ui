<?php
/**
 * Registry for Night Theme component definitions.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Supplies the Theme Editor, repository, previews, and extensions from one schema.
 */
final class ADAM_UI_Theme_Component_Registry {
	/** @var array<string,array> */
	private $components = array();

	/** @var int */
	private $revision = 0;

	public function __construct() {
		$this->register_builtins();
	}

	/**
	 * Registers or replaces a component definition.
	 *
	 * Controls are grouped arrays containing fields. Each field declares a token,
	 * type, default, and (for selects) options. Style presets contain token maps.
	 *
	 * @param string $slug Component identifier.
	 * @param array  $args Component definition.
	 * @return bool
	 */
	public function register( $slug, $args ) {
		$slug = sanitize_key( $slug );
		if ( ! $slug || ! is_array( $args ) || empty( $args['label'] ) ) {
			return false;
		}

		$args = wp_parse_args(
			$args,
			array(
				'label'       => $slug,
				'description' => '',
				'controls'    => array(),
				'styles'      => array(),
				'preview'     => '',
				'contrast'    => array(),
				'tokens'      => array(),
			)
		);

		$args['slug']     = $slug;
		$args['controls'] = is_array( $args['controls'] ) ? $args['controls'] : array();
		$args['styles']   = is_array( $args['styles'] ) ? $args['styles'] : array();
		$args['contrast'] = is_array( $args['contrast'] ) ? $args['contrast'] : array();
		$args['tokens']   = is_array( $args['tokens'] ) ? $args['tokens'] : array();

		$this->components[ $slug ] = $args;
		$this->revision++;
		return true;
	}

	/** @return array<string,array> */
	public function all() {
		$components = apply_filters( 'adam_ui_theme_components', $this->components, $this );
		if ( ! is_array( $components ) ) {
			return $this->components;
		}
		foreach ( $components as $slug => $component ) {
			if ( ! is_array( $component ) || empty( $component['label'] ) ) {
				unset( $components[ $slug ] );
				continue;
			}
			$components[ $slug ] = wp_parse_args(
				$component,
				array(
					'slug'        => sanitize_key( $slug ),
					'description' => '',
					'controls'    => array(),
					'styles'      => array(),
					'preview'     => '',
					'contrast'    => array(),
					'tokens'      => array(),
				)
			);
		}
		return $components;
	}

	/** @return array|null */
	public function get( $slug ) {
		$components = $this->all();
		$slug       = sanitize_key( $slug );
		return isset( $components[ $slug ] ) ? $components[ $slug ] : null;
	}

	/** @return int */
	public function revision() {
		return $this->revision;
	}

	/**
	 * Returns fields contributed to the persistent design-token schema.
	 *
	 * @return array<string,array>
	 */
	public function schema_fields() {
		$fields = array();
		foreach ( $this->all() as $component ) {
			foreach ( $component['controls'] as $group ) {
				if ( empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
					continue;
				}
				foreach ( $group['fields'] as $field ) {
					if ( empty( $field['token'] ) || empty( $field['label'] ) ) {
						continue;
					}
					$token = sanitize_key( $field['token'] );
					if ( ! $token || isset( $fields[ $token ] ) ) {
						continue;
					}
					$default          = isset( $field['default'] ) ? $field['default'] : '';
					$fields[ $token ] = array(
						'section'  => $component['label'],
						'label'    => $field['label'],
						'type'     => isset( $field['type'] ) ? $field['type'] : 'color',
						'light'    => $default,
						'dark'     => $default,
						'contrast' => $default,
						'options'  => isset( $field['options'] ) ? $field['options'] : array(),
						'editable' => true,
						'unit'     => '',
						'min'      => 0,
						'max'      => 100,
						'step'     => 1,
					);
				}
			}
			foreach ( $component['styles'] as $style ) {
				if ( empty( $style['tokens'] ) || ! is_array( $style['tokens'] ) ) {
					continue;
				}
				foreach ( $style['tokens'] as $token => $value ) {
					$token = sanitize_key( $token );
					if ( ! $token || isset( $fields[ $token ] ) ) {
						continue;
					}
					$fields[ $token ] = array(
						'section'  => $component['label'],
						'label'    => $token,
						'type'     => 'text',
						'light'    => $value,
						'dark'     => $value,
						'contrast' => $value,
						'editable' => false,
						'unit'     => '',
						'min'      => 0,
						'max'      => 100,
						'step'     => 1,
					);
				}
			}
			foreach ( $component['tokens'] as $token => $definition ) {
				$token      = sanitize_key( $token );
				$definition = is_array( $definition ) ? $definition : array( 'default' => $definition );
				if ( ! $token || isset( $fields[ $token ] ) ) {
					continue;
				}
				$default          = isset( $definition['default'] ) ? $definition['default'] : '#f2f4ee';
				$fields[ $token ] = array(
					'section'  => $component['label'],
					'label'    => isset( $definition['label'] ) ? $definition['label'] : $token,
					'type'     => isset( $definition['type'] ) ? $definition['type'] : 'color',
					'light'    => $default,
					'dark'     => $default,
					'contrast' => $default,
					'editable' => false,
					'unit'     => '',
					'min'      => 0,
					'max'      => 100,
					'step'     => 1,
				);
			}
		}
		return $fields;
	}

	/** Applies the selected visual preset for every registered component. */
	public function apply_styles( $tokens ) {
		foreach ( $this->all() as $component ) {
			foreach ( $component['controls'] as $group ) {
				if ( empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
					continue;
				}
				foreach ( $group['fields'] as $field ) {
					if ( empty( $field['token'] ) || 'select' !== $field['type'] || empty( $field['style_control'] ) ) {
						continue;
					}
					$token = $field['token'];
					$value = isset( $tokens[ $token ] ) ? $tokens[ $token ] : $field['default'];
					if ( isset( $component['styles'][ $value ]['tokens'] ) && is_array( $component['styles'][ $value ]['tokens'] ) ) {
						$tokens = array_merge( $tokens, $component['styles'][ $value ]['tokens'] );
					}
				}
			}
		}
		return $tokens;
	}

	/** Returns style maps for instant preview in the browser. */
	public function style_maps() {
		$maps = array();
		foreach ( $this->all() as $slug => $component ) {
			foreach ( $component['styles'] as $style => $definition ) {
				if ( isset( $definition['tokens'] ) && is_array( $definition['tokens'] ) ) {
					$maps[ $slug ][ $style ] = $definition['tokens'];
				}
			}
		}
		return $maps;
	}

	/** Returns component-defined automatic foreground relationships. */
	public function contrast_map() {
		$map = array();
		foreach ( $this->all() as $component ) {
			foreach ( $component['contrast'] as $background => $foregrounds ) {
				$map[ $background ] = array_values( array_unique( array_merge( isset( $map[ $background ] ) ? $map[ $background ] : array(), $foregrounds ) ) );
			}
		}
		return $map;
	}

	/** Registers the eight Night Theme component families. */
	private function register_builtins() {
		$this->register(
			'header',
			array(
				'label'       => __( 'Header', 'adam-ui' ),
				'description' => __( 'Control the site identity, navigation emphasis, and header depth.', 'adam-ui' ),
				'controls'    => array(
					$this->group( __( 'Background', 'adam-ui' ), array( $this->color( 'adam-header-bg', __( 'Header background', 'adam-ui' ), '#161d16' ) ) ),
					$this->group( __( 'Navigation Accent', 'adam-ui' ), array( $this->color( 'adam-header-active-bg', __( 'Active menu accent', 'adam-ui' ), '#9bc85a' ) ) ),
					$this->style_group( 'adam-header-style', 'solid', array( 'solid' => __( 'Solid', 'adam-ui' ), 'transparent' => __( 'Transparent', 'adam-ui' ), 'elevated' => __( 'Elevated', 'adam-ui' ) ) ),
				),
				'styles'      => array(
					'solid'       => $this->style( __( 'Solid', 'adam-ui' ), array( 'adam-header-surface-opacity' => '100%', 'adam-header-shadow' => 'none', 'adam-header-border-width' => '1px' ) ),
					'transparent' => $this->style( __( 'Transparent', 'adam-ui' ), array( 'adam-header-surface-opacity' => '0%', 'adam-header-shadow' => 'none', 'adam-header-border-width' => '0px' ) ),
					'elevated'    => $this->style( __( 'Elevated', 'adam-ui' ), array( 'adam-header-surface-opacity' => '100%', 'adam-header-shadow' => '0 8px 28px rgb(0 0 0 / 0.28)', 'adam-header-border-width' => '0px' ) ),
				),
				'preview'     => '<div class="adam-mini-header"><strong>ADAM</strong><nav><span>Home</span><span class="is-active">Members</span><span>News</span></nav><span aria-hidden="true">&#128269;</span></div>',
				'contrast'    => array( 'adam-header-bg' => array( 'adam-header-nav-text', 'adam-header-search-icon' ), 'adam-header-active-bg' => array( 'adam-header-active-text' ) ),
			)
		);

		$this->register(
			'sections',
			array(
				'label'       => __( 'Sections', 'adam-ui' ),
				'description' => __( 'Set the reusable content surface used by most pages.', 'adam-ui' ),
				'controls'    => array(
					$this->group( __( 'Background', 'adam-ui' ), array( $this->color( 'adam-section-standard-bg', __( 'Section background', 'adam-ui' ), '#141914' ) ) ),
					$this->style_group( 'adam-section-style', 'standard', array( 'standard' => __( 'Standard', 'adam-ui' ), 'alternate' => __( 'Alternate', 'adam-ui' ), 'highlight' => __( 'Highlight', 'adam-ui' ) ) ),
				),
				'styles'      => array(
					'standard'  => $this->style( __( 'Standard', 'adam-ui' ), array( 'adam-section-padding' => '2rem', 'adam-section-border-width' => '0px', 'adam-section-shadow' => 'none' ) ),
					'alternate' => $this->style( __( 'Alternate', 'adam-ui' ), array( 'adam-section-padding' => '2.5rem', 'adam-section-border-width' => '1px', 'adam-section-shadow' => 'none' ) ),
					'highlight' => $this->style( __( 'Highlight', 'adam-ui' ), array( 'adam-section-padding' => '2.5rem', 'adam-section-border-width' => '1px', 'adam-section-shadow' => 'inset 4px 0 0 var(--adam-btn-primary-bg)' ) ),
				),
				'preview'     => '<section class="adam-mini-section"><h3>Community news</h3><p>A reusable content section with automatic contrast.</p><button>Learn more</button></section>',
				'contrast'    => array( 'adam-section-standard-bg' => array( 'adam-section-standard-heading', 'adam-section-standard-text', 'adam-section-standard-link' ) ),
			)
		);

		$this->register(
			'feature-sections',
			array(
				'label'       => __( 'Feature Sections', 'adam-ui' ),
				'description' => __( 'Style highlighted areas for community, events, and partnerships.', 'adam-ui' ),
				'controls'    => array(
					$this->group( __( 'Background', 'adam-ui' ), array( $this->color( 'adam-section-feature-bg', __( 'Feature background', 'adam-ui' ), '#205033' ) ) ),
					$this->style_group( 'adam-feature-style', 'highlight', array( 'highlight' => __( 'Highlight', 'adam-ui' ), 'solid' => __( 'Solid', 'adam-ui' ), 'minimal' => __( 'Minimal', 'adam-ui' ) ) ),
				),
				'styles'      => array(
					'highlight' => $this->style( __( 'Highlight', 'adam-ui' ), array( 'adam-feature-padding' => '2rem', 'adam-feature-border-width' => '1px', 'adam-feature-icon-size' => '2.25rem' ) ),
					'solid'     => $this->style( __( 'Solid', 'adam-ui' ), array( 'adam-feature-padding' => '2.5rem', 'adam-feature-border-width' => '0px', 'adam-feature-icon-size' => '2.5rem' ) ),
					'minimal'   => $this->style( __( 'Minimal', 'adam-ui' ), array( 'adam-feature-padding' => '1.5rem', 'adam-feature-border-width' => '0px', 'adam-feature-icon-size' => '2rem' ) ),
				),
				'preview'     => '<section class="adam-mini-feature"><span aria-hidden="true">&#9733;</span><div><h3>Upcoming events</h3><p>Important content keeps a distinct place in the page rhythm.</p></div></section>',
				'contrast'    => array( 'adam-section-feature-bg' => array( 'adam-section-feature-heading', 'adam-section-feature-text', 'adam-section-feature-link' ) ),
			)
		);

		$this->register(
			'hero',
			array(
				'label'       => __( 'Hero', 'adam-ui' ),
				'description' => __( 'Control the opening statement without changing its structure or imagery.', 'adam-ui' ),
				'controls'    => array(
					$this->group( __( 'Background', 'adam-ui' ), array( $this->color( 'adam-hero-bg', __( 'Hero background', 'adam-ui' ), '#172016' ) ) ),
					$this->style_group( 'adam-hero-style', 'split', array( 'solid' => __( 'Solid', 'adam-ui' ), 'split' => __( 'Split', 'adam-ui' ), 'minimal' => __( 'Minimal', 'adam-ui' ) ) ),
					$this->group( __( 'Buttons', 'adam-ui' ), array( $this->color( 'adam-hero-primary', __( 'Primary action', 'adam-ui' ), '#9bc85a' ), $this->color( 'adam-hero-secondary', __( 'Secondary action', 'adam-ui' ), '#242b22' ) ) ),
				),
				'styles'      => array(
					'solid'   => $this->style( __( 'Solid', 'adam-ui' ), array( 'adam-hero-padding' => '4rem', 'adam-hero-layout-columns' => '1fr', 'adam-hero-border-width' => '0px' ) ),
					'split'   => $this->style( __( 'Split', 'adam-ui' ), array( 'adam-hero-padding' => '4rem', 'adam-hero-layout-columns' => '1.25fr 0.75fr', 'adam-hero-border-width' => '1px' ) ),
					'minimal' => $this->style( __( 'Minimal', 'adam-ui' ), array( 'adam-hero-padding' => '2.5rem', 'adam-hero-layout-columns' => '1fr', 'adam-hero-border-width' => '0px' ) ),
				),
				'preview'     => '<section class="adam-mini-hero"><div><small>ADAM COMMUNITY</small><h3>Play together. Grow together.</h3><p>The same familiar hero, made comfortable at night.</p><button>Join ADAM</button><button class="is-secondary">Learn more</button></div><span class="adam-mini-image" aria-label="Image placeholder">Image</span></section>',
				'contrast'    => array( 'adam-hero-bg' => array( 'adam-hero-eyebrow', 'adam-hero-heading', 'adam-hero-text' ) ),
			)
		);

		$this->register(
			'cards',
			array(
				'label'       => __( 'Cards', 'adam-ui' ),
				'description' => __( 'Choose one shared card treatment for dashboards, notices, and plugins.', 'adam-ui' ),
				'controls'    => array(
					$this->group( __( 'Background', 'adam-ui' ), array( $this->color( 'adam-card-bg', __( 'Card background', 'adam-ui' ), '#1a2019' ) ) ),
					$this->style_group( 'adam-card-style', 'elevated', array( 'flat' => __( 'Flat', 'adam-ui' ), 'elevated' => __( 'Elevated', 'adam-ui' ), 'glass' => __( 'Glass', 'adam-ui' ), 'minimal' => __( 'Minimal', 'adam-ui' ) ) ),
				),
				'styles'      => array(
					'flat'     => $this->style( __( 'Flat', 'adam-ui' ), array( 'adam-card-border-width' => '1px', 'adam-card-shadow-strength' => '0', 'adam-card-backdrop' => 'none', 'adam-card-surface-opacity' => '100%', 'adam-card-padding' => '1.25rem' ) ),
					'elevated' => $this->style( __( 'Elevated', 'adam-ui' ), array( 'adam-card-border-width' => '1px', 'adam-card-shadow-strength' => '.35', 'adam-card-backdrop' => 'none', 'adam-card-surface-opacity' => '100%', 'adam-card-padding' => '1.5rem' ) ),
					'glass'    => $this->style( __( 'Glass', 'adam-ui' ), array( 'adam-card-border-width' => '1px', 'adam-card-shadow-strength' => '.2', 'adam-card-backdrop' => 'blur(14px)', 'adam-card-surface-opacity' => '78%', 'adam-card-padding' => '1.5rem' ) ),
					'minimal'  => $this->style( __( 'Minimal', 'adam-ui' ), array( 'adam-card-border-width' => '0px', 'adam-card-shadow-strength' => '0', 'adam-card-backdrop' => 'none', 'adam-card-surface-opacity' => '0%', 'adam-card-padding' => '1rem' ) ),
				),
				'preview'     => '<article class="adam-mini-card"><small>ADAM UI</small><h3>Shared card</h3><p>One treatment updates member areas and future plugins.</p><button>Open card</button></article>',
				'contrast'    => array( 'adam-card-bg' => array( 'adam-card-heading', 'adam-card-text', 'adam-card-link' ) ),
			)
		);

		$this->register(
			'buttons',
			array(
				'label'       => __( 'Buttons', 'adam-ui' ),
				'description' => __( 'Manage the primary, secondary, and outline actions as one family.', 'adam-ui' ),
				'controls'    => array(
					$this->group( __( 'Background', 'adam-ui' ), array( $this->color( 'adam-btn-primary-bg', __( 'Primary', 'adam-ui' ), '#9bc85a' ), $this->color( 'adam-btn-secondary-bg', __( 'Secondary', 'adam-ui' ), '#374238' ), $this->color( 'adam-btn-outline-border', __( 'Outline', 'adam-ui' ), '#b5db70' ) ) ),
					$this->style_group( 'adam-button-style', 'filled', array( 'filled' => __( 'Filled', 'adam-ui' ), 'outline' => __( 'Outline', 'adam-ui' ), 'soft' => __( 'Soft', 'adam-ui' ) ) ),
				),
				'styles'      => array(
					'filled'  => $this->style( __( 'Filled', 'adam-ui' ), array( 'adam-button-fill-opacity' => '100%', 'adam-button-border-width' => '1px', 'adam-button-shadow' => 'none', 'adam-button-primary-text-display' => 'var(--adam-btn-primary-text)', 'adam-button-secondary-text-display' => 'var(--adam-btn-secondary-text)' ) ),
					'outline' => $this->style( __( 'Outline', 'adam-ui' ), array( 'adam-button-fill-opacity' => '0%', 'adam-button-border-width' => '2px', 'adam-button-shadow' => 'none', 'adam-button-primary-text-display' => 'var(--adam-btn-primary-bg)', 'adam-button-secondary-text-display' => 'var(--adam-btn-secondary-border)' ) ),
					'soft'    => $this->style( __( 'Soft', 'adam-ui' ), array( 'adam-button-fill-opacity' => '20%', 'adam-button-border-width' => '1px', 'adam-button-shadow' => 'none', 'adam-button-primary-text-display' => 'var(--adam-btn-primary-bg)', 'adam-button-secondary-text-display' => 'var(--adam-btn-secondary-border)' ) ),
				),
				'preview'     => '<div class="adam-mini-buttons"><button class="is-primary">Primary</button><button class="is-secondary">Secondary</button><button class="is-outline">Outline</button></div>',
			)
		);

		$this->register(
			'forms',
			array(
				'label'       => __( 'Forms', 'adam-ui' ),
				'description' => __( 'Choose one field treatment for every input workflow.', 'adam-ui' ),
				'controls'    => array(
					$this->style_group( 'adam-form-style', 'outlined', array( 'outlined' => __( 'Outlined', 'adam-ui' ), 'filled' => __( 'Filled', 'adam-ui' ), 'minimal' => __( 'Minimal', 'adam-ui' ) ) ),
				),
				'styles'      => array(
					'outlined' => $this->style( __( 'Outlined', 'adam-ui' ), array( 'adam-form-border-width' => '1px', 'adam-form-shadow' => 'none', 'adam-form-surface-opacity' => '100%' ) ),
					'filled'   => $this->style( __( 'Filled', 'adam-ui' ), array( 'adam-form-border-width' => '0px', 'adam-form-shadow' => 'inset 0 0 0 1px rgb(255 255 255 / .08)', 'adam-form-surface-opacity' => '100%' ) ),
					'minimal'  => $this->style( __( 'Minimal', 'adam-ui' ), array( 'adam-form-border-width' => '0px 0px 1px', 'adam-form-shadow' => 'none', 'adam-form-surface-opacity' => '0%' ) ),
				),
				'preview'     => '<div class="adam-mini-form"><label>Name<input placeholder="Member name"></label><label>Team<select><option>Choose a team</option></select></label><label class="is-check"><input type="checkbox" checked> Receive updates</label><button>Submit</button></div>',
				'contrast'    => array( 'adam-form-input-bg' => array( 'adam-form-input-text', 'adam-form-placeholder' ), 'adam-form-button' => array( 'adam-form-button-text' ) ),
			)
		);

		$this->register(
			'footer',
			array(
				'label'       => __( 'Footer', 'adam-ui' ),
				'description' => __( 'Keep footer content and the theme switcher visually independent.', 'adam-ui' ),
				'controls'    => array(
					$this->group( __( 'Background', 'adam-ui' ), array( $this->color( 'adam-footer-bg', __( 'Footer background', 'adam-ui' ), '#11170e' ) ) ),
					$this->style_group( 'adam-footer-style', 'solid', array( 'solid' => __( 'Solid', 'adam-ui' ), 'contrast' => __( 'Contrast', 'adam-ui' ), 'minimal' => __( 'Minimal', 'adam-ui' ) ) ),
				),
				'styles'      => array(
					'solid'    => $this->style( __( 'Solid', 'adam-ui' ), array( 'adam-footer-padding' => '2.5rem', 'adam-footer-divider-width' => '1px' ) ),
					'contrast' => $this->style( __( 'Contrast', 'adam-ui' ), array( 'adam-footer-padding' => '3rem', 'adam-footer-divider-width' => '2px' ) ),
					'minimal'  => $this->style( __( 'Minimal', 'adam-ui' ), array( 'adam-footer-padding' => '1.75rem', 'adam-footer-divider-width' => '0px' ) ),
				),
				'preview'     => '<footer class="adam-mini-footer"><strong>ADAM</strong><nav><a href="#">About</a> <a href="#">Contact</a></nav><span class="adam-mini-socials" aria-label="Social icons">&#9679; &#9679; &#9679;</span><div class="adam-mini-footer__switcher">Theme: System</div><small>&copy; ADAM</small></footer>',
				'contrast'    => array( 'adam-footer-bg' => array( 'adam-footer-heading', 'adam-footer-text', 'adam-footer-link', 'adam-footer-link-hover', 'adam-footer-social', 'adam-footer-copyright' ) ),
			)
		);
	}

	private function group( $label, $fields ) {
		return array( 'label' => $label, 'fields' => $fields );
	}

	private function color( $token, $label, $default ) {
		return array( 'token' => $token, 'label' => $label, 'type' => 'color', 'default' => $default );
	}

	private function style_group( $token, $default, $options ) {
		return $this->group(
			__( 'Style', 'adam-ui' ),
			array(
				array(
					'token'         => $token,
					'label'         => __( 'Visual style', 'adam-ui' ),
					'type'          => 'select',
					'default'       => $default,
					'options'       => $options,
					'style_control' => true,
				),
			)
		);
	}

	private function style( $label, $tokens ) {
		return array( 'label' => $label, 'tokens' => $tokens );
	}
}
