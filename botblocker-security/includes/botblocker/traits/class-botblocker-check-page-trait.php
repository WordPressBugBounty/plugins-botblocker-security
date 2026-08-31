<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BotBlockerCheckPageTrait {

	public function render_check_page() {
		if ( $this->should_show_check_page === true ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [CheckPage] render_check_page() called. Mode=' . (int) $this->settings->bbcs_captcha_mode . ' SecureMode=' . (int) $this->settings->secure_mode . ' DDoS=' . (int) $this->settings->bbcs_ddos_resilience );
			}
			$this->assemble_check_page();
			$this->load_check_template();
			$this->process_die();
		}
	}

	private function prepare_captcha_js_data(): void {
		require_once $this->dirs['public'] . 'class-botblocker-captcha-renderer.php';
		$botblocker_check_function_name = $this->js_data['checkFunctionName'];
		$renderer                       = new BotBlockerCaptchaRenderer( $botblocker_check_function_name );

		$this->captcha_data = $renderer->getCaptchaData();
		$token              = $renderer->getChallengeToken();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [CheckPage] prepare_captcha_js_data: mode=' . $this->captcha_data['mode'] . ' token_len=' . strlen( $token ) . ' params_keys=' . implode( ',', array_keys( $this->captcha_data['params'] ?? array() ) ) );
		}
		$this->captcha_data = array_merge(
			$this->captcha_data,
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'botblocker_nonce' ),
				'time'              => $this->time,
				'selectRequestMode' => $this->select_request_mode,
				'checkFunction'     => $this->js_data['checkFunctionName'],
				'data'              => 'window.data',
				'autoRender'        => false,
				'challengeToken'    => $token,
			)
		);
	}

	private function prepare_check_js_data(): void {
		require_once $this->dirs['public'] . 'class-botblocker-captcha-renderer.php';

		$botblocker_parse_url = wp_parse_url( $this->uri );
		$botblocker_output    = array();

		if ( $this->settings->utm_referrer == 1 && $this->referer != '' ) {
			if ( isset( $botblocker_parse_url['query'] ) ) {
				parse_str( $botblocker_parse_url['query'], $botblocker_output );
			}
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- UTM parameters come from external links and do not require nonce verification.
			if ( isset( $_GET['utm_referrer'] ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- UTM parameters come from external links and do not require nonce verification.
				$botblocker_output['utm_referrer'] = trim( wp_strip_all_tags( wp_unslash( $_GET['utm_referrer'] ) ) );
			} else {
				$botblocker_output['utm_referrer'] = $this->referer;
			}
			if ( ! isset( $botblocker_parse_url['path'] ) || $botblocker_parse_url['path'] == '' ) {
				$botblocker_parse_url['path'] = '/';
			}
			$bot_blocker_redirect_url = $botblocker_parse_url['path'] . '?' . http_build_query( $botblocker_output );
		} else {
			$bot_blocker_redirect_url = $this->uri;
		}

		$botblocker_check_function_name = 'f' . md5( $this->ip . $this->time );

		$host_no_port = strstr( $this->host, ':', true );
		if ( $host_no_port === false ) {
			$host_no_port = $this->host;
		}

		$this->js_data = array(
			'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
			'verifyUrl'          => home_url( '/bbcs-verify/' ),
			'nonce'              => wp_create_nonce( 'botblocker_nonce' ),
			'debugEnabled'       => ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG === true ),
			'version'            => $this->version,
			'shortName'          => BOTBLOCKER_SHORT_NAME,
			'hostBase64'         => base64_encode( $this->host ),
			'hostNoPortBase64'   => base64_encode( $host_no_port ),
			'redirectUrlBase64'  => base64_encode( esc_url_raw( $this->scheme . '://' . $this->host . $this->uri ) ),
			'loadingText'           => BotBlockerCaptchaRenderer::t( 'Loading...' ),
			'testHash'              => hash( 'sha256', $this->useragent . $this->ip . $this->time . $this->country . $this->ptr . $this->settings->salt ),
			'h1Hash'                => hash( 'sha256', $this->settings->cloud_api_email . $this->settings->cloud_api_pass . $this->host . $this->useragent . $this->ip . $this->time ),
			'time'                  => $this->time,
			'hosting'               => $this->hosting,
			'country'               => $this->country,
			'ip'                    => $this->ip,
			'cid'                   => $this->cid,
			'ptr'                   => $this->ptr,
			'httpAccept'            => urlencode( $this->http_accept ),
			'ipVersion'             => $this->ip_version,
			'recaptchaEnabled'      => ( $this->settings->recaptcha_check == 1 && ! empty( $this->settings->recaptcha_key3 ) && ! ( (int) $this->settings->recaptcha_v3_ipv6_block === 1 && $this->ip_version == 6 ) ),
			'recaptchaKey3'         => $this->settings->recaptcha_key3,
			'apiIpv6'               => BOTBLOCKER_API_IPV6,
			'apiGsIpv6'             => BOTBLOCKER_API_GS_IPV6,
			'emptyValue'            => BOTBLOCKER_EMPTY,
			'jsAdminEnabled'        => ( defined( 'BOTBLOCKER_JS_ADMIN' ) && BOTBLOCKER_JS_ADMIN == true ),
			'jsErrorMessage'        => $this->js_error_message,
			'cookieDisabledText'    => BotBlockerCaptchaRenderer::t( 'Cookies are disabled in your browser. Enable cookies to continue.' ),
			'jsDisabledText'        => BotBlockerCaptchaRenderer::t( 'JavaScript is disabled in your browser. Enable JavaScript to continue.' ),
			'redirectUrl'        => esc_js( esc_url_raw( $bot_blocker_redirect_url ) ),
			'checkFunctionName'  => $botblocker_check_function_name,
			'selectRequestMode'  => $this->select_request_mode,
			'ruleRecordId'       => $this->rule_record_id,
			'suspectStatus'      => $this->suspect_status,
			'reasonForAction'    => $this->reason_for_action,
			'resultOfAction'     => $this->result_of_action,
			'uid'                => $this->uid,
			'samesite'           => self::effective_samesite( $this->settings->samesite ),
			'cookieLifetime'     => (int) $this->settings->cookie_lifetime,
			'botblockerUrl'      => $this->botblockerUrl,
			'captchaURL'         => esc_url( 'https://www.google.com/recaptcha/api.js?render=' . $this->settings->recaptcha_key3 ),
			'silentMode'         => ( (int) $this->settings->bbcs_captcha_mode === BOTBLOCKER_CAPTCHA_MODE_SILENT ),
			'approvedText'           => BotBlockerCaptchaRenderer::t( 'Access approved. Redirecting...' ),
			'verifyingText'          => BotBlockerCaptchaRenderer::t( 'Verifying...' ),
			'retryingText'           => BotBlockerCaptchaRenderer::t( 'Retrying...' ),
			'verificationFailedText' => BotBlockerCaptchaRenderer::t( 'Verification failed. Please complete the challenge below.' ),
			'ddosResilience'         => ( $this->settings->bbcs_ddos_resilience == 1 ),
		);
	}

	private function create_custom_check_style(): string {
		$time          = $this->time ?? time();
		$success_class = 's' . md5( 'botblocker-btn-success' . $time );
		$color_class   = 's' . md5( 'botblocker-btn-color' . $time );

		$css  = '.' . $success_class . '{';
		$css .= 'border:1px solid transparent;';
		$css .= 'background:#7785ef;';
		$css .= 'color:#ffffff;';
		$css .= 'font-size:16px;';
		$css .= 'line-height:15px;';
		$css .= 'padding:10px 15px;';
		$css .= 'text-decoration:none;';
		$css .= 'text-shadow:none;';
		$css .= 'border-radius:5px;';
		$css .= 'box-shadow:none;';
		$css .= 'transition:0.25s;';
		$css .= 'display:block;';
		$css .= 'margin:0 auto;';
		$css .= 'font-weight:600;';
		$css .= '}';
		$css .= '.' . $success_class . ':hover{background-color:#bfc7ff;}';

		$css .= '.' . $color_class . '{';
		$css .= 'cursor:pointer;';
		$css .= 'padding:14px 14px;';
		$css .= 'text-decoration:none;';
		$css .= 'display:inline-block;';
		$css .= 'width:16px;';
		$css .= 'height:16px;';
		$css .= 'border-radius:80px;';
		$css .= '}';
		$css .= '.' . $color_class . ':hover{width:16px;height:16px;}';

		return $css;
	}

	private function create_check_inline_assets(): array {
		$captcha_mode = isset( $this->settings->bbcs_captcha_mode ) ? (int) $this->settings->bbcs_captcha_mode : 0;
		$external     = array();
		$styles       = array(
			BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'css/template.css' ),
			$this->create_custom_check_style(),
		);

		$scripts = array(
			'var bbcsJsData = ' . BotBlockerSecurityPageAssets::json( $this->js_data ) . ';',
			'window.adb_var = 1;',
			BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'js/rails.js' ),
			BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'js/detection-utils.js' ),
			BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'js/bbidentfunc.js' ),
		);

		if ( ! empty( $this->js_data['recaptchaEnabled'] ) ) {
			$external[] = $this->js_data['captchaURL'];
		}
		if ( $this->settings->bbcs_ddos_resilience == 1 ) {
			$scripts[] = BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'js/bbcs-circuit-breaker.js' );
		}

		$addon_js = null;
		if ( $captcha_mode >= 90 && class_exists( 'BotBlockerCaptchaRegistry' ) && BotBlockerCaptchaRegistry::has( $captcha_mode ) ) {
			try {
				$assets = BotBlockerCaptchaRegistry::getAssets( $captcha_mode );
				if ( '' !== $assets['js_content'] ) {
					$addon_js = $assets;
				}
			} catch ( \Throwable $e ) {
				$addon_js = null;
			}
			if ( null === $addon_js ) {
				// Provider JS vanished between render and asset assembly —
				// rebuild the page data as a full mode-0 challenge.
				$this->fallback_check_captcha_data_to_mode0();
				$captcha_mode = 0;
			}
		}

		$scripts[] = 'var bbcsCaptchaData = ' . BotBlockerSecurityPageAssets::json( $this->captcha_data ) . ';';
		$scripts[] = BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'captcha-js/captcha.js' );

		if ( null !== $addon_js ) {
			$scripts[] = $addon_js['js_content'];
			$external  = array_merge( $external, $addon_js['external'] );
		} else {
			$scripts[] = BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'captcha-js/mode' . $captcha_mode . '.js' );
		}

		$scripts[] = BotBlockerSecurityPageAssets::read( $this->dirs['public'], 'js/check-core.js' );

		return array(
			'styles'           => $styles,
			'scripts'          => $scripts,
			'external_scripts' => $external,
		);
	}

	private function fallback_check_captcha_data_to_mode0(): void {
		$renderer   = null;
		$saved      = isset( $this->settings->bbcs_captcha_mode ) ? (int) $this->settings->bbcs_captcha_mode : 0;
		$singleton  = class_exists( 'BotBlocker' ) ? BotBlocker::getInstance() : null;
		$saved_real = ( null !== $singleton && isset( $singleton->settings->bbcs_captcha_mode ) ) ? (int) $singleton->settings->bbcs_captcha_mode : null;

		try {
			require_once $this->dirs['public'] . 'class-botblocker-captcha-renderer.php';
			$this->settings->bbcs_captcha_mode          = 0;
			if ( null !== $saved_real ) {
				$singleton->settings->bbcs_captcha_mode = 0;
			}
			$renderer = new BotBlockerCaptchaRenderer( (string) $this->js_data['checkFunctionName'] );
			$fresh    = $renderer->getCaptchaData();
		} catch ( \Throwable $e ) {
			$fresh = array(
				'mode'   => 0,
				'params' => array(),
			);
		} finally {
			$this->settings->bbcs_captcha_mode = $saved;
			if ( null !== $saved_real && null !== $singleton ) {
				$singleton->settings->bbcs_captcha_mode = $saved_real;
			}
		}

		if ( ! is_array( $fresh ) || ! isset( $fresh['mode'], $fresh['params'] ) || ! is_array( $fresh['params'] ) ) {
			return;
		}

		$this->captcha_data = array_merge(
			$this->captcha_data,
			array(
				'mode'           => (int) $fresh['mode'],
				'params'         => $fresh['params'],
				'challengeToken' => ( null !== $renderer ) ? $renderer->getChallengeToken() : '',
			)
		);
	}

	private function assemble_check_page(): void {
		$this->prepare_check_js_data();
		$this->prepare_captcha_js_data();

		$this->template_data_check = array(
			'plugin_name' => BotBlockerCaptchaRenderer::t( 'BotBlocker security plugin' ),
			'h1_title'    => BotBlockerCaptchaRenderer::t( 'Please enable JavaScript and reload the page' ),
			'h2_title'    => BotBlockerCaptchaRenderer::t( 'Checking your browser before accessing the site' ),
			'extra_data'  => $this->ip,
		);
	}

	private function load_check_template(): void {
		$template_path = BOTBLOCKER_DIR . 'public/check-page.php';
		if ( file_exists( $template_path ) ) {
			$data                  = $this->template_data_check;
			$data['inline_assets'] = $this->create_check_inline_assets();
			include $template_path;
		}
	}
}
