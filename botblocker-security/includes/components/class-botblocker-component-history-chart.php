<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HistoryChart extends Base {
	protected $labels  = array();
	protected $uniques = array();
	protected $hits = array();

	public function withLabels( array $labels ): self {
		$this->labels = $labels;
		return $this;
	}

	public function withUniques( array $uniques ): self {
		$this->uniques = $uniques;
		return $this;
	}

	public function withHits( array $hits ): self {
		$this->hits = $hits;
		return $this;
	}

	public function render( bool $return = false ): string {
		$id      = self::escape( $this->id ?: 'bbcs_hits_and_uniques_chart', 'attr' );
		$labels  = wp_json_encode( array_values( $this->labels ) );
		$uniques = wp_json_encode( array_values( $this->uniques ) );
		$hits    = wp_json_encode( array_values( $this->hits ) );

		return self::output( '<div id="' . $id . '" class="bbcs-hits-uniques-chart"'
			. ' data-bbcs-labels=\'' . $labels . '\''
			. ' data-bbcs-values-uniques=\'' . $uniques . '\''
			. ' data-bbcs-values-hits=\'' . $hits . '\''
			. ' style="width: 100%; height: 400px;"></div>', $return );
	}
}
