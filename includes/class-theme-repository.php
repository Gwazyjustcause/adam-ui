<?php
/** Persistent theme definitions and generated design tokens. */

defined( 'ABSPATH' ) || exit;

final class ADAM_UI_Theme_Repository {
	const OPTION_KEY = 'adam_ui_themes';
	const SCHEMA_VERSION = 3;

	private $schema;

	public function __construct() {
		$this->schema = $this->build_schema();
	}

	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'ensure_storage' ), 1 );
		add_action( 'admin_post_adam_ui_theme_action', array( $this, 'handle_action' ) );
		add_action( 'admin_post_adam_ui_export_theme', array( $this, 'export_theme' ) );
	}

	public function schema() { return $this->schema; }

	public function ensure_storage() {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			update_option( self::OPTION_KEY, $this->defaults(), false );
		}
	}

	public function defaults() {
		return array(
			'version' => self::SCHEMA_VERSION,
			'active' => array( 'dark' => 'adam-night' ),
			'themes' => array(
				'adam-night' => $this->preset( 'ADAM Night', 'dark', 'dark', true ),
			),
		);
	}

	private function preset( $name, $mode, $column, $builtin ) {
		$tokens = array();
		foreach ( $this->schema as $key => $field ) {
			$tokens[ $key ] = $field[ $column ];
		}
		return array( 'name' => $name, 'mode' => $mode, 'builtin' => $builtin, 'tokens' => $this->apply_automatic_contrast( $tokens ) );
	}

	public function all() {
		$defaults = $this->defaults();
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) { return $defaults; }
		$data = $defaults;
		if ( ! empty( $saved['themes'] ) && is_array( $saved['themes'] ) ) {
			foreach ( $saved['themes'] as $id => $theme ) {
				$id = sanitize_key( $id );
				if ( $id ) { $data['themes'][ $id ] = $this->sanitize_theme( $theme, isset( $defaults['themes'][ $id ] ) ? $defaults['themes'][ $id ] : null ); }
			}
		}
		if ( ! empty( $saved['active']['dark'] ) ) {
			$id = sanitize_key( $saved['active']['dark'] );
			if ( isset( $data['themes'][ $id ] ) && 'dark' === $data['themes'][ $id ]['mode'] ) { $data['active']['dark'] = $id; }
		}
		return $data;
	}

	private function sanitize_theme( $theme, $fallback = null ) {
		$fallback = is_array( $fallback ) ? $fallback : $this->preset( 'Custom Night Theme', 'dark', 'dark', false );
		$theme = is_array( $theme ) ? $theme : array();
		$out = array(
			'name' => isset( $theme['name'] ) ? sanitize_text_field( $theme['name'] ) : $fallback['name'],
			'mode' => isset( $theme['mode'] ) && 'dark' === $theme['mode'] ? 'dark' : 'light',
			'builtin' => ! empty( $fallback['builtin'] ),
			'tokens' => array(),
		);
		$tokens = isset( $theme['tokens'] ) && is_array( $theme['tokens'] ) ? $theme['tokens'] : array();
		$tokens = $this->migrate_legacy_tokens( $tokens );
		foreach ( $this->schema as $key => $field ) {
			$value = isset( $tokens[ $key ] ) ? wp_unslash( $tokens[ $key ] ) : $fallback['tokens'][ $key ];
			$out['tokens'][ $key ] = $this->sanitize_token( $value, $field );
		}
		if ( 'dark' === $out['mode'] ) { $out['tokens'] = $this->apply_automatic_contrast( $out['tokens'] ); }
		return $out;
	}

	/** Preserves themes created before the component-oriented schema. */
	private function migrate_legacy_tokens( $tokens ) {
		$map = array(
			'adam-header-bg' => 'adam-nav-bg', 'adam-header-nav-bg' => 'adam-nav-bg', 'adam-header-nav-text' => 'adam-text', 'adam-header-active-bg' => 'adam-primary', 'adam-header-active-text' => 'adam-on-primary', 'adam-header-hover-bg' => 'adam-feature-bg', 'adam-header-hover-text' => 'adam-link-hover', 'adam-header-search-icon' => 'adam-primary', 'adam-header-border' => 'adam-border',
			'adam-footer-heading' => 'adam-heading', 'adam-footer-text' => 'adam-text', 'adam-footer-link' => 'adam-link', 'adam-footer-link-hover' => 'adam-link-hover', 'adam-footer-social' => 'adam-primary', 'adam-footer-divider' => 'adam-divider', 'adam-footer-copyright' => 'adam-text-muted', 'adam-footer-switcher-bg' => 'adam-surface', 'adam-footer-switcher-text' => 'adam-text', 'adam-footer-switcher-border' => 'adam-border',
			'adam-hero-eyebrow' => 'adam-primary', 'adam-hero-heading' => 'adam-heading', 'adam-hero-text' => 'adam-text-secondary', 'adam-hero-primary' => 'adam-primary', 'adam-hero-secondary' => 'adam-surface-elevated',
			'adam-section-standard-bg' => 'adam-section-bg', 'adam-section-standard-heading' => 'adam-heading', 'adam-section-standard-text' => 'adam-text', 'adam-section-standard-link' => 'adam-link', 'adam-section-alternate-bg' => 'adam-bg', 'adam-section-alternate-heading' => 'adam-heading', 'adam-section-alternate-text' => 'adam-text', 'adam-section-alternate-link' => 'adam-link', 'adam-section-feature-bg' => 'adam-feature-bg', 'adam-section-feature-heading' => 'adam-heading', 'adam-section-feature-text' => 'adam-text', 'adam-section-feature-link' => 'adam-link', 'adam-section-cta-bg' => 'adam-primary', 'adam-section-cta-heading' => 'adam-heading', 'adam-section-cta-text' => 'adam-text', 'adam-section-cta-link' => 'adam-link', 'adam-section-overlay-bg' => 'adam-footer-bg', 'adam-section-overlay-heading' => 'adam-heading', 'adam-section-overlay-text' => 'adam-text', 'adam-section-overlay-link' => 'adam-link',
			'adam-card-bg' => 'adam-surface', 'adam-card-elevated-bg' => 'adam-surface-elevated', 'adam-card-border' => 'adam-border', 'adam-card-heading' => 'adam-heading', 'adam-card-text' => 'adam-text-secondary', 'adam-card-link' => 'adam-link',
			'adam-form-label' => 'adam-text', 'adam-form-input-bg' => 'adam-input-bg', 'adam-form-input-text' => 'adam-text', 'adam-form-placeholder' => 'adam-text-muted', 'adam-form-border' => 'adam-input-border', 'adam-form-focus' => 'adam-input-focus', 'adam-form-button' => 'adam-primary',
			'adam-table-header-bg' => 'adam-feature-bg', 'adam-table-row-bg' => 'adam-surface', 'adam-table-alt-row-bg' => 'adam-bg', 'adam-table-border' => 'adam-border',
		);
		foreach ( array( 'primary', 'secondary', 'danger', 'success' ) as $variant ) {
			$legacy = 'primary' === $variant ? 'adam-primary' : ( 'secondary' === $variant ? 'adam-brand-secondary' : 'adam-' . $variant );
			foreach ( array( 'bg', 'border', 'hover-bg', 'hover-border' ) as $suffix ) { $map[ 'adam-btn-' . $variant . '-' . $suffix ] = $legacy; }
			$map[ 'adam-btn-' . $variant . '-text' ] = 'adam-on-' . $variant;
			$map[ 'adam-btn-' . $variant . '-hover-text' ] = 'adam-on-' . $variant;
		}
		foreach ( $map as $new => $old ) {
			if ( ! isset( $tokens[ $new ] ) && isset( $tokens[ $old ] ) ) { $tokens[ $new ] = $tokens[ $old ]; }
		}
		return $tokens;
	}

	private function sanitize_token( $value, $field ) {
		if ( 'color' === $field['type'] ) {
			return $this->sanitize_css_color( $value, $field['light'] );
		}
		if ( 'number' === $field['type'] ) {
			$value = (float) $value;
			$value = max( $field['min'], min( $field['max'], $value ) );
			return rtrim( rtrim( number_format( $value, 3, '.', '' ), '0' ), '.' ) . $field['unit'];
		}
		return preg_match( '/^[#(),.%\-\s\w\/]+$/', (string) $value ) ? trim( $value ) : $field['light'];
	}

	/** Accepts safe CSS Color values without limiting the editor to a palette. */
	private function sanitize_css_color( $value, $fallback ) {
		$value = trim( wp_strip_all_tags( (string) $value ) );

		if ( preg_match( '/^#[0-9a-f]{3,4}(?:[0-9a-f]{3,4})?$/i', $value ) ) {
			return $value;
		}

		if ( preg_match( '/^(?:rgb|rgba|hsl|hsla|hwb|lab|lch|oklab|oklch|color)\(\s*[-+0-9.%\s,\/a-z]+\)$/i', $value ) ) {
			return $value;
		}

		if ( preg_match( '/^(?:transparent|currentcolor|[a-z]+)$/i', $value ) ) {
			return $value;
		}

		return $fallback;
	}

	/** Derives readable foregrounds from each Night surface. */
	private function apply_automatic_contrast( $tokens ) {
		foreach ( $this->contrast_map() as $background => $foregrounds ) {
			if ( ! isset( $tokens[ $background ] ) ) { continue; }
			$contrast = $this->contrast_text( $tokens[ $background ] );
			foreach ( $foregrounds as $foreground ) { if ( isset( $tokens[ $foreground ] ) ) { $tokens[ $foreground ] = $contrast; } }
		}
		if ( isset( $tokens['adam-section-standard-bg'], $tokens['adam-form-label'] ) ) { $tokens['adam-form-label'] = $this->contrast_text( $tokens['adam-section-standard-bg'] ); }
		return $tokens;
	}

	/** Returns the single background-to-foreground derivation contract. */
	public function contrast_map() {
		$groups = array(
			'adam-header-bg' => array( 'adam-header-search-icon' ),
			'adam-header-nav-bg' => array( 'adam-header-nav-text' ),
			'adam-header-active-bg' => array( 'adam-header-active-text' ),
			'adam-header-hover-bg' => array( 'adam-header-hover-text' ),
			'adam-footer-bg' => array( 'adam-footer-heading', 'adam-footer-text', 'adam-footer-link', 'adam-footer-link-hover', 'adam-footer-social', 'adam-footer-copyright' ),
			'adam-footer-switcher-bg' => array( 'adam-footer-switcher-text' ),
			'adam-hero-bg' => array( 'adam-hero-eyebrow', 'adam-hero-heading', 'adam-hero-text' ),
			'adam-card-bg' => array( 'adam-card-heading', 'adam-card-text', 'adam-card-link' ),
			'adam-form-input-bg' => array( 'adam-form-input-text', 'adam-form-placeholder' ),
			'adam-form-button' => array( 'adam-form-button-text' ),
			'adam-notice-success-bg' => array( 'adam-notice-success-text' ),
			'adam-notice-info-bg' => array( 'adam-notice-info-text' ),
			'adam-notice-warning-bg' => array( 'adam-notice-warning-text' ),
			'adam-notice-error-bg' => array( 'adam-notice-error-text' ),
		);
		foreach ( array( 'standard', 'alternate', 'feature', 'cta', 'overlay' ) as $section ) {
			$groups[ 'adam-section-' . $section . '-bg' ] = array( 'adam-section-' . $section . '-heading', 'adam-section-' . $section . '-text', 'adam-section-' . $section . '-link' );
		}
		foreach ( array( 'primary', 'secondary', 'danger', 'success' ) as $button ) {
			$groups[ 'adam-btn-' . $button . '-bg' ] = array( 'adam-btn-' . $button . '-text' );
			$groups[ 'adam-btn-' . $button . '-hover-bg' ] = array( 'adam-btn-' . $button . '-hover-text' );
		}
		return $groups;
	}

	/** Chooses the higher-contrast Night foreground for a CSS colour. */
	private function contrast_text( $color ) {
		$rgb = $this->css_color_rgb( $color );
		if ( ! $rgb ) { return '#f2f4ee'; }
		$luminance = 0;
		foreach ( array( 0.2126, 0.7152, 0.0722 ) as $index => $weight ) {
			$channel = $rgb[ $index ] / 255;
			$channel = $channel <= 0.04045 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
			$luminance += $channel * $weight;
		}
		$light_luminance = 0.904;
		$dark_luminance = 0.014;
		$light_ratio = ( max( $light_luminance, $luminance ) + 0.05 ) / ( min( $light_luminance, $luminance ) + 0.05 );
		$dark_ratio = ( max( $dark_luminance, $luminance ) + 0.05 ) / ( min( $dark_luminance, $luminance ) + 0.05 );
		if ( max( $light_ratio, $dark_ratio ) < 4.5 ) { return $luminance <= 0.179 ? '#ffffff' : '#000000'; }
		return $light_ratio >= $dark_ratio ? '#f2f4ee' : '#172107';
	}

	/** Parses the editable CSS colour formats needed for contrast calculation. */
	private function css_color_rgb( $color ) {
		$color = strtolower( trim( (string) $color ) );
		$named = array( 'black' => array( 0, 0, 0 ), 'white' => array( 255, 255, 255 ), 'transparent' => array( 0, 0, 0 ) );
		if ( isset( $named[ $color ] ) ) { return $named[ $color ]; }
		if ( preg_match( '/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $color, $match ) ) {
			$hex = $match[1];
			if ( in_array( strlen( $hex ), array( 3, 4 ), true ) ) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
			return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
		}
		if ( preg_match( '/^rgba?\(([^)]+)\)$/i', $color, $match ) ) {
			$parts = preg_split( '/[\s,\/]+/', trim( $match[1] ) );
			if ( count( $parts ) >= 3 ) { return array_map( static function ( $part ) { return false !== strpos( $part, '%' ) ? 255 * (float) $part / 100 : max( 0, min( 255, (float) $part ) ); }, array_slice( $parts, 0, 3 ) ); }
		}
		if ( preg_match( '/^hsla?\(\s*([-+0-9.]+)(?:deg)?[\s,]+([-+0-9.]+)%[\s,]+([-+0-9.]+)%/i', $color, $match ) ) {
			$h = fmod( (float) $match[1], 360 ) / 360; if ( $h < 0 ) { $h += 1; } $s = max( 0, min( 1, (float) $match[2] / 100 ) ); $l = max( 0, min( 1, (float) $match[3] / 100 ) );
			if ( $s <= 0 ) { return array( 255 * $l, 255 * $l, 255 * $l ); }
			$q = $l < .5 ? $l * ( 1 + $s ) : $l + $s - ( $l * $s ); $p = ( 2 * $l ) - $q;
			$hue = static function ( $p, $q, $t ) { if ( $t < 0 ) { $t += 1; } if ( $t > 1 ) { $t -= 1; } if ( $t < 1/6 ) { return $p + ( $q - $p ) * 6 * $t; } if ( $t < 1/2 ) { return $q; } if ( $t < 2/3 ) { return $p + ( $q - $p ) * ( 2/3 - $t ) * 6; } return $p; };
			return array( 255 * $hue( $p, $q, $h + 1/3 ), 255 * $hue( $p, $q, $h ), 255 * $hue( $p, $q, $h - 1/3 ) );
		}
		return null;
	}

	public function themes() { $all = $this->all(); return $all['themes']; }
	/** Returns only Night presets managed by ADAM UI. Legacy Light data stays stored but hidden. */
	public function night_themes() { return array_filter( $this->themes(), static function ( $theme ) { return isset( $theme['mode'] ) && 'dark' === $theme['mode']; } ); }
	public function get_theme( $id ) { $themes = $this->themes(); return isset( $themes[ $id ] ) ? $themes[ $id ] : null; }
	public function active_id( $mode ) { if ( 'dark' !== $mode ) { return ''; } $all = $this->all(); return isset( $all['active']['dark'] ) ? $all['active']['dark'] : 'adam-night'; }
	public function active_theme( $mode ) { return 'dark' === $mode ? $this->get_theme( $this->active_id( 'dark' ) ) : null; }
	public function tokens( $mode = 'dark' ) { $theme = $this->active_theme( $mode ); return $theme ? $theme['tokens'] : array(); }
	public function token( $name, $mode = 'dark', $fallback = '' ) {
		$name = ltrim( sanitize_key( str_replace( '_', '-', $name ) ), '-' );
		$tokens = $this->tokens( $mode );
		return isset( $tokens[ $name ] ) ? $tokens[ $name ] : $fallback;
	}

	public function generated_css() {
		$css = 'body.adam-theme-dark,.adam-theme-dark{';
		foreach ( $this->tokens( 'dark' ) as $key => $value ) { $css .= '--' . $key . ':' . $value . ';'; }
		$css .= $this->compatibility_aliases();
		$css .= '--adam-card-shadow:0 .5rem 1.5rem rgb(0 0 0 / var(--adam-card-shadow-strength));}';
		return $css;
	}

	/** Maps the component editor vocabulary onto the stable ecosystem tokens. */
	private function compatibility_aliases() {
		return implode(
			'',
			array(
				'--adam-bg:var(--adam-section-standard-bg);--adam-section-bg:var(--adam-section-standard-bg);--adam-feature-bg:var(--adam-section-feature-bg);--adam-section-canvas:var(--adam-section-standard-bg);--adam-section-base:var(--adam-section-standard-bg);--adam-section-muted:var(--adam-section-alternate-bg);--adam-section-soft:var(--adam-section-alternate-bg);--adam-section-pale:var(--adam-section-feature-bg);--adam-section-feature:var(--adam-section-feature-bg);--adam-section-accent:var(--adam-section-cta-bg);--adam-section-deep:var(--adam-section-overlay-bg);--adam-section-gradient-feature:var(--adam-section-feature-bg);--adam-section-gradient-soft:var(--adam-section-alternate-bg);--adam-section-gradient-neutral:var(--adam-section-standard-bg);',
				'--adam-on-section-base:var(--adam-section-standard-text);--adam-on-section-soft:var(--adam-section-alternate-text);--adam-on-section-feature:var(--adam-section-feature-text);--adam-on-section-accent:var(--adam-section-cta-text);--adam-on-section-deep:var(--adam-section-overlay-text);',
				'--adam-surface:var(--adam-card-bg);--adam-surface-2:var(--adam-card-elevated-bg);--adam-surface-hover:var(--adam-card-elevated-bg);--adam-surface-elevated:var(--adam-card-elevated-bg);--adam-surface-card:var(--adam-card-bg);--adam-heading:var(--adam-section-standard-heading);--adam-text:var(--adam-section-standard-text);--adam-text-primary:var(--adam-section-standard-text);--adam-text-secondary:var(--adam-card-text);--adam-text-muted:var(--adam-form-placeholder);--adam-text-disabled:var(--adam-form-placeholder);--adam-link:var(--adam-section-standard-link);--adam-link-hover:var(--adam-section-standard-link);--adam-border:var(--adam-card-border);--adam-border-strong:var(--adam-card-border);--adam-divider:var(--adam-footer-divider);',
				'--adam-header-text:var(--adam-header-nav-text);--adam-nav-bg:var(--adam-header-nav-bg);--adam-nav-hover:var(--adam-header-hover-bg);',
				'--adam-primary:var(--adam-btn-primary-bg);--adam-primary-hover:var(--adam-btn-primary-hover-bg);--adam-on-primary:var(--adam-btn-primary-text);--adam-secondary:var(--adam-btn-secondary-bg);--adam-secondary-hover:var(--adam-btn-secondary-hover-bg);--adam-on-secondary:var(--adam-btn-secondary-text);--adam-danger:var(--adam-btn-danger-bg);--adam-danger-hover:var(--adam-btn-danger-hover-bg);--adam-on-danger:var(--adam-btn-danger-text);--adam-success:var(--adam-btn-success-bg);--adam-on-success:var(--adam-btn-success-text);',
				'--adam-input-bg:var(--adam-form-input-bg);--adam-input-disabled-bg:var(--adam-card-elevated-bg);--adam-input-border:var(--adam-form-border);--adam-placeholder:var(--adam-form-placeholder);--adam-focus-ring:var(--adam-form-focus);--adam-table-stripe:var(--adam-table-alt-row-bg);--adam-code-bg:var(--adam-card-elevated-bg);--adam-info:var(--adam-notice-info-border);--adam-info-bg:var(--adam-notice-info-bg);--adam-success-bg:var(--adam-notice-success-bg);--adam-warning:var(--adam-notice-warning-border);--adam-warning-bg:var(--adam-notice-warning-bg);--adam-danger-bg:var(--adam-notice-error-bg);--adam-selection-bg:var(--adam-btn-primary-bg);--adam-selection-text:var(--adam-btn-primary-text);--adam-overlay:var(--adam-section-overlay-bg);--adam-primary-soft:color-mix(in srgb,var(--adam-btn-primary-bg) 16%,transparent);--adam-color-scheme:dark;',
				'--theme-palette-color-1:var(--adam-section-feature-bg);--theme-palette-color-2:var(--adam-section-cta-bg);--theme-palette-color-4:var(--adam-section-overlay-bg);--theme-palette-color-5:var(--adam-section-feature-bg);--theme-palette-color-6:var(--adam-section-alternate-bg);--theme-palette-color-7:var(--adam-section-standard-bg);--theme-palette-color-8:var(--adam-section-standard-bg);',
			)
		);
	}

	public function handle_action() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'adam-ui' ) ); }
		check_admin_referer( 'adam_ui_theme_action' );
		$operation = isset( $_POST['theme_operation'] ) ? sanitize_key( wp_unslash( $_POST['theme_operation'] ) ) : 'save';
		$id = isset( $_POST['theme_id'] ) ? sanitize_key( wp_unslash( $_POST['theme_id'] ) ) : '';
		$data = $this->all();
		if ( 'import' === $operation ) { $id = $this->import_uploaded( $data ); }
		elseif ( isset( $data['themes'][ $id ] ) ) {
			if ( 'delete' === $operation && empty( $data['themes'][ $id ]['builtin'] ) ) {
				unset( $data['themes'][ $id ] );
				if ( isset( $data['active']['dark'] ) && $data['active']['dark'] === $id ) { $data['active']['dark'] = 'adam-night'; }
				$id = 'adam-night';
			} elseif ( 'duplicate' === $operation ) {
				$new_id = $this->unique_id( sanitize_title( $data['themes'][ $id ]['name'] . '-copy' ), $data['themes'] );
				$data['themes'][ $new_id ] = $data['themes'][ $id ]; $data['themes'][ $new_id ]['name'] .= ' Copy'; $data['themes'][ $new_id ]['builtin'] = false; $id = $new_id;
			} elseif ( 'save' === $operation ) {
				$posted = array( 'name' => isset( $_POST['theme_name'] ) ? wp_unslash( $_POST['theme_name'] ) : $data['themes'][ $id ]['name'], 'mode' => 'dark', 'tokens' => isset( $_POST['tokens'] ) ? $_POST['tokens'] : array() );
				$data['themes'][ $id ] = $this->sanitize_theme( $posted, $data['themes'][ $id ] );
				$data['active']['dark'] = $id;
			}
		}
		update_option( self::OPTION_KEY, $data, false );
		wp_safe_redirect( add_query_arg( array( 'page' => 'adam-ui-theme-editor', 'theme' => $id, 'updated' => 1 ), admin_url( 'admin.php' ) ) ); exit;
	}

	private function import_uploaded( &$data ) {
		if ( empty( $_FILES['theme_file']['tmp_name'] ) ) { return 'adam-night'; }
		if ( ! empty( $_FILES['theme_file']['size'] ) && (int) $_FILES['theme_file']['size'] > 1048576 ) { wp_die( esc_html__( 'Theme files must be smaller than 1 MB.', 'adam-ui' ) ); }
		$decoded = json_decode( file_get_contents( $_FILES['theme_file']['tmp_name'] ), true );
		if ( ! is_array( $decoded ) || ( isset( $decoded['format'] ) && 'adam-ui-theme' !== $decoded['format'] ) ) { wp_die( esc_html__( 'This is not a valid ADAM UI theme file.', 'adam-ui' ) ); }
		$source = isset( $decoded['theme'] ) ? $decoded['theme'] : $decoded;
		if ( ! is_array( $source ) || empty( $source['tokens'] ) || ! is_array( $source['tokens'] ) ) { wp_die( esc_html__( 'The theme file does not contain design tokens.', 'adam-ui' ) ); }
		$source['mode'] = 'dark';
		$theme = $this->sanitize_theme( $source ); $theme['builtin'] = false;
		$id = $this->unique_id( sanitize_title( $theme['name'] ), $data['themes'] ); $data['themes'][ $id ] = $theme; return $id;
	}

	private function unique_id( $base, $themes ) { $base = $base ? $base : 'custom-theme'; $id = $base; $n = 2; while ( isset( $themes[ $id ] ) ) { $id = $base . '-' . $n++; } return $id; }

	public function export_theme() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'adam-ui' ) ); }
		check_admin_referer( 'adam_ui_export_theme' );
		$id = isset( $_GET['theme'] ) ? sanitize_key( wp_unslash( $_GET['theme'] ) ) : '';
		$theme = $this->get_theme( $id ); if ( ! $theme ) { wp_die( esc_html__( 'Theme not found.', 'adam-ui' ) ); }
		header( 'Content-Type: application/json; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="' . $id . '.json"' );
		echo wp_json_encode( array( 'format' => 'adam-ui-theme', 'version' => self::SCHEMA_VERSION, 'theme' => $theme ), JSON_PRETTY_PRINT ); exit;
	}

	private function build_schema() {
		$s = array();
		$add = function( $key, $section, $label, $type, $light, $dark, $contrast, $args = array() ) use ( &$s ) { $s[ $key ] = array_merge( array( 'section'=>$section,'label'=>$label,'type'=>$type,'light'=>$light,'dark'=>$dark,'contrast'=>$contrast,'unit'=>'','min'=>0,'max'=>100,'step'=>1,'editable'=>true ), $args ); };
		$colors = array(
			array('adam-header-bg','Header','Background','#ffffff','#161d16','#000000'),array('adam-header-nav-bg','Header','Navigation Background','#ffffff','#161d16','#000000'),array('adam-header-nav-text','Header','Navigation Text','#293124','#f1f4ed','#ffffff'),array('adam-header-active-bg','Header','Active Menu Background','#416900','#9bc85a','#ffffff'),array('adam-header-active-text','Header','Active Menu Text','#ffffff','#172107','#000000'),array('adam-header-hover-bg','Header','Hover Background','#edf5e2','#2c3929','#ffffff'),array('adam-header-hover-text','Header','Hover Text','#2f4d00','#d0ee9d','#000000'),array('adam-header-search-icon','Header','Search Icon','#416900','#d0ee9d','#ffffff'),array('adam-header-logo-bg','Header','Logo Area Background','transparent','transparent','#000000'),array('adam-header-border','Header','Bottom Border','#d8ddd2','#41493e','#ffffff'),
			array('adam-footer-bg','Footer','Background','#f3f5f0','#11170e','#000000'),array('adam-footer-heading','Footer','Heading Colour','#26331d','#f3f6ef','#ffffff'),array('adam-footer-text','Footer','Text Colour','#293124','#e7ebe3','#ffffff'),array('adam-footer-link','Footer','Link Colour','#416900','#d0ee9d','#ffff00'),array('adam-footer-link-hover','Footer','Link Hover Colour','#2f4d00','#ffffff','#00ffff'),array('adam-footer-social','Footer','Social Icons','#416900','#d0ee9d','#ffffff'),array('adam-footer-divider','Footer','Divider Colour','#d8ddd2','#41493e','#ffffff'),array('adam-footer-copyright','Footer','Copyright Text','#53604b','#c7cec0','#ffffff'),array('adam-footer-switcher-bg','Footer','Theme Switcher Background','#ffffff','#242b22','#000000'),array('adam-footer-switcher-text','Footer','Theme Switcher Text','#293124','#e7ebe3','#ffffff'),array('adam-footer-switcher-border','Footer','Theme Switcher Border','#d8ddd2','#596254','#ffffff'),
			array('adam-hero-bg','Hero','Background','#edf5e2','#172016','#000000'),array('adam-hero-eyebrow','Hero','Eyebrow Text','#416900','#b5db70','#ffff00'),array('adam-hero-heading','Hero','Heading','#26331d','#f3f6ef','#ffffff'),array('adam-hero-text','Hero','Paragraph','#53604b','#c7cec0','#ffffff'),array('adam-hero-primary','Hero','Primary Button','#416900','#9bc85a','#ffff00'),array('adam-hero-secondary','Hero','Secondary Button','#ffffff','#242b22','#000000'),
		);
		$section_defaults = array('standard'=>array('#ffffff','#141914'),'alternate'=>array('#f3f7f2','#222b23'),'feature'=>array('#e9f4d4','#205033'),'cta'=>array('#47592c','#394e2e'),'overlay'=>array('rgba(15, 26, 18, 0.78)','rgba(15, 26, 18, 0.86)'));
		$section_labels = array('standard'=>'Standard Section','alternate'=>'Alternate Section','feature'=>'Feature Strip','cta'=>'CTA Section','overlay'=>'Image Overlay Section');
		foreach($section_defaults as $type=>$backgrounds){$dark_text=in_array($type,array('cta','overlay'),true);$colors[]=array('adam-section-'.$type.'-bg','Sections',$section_labels[$type].' Background',$backgrounds[0],$backgrounds[1],'#000000');$colors[]=array('adam-section-'.$type.'-heading','Sections',$section_labels[$type].' Heading',$dark_text?'#ffffff':'#26331d','#f3f6ef','#ffffff');$colors[]=array('adam-section-'.$type.'-text','Sections',$section_labels[$type].' Body Text',$dark_text?'#ffffff':'#293124','#e7ebe3','#ffffff');$colors[]=array('adam-section-'.$type.'-link','Sections',$section_labels[$type].' Links',$dark_text?'#ffffff':'#416900','#d0ee9d','#ffff00');}
		$colors=array_merge($colors,array(
			array('adam-card-bg','Cards','Background','#ffffff','#1a2019','#000000'),array('adam-card-elevated-bg','Cards','Elevated Background','#ffffff','#242b22','#111111'),array('adam-card-border','Cards','Border','#d8ddd2','#41493e','#ffffff'),array('adam-card-heading','Cards','Heading','#26331d','#f3f6ef','#ffffff'),array('adam-card-text','Cards','Body','#53604b','#c7cec0','#ffffff'),array('adam-card-link','Cards','Links','#416900','#d0ee9d','#ffff00'),
			array('adam-form-label','Forms','Labels','#293124','#e7ebe3','#ffffff'),array('adam-form-input-bg','Forms','Inputs','#ffffff','#171d16','#000000'),array('adam-form-input-text','Forms','Input Text','#293124','#e7ebe3','#ffffff'),array('adam-form-placeholder','Forms','Placeholder','#6b7564','#aeb8a7','#ffffff'),array('adam-form-border','Forms','Borders','#aeb7a7','#596254','#ffffff'),array('adam-form-focus','Forms','Focus','#416900','#b5db70','#ffff00'),array('adam-form-button','Forms','Buttons','#416900','#9bc85a','#ffff00'),array('adam-form-button-text','Forms','Button Text','#ffffff','#172107','#000000'),
			array('adam-table-header-bg','Tables','Header','#edf5e2','#2c3929','#ffffff'),array('adam-table-row-bg','Tables','Row','#ffffff','#1a2019','#000000'),array('adam-table-alt-row-bg','Tables','Alternate Row','#f8f9f7','#263127','#111111'),array('adam-table-border','Tables','Borders','#d8ddd2','#41493e','#ffffff')
		));
		$button_defaults=array('primary'=>array('#416900','#ffffff','#416900','#2f4d00','#ffffff','#2f4d00'),'secondary'=>array('#edf5e2','#26331d','#aeb7a7','#dfeccf','#26331d','#416900'),'outline'=>array('transparent','#416900','#416900','#edf5e2','#2f4d00','#2f4d00'),'danger'=>array('#b42318','#ffffff','#b42318','#8f1c13','#ffffff','#8f1c13'),'success'=>array('#247a3b','#ffffff','#247a3b','#1d6230','#ffffff','#1d6230'));
		foreach($button_defaults as $variant=>$values){$label=ucfirst($variant);$dark=$values;if('primary'===$variant){$dark=array('#9bc85a','#172107','#9bc85a','#b5db70','#172107','#b5db70');}elseif('secondary'===$variant){$dark=array('#374238','#f2f4ee','#596254','#414e40','#ffffff','#759640');}elseif('outline'===$variant){$dark=array('transparent','#b5db70','#b5db70','#2c3929','#d0ee9d','#d0ee9d');}elseif('danger'===$variant){$dark=array('#ff8b82','#2b0e0b','#ff8b82','#ffaaa3','#2b0e0b','#ffaaa3');}elseif('success'===$variant){$dark=array('#78d68c','#102116','#78d68c','#9ee8ad','#102116','#9ee8ad');}$suffixes=array('bg'=>'Background','text'=>'Text','border'=>'Border','hover-bg'=>'Hover Background','hover-text'=>'Hover Text','hover-border'=>'Hover Border');$i=0;foreach($suffixes as $suffix=>$field_label){$colors[]=array('adam-btn-'.$variant.'-'.$suffix,'Buttons',$label.' '.$field_label,$values[$i],$dark[$i],'#ffffff');$i++;}}
		$notice_defaults=array('success'=>array('#edf8f0','#193724','#247a3b','#78d68c'),'info'=>array('#eaf4ff','#183248','#0a66c2','#79b8f3'),'warning'=>array('#fff7dc','#3d3217','#8a5a00','#f2c75c'),'error'=>array('#fff0ee','#402321','#b42318','#ff8b82'));
		foreach($notice_defaults as $type=>$values){$label='info'===$type?'Information':ucfirst($type);$colors[]=array('adam-notice-'.$type.'-bg','Notifications',$label.' Background',$values[0],$values[1],'#000000');$colors[]=array('adam-notice-'.$type.'-text','Notifications',$label.' Text',$values[2],$values[3],'#ffffff');$colors[]=array('adam-notice-'.$type.'-border','Notifications',$label.' Border',$values[2],$values[3],'#ffffff');}
		foreach ($colors as $c) { $add($c[0],$c[1],$c[2],'color',$c[3],$c[4],$c[5]); }
		$automatic_foregrounds = array(
			'adam-header-nav-text','adam-header-active-text','adam-header-hover-text','adam-header-search-icon',
			'adam-footer-heading','adam-footer-text','adam-footer-link','adam-footer-link-hover','adam-footer-social','adam-footer-copyright','adam-footer-switcher-text',
			'adam-hero-eyebrow','adam-hero-heading','adam-hero-text','adam-card-heading','adam-card-text','adam-card-link','adam-form-label','adam-form-input-text','adam-form-placeholder','adam-form-button-text',
		);
		foreach ( array('standard','alternate','feature','cta','overlay') as $section ) { foreach ( array('heading','text','link') as $role ) { $automatic_foregrounds[]='adam-section-'.$section.'-'.$role; } }
		foreach ( array('primary','secondary','danger','success') as $button ) { $automatic_foregrounds[]='adam-btn-'.$button.'-text'; $automatic_foregrounds[]='adam-btn-'.$button.'-hover-text'; }
		foreach ( array('success','info','warning','error') as $notice ) { $automatic_foregrounds[]='adam-notice-'.$notice.'-text'; }
		foreach ( $automatic_foregrounds as $key ) { if ( isset($s[$key]) ) { $s[$key]['editable']=false; $s[$key]['automatic']=true; } }
		$numbers = array(
			array('adam-card-radius','Cards','Radius',12,12,0,'px',0,40),array('adam-card-border-width','Cards','Border Width',1,1,2,'px',0,8),array('adam-card-shadow-strength','Cards','Shadow',.10,.35,0,'',0,.8,.01),array('adam-button-radius','Buttons','Radius',6,6,0,'px',0,40),array('adam-button-height','Buttons','Height',42,42,44,'px',28,72),array('adam-button-padding-x','Buttons','Horizontal Padding',18,18,20,'px',0,48),array('adam-button-padding-y','Buttons','Vertical Padding',10,10,10,'px',0,32),array('adam-input-radius','Forms','Input Radius',6,6,0,'px',0,40)
		);
		foreach ($numbers as $n) { $add($n[0],$n[1],$n[2],'number',(string)$n[3].$n[6],(string)$n[4].$n[6],(string)$n[5].$n[6],array('unit'=>$n[6],'min'=>$n[7],'max'=>$n[8],'step'=>isset($n[9])?$n[9]:1)); }
		$hidden = array('adam-radius-sm'=>'4px','adam-radius'=>'8px','adam-radius-lg'=>'16px','adam-badge-radius'=>'999px','adam-badge-padding-x'=>'10px','adam-badge-padding-y'=>'4px','adam-space-1'=>'0.25rem','adam-space-2'=>'0.5rem','adam-space-3'=>'0.75rem','adam-space-4'=>'1rem','adam-space-6'=>'1.5rem','adam-space-8'=>'2rem','adam-shadow-sm'=>'0 1px 3px rgb(0 0 0 / 0.12)','adam-shadow-md'=>'0 8px 24px rgb(0 0 0 / 0.14)','adam-shadow-lg'=>'0 20px 48px rgb(0 0 0 / 0.18)','adam-font-body'=>'system-ui, sans-serif','adam-font-heading'=>'inherit','adam-z-modal'=>'100000','adam-duration'=>'200ms');
		foreach ($hidden as $key=>$value) { $add($key,'Foundation',$key,'text',$value,$value,$value,array('editable'=>false)); }
		return $s;
	}
}
