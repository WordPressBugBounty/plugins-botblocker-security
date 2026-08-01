<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CustomSelect - Renders the bbcs-select custom dropdown used in settings tabs.
 *
 * Generates the trigger + dropdown menu + hidden input, wrapped in a bbcs-field with label and optional tooltip.
 * JS interactivity is handled by bbcs-multipage.js which binds to the .bbcs-select DOM structure.
 */
final class CustomSelect extends Base {

	/** @var string Input name attribute. */
	private $name = '';

	/** @var string Selected option value. */
	private $value = '';

	/** @var array<string, string> Options as value => label pairs. */
	private $options = array();

	/** @var string Field label text (already translated). */
	private $label = '';

	/** @var string Tooltip text (already translated). */
	private $tooltip = '';

	/** @var string Optional field description shown below the control. */
	private $description = '';

	/** @var bool Whether the field is disabled. */
	private $disabled = false;

	public function withName( string $name ): self {
		$this->name = $name;
		return $this;
	}

	public function withValue( $value ): self {
		$this->value = $value;
		return $this;
	}

	/**
	 * @param array<string, string> $options Value => Label map.
	 */
	public function withOptions( array $options ): self {
		$this->options = $options;
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

	public function withDescription( string $description ): self {
		$this->description = $description;
		return $this;
	}

	public function withDisabled( bool $disabled = true ): self {
		$this->disabled = $disabled;
		return $this;
	}

	public function render( bool $return = false ): string {
		$html = '<div class="bbcs-field"' . $this->anchor_attr() . '>';

		if ( $this->label !== '' ) {
			$html .= '<div class="bbcs-field-label">';
			$html .= '<span>' . self::escape( $this->label ) . '</span>';
			if ( $this->tooltip !== '' ) {
				$html .= self::tooltip( $this->tooltip );
			}
			$html .= '</div>';
		}

		$html .= '<div class="bbcs-select">';
		$html .= '<div class="bbcs-select-trigger">';

		$selected_label = $this->options[ $this->value ] ?? $this->value;
		$html .= '<span class="bbcs-select-value">' . self::escape( $selected_label ) . '</span>';
		$html .= '<span class="bbcs-select-caret">' . self::svg_icon( 'chevron', 'sm' ) . '</span>';
		$html .= '</div>';

		$html .= '<div class="bbcs-select-menu">';
		foreach ( $this->options as $val => $label ) {
			$selected_class = (string) $val === $this->value ? ' is-sel' : '';
			$html .= '<div class="bbcs-select-opt' . $selected_class . '" data-value="' . self::escape( $val, 'attr' ) . '">'
				. self::escape( $label )
				. '</div>';
		}
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<input type="hidden" name="' . self::escape( $this->name, 'attr' ) . '" value="' . self::escape( $this->value, 'attr' ) . '"'
			. ( $this->disabled ? ' disabled' : '' )
			. '>';

		if ( $this->description !== '' ) {
			$html .= '<div class="bbcs-field-desc">' . self::escape( $this->description ) . '</div>';
		}

		$html .= '</div>';

		return self::output( $html, $return );
	}
}
