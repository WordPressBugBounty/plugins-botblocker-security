<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\Base;

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
	$memcached_available = $data->has_memcached_ext;
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="memcached"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/memcached.svg'); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('Memcached provides distributed memory caching for security data and visitor analytics, reducing database queries on busy sites.', 'botblocker-security'); ?></div>
				<div class="bbcs-infocol-desc"><?php esc_html_e('Configure Memcached server details and cache key prefixes for proper data organization.', 'botblocker-security'); ?></div>
<?php if ( ! $memcached_available ) : ?>
				<div class="bbcs-infocol-note bbcs-infocol-note--warn">
					<strong><?php esc_html_e( 'PHP extension not installed', 'botblocker-security' ); ?>:</strong>
					<?php esc_html_e( 'The memcached PHP extension is required to use this cache. Install it via your hosting control panel or contact your host.', 'botblocker-security' ); ?>
				</div>
<?php endif; ?>
				<div class="bbcs-infocol-doc"><a class="bbcs-link bbcs-fs-sm" href="<?php echo esc_url( BotBlockerDocs::page( 'cache-backends' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read the documentation', 'botblocker-security' ); ?> &#8599;</a></div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('Memcached Cache Integration', 'botblocker-security'); ?></div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('memcached_enable') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('memcached_enable') ? 'true' : 'false'; ?>" data-field="memcached_enable"<?php echo $memcached_available ? '' : ' disabled'; ?>><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="memcached_enable" value="<?php echo $data->is_checked('memcached_enable') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Enable Memcached counters', 'botblocker-security'); ?></span>
					<?php echo Base::help( __( 'Cache security counters and visitor data in Memcached instead of the database.', 'botblocker-security' ), BotBlockerDocs::url( 'memcached_enable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('Server Host:', 'botblocker-security'); ?>
						
						<?php echo Base::help( __( 'Specify the Memcached server hostname or IP address. Default is localhost (127.0.0.1) for local installations.', 'botblocker-security' ), BotBlockerDocs::url( 'memcached_host' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="memcached_host" value="<?php echo esc_attr($data->get('memcached_host', '127.0.0.1')); ?>"></div>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('Server Port:', 'botblocker-security'); ?>
						
						<?php echo Base::help( __( 'Enter the Memcached server port number. Standard port is 11211 for most Memcached installations.', 'botblocker-security' ), BotBlockerDocs::url( 'memcached_port' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
					</div>
					<div class="bbcs-field-box"><input type="number" class="bbcs-input bbcs-input--mono" name="memcached_port" value="<?php echo esc_attr($data->get('memcached_port', '11211')); ?>"></div>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('Key Prefix:', 'botblocker-security'); ?>
						
						<?php echo Base::help( __( 'Define a unique prefix for all Memcached keys to avoid conflicts with other applications using the same cache server.', 'botblocker-security' ), BotBlockerDocs::url( 'memcached_prefix' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="memcached_prefix" value="<?php echo esc_attr($data->get('memcached_prefix', BOTBLOCKER_PREFIX)); ?>"></div>
				</div>
			</div>
		</div>
	</div>
<?php
};
