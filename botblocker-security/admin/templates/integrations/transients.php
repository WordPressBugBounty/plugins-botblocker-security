<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="transients"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/transient.svg'); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('Caching via WordPress Transients API.', 'botblocker-security'); ?></div>
				<div class="bbcs-doclist">
					<div class="bbcs-doclist-head"><span class="bbcs-help-q">?</span><?php esc_html_e('Documentation', 'botblocker-security'); ?></div><a href="<?php echo esc_url($data->docs_url); ?>/transients-api/" target="_blank" class="bbcs-link bbcs-fs-xs"><?php esc_html_e('Transients API', 'botblocker-security'); ?></a><a href="<?php echo esc_url($data->docs_url); ?>/caching/" target="_blank" class="bbcs-link bbcs-fs-xs"><?php esc_html_e('Caching', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('Transients', 'botblocker-security'); ?></div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('transients_enable', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('transients_enable', '1') ? 'true' : 'false'; ?>" data-field="transients_enable"><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="transients_enable" value="<?php echo $data->is_checked('transients_enable', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Store Cloud API responses in transients', 'botblocker-security'); ?></span><span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Caches cloud API responses in WordPress Transients to speed up repeated requests.', 'botblocker-security'); ?></span></span></div>
			</div>
		</div>
	</div>
<?php
};
