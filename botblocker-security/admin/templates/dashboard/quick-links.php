<?php

use BotBlocker\Component\QuickLink;

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_DashboardViewModel $data): void {
	$items = array(
		QuickLink::make()->withUrl($data->urls->settings)->withTitle(__('Settings', 'botblocker-security'))->withSub(__('Bot detection, Captcha, protection', 'botblocker-security'))->withIcon('shieldCheck')->withAcc('green'),
		QuickLink::make()->withUrl($data->urls->settings . '#advanced-protection')->withTitle(__('Advanced Protection', 'botblocker-security'))->withSub(__('Cloud validation, DDoS, forced verification', 'botblocker-security'))->withIcon('gear')->withAcc('blue'),
		QuickLink::make()->withUrl($data->urls->rules)->withTitle(__('Rules & IPs', 'botblocker-security'))->withSub(__('IP lists, ASN, GEO, white bots', 'botblocker-security'))->withIcon('list')->withAcc('blue'),
		QuickLink::make()->withUrl($data->urls->reports)->withTitle(__('Log', 'botblocker-security'))->withSub(__('Who visited and who was blocked', 'botblocker-security'))->withIcon('chart')->withAcc('violet'),
		QuickLink::make()->withUrl($data->urls->addons)->withTitle(__('Addons', 'botblocker-security'))->withSub(__('Extra modules', 'botblocker-security'))->withIcon('puzzle')->withAcc('amber'),
		QuickLink::make()->withUrl($data->urls->tools)->withTitle(__('Tools', 'botblocker-security'))->withSub(__('Database, cache, maintenance', 'botblocker-security'))->withIcon('sliders')->withAcc('green'),
		QuickLink::make()->withUrl($data->urls->integrations)->withTitle(__('Integrations', 'botblocker-security'))->withSub(__('reCaptcha, Redis', 'botblocker-security'))->withIcon('plug')->withAcc('blue'),
		QuickLink::make()->withUrl($data->urls->integrations . '#bbcs-2fa')->withTitle(__('2FA', 'botblocker-security'))->withSub(__('Two-factor login protection', 'botblocker-security'))->withIcon('lock')->withAcc('blue'),
		QuickLink::make()->withUrl($data->urls->setup)->withTitle(__('System Status', 'botblocker-security'))->withSub(__('Protection checklist and server', 'botblocker-security'))->withIcon('eye')->withAcc('violet'),
		QuickLink::make()->withUrl($data->urls->cloud_api)->withTitle(__('BotBlocker PRO', 'botblocker-security'))->withSub(__('License, cloud API, premium features', 'botblocker-security'))->withIcon('crown')->withAcc('amber'),
		QuickLink::make()->withUrl($data->urls->about)->withTitle(__('Support', 'botblocker-security'))->withSub(__('Tickets, Telegram, documentation', 'botblocker-security'))->withIcon('headset')->withAcc('green'),
		QuickLink::make()->withUrl($data->urls->settings . '#cron')->withTitle(__('Cron Jobs', 'botblocker-security'))->withSub(__('Background tasks and scheduling', 'botblocker-security'))->withIcon('clock')->withAcc('green'),
	);
?>
	<div class="bbcs-section">
		<div class="bbcs-section-head">
			<div class="bbcs-section-title"><?php esc_html_e('Quick access', 'botblocker-security'); ?></div>
			<span class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('All plugin sections in one click', 'botblocker-security'); ?></span>
		</div>
		<div class="bbcs-grid bbcs-grid--3">
			<?php foreach ($items as $item) : ?>
				<?php $item->render(); ?>
			<?php endforeach; ?>
		</div>
	</div>
<?php
};
