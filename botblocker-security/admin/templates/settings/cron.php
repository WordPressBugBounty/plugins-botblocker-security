<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\Base;
use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ToggleOption;

/**
 * Format seconds into a human-readable time remaining string.
 * Matches the format used in the legacy header JS (bbcs-common.js).
 */
function bbcs_format_time_remaining( int $seconds ): string {
	if ( $seconds < 60 ) {
		// translators: %d: seconds count, e.g. "34s"
		return sprintf( _n( '%ds', '%ds', $seconds, 'botblocker-security' ), $seconds );
	}
	if ( $seconds < 3600 ) {
		// translators: %1$d minutes, %2$d seconds, e.g. "12m 30s"
		return sprintf( __( '%1$dm %2$ds', 'botblocker-security' ), intdiv( $seconds, 60 ), $seconds % 60 );
	}
	if ( $seconds < 86400 ) {
		// translators: %1$d hours, %2$d minutes, e.g. "12h 30m"
		return sprintf( __( '%1$dh %2$dm', 'botblocker-security' ), intdiv( $seconds, 3600 ), intdiv( $seconds % 3600, 60 ) );
	}
	// translators: %1$d days, %2$d hours, e.g. "2d 5h"
	return sprintf( __( '%1$dd %2$dh', 'botblocker-security' ), intdiv( $seconds, 86400 ), intdiv( $seconds % 86400, 3600 ) );
}

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
	$tasks           = $data->cron_tasks;
	$total           = $data->cron_total_count;
	$recurring_count = $data->cron_recurring_count;
	$one_time_count  = $data->cron_one_time_count;

	$disabled_toggle = ToggleOption::make()
		->withName( 'wp_cron_enabled' )
		->withChecked( $data->wp_cron_enabled )
		->withLabel( __( 'WP-Cron enabled', 'botblocker-security' ) );

	// Filter to only scheduled tasks (matches old getTasksList behaviour)
	$scheduled_tasks = array_values(
		array_filter( $tasks, static function ( $t ) {
			return $t->next_run !== null;
		} )
	);
	$scheduled_total      = count( $scheduled_tasks );
	$scheduled_recurring  = count( array_filter( $scheduled_tasks, static function ( $t ) {
		return $t->type === 'recurring';
	} ) );
	$scheduled_onetime    = count( array_filter( $scheduled_tasks, static function ( $t ) {
		return $t->type === 'one-time';
	} ) );

	// Build summary: "N tasks · X recurring · Y one-time"
	$summary_parts = array();
	$summary_parts[] = sprintf(
		// translators: %d: total task count
		__( '%d tasks', 'botblocker-security' ),
		$scheduled_total
	);
	if ( $scheduled_recurring > 0 ) {
		$summary_parts[] = sprintf(
			// translators: %d: recurring task count
			__( '%d recurring', 'botblocker-security' ),
			$scheduled_recurring
		);
	}
	if ( $scheduled_onetime > 0 ) {
		$summary_parts[] = sprintf(
			// translators: %d: one-time task count
			__( '%d one-time', 'botblocker-security' ),
			$scheduled_onetime
		);
	}
	$summary_text = implode( ' · ', $summary_parts );
