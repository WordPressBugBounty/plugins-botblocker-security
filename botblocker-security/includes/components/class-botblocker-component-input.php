<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Input extends Base {
	protected $type = 'text';
	protected $name = '';
	protected $id = '';
	protected $label = '';
	protected $value = '';
	protected $input_class = '';
	protected $placeholder = '';
	protected $required = false;
	protected $disabled = false;
	protected $readonly = false;
	protected $min;
	protected $max;
	protected $step;
	protected $tooltip = '';
	protected $after = '';

	public function withType( string $type ): self {
		$this->type = $type;
		return $this;
	}

	public function withName( string $name ): self {
		$this->name = $name;
		return $this;
	}

	public function withLabel( string $label ): self {
		$this->label = $label;
		return $this;
	}

	public function withValue( $value ): self {
		$this->value = $value;
		return $this;
	}

	public function withInputClass( string $input_class ): self {
		$this->input_class = $input_class;
		return $this;
	}

	public function withPlaceholder( string $placeholder ): self {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function withRequired( bool $required ): self {
		$this->required = $required;
		return $this;
	}

	public function withDisabled( bool $disabled ): self {
		$this->disabled = $disabled;
		return $this;
	}

	public function withReadonly( bool $readonly ): self {
		$this->readonly = $readonly;
		return $this;
	}

	public function withMin( $min ): self {
		$this->min = $min;
		return $this;
	}

	public function withMax( $max ): self {
		$this->max = $max;
		return $this;
	}

	public function withStep( $step ): self {
		$this->step = $step;
		return $this;
	}

	public function withTooltip( string $tooltip ): self {
		$this->tooltip = $tooltip;
		return $this;
	}

	public function withAfter( $after ): self {
		$this->after = $after;
		return $this;
	}

	public function toHtmlAttrs(): array {
		$attrs = array(
			'type'        => $this->type,
			'name'        => $this->name,
			'id'          => $this->id,
			'value'       => $this->value,
			'placeholder' => $this->placeholder !== '' ? $this->placeholder : null,
			'required'    => $this->required,
			'disabled'    => $this->disabled,
			'readonly'    => $this->readonly,
			'min'         => $this->min,
			'max'         => $this->max,
			'step'        => $this->step,
		);
		return array_merge(
			array_filter( $attrs, function ( $v ) { return $v !== null; } ),
			parent::toHtmlAttrs()
		);
	}

	public function cssClasses( string $base = '' ): array {
		return array_merge(
			parent::cssClasses( $base ),
			$this->class !== '' ? array( $this->class ) : array()
		);
	}

	public function render( bool $return = false ): string {
		$attrs = $this->toHtmlAttrs();
		$attrs['class'] = self::classes( array( 'bbcs_text_input_input', $this->input_class ) );

		$html  = '<div class="' . self::classes( $this->cssClasses( 'bbcs_text_input mb-2' ) ) . '">';
		if ( $this->label !== '' ) {
			$html .= '<div class="bbcs_label_input_box"><span class="bbcs-label-input-small">' . self::escape( $this->label ) . '</span>' . self::tooltip( $this->tooltip ) . '</div>';
		}
		$html .= '<div class="bbcs_text_input_inner" style="position: relative;"><input' . self::attrs( $attrs ) . '>';
		$html .= self::content( $this->after );
		$html .= '</div></div>';

		return self::output( $html, $return );
	}
}
