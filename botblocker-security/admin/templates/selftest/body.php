<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SelfTestViewModel $data ): void {
?>
	<div class="bbcs-st" id="bbcs-selftest">
		<details class="bbcs-st-card bbcs-st-about" id="bbcs-st-about" open>
			<summary><?php esc_html_e( 'How this test works', 'botblocker-security' ); ?></summary>
			<div class="bbcs-st-about-body">
				<p class="bbcs-st-about-intro"><?php esc_html_e( 'You never see the captcha on your own site because BotBlocker recognizes you. This test removes that recognition for one visit, so you see what a first-time visitor sees.', 'botblocker-security' ); ?></p>
				<div class="bbcs-st-cols">
					<div class="bbcs-st-col">
						<h3><span class="dashicons dashicons-visibility"></span><?php esc_html_e( 'What the test does', 'botblocker-security' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Opens your real home page in the window below. Nothing is simulated.', 'botblocker-security' ); ?></li>
							<li><?php esc_html_e( 'Runs the same checks a new visitor gets: the check page, your captcha, and the redirect to the site.', 'botblocker-security' ); ?></li>
							<li><?php esc_html_e( 'Records each step and shows the result on the left.', 'botblocker-security' ); ?></li>
						</ul>
					</div>
					<div class="bbcs-st-col skip">
						<h3><span class="dashicons dashicons-controls-skipforward"></span><?php esc_html_e( 'What is skipped, for this visit only', 'botblocker-security' ); ?></h3>
						<p><?php esc_html_e( 'These normally let you in without any check:', 'botblocker-security' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Your administrator login. Administrators and editors are never checked.', 'botblocker-security' ); ?></li>
							<li><?php esc_html_e( 'Allow rules that match your IP address.', 'botblocker-security' ); ?></li>
							<li><?php esc_html_e( 'The pass cookie your browser got from an earlier check.', 'botblocker-security' ); ?></li>
						</ul>
					</div>
					<div class="bbcs-st-col safe">
						<h3><span class="dashicons dashicons-lock"></span><?php esc_html_e( 'What stays safe', 'botblocker-security' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'You cannot get banned here. If you fail the captcha, nothing is saved against you, so you will not lock yourself out of the admin panel.', 'botblocker-security' ); ?></li>
							<li><?php esc_html_e( 'Your login and your pass cookie are not changed.', 'botblocker-security' ); ?></li>
							<li><?php esc_html_e( 'The test link works only for you, only while you are logged in, and expires after 5 minutes.', 'botblocker-security' ); ?></li>
						</ul>
					</div>
				</div>
				<div class="bbcs-st-warn">
					<span class="dashicons dashicons-warning"></span>
					<div>
						<strong><?php esc_html_e( 'Important: the test uses your own connection', 'botblocker-security' ); ?></strong>
						<p><?php esc_html_e( 'The test runs from your own IP address. That is why a failed attempt is never counted as a ban: if it were, your own IP would be blocked and you could not open this admin panel anymore.', 'botblocker-security' ); ?></p>
					</div>
				</div>
				<div class="bbcs-st-warn bbcs-st-warn-info">
					<span class="dashicons dashicons-info"></span>
					<div>
						<strong><?php esc_html_e( 'Real visitors may see something different', 'botblocker-security' ); ?></strong>
						<p><?php esc_html_e( 'Real visitors use their own connections. Those from other countries, networks or devices can match other rules, such as country blocks, VPN detection or bot signatures, and may get a different result.', 'botblocker-security' ); ?></p>
					</div>
				</div>
			</div>
		</details>

		<div class="bbcs-st-grid">
			<div>
				<div class="bbcs-st-card bbcs-st-result" id="bbcs-st-result" data-tone="">
					<div class="bbcs-st-icon">?</div>
					<div>
						<strong><?php esc_html_e( 'Not tested yet', 'botblocker-security' ); ?></strong>
						<span class="bbcs-st-sub"><?php esc_html_e( 'Click "Run test" to open your site as a new visitor.', 'botblocker-security' ); ?></span>
					</div>
				</div>
				<div class="bbcs-st-card" id="bbcs-st-settings" hidden></div>
				<div class="bbcs-st-card" id="bbcs-st-why" hidden></div>
				<div class="bbcs-st-card">
					<h2><?php esc_html_e( 'What happened', 'botblocker-security' ); ?></h2>
					<ul class="bbcs-st-steps" id="bbcs-st-steps"><li class="bbcs-st-empty"><?php esc_html_e( 'Steps appear here while the test runs.', 'botblocker-security' ); ?></li></ul>
				</div>
			</div>
			<div class="bbcs-st-browser">
				<div class="bbcs-st-bar">
					<div class="bbcs-st-dots"><i></i><i></i><i></i></div>
					<div class="bbcs-st-url" id="bbcs-st-url"><?php echo esc_html( $data->host ); ?></div>
					<div class="bbcs-st-badge"><?php esc_html_e( 'New visitor view', 'botblocker-security' ); ?></div>
				</div>
				<div class="bbcs-st-placeholder" id="bbcs-st-placeholder"><span class="dashicons dashicons-shield"></span><?php esc_html_e( 'The visitor view opens here.', 'botblocker-security' ); ?></div>
				<iframe id="bbcs-selftest-frame" class="bbcs-st-frame" title="Protection test" hidden></iframe>
			</div>
		</div>
	</div>
<?php
};
