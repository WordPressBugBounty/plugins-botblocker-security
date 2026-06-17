<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerMailer {

	public static function getEmail( $user_id ): string {
		$user = get_userdata( $user_id );
		if ( $user ) {
			return $user->user_email;
		} else {
			return '';
		}
	}

	public static function getDisableUrl(): string {
		$BBCS = BotBlocker::getInstance();
		return BotBlockerMultisite::getCurrentSiteUrl() . '/?' . $BBCS->settings->secret_botblocker_get_param . '=' . $BBCS->action_disable; //BBCS-MULTISITE
	}

	public static function getOffUrl(): string {
		$BBCS = BotBlocker::getInstance();
		return BotBlockerMultisite::getCurrentSiteUrl() . '/?' . $BBCS->settings->secret_botblocker_get_param . '=' . $BBCS->action_off; //BBCS-MULTISITE
	}

	public static function getOnUrl(): string {
		$BBCS = BotBlocker::getInstance();
		return BotBlockerMultisite::getCurrentSiteUrl() . '/?' . $BBCS->settings->secret_botblocker_get_param . '=' . $BBCS->action_on; //BBCS-MULTISITE
	}

	public static function sendAdminLinks(): bool {
		$admin_email = BotBlockerInstall::getCloudAPIEmail();
		if ( empty( $admin_email ) ) {
			return false;
		}

		$disable_url = self::getDisableUrl();
		$off_url     = self::getOffUrl();
		$on_url      = self::getOnUrl();

		$subject = 'BotBlocker Management Links';

		$message = "
        <html>
        <head>
            <title>Manage BotBlocker Settings</title>
        </head>
        <body>
            <p>Hello,</p>
            <p>Below are the links to manage the BotBlocker settings on your site:</p>
            <ul>
                <li><b>Temporary Disable:</b> You can temporarily disable BotBlocker for the current request. Add this parameter to any site URL: <br>
                    <a href='{$disable_url}'>{$disable_url}</a></li>
                <li><b>Turn Off BotBlocker:</b> Use this link to permanently disable BotBlocker: <br>
                    <a href='{$off_url}'>{$off_url}</a></li>
                <li><b>Turn On BotBlocker:</b> Use this link to enable BotBlocker again: <br>
                    <a href='{$on_url}'>{$on_url}</a></li>
            </ul>
            <p>Best regards,</p>
            <p>BotBlocker Team</p>
        </body>
        </html>
    ";

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: BotBlocker <no-reply@' . wp_parse_url( BotBlockerMultisite::getCurrentSiteUrl(), PHP_URL_HOST ) . '>', //BBCS-MULTISITE
		);

		return wp_mail( $admin_email, $subject, $message, $headers );
	}

	public static function sendExpirationEmail( string $message, bool $expired = false ): bool {
		$admin_email = BotBlockerInstall::getCloudAPIEmail();
		if ( empty( $admin_email ) ) {
			return false;
		}

		$transient_key = 'bbcs_email_sent_' . md5( $message . ( $expired ? '1' : '0' ) );

		// Store a Unix timestamp so we can manually verify the 24-hour window even
		// when a persistent object cache (Redis/Memcached) ignores the transient TTL.
		$cached_at = get_transient( $transient_key );
		if ( $cached_at !== false && ( time() - (int) $cached_at ) < DAY_IN_SECONDS ) {
			return false;
		}

		$subject = $expired ? 'Your BotBlocker Cloud API connection has Expired' : 'Your BotBlocker Cloud API connection is Expiring Soon';
		$message = "
        <html>
        <head>
            <title>BotBlocker Cloud API Connection Expiration Notice</title>
        </head>
        <body>
            <p>Hello,</p>
            <p>{$message}</p>
            <p>Best regards,</p>
            <p>BotBlocker Team</p>
        </body>
        </html>
    ";

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: BotBlocker <no-reply@' . wp_parse_url( BotBlockerMultisite::getCurrentSiteUrl(), PHP_URL_HOST ) . '>', //BBCS-MULTISITE
		);

		$email_sent = wp_mail( $admin_email, $subject, $message, $headers );

		if ( $email_sent ) {
			set_transient( $transient_key, time(), DAY_IN_SECONDS );
		}

		return $email_sent;
	}
}
