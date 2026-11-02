<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-header-viewmodel.php';
require_once __DIR__ . '/class-sidebar-viewmodel.php';

final class Botblocker_SelfTestViewModel {
	/** @var Botblocker_HeaderViewModel */
	public $header;
	/** @var Botblocker_SidebarViewModel */
	public $sidebar;
	/** @var string */
	public $host;
	/** @var string */
	public $ajax_url;
	/** @var string */
	public $nonce;

	public function __construct() {
		$this->header   = new Botblocker_HeaderViewModel();
		$this->sidebar  = new Botblocker_SidebarViewModel();
		$this->host     = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$this->ajax_url = admin_url( 'admin-ajax.php' );
		$this->nonce    = wp_create_nonce( BotBlockerSelfTest::NONCE_ACTION );
	}

	public function scriptConfig(): array {
		return array(
			'ajaxUrl' => $this->ajax_url,
			'nonce'   => $this->nonce,
			'host'    => $this->host,
			'i18n'    => $this->i18n(),
		);
	}

	private function i18n(): array {
		return array(
			'runAgain'          => __( 'Run again', 'botblocker-security' ),
			'testing'           => __( 'Testing…', 'botblocker-security' ),
			'startingTitle'     => __( 'Starting…', 'botblocker-security' ),
			'startingText'      => __( 'Preparing a one-time test session.', 'botblocker-security' ),
			'startFailedTitle'  => __( 'Could not start the test', 'botblocker-security' ),
			'startFailedText'   => __( 'Reload the page and try again.', 'botblocker-security' ),
			'statusFailedTitle' => __( 'Status check failed', 'botblocker-security' ),
			'statusFailedText'  => __( 'The connection to your site was interrupted.', 'botblocker-security' ),
			'waitingForStep'    => __( 'Waiting for the first step…', 'botblocker-security' ),
			'whyTitle'          => __( 'Why you don’t see a captcha yourself', 'botblocker-security' ),
			'result'            => array(
				'worksTitle'         => __( 'Protection works', 'botblocker-security' ),
				'worksSolvedServed'  => __( 'A new visitor gets a captcha. After solving it, the site opens.', 'botblocker-security' ),
				'worksSolvedPending' => __( 'A new visitor gets a captcha. Solve it in the window on the right to finish the test.', 'botblocker-security' ),
				'worksSilentServed'  => __( 'A new visitor passed the Silent captcha automatically, then the site opened.', 'botblocker-security' ),
				'worksSilentPending' => __( 'A new visitor is going through the Silent captcha.', 'botblocker-security' ),
				'checkedTitle'       => __( 'Checked, no captcha needed', 'botblocker-security' ),
				'checkedServed'      => __( 'The visitor passed the background check and the site opened. Force verification is off, so no captcha was required.', 'botblocker-security' ),
				'checkedPending'     => __( 'The visitor passed the background check. Force verification is off, so no captcha was required.', 'botblocker-security' ),
				'notCheckedTitle'    => __( 'New visitors are not checked', 'botblocker-security' ),
				'notCheckedText'     => __( 'The site opened without any check.', 'botblocker-security' ),
				'checkingTitle'      => __( 'Checking the visitor…', 'botblocker-security' ),
				'checkingText'       => __( 'The check page is running.', 'botblocker-security' ),
				'loadingTitle'       => __( 'Loading the site…', 'botblocker-security' ),
				'loadingText'        => __( 'Opening your home page as a new visitor.', 'botblocker-security' ),
			),
			'settings'          => array(
				'title'       => __( 'Current settings', 'botblocker-security' ),
				'protection'  => __( 'Protection', 'botblocker-security' ),
				'mode'        => __( 'Mode', 'botblocker-security' ),
				'captcha'     => __( 'Captcha', 'botblocker-security' ),
				'forceCheck'  => __( 'Force verification for all', 'botblocker-security' ),
				'on'          => __( 'On', 'botblocker-security' ),
				'off'         => __( 'Off', 'botblocker-security' ),
				'silent'      => __( 'Silent', 'botblocker-security' ),
				'silentAlert' => __( 'Silent captcha is on. Every new visitor goes through the captcha, but it runs automatically, so there is nothing to click or solve.', 'botblocker-security' ),
			),
			'steps'             => array(
				'allowIgnored'       => __( 'Allow rule for your IP ignored', 'botblocker-security' ),
				'challengePage'      => __( 'Check page shown', 'botblocker-security' ),
				'challengePageNote'  => __( 'The visitor has no pass cookie', 'botblocker-security' ),
				'captchaSilent'      => __( 'Silent captcha started', 'botblocker-security' ),
				'captchaSilentNote'  => __( 'Runs automatically, no interaction', 'botblocker-security' ),
				'captchaShown'       => __( 'Captcha shown to the visitor', 'botblocker-security' ),
				'captchaShownNote'   => __( 'Force verification is on', 'botblocker-security' ),
				'passedSilently'     => __( 'Passed the background check', 'botblocker-security' ),
				'passedSilentlyNote' => __( 'Force verification is off, so no captcha was needed', 'botblocker-security' ),
				'timeout'            => __( 'Captcha timed out', 'botblocker-security' ),
				'timeoutNote'        => __( 'The check page reloads with a new captcha. Not banned, because this is a test', 'botblocker-security' ),
				'wrong'              => __( 'Wrong captcha answer', 'botblocker-security' ),
				'wrongNote'          => __( 'Not banned, because this is a test', 'botblocker-security' ),
				'solvedSilent'       => __( 'Silent captcha passed', 'botblocker-security' ),
				'solvedSilentNote'   => __( 'Solved automatically in the browser', 'botblocker-security' ),
				'solved'             => __( 'Captcha solved', 'botblocker-security' ),
				'served'             => __( 'Site opened for the verified visitor', 'botblocker-security' ),
				/* translators: %d: number of seconds, shown as an offset (e.g. +3s). */
				'timeSuffix'         => __( '+%ds', 'botblocker-security' ),
			),
			'bypass'            => array(
				'admin_role'  => __( 'You are logged in as an administrator or editor. BotBlocker never checks them.', 'botblocker-security' ),
				'logged_in'   => __( 'Logged-in users are skipped in your settings.', 'botblocker-security' ),
				'pass_cookie' => __( 'Your browser already passed the check and keeps a pass cookie.', 'botblocker-security' ),
				'allowed_ip'  => __( 'Your IP address is on the allow list.', 'botblocker-security' ),
			),
		);
	}
}
