<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldPair extends Base {

	/** @var callable|null Callback that renders the pair's child fields. */
	private $items = null;

	public function withItems( callable $items ): self {
		$this->items = $items;
		return $this;
	}

	public function render( bool $return = false ): string {
		$html = '<div class="bbcs-field-pair">';

		if ( is_callable( $this->items ) ) {
			$html .= self::content( $this->items );
		}

		$html .= '</div>';

		return self::output( $html, $return );
	}
}
