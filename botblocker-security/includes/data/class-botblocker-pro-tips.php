<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/dto/class-notification-row.php';

use BotBlocker\Component\Botblocker_NotificationRow;

class BotBlockerProTips {

	private const CONNECTION_OPTIONS = array( 'block_vpn_users', 'block_tor_users', 'hosting_block' );

	public static function getAll(): array {
		$pro  = BotBlockerPro::isActive();
		$tips = array();

		if ( ! $pro ) {
			$tips[] = self::cloudTip();
		}
		if ( self::connectionTipVisible( $pro ) ) {
			$tips[] = self::connectionTip( $pro );
		}
		if ( ! $pro ) {
			$tips[] = self::addonsTip();
		}

		return $tips;
	}

	public static function connectionOptionsOff(): bool {
		$bb = BotBlocker::getInstance();
		if ( null === $bb || ! isset( $bb->settings ) ) {
			return false;
		}
		$settings = $bb->settings;
		foreach ( self::CONNECTION_OPTIONS as $option ) {
			if ( empty( $settings->{$option} ) ) {
				return true;
			}
		}
		return false;
	}

	private static function connectionTipVisible( bool $pro ): bool {
		if ( ! $pro ) {
			return true;
		}
		return self::connectionOptionsOff();
	}

	private static function cloudTip(): Botblocker_NotificationRow {
		$tip = new Botblocker_NotificationRow();
		$tip->type = 'pro_tip_cloud';
		$tip->pro = true;
		$tip->icon = 'crown';
		$tip->title = __( 'Cloud protection', 'botblocker-security' );
		$tip->message = __( 'Validate visitors in the cloud and share a live threat network across PRO sites.', 'botblocker-security' );
		$tip->link = BotBlockerMultisite::getAdminPageUrl( 'bbcs_cloud_api' );
		$tip->link_text = __( 'Activate PRO', 'botblocker-security' );
		return $tip;
	}

	private static function connectionTip( bool $pro ): Botblocker_NotificationRow {
		$tip = new Botblocker_NotificationRow();
		$tip->type = 'pro_tip_connections';
		$tip->pro = true;
		$tip->icon = 'crown';
		$tip->title = __( 'Block VPN & Tor', 'botblocker-security' );
		$tip->message = __( 'Stop anonymized and hosting traffic that bots hide behind.', 'botblocker-security' );
		if ( $pro ) {
			$tip->link      = BotBlockerMultisite::getAdminPageUrl( 'bbcs_settings' );
			$tip->link_text = __( 'Open settings', 'botblocker-security' );
		} else {
			$tip->link      = BotBlockerMultisite::getAdminPageUrl( 'bbcs_cloud_api' );
			$tip->link_text = __( 'Activate PRO', 'botblocker-security' );
		}
		return $tip;
	}

	private static function addonsTip(): Botblocker_NotificationRow {
		$tip = new Botblocker_NotificationRow();
		$tip->type = 'pro_tip_addons';
		$tip->pro = true;
		$tip->icon = 'crown';
		$tip->title = __( 'Add-ons', 'botblocker-security' );
		$tip->message = __( 'Hide Login, Page Cache, Anti-Spam, malware scan and more need PRO.', 'botblocker-security' );
		$tip->link = BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' );
		$tip->link_text = __( 'Browse add-ons', 'botblocker-security' );
		return $tip;
	}
}
