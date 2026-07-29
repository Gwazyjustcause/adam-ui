<?php
/**
 * Accessible colour derivation for Night Theme components.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turns a small number of administrator-selected surfaces into complete,
 * readable component palettes.
 */
final class ADAM_UI_Color_Engine {
	/**
	 * Applies semantic component contracts to a token collection.
	 *
	 * @param array $tokens    Current design tokens.
	 * @param array $contracts Component intelligence contracts.
	 * @return array
	 */
	public function derive( $tokens, $contracts ) {
		foreach ( $contracts as $contract ) {
			if ( empty( $contract['background'] ) || empty( $tokens[ $contract['background'] ] ) ) {
				continue;
			}
			$tokens = $this->derive_contract( $tokens, $contract );
		}
		return $tokens;
	}

	/**
	 * Returns the accessible foreground used by compatibility callers.
	 *
	 * @param string $background CSS colour.
	 * @param float  $minimum    Minimum contrast ratio.
	 * @return string
	 */
	public function foreground( $background, $minimum = 4.5 ) {
		$background_rgb = $this->parse( $background );
		if ( ! $background_rgb ) {
			return '#f2f4ee';
		}
		$light = array( 247, 250, 244 );
		$dark  = array( 23, 33, 7 );
		$best  = $this->contrast( $light, $background_rgb ) >= $this->contrast( $dark, $background_rgb ) ? $light : $dark;
		if ( $this->contrast( $best, $background_rgb ) < $minimum ) {
			$best = $this->luminance( $background_rgb ) < 0.179 ? array( 255, 255, 255 ) : array( 0, 0, 0 );
		}
		return $this->hex( $best );
	}

	/** Returns the contrast ratio between two supported CSS colours. */
	public function contrast_ratio( $foreground, $background ) {
		$foreground = is_array( $foreground ) ? $foreground : $this->parse( $foreground );
		$background = is_array( $background ) ? $background : $this->parse( $background );
		return $foreground && $background ? $this->contrast( $foreground, $background ) : 1;
	}

	private function derive_contract( $tokens, $contract ) {
		$background = $this->parse( $tokens[ $contract['background'] ] );
		if ( ! $background ) {
			return $tokens;
		}

		$heading = $this->parse( $this->foreground( $tokens[ $contract['background'] ], 7 ) );
		$body    = $this->readable_tone( $background, $heading, 4.5, 0.76 );
		$muted   = $this->readable_tone( $background, $heading, 4.5, 0.58 );
		$accent  = $this->contract_accent( $tokens, $contract, $heading );
		$link    = $this->ensure_contrast( $accent, $background, 4.5, $heading );
		$icon    = $this->ensure_contrast( $accent, $background, 3, $heading );
		$border  = $this->mix( $background, $heading, 0.18 );
		$hover   = $this->mix( $background, $heading, 0.12 );
		$hover_text = $this->parse( $this->foreground( $this->hex( $hover ), 4.5 ) );
		$focus   = $this->ensure_contrast( $accent, $background, 3, $heading );
		$surface = $this->mix( $background, $heading, 0.075 );
		$disabled_background = $this->mix( $background, $heading, 0.08 );
		$disabled_text       = $this->readable_tone( $disabled_background, $heading, 3, 0.48 );

		$roles = array(
			'heading'             => $heading,
			'text'                => $body,
			'muted'               => $muted,
			'link'                => $link,
			'icon'                => $icon,
			'border'              => $border,
			'hover_background'    => $hover,
			'hover_text'          => $hover_text,
			'focus'               => $focus,
			'surface'             => $surface,
			'surface_text'        => $this->parse( $this->foreground( $this->hex( $surface ), 4.5 ) ),
			'disabled_background' => $disabled_background,
			'disabled_text'       => $disabled_text,
		);

		foreach ( $roles as $role => $colour ) {
			if ( empty( $contract[ $role ] ) || ! is_array( $contract[ $role ] ) ) {
				continue;
			}
			foreach ( $contract[ $role ] as $token ) {
				if ( isset( $tokens[ $token ] ) ) {
					$tokens[ $token ] = $this->hex( $colour );
				}
			}
		}

		if ( ! empty( $contract['shadow'] ) && is_array( $contract['shadow'] ) ) {
			$shadow = $this->luminance( $background ) < 0.25 ? 'rgb(0 0 0 / 0.42)' : 'rgb(0 0 0 / 0.2)';
			foreach ( $contract['shadow'] as $token ) {
				if ( isset( $tokens[ $token ] ) ) {
					$tokens[ $token ] = $shadow;
				}
			}
		}

		return $tokens;
	}

