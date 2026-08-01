<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VisitorsMap extends Base {
	protected $data = array();

	public function withData( array $data ): self {
		$this->data = $data;
		return $this;
	}

	public function render( bool $return = false ): string {
		$values = wp_json_encode( $this->data );

		return self::output( '<div id="bbcs_visitors_jsvectormap"'
			. ' class="bbcs-visitors-map"'
			. ' data-bbcs-values=\'' . $values . '\''
			. ' style="width: 100%; min-height: 300px;"></div>', $return );
	}
}
