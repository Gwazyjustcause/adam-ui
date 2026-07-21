<?php
/**
 * ADAM Interface settings and diagnostics screens.
 *
 * @package ADAM_Interface
 */

defined( 'ABSPATH' ) || exit;

/** Owns the ADAM-only administration experience. */
final class ADAM_Interface_Admin {
	/**
	 * Settings service.
	 *
	 * @var ADAM_Interface_Settings
	 */
	private $settings;

	/**
	 * Theme manager.
	 *
	 * @var ADAM_Interface_Theme_Manager
	 */
	private $themes;

	/**
	 * Asset registry.
	 *
	 * @var ADAM_Interface_Asset_Registry
	 */
	private $assets;

	/**
	 * Ecosystem plugin registry.
	 *
	 * @var ADAM_Interface_Plugin_Registry
	 */
	private $plugins;

	/**
	 * Constructor.
	 *
	 * @param ADAM_Interface_Settings        $settings Settings service.
	 * @param ADAM_Interface_Theme_Manager   $themes   Theme manager.
	 * @param ADAM_Interface_Asset_Registry  $assets   Asset registry.
	 * @param ADAM_Interface_Plugin_Registry $plugins  Plugin registry.
	 */
	public function __construct( $settings, $themes, $assets, $plugins ) {
		$this->settings = $settings;
		$this->themes   = $themes;
		$this->assets   = $assets;
		$this->plugins  = $plugins;
	}

