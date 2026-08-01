<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Checkbox extends Base {
	protected $name = '';
	protected $id = '';
	protected $value = '1';
	protected $input_class = '';
	protected $checked = false;
	protected $disabled = false;
	protected $label = '';
	protected $tooltip = '';
	protected $after = '';

	public function withName( string $name ): self {
		$this->name = $name;
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

	public function withChecked( bool $checked ): self {
		$this->checked = $checked;
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

	public function toHtmlAttrs(): array {
		return array_merge(
			array(
				'type'     => 'checkbox',
				'name'     => $this->name,
				'id'       => $this->id,
				'value'    => $this->value,
				'checked'  => $this->checked,
				'disabled' => $this->disabled,
			),
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
		$attrs['class'] = self::classes( array( 'bbcs_checkbox_input_input', $this->input_class ) );

		$html  = '<div class="' . self::classes( $this->cssClasses( 'bbcs_checkbox_input' ) ) . '">';
		$html .= '<div class="bbcs_label_checkbox_box"><input' . self::attrs( $attrs ) . '>';
		$html .= '<span class="bbcs_label_input_checkbox">' . self::escape( $this->label ) . '</span></div>';
		$html .= self::tooltip( $this->tooltip );
		$html .= self::content( $this->after );
		$html .= '</div>';

		return self::output( $html, $return );
	}
}
