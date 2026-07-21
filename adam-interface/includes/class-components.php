<?php
/**
 * Reusable ADAM component markup helpers.
 *
 * @package ADAM_Interface
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generates accessible, theme-aware component markup for ADAM plugins.
 */
final class ADAM_Interface_Components {
	/**
	 * Generates a shared card.
	 *
	 * @param string $content Card content.
	 * @param array  $args Optional title, footer, allow_html, and attributes.
	 * @return string
	 */
	public function card( $content, $args = array() ) {
		$args = wp_parse_args( $args, array( 'title' => '', 'footer' => '', 'allow_html' => false, 'attributes' => array() ) );
		$attributes          = (array) $args['attributes'];
		$attributes['class'] = trim( 'adam-card ' . ( isset( $attributes['class'] ) ? $attributes['class'] : '' ) );
		$content = $args['allow_html'] ? wp_kses_post( $content ) : esc_html( $content );
		$header  = '' !== $args['title'] ? '<div class="adam-card-header"><h3>' . esc_html( $args['title'] ) . '</h3></div>' : '';
		$footer  = '' !== $args['footer'] ? '<div class="adam-card-footer">' . wp_kses_post( $args['footer'] ) . '</div>' : '';

		return '<section' . $this->attributes( $attributes ) . '>' . $header . '<div class="adam-card-body">' . $content . '</div>' . $footer . '</section>';
	}

	/**
	 * Builds an attribute string from a safe associative array.
	 *
	 * @param array<string, scalar> $attributes Attributes keyed by name.
	 * @return string
	 */
	private function attributes( $attributes ) {
		$output = '';

		foreach ( (array) $attributes as $name => $value ) {
			$name = strtolower( (string) $name );

			if ( ! preg_match( '/^(id|class|name|value|type|href|target|rel|title|role|tabindex|disabled|aria-[a-z-]+|data-[a-z0-9_-]+)$/', $name ) ) {
				continue;
			}

			if ( false === $value || null === $value ) {
				continue;
			}

			if ( true === $value ) {
				$output .= ' ' . esc_attr( $name );
				continue;
			}

			$output .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
		}

		return $output;
	}

	/**
	 * Generates an accessible notice.
	 *
	 * @param string $message Notice text.
	 * @param string $type    Notice type.
	 * @param array  $args    Optional attributes and allow_html flag.
	 * @return string
	 */
	public function notice( $message, $type = 'info', $args = array() ) {
		$type = in_array( $type, array( 'info', 'success', 'warning', 'error', 'danger' ), true ) ? $type : 'info';
		$type = 'danger' === $type ? 'error' : $type;
		$role = in_array( $type, array( 'error', 'warning' ), true ) ? 'alert' : 'status';
		$args = wp_parse_args( $args, array( 'attributes' => array(), 'allow_html' => false ) );
		$attributes          = (array) $args['attributes'];
		$attributes['class'] = trim( 'adam-alert adam-alert-' . $type . ' ' . ( isset( $attributes['class'] ) ? $attributes['class'] : '' ) );
		$attributes['role']  = isset( $attributes['role'] ) ? $attributes['role'] : $role;
		$content             = $args['allow_html'] ? wp_kses_post( $message ) : esc_html( $message );

		return '<div' . $this->attributes( $attributes ) . '>' . $content . '</div>';
	}

	/**
	 * Generates a button or link.
	 *
	 * @param string $label Button label.
	 * @param string $url   Optional link URL.
	 * @param array  $args  Optional variant, type, icon, and attributes.
	 * @return string
	 */
	public function button( $label, $url = '', $args = array() ) {
		$args = wp_parse_args(
			$args,
			array( 'variant' => 'primary', 'type' => 'button', 'icon' => '', 'attributes' => array() )
		);
		$variants = array( 'primary', 'secondary', 'success', 'warning', 'danger' );
		$variant  = in_array( $args['variant'], $variants, true ) ? $args['variant'] : 'primary';
		$attributes          = (array) $args['attributes'];
		$attributes['class'] = trim( 'adam-button adam-button-' . $variant . ' ' . ( isset( $attributes['class'] ) ? $attributes['class'] : '' ) );
		$content = '' !== $args['icon'] ? '<span class="adam-icon" aria-hidden="true">' . wp_kses_post( $args['icon'] ) . '</span>' : '';
		$content .= '<span>' . esc_html( $label ) . '</span>';

		if ( '' !== $url ) {
			$attributes['href'] = esc_url( $url );
			return '<a' . $this->attributes( $attributes ) . '>' . $content . '</a>';
		}

		$attributes['type'] = in_array( $args['type'], array( 'button', 'submit', 'reset' ), true ) ? $args['type'] : 'button';
		return '<button' . $this->attributes( $attributes ) . '>' . $content . '</button>';
	}

