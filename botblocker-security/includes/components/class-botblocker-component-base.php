<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Base {
	protected $class = '';
	protected $id = '';
	protected $anchor = '';
	protected $attrs = array();
	protected $data = array();
	protected $content;

	public static function make() {
		return new static();
	}

	public function withClass( string $class ): self {
		$this->class = $class;
		return $this;
	}

	public function withId( $id ): self {
		$this->id = $id;
		return $this;
	}

	public function withAttrs( array $attrs ): self {
		$this->attrs = $attrs;
		return $this;
	}

	public function withData( array $data ): self {
		$this->data = $data;
		return $this;
	}

	/**
	 * Set a data-anchor attribute on the component's root element.
	 * Used by the shared helpers JS to find and highlight the
	 * correct row when a user jumps to a setting (focus=key).
	 */
	public function withAnchor( string $anchor ): self {
		$this->anchor = $anchor;
		return $this;
	}

	public function block( callable $slot ): string {
		$this->content = $slot;
		return $this->render( true );
	}

	abstract public function render( bool $return = false ): string;

	protected static function get( array $data, string $key, $default = '' ) {
		return array_key_exists( $key, $data ) ? $data[ $key ] : $default;
	}

	public static function escape( $value, string $context = 'html' ): string {
		if ( $context === 'attr' ) {
			return esc_attr( (string) $value );
		}

		if ( $context === 'url' ) {
			return esc_url( (string) $value );
		}

		return esc_html( (string) $value );
	}

	public static function classes( $classes ): string {
		if ( is_string( $classes ) ) {
			$classes = preg_split( '/\s+/', $classes );
		}

		if ( ! is_array( $classes ) ) {
			return '';
		}

		$clean = array();
		foreach ( $classes as $key => $value ) {
			if ( is_int( $key ) ) {
				$class = (string) $value;
			} elseif ( $value ) {
				$class = (string) $key;
			} else {
				continue;
			}

			foreach ( preg_split( '/\s+/', trim( $class ) ) as $class_name ) {
				if ( $class_name !== '' ) {
					$clean[] = sanitize_html_class( $class_name );
				}
			}
		}

		return implode( ' ', array_filter( $clean ) );
	}

	public static function attrs( array $attrs ): string {
		$html = '';
		foreach ( $attrs as $name => $value ) {
			if ( $value === false || $value === null || $value === '' ) {
				continue;
			}

			$name = preg_replace( '/[^a-zA-Z0-9_:\-]/', '', (string) $name );
			if ( $name === '' ) {
				continue;
			}

			if ( $value === true ) {
				$html .= ' ' . $name;
				continue;
			}

			$html .= ' ' . $name . '="' . self::escape( $value, 'attr' ) . '"';
		}

		return $html;
	}

	protected static function data_attrs( array $data ): array {
		$attrs = array();
		foreach ( $data as $name => $value ) {
			$attrs[ 'data-' . str_replace( '_', '-', (string) $name ) ] = $value;
		}

		return $attrs;
	}

	/**
	 * Return the data-anchor HTML attribute string, or empty string if not set.
	 */
	protected function anchor_attr(): string {
		if ( $this->anchor === '' ) {
			return '';
		}
		return ' data-anchor="' . self::escape( $this->anchor, 'attr' ) . '"';
	}

	/**
	 * Renders an SVG sprite icon.
	 *
	 * @param string $name Icon name (without #bbcs-i- prefix).
	 * @param string $size Size class suffix (e.g. 'sm', 'lg'). Default: 'sm'.
	 * @param string $extra_class Additional CSS classes for the <svg> element.
	 * @return string The SVG HTML string.
	 */
	protected static function svg_icon( string $name, string $size = 'sm', string $extra_class = '' ): string {
		$class = self::classes( array( 'bbcs-ico', 'bbcs-ico--' . $size, $extra_class ) );
		return sprintf(
			'<svg class="%s" aria-hidden="true"><use href="#bbcs-i-%s"></use></svg>',
			$class,
			self::escape( $name, 'attr' )
		);
	}

	/**
	 * Renders a standardized tooltip using the bbcs-help pattern.
	 *
	 * Output: <span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip">{text}</span></span>
	 *
	 * This matches the tooltip style used by ToggleOption::withTooltip().
	 *
	 * @param string $tooltip Tooltip text (already translated).
	 * @return string Empty string if tooltip is empty, otherwise the help HTML.
	 */
	public static function tooltip( string $tooltip ): string {
		if ( $tooltip === '' ) {
			return '';
		}

		return '<span class="bbcs-help">'
			. '<span class="bbcs-help-q">?</span>'
			. '<span class="bbcs-help-tip">' . self::escape( $tooltip ) . '</span>'
			. '</span>';
	}

	protected static function content( $content ): string {
		if ( is_callable( $content ) ) {
			ob_start();
			$content();
			return (string) ob_get_clean();
		}

		return (string) $content;
	}

	protected static function output( string $html, bool $return ): string {
		if ( ! $return ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML assembled with per-part escaping inside the component.
		}

		return $html;
	}

	public function toHtmlAttrs(): array {
		$result = $this->attrs;
		foreach ( $this->data as $key => $value ) {
			$result[ 'data-' . $key ] = $value;
		}

		return $result;
	}

	public function cssClasses( string $base = '' ): array {
		if ( $base !== '' ) {
			return array( $base );
		}

		return array();
	}
}
