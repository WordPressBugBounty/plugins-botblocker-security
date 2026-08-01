<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HealthGauge extends Base {
	protected $value    = 0;
	protected $max      = 100;
	protected $label    = '';
	protected $decimals = 0;

	public function withValue( $value ): self {
		$this->value = (float) $value;
		return $this;
	}

	public function withMax( $max ): self {
		$this->max = (int) $max;
		return $this;
	}

	public function withLabel( string $label ): self {
		$this->label = $label;
		return $this;
	}

	public function withDecimals( int $decimals ): self {
		$this->decimals = $decimals < 0 ? 0 : $decimals;
		return $this;
	}

	public function render( bool $return = false ): string {
		$display_value = self::escape( number_format( $this->value, $this->decimals ) );
		$label         = self::escape( $this->label );

		return self::output( '<canvas id="bbcs-health_gauge"'
			. ' data-health-value="' . $display_value . '"'
			. ' data-bbcs-label="' . $label . '"'
			. ' style="width: 100%; height: auto; padding-left: 10px; padding-right: 10px;"></canvas>', $return );
	}
}