	/**
	 * Generates a statistic card.
	 *
	 * @param string $label Statistic label.
	 * @param string $value Statistic value.
	 * @param array  $args  Optional icon, trend, and attributes.
	 * @return string
	 */
	public function stat_card( $label, $value, $args = array() ) {
		$args = wp_parse_args( $args, array( 'icon' => '', 'trend' => '', 'attributes' => array() ) );
		$attributes          = (array) $args['attributes'];
		$attributes['class'] = trim( 'adam-stat-card ' . ( isset( $attributes['class'] ) ? $attributes['class'] : '' ) );
		$icon  = '' !== $args['icon'] ? '<span class="adam-stat-card__icon adam-icon" aria-hidden="true">' . wp_kses_post( $args['icon'] ) . '</span>' : '';
		$trend = '' !== $args['trend'] ? '<span class="adam-stat-card__trend">' . esc_html( $args['trend'] ) . '</span>' : '';

		return '<article' . $this->attributes( $attributes ) . '>' . $icon . '<div class="adam-stat-card__content"><span class="adam-stat-card__label">' . esc_html( $label ) . '</span><strong class="adam-stat-card__value">' . esc_html( $value ) . '</strong>' . $trend . '</div></article>';
	}

	/**
	 * Generates an empty state.
	 *
	 * @param string $title       Empty-state title.
	 * @param string $description Supporting description.
	 * @param array  $args        Optional icon, action_html, and attributes.
	 * @return string
	 */
	public function empty_state( $title, $description = '', $args = array() ) {
		$args = wp_parse_args( $args, array( 'icon' => '', 'action_html' => '', 'attributes' => array() ) );
		$attributes          = (array) $args['attributes'];
		$attributes['class'] = trim( 'adam-empty-state ' . ( isset( $attributes['class'] ) ? $attributes['class'] : '' ) );
		$icon        = '' !== $args['icon'] ? '<div class="adam-empty-state__icon adam-icon" aria-hidden="true">' . wp_kses_post( $args['icon'] ) . '</div>' : '';
		$description = '' !== $description ? '<p class="adam-empty-state__description">' . esc_html( $description ) . '</p>' : '';
		$action      = '' !== $args['action_html'] ? '<div class="adam-empty-state__actions">' . wp_kses_post( $args['action_html'] ) . '</div>' : '';

		return '<div' . $this->attributes( $attributes ) . '>' . $icon . '<h3 class="adam-empty-state__title">' . esc_html( $title ) . '</h3>' . $description . $action . '</div>';
	}

	/**
	 * Generates a loading indicator.
	 *
	 * @param string $label Screen-reader label.
	 * @param array  $args  Optional size and attributes.
	 * @return string
	 */
	public function loading_indicator( $label = '', $args = array() ) {
		$args = wp_parse_args( $args, array( 'size' => '', 'attributes' => array() ) );
		$size = in_array( $args['size'], array( 'small', 'large' ), true ) ? ' adam-loading--' . $args['size'] : '';
		$attributes                    = (array) $args['attributes'];
		$attributes['class']           = trim( 'adam-loading' . $size . ' ' . ( isset( $attributes['class'] ) ? $attributes['class'] : '' ) );
		$attributes['role']            = 'status';
		$attributes['aria-live']       = 'polite';
		$label = '' !== $label ? $label : __( 'A carregar…', 'adam-interface' );

		return '<span' . $this->attributes( $attributes ) . '><span class="adam-loading__spinner" aria-hidden="true"></span><span class="adam-sr-only">' . esc_html( $label ) . '</span></span>';
	}

	/**
	 * Generates a confirmation dialog controlled by the JavaScript API.
	 *
	 * @param string $message Confirmation message.
	 * @param array  $args    Optional title, labels, and attributes.
	 * @return string
	 */
	public function confirmation_dialog( $message, $args = array() ) {
		$args = wp_parse_args( $args, array( 'title' => __( 'Confirmar ação', 'adam-interface' ), 'confirm_label' => __( 'Confirmar', 'adam-interface' ), 'cancel_label' => __( 'Cancelar', 'adam-interface' ), 'attributes' => array() ) );
		$attributes                       = (array) $args['attributes'];
		$attributes['class']              = trim( 'adam-confirmation adam-modal ' . ( isset( $attributes['class'] ) ? $attributes['class'] : '' ) );
		$attributes['data-adam-confirm']  = true;
		$title_id = isset( $attributes['id'] ) ? sanitize_html_class( $attributes['id'] ) . '-title' : wp_unique_id( 'adam-confirm-title-' );
		$attributes['aria-labelledby']   = $title_id;

		return '<dialog' . $this->attributes( $attributes ) . '><div class="adam-modal__header"><h2 id="' . esc_attr( $title_id ) . '" class="adam-modal__title">' . esc_html( $args['title'] ) . '</h2></div><div class="adam-modal__body"><p>' . esc_html( $message ) . '</p></div><div class="adam-modal__footer"><button class="adam-button adam-button-secondary" type="button" value="cancel" data-adam-confirm-cancel>' . esc_html( $args['cancel_label'] ) . '</button><button class="adam-button adam-button-danger" type="button" value="confirm" data-adam-confirm-accept>' . esc_html( $args['confirm_label'] ) . '</button></div></dialog>';
	}
}
