<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerDashboardWidgets {

	public static function register(): void {
		add_action( 'admin_enqueue_scripts', array( self::class, 'dashboardAssets' ) );
		add_action( 'wp_dashboard_setup', array( self::class, 'registerCustomDashboardWidgets' ) );
	}

	public static function dashboardAssets(): void {
		$screen = get_current_screen();

		if ( $screen && $screen->id === 'dashboard' ) {
			if ( class_exists( 'BBCS_Toastify' ) ) {
				BBCS_Toastify::enqueue_assets();
			}
			wp_enqueue_style(
				BOTBLOCKER_SHORT_NAME . '-fa',
				BOTBLOCKER_URL . 'admin/css/all.min.css',
				array(),
				BOTBLOCKER_VERSION,
				'all'
			);

			wp_enqueue_style(
				BOTBLOCKER_SHORT_NAME . '-dashboard',
				BOTBLOCKER_URL . 'admin/dashboard/css/dashboard.css',
				array(),
				BOTBLOCKER_VERSION,
				'all'
			);

			wp_enqueue_script(
				BOTBLOCKER_SHORT_NAME . '-dashboard',
				BOTBLOCKER_URL . 'admin/dashboard/js/dashboard.js',
				array( 'jquery', BOTBLOCKER_SHORT_NAME . '-toast-js' ),
				BOTBLOCKER_VERSION,
				true
			);

			wp_enqueue_script(
				BOTBLOCKER_SHORT_NAME . '-chartjs',
				BOTBLOCKER_URL . 'admin/js/chartjs/chart.umd.js',
				array(),
				BOTBLOCKER_VERSION,
				false
			);

			wp_enqueue_script(
				BOTBLOCKER_SHORT_NAME . '-chart-js',
				BOTBLOCKER_URL . 'admin/js/bbcs-js/bbcs-charts.js',
				array(),
				BOTBLOCKER_VERSION,
				false
			);

			wp_enqueue_script(
				BOTBLOCKER_SHORT_NAME . '-common',
				BOTBLOCKER_URL . 'admin/js/bbcs-js/bbcs-health-gauge.js',
				array(),
				BOTBLOCKER_VERSION,
				false
			);

			wp_localize_script(
				BOTBLOCKER_SHORT_NAME . '-dashboard',
				'botblockerData',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
				)
			);
			wp_localize_script(
				BOTBLOCKER_SHORT_NAME . '-dashboard',
				'bbcsDashboardWidgetL10n',
				array(
					'rule_added'            => __( 'Success: IP rule added', 'botblocker-security' ),
					'failed_create_rule'    => __( 'Failed to create rule: ', 'botblocker-security' ),
					'import_success_prefix' => __( 'Successfully imported ', 'botblocker-security' ),
					'import_imported'       => __( 'Imported: ', 'botblocker-security' ),
					'import_skipped'        => __( 'Skipped: ', 'botblocker-security' ),
					'failed_import_prefix'  => __( 'Failed to import ', 'botblocker-security' ),
					'invalid_json'          => __( 'Invalid JSON file: ', 'botblocker-security' ),
				)
			);
		}
	}

	public static function registerCustomDashboardWidgets(): void {
		if ( defined( 'BOTBLOCKER_DISPLAY_NEWS' ) && BOTBLOCKER_DISPLAY_NEWS ) {
			wp_add_dashboard_widget(
				'bbcs_news_widget',
				__( 'BotBlocker News', 'botblocker-security' ),
				array( self::class, 'displayNewsWidget' )
			);
		}
		wp_add_dashboard_widget(
			'custom_stats_widget',
			__( 'BotBlocker Stats', 'botblocker-security' ),
			array( self::class, 'displayStatsWidget' )
		);
		wp_add_dashboard_widget(
			'custom_form_widget',
			__( 'BotBlocker Quick Rule', 'botblocker-security' ),
			array( self::class, 'displayFormWidget' )
		);
	}

	public static function displayNewsWidget(): void {
		echo do_shortcode( '[bbcs_botblocker_news count="5"]' );
		echo '<hr>';
		echo '<small>';
			echo do_shortcode( '[bbcs_database_update]' );
			echo '<br>';
			echo do_shortcode( '[bbcs_database_total]' );
		echo '</small>';
	}

	// TODO ACTIVE PRO STATUS INDICATOR
	public static function displayStatsWidget(): void {
		$BBCSA = Botblocker_Admin::getInstance();
		BotBlockerHealthShortcodes::collectStatisticData();
		$has_pro = BotBlockerPro::isActive();
		?>
			<div style="position: absolute; z-index: 100;">
				<div class="bbcs-card-actions">
					<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>" class="bbcs-icon-button" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php echo $has_pro ? esc_attr__( 'You have PRO activated. Check your plan.', 'botblocker-security' ) : esc_attr__( 'Upgrade your plan for better protection.', 'botblocker-security' ); ?>">
						<i class="bbcs-card-action fa-solid fa-crown <?php echo $has_pro ? 'bbcs-cloud-api-color' : ''; ?>"></i>
					</a>
					<a href="<?php echo esc_url( $BBCSA->pages_settings ); ?>" class="bbcs-icon-button" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
						data-bs-original-title="<?php esc_attr_e( 'Settings', 'botblocker-security' ); ?>">
						<i class="bbcs-card-action fa-solid fa-gear"></i>
					</a>
				</div>
			</div>
			<div class="bbcs-health-wrapper"> 
				<div class="col-lg-6">
					<?php
					echo do_shortcode(
						'[bbcs_health_gauge id="health_gauge" value="' . BotBlockerHealthShortcodes::calculateSiteHealth() .
						'" max="100"]'
					);
					?>
					<?php echo do_shortcode( '[bbcs_counters_grid]' ); ?>
				</div>
				<div class="col-lg-6">
					<?php echo do_shortcode( '[botblocker_generateSiteHealthList]' ); ?>
				</div>
			</div>
		<?php
	}

	public static function displayFormWidget(): void {
		?>
		<div class="bbcs-quick-rule-widget">
			<h4 class="bbcs-qr-title"><i class="fa-solid fa-list" aria-hidden="true"></i> <?php esc_html_e( 'IP List Import', 'botblocker-security' ); ?></h4>
			<div class="bbcs-import-groups">
				<div class="bbcs-import-group">
					<label class="bbcs-import-label"><?php esc_html_e( 'IPv4 Lists:', 'botblocker-security' ); ?></label>
					<div class="bbcs-import-group-btns">
						<a href="#" id="bbcs_ipv4_import_white" class="bbcs-btn bbcs-btn-sm bbcs-btn-default" aria-label="<?php esc_attr_e( 'Import IPv4 whitelist TXT', 'botblocker-security' ); ?>"><i class="fa-regular fa-flag" aria-hidden="true"></i> <?php esc_html_e( 'White', 'botblocker-security' ); ?></a>
						<a href="#" id="bbcs_ipv4_import_black" class="bbcs-btn bbcs-btn-sm bbcs-btn-default" aria-label="<?php esc_attr_e( 'Import IPv4 blacklist TXT', 'botblocker-security' ); ?>"><i class="fa-solid fa-flag" aria-hidden="true"></i> <?php esc_html_e( 'Black', 'botblocker-security' ); ?></a>
					</div>
				</div>
				<div class="bbcs-import-group">
					<label class="bbcs-import-label"><?php esc_html_e( 'IPv6 Lists:', 'botblocker-security' ); ?></label>
					<div class="bbcs-import-group-btns">
						<a href="#" id="bbcs_ipv6_import_white" class="bbcs-btn bbcs-btn-sm bbcs-btn-default" aria-label="<?php esc_attr_e( 'Import IPv6 whitelist TXT', 'botblocker-security' ); ?>"><i class="fa-regular fa-flag" aria-hidden="true"></i> <?php esc_html_e( 'White', 'botblocker-security' ); ?></a>
						<a href="#" id="bbcs_ipv6_import_black" class="bbcs-btn bbcs-btn-sm bbcs-btn-default" aria-label="<?php esc_attr_e( 'Import IPv6 blacklist TXT', 'botblocker-security' ); ?>"><i class="fa-solid fa-flag" aria-hidden="true"></i> <?php esc_html_e( 'Black', 'botblocker-security' ); ?></a>
					</div>
				</div>
			</div>
			<p class="bbcs-qr-desc">
				<?php esc_html_e( 'Import IP lists from TXT files. White lists allow IPs, black lists block them. One IP per line.', 'botblocker-security' ); ?>
				<br>
				<?php esc_html_e( 'You can prepare your list in any text editor. Each line should contain a single IP address or subnet in CIDR format (e.g. 192.168.1.0/24).', 'botblocker-security' ); ?>
				<br>
				<?php esc_html_e( 'Example:', 'botblocker-security' ); ?> <code>203.0.113.45</code>, <code>2001:db8::/32</code>
				<br>
				<?php esc_html_e( 'Uploading a new list will add new IPs and skip existing ones. Invalid lines will be skipped automatically.', 'botblocker-security' ); ?>
			</p>
		</div>
		
		<div class="bbcs-quick-rule-widget" style="margin-top: 20px;">
			<h4 class="bbcs-qr-title"><i class="fa-solid fa-bolt" aria-hidden="true"></i> <?php esc_html_e( 'Quick IP Rule', 'botblocker-security' ); ?></h4>
			<p class="bbcs-qr-desc" style="margin-top:4px;">
				<?php esc_html_e( 'Create allow/block rules for IPs or subnets. Use import buttons to add search engines and white bots.', 'botblocker-security' ); ?>
			</p>
			<form id="addIpRuleForm" class="bbcs-qr-form" novalidate>
				<div class="bbcs-qr-grid">
					<div class="bbcs-qr-field">
						<label for="bbcs-add-priority" class="bbcs-qr-label"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> <?php esc_html_e( 'Priority', 'botblocker-security' ); ?></label>
						<input type="number" class="bbcs-qr-input" id="bbcs-add-priority" name="priority" min="1" max="100" value="50" required>
						<small class="bbcs-qr-help"><?php esc_html_e( '1 = highest. Higher priority rules processed first.', 'botblocker-security' ); ?></small>
					</div>
					<div class="bbcs-qr-field">
						<label for="addIp" class="bbcs-qr-label"><i class="fa-solid fa-globe" aria-hidden="true"></i> <?php esc_html_e( 'IP / Subnet', 'botblocker-security' ); ?></label>
						<input type="text" class="bbcs-qr-input" id="addIp" name="ip" placeholder="203.0.113.45, 2001:db8::/32, or CIDR" required>
						<small class="bbcs-qr-help"><?php esc_html_e( 'Enter single IP or CIDR (e.g. 198.51.100.0/24 or 2001:db8::/32).', 'botblocker-security' ); ?></small>
					</div>
					<div class="bbcs-qr-field">
						<label for="addRule" class="bbcs-qr-label"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> <?php esc_html_e( 'Action', 'botblocker-security' ); ?></label>
						<select class="bbcs-qr-input" id="addRule" name="rule" required>
							<option value="allow"><?php esc_html_e( 'Allow', 'botblocker-security' ); ?></option>
							<option value="block"><?php esc_html_e( 'Block', 'botblocker-security' ); ?></option>
						</select>
						<small class="bbcs-qr-help"><?php esc_html_e( 'Choose how to treat this IP / subnet.', 'botblocker-security' ); ?></small>
					</div>
					<div class="bbcs-qr-field">
						<label for="addExpires" class="bbcs-qr-label"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> <?php esc_html_e( 'Expires', 'botblocker-security' ); ?></label>
						<input type="datetime-local" class="bbcs-qr-input" id="addExpires" name="expires" value="<?php echo esc_attr( BotBlockerCompatibility::wpDate( 'Y-m-d\TH:i', strtotime( '+1 day' ) ) ); ?>" required>
						<small class="bbcs-qr-help"><?php esc_html_e( 'When the rule should stop applying.', 'botblocker-security' ); ?></small>
					</div>
					<div class="bbcs-qr-field bbcs-qr-field-full">
						<label for="addComment" class="bbcs-qr-label"><i class="fa-solid fa-note-sticky" aria-hidden="true"></i> <?php esc_html_e( 'Comment (optional)', 'botblocker-security' ); ?></label>
						<textarea class="bbcs-qr-input" id="addComment" name="comment" rows="2" placeholder="<?php echo esc_attr__( 'Reason or context (e.g. temporary block)', 'botblocker-security' ); ?>"></textarea>
						<small class="bbcs-qr-help"><?php esc_html_e( 'Add a short note to remember why you created this rule.', 'botblocker-security' ); ?></small>
					</div>
				</div>
				<button type="submit" class="bbcs-qr-submit"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Add Rule', 'botblocker-security' ); ?></button>
			</form>
			<div class="bbcs-qr-legend">
				<strong><?php esc_html_e( 'Legend:', 'botblocker-security' ); ?></strong>
				<span><i class="fa-solid fa-layer-group" aria-hidden="true"></i> <?php esc_html_e( 'Order of execution', 'botblocker-security' ); ?></span>
				<span><i class="fa-solid fa-globe" aria-hidden="true"></i> <?php esc_html_e( 'Target IP/Subnet', 'botblocker-security' ); ?></span>
				<span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> <?php esc_html_e( 'Allow or Block', 'botblocker-security' ); ?></span>
				<span><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> <?php esc_html_e( 'Rule lifetime', 'botblocker-security' ); ?></span>
			</div>
		</div>
		<?php
	}
}
