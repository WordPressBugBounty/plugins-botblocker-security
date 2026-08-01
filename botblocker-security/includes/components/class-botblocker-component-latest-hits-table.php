<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LatestHitsTable extends Base {
	protected $rows       = array();
	protected $gmt_offset = 0.0;

	public function withRows( array $rows ): self {
		$this->rows = $rows;
		return $this;
	}

	public function withGmtOffset( float $offset ): self {
		$this->gmt_offset = $offset;
		return $this;
	}

	public function cssClasses( string $base = '' ): array {
		return array_merge(
			parent::cssClasses( $base ),
			$this->class !== '' ? array( $this->class ) : array()
		);
	}

	public function render( bool $return = false ): string {
		if ( empty( $this->rows ) ) {
			return self::output( '<p>' . esc_html__( 'No data available.', 'botblocker-security' ) . '</p>', $return );
		}

		$rows = $this->prepareRows();

		$html = '<table class="' . self::classes( $this->cssClasses( 'bbcs-table' ) ) . '"';
		if ( $this->id !== '' ) {
			$html .= ' id="' . self::escape( $this->id, 'attr' ) . '"';
		}
		$html .= self::attrs( $this->toHtmlAttrs() ) . '>';
		$html .= '<thead><tr>'
			. '<th>' . esc_html__( 'Date/Time', 'botblocker-security' ) . '</th>'
			. '<th>' . esc_html__( 'IP Address', 'botblocker-security' ) . '</th>'
			. '<th>' . esc_html__( 'Country', 'botblocker-security' ) . '</th>'
			. '<th>' . esc_html__( 'Language', 'botblocker-security' ) . '</th>'
			. '<th>' . esc_html__( 'Device', 'botblocker-security' ) . '</th>'
			. '<th>' . esc_html__( 'Operating System', 'botblocker-security' ) . '</th>'
			. '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			$country_lc = strtolower( $row['country'] );
			$html .= '<tr>'
				. '<td>' . self::escape( $row['datetime'] ) . '</td>'
				. '<td>' . self::escape( $row['ip'] ) . '</td>'
				. '<td><span class="bbcs-top-span"><div class="bbcs-flag-wrapper"><div class="flag flag-' . self::escape( $country_lc, 'attr' ) . ' bbcs-flag-scale"></div></div><div class="ms-2">' . self::escape( $row['country'] ) . '</div></span></td>'
				. '<td>' . self::escape( $row['lang'] ) . '</td>'
				. '<td>' . self::escape( $row['device'] ) . '</td>'
				. '<td>' . self::escape( $row['os'] ) . '</td>'
				. '</tr>';
		}

		$html .= '</tbody></table>';

		return self::output( $html, $return );
	}

	private function prepareRows(): array {
		$rows = array();
		foreach ( $this->rows as $row ) {
			$datetime = new \DateTime( "@{$row['date']}", new \DateTimeZone( 'UTC' ) );
			if ( $this->gmt_offset != 0 ) {
				$hours         = floor( abs( $this->gmt_offset ) );
				$minutes       = ( abs( $this->gmt_offset ) * 60 ) % 60;
				$interval_spec = 'PT' . $hours . 'H' . $minutes . 'M';
				$interval      = new \DateInterval( $interval_spec );
				if ( $this->gmt_offset > 0 ) {
					$datetime->add( $interval );
				} else {
					$datetime->sub( $interval );
				}
			}
			$row['datetime'] = $datetime->format( 'Y-m-d H:i:s' );
			$rows[]          = $row;
		}
		return $rows;
	}
}
