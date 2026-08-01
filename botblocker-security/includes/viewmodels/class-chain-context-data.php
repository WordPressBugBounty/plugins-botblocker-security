<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ViewModel for the request processing chain visualization.
 *
 * Pre-computes all display data (descriptions, block_at, active flags)
 * so templates receive pure presentation data with zero business logic.
 *
 * Legacy getters retained for chain-body.php backward compat.
 */
final class Botblocker_ChainContextData {
	// ── Public properties for templates (request-chain.php) ──

	/** @var bool */
	public $early_init_active;

	/** @var bool */
	public $mu_active;

	/** @var bool */
	public $botblocker_active;

	/** @var bool */
	public $show_text;

	/** @var bool */
	public $early_available;

	/** @var string */
	public $cloud_api_url;

	/** @var string */
	public $addons_url;

	/** @var int 0=early, 1=mu, 2=botblocker */
	public $block_at;

	/** @var string Translated description for the early init step */
	public $early_desc;

	/** @var string Translated description for the MU step */
	public $mu_desc;

	/** @var string Translated description for the BotBlocker step */
	public $plugin_desc;

	// ── Legacy spin classes (for chain-body.php backward compat) ──

	/** @var string CSS class for early init spin icon */
	private $early_spin;

	/** @var string CSS class for MU spin icon */
	private $mu_spin;

	/** @var string CSS class for plugin spin icon */
	private $plugin_spin;

	public function __construct(
		bool $early_init_active,
		bool $mu_active,
		bool $botblocker_active = true,
		bool $show_text = true,
		bool $early_available = true,
		string $cloud_api_url = '',
		string $addons_url = ''
	) {
		$this->early_init_active = $early_init_active;
		$this->mu_active         = $mu_active;
		$this->botblocker_active = $botblocker_active;
		$this->show_text         = $show_text;
		$this->early_available   = $early_available;
		$this->cloud_api_url     = $cloud_api_url;
		$this->addons_url        = $addons_url;

		// ── Determine where bad requests get blocked (earliest active layer wins) ──
		if ( $early_init_active ) {
			$this->block_at = 0;
		} elseif ( $mu_active ) {
			$this->block_at = 1;
		} else {
			$this->block_at = 2;
		}

		// Mutual exclusion: MU is bypassed when Early Init is active
		$mu = $mu_active;
		if ( $early_init_active && $mu ) {
			$mu = false;
		}

		// ── Descriptions (translated in ViewModel per refactor-rules.md Rule 1) ──
		if ( $early_init_active ) {
			$this->early_desc = __( 'Early initialization enabled. IP blacklist and base rule filtering run before WordPress loads. MU mode is not required.', 'botblocker-security' );
			$this->mu_desc    = __( 'MU mode disabled. Early initialization already performs pre-filtering. Enabling MU is unnecessary.', 'botblocker-security' );
		} elseif ( $mu ) {
			$this->early_desc = __( 'Early initialization disabled. Its functions are handled by the active MU plugin.', 'botblocker-security' );
			$this->mu_desc    = __( 'MU plugin active. Early IP and rule filtering run before other plugins. Early initialization is not required.', 'botblocker-security' );
		} else {
			$this->early_desc = __( 'Early initialization disabled. Enable it for earlier IP filtering.', 'botblocker-security' );
			$this->mu_desc    = __( 'MU plugin mode disabled. You can enable it (or early initialization) for preliminary malicious IP rejection.', 'botblocker-security' );
		}

		$this->plugin_desc = ( $early_init_active || $mu_active )
			? __( 'BotBlocker operates in normal mode processing all threat types (bots, proxies, referrers, languages etc.) after base early filtering.', 'botblocker-security' )
			: __( 'BotBlocker operates in normal mode processing all threat types at WordPress load.', 'botblocker-security' );

		// ── Legacy spin classes (Font Awesome, used by chain-body.php) ──
		$this->early_spin  = $early_init_active ? ' fa-spin' : '';
		$this->mu_spin     = $mu_active ? ' fa-spin' : '';
		$this->plugin_spin = ' fa-spin';

		if ( $early_init_active && $mu_active ) {
			$this->mu_spin = '';
		}
	}

	// ── Legacy getters (for chain-body.php backward compat) ──

	public function getEarlySpin(): string {
		return $this->early_spin;
	}

	public function getMuSpin(): string {
		return $this->mu_spin;
	}

	public function getPluginSpin(): string {
		return $this->plugin_spin;
	}

	public function getEarlyText(): string {
		return $this->early_desc;
	}

	public function getMuText(): string {
		return $this->mu_desc;
	}

	public function getPluginText(): string {
		return $this->plugin_desc;
	}

	public function isEarlyActive(): bool {
		return $this->early_init_active;
	}

	public function isMuActive(): bool {
		return $this->mu_active;
	}
}