	/** Registers admin hooks. */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 1 );
		add_action( 'admin_notices', array( $this, 'render_compatibility_notices' ) );
	}

	/** Registers the ADAM Interface menu and diagnostics submenu. */
	public function register_menu() {
		add_menu_page( __( 'ADAM Interface', 'adam-interface' ), __( 'ADAM Interface', 'adam-interface' ), 'manage_options', 'adam-interface', array( $this, 'render_settings' ), 'dashicons-art', 81 );
		add_submenu_page( 'adam-interface', __( 'Settings', 'adam-interface' ), __( 'Settings', 'adam-interface' ), 'manage_options', 'adam-interface', array( $this, 'render_settings' ) );
		add_submenu_page( 'adam-interface', __( 'Diagnostics', 'adam-interface' ), __( 'Diagnostics', 'adam-interface' ), 'manage_options', 'adam-interface-diagnostics', array( $this, 'render_diagnostics' ) );
	}

	/**
	 * Enables shared styling only on the plugin's two admin screens.
	 *
	 * @param string $hook_suffix Current WordPress admin hook suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( false === strpos( (string) $hook_suffix, 'adam-interface' ) ) {
			return;
		}

		$this->themes->enable_admin_theme();
		$this->assets->enqueue_admin();
	}

	/** Renders global theme settings. */
	public function render_settings() {
		$this->authorize();
		$values = $this->settings->all();
		$name   = ADAM_Interface_Settings::OPTION_KEY;
		?>
		<div class="wrap adam-admin-page adam-interface-settings">
			<header class="adam-page-header"><div class="adam-page-header__content">
				<h1 class="adam-page-title"><?php esc_html_e( 'ADAM Interface', 'adam-interface' ); ?></h1>
				<p class="adam-page-description"><?php esc_html_e( 'Configure the global theme behavior for every ADAM-owned interface.', 'adam-interface' ); ?></p>
			</div></header>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'adam_interface_settings' ); ?>
				<section class="adam-card"><div class="adam-card-header"><h2><?php esc_html_e( 'Theme behavior', 'adam-interface' ); ?></h2></div><div class="adam-card-body">
					<div class="adam-interface-settings__field"><div><strong><?php esc_html_e( 'Website default', 'adam-interface' ); ?></strong><p class="adam-field__help"><?php esc_html_e( 'Used when no user or operating-system preference is available.', 'adam-interface' ); ?></p></div><div class="adam-interface-settings__control"><select class="adam-select" name="<?php echo esc_attr( $name ); ?>[default_theme]"><option value="light" <?php selected( $values['default_theme'], 'light' ); ?>><?php esc_html_e( 'Light', 'adam-interface' ); ?></option><option value="dark" <?php selected( $values['default_theme'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'adam-interface' ); ?></option></select></div></div>
					<?php $this->checkbox_field( $name, 'allow_visitor_switcher', __( 'Allow visitors to change theme', 'adam-interface' ), __( 'Anonymous visitors store their choice in localStorage.', 'adam-interface' ), $values ); ?>
					<?php $this->checkbox_field( $name, 'allow_user_preferences', __( 'Save logged-in user preferences', 'adam-interface' ), __( 'Stores the selected mode in WordPress user meta.', 'adam-interface' ), $values ); ?>
					<?php $this->checkbox_field( $name, 'enable_system_mode', __( 'Enable System mode', 'adam-interface' ), __( 'Follows the operating-system color preference when no explicit mode overrides it.', 'adam-interface' ), $values ); ?>
					<?php $this->checkbox_field( $name, 'enable_transitions', __( 'Enable transition animations', 'adam-interface' ), __( 'Uses the shared short transition tokens; reduced-motion preferences are always respected.', 'adam-interface' ), $values ); ?>
				</div></section>
				<?php submit_button( __( 'Save settings', 'adam-interface' ), 'primary adam-button adam-button-primary' ); ?>
			</form>
		</div>
		<?php
	}

	/** Renders developer diagnostics. */
	public function render_diagnostics() {
		$this->authorize();
		$plugins  = $this->plugins->all();
		$warnings = $this->plugins->get_warnings();
		?>
		<div class="wrap adam-admin-page adam-interface-settings">
			<header class="adam-page-header"><div class="adam-page-header__content"><h1 class="adam-page-title"><?php esc_html_e( 'ADAM Interface diagnostics', 'adam-interface' ); ?></h1><p class="adam-page-description"><?php esc_html_e( 'Runtime information for troubleshooting ADAM ecosystem integrations.', 'adam-interface' ); ?></p></div></header>
			<div class="adam-diagnostics-grid">
				<?php
				$this->diagnostic_card(
					__( 'Theme', 'adam-interface' ),
					array(
						__( 'Configured mode', 'adam-interface' ) => $this->themes->get_theme_mode(),
						__( 'Resolved theme', 'adam-interface' ) => $this->themes->get_resolved_theme(),
						__( 'Theme source', 'adam-interface' ) => $this->themes->get_theme_source(),
					)
				);
				?>
				<?php
				$this->diagnostic_card(
					__( 'Assets', 'adam-interface' ),
					array(
						__( 'CSS version', 'adam-interface' ) => ADAM_INTERFACE_VERSION,
						__( 'JS version', 'adam-interface' )  => ADAM_INTERFACE_VERSION,
						__( 'Loaded components', 'adam-interface' ) => '' !== implode( ', ', $this->assets->get_loaded_components() ) ? implode( ', ', $this->assets->get_loaded_components() ) : __( 'Core only', 'adam-interface' ),
					)
				);
				?>
			</div>
			<section class="adam-card"><div class="adam-card-header"><h2><?php esc_html_e( 'Registered ADAM plugins', 'adam-interface' ); ?></h2></div><div class="adam-card-body">
			<?php
			if ( empty( $plugins ) ) :
				?>
				<div class="adam-empty-state"><h3 class="adam-empty-state__title"><?php esc_html_e( 'No integrations registered', 'adam-interface' ); ?></h3></div>
				<?php
else :
	?>
				<div class="adam-table-responsive"><table class="adam-table"><thead><tr><th><?php esc_html_e( 'Plugin', 'adam-interface' ); ?></th><th><?php esc_html_e( 'Version', 'adam-interface' ); ?></th><th><?php esc_html_e( 'Requires Interface', 'adam-interface' ); ?></th><th><?php esc_html_e( 'Components', 'adam-interface' ); ?></th></tr></thead><tbody>
				<?php
				foreach ( $plugins as $plugin ) :
					?>
	<tr><td><?php echo esc_html( $plugin['name'] ); ?></td><td><?php echo esc_html( '' !== $plugin['version'] ? $plugin['version'] : '—' ); ?></td><td><?php echo esc_html( $plugin['requires_interface'] ); ?></td><td><?php echo esc_html( '' !== implode( ', ', $plugin['components'] ) ? implode( ', ', $plugin['components'] ) : '—' ); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
			</div></section>
			<section class="adam-card"><div class="adam-card-header"><h2><?php esc_html_e( 'Compatibility', 'adam-interface' ); ?></h2></div><div class="adam-card-body">
			<?php
			if ( empty( $warnings ) ) :
				?>
				<div class="adam-alert adam-alert-success" role="status"><?php esc_html_e( 'All registered plugins are compatible.', 'adam-interface' ); ?></div>
				<?php
else :
	?>
				<?php
				foreach ( $warnings as $warning ) :
					?>
	<div class="adam-alert adam-alert-warning" role="alert"><?php echo esc_html( $warning['message'] ); ?></div><?php endforeach; ?><?php endif; ?></div></section>
		</div>
		<?php
	}

	/** Shows non-fatal version warnings to administrators. */
	public function render_compatibility_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( $this->plugins->get_warnings() as $warning ) {
			echo '<div class="notice notice-warning"><p>' . esc_html( $warning['message'] ) . '</p></div>';
		}
	}

	/**
	 * Renders a boolean settings field.
	 *
	 * @param string $name        Option name.
	 * @param string $key         Setting key.
	 * @param string $label       Field label.
	 * @param string $description Field description.
	 * @param array  $values      Current settings.
	 */
	private function checkbox_field( $name, $key, $label, $description, $values ) {
		?>
		<div class="adam-interface-settings__field"><div><strong><?php echo esc_html( $label ); ?></strong><p class="adam-field__help"><?php echo esc_html( $description ); ?></p></div><div class="adam-interface-settings__control"><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $values[ $key ] ) ); ?> /> <?php esc_html_e( 'Enabled', 'adam-interface' ); ?></label></div></div>
		<?php
	}

	/**
	 * Renders a diagnostics card.
	 *
	 * @param string $title Card title.
	 * @param array  $rows  Label/value rows.
	 */
	private function diagnostic_card( $title, $rows ) {
		?>
		<section class="adam-card"><div class="adam-card-header"><h2><?php echo esc_html( $title ); ?></h2></div><div class="adam-card-body"><ul class="adam-diagnostics-list">
		<?php
		foreach ( $rows as $label => $value ) :
			?>
			<li><span><?php echo esc_html( $label ); ?></span><strong><?php echo esc_html( $value ); ?></strong></li><?php endforeach; ?></ul></div></section>
		<?php
	}

	/** Verifies access to Interface administration screens. */
	private function authorize() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'adam-interface' ) );
		}
	}
}
