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

	/** @var array<string, string|array{label: string, disabled?: bool}> Options as value => label pairs; an option may also be an array with a "disabled" flag. */
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
	 * @param array<string, string|array{label: string, disabled?: bool}> $options Value => Label map. An option value may map to an array with "label" and optional "disabled" keys.
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

	/**
	 * Resolve an option entry to its label and disabled state.
	 *
	 * @param string|array $opt Option value from the options map.
	 * @return array{label: string, disabled: bool}
	 */
	private static function option( $opt ): array {
		if ( is_array( $opt ) ) {
			return array(
				'label'    => isset( $opt['label'] ) ? (string) $opt['label'] : '',
				'disabled' => ! empty( $opt['disabled'] ),
			);
		}
		return array(
			'label'    => (string) $opt,
			'disabled' => false,
		);
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

		$selected_option = isset( $this->options[ $this->value ] ) ? self::option( $this->options[ $this->value ] ) : null;
		$selected_label  = $selected_option === null ? $this->value : $selected_option['label'];

		$html .= '<div class="bbcs-select' . ( $this->disabled ? ' is-disabled' : '' ) . '">';
		$html .= '<div class="bbcs-select-trigger">';

		$html .= '<span class="bbcs-select-value">' . self::escape( $selected_label ) . '</span>';
		$html .= '<span class="bbcs-select-caret">' . self::svg_icon( 'chevron', 'sm' ) . '</span>';
		$html .= '</div>';

		$html .= '<div class="bbcs-select-menu">';
		foreach ( $this->options as $val => $entry ) {
			$opt            = self::option( $entry );
			$selected_class = (string) $val === $this->value ? ' is-sel' : '';
			$disabled_class = $opt['disabled'] ? ' is-disabled' : '';
			$html .= '<div class="bbcs-select-opt' . $selected_class . $disabled_class . '" data-value="' . self::escape( $val, 'attr' ) . '"'
				. ( $opt['disabled'] ? ' data-disabled="1"' : '' )
				. '>'
				. self::escape( $opt['label'] )
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