?>
	<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="cron"<?php echo $isActive ? '' : ' hidden' ?>>

		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/cron.svg', __( 'Cron Jobs', 'botblocker-security' ) )
			->withDescription( __( 'BotBlocker uses cron jobs for log cleanup and data processing.', 'botblocker-security' ) )
			->withDescription( __( 'If WP-Cron is unreliable, set up a system cron job using the commands below.', 'botblocker-security' ) )
			->withDocLink( 'https://developer.wordpress.org/plugins/cron/', __( 'WP-Cron', 'botblocker-security' ) )
			->withDocLink( 'https://en.wikipedia.org/wiki/Cron', __( 'Cron', 'botblocker-security' ) )
			->withDocLink( 'https://docs.cpanel.net/cpanel/advanced/cron-jobs/', __( 'cPanel Cron Jobs', 'botblocker-security' ) )
			->withDocLink( 'https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/scheduling-tasks.64993/', __( 'Plesk Cron', 'botblocker-security' ) )
			->withDocLink( 'https://www.ispmanager.com/docs/ispmanager/scheduler-cron', __( 'ISPmanager scheduler', 'botblocker-security' ) )
			->render();
		?>

		<div>
		<?php
		// ── Cron Settings ────────────────────────────────────────────
		SettingsGroup::make()
			->withTitle( __( 'Cron Settings', 'botblocker-security' ) )
			->withItems( static function () use ( $data, $disabled_toggle ): void {
				?>
				<div class="bbcs-togglerow" data-anchor="cron-system-cron">
					<div class="bbcs-fill bbcs-row bbcs-g-2">
						<?php $disabled_toggle->withDisabled()->render(); ?>
						<span class="bbcs-help">
							<span class="bbcs-help-q">?</span>
							<span class="bbcs-help-tip"><?php esc_html_e( 'WP-Cron status (read-only, managed automatically).', 'botblocker-security' ); ?></span>
						</span>
					</div>
					<span class="bbcs-pill bbcs-pill--green bbcs-pill--dot"><?php esc_html_e( 'Active', 'botblocker-security' ); ?></span>
				</div>
				<div class="bbcs-field" data-anchor="cron_curl">
					<div class="bbcs-field-label">
						<span><?php esc_html_e( 'cURL command (for system cron)', 'botblocker-security' ); ?></span>
						<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						<?php echo Base::tooltip( __( 'Copy this command to your server crontab.', 'botblocker-security' ) ); ?>
					</div>
					<div class="bbcs-field-box">
						<span class="bbcs-field-val bbcs-mono bbcs-fs-xs"><?php echo esc_html( $data->curl_cmd ); ?></span>
						<button type="button" class="bbcs-btn bbcs-btn--ghost bbcs-btn--icon bbcs-ml-1h bbcs-copy-btn" onclick="copyToClipboard(this)" title="<?php esc_attr_e( 'Copy to clipboard', 'botblocker-security' ); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-copy"></use></svg></button>
					</div>
				</div>
				<div class="bbcs-field" data-anchor="cron_wget">
					<div class="bbcs-field-label">
						<span><?php esc_html_e( 'Wget command (for system cron)', 'botblocker-security' ); ?></span>
						<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
						<?php echo Base::tooltip( __( 'Copy this command to your server crontab.', 'botblocker-security' ) ); ?>
					</div>
					<div class="bbcs-field-box">
						<span class="bbcs-field-val bbcs-mono bbcs-fs-xs"><?php echo esc_html( $data->wget_cmd ); ?></span>
						<button type="button" class="bbcs-btn bbcs-btn--ghost bbcs-btn--icon bbcs-ml-1h bbcs-copy-btn" onclick="copyToClipboard(this)" title="<?php esc_attr_e( 'Copy to clipboard', 'botblocker-security' ); ?>"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-copy"></use></svg></button>
					</div>
				</div>
				<?php
				// Task Recovery (static informational - read-only)
				SettingsGroup::make()
					->withTitle( __( 'Task Recovery', 'botblocker-security' ) )
					->withInfo( __( 'The fallback runner checks overdue tasks (>1.5× interval) and recreates missing cron events. Runs automatically every 15 minutes via the WordPress init hook.', 'botblocker-security' ) )
					->withItems( static function (): void {
						?>
						<div class="bbcs-togglerow" data-anchor="cron-task-recovery-auto">
							<div class="bbcs-fill">
								<div class="bbcs-togglerow-label"><?php esc_html_e( 'Automatic task recovery', 'botblocker-security' ); ?></div>
								<div class="bbcs-togglerow-desc"><?php esc_html_e( 'If a scheduled task is missed (WP Cron unstable), BotBlocker will detect and reschedule it on the next check.', 'botblocker-security' ); ?></div>
							</div>
							<span class="bbcs-pill bbcs-pill--green bbcs-pill--dot"><?php esc_html_e( 'Active', 'botblocker-security' ); ?></span>
						</div>
						<div class="bbcs-togglerow bbcs-mt-1h" data-anchor="cron-task-recovery-fallback">
							<div class="bbcs-fill">
								<div class="bbcs-togglerow-label"><?php esc_html_e( 'Fallback check every 15 minutes', 'botblocker-security' ); ?></div>
								<div class="bbcs-togglerow-desc"><?php esc_html_e( 'Checks for missed tasks every 15 minutes via the WordPress', 'botblocker-security' ); ?> <code class="bbcs-mono">init</code> <?php esc_html_e( 'hook. Independent of WP Cron.', 'botblocker-security' ); ?></div>
							</div>
							<span class="bbcs-pill bbcs-pill--green bbcs-pill--dot"><?php esc_html_e( 'Active', 'botblocker-security' ); ?></span>
						</div>
						<?php
					} )
					->render();
			} )
			->render();

		// ── Task List ────────────────────────────────────────────────
		SettingsGroup::make()
			->withTitle( __( 'Task List', 'botblocker-security' ) )
			->withItems( static function () use ( $data, $scheduled_tasks, $summary_text ): void {
				?>
				<div class="bbcs-row bbcs-g-2 bbcs-mb-3h bbcs-ai-center" data-anchor="task-list">
					<div class="bbcs-fill bbcs-fs-xs bbcs-dim bbcs-mono"><?php echo esc_html( $summary_text ); ?></div>
				</div>
				<div class="bbcs-table-wrap" data-anchor="task-list-table">
				<table class="bbcs-table bbcs-table--cron">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Hook', 'botblocker-security' ); ?></th>
							<th><?php esc_html_e( 'Schedule', 'botblocker-security' ); ?></th>
							<th><?php esc_html_e( 'Label', 'botblocker-security' ); ?></th>
							<th><?php esc_html_e( 'Next Run', 'botblocker-security' ); ?></th>
							<th><?php esc_html_e( 'Progress', 'botblocker-security' ); ?></th>
							<th><?php esc_html_e( 'Status', 'botblocker-security' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $scheduled_tasks as $task ) : ?>
						<tr data-bbcs-cron-hook="<?php echo esc_attr( $task->hook ); ?>">
							<td class="bbcs-mono bbcs-fs-2xs" data-label="<?php esc_attr_e( 'Hook', 'botblocker-security' ); ?>"><?php echo esc_html( $task->hook ); ?></td>
							<td data-label="<?php esc_attr_e( 'Schedule', 'botblocker-security' ); ?>">
								<?php if ( $task->type === 'recurring' ) : ?>
									<span class="bbcs-tag bbcs-tag--blue"><?php echo esc_html( $task->schedule ); ?></span>
								<?php else : ?>
									<span class="bbcs-tag"><?php echo esc_html( $task->schedule ); ?></span>
								<?php endif; ?>
							</td>
							<td data-label="<?php esc_attr_e( 'Label', 'botblocker-security' ); ?>"><?php echo esc_html( $task->label ); ?></td>
							<td class="bbcs-mono bbcs-fs-xs" data-label="<?php esc_attr_e( 'Next Run', 'botblocker-security' ); ?>">
								<?php if ( $task->next_run_display !== null ) : ?>
									<?php echo esc_html( $task->next_run_display ); ?>
								<?php else : ?>
									<span class="bbcs-dim">&mdash;</span>
								<?php endif; ?>
							</td>
							<td data-label="<?php esc_attr_e( 'Progress', 'botblocker-security' ); ?>">
								<div class="bbcs-cron-progress" title="<?php echo esc_attr( round( $task->progress ) ); ?>%">
									<div class="bbcs-cron-progress-bar bbcs-cron-progress-bar--animated" style="width: <?php echo esc_attr( (string) $task->progress ); ?>%;"></div>
									<span class="bbcs-cron-progress-s" data-seconds="<?php echo esc_attr( (string) $task->time_remaining ); ?>"><?php echo esc_html( bbcs_format_time_remaining( $task->time_remaining ) ); ?></span>
								</div>
							</td>
							<td data-label="<?php esc_attr_e( 'Status', 'botblocker-security' ); ?>">
								<?php if ( $task->status === 'active' ) : ?>
									<span class="bbcs-tag bbcs-tag--green"><?php esc_html_e( 'Active', 'botblocker-security' ); ?></span>
								<?php elseif ( $task->status === 'overdue' ) : ?>
									<span class="bbcs-tag bbcs-tag--red"><?php esc_html_e( 'Overdue', 'botblocker-security' ); ?></span>
								<?php else : ?>
									<span class="bbcs-tag"><?php esc_html_e( 'Pending', 'botblocker-security' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
						<?php if ( empty( $scheduled_tasks ) ) : ?>
						<tr>
							<td colspan="6" class="bbcs-ta-center bbcs-dim bbcs-fs-sm" style="padding:var(--bbcs-sp-7) var(--bbcs-sp-4);"><?php esc_html_e( 'No scheduled tasks at this time.', 'botblocker-security' ); ?></td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
				</div>
				<?php
			} )
			->render();
		?>
		</div>

	</div>

<script>
(function() {
	'use strict';
	function fmt(sec) {
		if (sec < 60) return sec + 's';
		if (sec < 3600) return Math.floor(sec / 60) + 'm ' + (sec % 60) + 's';
		if (sec < 86400) return Math.floor(sec / 3600) + 'h ' + Math.floor((sec % 3600) / 60) + 'm';
		var d = Math.floor(sec / 86400);
		var h = Math.floor((sec % 86400) / 3600);
		return d + 'd ' + h + 'h';
	}

	setInterval(function() {
		var els = document.querySelectorAll('.bbcs-cron-progress-s');
		[].forEach.call(els, function(el) {
			var s = parseInt(el.getAttribute('data-seconds'), 10);
			if (s > 0) {
				s -= 1;
				el.setAttribute('data-seconds', s);
				el.textContent = fmt(s);
			} else {
				el.textContent = '<?php echo esc_js( __( 'Overdue', 'botblocker-security' ) ); ?>';
			}
		});
	}, 1000);
})();
</script>
<?php
};
