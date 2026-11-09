<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/dto/class-notification-row.php';

use BotBlocker\Component\Botblocker_NotificationRow;

final class Botblocker_NotificationsViewModel {
	/** @var Botblocker_NotificationRow[] */
	public $rows;
	/** @var int */
	public $total;
	/** @var int */
	private $visible_limit = 5;

	public function __construct( array $rows ) {
		$this->rows = array_values( $rows );
		$this->total = count( $this->rows );
	}

	public function isExtra( int $index ): bool {
		return $index >= $this->visible_limit;
	}

	public function rowClass( Botblocker_NotificationRow $row, int $index ): string {
		$class = 'bbcs-notif-row';
		if ( $row->pro ) {
			$class .= ' bbcs-notif-row--pro';
		}
		if ( $this->isExtra( $index ) ) {
			$class .= ' bbcs-notif-row--extra';
		}
		return $class;
	}

	public function hiddenAttribute( int $index ): string {
		if ( $this->isExtra( $index ) ) {
			return ' hidden';
		}
		return '';
	}

	public function hasExtra(): bool {
		return $this->total > $this->visible_limit;
	}
}
