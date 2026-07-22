<?php
/** Persistent theme definitions and generated design tokens. */

defined( 'ABSPATH' ) || exit;

final class ADAM_UI_Theme_Repository {
	const OPTION_KEY = 'adam_ui_themes';
	const SCHEMA_VERSION = 1;

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
			'active' => array( 'light' => 'adam-light', 'dark' => 'adam-night' ),
			'themes' => array(
				'adam-light' => $this->preset( 'ADAM Light', 'light', 'light', true ),
				'adam-night' => $this->preset( 'ADAM Night', 'dark', 'dark', true ),
				'high-contrast' => $this->preset( 'High Contrast', 'dark', 'contrast', true ),
			),
		);
	}

	private function preset( $name, $mode, $column, $builtin ) {
		$tokens = array();
		foreach ( $this->schema as $key => $field ) {
			$tokens[ $key ] = $field[ $column ];
		}
		return array( 'name' => $name, 'mode' => $mode, 'builtin' => $builtin, 'tokens' => $tokens );
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
		if ( ! empty( $saved['active'] ) ) {
			foreach ( array( 'light', 'dark' ) as $mode ) {
				$id = isset( $saved['active'][ $mode ] ) ? sanitize_key( $saved['active'][ $mode ] ) : '';
				if ( isset( $data['themes'][ $id ] ) ) { $data['active'][ $mode ] = $id; }
			}
		}
		return $data;
	}

	private function sanitize_theme( $theme, $fallback = null ) {
		$fallback = is_array( $fallback ) ? $fallback : $this->preset( 'Custom Theme', 'light', 'light', false );
		$theme = is_array( $theme ) ? $theme : array();
		$out = array(
			'name' => isset( $theme['name'] ) ? sanitize_text_field( $theme['name'] ) : $fallback['name'],
			'mode' => isset( $theme['mode'] ) && 'dark' === $theme['mode'] ? 'dark' : 'light',
			'builtin' => ! empty( $fallback['builtin'] ),
			'tokens' => array(),
		);
		$tokens = isset( $theme['tokens'] ) && is_array( $theme['tokens'] ) ? $theme['tokens'] : array();
		foreach ( $this->schema as $key => $field ) {
			$value = isset( $tokens[ $key ] ) ? wp_unslash( $tokens[ $key ] ) : $fallback['tokens'][ $key ];
			$out['tokens'][ $key ] = $this->sanitize_token( $value, $field );
		}
		return $out;
	}

	private function sanitize_token( $value, $field ) {
		if ( 'color' === $field['type'] ) {
			$color = sanitize_hex_color( $value );
			return $color ? $color : $field['light'];
		}
		if ( 'number' === $field['type'] ) {
			$value = (float) $value;
			$value = max( $field['min'], min( $field['max'], $value ) );
			return rtrim( rtrim( number_format( $value, 3, '.', '' ), '0' ), '.' ) . $field['unit'];
		}
		return preg_match( '/^[#(),.%\-\s\w\/]+$/', (string) $value ) ? trim( $value ) : $field['light'];
	}

	public function themes() { $all = $this->all(); return $all['themes']; }
	public function get_theme( $id ) { $themes = $this->themes(); return isset( $themes[ $id ] ) ? $themes[ $id ] : null; }
	public function active_id( $mode ) { $all = $this->all(); return isset( $all['active'][ $mode ] ) ? $all['active'][ $mode ] : 'adam-light'; }
	public function active_theme( $mode ) { return $this->get_theme( $this->active_id( $mode ) ); }
	public function tokens( $mode = 'light' ) { $theme = $this->active_theme( $mode ); return $theme ? $theme['tokens'] : array(); }
	public function token( $name, $mode = 'light', $fallback = '' ) {
		$name = ltrim( sanitize_key( str_replace( '_', '-', $name ) ), '-' );
		$tokens = $this->tokens( $mode );
		return isset( $tokens[ $name ] ) ? $tokens[ $name ] : $fallback;
	}

	public function generated_css() {
		$css = '';
		foreach ( array( 'light', 'dark' ) as $mode ) {
			$css .= "body.adam-theme-{$mode},.adam-theme-{$mode}{";
			foreach ( $this->tokens( $mode ) as $key => $value ) { $css .= '--' . $key . ':' . $value . ';'; }
			$css .= '--adam-text-primary:var(--adam-text);--adam-surface-card:var(--adam-surface);--adam-secondary:var(--adam-brand-secondary);--adam-button-primary:var(--adam-primary);';
			$css .= '--adam-card-shadow:0 .5rem 1.5rem rgb(0 0 0 / var(--adam-card-shadow-strength));}';
		}
		return $css;
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
				foreach ( $data['active'] as $mode => $active ) { if ( $active === $id ) { $data['active'][ $mode ] = 'dark' === $mode ? 'adam-night' : 'adam-light'; } }
				$id = 'adam-light';
			} elseif ( 'duplicate' === $operation ) {
				$new_id = $this->unique_id( sanitize_title( $data['themes'][ $id ]['name'] . '-copy' ), $data['themes'] );
				$data['themes'][ $new_id ] = $data['themes'][ $id ]; $data['themes'][ $new_id ]['name'] .= ' Copy'; $data['themes'][ $new_id ]['builtin'] = false; $id = $new_id;
			} elseif ( 'save' === $operation ) {
				$posted = array( 'name' => isset( $_POST['theme_name'] ) ? wp_unslash( $_POST['theme_name'] ) : $data['themes'][ $id ]['name'], 'mode' => isset( $_POST['theme_mode'] ) ? wp_unslash( $_POST['theme_mode'] ) : $data['themes'][ $id ]['mode'], 'tokens' => isset( $_POST['tokens'] ) ? $_POST['tokens'] : array() );
				$data['themes'][ $id ] = $this->sanitize_theme( $posted, $data['themes'][ $id ] );
				$slot = isset( $_POST['active_slot'] ) ? sanitize_key( wp_unslash( $_POST['active_slot'] ) ) : '';
				if ( in_array( $slot, array( 'light', 'dark' ), true ) ) { $data['active'][ $slot ] = $id; }
			}
		}
		update_option( self::OPTION_KEY, $data, false );
		wp_safe_redirect( add_query_arg( array( 'page' => 'adam-ui-theme-editor', 'theme' => $id, 'updated' => 1 ), admin_url( 'admin.php' ) ) ); exit;
	}

	private function import_uploaded( &$data ) {
		if ( empty( $_FILES['theme_file']['tmp_name'] ) ) { return 'adam-light'; }
		if ( ! empty( $_FILES['theme_file']['size'] ) && (int) $_FILES['theme_file']['size'] > 1048576 ) { wp_die( esc_html__( 'Theme files must be smaller than 1 MB.', 'adam-ui' ) ); }
		$decoded = json_decode( file_get_contents( $_FILES['theme_file']['tmp_name'] ), true );
		if ( ! is_array( $decoded ) || ( isset( $decoded['format'] ) && 'adam-ui-theme' !== $decoded['format'] ) ) { wp_die( esc_html__( 'This is not a valid ADAM UI theme file.', 'adam-ui' ) ); }
		$source = isset( $decoded['theme'] ) ? $decoded['theme'] : $decoded;
		if ( ! is_array( $source ) || empty( $source['tokens'] ) || ! is_array( $source['tokens'] ) ) { wp_die( esc_html__( 'The theme file does not contain design tokens.', 'adam-ui' ) ); }
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
			array('adam-primary','Brand','Primary Green','#416900','#9bc85a','#ffff00'), array('adam-brand-secondary','Brand','Secondary Green','#668c2b','#759640','#00ffff'), array('adam-accent','Brand','Accent','#75a83b','#b5db70','#ffff00'), array('adam-success','Brand','Success','#2f7d32','#70c873','#00ff00'), array('adam-warning','Brand','Warning','#a86400','#e4a74e','#ffff00'), array('adam-danger','Brand','Danger','#b42318','#f27c72','#ff4040'),
			array('adam-bg','Surfaces','Page Background','#ffffff','#10140f','#000000'), array('adam-hero-bg','Surfaces','Hero Background','#f2f7ea','#172016','#000000'), array('adam-section-bg','Surfaces','Content Sections','#ffffff','#141914','#000000'), array('adam-feature-bg','Surfaces','Feature Sections','#edf5e2','#202a1c','#111111'), array('adam-surface','Surfaces','Card Background','#ffffff','#1a2019','#000000'), array('adam-surface-elevated','Surfaces','Elevated Card','#ffffff','#242b22','#111111'), array('adam-nav-bg','Surfaces','Navigation','#ffffff','#121711','#000000'), array('adam-footer-bg','Surfaces','Footer','#253515','#11170e','#000000'),
			array('adam-heading','Typography','Heading','#26331d','#f3f6ef','#ffffff'), array('adam-text','Typography','Body','#293124','#e7ebe3','#ffffff'), array('adam-text-secondary','Typography','Secondary Text','#53604b','#c7cec0','#ffffff'), array('adam-text-muted','Typography','Muted Text','#6b7564','#aeb8a7','#ffffff'), array('adam-link','Typography','Links','#416900','#b5db70','#00ffff'), array('adam-link-hover','Typography','Link Hover','#2f4d00','#d0ee9d','#ffff00'),
			array('adam-border','Borders','Border','#d8ddd2','#41493e','#ffffff'), array('adam-divider','Borders','Divider','#e5e8e1','#323a30','#ffffff'), array('adam-input-border','Borders','Input Border','#aeb7a7','#596254','#ffffff'), array('adam-input-focus','Inputs','Focus Border','#416900','#b5db70','#ffff00'), array('adam-input-bg','Inputs','Background','#ffffff','#171d16','#000000')
		);
		foreach ($colors as $c) { $add($c[0],$c[1],$c[2],'color',$c[3],$c[4],$c[5]); }
		$numbers = array(
			array('adam-card-radius','Cards','Border Radius',12,12,0,'px',0,40), array('adam-card-border-width','Cards','Border Width',1,1,2,'px',0,8), array('adam-card-shadow-strength','Cards','Shadow Strength',.10,.35,0,'',0,.8,.01), array('adam-button-radius','Buttons','Radius',6,6,0,'px',0,40), array('adam-button-height','Buttons','Height',42,42,44,'px',28,72), array('adam-button-padding-x','Buttons','Horizontal Padding',18,18,20,'px',0,48), array('adam-button-padding-y','Buttons','Vertical Padding',10,10,10,'px',0,32), array('adam-input-radius','Inputs','Radius',6,6,0,'px',0,40), array('adam-badge-radius','Badges','Radius',999,999,0,'px',0,999), array('adam-badge-padding-x','Badges','Horizontal Padding',10,10,10,'px',0,32), array('adam-badge-padding-y','Badges','Vertical Padding',4,4,4,'px',0,20)
		);
		foreach ($numbers as $n) { $add($n[0],$n[1],$n[2],'number',(string)$n[3].$n[6],(string)$n[4].$n[6],(string)$n[5].$n[6],array('unit'=>$n[6],'min'=>$n[7],'max'=>$n[8],'step'=>isset($n[9])?$n[9]:1)); }
		$hidden = array('adam-radius-sm'=>'4px','adam-radius'=>'8px','adam-radius-lg'=>'16px','adam-space-1'=>'0.25rem','adam-space-2'=>'0.5rem','adam-space-3'=>'0.75rem','adam-space-4'=>'1rem','adam-space-6'=>'1.5rem','adam-space-8'=>'2rem','adam-shadow-sm'=>'0 1px 3px rgb(0 0 0 / 0.12)','adam-shadow-md'=>'0 8px 24px rgb(0 0 0 / 0.14)','adam-shadow-lg'=>'0 20px 48px rgb(0 0 0 / 0.18)','adam-font-body'=>'system-ui, sans-serif','adam-font-heading'=>'inherit','adam-z-modal'=>'100000','adam-duration'=>'200ms');
		foreach ($hidden as $key=>$value) { $add($key,'Foundation',$key,'text',$value,$value,$value,array('editable'=>false)); }
		return $s;
	}
}
