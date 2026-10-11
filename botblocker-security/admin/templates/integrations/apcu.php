<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\Base;

return static function ( Botblocker_IntegrationsViewModel $data, bool $isActive ): void {
	$apcu_available = $data->has_apcu_ext;
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="apcu"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/apcu.svg' ); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e( 'APCu provides in-process shared memory caching for security counters and visitor data, with no disk I/O and no network connection.', 'botblocker-security' ); ?></div>
				<div class="bbcs-infocol-desc"><?php esc_html_e( 'Configure the APCu key prefix for isolation from other applications sharing the same server memory.', 'botblocker-security' ); ?></div>
<?php if ( ! $apcu_available ) : ?>
				<div class="bbcs-infocol-note bbcs-infocol-note--warn">
					<strong><?php esc_html_e( 'PHP extension not installed', 'botblocker-security' ); ?>:</strong>
					<?php esc_html_e( 'The apcu PHP extension is required to use this cache. Install it via your hosting control panel or contact your host.', 'botblocker-security' ); ?>
				</div>
<?php endif; ?>
				<div class="bbcs-infocol-note bbcs-infocol-note--warn">
					<strong><?php esc_html_e( 'Single-pool scope', 'botblocker-security' ); ?>:</strong>
					<?php esc_html_e( 'APCu is shared memory of one PHP process pool. Cache invalidation and security counters are not shared across PHP-FPM pools or web servers — for load-balanced or multi-pool hosting enable Redis or Memcached instead.', 'botblocker-security' ); ?>
				</div>
				<div class="bbcs-infocol-doc"><a class="bbcs-link bbcs-fs-sm" href="<?php echo esc_url( BotBlockerDocs::page( 'cache-backends' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read the documentation', 'botblocker-security' ); ?> &#8599;</a></div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e( 'APCu Cache Integration', 'botblocker-security' ); ?></div>
				<div class="bbcs-option bbcs-hoverbg"><button class="bbcs-toggle<?php echo $data->is_checked( 'apcu_enable', '1' ) ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $data->is_checked( 'apcu_enable', '1' ) ? 'true' : 'false'; ?>" data-field="apcu_enable"<?php echo $apcu_available ? '' : ' disabled'; ?>><span class="bbcs-toggle-knob"></span></button><input type="hidden" name="apcu_enable" value="<?php echo $data->is_checked( 'apcu_enable', '1' ) ? '1' : '0'; ?>"><span class="bbcs-option-label"><?php esc_html_e( 'Enable APCu counters', 'botblocker-security' ); ?></span>
					<?php echo Base::help( __( 'Cache security counters and visitor data in APCu instead of the database.', 'botblocker-security' ), BotBlockerDocs::url( 'apcu_enable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				</div>
				<div class="bbcs-field">
					<div class="bbcs-field-label"><?php esc_html_e( 'Key Prefix:', 'botblocker-security' ); ?>
						
						<?php echo Base::help( __( 'Define a unique prefix for all APCu keys to avoid conflicts with other applications using the same server memory.', 'botblocker-security' ), BotBlockerDocs::url( 'apcu_prefix' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
					</div>
					<div class="bbcs-field-box"><input type="text" class="bbcs-input bbcs-input--mono" name="apcu_prefix" value="<?php echo esc_attr( $data->get( 'apcu_prefix', BOTBLOCKER_PREFIX ) ); ?>"></div>
				</div>
			</div>
		</div>
	</div>
<?php
};
