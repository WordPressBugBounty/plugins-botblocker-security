<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Select extends Base {
	protected $name = '';
	protected $id = '';
	protected $value = '';
	protected $input_class = '';
	protected $required = false;
	protected $disabled = false;
	protected $label = '';
	protected $tooltip = '';
	protected $after = '';
	protected $options = array();

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

	public function withRequired( bool $required ): self {
		$this->required = $required;
		return $this;
	}

	public function withDisabled( bool $disabled ): self {
		$this->disabled = $disabled;
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

	public function withOptions( array $options ): self {
		$this->options = $options;
		return $this;
	}

	public function toHtmlAttrs(): array {
		$attrs = array(
			'name'     => $this->name,
			'id'       => $this->id,
			'required' => $this->required,
			'disabled' => $this->disabled,
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

		$html  = '<div class="' . self::classes( $this->cssClasses( 'bbcs_text_input' ) ) . '">';
		$html .= '<div class="bbcs_label_input_box"><span class="bbcs-label-input">' . self::escape( $this->label ) . '</span>' . self::tooltip( $this->tooltip ) . '</div>';
		$html .= '<div class="bbcs_text_input_inner"><select' . self::attrs( $attrs ) . '>';
		foreach ( $this->options as $option_value => $option_label ) {
			$html .= '<option value="' . self::escape( $option_value, 'attr' ) . '"' . selected( $this->value, (string) $option_value, false ) . '>' . self::escape( $option_label ) . '</option>';
		}
		$html .= '</select>' . self::content( $this->after ) . '</div></div>';

		return self::output( $html, $return );
	}
}
