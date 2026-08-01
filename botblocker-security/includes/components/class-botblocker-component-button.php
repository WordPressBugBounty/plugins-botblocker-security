<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Button extends Base {
	const VARIANT_PRIMARY   = 'primary';
	const VARIANT_DANGER    = 'danger';
	const VARIANT_WARNING   = 'warning';
	const VARIANT_DEFAULT   = 'default';
	const VARIANT_OUTLINE   = 'outline';

	const TYPE_SUBMIT = 'submit';
	const TYPE_BUTTON = 'button';
	const TYPE_RESET  = 'reset';

	protected $type = self::TYPE_BUTTON;
	protected $name;
	protected $value;
	protected $disabled = false;
	protected $variant = self::VARIANT_DEFAULT;
	protected $icon = '';
	protected $label = '';
	protected $tooltip = '';
	protected $wrap = false;
	protected $wrapper_class = 'bbcs_settings_button';

	public function withType( string $type ): self {
		$this->type = $type;
		return $this;
	}

	public function withName( $name ): self {
		$this->name = $name;
		return $this;
	}

	public function withValue( $value ): self {
		$this->value = $value;
		return $this;
	}

	public function withDisabled( bool $disabled ): self {
		$this->disabled = $disabled;
		return $this;
	}

	public function withVariant( string $variant ): self {
		$this->variant = $variant;
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

	public function withWrap( bool $wrap ): self {
		$this->wrap = $wrap;
		return $this;
	}

	public function withWrapperClass( string $wrapper_class ): self {
		$this->wrapper_class = $wrapper_class;
		return $this;
	}

	public function toHtmlAttrs(): array {
		$attrs = array(
			'type'     => $this->type,
			'name'     => $this->name,
			'id'       => $this->id,
			'value'    => $this->value,
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
			array( 'bbcs-button--' . $this->variant ),
			$this->class !== '' ? array( $this->class ) : array()
		);
	}

	public function render( bool $return = false ): string {
		$attrs = $this->toHtmlAttrs();
		$attrs['class'] = self::classes( $this->cssClasses( 'bbcs-button' ) );

		$html = '<button' . self::attrs( $attrs ) . '>';
		if ( $this->icon !== '' ) {
			$html .= '<i class="' . self::classes( $this->icon ) . '"></i> ';
		}
		$html .= self::escape( $this->label ) . '</button>';

		$tooltip = self::tooltip( $this->tooltip );
		if ( $tooltip !== '' || $this->wrap ) {
			$html = '<div class="' . self::classes( $this->wrapper_class ) . '">' . $html . $tooltip . '</div>';
		}

		return self::output( $html, $return );
	}
}
