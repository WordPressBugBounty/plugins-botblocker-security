<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="wp-connectors"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/wordpress.svg'); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('WordPress Connectors integrates your site with the WordPress ecosystem. Disable to save ~180ms per page load when not needed.', 'botblocker-security'); ?></div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('WordPress Connectors', 'botblocker-security'); ?></div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('bbcs_wp_connectors_enabled', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('bbcs_wp_connectors_enabled', '1') ? 'true' : 'false'; ?>" data-field="bbcs_wp_connectors_enabled"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="bbcs_wp_connectors_enabled" value="<?php echo $data->is_checked('bbcs_wp_connectors_enabled', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Enable BotBlocker in WordPress Connectors', 'botblocker-security'); ?></span><span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Registers BotBlocker in the WordPress Connectors system. Disable if you do not use WordPress Connectors to save ~180ms per admin page load.', 'botblocker-security'); ?></span></span></div>
			</div>
		</div>
	</div>
<?php
};
