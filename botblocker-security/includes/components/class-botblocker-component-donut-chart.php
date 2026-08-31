<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DonutChart extends Base {
	protected $labels  = array();
	protected $values = array();
	protected $title   = '';
	protected $type    = 'donut';
	protected $width   = 'auto';
	protected $height  = '90px';

	public function withId( $id ): self {
		parent::withId( $id );
		return $this;
	}

	public function withLabels( array $labels ): self {
		$this->labels = $labels;
		return $this;
	}

	public function withValues( array $values ): self {
		$this->values = $values;
		return $this;
	}

	public function withTitle( string $title ): self {
		$this->title = $title;
		return $this;
	}

	public function withType( string $type ): self {
		$this->type = $type;
		return $this;
	}

	public function withWidth( string $width ): self {
		$this->width = self::sanitizeCssSize( $width, 'auto' );
		return $this;
	}

	public function withHeight( string $height ): self {
		$this->height = self::sanitizeCssSize( $height, 'auto' );
		return $this;
	}

	protected static function sanitizeCssSize( string $value, string $default ): string {
		$value = trim( $value );
		if ( $value === '' || preg_match( '/[^a-zA-Z0-9.%pxemremvhvcmin]/', $value ) === 1 ) {
			return $default;
		}

		return $value;
	}

	public function render( bool $return = false ): string {
		$container_id = 'bbcs_statistics_chart_' . sanitize_key( $this->title );
		if ( $this->id !== '' ) {
			$container_id = self::escape( $this->id, 'attr' );
		}

		$has_data = false;
		foreach ( $this->values as $bbcs_value ) {
			if ( (float) $bbcs_value > 0 ) {
				$has_data = true;
				break;
			}
		}

		$labels = wp_json_encode( array_values( $this->labels ) );
		$values = wp_json_encode( array_values( $this->values ) );
		$title  = self::escape( $this->title );
		$type   = self::escape( $this->type, 'attr' );
		$width  = self::escape( $this->width, 'attr' );
		$height = self::escape( $this->height, 'attr' );

		return self::output( '<div class="bbcs-statistics-chart-title-div">'
			. '<span class="bbcs-statistics-chart-title">' . $title . '</span>'
			. '</div>'
			. '<div id="' . $container_id . '"'
			. ' class="bbcs-statistics-chart"'
			. ' data-bbcs-type="' . $type . '"'
			. ' data-bbcs-title=\'' . $title . '\''
			. ' data-bbcs-labels=\'' . $labels . '\''
			. ' data-bbcs-values=\'' . $values . '\''
			. ( $has_data ? '' : ' data-bbcs-empty="1"' )
			. ' style="width: ' . $width . '; height: ' . $height . ';"></div>', $return );
	}
}
