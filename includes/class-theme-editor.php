<?php
/** Component-based Night Theme editor administration screen. */

defined( 'ABSPATH' ) || exit;

final class ADAM_UI_Theme_Editor {
	private $repository;
	private $assets;
	private $components;

	public function __construct( $repository, $assets, $components = null ) {
		$this->repository = $repository;
		$this->assets     = $assets;
		$this->components = $components ? $components : $repository->component_registry();
	}

	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
	}

	public function register_menu() {
		add_submenu_page( 'adam-ui', __( 'Theme Editor', 'adam-ui' ), __( 'Theme Editor', 'adam-ui' ), 'manage_options', 'adam-ui-theme-editor', array( $this, 'render' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'adam-ui-theme-editor' ) ) {
			return;
		}
		wp_enqueue_style( 'adam-ui-theme-editor', ADAM_UI_URL . 'assets/css/theme-editor.css', array( 'adam-ui-admin' ), ADAM_UI_VERSION );
		wp_enqueue_script( 'adam-ui-theme-editor', ADAM_UI_URL . 'assets/js/theme-editor.js', array(), ADAM_UI_VERSION, true );
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'adam-ui' ) );
		}

		$themes = $this->repository->night_themes();
		$id     = isset( $_GET['theme'] ) ? sanitize_key( wp_unslash( $_GET['theme'] ) ) : $this->repository->active_id( 'dark' );
		if ( ! isset( $themes[ $id ] ) ) {
			$id = 'adam-night';
		}
		$theme      = $themes[ $id ];
		$schema     = $this->repository->schema();
		$components = $this->components->all();
		?>
		<div
			class="wrap adam-admin-page adam-theme-editor"
			data-adam-theme-editor
			data-adam-style-maps="<?php echo esc_attr( wp_json_encode( $this->components->style_maps() ) ); ?>"
			data-adam-intelligence="<?php echo esc_attr( wp_json_encode( $this->repository->intelligence_contracts() ) ); ?>"
		>
			<header class="adam-page-header">
				<div>
					<h1><?php esc_html_e( 'Night Theme Editor', 'adam-ui' ); ?></h1>
					<p><?php esc_html_e( 'Choose how reusable website components behave at night. Text, icons, borders, and interaction states adapt automatically.', 'adam-ui' ); ?></p>
				</div>
			</header>

			<div class="adam-theme-editor__toolbar adam-card">
				<label for="adam-theme-preset"><strong><?php esc_html_e( 'Night preset', 'adam-ui' ); ?></strong></label>
				<select id="adam-theme-preset" class="adam-select" onchange="location.href=this.value">
					<?php foreach ( $themes as $theme_id => $item ) : ?>
						<option value="<?php echo esc_url( add_query_arg( array( 'page' => 'adam-ui-theme-editor', 'theme' => $theme_id ), admin_url( 'admin.php' ) ) ); ?>" <?php selected( $id, $theme_id ); ?>><?php echo esc_html( $item['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<a class="adam-button adam-button-secondary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'adam_ui_export_theme', 'theme' => $id ), admin_url( 'admin-post.php' ) ), 'adam_ui_export_theme' ) ); ?>"><?php esc_html_e( 'Export JSON', 'adam-ui' ); ?></a>
			</div>

			<div class="adam-theme-editor__split">
				<form class="adam-theme-editor__settings" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="adam_ui_theme_action">
					<input type="hidden" name="theme_operation" value="save">
					<input type="hidden" name="theme_id" value="<?php echo esc_attr( $id ); ?>">
					<?php wp_nonce_field( 'adam_ui_theme_action' ); ?>

					<div class="adam-card adam-theme-editor__identity">
						<label><?php esc_html_e( 'Night preset name', 'adam-ui' ); ?><input class="adam-input" name="theme_name" value="<?php echo esc_attr( $theme['name'] ); ?>" <?php disabled( ! empty( $theme['builtin'] ) ); ?>></label>
						<p><?php esc_html_e( 'One component choice updates every page and plugin that uses that component.', 'adam-ui' ); ?></p>
					</div>

					<div class="adam-theme-editor__workspace">
						<nav class="adam-theme-editor__nav" aria-label="<?php echo esc_attr__( 'Theme components', 'adam-ui' ); ?>" role="tablist" aria-orientation="vertical">
							<?php $nav_index = 0; foreach ( $components as $slug => $component ) : $active = 0 === $nav_index++; ?>
								<button type="button" role="tab" id="adam-editor-tab-<?php echo esc_attr( $slug ); ?>" aria-controls="adam-editor-panel-<?php echo esc_attr( $slug ); ?>" aria-selected="<?php echo $active ? 'true' : 'false'; ?>" tabindex="<?php echo $active ? '0' : '-1'; ?>" data-adam-editor-tab="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $component['label'] ); ?></button>
							<?php endforeach; ?>
						</nav>

						<div class="adam-theme-editor__panels">
							<?php $panel_index = 0; foreach ( $components as $slug => $component ) : $active = 0 === $panel_index++; ?>
								<section class="adam-card adam-theme-editor__panel" role="tabpanel" id="adam-editor-panel-<?php echo esc_attr( $slug ); ?>" aria-labelledby="adam-editor-tab-<?php echo esc_attr( $slug ); ?>" data-adam-editor-panel="<?php echo esc_attr( $slug ); ?>"<?php echo $active ? '' : ' hidden'; ?>>
									<header class="adam-theme-editor__panel-header">
										<h2><?php echo esc_html( $component['label'] ); ?></h2>
										<p><?php echo esc_html( $component['description'] ); ?></p>
									</header>
									<?php $this->render_component_preview( $slug, $component ); ?>
									<?php foreach ( $component['controls'] as $group ) : ?>
										<fieldset class="adam-theme-editor__field-set">
											<legend><?php echo esc_html( $group['label'] ); ?></legend>
											<div class="adam-theme-editor__fields">
												<?php foreach ( $group['fields'] as $field ) :
													$key = sanitize_key( $field['token'] );
													if ( isset( $schema[ $key ], $theme['tokens'][ $key ] ) ) {
														$this->render_field( $key, $schema[ $key ], $theme['tokens'][ $key ], $field['label'], $slug );
													}
												endforeach; ?>
											</div>
										</fieldset>
									<?php endforeach; ?>
								</section>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="adam-theme-editor__actions">
						<button class="adam-button adam-button-primary" type="submit"><?php esc_html_e( 'Save Current Theme', 'adam-ui' ); ?></button>
						<button class="adam-button adam-button-secondary" name="theme_operation" value="duplicate"><?php esc_html_e( 'Duplicate Theme', 'adam-ui' ); ?></button>
						<?php if ( empty( $theme['builtin'] ) ) : ?>
							<button class="adam-button adam-button-danger" name="theme_operation" value="delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this custom theme?', 'adam-ui' ) ); ?>')"><?php esc_html_e( 'Delete Custom Theme', 'adam-ui' ); ?></button>
						<?php endif; ?>
					</div>
				</form>

				<aside class="adam-theme-preview" data-adam-preview>
					<div class="adam-preview-nav">ADAM <span><?php esc_html_e( 'Navigation', 'adam-ui' ); ?></span></div>
					<section class="adam-preview-hero"><small><?php esc_html_e( 'Live preview', 'adam-ui' ); ?></small><h2><?php esc_html_e( 'One connected design system.', 'adam-ui' ); ?></h2><p><?php esc_html_e( 'Component choices update this complete page immediately.', 'adam-ui' ); ?></p><button class="adam-preview-button"><?php esc_html_e( 'Primary action', 'adam-ui' ); ?></button></section>
					<section class="adam-preview-content"><article class="adam-preview-card"><span class="adam-preview-badge">ADAM UI</span><h3><?php esc_html_e( 'Shared card', 'adam-ui' ); ?></h3><p><?php esc_html_e( 'The same component language flows into every ADAM plugin.', 'adam-ui' ); ?></p><input placeholder="<?php echo esc_attr__( 'Form input', 'adam-ui' ); ?>"></article></section>
					<footer class="adam-preview-footer"><?php esc_html_e( 'Footer surface', 'adam-ui' ); ?></footer>
				</aside>
			</div>

			<form class="adam-card adam-theme-import" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="adam_ui_theme_action"><input type="hidden" name="theme_operation" value="import"><?php wp_nonce_field( 'adam_ui_theme_action' ); ?>
				<h2><?php esc_html_e( 'Import theme', 'adam-ui' ); ?></h2><input type="file" name="theme_file" accept="application/json,.json" required><button class="adam-button adam-button-secondary"><?php esc_html_e( 'Import JSON', 'adam-ui' ); ?></button>
			</form>
		</div>
		<?php
	}

	private function render_field( $key, $field, $value, $label, $component ) {
		?>
		<label class="adam-theme-editor__field">
			<span><?php echo esc_html( $label ); ?></span>
			<?php if ( 'color' === $field['type'] ) : ?>
				<span class="adam-css-color-control">
					<input class="adam-css-color-value adam-input" type="text" name="tokens[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" data-adam-token="--<?php echo esc_attr( $key ); ?>" data-invalid-message="<?php echo esc_attr__( 'Enter a valid CSS colour.', 'adam-ui' ); ?>" spellcheck="false">
					<input class="adam-css-color-picker" type="color" value="<?php echo esc_attr( preg_match( '/^#[0-9a-f]{6}$/i', $value ) ? $value : '#000000' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Pick %s', 'adam-ui' ), $label ) ); ?>">
					<span class="adam-css-color-swatch" aria-hidden="true"></span>
				</span>
			<?php elseif ( 'select' === $field['type'] ) : ?>
				<select class="adam-select" name="tokens[<?php echo esc_attr( $key ); ?>]" data-adam-token="--<?php echo esc_attr( $key ); ?>" data-adam-style-component="<?php echo esc_attr( $component ); ?>">
					<?php foreach ( $field['options'] as $option => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>><?php echo esc_html( $option_label ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
		</label>
		<?php
	}

	private function render_component_preview( $slug, $component ) {
		$preview = is_callable( $component['preview'] ) ? call_user_func( $component['preview'], $component ) : $component['preview'];
		$allowed = wp_kses_allowed_html( 'post' );
		foreach ( array( 'button', 'input', 'select', 'option' ) as $tag ) {
			$allowed[ $tag ] = array(
				'type'        => true,
				'class'       => true,
				'name'        => true,
				'value'       => true,
				'placeholder' => true,
				'checked'     => true,
				'selected'    => true,
				'disabled'    => true,
				'aria-label'  => true,
			);
		}
		echo '<div class="adam-component-preview" data-adam-component-preview="' . esc_attr( $slug ) . '">' . wp_kses( $preview, $allowed ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
