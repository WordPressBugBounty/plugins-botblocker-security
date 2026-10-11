<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_SettingsViewModel $data): void {
?>
	<div class="bbcs-pagehead">
		<div>
			<div class="bbcs-pagehead-title"><?php esc_html_e('Protection Settings', 'botblocker-security'); ?></div>
			<div class="bbcs-pagehead-sub"><?php esc_html_e('What and how BotBlocker blocks', 'botblocker-security'); ?></div>
			<a class="bbcs-link bbcs-pagehead-doc" href="<?php echo esc_url( BotBlockerDocs::page( 'protection' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read the documentation', 'botblocker-security' ); ?> &#8599;</a>
		</div>
		<div class="bbcs-pagehead-actions">
			<a class="bbcs-btn bbcs-btn--pri" href="#" id="bbcsOpenOneClickSetup">
				<svg class="bbcs-ico bbcs-ico--sm">
					<use href="#bbcs-i-bolt"></use>
				</svg>
				<?php esc_html_e( 'One-click setup', 'botblocker-security' ); ?>
			</a>
			<button class="bbcs-btn" type="button" onclick="window.location.href=window.location.href.split('#')[0]" data-bbcs-reset>
				<svg class="bbcs-ico bbcs-ico--sm">
					<use href="#bbcs-i-refresh"></use>
				</svg>
				<?php esc_html_e('Reset', 'botblocker-security'); ?>
			</button>
			<?php (require __DIR__ . '/save-settings.php')($data); ?>
		</div>
	</div>
<?php
};
