<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Toggle extends Base {
	protected $id = '';
	protected $checked = false;
	protected $label = '';
	protected $tooltip = '';
	protected $gear_url = '';
	protected $setup_url = '';

	public function withChecked( bool $checked ): self {
		$this->checked = $checked;
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

	public function withGearUrl( string $gear_url ): self {
		$this->gear_url = $gear_url;
		return $this;
	}

	public function withSetupUrl( string $setup_url ): self {
		$this->setup_url = $setup_url;
		return $this;
	}

	public function toHtmlAttrs(): array {
		return array_merge(
			array(
				'type'   => 'checkbox',
				'id'     => $this->id,
				'value'  => '1',
				'checked' => $this->checked,
			),
			parent::toHtmlAttrs()
		);
	}

	public function render( bool $return = false ): string {
		$attrs         = $this->toHtmlAttrs();
		$attrs['class'] = self::classes( array( 'bbcs_switch', $this->class ) );

		$html  = '<div class="bbcs_switch_container">';
		$html .= '<label class="bbcs_switch"><input' . self::attrs( $attrs ) . '>';
		$html .= '<span class="bbcs_slider"></span></label>';
		$html .= '<span class="bbcs_switch_label">' . self::escape( $this->label );

		if ( $this->setup_url !== '' ) {
			$html .= '<a href="' . self::escape( $this->setup_url, 'url' ) . '"><i class="fa-solid fa-person-running bbcs-gray"></i></a>';
		}

		if ( $this->gear_url !== '' ) {
			$html .= '<a href="' . self::escape( $this->gear_url, 'url' ) . '"><i class="fas fa-gear bbcs-gray ms-1"></i></a>';
		}

		if ( $this->tooltip !== '' ) {
			$html .= '<span class="bbcs-help" style="display:inline-flex"><a href="#"><i class="fas fa-info-circle bbcs-gray ms-1"></i></a><span class="bbcs-help-tip">' . self::escape( $this->tooltip ) . '</span></span>';
		}

		$html .= '</span></div>';

		return self::output( $html, $return );
	}
}
