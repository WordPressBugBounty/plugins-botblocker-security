<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/dto/class-pro-status-option-data.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-snav.php';

final class Botblocker_ProStatusViewModel {
	/** @var bool */
	public $has_pro;
	/** @var string Current license tier: premium|pro|ultimate, '' when not active. */
	public $tier;
	/** @var int|false Remaining API requests, false when not cached. */
	public $remaining_hits;
	/** @var int|false Remaining subscription days, false when not cached. */
	public $remaining_days;
	/** @var bool True when PRO counters are missing and the dashboard should fetch them. */
	public $should_fetch;
	/** @var Botblocker_ProStatusOptionData[] */
	public $options;
	/** @var string 'ok'|'warn'|'critical' */
	public $alert_level;
	/** @var string PRO management / upgrade page URL. */
	public $upgrade_url;

	public function __construct() {
		$bbcsa = Botblocker_Admin::getInstance();

		$this->upgrade_url    = $bbcsa->pages_cloud_api;
		$this->has_pro        = BotBlockerPro::isActive();
		$this->tier           = BotBlockerPro::getTier();
		$this->remaining_hits = BotBlockerPro::getRemainingHits();
		$this->remaining_days = BotBlockerPro::getRemainingDays();
		$this->should_fetch   = $this->has_pro && ( $this->remaining_hits === false || $this->remaining_days === false );
		$this->options        = $this->buildOptions();
		$this->alert_level    = $this->buildAlertLevel();
	}

	/**
	 * @return Botblocker_ProStatusOptionData[]
	 */
	private function buildOptions(): array {
		$BBCS  = BotBlocker::getInstance();
		$defs  = $this->optionDefs();
		$items = array();

		foreach ( $defs as $def ) {
			$key         = $def['key'];
			$tier        = BotBlockerPro::PRO_FEATURE_TIERS[ $key ];
			$section_url = $this->resolveSectionUrl( $key );

			$status = 'locked';
			if ( BotBlockerPro::allowsFeature( $key ) ) {
				$status = $this->isChecked( $BBCS, $key ) ? 'on' : 'off';
			}

			$items[] = new Botblocker_ProStatusOptionData(
				$key,
				$def['label'],
				$def['tooltip'],
				$tier,
				$status,
				$section_url
			);
		}

		return $items;
	}

	/**
	 * @return array<int, array{key: string, label: string, tooltip: string}>
	 */
	private function optionDefs(): array {
		return array(
			array(
				'key'     => 'check',
				'label'   => __( 'Cloud Validation', 'botblocker-security' ),
				'tooltip' => __( 'Send visitors on the check page to BotBlocker Cloud for a verdict.', 'botblocker-security' ),
			),
			array(
				'key'     => 'unresponsive',
				'label'   => __( 'Block Unresponsive Clients', 'botblocker-security' ),
				'tooltip' => __( 'Block clients that don\'t respond to verification checks.', 'botblocker-security' ),
			),
			array(
				'key'     => 'cloud_fallback_block',
				'label'   => __( 'Block on Cloud API Errors', 'botblocker-security' ),
				'tooltip' => __( 'When cloud API returns unexpected data - block the visitor instead of silent pass.', 'botblocker-security' ),
			),
			array(
				'key'     => 'force_cloud_validation',
				'label'   => __( 'Force Cloud Validation', 'botblocker-security' ),
				'tooltip' => __( 'Verify every visitor via cloud database. Ultimate tier only.', 'botblocker-security' ),
			),
			array(
				'key'     => 'hosting_block',
				'label'   => __( 'Hosting Provider IPs', 'botblocker-security' ),
				'tooltip' => __( 'Block data center IPs (VPS, AWS, DigitalOcean, etc.). Search engines are always whitelisted.', 'botblocker-security' ),
			),
			array(
				'key'     => 'block_vpn_users',
				'label'   => __( 'VPN Connections', 'botblocker-security' ),
				'tooltip' => __( 'Block traffic from known VPN service IP addresses.', 'botblocker-security' ),
			),
			array(
				'key'     => 'block_tor_users',
				'label'   => __( 'Tor Exit Nodes', 'botblocker-security' ),
				'tooltip' => __( 'Block traffic from known Tor exit nodes.', 'botblocker-security' ),
			),
		);
	}

	private function resolveSectionUrl( string $key ): string {
		return BotBlockerSnav::getSettingLink( $key );
	}

	private function isChecked( $BBCS, string $key ): bool {
		$val = isset( $BBCS->settings->{$key} ) ? $BBCS->settings->{$key} : 0;
		if ( is_bool( $val ) ) {
			return $val;
		}
		return (string) $val === '1';
	}

	private function buildAlertLevel(): string {
		return self::calcAlertLevel( $this->has_pro, $this->remaining_hits, $this->remaining_days );
	}

	/**
	 * Single source of truth for the PRO alert level, shared by the dashboard
	 * PRO Status card and the header pill so both can never drift apart.
	 *
	 * @param mixed    $hits Remaining requests (false when not cached) or 0 when exhausted.
	 * @param mixed    $days Remaining subscription days (false when not cached) or 0 when expired.
	 */
	public static function calcAlertLevel( bool $has_pro, $hits, $days ): string {
		if ( ! $has_pro ) {
			return 'ok';
		}

		if ( $hits === false && $days === false ) {
			return 'ok';
		}

		if ( ( $hits !== false && (int) $hits === 0 ) || ( $days !== false && (int) $days === 0 ) ) {
			return 'critical';
		}

		$hits_threshold = max( BotBlockerPro::getHitsWarningDays() );
		$days_threshold = max( BotBlockerPro::getExpiryWarningDays() );

		if ( ( $hits !== false && (int) $hits <= $hits_threshold ) || ( $days !== false && (int) $days <= $days_threshold ) ) {
			return 'warn';
		}

		return 'ok';
	}
}
