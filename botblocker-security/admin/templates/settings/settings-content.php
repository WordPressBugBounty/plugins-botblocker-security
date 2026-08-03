<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\SidebarNav;

/**
 * Tab IDs that belong to the Simple / General group (visible in simple mode).
 * All other tabs are hidden when simple mode is on.
 * Matches the first block of the old layout.
 */
function bbcs_get_simple_tab_ids(): array {
	return array(
		'simple-detection',
		'connection-types',
		'browser-plugins',
		'data-log',
		'advanced-protection',
	);
}

return static function (Botblocker_SettingsViewModel $data): void {
	$active_tab = '';
	foreach ($data->nav_groups as $group) {
		foreach ($group['items'] as $item) {
			if ($item->active) {
				$active_tab = $item->id;
				break 2;
			}
		}
	}

	$bbcs_tabpanels = bbcs_get_tabpanels();
	$simple_ids     = bbcs_get_simple_tab_ids();

	// Build list of simple nav item IDs so the sidebar nav can mark advanced items.
	$simple_nav_ids = array();
	foreach ( $data->nav_groups as $group ) {
		foreach ( $group['items'] as $item ) {
			if ( in_array( $item->id, $simple_ids, true ) ) {
				$simple_nav_ids[] = $item->id;
			}
		}
	}

	if ( $active_tab === '' || ! in_array( $active_tab, $simple_ids, true ) ) {
		$active_tab = $simple_ids[0] ?? ( array_keys( $bbcs_tabpanels )[0] ?? '' );
	}

?>
	<div class="bbcs-settings-layout">
		<?php
		SidebarNav::make()
			->withGroups( $data->nav_groups )
			->withSimpleNavIds( $simple_nav_ids )
			->withAriaLabel( __( 'Settings sections', 'botblocker-security' ) )
			->withSearchPlaceholder( __( 'Find setting…', 'botblocker-security' ) )
			->withDefaultIcon( 'search' )
			->withDefaultLabel( __( 'Bot Detection', 'botblocker-security' ) )
			->withSimpleModeToggle( true )
			->render();
		?>

		<div class="bbcs-settings-content">
			<div class="bbcs-card bbcs-card-pad bbcs-mb-5h">
				<?php
				foreach ( $bbcs_tabpanels as $tab_id => $tab_file ) {
					$is_simple_tab = in_array( $tab_id, $simple_ids, true );
					$is_visible    = $tab_id === $active_tab && $is_simple_tab;
					(require $tab_file)( $data, $is_visible );
				}
				?>
			</div>
		</div>
	</div>
<?php
};
