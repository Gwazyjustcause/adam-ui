<?php
/**
 * WordPress widget adapter for the reusable Theme Switcher.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

/** Places the shared Theme Switcher in any registered widget area. */
final class ADAM_UI_Theme_Switcher_Widget extends WP_Widget {
	/** Creates the widget definition after WordPress has initialized widgets. */
	public function __construct() {
		parent::__construct(
			'adam_ui_theme_switcher',
			__( 'ADAM UI — Theme Switcher', 'adam-ui' ),
			array(
				'description' => __( 'Displays the shared Light, Night, and System theme control.', 'adam-ui' ),
			)
		);
	}

	/** Renders the widget. */
	public function widget( $args, $instance ) {
		if ( 'widget' !== adam_ui()->get_settings()->get_theme_switcher_placement() ) {
			return;
		}

		echo isset( $args['before_widget'] ) ? $args['before_widget'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( ! empty( $instance['title'] ) ) {
			echo isset( $args['before_title'] ) ? $args['before_title'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo esc_html( $instance['title'] );
			echo isset( $args['after_title'] ) ? $args['after_title'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo adam_ui_get_theme_manager()->get_theme_switcher_markup( array( 'context' => 'widget' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo isset( $args['after_widget'] ) ? $args['after_widget'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/** Renders the widget title field. */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? (string) $instance['title'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'adam-ui' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	/** Sanitizes widget settings. */
	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
		);
	}
}
