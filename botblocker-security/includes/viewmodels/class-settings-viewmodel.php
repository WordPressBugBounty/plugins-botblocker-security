<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-dashboard-urls.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-secret-links.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-cron-task-data.php';
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-data-time.php';
require_once BOTBLOCKER_DIR . 'includes/components/class-botblocker-tab-item.php';

use BotBlocker\Component\TabItem;

final class Botblocker_SettingsViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;

	/** @var array<string,mixed> */
	public $settings;

	/** @var bool */
	public $has_pro;
	/** @var bool */
	public $has_cloud_api;
	/** @var bool */
	public $has_ultimate;
	/** @var bool */
	public $has_gd;
	/** @var bool */
	public $has_recaptcha_v2;
	/** @var bool */
	public $has_recaptcha_v3;
	/** @var bool */
	public $has_ecommerce;
	/** @var bool */
	public $has_behavior_engine;
	/** @var bool */
	public $behavior_engine_active;

	/** @var Botblocker_DashboardUrls */
	public $urls;
	/** @var Botblocker_SecretLinks */
	public $secret;
	/** @var string */
	public $integrations_url;
	/** @var string */
	public $addons_url;
	/** @var string */
	public $tools_url;

	/** @var array<int, array{title: string, icon: string, items: TabItem[]}> */
	public $nav_groups;
	/** @var string Current preset name */
	public $preset_name;
	/** @var string Current mode label */
	public $mode_label;

	/** @var array */
	public $cache_durations;
	/** @var array */
	public $cookie_lifetimes;
	/** @var array */
	public $ptr_lifetimes;
	/** @var array */
	public $subnet_mask_options;
	/** @var array */
	public $ptrcache_rule_ttl_options;
	/** @var array */
	public $rate_subnet_mask_options;
	/** @var array */
	public $error_headers;
	/** @var array */
	public $x_robots_directives;
	/** @var string */
	public $wp_cron_url;
	/** @var string */
	public $curl_cmd;
	/** @var string */
	public $wget_cmd;
	/** @var bool */
	public $wp_cron_enabled;
	/** @var string */
	public $current_ja3;
	/** @var string */
	public $current_ja4;
	/** @var string */
	public $docs_url;

	/** @var Botblocker_CronTaskData[] */
	public $cron_tasks;

	/** @var int */
	public $cron_recurring_count;

	/** @var int */
	public $cron_one_time_count;

	/** @var int */
	public $cron_total_count;

	/** @var array */
	public $cron_custom_intervals;

	public function __construct() {

		$BBCS = BotBlocker::getInstance();
		$BBCSA = Botblocker_Admin::getInstance();

		$this->header  = new Botblocker_HeaderViewModel();
		$this->sidebar = new Botblocker_SidebarViewModel();

		$this->settings = is_object( $BBCS->settings ) ? get_object_vars( $BBCS->settings ) : array();

		$this->has_pro            = BotBlockerPro::isActive();
		$this->has_cloud_api      = BotBlockerPro::isActive();
		$this->has_ultimate       = BotBlockerPro::isUltimate();
		$this->has_gd             = isset( $BBCS->prefly['gd'] ) && $BBCS->prefly['gd'] === 1;
		$this->has_recaptcha_v2   = ! empty( $BBCS->settings->recaptcha_key2 ) && ! empty( $BBCS->settings->recaptcha_secret2 );
		$this->has_recaptcha_v3   = ! empty( $BBCS->settings->recaptcha_key3 ) && ! empty( $BBCS->settings->recaptcha_secret3 );
		$this->has_ecommerce      = BotBlockerPaymentData::detectEcommerce();
		$this->has_behavior_engine = class_exists( 'BotBlockerAddons' )
		&& BotBlockerAddons::hasActiveFeature( 'behavioral_analysis_engine' );
		$this->behavior_engine_active = $this->has_behavior_engine;

		$this->urls               = new Botblocker_DashboardUrls();
		$this->urls->cloud_api    = $BBCSA->pages_cloud_api;
		$this->urls->settings     = $BBCSA->pages_settings;
		$this->urls->setup        = $BBCSA->pages_setup;
		$this->urls->reports      = $BBCSA->pages_reports;
		$this->urls->addons       = $BBCSA->pages_addons;
		$this->urls->wizard       = $BBCSA->pages_wizard;
		$this->urls->pricing      = 'https://botblocker.com/pricing/';

		$this->secret = new Botblocker_SecretLinks();
		if ( isset( $BBCS->settings->action_disable ) && $BBCS->settings->action_disable !== '' ) {
			$this->secret->disable_url = $BBCS->settings->action_disable;
		}
		if ( isset( $BBCS->settings->action_off ) && $BBCS->settings->action_off !== '' ) {
			$this->secret->off_url = $BBCS->settings->action_off;
		}
		if ( isset( $BBCS->settings->action_on ) && $BBCS->settings->action_on !== '' ) {
			$this->secret->on_url = $BBCS->settings->action_on;
		}

		$this->integrations_url = $BBCSA->pages_integrations;
		$this->addons_url       = $BBCSA->pages_addons;
		$this->tools_url        = $BBCSA->pages_tools;

		// Vertical sidebar nav - 5 groups combining all tabs
		$this->nav_groups = array(
			array(
				'title' => __( 'Detection', 'botblocker-security' ),
				'icon'  => 'search',
				'items' => array(
					( new TabItem( 'simple-detection', '', true, '', '', __( 'Bot Detection', 'botblocker-security' ), 'bot' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/simple-bot-detection.svg' ),
					( new TabItem( 'browser-plugins', '', false, '', '', __( 'Browser &amp; Plugins', 'botblocker-security' ), 'browser' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/browser-plugins.svg' ),
					( new TabItem( 'tls_fingerprint', '', false, '', '', __( 'TLS Fingerprinting', 'botblocker-security' ), 'lock' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/security.svg' ),
				),
			),
			array(
				'title' => __( 'Traffic', 'botblocker-security' ),
				'icon'  => 'traffic',
				'items' => array(
					( new TabItem( 'connection-types', '', false, '', '', __( 'Connection Types', 'botblocker-security' ), 'link' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/connections-types.svg' ),
					( new TabItem( 'rate-limiting', '', false, '', '', __( 'Rate Limiting', 'botblocker-security' ), 'gauge' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/rate-limiting.svg' ),
					( new TabItem( 'traffic', '', false, '', '', __( 'Traffic &amp; Referrer', 'botblocker-security' ), 'referer' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/traffic.svg' ),
				),
			),
			array(
				'title' => __( 'Access Control', 'botblocker-security' ),
				'icon'  => 'lock',
				'items' => array(
					( new TabItem( 'brute-force', '', false, '', '', __( 'Login Brute-Force', 'botblocker-security' ), 'bruteforce' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/security.svg' ),
					( new TabItem( 'captcha', '', false, '', '', __( 'Captcha', 'botblocker-security' ), 'captcha' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/captcha.svg' ),
					( new TabItem( 'payment', '', false, '', '', __( 'Payment Gateways', 'botblocker-security' ), 'payment' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/tarifs.svg' ),
				),
			),
			array(
				'title' => __( 'Data', 'botblocker-security' ),
				'icon'  => 'database',
				'items' => array(
					( new TabItem( 'data-log', '', false, '', '', __( 'Data Log &amp; Processing', 'botblocker-security' ), 'chart' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/data-log-processing.svg' ),
					( new TabItem( 'log', '', false, '', '', __( 'Logging', 'botblocker-security' ), 'doc' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/logging-settings.svg' ),
					( new TabItem( 'cron', '', false, '', '', __( 'Cron Jobs', 'botblocker-security' ), 'clock' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/cron.svg' ),
				),
			),
			array(
				'title' => __( 'System', 'botblocker-security' ),
				'icon'  => 'system',
				'items' => array(
					( new TabItem( 'performance', '', false, '', '', __( 'Early Phase', 'botblocker-security' ), 'gauge' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/status.svg' ),
					( new TabItem( 'cookie', '', false, '', '', __( 'Cookie Settings', 'botblocker-security' ), 'cookie2' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/cookie.svg' ),
					( new TabItem( 'general', '', false, '', '', __( 'General', 'botblocker-security' ), 'gear' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/general.svg' ),
					( new TabItem( 'error', '', false, '', '', __( 'Error &amp; Access', 'botblocker-security' ), 'alert' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/error-access.svg' ),
					( new TabItem( 'advanced-protection', '', false, '', '', __( 'Advanced Protection', 'botblocker-security' ), 'shield' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/advanced-protection.svg' ),
					( new TabItem( 'settings-ui', '', false, '', '', __( 'UI Settings', 'botblocker-security' ), 'eye' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/ui.svg' ),
					( new TabItem( 'notifications', '', false, '', '', __( 'Notifications', 'botblocker-security' ), 'bell' ) )
						->withIconImage( BOTBLOCKER_URL . 'public/icons/notification.svg' ),
				),
			),
		);

		$sm = (int) $this->get( 'secure_mode', '2' );
		$this->preset_name = __( 'Recommended preset', 'botblocker-security' );
		$this->mode_label  = $sm === 2 ? __( 'Full mode', 'botblocker-security' ) : __( 'Frontend only mode', 'botblocker-security' );

		$this->cache_durations         = bbcs_get_cache_durations();
		$this->cookie_lifetimes        = bbcs_get_cookie_lifetimes();
		$this->ptr_lifetimes           = bbcs_get_ptr_lifetimes();
		$this->subnet_mask_options     = bbcs_get_subnet_mask_options();
		$this->ptrcache_rule_ttl_options = bbcs_get_ptrcache_rule_ttl_options();
		$this->rate_subnet_mask_options = bbcs_get_rate_subnet_mask_options();

		$this->error_headers = isset( $BBCS->error_headers ) && is_array( $BBCS->error_headers ) ? $BBCS->error_headers : array();

		$this->x_robots_directives = BotBlockerData::getXRobotTags();
		$this->wp_cron_url = rtrim( site_url(), '/' ) . '/wp-cron.php?doing_wp_cron';
		$this->curl_cmd    = BOTBLOCKER_CRON_SCHEDULE . ' curl -s "' . $this->wp_cron_url . '" > /dev/null 2>&1';
		$this->wget_cmd    = BOTBLOCKER_CRON_SCHEDULE . ' wget -q -O - "' . $this->wp_cron_url . '" > /dev/null 2>&1';
		$this->wp_cron_enabled = defined( 'BOTBLOCKER_WP_CRON_ENABLED' ) && BOTBLOCKER_WP_CRON_ENABLED;

		$this->current_ja3 = '';
		if ( isset( $_SERVER['HTTP_X_TLS_JA3'] ) ) {
			$this->current_ja3 = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_TLS_JA3'] ) );
		} elseif ( isset( $_SERVER['HTTP_CF_JA3_FINGERPRINT'] ) ) {
			$this->current_ja3 = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_JA3_FINGERPRINT'] ) );
		}
		$this->current_ja4 = '';
		if ( isset( $_SERVER['HTTP_X_TLS_JA4'] ) ) {
			$this->current_ja4 = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_TLS_JA4'] ) );
		}

		$this->docs_url = BOTBLOCKER_DOCS_URL;

		$this->buildCronTaskData();
	}

	private function buildCronTaskData(): void {
		$all_tasks    = BotBlockerCron::getAllTasks();
		$labels       = BotBlockerCron::getTaskLabels();
		$cron_jobs    = _get_cron_array();
		if ( ! is_array( $cron_jobs ) ) {
			$cron_jobs = array();
		}
		$current_time = time();

		$this->cron_tasks           = array();
		$this->cron_recurring_count  = 0;
		$this->cron_one_time_count   = 0;

		$schedules = wp_get_schedules();
		$asn_download_hook = 'bbcs_asn_db_download_event';
		$asn_window        = 5 * MINUTE_IN_SECONDS;

		foreach ( $all_tasks as $hook => $def ) {
			$task          = new Botblocker_CronTaskData();
			$task->hook    = $hook;
			$task->interval = $def['interval'];
			$task->label   = isset( $labels[ $hook ] ) ? $labels[ $hook ] : $hook;

			if ( $def['schedule'] !== null ) {
				$task->type     = 'recurring';
				$task->schedule = isset( $schedules[ $def['schedule'] ]['display'] )
					? $schedules[ $def['schedule'] ]['display']
					: $def['schedule'];
				$this->cron_recurring_count++;
			} else {
				$task->type     = 'one-time';
				$task->schedule = self::formatInterval( $def['interval'] );
				$this->cron_one_time_count++;
			}

			// Find next run and calculate progress
			$task->next_run = null;
			$task->status   = 'pending';
			$task->progress = 0.0;
			$task->time_remaining = 0;

			foreach ( $cron_jobs as $timestamp => $hooks ) {
				if ( isset( $hooks[ $hook ] ) ) {
					$task->next_run = $timestamp;
					$task->time_remaining = $timestamp > $current_time
						? $timestamp - $current_time
						: 0;

					if ( $timestamp <= $current_time ) {
						$overdue_threshold = $def['interval'] * 1.5;
						$task->status       = ( $current_time - $timestamp ) > $overdue_threshold ? 'overdue' : 'active';
					} else {
						$task->status = 'active';
					}

					if ( $hook === 'bbcs_one_time_task' ) {
						$one_time_event = bbcs_get_scheduled_event( 'bbcs_one_time_task' );
						if ( is_object( $one_time_event ) ) {
							$time_left = $one_time_event->timestamp - $current_time;
							$task->progress = $time_left > 0
								? round( min( 100, max( 0, ( ( 600 - $time_left ) / 600 ) * 100 ) ), 2 )
								: 0.0;
						}
					} elseif ( $hook === $asn_download_hook ) {
						$delta          = max( 0, $timestamp - $current_time );
						$task->progress = round( min( 100, max( 0, ( ( $asn_window - $delta ) / $asn_window ) * 100 ) ), 2 );
					} else {
						$interval = $def['interval'];
						$task->progress = $interval > 0
							? round( min( 100, max( 0, ( ( $current_time - ( $timestamp - $interval ) ) / $interval ) * 100 ) ), 2 )
							: 0.0;
					}

					break;
				}
			}

			if ( $task->next_run !== null ) {
				$task->next_run_display = bbcs_wp_date( 'Y-m-d H:i', $task->next_run );
			} else {
				$task->next_run_display = null;
			}

			$this->cron_tasks[] = $task;
		}

		$this->cron_total_count = count( $this->cron_tasks );

		// Custom intervals for display cards
		$this->cron_custom_intervals = array(
			'every_five_days' => array(
				'label'    => __( 'Every 5 Days', 'botblocker-security' ),
				'seconds'  => 5 * DAY_IN_SECONDS,
				'used_by'  => array(
					__( 'ASN Database Freshness Check', 'botblocker-security' ),
				),
			),
		);
	}

	public function get( string $key, $default = '' ) {
		return $this->settings[ $key ] ?? $default;
	}

	public function is_on( string $key ): bool {
		return ! empty( $this->settings[ $key ] );
	}

	public function is_checked( string $key, $expected = 1 ): bool {
		$val = $this->settings[ $key ] ?? 0;
		if ( is_bool( $val ) ) {
			return $val;
		}
		return (string) $val === (string) $expected;
	}

	public function get_selected( string $key, $value ): string {
		$current = $this->settings[ $key ] ?? '';
		if ( is_numeric( $current ) && is_numeric( $value ) ) {
			return (float) $current === (float) $value ? 'selected' : '';
		}
		return (string) $current === (string) $value ? 'selected' : '';
	}

	public function get_secure_mode(): int {
		return (int) ( $this->settings['secure_mode'] ?? 2 );
	}

	public function get_hits_per_user(): string {
		return (string) ( $this->settings['hits_per_user'] ?? '500' );
	}

	public function get_ptrcache_ttl(): string {
		return (string) ( $this->settings['ptrcache_time'] ?? '86400' );
	}

	public function get_cloud_timeout(): string {
		return (string) ( $this->settings['cloud_api_timeout'] ?? '5' );
	}

	public function get_selected_directive( string $directive ): bool {
		$selected = $this->settings['x_robots_directives'] ?? array();
		if ( ! is_array( $selected ) ) {
			$decoded = json_decode( $selected, true );
			$selected = is_array( $decoded ) ? $decoded : array();
		}
		return in_array( $directive, $selected, true );
	}

	public function is_payment_enabled(): bool {
		return isset( $this->settings['payment_bypass_enable'] ) && (int) $this->settings['payment_bypass_enable'] === 1;
	}

	public function gmt_label( $offset ): string {
		if ( (float) $offset === 0.0 ) {
			return 'GMT';
		}
		$prefix = (float) $offset > 0 ? 'GMT+' : 'GMT';
		return $prefix . $offset;
	}

	public function store_period_label( int $days ): string {
		return $days . ' ' . __( 'days', 'botblocker-security' );
	}

	public function captcha_img_pack_disabled(): bool {
		$mode = $this->get( 'bbcs_captcha_mode', BOTBLOCKER_CAPTCHA_MODE_DEFAULT );
		return $mode !== '2';
	}

	/**
	 * Convert seconds to a human-readable interval label.
	 *
	 * Used for one-time-type tasks that repeat via wp_schedule_single_event().
	 *
	 * @param int $seconds Interval in seconds.
	 * @return string e.g. "Every 10 min", "Every 5 min", "Every 2 min".
	 */
	private static function formatInterval( int $seconds ): string {
		if ( $seconds % 3600 === 0 ) {
			$hours = $seconds / 3600;
			// translators: %d: number of hours
			return sprintf( _n( 'Every %d hour', 'Every %d hours', $hours, 'botblocker-security' ), $hours );
		}
		if ( $seconds % 60 === 0 ) {
			$minutes = $seconds / 60;
			/* translators: %d: number of minutes */
			return sprintf( __( 'Every %d min', 'botblocker-security' ), $minutes );
		}
		// translators: %d: number of seconds
		return sprintf( __( 'Every %d s', 'botblocker-security' ), $seconds );
	}
}
