<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Textarea extends Base {
	protected $name = '';
	protected $id = '';
	protected $value = '';
	protected $input_class = '';
	protected $rows;
	protected $placeholder = '';
	protected $required = false;
	protected $disabled = false;
	protected $readonly = false;
	protected $label = '';
	protected $tooltip = '';
	protected $after = '';

	public function withName( string $name ): self {
		$this->name = $name;
		return $this;
	}

	public function withId( $id ): self {
		$this->id = $id;
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

	public function withRows( $rows ): self {
		$this->rows = $rows;
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

	public function withLabel( string $label ): self {
		$this->label = $label;
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
			'name'        => $this->name,
			'id'          => $this->id,
			'rows'        => $this->rows,
			'placeholder' => $this->placeholder !== '' ? $this->placeholder : null,
			'required'    => $this->required,
			'disabled'    => $this->disabled,
			'readonly'    => $this->readonly,
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
		$attrs['class'] = self::classes( array( 'bbcs-input', 'bbcs-input--textarea', $this->input_class ) );

		$html = '<div class="bbcs-field"' . $this->anchor_attr() . '>';

		if ( $this->label !== '' ) {
			$html .= '<div class="bbcs-field-label">';
			$html .= '<span>' . self::escape( $this->label ) . '</span>';
			if ( $this->tooltip !== '' ) {
				$html .= self::tooltip( $this->tooltip );
			}
			$html .= '</div>';
		}

		$html .= '<div class="bbcs-field-box bbcs-field-box--textarea">';
		$html .= '<textarea' . self::attrs( $attrs ) . '>' . self::escape( $this->value ) . '</textarea>';
		$html .= self::content( $this->after );
		$html .= '</div>';
		$html .= '</div>';

		return self::output( $html, $return );
	}
}
