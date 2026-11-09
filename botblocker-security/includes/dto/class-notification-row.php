<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_NotificationRow {
	/** @var string */
	public $title = '';
	/** @var string */
	public $message = '';
	/** @var string */
	public $link = '';
	/** @var string */
	public $link_text = '';
	/** @var string */
	public $extra_link = '';
	/** @var string */
	public $extra_link_text = '';
	/** @var string */
	public $type = '';
	/** @var string */
	public $icon = '';
	/** @var bool */
	public $pro = false;

	public static function fromAlert( array $alert ): self {
		$row = new self();
		$row->title = (string) ( $alert['title'] ?? '' );
		$row->message = (string) ( $alert['message'] ?? '' );
		$row->link = (string) ( $alert['link'] ?? '' );
		$row->link_text = (string) ( $alert['link_text'] ?? '' );
		$row->extra_link = (string) ( $alert['extra_link'] ?? '' );
		$row->extra_link_text = (string) ( $alert['extra_link_text'] ?? '' );
		$row->type = (string) ( $alert['type'] ?? '' );
		$row->icon = (string) ( $alert['icon'] ?? '' );
		$row->pro = ! empty( $alert['pro'] );
		return $row;
	}

	public function iconName(): string {
		if ( $this->pro ) {
			return 'crown';
		}
		return 'alert';
	}

	public function linkClass(): string {
		if ( $this->pro ) {
			return 'bbcs-notif-link bbcs-notif-link--pro';
		}
		return 'bbcs-notif-link bbcs-tx-green';
	}
}
