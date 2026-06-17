<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="tab-pane container fade" id="tools-maintenance"> 
	<div class="row">		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/database.svg' ); ?>" 
					alt="<?php esc_attr_e( 'Maintenance', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Manage the plugin database, temporary files, logs, and service data to keep your site running efficiently.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Clear outdated logs, remove temporary files, and optimize the database. Regular maintenance prevents storage-related issues.', 'botblocker-security' ); ?>
				</p>
				
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/tools/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Tools', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Database', 'botblocker-security' ); ?></h3>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-reinstall-database" class="mb-1 btn btn-xs btn-danger">
					<i class="fas fa-sync"></i>
					<?php esc_html_e( 'Reinstall Database', 'botblocker-security' ); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_html_e( 'Reset all BotBlocker tables to default settings', 'botblocker-security' ); ?>"></i>
			</div>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-db-repair-info" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-screwdriver-wrench"></i>
					<?php esc_html_e( 'Repair and Optimize Database', 'botblocker-security' ); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Open WordPress database repair and optimization tool', 'botblocker-security' ); ?>"></i>
			</div>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-clear-hits-database" class="mb-1 btn btn-xs btn-default">
					<i class="fa-regular fa-trash-can"></i>
					<?php esc_html_e( 'Clear All Visitor Data', 'botblocker-security' ); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_html_e( 'Delete all visitor records and statistics', 'botblocker-security' ); ?>"></i>
			</div>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-clear-transients" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-broom"></i>
					<?php esc_html_e( 'Clear transients', 'botblocker-security' ); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Clear expired transients from the database', 'botblocker-security' ); ?>"></i>
			</div>

			<?php
			$bbcs_asn_status  = class_exists( 'BotBlockerAsnDb' ) ? BotBlockerAsnDb::getStatus() : array();
			$bbcs_asn_state   = isset( $bbcs_asn_status['state'] ) ? (string) $bbcs_asn_status['state'] : 'absent';
			$bbcs_asn_size    = isset( $bbcs_asn_status['size'] ) ? (int) $bbcs_asn_status['size'] : 0;
			$bbcs_asn_dl      = isset( $bbcs_asn_status['downloaded_at'] ) ? (int) $bbcs_asn_status['downloaded_at'] : 0;
			$bbcs_asn_type    = isset( $bbcs_asn_status['database_type'] ) ? (string) $bbcs_asn_status['database_type'] : '';
			$bbcs_asn_present = class_exists( 'BotBlockerAsnDb' ) ? BotBlockerAsnDb::isPresent() : false;
			?>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-update-asn-database" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-cloud-arrow-down"></i>
					<?php esc_html_e( 'Update ASN database', 'botblocker-security' ); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Schedule an immediate background download of the latest ASN (autonomous system) database from BotBlocker servers.', 'botblocker-security' ); ?>"></i>
			</div>
			<div class="bbcs_settings_info" id="bbcs-asn-database-status" style="font-size:12px;color:#666;margin:4px 0 8px 4px;">
				<?php
				if ( $bbcs_asn_present && $bbcs_asn_dl > 0 ) {
					$bbcs_asn_age = human_time_diff( $bbcs_asn_dl, time() );
					echo esc_html(
						sprintf(
						/* translators: 1: ASN database type, 2: file size, 3: human-readable time difference. */
							__( 'ASN DB: %1$s | %2$s | updated %3$s ago', 'botblocker-security' ),
							$bbcs_asn_type !== '' ? $bbcs_asn_type : __( 'ASN database', 'botblocker-security' ),
							size_format( $bbcs_asn_size ),
							$bbcs_asn_age
						)
					);
				} else {
					esc_html_e( 'ASN DB: not yet downloaded.', 'botblocker-security' );
				}
				if ( ! empty( $bbcs_asn_status['last_error'] ) ) {
					echo '<br><span style="color:#b32d2e;">' . esc_html(
						sprintf(
						/* translators: %s: short error code returned by the ASN downloader. */
							__( 'Last error: %s', 'botblocker-security' ),
							$bbcs_asn_status['last_error']
						)
					) . '</span>';
				}
				?>
			</div>

			<?php
			$bbcs_rugov_status     = class_exists( 'BotBlockerRugov' ) ? BotBlockerRugov::getStatus() : array();
			$bbcs_rugov_state      = isset( $bbcs_rugov_status['state'] ) ? (string) $bbcs_rugov_status['state'] : 'absent';
			$bbcs_rugov_last_sync  = isset( $bbcs_rugov_status['last_sync'] ) ? (int) $bbcs_rugov_status['last_sync'] : 0;
			$bbcs_rugov_range_count = isset( $bbcs_rugov_status['range_count'] ) ? (int) $bbcs_rugov_status['range_count'] : 0;
			$bbcs_rugov_present    = class_exists( 'BotBlockerRugov' ) ? BotBlockerRugov::isFilePresent() : false;
			?>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-update-rugov" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-cloud-arrow-down"></i>
					<?php esc_html_e( 'Update RU-Gov list', 'botblocker-security' ); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Schedule an immediate background download of the latest RU-Gov CIDR list from C24Be/AS_Network_List (VK excluded).', 'botblocker-security' ); ?>"></i>
			</div>
			<div class="bbcs_settings_info" id="bbcs-rugov-status" style="font-size:12px;color:#666;margin:4px 0 8px 4px;">
				<?php
				if ( $bbcs_rugov_present && $bbcs_rugov_last_sync > 0 ) {
					$bbcs_rugov_age = human_time_diff( $bbcs_rugov_last_sync, time() );
					echo esc_html(
						sprintf(
						/* translators: 1: number of CIDR ranges, 2: human-readable time difference. */
							__( 'RU-Gov: %1$s ranges | updated %2$s ago', 'botblocker-security' ),
							number_format_i18n( $bbcs_rugov_range_count ),
							$bbcs_rugov_age
						)
					);
				} else {
					esc_html_e( 'RU-Gov list: not yet downloaded.', 'botblocker-security' );
				}
				if ( ! empty( $bbcs_rugov_status['last_error'] ) ) {
					echo '<br><span style="color:#b32d2e;">' . esc_html(
						sprintf(
						/* translators: %s: short error code returned by the RU-Gov downloader. */
							__( 'Last error: %s', 'botblocker-security' ),
							$bbcs_rugov_status['last_error']
						)
					) . '</span>';
				}
				?>
			</div>

			<?php
			$bbcs_llm_status         = BotBlockerLlmSync::getStatus();
			$bbcs_llm_last_sync      = isset( $bbcs_llm_status['last_sync'] ) ? (int) $bbcs_llm_status['last_sync'] : 0;
			$bbcs_llm_provider_count = isset( $bbcs_llm_status['provider_count'] ) ? (int) $bbcs_llm_status['provider_count'] : 0;
			$bbcs_llm_state          = isset( $bbcs_llm_status['state'] ) ? (string) $bbcs_llm_status['state'] : 'absent';
			?>
			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-sync-llm" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-cloud-arrow-down"></i>
					<?php esc_html_e( 'Sync LLM providers', 'botblocker-security' ); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Immediately sync LLM provider list from BotBlocker cloud.', 'botblocker-security' ); ?>"></i>
			</div>
			<div class="bbcs_settings_info" id="bbcs-llm-sync-status" style="font-size:12px;color:#666;margin:4px 0 8px 4px;">
				<?php
				if ( $bbcs_llm_last_sync > 0 ) {
					$bbcs_llm_age = human_time_diff( $bbcs_llm_last_sync, time() );
					echo esc_html(
						sprintf(
						/* translators: 1: number of LLM providers, 2: human-readable time difference. */
							__( 'LLM providers: %1$s | synced %2$s ago', 'botblocker-security' ),
							number_format_i18n( $bbcs_llm_provider_count ),
							$bbcs_llm_age
						)
					);
				} else {
					esc_html_e( 'LLM providers: not yet synced.', 'botblocker-security' );
				}
				if ( ! empty( $bbcs_llm_status['last_error'] ) ) {
					echo '<br><span style="color:#b32d2e;">' . esc_html(
						sprintf(
						/* translators: %s: error message from LLM sync. */
							__( 'Last error: %s', 'botblocker-security' ),
							$bbcs_llm_status['last_error']
						)
					) . '</span>';
				}
				?>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Features', 'botblocker-security' ); ?></h3>

			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-clear-cookies" class="mb-1 btn btn-xs btn-default">
					<i class="fa-regular fa-trash-can"></i>
					<?php esc_html_e( 'Clear visitor cookies', 'botblocker-security' ); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Resets all visitor cookies - visitors must re-verify', 'botblocker-security' ); ?>"></i>
			</div>

			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-flush-rewrite-rules" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-link"></i>
					<?php esc_html_e( 'Reset URL rewrite rules', 'botblocker-security' ); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Helps resolve 404 errors after changing the permalink structure', 'botblocker-security' ); ?>"></i>
			</div>

			<div class="bbcs_settings_button">
				<button type="button" id="bbcs-flush-object-cache" class="mb-1 btn btn-xs btn-default">
					<i class="fa-solid fa-memory"></i>
					<?php esc_html_e( 'Clear Object Cache', 'botblocker-security' ); ?>
				</button>
				<i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Resets the internal WordPress cache and external caching systems (Redis, Memcached)', 'botblocker-security' ); ?>"></i>
			</div>			
		</div>
		
	</div>
</div>
