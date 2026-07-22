<?php
/** Theme editor administration screen. */

defined( 'ABSPATH' ) || exit;

final class ADAM_UI_Theme_Editor {
	private $repository;
	private $assets;

	public function __construct( $repository, $assets ) { $this->repository = $repository; $this->assets = $assets; }
	public function register_hooks() { add_action( 'admin_menu', array( $this, 'register_menu' ), 20 ); add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 ); }
	public function register_menu() { add_submenu_page( 'adam-ui', __( 'Theme Editor', 'adam-ui' ), __( 'Theme Editor', 'adam-ui' ), 'manage_options', 'adam-ui-theme-editor', array( $this, 'render' ) ); }
	public function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'adam-ui-theme-editor' ) ) { return; }
		wp_enqueue_style( 'adam-ui-theme-editor', ADAM_UI_URL . 'assets/css/theme-editor.css', array( 'adam-ui-admin' ), ADAM_UI_VERSION );
		wp_enqueue_script( 'adam-ui-theme-editor', ADAM_UI_URL . 'assets/js/theme-editor.js', array(), ADAM_UI_VERSION, true );
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'adam-ui' ) ); }
		$themes = $this->repository->themes();
		$id = isset( $_GET['theme'] ) ? sanitize_key( wp_unslash( $_GET['theme'] ) ) : $this->repository->active_id( 'light' );
		if ( ! isset( $themes[ $id ] ) ) { $id = 'adam-light'; }
		$theme = $themes[ $id ]; $schema = $this->repository->schema(); $sections = array();
		foreach ( $schema as $key => $field ) { if ( ! empty( $field['editable'] ) ) { $sections[ $field['section'] ][ $key ] = $field; } }
		$section_order = array_flip( array( 'Header', 'Footer', 'Hero', 'Sections', 'Cards', 'Buttons', 'Forms', 'Tables', 'Notifications' ) );
		uksort(
			$sections,
			static function ( $left, $right ) use ( $section_order ) {
				return ( $section_order[ $left ] ?? PHP_INT_MAX ) <=> ( $section_order[ $right ] ?? PHP_INT_MAX );
			}
		);
		?>
		<div class="wrap adam-admin-page adam-theme-editor" data-adam-theme-editor>
			<header class="adam-page-header"><div><h1><?php esc_html_e( 'Theme Editor', 'adam-ui' ); ?></h1><p><?php esc_html_e( 'Configure the shared visual language used by every ADAM plugin.', 'adam-ui' ); ?></p></div></header>
			<div class="adam-theme-editor__toolbar adam-card">
				<label for="adam-theme-preset"><strong><?php esc_html_e( 'Theme', 'adam-ui' ); ?></strong></label>
				<select id="adam-theme-preset" class="adam-select" onchange="location.href=this.value"><?php foreach ( $themes as $theme_id => $item ) : ?><option value="<?php echo esc_url( add_query_arg( array( 'page'=>'adam-ui-theme-editor','theme'=>$theme_id ), admin_url( 'admin.php' ) ) ); ?>" <?php selected( $id, $theme_id ); ?>><?php echo esc_html( $item['name'] ); ?></option><?php endforeach; ?></select>
				<a class="adam-button adam-button-secondary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action'=>'adam_ui_export_theme','theme'=>$id ), admin_url( 'admin-post.php' ) ), 'adam_ui_export_theme' ) ); ?>"><?php esc_html_e( 'Export JSON', 'adam-ui' ); ?></a>
			</div>
			<div class="adam-theme-editor__split">
			<form class="adam-theme-editor__settings" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="adam_ui_theme_action"><input type="hidden" name="theme_operation" value="save"><input type="hidden" name="theme_id" value="<?php echo esc_attr( $id ); ?>"><?php wp_nonce_field( 'adam_ui_theme_action' ); ?>
				<div class="adam-card adam-theme-editor__identity"><label><?php esc_html_e( 'Name', 'adam-ui' ); ?><input class="adam-input" name="theme_name" value="<?php echo esc_attr( $theme['name'] ); ?>" <?php disabled( ! empty( $theme['builtin'] ) ); ?>></label><label><?php esc_html_e( 'Theme family', 'adam-ui' ); ?><select class="adam-select" name="theme_mode"><option value="light" <?php selected($theme['mode'],'light'); ?>><?php esc_html_e('Light','adam-ui'); ?></option><option value="dark" <?php selected($theme['mode'],'dark'); ?>><?php esc_html_e('Night','adam-ui'); ?></option></select></label><label><?php esc_html_e( 'Use for selector mode', 'adam-ui' ); ?><select class="adam-select" name="active_slot"><option value=""><?php esc_html_e('Do not change','adam-ui'); ?></option><option value="light"><?php esc_html_e('Light','adam-ui'); ?></option><option value="dark"><?php esc_html_e('Night','adam-ui'); ?></option></select></label></div>
				<p class="adam-theme-editor__colour-help"><?php esc_html_e( 'Colour fields accept any valid CSS colour, including HEX, RGB/RGBA, HSL/HSLA, named colours, and transparent.', 'adam-ui' ); ?></p>
				<?php foreach ( $sections as $section => $fields ) : ?>
					<details class="adam-card adam-theme-editor__section" open>
						<summary><?php echo esc_html( $section ); ?></summary>
						<?php $this->render_component_preview( $section ); ?>
						<div class="adam-theme-editor__fields">
						<?php foreach ( $fields as $key => $field ) : $value = $theme['tokens'][$key]; ?>
							<label class="adam-theme-editor__field">
								<span><?php echo esc_html( $field['label'] ); ?></span>
								<?php if ( 'color' === $field['type'] ) : ?>
									<span class="adam-css-color-control">
										<input class="adam-css-color-value adam-input" type="text" name="tokens[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" data-adam-token="--<?php echo esc_attr( $key ); ?>" data-invalid-message="<?php echo esc_attr__( 'Enter a valid CSS colour.', 'adam-ui' ); ?>" spellcheck="false">
										<input class="adam-css-color-picker" type="color" value="<?php echo esc_attr( preg_match( '/^#[0-9a-f]{6}$/i', $value ) ? $value : '#000000' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Pick %s', 'adam-ui' ), $field['label'] ) ); ?>">
										<span class="adam-css-color-swatch" aria-hidden="true"></span>
									</span>
								<?php else : $numeric = (float) $value; ?>
									<input type="range" name="tokens[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $numeric ); ?>" min="<?php echo esc_attr( $field['min'] ); ?>" max="<?php echo esc_attr( $field['max'] ); ?>" step="<?php echo esc_attr( $field['step'] ); ?>" data-adam-token="--<?php echo esc_attr( $key ); ?>" data-unit="<?php echo esc_attr( $field['unit'] ); ?>"><output><?php echo esc_html( $value ); ?></output>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
						</div>
					</details>
				<?php endforeach; ?>
				<div class="adam-theme-editor__actions"><button class="adam-button adam-button-primary" type="submit"><?php esc_html_e( 'Save Current Theme', 'adam-ui' ); ?></button><button class="adam-button adam-button-secondary" name="theme_operation" value="duplicate"><?php esc_html_e( 'Duplicate Theme', 'adam-ui' ); ?></button><?php if ( empty($theme['builtin']) ) : ?><button class="adam-button adam-button-danger" name="theme_operation" value="delete" onclick="return confirm('<?php echo esc_js(__('Delete this custom theme?','adam-ui')); ?>')"><?php esc_html_e('Delete Custom Theme','adam-ui'); ?></button><?php endif; ?></div>
			</form>
			<aside class="adam-theme-preview" data-adam-preview><div class="adam-preview-nav">ADAM <span><?php esc_html_e('Navigation','adam-ui'); ?></span></div><section class="adam-preview-hero"><small><?php esc_html_e('Live preview','adam-ui'); ?></small><h2><?php esc_html_e('A theme designed as one system.','adam-ui'); ?></h2><p><?php esc_html_e('Colours and component tokens update here immediately.','adam-ui'); ?></p><button class="adam-preview-button"><?php esc_html_e('Primary action','adam-ui'); ?></button></section><section class="adam-preview-content"><article class="adam-preview-card"><span class="adam-preview-badge">ADAM UI</span><h3><?php esc_html_e('Shared card','adam-ui'); ?></h3><p><?php esc_html_e('This preview uses the same design tokens exposed to every ADAM plugin.','adam-ui'); ?></p><input placeholder="<?php echo esc_attr__('Form input','adam-ui'); ?>"></article></section><footer class="adam-preview-footer"><?php esc_html_e('Footer surface','adam-ui'); ?></footer></aside>
			</div>
			<form class="adam-card adam-theme-import" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="adam_ui_theme_action"><input type="hidden" name="theme_operation" value="import"><?php wp_nonce_field('adam_ui_theme_action'); ?><h2><?php esc_html_e('Import theme','adam-ui'); ?></h2><input type="file" name="theme_file" accept="application/json,.json" required><button class="adam-button adam-button-secondary"><?php esc_html_e('Import JSON','adam-ui'); ?></button></form>
		</div><?php
	}

	/** Renders a compact, token-driven preview for one component family. */
	private function render_component_preview( $section ) {
		$previews = array(
			'Header' => '<div class="adam-mini-header"><strong>ADAM</strong><nav><span>Home</span><span class="is-active">Members</span><span>News</span></nav><span aria-hidden="true">⌕</span></div>',
			'Footer' => '<div class="adam-mini-footer"><strong>ADAM</strong><p>Community and events</p><a href="#">Footer link</a><div class="adam-mini-footer__switcher">Theme: System</div><small>© ADAM</small></div>',
			'Hero' => '<div class="adam-mini-hero"><small>ADAM ECOSYSTEM</small><h3>Build one consistent experience.</h3><p>A shared hero component for every ADAM page.</p><button>Primary</button><button class="is-secondary">Secondary</button></div>',
			'Sections' => '<div class="adam-mini-sections"><div class="is-standard"><b>Standard</b><span>Content</span></div><div class="is-alternate"><b>Alternate</b><span>Content</span></div><div class="is-feature"><b>Feature</b><span>Strip</span></div><div class="is-cta"><b>CTA</b><span>Action</span></div><div class="is-overlay"><b>Overlay</b><span>Image context</span></div></div>',
			'Cards' => '<article class="adam-mini-card"><small>Elevated card</small><h3>Shared card</h3><p>Cards update everywhere from one component definition.</p><a href="#">Learn more</a></article>',
			'Buttons' => '<div class="adam-mini-buttons"><button class="is-primary">Primary</button><button class="is-secondary">Secondary</button><button class="is-outline">Outline</button><button class="is-danger">Danger</button><button class="is-success">Success</button></div>',
			'Forms' => '<div class="adam-mini-form"><label>Member name<input placeholder="Enter a name"></label><button>Submit</button></div>',
			'Tables' => '<table class="adam-mini-table"><thead><tr><th>Member</th><th>Status</th></tr></thead><tbody><tr><td>Alex</td><td>Active</td></tr><tr><td>Maria</td><td>Pending</td></tr></tbody></table>',
			'Notifications' => '<div class="adam-mini-notices"><p class="is-success">Success notification</p><p class="is-info">Information notification</p><p class="is-warning">Warning notification</p><p class="is-error">Error notification</p></div>',
		);

		if ( isset( $previews[ $section ] ) ) {
			// Preview fragments are fixed plugin-owned markup and contain no user data.
			echo '<div class="adam-component-preview" data-adam-component-preview="' . esc_attr( strtolower( $section ) ) . '">' . $previews[ $section ] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
