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
		$section_order = array_flip( array( 'Header', 'Hero', 'Sections', 'Cards', 'Buttons', 'Forms', 'Tables', 'Notifications', 'Footer' ) );
		uksort(
			$sections,
			static function ( $left, $right ) use ( $section_order ) {
				return ( $section_order[ $left ] ?? PHP_INT_MAX ) <=> ( $section_order[ $right ] ?? PHP_INT_MAX );
			}
		);
		$primary_groups = $this->get_primary_groups();
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
				<div class="adam-theme-editor__workspace">
					<nav class="adam-theme-editor__nav" aria-label="<?php echo esc_attr__( 'Theme components', 'adam-ui' ); ?>" role="tablist" aria-orientation="vertical">
						<?php $nav_index = 0; foreach ( array_keys( $primary_groups ) as $group_id ) : $is_active = 0 === $nav_index++; ?>
							<button type="button" role="tab" id="adam-editor-tab-<?php echo esc_attr( $group_id ); ?>" aria-controls="adam-editor-panel-<?php echo esc_attr( $group_id ); ?>" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>" tabindex="<?php echo $is_active ? '0' : '-1'; ?>" data-adam-editor-tab="<?php echo esc_attr( $group_id ); ?>"><?php echo esc_html( $primary_groups[ $group_id ]['label'] ); ?></button>
						<?php endforeach; ?>
						<button type="button" role="tab" id="adam-editor-tab-advanced" aria-controls="adam-editor-panel-advanced" aria-selected="false" tabindex="-1" data-adam-editor-tab="advanced"><?php esc_html_e( 'Advanced', 'adam-ui' ); ?></button>
					</nav>
					<div class="adam-theme-editor__panels">
						<?php $panel_index = 0; foreach ( $primary_groups as $group_id => $group ) : $is_active = 0 === $panel_index++; ?>
							<section class="adam-card adam-theme-editor__panel" role="tabpanel" id="adam-editor-panel-<?php echo esc_attr( $group_id ); ?>" aria-labelledby="adam-editor-tab-<?php echo esc_attr( $group_id ); ?>" data-adam-editor-panel="<?php echo esc_attr( $group_id ); ?>"<?php echo $is_active ? '' : ' hidden'; ?>>
								<header class="adam-theme-editor__panel-header"><h2><?php echo esc_html( $group['label'] ); ?></h2><p><?php echo esc_html( $group['description'] ); ?></p></header>
								<?php foreach ( $group['previews'] as $preview ) { $this->render_component_preview( $preview ); } ?>
								<?php foreach ( $group['sets'] as $set_label => $field_labels ) : ?>
									<fieldset class="adam-theme-editor__field-set"><legend><?php echo esc_html( $set_label ); ?></legend><div class="adam-theme-editor__fields">
										<?php foreach ( $field_labels as $key => $label ) { if ( isset( $schema[ $key ] ) ) { $this->render_field( $key, $schema[ $key ], $theme['tokens'][ $key ], $label ); } } ?>
									</div></fieldset>
								<?php endforeach; ?>
							</section>
						<?php endforeach; ?>
						<section class="adam-card adam-theme-editor__panel adam-theme-editor__advanced" role="tabpanel" id="adam-editor-panel-advanced" aria-labelledby="adam-editor-tab-advanced" data-adam-editor-panel="advanced" hidden>
							<header class="adam-theme-editor__panel-header"><h2><?php esc_html_e( 'Advanced', 'adam-ui' ); ?></h2><p><?php esc_html_e( 'Fine-tune every design token. These controls are optional and preserve the full editor capability.', 'adam-ui' ); ?></p></header>
							<p class="adam-theme-editor__colour-help"><?php esc_html_e( 'Colour fields accept any valid CSS colour, including HEX, RGB/RGBA, HSL/HSLA, named colours, and transparent.', 'adam-ui' ); ?></p>
							<?php foreach ( $sections as $section => $fields ) : ?>
								<details class="adam-theme-editor__advanced-section"><summary><?php echo esc_html( $section ); ?></summary><div class="adam-theme-editor__fields">
									<?php foreach ( $fields as $key => $field ) { $this->render_field( $key, $field, $theme['tokens'][ $key ] ); } ?>
								</div></details>
							<?php endforeach; ?>
						</section>
					</div>
				</div>
				<div class="adam-theme-editor__actions"><button class="adam-button adam-button-primary" type="submit"><?php esc_html_e( 'Save Current Theme', 'adam-ui' ); ?></button><button class="adam-button adam-button-secondary" name="theme_operation" value="duplicate"><?php esc_html_e( 'Duplicate Theme', 'adam-ui' ); ?></button><?php if ( empty($theme['builtin']) ) : ?><button class="adam-button adam-button-danger" name="theme_operation" value="delete" onclick="return confirm('<?php echo esc_js(__('Delete this custom theme?','adam-ui')); ?>')"><?php esc_html_e('Delete Custom Theme','adam-ui'); ?></button><?php endif; ?></div>
			</form>
			<aside class="adam-theme-preview" data-adam-preview><div class="adam-preview-nav">ADAM <span><?php esc_html_e('Navigation','adam-ui'); ?></span></div><section class="adam-preview-hero"><small><?php esc_html_e('Live preview','adam-ui'); ?></small><h2><?php esc_html_e('A theme designed as one system.','adam-ui'); ?></h2><p><?php esc_html_e('Colours and component tokens update here immediately.','adam-ui'); ?></p><button class="adam-preview-button"><?php esc_html_e('Primary action','adam-ui'); ?></button></section><section class="adam-preview-content"><article class="adam-preview-card"><span class="adam-preview-badge">ADAM UI</span><h3><?php esc_html_e('Shared card','adam-ui'); ?></h3><p><?php esc_html_e('This preview uses the same design tokens exposed to every ADAM plugin.','adam-ui'); ?></p><input placeholder="<?php echo esc_attr__('Form input','adam-ui'); ?>"></article></section><footer class="adam-preview-footer"><?php esc_html_e('Footer surface','adam-ui'); ?></footer></aside>
			</div>
			<form class="adam-card adam-theme-import" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="adam_ui_theme_action"><input type="hidden" name="theme_operation" value="import"><?php wp_nonce_field('adam_ui_theme_action'); ?><h2><?php esc_html_e('Import theme','adam-ui'); ?></h2><input type="file" name="theme_file" accept="application/json,.json" required><button class="adam-button adam-button-secondary"><?php esc_html_e('Import JSON','adam-ui'); ?></button></form>
		</div><?php
	}

	/** Returns the approachable first-level editor structure. */
	private function get_primary_groups() {
		return array(
			'header' => array(
				'label'       => __( 'Header', 'adam-ui' ),
				'description' => __( 'Set the main header surface and the accent used to show the current navigation item.', 'adam-ui' ),
				'previews'    => array( 'Header' ),
				'sets'        => array(
					__( 'Background', 'adam-ui' )        => array( 'adam-header-bg' => __( 'Header background', 'adam-ui' ) ),
					__( 'Navigation accent', 'adam-ui' ) => array( 'adam-header-active-bg' => __( 'Active item accent', 'adam-ui' ) ),
					__( 'Style', 'adam-ui' )             => array( 'adam-header-nav-text' => __( 'Navigation text', 'adam-ui' ), 'adam-header-border' => __( 'Bottom divider', 'adam-ui' ) ),
				),
			),
			'sections' => array(
				'label'       => __( 'Sections', 'adam-ui' ),
				'description' => __( 'Control the major surfaces that create hierarchy across pages.', 'adam-ui' ),
				'previews'    => array( 'Hero', 'Sections' ),
				'sets'        => array(
					__( 'Hero', 'adam-ui' )              => array( 'adam-hero-bg' => __( 'Hero background', 'adam-ui' ), 'adam-hero-heading' => __( 'Hero heading', 'adam-ui' ) ),
					__( 'Content', 'adam-ui' )           => array( 'adam-section-standard-bg' => __( 'Standard section', 'adam-ui' ), 'adam-section-alternate-bg' => __( 'Alternate section', 'adam-ui' ) ),
					__( 'Feature areas', 'adam-ui' )     => array( 'adam-section-feature-bg' => __( 'Feature strip', 'adam-ui' ), 'adam-section-cta-bg' => __( 'Call to action', 'adam-ui' ) ),
				),
			),
			'cards' => array(
				'label'       => __( 'Cards', 'adam-ui' ),
				'description' => __( 'Choose the card surfaces and overall visual weight.', 'adam-ui' ),
				'previews'    => array( 'Cards' ),
				'sets'        => array(
					__( 'Background', 'adam-ui' ) => array( 'adam-card-bg' => __( 'Card background', 'adam-ui' ), 'adam-card-elevated-bg' => __( 'Elevated card', 'adam-ui' ) ),
					__( 'Appearance', 'adam-ui' ) => array( 'adam-card-radius' => __( 'Corner roundness', 'adam-ui' ), 'adam-card-shadow-strength' => __( 'Shadow strength', 'adam-ui' ) ),
				),
			),
			'buttons' => array(
				'label'       => __( 'Buttons', 'adam-ui' ),
				'description' => __( 'Set the most common actions and the shared button shape.', 'adam-ui' ),
				'previews'    => array( 'Buttons' ),
				'sets'        => array(
					__( 'Actions', 'adam-ui' )    => array( 'adam-btn-primary-bg' => __( 'Primary action', 'adam-ui' ), 'adam-btn-secondary-bg' => __( 'Secondary action', 'adam-ui' ), 'adam-btn-outline-text' => __( 'Outline accent', 'adam-ui' ) ),
					__( 'Appearance', 'adam-ui' ) => array( 'adam-button-radius' => __( 'Corner roundness', 'adam-ui' ), 'adam-button-height' => __( 'Button height', 'adam-ui' ) ),
				),
			),
			'forms' => array(
				'label'       => __( 'Forms', 'adam-ui' ),
				'description' => __( 'Control the appearance of fields and their active focus state.', 'adam-ui' ),
				'previews'    => array( 'Forms' ),
				'sets'        => array(
					__( 'Fields', 'adam-ui' )     => array( 'adam-form-input-bg' => __( 'Input background', 'adam-ui' ), 'adam-form-border' => __( 'Input border', 'adam-ui' ) ),
					__( 'Interaction', 'adam-ui' ) => array( 'adam-form-focus' => __( 'Focus accent', 'adam-ui' ) ),
					__( 'Appearance', 'adam-ui' ) => array( 'adam-input-radius' => __( 'Corner roundness', 'adam-ui' ) ),
				),
			),
			'footer' => array(
				'label'       => __( 'Footer', 'adam-ui' ),
				'description' => __( 'Set the footer surface, content accent, and integrated theme selector.', 'adam-ui' ),
				'previews'    => array( 'Footer' ),
				'sets'        => array(
					__( 'Background', 'adam-ui' )     => array( 'adam-footer-bg' => __( 'Footer background', 'adam-ui' ) ),
					__( 'Style', 'adam-ui' )          => array( 'adam-footer-heading' => __( 'Heading', 'adam-ui' ), 'adam-footer-link' => __( 'Link accent', 'adam-ui' ) ),
					__( 'Theme switcher', 'adam-ui' ) => array( 'adam-footer-switcher-bg' => __( 'Switcher background', 'adam-ui' ) ),
				),
			),
		);
	}

	/** Renders one token control for both the simple and Advanced views. */
	private function render_field( $key, $field, $value, $label = '' ) {
		$label = $label ? $label : $field['label'];
		?>
		<label class="adam-theme-editor__field">
			<span><?php echo esc_html( $label ); ?></span>
			<?php if ( 'color' === $field['type'] ) : ?>
				<span class="adam-css-color-control">
					<input class="adam-css-color-value adam-input" type="text" name="tokens[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" data-adam-token="--<?php echo esc_attr( $key ); ?>" data-invalid-message="<?php echo esc_attr__( 'Enter a valid CSS colour.', 'adam-ui' ); ?>" spellcheck="false">
					<input class="adam-css-color-picker" type="color" value="<?php echo esc_attr( preg_match( '/^#[0-9a-f]{6}$/i', $value ) ? $value : '#000000' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Pick %s', 'adam-ui' ), $label ) ); ?>">
					<span class="adam-css-color-swatch" aria-hidden="true"></span>
				</span>
			<?php else : $numeric = (float) $value; ?>
				<input type="range" name="tokens[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $numeric ); ?>" min="<?php echo esc_attr( $field['min'] ); ?>" max="<?php echo esc_attr( $field['max'] ); ?>" step="<?php echo esc_attr( $field['step'] ); ?>" data-adam-token="--<?php echo esc_attr( $key ); ?>" data-unit="<?php echo esc_attr( $field['unit'] ); ?>"><output><?php echo esc_html( $value ); ?></output>
			<?php endif; ?>
		</label>
		<?php
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
		// Use entities so preview symbols remain stable across filesystem encodings.
		$previews['Header'] = '<div class="adam-mini-header"><strong>ADAM</strong><nav><span>Home</span><span class="is-active">Members</span><span>News</span></nav><span aria-hidden="true">&#128269;</span></div>';
		$previews['Footer'] = '<div class="adam-mini-footer"><strong>ADAM</strong><p>Community and events</p><a href="#">Footer link</a><div class="adam-mini-footer__switcher">Theme: System</div><small>&copy; ADAM</small></div>';

		if ( isset( $previews[ $section ] ) ) {
			// Preview fragments are fixed plugin-owned markup and contain no user data.
			echo '<div class="adam-component-preview" data-adam-component-preview="' . esc_attr( strtolower( $section ) ) . '">' . $previews[ $section ] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
