<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ActionButton - Renders the bbcs-settings-row action button pattern used in tools tabs.
 *
 * Pattern: button with SVG sprite icon + label + help tooltip, all in a single row.
 */
final class ActionButton extends Base {

	/** @var string Default button variant. */
	const VARIANT_DEFAULT = 'default';

	/** @var string Danger (red) button variant. */
	const VARIANT_DANGER = 'danger';

	/** @var string Button id attribute. */
	private $btn_id = '';

	/** @var string SVG sprite icon name (without #bbcs-i- prefix). */
	private $icon = '';

	/** @var string Button label text (already translated). */
	private $label = '';

	/** @var string Tooltip text (already translated). */
	private $tooltip = '';

	/** @var string Button variant (constant from this class). */
	private $variant = self::VARIANT_DEFAULT;

	/** @var bool Whether the button is disabled. */
	private $disabled = false;

	/** @var array<string,string> Extra data-* attributes. */
	private $data_attrs = array();

	public function withId( $btn_id ): self {
		$this->btn_id = $btn_id;
		return $this;
	}

	/**
	 * Adds a data-* attribute (e.g. data-anchor="key") to the rendered button.
	 *
	 * @param string $key   Attribute name without the "data-" prefix.
	 * @param string $value Attribute value.
	 * @return self
	 */
	public function withDataAttribute( string $key, string $value ): self {
		$this->data_attrs[ $key ] = $value;
		return $this;
	}

	public function withIcon( string $icon ): self {
		$this->icon = $icon;
		return $this;
	}

	public function withLabel( string $label ): self {
		$this->label = $label;
		return $this;
	}

	public function withTooltip( string $tooltip ): self {
		$this->tooltip = $tooltip;
		return $this;
	}

	/**
	 * @param string $variant Use ACTION_BUTTON_VARIANT_DEFAULT or ACTION_BUTTON_VARIANT_DANGER.
	 */
	public function withVariant( string $variant ): self {
		$this->variant = $variant;
		return $this;
	}

	public function withDisabled( bool $disabled = true ): self {
		$this->disabled = $disabled;
		return $this;
	}

	public function render( bool $return = false ): string {
		$btn_class = 'bbcs-btn';
		if ( $this->variant === self::VARIANT_DANGER ) {
			$btn_class .= ' bbcs-btn--danger';
		}

		$html = '<div class="bbcs-settings-row">';
		$html .= '<button class="' . $btn_class . '" type="button"'
			. ( $this->btn_id !== '' ? ' id="' . self::escape( $this->btn_id, 'attr' ) . '"' : '' )
			. ( $this->disabled ? ' disabled' : '' )
			. ( $this->data_attrs !== array() ? ' ' . implode( ' ', array_map( static function ( $k, $v ) {
				return 'data-' . self::escape( $k, 'attr' ) . '="' . self::escape( $v, 'attr' ) . '"';
			}, array_keys( $this->data_attrs ), array_values( $this->data_attrs ) ) ) : '' )
			. '>';

		if ( $this->icon !== '' ) {
			$html .= self::svg_icon( $this->icon, 'sm' );
		}

		$html .= self::escape( $this->label );
		$html .= '</button>';

		if ( $this->tooltip !== '' ) {
			$html .= '<span class="bbcs-help">';
			$html .= '<span class="bbcs-help-q">?</span>';
			$html .= '<span class="bbcs-help-tip">' . self::escape( $this->tooltip ) . '</span>';
			$html .= '</span>';
		}

		$html .= '</div>';

		return self::output( $html, $return );
	}
}
