<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NewsItem extends Base {
	protected $item = array();

	public function withItem( array $item ): self {
		$this->item = $item;
		return $this;
	}

	public function render( bool $return = false ): string {
		$item  = $this->item;
		$link  = esc_url( $item['link'] ?? '' );
		$title = esc_html( $item['title'] ?? '' );
		$date  = esc_html( $item['date'] ?? '' );
		$time  = esc_html( $item['time'] ?? '' );

		$html  = '<li class="bbcs_news-item">';
		$html .= '<a href="' . $link . '" target="_blank" class="bbcs_news_a">' . $title . '</a>';
		$html .= '<span class="bbcs_news-date">' . $date . ' at ' . $time . '</span>';
		$html .= '</li>';

		return self::output( $html, $return );
	}
}
