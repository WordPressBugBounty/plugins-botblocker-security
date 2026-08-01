<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DailyHitsChart extends Base {
	protected $labels = array();
	protected $values = array();
	protected $id     = 'bbcs_daily_hits_chart';

	public function withLabels( array $labels ): self {
		$this->labels = $labels;
		return $this;
	}

	public function withValues( array $values ): self {
		$this->values = $values;
		return $this;
	}

	public function render( bool $return = false ): string {
		$id     = self::escape( $this->id, 'attr' );
		$class  = self::escape( 'bbcs-daily-hits-chart', 'attr' );
		$labels = wp_json_encode( array_values( $this->labels ) );
		$values = wp_json_encode( array_values( $this->values ) );

		return self::output( '<div id="' . $id . '" class="' . $class . '"'
			. " data-bbcs-labels='" . $labels . "'"
			. " data-bbcs-values='" . $values . "'"
			. ' style="width: 100%; height: 200px;"></div>', $return );
	}
}
