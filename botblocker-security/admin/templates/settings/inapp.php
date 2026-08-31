<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ToggleOption;

return static function ( Botblocker_SettingsViewModel $data, bool $isActive ): void {
	$enabled      = BotBlockerInApp::enabled();
	$rescue_codes = BotBlockerInApp::getRescueCodes();
	$groups       = BotBlockerInApp::RESCUE_GROUPS;

	$group_defs = array(
		'fake_bot' => array(
			'label'   => __( 'Fake bot (7)', 'botblocker-security' ),
			'tooltip' => __( 'A human inside the app whose UA collides with a crawler and the mobile IP has no matching PTR (the Instagram case).', 'botblocker-security' ),
		),
		'hosting' => array(
			'label'   => __( 'Hosting / Bad IP (17)', 'botblocker-security' ),
			'tooltip' => __( 'Mobile ISP ranges wrongly flagged as hosting.', 'botblocker-security' ),
		),
		'language' => array(
			'label'   => __( 'Language-country (57)', 'botblocker-security' ),
			'tooltip' => __( 'In-app browsers send the device language.', 'botblocker-security' ),
		),
		'referer' => array(
			'label'   => __( 'Fake referer (58)', 'botblocker-security' ),
			'tooltip' => __( 'In-app browsers may carry an unusual referer.', 'botblocker-security' ),
		),
	);
	?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="in-app"<?php echo $isActive ? '' : ' hidden'; ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/chat.svg', __( 'In-App Browser Mode', 'botblocker-security' ) )
			->withTitle( __( 'In-App Browser Mode', 'botblocker-security' ) )
			->withDescription( __( 'Rescues legitimate humans opening the site from Instagram, LinkedIn, Twitter or WhatsApp in-app browsers when their request would otherwise be denied as a fake bot.', 'botblocker-security' ) )
			->withDescription( __( 'Disabled by default. When off, the plugin behaves exactly as before — no rescue, no extra checks.', 'botblocker-security' ) )
			->withNote( __( 'Country (13), RKN (62), black UA (54), IPv6 (51), Cloudflare (55), proxy (56), PTR==IP (60), manual bans and rate limit are NEVER rescued.', 'botblocker-security' ), InfoColumn::NOTE_WARN )
			->render();
		?>
		<div>
			<?php
			SettingsGroup::make()
				->withTitle( __( 'Master switch', 'botblocker-security' ) )
				->withItems(
					static function () use ( $enabled ): void {
						ToggleOption::make()
							->withName( 'bbcs_inapp_enabled' )
							->withChecked( $enabled )
							->withLabel( __( 'Enable In-App Browser Mode', 'botblocker-security' ) )
							->withTooltip( __( 'Rescues in-app browsers from the selected deny reasons. Disabled = current behavior unchanged.', 'botblocker-security' ) )
							->render();
					}
				)
				->render();

			SettingsGroup::make()
				->withTitle( __( 'Rescue groups', 'botblocker-security' ) )
				->withItems(
					static function () use ( $groups, $rescue_codes, $group_defs ): void {
						// Rescue codes are a multi-value list — a plain toggle button
						// syncing a checkbox (pattern: x_robots_directives[] in error.php),
						// NOT a ToggleOption (its hidden input hard-codes 1/0).
						foreach ( $group_defs as $slug => $def ) {
							$checked = in_array( $groups[ $slug ], $rescue_codes, true );
							?>
							<div class="bbcs-option bbcs-hoverbg">
								<button class="bbcs-toggle<?php echo $checked ? ' is-on' : ''; ?>" role="switch" aria-checked="<?php echo $checked ? 'true' : 'false'; ?>" type="button">
									<span class="bbcs-toggle-knob"></span>
								</button>
								<input type="checkbox" name="bbcs_inapp_rescue_codes[]" value="<?php echo esc_attr( (string) $groups[ $slug ] ); ?>" <?php checked( $checked ); ?> style="position:absolute;opacity:0;pointer-events:none">
								<span class="bbcs-option-label"><?php echo esc_html( $def['label'] ); ?></span>
								<span class="bbcs-help">
									<span class="bbcs-help-q">?</span>
									<span class="bbcs-help-tip"><?php echo esc_html( $def['tooltip'] ); ?></span>
								</span>
							</div>
							<?php
						}
					}
				)
				->render();
			?>
		</div>
	</div>
	<?php
};
