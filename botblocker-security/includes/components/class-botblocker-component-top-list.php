<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TopList extends Base {
	protected $title = '';
	protected $items = array();
	protected $type  = '';

	public function withTitle( string $title ): self {
		$this->title = $title;
		return $this;
	}

	public function withItems( array $items ): self {
		$this->items = $items;
		return $this;
	}

	public function withType( string $type ): self {
		$this->type = $type;
		return $this;
	}

	public function render( bool $return = false ): string {
		if ( empty( $this->items ) ) {
			return self::output( '<p>' . esc_html__( 'No data available.', 'botblocker-security' ) . '</p>', $return );
		}

		$output  = '<div class="bbcs-statistics-chart-title-div-start">';
		$output .= '<span class="bbcs-statistics-chart-title">' . self::escape( $this->title ) . '</span>';
		$output .= '</div>';
		$output .= '<ul class="bbcs-top-ul">';

		foreach ( $this->items as $item ) {
			$key   = self::escape( $item[ $this->type ] );
			$count = self::escape( $item['count'] );

			if ( $this->type === 'country' ) {
				$flag       = strtolower( $key );
				$country_name = \BotBlockerGeo::getCountryByCode( $key );
				$output    .= '<li class="bbcs-top-li">'
					. '<span class="bbcs-top-span">'
					. '<div class="bbcs-flag-wrapper">'
					. '<div class="flag flag-' . $flag . ' bbcs-flag-scale"></div>'
					. '</div>'
					. '<span class="bbcs-top-span-text" data-bs-toggle="tooltip" title="' . $key . ' – ' . $country_name . '">'
					. $key . ' – ' . $country_name
					. '</span>'
					. '<span class="bbcs-top-count">' . $count . '</span>'
					. '</span>'
					. '</li>';
			} else {
				$output .= '<li class="bbcs-top-li">'
					. '<span class="bbcs-top-span">'
					. '<span class="bbcs-top-span-text" data-bs-toggle="tooltip" title="' . $key . '">'
					. $key
					. '</span>'
					. '<span class="bbcs-top-count">' . $count . '</span>'
					. '</span>'
					. '</li>';
			}
		}

		$output .= '</ul>';

		return self::output( $output, $return );
	}
}
