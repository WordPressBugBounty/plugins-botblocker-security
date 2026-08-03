<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ActionButton;

return static function ( Botblocker_ToolsViewModel $data, bool $isActive ): void {
	$asn_dl    = isset( $data->asn_status['downloaded_at'] ) ? (int) $data->asn_status['downloaded_at'] : 0;
	$asn_size  = isset( $data->asn_status['size'] ) ? (int) $data->asn_status['size'] : 0;
	$asn_type  = isset( $data->asn_status['database_type'] ) ? (string) $data->asn_status['database_type'] : '';
	$asn_error = isset( $data->asn_status['last_error'] ) ? (string) $data->asn_status['last_error'] : '';

	$rugov_last_sync   = isset( $data->rugov_status['last_sync'] ) ? (int) $data->rugov_status['last_sync'] : 0;
	$rugov_range_count = isset( $data->rugov_status['range_count'] ) ? (int) $data->rugov_status['range_count'] : 0;
	$rugov_error       = isset( $data->rugov_status['last_error'] ) ? (string) $data->rugov_status['last_error'] : '';

	$block_rkn_enabled = false;
	$bbcs_instance     = BotBlocker::getInstance();
	if ( isset( $bbcs_instance->settings ) ) {
		$block_rkn_enabled = ! empty( $bbcs_instance->settings->block_rkn );
	}

	$llm_last_sync      = isset( $data->llm_status['last_sync'] ) ? (int) $data->llm_status['last_sync'] : 0;
	$llm_provider_count = isset( $data->llm_status['provider_count'] ) ? (int) $data->llm_status['provider_count'] : 0;
	$llm_error          = isset( $data->llm_status['last_error'] ) ? (string) $data->llm_status['last_error'] : '';

	if ( $data->asn_present && $asn_dl > 0 ) {
		$asn_age = human_time_diff( $asn_dl, time() );
		$asn_info = sprintf(
				/* translators: %1$s: ASN database type, %2$s: database size, %3$s: time since last update */
			__( 'ASN DB: %1$s | %2$s | updated %3$s ago', 'botblocker-security' ),
			$asn_type !== '' ? $asn_type : __( 'ASN database', 'botblocker-security' ),
			size_format( $asn_size ),
			$asn_age
		);
	} else {
		$asn_info = __( 'ASN DB: not yet downloaded.', 'botblocker-security' );
	}
	if ( ! empty( $asn_error ) ) {
		$asn_info .= '<br><span class="bbcs-tx-danger">' . esc_html( $asn_error ) . '</span>';
	}

	if ( $data->rugov_present && $rugov_last_sync > 0 ) {
		$rugov_age = human_time_diff( $rugov_last_sync, time() );
		$rugov_info = sprintf(
				/* translators: %1$s: number of IP ranges, %2$s: time since last update */
			__( 'RU-Gov: %1$s ranges | updated %2$s ago', 'botblocker-security' ),
			number_format_i18n( $rugov_range_count ),
			$rugov_age
		);
	} else {
		$rugov_info = sprintf(
			/* translators: %s: linked RU-Gov label */
			__( '%s list: not yet downloaded.', 'botblocker-security' ),
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( bbcs_get_setting_link( 'block_rkn', true ) ),
				esc_html( __( 'RU-Gov', 'botblocker-security' ) )
			)
		);
	}
	if ( ! empty( $rugov_error ) ) {
		$rugov_info .= '<br><span class="bbcs-tx-danger">' . esc_html( $rugov_error ) . '</span>';
	}

	if ( $llm_last_sync > 0 ) {
		$llm_age = human_time_diff( $llm_last_sync, time() );
		$llm_info = sprintf(
				/* translators: %1$s: number of LLM providers, %2$s: time since last sync */
			__( 'LLM providers: %1$s | synced %2$s ago', 'botblocker-security' ),
			number_format_i18n( $llm_provider_count ),
			$llm_age
		);
	} else {
		$llm_info = __( 'LLM providers: not yet synced.', 'botblocker-security' );
	}
	if ( ! empty( $llm_error ) ) {
		$llm_info .= '<br><span class="bbcs-tx-danger">' . esc_html( $llm_error ) . '</span>';
	}
	?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="Maintenance"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/database.svg', __( 'Maintenance', 'botblocker-security' ) )
			->withDescription( __( 'Manage the plugin database, temporary files, logs, and service data to keep your site running efficiently.', 'botblocker-security' ) )
			->withDescription( __( 'Clear outdated logs, remove temporary files, and optimize the database. Regular maintenance prevents storage-related issues.', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/tools/', __( 'Tools', 'botblocker-security' ) )
			->render();
		?>
		<div>
			<?php
			SettingsGroup::make()
				->withTitle( __( 'Database', 'botblocker-security' ) )
				->withItems( static function () use ( $asn_info, $rugov_info, $llm_info, $block_rkn_enabled ): void {
					ActionButton::make()
						->withId( 'bbcs-reinstall-database' )
						->withIcon( 'reinstall' )
						->withLabel( __( 'Reinstall Database', 'botblocker-security' ) )
						->withTooltip( __( 'Reset all BotBlocker tables to default settings', 'botblocker-security' ) )
						->withVariant( ActionButton::VARIANT_DANGER )
						->render();
					ActionButton::make()
						->withId( 'bbcs-db-repair-info' )
						->withIcon( 'fix' )
						->withLabel( __( 'Repair and Optimize Database', 'botblocker-security' ) )
						->withTooltip( __( 'Open WordPress database repair and optimization tool', 'botblocker-security' ) )
						->render();
					ActionButton::make()
						->withId( 'bbcs-clear-hits-database' )
						->withIcon( 'trash' )
						->withLabel( __( 'Clear All Visitor Data', 'botblocker-security' ) )
						->withTooltip( __( 'Delete all visitor records and statistics', 'botblocker-security' ) )
						->withVariant( ActionButton::VARIANT_DANGER )
						->render();
					ActionButton::make()
						->withId( 'bbcs-clear-transients' )
						->withIcon( 'broom' )
						->withLabel( __( 'Clear transients', 'botblocker-security' ) )
						->withTooltip( __( 'Clear expired transients from the database', 'botblocker-security' ) )
						->render();
					ActionButton::make()
						->withId( 'bbcs-update-asn-database' )
						->withIcon( 'cloud-download' )
						->withLabel( __( 'Update ASN database', 'botblocker-security' ) )
						->withTooltip( __( 'Schedule an immediate background download of the latest ASN (autonomous system) database from BotBlocker servers.', 'botblocker-security' ) )
						->render();
					$info_kses = array(
						'div'  => array( 'class' => array() ),
						'span' => array( 'class' => array() ),
						'br'   => array(),
						'a'    => array( 'href' => array() ),
					);
					echo wp_kses( '<div class="bbcs-settings-info">' . $asn_info . '</div>', $info_kses );
					ActionButton::make()
						->withId( 'bbcs-update-rugov' )
						->withIcon( 'cloud-download' )
						->withLabel( __( 'Update RU-Gov list', 'botblocker-security' ) )
						->withTooltip( __( 'Schedule an immediate background download of the latest RU-Gov CIDR list from C24Be/AS_Network_List (VK excluded).', 'botblocker-security' ) )
						->withDisabled( ! $block_rkn_enabled )
						->render();
					echo wp_kses( '<div class="bbcs-settings-info">' . $rugov_info . '</div>', $info_kses );
					ActionButton::make()
						->withId( 'bbcs-sync-llm' )
						->withIcon( 'link' )
						->withLabel( __( 'Sync LLM providers', 'botblocker-security' ) )
						->withTooltip( __( 'Immediately sync LLM provider list from BotBlocker cloud.', 'botblocker-security' ) )
						->render();
					echo wp_kses( '<div class="bbcs-settings-info">' . $llm_info . '</div>', $info_kses );
				} )
				->render();
			?>

			<?php
			SettingsGroup::make()
				->withTitle( __( 'Features', 'botblocker-security' ) )
				->withItems( static function (): void {
					ActionButton::make()
						->withId( 'bbcs-clear-cookies' )
						->withIcon( 'trash' )
						->withLabel( __( 'Clear visitor cookies', 'botblocker-security' ) )
						->withTooltip( __( 'Resets all visitor cookies - visitors must re-verify', 'botblocker-security' ) )
						->render();
					ActionButton::make()
						->withId( 'bbcs-flush-rewrite-rules' )
						->withIcon( 'link' )
						->withLabel( __( 'Reset URL rewrite rules', 'botblocker-security' ) )
						->withTooltip( __( 'Helps resolve 404 errors after changing the permalink structure', 'botblocker-security' ) )
						->render();
					ActionButton::make()
						->withId( 'bbcs-flush-object-cache' )
						->withIcon( 'memory' )
						->withLabel( __( 'Clear Object Cache', 'botblocker-security' ) )
						->withTooltip( __( 'Resets the internal WordPress cache and external caching systems (Redis, Memcached)', 'botblocker-security' ) )
						->render();
				} )
				->render();
			?>
		</div>
	</div>
	<?php
};
