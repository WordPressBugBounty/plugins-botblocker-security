<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerTelegram
{
	private const API_URL = 'https://api.telegram.org/bot';

	public static function sendMessage( string $text ): bool
	{
		$BBCS = BotBlocker::getInstance();

		$bot_token = isset( $BBCS->settings->telegram_bot_token ) ? $BBCS->settings->telegram_bot_token : '';
		$chat_id   = isset( $BBCS->settings->telegram_chat_id ) ? $BBCS->settings->telegram_chat_id : '';

		if ( empty( $bot_token ) || empty( $chat_id ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Telegram] Missing bot token or chat ID' );
			}
			return false;
		}

		$url = self::API_URL . $bot_token . '/sendMessage';

		$body = wp_json_encode(
			array(
				'chat_id'                  => $chat_id,
				'text'                     => $text,
				'parse_mode'               => 'HTML',
				'disable_web_page_preview' => true,
			)
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => $body,
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Telegram] WP Error: ' . $response->get_error_message() );
			}
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				$body_raw = wp_remote_retrieve_body( $response );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Telegram] HTTP ' . $code . ': ' . $body_raw );
			}
			return false;
		}

		return true;
	}

	public static function buildWeeklyReport(): string
	{
		$BBCS = BotBlocker::getInstance();

		$site_name = get_bloginfo( 'name' );
		$site_url  = get_bloginfo( 'url' );
		$week_ago  = time() - WEEK_IN_SECONDS;
		$date_from = date_i18n( 'd.m.Y', $week_ago );
		$date_to   = date_i18n( 'd.m.Y', time() );
		$timezone  = bbcs_wp_timezone_string();

		$total_hits       = 0;
		$blocked_count    = 0;
		$suspicious_count = 0;
		$allowed_count    = 0;
		$searchbot_count  = 0;
		$fakebot_count    = 0;

		if ( ! empty( $BBCS->statistics ) && is_array( $BBCS->statistics ) ) {
			$allowed_count   = (int) ( $BBCS->statistics['total_hits'] ?? 0 );
			$blocked_count   = (int) ( $BBCS->statistics['total_blocked'] ?? 0 );
			$searchbot_count = (int) ( $BBCS->statistics['search_engine_visits'] ?? 0 );
			$total_hits      = $allowed_count + $blocked_count;
		}

		$is_disabled = (int) ( $BBCS->settings->disable ?? 0 );
		$secure_mode = (int) ( $BBCS->settings->secure_mode ?? 2 );
		$mode_label  = $secure_mode === 2 ? 'FULL' : 'FRONTEND';
		$block_rate  = $total_hits > 0 ? round( ( $blocked_count / $total_hits ) * 100, 2 ) : 0;

		$host = esc_html( wp_parse_url( $site_url, PHP_URL_HOST ) );

		$stats = array(
			__( 'Total requests', 'botblocker-security' ) => $total_hits,
			__( 'Allowed', 'botblocker-security' )        => $allowed_count,
			__( 'Blocked', 'botblocker-security' )        => $blocked_count,
			__( 'Suspicious', 'botblocker-security' )     => $suspicious_count,
			__( 'Search bots', 'botblocker-security' )    => $searchbot_count,
			__( 'Fake bots', 'botblocker-security' )      => $fakebot_count,
		);

		$label_width = 16;
		foreach ( $stats as $label => $value ) {
			$label_width = max( $label_width, self::displayWidth( (string) $label ) + 2 );
		}

		$report = '';
		$report .= '🛡 <b>' . esc_html__( 'BotBlocker Weekly Report', 'botblocker-security' ) . "</b>\n";
		$report .= '<b>' . esc_html( $site_name ) . "</b>\n";
		$report .= '<a href="' . esc_url( $site_url ) . '">' . $host . "</a>\n";
		$report .= "\n";
		$report .= '<code>' . $date_from . ' → ' . $date_to . "</code>\n";
		$report .= '<code>' . esc_html__( 'Timezone', 'botblocker-security' ) . ': ' . esc_html( $timezone ) . "</code>\n";
		$report .= "\n";
		$report .= ( $is_disabled ? '🔴' : '🟢' ) . ' <b>' . ( $is_disabled ? esc_html__( 'Protection is disabled', 'botblocker-security' ) : esc_html__( 'Protection is active', 'botblocker-security' ) ) . "</b>\n";
		$report .= '' . esc_html__( 'Mode', 'botblocker-security' ) . ': <b>' . $mode_label . "</b>\n";
		$report .= "\n";
		$report .= '<b>' . esc_html__( 'Security result', 'botblocker-security' ) . "</b>\n";
		$report .= "<pre>\n";
		foreach ( $stats as $label => $value ) {
			$pad     = max( 1, $label_width - self::displayWidth( (string) $label ) );
			$report .= $label . str_repeat( ' ', $pad ) . str_pad( number_format( (int) $value, 0, '.', ' ' ), 10, ' ', STR_PAD_LEFT ) . "\n";
		}
		$report .= "</pre>\n";
		$report .= '<b>' . esc_html__( 'Block rate', 'botblocker-security' ) . "</b>\n";
		$report .= '<code>' . number_format( $block_rate, 2, '.', ' ' ) . '%' . "</code>\n";
		$report .= '✅ ' . esc_html__( 'Website protection worked normally this week.', 'botblocker-security' ) . "\n";
		$report .= "\n";
		$report .= '<code>' . esc_html__( 'Generated by BotBlocker Security', 'botblocker-security' ) . "</code>\n";

		return $report;
	}

	private static function displayWidth( string $text ): int
	{
		if ( function_exists( 'mb_strwidth' ) ) {
			return mb_strwidth( $text, 'UTF-8' );
		}
		if ( function_exists( 'mb_strlen' ) ) {
			return mb_strlen( $text, 'UTF-8' );
		}
		return strlen( $text );
	}

	public static function sendWeeklyReport(): bool
	{
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Telegram] Sending weekly report' );
		}

		$BBCS = BotBlocker::getInstance();

		if ( empty( $BBCS->settings->telegram_notifications ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Telegram] Notifications disabled, skipping' );
			}
			return false;
		}

		bbcs_collect_statistic_data();

		$report = self::buildWeeklyReport();
		return self::sendMessage( $report );
	}

	public static function testConnection(): bool
	{
		$BBCS = BotBlocker::getInstance();

		$bot_token = isset( $BBCS->settings->telegram_bot_token ) ? $BBCS->settings->telegram_bot_token : '';
		$chat_id   = isset( $BBCS->settings->telegram_chat_id ) ? $BBCS->settings->telegram_chat_id : '';

		if ( empty( $bot_token ) || empty( $chat_id ) ) {
			return false;
		}

		$site_name = get_bloginfo( 'name' );
		$site_url  = get_bloginfo( 'url' );

		$text = '🟢 <b>' . __( 'BotBlocker Telegram', 'botblocker-security' ) . '</b>' . "\n";
		$text .= __( 'Connection test successful!', 'botblocker-security' ) . "\n";
		$text .= "\n";
		$text .= esc_html( $site_name ) . "\n";
		$text .= esc_url( $site_url ) . "\n";
		$text .= "\n";
		$text .= __( 'You will receive weekly security reports here.', 'botblocker-security' ) . "\n";

		return self::sendMessage( $text );
	}
}
