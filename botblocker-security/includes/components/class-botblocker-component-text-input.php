<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TextInput - Renders a bbcs-field with label, tooltip, and an <input> inside a bbcs-field-box.
 *
 * The bbcs-field-box wrapper provides the same light-gray background styling as the CustomSelect dropdowns.
 * Supports editable mode (bbcs-field-box--editable) with optional suffix text.
 */
final class TextInput extends Base {

	/** @var string Input type (text, password, number, email). */
	private $input_type = 'text';

	/** @var string Input name attribute. */
	private $name = '';

	/** @var string Input value. */
	private $value = '';

	/** @var string Field label text (already translated). */
	private $label = '';

	/** @var string Tooltip text (already translated). */
	private $tooltip = '';

	/** @var string Placeholder text. */
	private $placeholder = '';

	/** @var bool Whether the input is readonly. */
	private $readonly = false;

	/** @var bool Whether the input is disabled. */
	private $disabled = false;

	/** @var string Additional CSS class for the <input> element. */
	private $input_class = '';

	/** @var string Optional numeric min attribute. */
	private $min = '';

	/** @var string Optional numeric max attribute. */
	private $max = '';

	/** @var string Optional numeric step attribute. */
	private $step = '';

	/** @var bool Whether to render the editable field-box variant. */
	private $editable = false;

	/** @var string Optional suffix text shown after the input (e.g. 'sec', 'hits'). */
	private $suffix = '';

	public function withType( string $type ): self {
		$this->input_type = $type;
		return $this;
	}

	public function withName( string $name ): self {
		$this->name = $name;
		return $this;
	}

	public function withValue( string $value ): self {
		$this->value = $value;
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

	public function withPlaceholder( string $placeholder ): self {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function withReadonly( bool $readonly = true ): self {
		$this->readonly = $readonly;
		return $this;
	}

	public function withDisabled( bool $disabled = true ): self {
		$this->disabled = $disabled;
		return $this;
	}

	public function withInputClass( string $class ): self {
		$this->input_class = $class;
		return $this;
	}

	public function withMin( string $min ): self {
		$this->min = $min;
		return $this;
	}

	public function withMax( string $max ): self {
		$this->max = $max;
		return $this;
	}

	public function withStep( string $step ): self {
		$this->step = $step;
		return $this;
	}

	/**
	 * Enable editable mode (bbcs-field-box--editable + bbcs-input--num class).
	 * For number inputs that are directly editable by the user.
	 */
	public function withEditable( bool $editable = true ): self {
		$this->editable = $editable;
		return $this;
	}

	/**
	 * Set suffix text displayed after the input (e.g. 'sec', 'hits', 'rpm').
	 */
	public function withSuffix( string $suffix ): self {
		$this->suffix = $suffix;
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

		$box_class = 'bbcs-field-box';
		if ( $this->editable ) {
			$box_class .= ' bbcs-field-box--editable';
		}

		$html .= '<div class="' . $box_class . '">';
		$html .= '<input type="' . self::escape( $this->input_type, 'attr' ) . '"'
			. ' class="bbcs-input' . ( $this->input_class !== '' ? ' ' . self::escape( $this->input_class, 'attr' ) : '' ) . '"'
			. ' name="' . self::escape( $this->name, 'attr' ) . '"'
			. ' value="' . self::escape( $this->value, 'attr' ) . '"'
			. ( $this->placeholder !== '' ? ' placeholder="' . self::escape( $this->placeholder, 'attr' ) . '"' : '' )
			. ( $this->readonly ? ' readonly' : '' )
			. ( $this->disabled ? ' disabled' : '' )
			. ( $this->min !== '' ? ' min="' . self::escape( $this->min, 'attr' ) . '"' : '' )
			. ( $this->max !== '' ? ' max="' . self::escape( $this->max, 'attr' ) . '"' : '' )
			. ( $this->step !== '' ? ' step="' . self::escape( $this->step, 'attr' ) . '"' : '' )
			. '>';

		if ( $this->suffix !== '' ) {
			$html .= '<span class="bbcs-field-suffix">' . self::escape( $this->suffix ) . '</span>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return self::output( $html, $return );
	}
}