	private function contract_accent( $tokens, $contract, $fallback ) {
		if ( ! empty( $contract['accent'] ) && ! empty( $tokens[ $contract['accent'] ] ) ) {
			$accent = $this->parse( $tokens[ $contract['accent'] ] );
			if ( $accent ) {
				return $accent;
			}
		}
		if ( ! empty( $tokens['adam-btn-primary-bg'] ) ) {
			$accent = $this->parse( $tokens['adam-btn-primary-bg'] );
			if ( $accent ) {
				return $accent;
			}
		}
		return $fallback;
	}

	private function readable_tone( $background, $foreground, $minimum, $start ) {
		$candidate = $this->mix( $background, $foreground, $start );
		return $this->ensure_contrast( $candidate, $background, $minimum, $foreground );
	}

	private function ensure_contrast( $colour, $background, $minimum, $toward ) {
		$colour = $colour ? $colour : $toward;
		for ( $amount = 0; $amount <= 1; $amount += 0.04 ) {
			$candidate = $this->mix( $colour, $toward, $amount );
			if ( $this->contrast( $candidate, $background ) >= $minimum ) {
				return $candidate;
			}
		}
		return $toward;
	}

	private function mix( $from, $to, $amount ) {
		$amount = max( 0, min( 1, (float) $amount ) );
		return array(
			$from[0] + ( ( $to[0] - $from[0] ) * $amount ),
			$from[1] + ( ( $to[1] - $from[1] ) * $amount ),
			$from[2] + ( ( $to[2] - $from[2] ) * $amount ),
		);
	}

	private function contrast( $first, $second ) {
		$first  = $this->luminance( $first );
		$second = $this->luminance( $second );
		return ( max( $first, $second ) + 0.05 ) / ( min( $first, $second ) + 0.05 );
	}

	private function luminance( $rgb ) {
		$total = 0;
		foreach ( array( 0.2126, 0.7152, 0.0722 ) as $index => $weight ) {
			$channel = max( 0, min( 255, $rgb[ $index ] ) ) / 255;
			$channel = $channel <= 0.04045 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
			$total  += $channel * $weight;
		}
		return $total;
	}

	private function hex( $rgb ) {
		return sprintf( '#%02x%02x%02x', round( $rgb[0] ), round( $rgb[1] ), round( $rgb[2] ) );
	}

