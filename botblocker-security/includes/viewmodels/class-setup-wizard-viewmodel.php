<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_SetupWizard_ViewModel {

	/** @var string Plugin version for display in plughead chip. */
	public $version;
	/** @var bool Whether a PRO license is active. */
	public $has_pro;
	/** @var bool Whether the Early Init addon is active. */
	public $early_addon_active;
	/** @var bool Whether Early Init is available (PRO + addon active). */
	public $early_available;
	/** @var string Pre-filled contact email (current user email). */
	public $contact_email;
	/** @var bool Whether the contact email has already been collected. */
	public $contact_collected;
	/** @var string Current visitor IP address for display in exclusions step. */
	public $current_ip;

	/** @var string */
	public $dashboard_url;
	/** @var string */
	public $reports_url;
	/** @var string */
	public $rules_url;
	/** @var string */
	public $settings_url;
	/** @var string */
	public $integrations_url;
	/** @var string */
	public $addons_url;
	/** @var string */
	public $pro_url;
	/** @var string */
	public $docs_url;
	/** @var string */
	public $early_init_slug;

	/** @var string URL for the silent-mode CAPTCHA preview image. */
	public $captcha_preview_img_url;

	/** @var string BotBlocker logo URL for the plughead. */
	public $logo_url;

	public function __construct() {
		$bbcs  = BotBlocker::getInstance();
		$bbcsa = Botblocker_Admin::getInstance();

		$this->version           = defined( 'BOTBLOCKER_VERSION' ) ? BOTBLOCKER_VERSION : '';
		$this->has_pro             = class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive();
		$this->early_addon_active  = class_exists( 'BotBlockerGateway' ) && BotBlockerGateway::isRegistered( 'early_init' );
		$this->early_available     = $this->has_pro && $this->early_addon_active;
		$this->early_init_slug     = class_exists( 'BotBlockerGateway' ) ? BotBlockerGateway::firstSlug( 'early_init', 'bbcs-early-init' ) : 'bbcs-early-init';
		$this->contact_email     = wp_get_current_user()->user_email;
		$this->contact_collected = (bool) BotBlockerMultisite::getOption( 'bbcs_contact_email_collected', false );
		$this->current_ip        = BotBlockerIp::getCurrentIp();

		$this->dashboard_url    = $bbcsa->pages_dashboard;
		$this->reports_url      = $bbcsa->pages_reports;
		$this->rules_url        = $bbcsa->pages_rules;
		$this->settings_url     = $bbcsa->pages_settings;
		$this->integrations_url = $bbcsa->pages_integrations;
		$this->addons_url       = $bbcsa->pages_addons;
		$this->pro_url          = $bbcsa->pages_cloud_api;
		$this->docs_url         = BOTBLOCKER_DOCS_URL;

		$this->captcha_preview_img_url = BOTBLOCKER_MATERIALS_URL . 'image/silent-mode.webp';
		$this->logo_url                = $bbcs->media_logo_botblocker;
	}


}
