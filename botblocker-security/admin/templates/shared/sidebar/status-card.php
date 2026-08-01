<?php
use BotBlocker\Component\Toggle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_SidebarViewModel $sidebar ): void {
	$s = $sidebar;
	?>
	<section class="card bbcs-card-border-left ">
		<header class="card-header bbcs_small_header">
			<div class="card-actions bbcs_header_controls">
				<span class="bbcs-help" style="display:inline-flex">
					<a href="<?php echo esc_url( $s->settings_url ); ?>"><i class="fa-solid fa-gear bbcs-h-btn-gray"></i></a>
					<span class="bbcs-help-tip"><?php esc_html_e( 'BotBlocker Settings', 'botblocker-security' ); ?></span>
				</span>
			</div>
			<h2 class="card-title"><?php esc_html_e( 'Status', 'botblocker-security' ); ?></h2>
			<!--<p class="card-subtitle"></p>-->
		</header>
		<div class="card-body">
			<?php
			$render_chain = require BOTBLOCKER_DIR . 'admin/templates/shared/request-chain.php';
			$render_chain( new Botblocker_ChainContextData(
				(bool) $s->toggles->early_init_checked,
				(bool) $s->toggles->mu_checked,
				(bool) $s->is_active,
				true,
				(bool) $s->early_available,
				$s->cloud_api_url,
				$s->addons_url
			) );
			?>

			<?php
			Toggle::make()
				->withId( 'bbcs_switch_early_init' )
				->withChecked( (bool) $s->toggles->early_init_checked )
				->withLabel( __( 'Early initialization', 'botblocker-security' ) )
				->withTooltip( __( 'Loads black/white IP lists via wp-config before WordPress core starts', 'botblocker-security' ) )
				->withSetupUrl( $s->setup_url )
				->withAttrs(
					array(
						'disabled'             => ! $s->early_available,
						'data-early-available' => (int) $s->early_available,
						'data-addons-url'      => esc_url( $s->addons_url ),
						'data-pro-url'         => esc_url( $s->cloud_api_url ),
					)
				)
				->render();
			?>

			<?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
			<div class="text-muted bbcs-sidebar-pro-text " <?php echo $s->early_available ? 'hidden' : ''; ?>>
				<?php echo $s->early_init_disabled_message; ?>
			<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				(<a href="<?php echo esc_url( $s->cloud_api_url ); ?>"><?php esc_html_e( 'Get PRO', 'botblocker-security' ); ?></a>
				<?php esc_html_e( 'or', 'botblocker-security' ); ?>
				<a href="<?php echo esc_url( $s->addons_url ); ?>"><?php esc_html_e( 'manage add-ons', 'botblocker-security' ); ?></a>)
			</div>

			<?php
			Toggle::make()
				->withId( 'bbcs_switch_mu_plugin' )
				->withChecked( (bool) $s->toggles->mu_checked )
				->withLabel( __( 'MU plugin', 'botblocker-security' ) )
				->withTooltip( __( 'MU mode loads black/white IP lists before regular plugins and WordPress core', 'botblocker-security' ) )
				->withSetupUrl( $s->setup_url )
				->render();

			Toggle::make()
				->withId( 'bbcs_switch_redis' )
				->withChecked( (bool) $s->toggles->redis_checked )
				->withLabel( __( 'Redis', 'botblocker-security' ) )
				->withTooltip( $s->toggles->redis_disabled ? __( 'Redis PHP extension not installed on this server', 'botblocker-security' ) : __( 'Speeds up visitor processing via Redis', 'botblocker-security' ) )
				->withGearUrl( $s->integrations_url . '#bbcs_redis' )
				->withAttrs( array( 'disabled' => $s->toggles->redis_disabled ) )
				->render();

			Toggle::make()
				->withId( 'bbcs_switch_memcached' )
				->withChecked( (bool) $s->toggles->memcached_checked )
				->withLabel( __( 'Memcached', 'botblocker-security' ) )
				->withTooltip( $s->toggles->memcached_disabled ? __( 'Memcached PHP extension not installed on this server', 'botblocker-security' ) : __( 'Speeds up visitor processing via Memcached', 'botblocker-security' ) )
				->withGearUrl( $s->integrations_url . '#bbcs_memcached' )
				->withAttrs( array( 'disabled' => $s->toggles->memcached_disabled ) )
				->render();

			Toggle::make()
				->withId( 'bbcs_switch_apcu' )
				->withChecked( (bool) $s->toggles->ptr_cache_checked )
				->withLabel( __( 'PTR Cache', 'botblocker-security' ) )
				->withTooltip( __( 'Caches PTR lookups to speed up repeat visitor checks (24h TTL)', 'botblocker-security' ) )
				->render();
			?>

		</div>
		<div class="card-footer">
			<small>
				<?php esc_html_e( 'Today blocked:', 'botblocker-security' ); ?> <b><?php echo esc_html( $s->today_blocked ); ?></b>
				<br>
				<?php esc_html_e( 'Total blocked:', 'botblocker-security' ); ?> <b><?php echo esc_html( $s->total_blocked ); ?></b>
			</small>
		</div>

	</section>
	<?php
};