	/**
	 * Parses the editor's common CSS colour formats.
	 *
	 * Complex colour spaces remain valid stored CSS, but use the safe Night
	 * foreground fallback when they cannot be reduced reliably in PHP.
	 */
	private function parse( $colour ) {
		$colour = strtolower( trim( (string) $colour ) );
		$named  = array(
			'black' => array( 0, 0, 0 ),
			'white' => array( 255, 255, 255 ),
			'transparent' => array( 0, 0, 0 ),
			'red' => array( 255, 0, 0 ),
			'green' => array( 0, 128, 0 ),
			'blue' => array( 0, 0, 255 ),
			'navy' => array( 0, 0, 128 ),
			'gray' => array( 128, 128, 128 ),
			'grey' => array( 128, 128, 128 ),
		);
		if ( isset( $named[ $colour ] ) ) {
			return $named[ $colour ];
		}
		if ( preg_match( '/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $colour, $match ) ) {
			$hex = $match[1];
			if ( in_array( strlen( $hex ), array( 3, 4 ), true ) ) {
				$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
			}
			return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
		}
		if ( preg_match( '/^rgba?\(([^)]+)\)$/i', $colour, $match ) ) {
			$parts = preg_split( '/[\s,\/]+/', trim( $match[1] ) );
			if ( count( $parts ) >= 3 ) {
				return array_map(
					static function ( $part ) {
						return false !== strpos( $part, '%' ) ? 255 * (float) $part / 100 : max( 0, min( 255, (float) $part ) );
					},
					array_slice( $parts, 0, 3 )
				);
			}
		}
		if ( preg_match( '/^hsla?\(\s*([-+0-9.]+)(?:deg)?[\s,]+([-+0-9.]+)%[\s,]+([-+0-9.]+)%/i', $colour, $match ) ) {
			$h = fmod( (float) $match[1], 360 ) / 360;
			if ( $h < 0 ) {
				$h += 1;
			}
			$s = max( 0, min( 1, (float) $match[2] / 100 ) );
			$l = max( 0, min( 1, (float) $match[3] / 100 ) );
			if ( $s <= 0 ) {
				return array( 255 * $l, 255 * $l, 255 * $l );
			}
			$q   = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - ( $l * $s );
			$p   = ( 2 * $l ) - $q;
			$hue = static function ( $p, $q, $value ) {
				if ( $value < 0 ) { $value += 1; }
				if ( $value > 1 ) { $value -= 1; }
				if ( $value < 1 / 6 ) { return $p + ( $q - $p ) * 6 * $value; }
				if ( $value < 1 / 2 ) { return $q; }
				if ( $value < 2 / 3 ) { return $p + ( $q - $p ) * ( 2 / 3 - $value ) * 6; }
				return $p;
			};
			return array( 255 * $hue( $p, $q, $h + 1 / 3 ), 255 * $hue( $p, $q, $h ), 255 * $hue( $p, $q, $h - 1 / 3 ) );
		}
		if ( preg_match( '/^hwb\(\s*([-+0-9.]+)(?:deg)?[\s,]+([-+0-9.]+)%[\s,]+([-+0-9.]+)%/i', $colour, $match ) ) {
			$h = fmod( (float) $match[1], 360 );
			if ( $h < 0 ) { $h += 360; }
			$w = max( 0, min( 1, (float) $match[2] / 100 ) );
			$b = max( 0, min( 1, (float) $match[3] / 100 ) );
			if ( $w + $b >= 1 ) {
				$gray = 255 * $w / ( $w + $b );
				return array( $gray, $gray, $gray );
			}
			$sector = $h / 60;
			$x      = 1 - abs( fmod( $sector, 2 ) - 1 );
			$base   = array( 0, 0, 0 );
			if ( $sector < 1 ) { $base = array( 1, $x, 0 ); }
			elseif ( $sector < 2 ) { $base = array( $x, 1, 0 ); }
			elseif ( $sector < 3 ) { $base = array( 0, 1, $x ); }
			elseif ( $sector < 4 ) { $base = array( 0, $x, 1 ); }
			elseif ( $sector < 5 ) { $base = array( $x, 0, 1 ); }
			else { $base = array( 1, 0, $x ); }
			$chroma = 1 - $w - $b;
			return array_map( static function ( $channel ) use ( $chroma, $w ) { return 255 * ( ( $channel * $chroma ) + $w ); }, $base );
		}
		if ( preg_match( '/^okl(?:ab|ch)\(\s*([-+0-9.]+)(%)?[\s,]+([-+0-9.]+)[\s,]+([-+0-9.]+)(?:deg)?/i', $colour, $match ) ) {
			$l = (float) $match[1];
			if ( '%' === $match[2] ) { $l /= 100; }
			if ( 0 === strpos( $colour, 'oklch' ) ) {
				$c = (float) $match[3];
				$h = deg2rad( (float) $match[4] );
				$a = $c * cos( $h );
				$b = $c * sin( $h );
			} else {
				$a = (float) $match[3];
				$b = (float) $match[4];
			}
			$lms = array(
				pow( $l + ( 0.3963377774 * $a ) + ( 0.2158037573 * $b ), 3 ),
				pow( $l - ( 0.1055613458 * $a ) - ( 0.0638541728 * $b ), 3 ),
				pow( $l - ( 0.0894841775 * $a ) - ( 1.291485548 * $b ), 3 ),
			);
			return array(
				255 * $this->encode_srgb( ( 4.0767416621 * $lms[0] ) - ( 3.3077115913 * $lms[1] ) + ( 0.2309699292 * $lms[2] ) ),
				255 * $this->encode_srgb( ( -1.2684380046 * $lms[0] ) + ( 2.6097574011 * $lms[1] ) - ( 0.3413193965 * $lms[2] ) ),
				255 * $this->encode_srgb( ( -0.0041960863 * $lms[0] ) - ( 0.7034186147 * $lms[1] ) + ( 1.707614701 * $lms[2] ) ),
			);
		}
		if ( preg_match( '/^(?:lab|lch)\(\s*([-+0-9.]+)(%)?/i', $colour, $match ) ) {
			// L* is sufficient for a conservative light/dark decision when a
			// full colour-management extension is unavailable.
			$lightness = (float) $match[1] / ( '%' === $match[2] ? 100 : 100 );
			$gray      = 255 * max( 0, min( 1, $lightness ) );
			return array( $gray, $gray, $gray );
		}
		if ( preg_match( '/^color\(\s*(?:srgb|display-p3)\s+([-+0-9.]+%?)[\s,]+([-+0-9.]+%?)[\s,]+([-+0-9.]+%?)/i', $colour, $match ) ) {
			return array_map(
				static function ( $channel ) {
					return 255 * max( 0, min( 1, false !== strpos( $channel, '%' ) ? (float) $channel / 100 : (float) $channel ) );
				},
				array_slice( $match, 1, 3 )
			);
		}
		return null;
	}

	private function encode_srgb( $channel ) {
		$channel = $channel <= 0.0031308 ? 12.92 * $channel : ( 1.055 * pow( max( 0, $channel ), 1 / 2.4 ) ) - 0.055;
		return max( 0, min( 1, $channel ) );
	}
}
