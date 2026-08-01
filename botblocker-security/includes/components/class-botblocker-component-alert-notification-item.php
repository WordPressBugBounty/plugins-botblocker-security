<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AlertNotificationItem extends Base {
	protected $alert;

	public function withAlert( \Botblocker_AlertItemData $alert ): self {
		$this->alert = $alert;
		return $this;
	}

	public function render( bool $return = false ): string {
		$alert     = $this->alert;
		$link      = ! empty( $alert->link ) ? esc_url( $alert->link ) : '#';
		$icon      = esc_attr( $alert->icon ?? '' );
		$title     = esc_html( $alert->title ?? '' );
		$message   = esc_html( $alert->message ?? '' );
		$link_text = ! empty( $alert->link_text ) ? esc_html( $alert->link_text ) : '';

		$html = '<li>';
		$html .= '<a href="' . $link . '" class="clearfix">';
		$html .= '<div class="image"><i class="' . $icon . '"></i></div>';
		$html .= '<span class="title">' . $title . '</span>';
		$html .= '<span class="message">' . $message;
		if ( $link_text !== '' ) {
			$html .= '<strong>' . $link_text . '</strong>';
		}
		$html .= '</span></a></li>';

		return self::output( $html, $return );
	}
}
