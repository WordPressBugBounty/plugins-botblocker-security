<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CountersGrid extends Base {
	protected $data = array();

	public function withData( array $data ): self {
		$this->data = $data;
		return $this;
	}

	public function render( bool $return = false ): string {
		if ( empty( $this->data ) ) {
			return self::output( '<div class="bbcs-counters-grid">' . esc_html__( 'No data available.', 'botblocker-security' ) . '</div>', $return );
		}

		$output  = '<div class="bbcs-counters-grid">';
		$output .= '<div><span class="bbcs-h-today">' . self::escape( $this->data['today_hits'] ) . '</span><span class="bbcs-h-today-text">' . esc_html__( 'Today hits', 'botblocker-security' ) . '</span></div>';
		$output .= '<div><span class="bbcs-h-today-block">' . self::escape( $this->data['today_blocked'] ) . '</span><span class="bbcs-h-today-block-text">' . esc_html__( 'Today blocked', 'botblocker-security' ) . '</span></div>';
		$output .= '<div><span class="bbcs-h-total">' . self::escape( $this->data['total_hits'] ) . '</span><span class="bbcs-h-total-text">' . esc_html__( 'Total hits', 'botblocker-security' ) . '</span></div>';
		$output .= '<div><span class="bbcs-h-total-block">' . self::escape( $this->data['total_blocked'] ) . '</span><span class="bbcs-h-total-block-text">' . esc_html__( 'Total blocked', 'botblocker-security' ) . '</span></div>';
		$output .= '<div><span class="bbcs-h-se">' . self::escape( $this->data['search_engine_visits'] ) . '</span><span class="bbcs-h-se-text">' . esc_html__( 'Search engine visits', 'botblocker-security' ) . '</span></div>';
		$output .= '<div><span class="bbcs-h-percent-eff">' . self::escape( $this->data['percent_requests_blocked'] ) . '%</span><span class="bbcs-h-percent-eff-text">' . esc_html__( 'Requests blocked', 'botblocker-security' ) . '</span></div>';
		$output .= '</div>';

		return self::output( $output, $return );
	}
}
