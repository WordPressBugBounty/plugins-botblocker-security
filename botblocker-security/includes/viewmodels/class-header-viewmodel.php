<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/dto/class-alert-item-data.php';
require_once BOTBLOCKER_DIR . 'includes/dto/class-language-option-data.php';

final class Botblocker_HeaderViewModel {

	/**
	 * @param array[] $alerts Raw alert arrays from BotBlockerAlerts.
	 * @return Botblocker_AlertItemData[]
	 */
	private static function mapAlerts( array $alerts ): array {
		return array_map(
			static function ( $a ): Botblocker_AlertItemData {
				if ( ! is_array( $a ) ) {
					$a = array();
				}
				return new Botblocker_AlertItemData(
					$a['title'] ?? '',
					$a['message'] ?? '',
					$a['type'] ?? '',
					$a['icon'] ?? '',
					$a['link'] ?? '',
					$a['link_text'] ?? ''
				);
			},
			$alerts
		);
	}

	/**
	 * @param array[] $options Raw language option arrays.
	 * @return Botblocker_LanguageOptionData[]
	 */
	private static function mapLangOptions( array $options ): array {
		return array_map(
			static function ( array $o ): Botblocker_LanguageOptionData {
				return new Botblocker_LanguageOptionData(
					$o['lang'] ?? '',
					$o['flag'] ?? '',
					$o['name'] ?? ''
				);
			},
			$options
		);
	}


	/** @var string */
	public $logo_url;
	/** @var string */
	public $site_name;
	/** @var string */
	public $dashboard_url;
	/** @var string */
	public $cloud_api_url;
	/** @var string */
	public $about_url;
	/** @var string */
	public $setup_url;
	/** @var string */
	public $wizard_url;
	/** @var bool */
	public $wizard_completed;
	/** @var bool */
	public $has_pro;
	/** @var Botblocker_AlertItemData[] */
	public $alerts;
	/** @var int */
	public $alerts_count;
	/** @var string */
	public $display_name;
	/** @var string */
	public $user_role;
	/** @var string */
	public $avatar_url;
	/** @var Botblocker_LanguageOptionData[] */
	public $lang_options;
	/** @var string Plugin version shown in the header chip. */
	public $version;
	/** @var bool Whether BotBlocker protection is currently active. */
	public $is_active;
	/** @var string Protection status label for the header pill. */
	public $status_label;
	/** @var string Current locale (respects bbcs_preferred_language cookie). */
	public $current_locale;

	public function __construct() {
		$bbcs  = BotBlocker::getInstance();
		$bbcsa = Botblocker_Admin::getInstance();

		$alerts = BotBlockerAlerts::getAll();

		$this->logo_url         = $bbcs->media_logo_botblocker;
		$this->site_name        = BOTBLOCKER_SHORT_NAME;
		$this->dashboard_url    = $bbcsa->pages_dashboard;
		$this->cloud_api_url    = $bbcsa->pages_cloud_api;
		$this->about_url        = $bbcsa->pages_about;
		$this->setup_url        = $bbcsa->pages_setup;
		$this->wizard_url       = BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' );
		$this->wizard_completed = (bool) BotBlockerMultisite::getOption( 'bbcs_setup_wizard_completed', false );
		$this->has_pro          = BotBlockerPro::isActive();
		$this->alerts           = self::mapAlerts( $alerts );
		$this->alerts_count     = count( $alerts );

		$user         = wp_get_current_user();
		$avatar_path  = BotBlockerWpUser::getAvatarPath( $user->ID );
		$display_name = BotBlockerWpUser::getDisplayName( $user->ID );
		$user_role    = BotBlockerWpUser::getUserRole( $user->ID );

		if ( $avatar_path == BOTBLOCKER_EMPTY ) {
			$avatar_path = $bbcsa->custom_avatar;
		}

		$this->avatar_url   = $avatar_path;
		$this->display_name = $display_name;
		$this->user_role    = $user_role;

		$this->lang_options = self::mapLangOptions( bbcs_get_lang_options() );

		$preferred_locale = isset( $_COOKIE['bbcs_preferred_language'] )
			? sanitize_text_field( wp_unslash( $_COOKIE['bbcs_preferred_language'] ) )
			: '';
		$this->current_locale = ! empty( $preferred_locale ) ? $preferred_locale : determine_locale();

		$this->version    = defined( 'BOTBLOCKER_VERSION' ) ? BOTBLOCKER_VERSION : '';
		$this->is_active  = ! $bbcs->isDisabled;
		$this->status_label = $this->is_active
			? esc_html__( 'Protection active', 'botblocker-security' )
			: esc_html__( 'Protection paused', 'botblocker-security' );
	}
}
