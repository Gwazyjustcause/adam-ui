<?php
/**
 * Persistent global ADAM UI settings and user preferences.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides validated global settings and the theme storage contract.
 */
final class ADAM_UI_Settings {
	const OPTION_KEY    = 'adam_ui_settings';
	const USER_META_KEY = 'adam_ui_theme';

	/**
	 * Returns production defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults() {
		return array(
			'default_theme'          => 'light',
			'allow_visitor_switcher' => true,
			'allow_user_preferences' => true,
			'enable_system_mode'     => true,
			'enable_transitions'     => true,
			'enable_inspector'       => false,
		);
	}

	/**
	 * Returns stored settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all() {
		$stored = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), $this->defaults() );
	}

	/**
	 * Sanitizes the global settings option.
	 *
	 * @param mixed $input Submitted value.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$theme = isset( $input['default_theme'] ) ? sanitize_key( $input['default_theme'] ) : 'light';

		return array(
			'default_theme'          => in_array( $theme, array( 'light', 'dark' ), true ) ? $theme : 'light',
			'allow_visitor_switcher' => ! empty( $input['allow_visitor_switcher'] ),
			'allow_user_preferences' => ! empty( $input['allow_user_preferences'] ),
			'enable_system_mode'     => ! empty( $input['enable_system_mode'] ),
			'enable_transitions'     => ! empty( $input['enable_transitions'] ),
			'enable_inspector'       => ! empty( $input['enable_inspector'] ),
		);
	}

	/** Registers the global option and preference endpoint. */
	public function register_hooks() {
		add_action( 'init', array( $this, 'migrate_saved_settings' ), 1 );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'wp_ajax_adam_ui_save_theme', array( $this, 'save_user_preference' ) );
	}

	/** Migrates saved settings from the previous plugin identity once. */
	public function migrate_saved_settings() {
		if ( false !== get_option( self::OPTION_KEY, false ) ) {
			return;
		}

		$legacy = get_option( 'adam_' . 'inter' . 'face_settings', false );
		if ( is_array( $legacy ) ) {
			update_option( self::OPTION_KEY, $this->sanitize( $legacy ), false );
		}
	}

	/** Registers the settings option. */
	public function register_setting() {
		register_setting(
			'adam_ui_settings',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->defaults(),
			)
		);
	}

	/**
	 * Returns a boolean setting.
	 *
	 * @param string $key Boolean option key.
	 */
	public function is_enabled( $key ) {
		$settings = $this->all();
		return ! empty( $settings[ $key ] );
	}

	/**
	 * Returns the filterable website default theme.
	 *
	 * @param string $fallback Fallback theme.
	 * @return string
	 */
	public function get_default_theme_mode( $fallback = 'light' ) {
		$settings = $this->all();
		$theme    = isset( $settings['default_theme'] ) ? $settings['default_theme'] : $fallback;

		return (string) apply_filters( 'adam_ui_default_theme_mode', $theme );
	}

	/**
	 * Returns the website fallback when browser system preference is unavailable.
	 *
	 * @param string $fallback Fallback theme.
	 * @return string
	 */
	public function get_system_fallback( $fallback = 'light' ) {
		$theme = $this->get_default_theme_mode( $fallback );
		return (string) apply_filters( 'adam_ui_system_fallback', $theme );
	}

	/** Returns the saved preference for the current logged-in user. */
	public function get_user_preference() {
		if ( ! is_user_logged_in() || ! $this->is_enabled( 'allow_user_preferences' ) ) {
			return '';
		}

		$user_id = get_current_user_id();
		$mode    = sanitize_key( (string) get_user_meta( $user_id, self::USER_META_KEY, true ) );

		if ( '' === $mode ) {
			$mode = sanitize_key( (string) get_user_meta( $user_id, 'adam_' . 'inter' . 'face_theme', true ) );
			if ( in_array( $mode, array( 'light', 'dark', 'system' ), true ) ) {
				update_user_meta( $user_id, self::USER_META_KEY, $mode );
			}
		}
		if ( 'system' === $mode && ! $this->is_enabled( 'enable_system_mode' ) ) {
			return '';
		}

		return in_array( $mode, array( 'light', 'dark', 'system' ), true ) ? $mode : '';
	}

	/** Returns whether the current visitor may see and use the switcher. */
	public function can_change_theme() {
		if ( is_user_logged_in() && $this->is_enabled( 'allow_user_preferences' ) ) {
			return true;
		}

		return $this->is_enabled( 'allow_visitor_switcher' );
	}

	/**
	 * Returns browser storage configuration.
	 *
	 * Logged-in users use a user-meta adapter; visitors retain localStorage.
	 *
	 * @return array<string, mixed>
	 */
	public function get_storage_config() {
		$config = array(
			'adapter' => $this->is_enabled( 'allow_visitor_switcher' ) ? 'localStorage' : '',
			'key'     => 'adam-theme',
		);

		if ( is_user_logged_in() && $this->is_enabled( 'allow_user_preferences' ) ) {
			$config = array(
				'adapter' => 'userMeta',
				'key'     => self::USER_META_KEY,
				'initial' => $this->get_user_preference(),
				'saveUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => 'adam_ui_save_theme',
				'nonce'   => wp_create_nonce( 'adam_ui_theme_preference' ),
			);
		}

		return (array) apply_filters( 'adam_ui_theme_storage_config', $config );
	}

	/** Persists a logged-in user's theme preference without reloading. */
	public function save_user_preference() {
		if ( ! is_user_logged_in() || ! $this->is_enabled( 'allow_user_preferences' ) ) {
			wp_send_json_error( array( 'message' => __( 'Theme preferences are disabled.', 'adam-ui' ) ), 403 );
		}

		check_ajax_referer( 'adam_ui_theme_preference', 'nonce' );
		$mode = isset( $_POST['theme'] ) ? sanitize_key( wp_unslash( $_POST['theme'] ) ) : '';

		if ( '' === $mode ) {
			delete_user_meta( get_current_user_id(), self::USER_META_KEY );
			wp_send_json_success( array( 'theme' => '' ) );
		}

		if ( ! in_array( $mode, array( 'light', 'dark', 'system' ), true ) || ( 'system' === $mode && ! $this->is_enabled( 'enable_system_mode' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid theme preference.', 'adam-ui' ) ), 400 );
		}

		update_user_meta( get_current_user_id(), self::USER_META_KEY, $mode );
		wp_send_json_success( array( 'theme' => $mode ) );
	}
}
