<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_IntegrationsViewModel $data, bool $isActive): void {
	$redis_available = $data->has_redis_ext;
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="redis"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/redis.svg'); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e('Redis provides in-memory caching for security counters, visitor statistics, and threat detection data.', 'botblocker-security'); ?></div>
				<div class="bbcs-infocol-desc"><?php esc_html_e('Configure Redis host, port, authentication, and key prefix isolation.', 'botblocker-security'); ?></div>
<?php if ( ! $redis_available ) : ?>
				<div class="bbcs-infocol-note bbcs-infocol-note--warn">
					<strong><?php esc_html_e( 'PHP extension not installed', 'botblocker-security' ); ?>:</strong>
					<?php esc_html_e( 'The redis PHP extension is required to use this cache. Install it via your hosting control panel or contact your host.', 'botblocker-security' ); ?>
				</div>
<?php endif; ?>
				<div class="bbcs-doclist">
					<div class="bbcs-doclist-head"><span class="bbcs-help-q">?</span><?php esc_html_e('Documentation', 'botblocker-security'); ?></div><a href="<?php echo esc_url($data->docs_url); ?>/what-is-redis-and-how-does-it-power-botblockers-fast-checks-in-wordpress/" target="_blank" class="bbcs-link bbcs-fs-xs"><?php esc_html_e('About Redis', 'botblocker-security'); ?></a><a href="<?php echo esc_url($data->docs_url); ?>/redis-vs-memcached-for-botblocker-which-cache-is-better-for-wordpress-security/" target="_blank" class="bbcs-link bbcs-fs-xs"><?php esc_html_e('Redis vs Memcached', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e('Redis Cache Integration', 'botblocker-security'); ?></div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked('redis_enable', '1') ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked('redis_enable', '1') ? 'true' : 'false'; ?>" data-field="redis_enable"<?php echo $redis_available ? '' : ' disabled'; ?>><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="redis_enable" value="<?php echo $data->is_checked('redis_enable', '1') ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e('Enable Redis counters', 'botblocker-security'); ?></span><span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Cache security counters and visitor data in Redis instead of the database.', 'botblocker-security'); ?></span></span></div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('Server Host:', 'botblocker-security'); ?>
						<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Specify the Redis server hostname or IP address. Default is localhost (127.0.0.1) for local Redis installations.', 'botblocker-security'); ?></span></span>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="redis_host" value="<?php echo esc_attr($data->get('redis_host', '')); ?>"></div>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('Key Prefix:', 'botblocker-security'); ?>
						<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Define a unique prefix for all Redis keys to organize data and prevent conflicts with other applications using the same Redis instance.', 'botblocker-security'); ?></span></span>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="redis_prefix" value="<?php echo esc_attr($data->get('redis_prefix', BOTBLOCKER_PREFIX)); ?>"></div>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e('Password:', 'botblocker-security'); ?>
						<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Enter Redis server authentication password if required. Leave empty if Redis server does not require authentication.', 'botblocker-security'); ?></span></span>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="redis_password" value="<?php echo esc_attr($data->get('redis_password', '')); ?>"></div>
				</div>
				<div class="bbcs-field-pair">
					<div class="bbcs-field">
						<div class="bbcs-field-label"><?php esc_html_e('Server Port:', 'botblocker-security'); ?>
							<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Enter the Redis server port number. Standard port is 6379 for most Redis installations.', 'botblocker-security'); ?></span></span>
						</div>
						<div class="bbcs-field-box"><input type="number" class="bbcs-input bbcs-input--mono" name="redis_port" value="<?php echo esc_attr($data->get('redis_port', '0')); ?>"></div>
					</div>
					<div class="bbcs-field">
						<div class="bbcs-field-label"><?php esc_html_e('Database Index:', 'botblocker-security'); ?>
							<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e('Select the Redis database index (0-15) to use. Default is 0. Different database indexes provide logical isolation between applications sharing the same Redis server.', 'botblocker-security'); ?></span></span>
						</div>
						<div class="bbcs-field-box"><input type="number" min="0" max="15" class="bbcs-input bbcs-input--mono" name="redis_database" value="<?php echo esc_attr($data->get('redis_database', '0')); ?>"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
};
