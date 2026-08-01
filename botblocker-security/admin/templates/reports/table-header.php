<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (): void {
	?>
	<thead>
	<tr>
		<th style="min-width: 85px;"><?php esc_html_e( 'Date', 'botblocker-security' ); ?>/<?php esc_html_e( 'Time', 'botblocker-security' ); ?></th>
		<th style="min-width: 100px;"><?php esc_html_e( 'IP', 'botblocker-security' ); ?>/<?php esc_html_e( 'PTR', 'botblocker-security' ); ?></th>
		<th style="min-width: 100px;"><?php esc_html_e( 'AS Info', 'botblocker-security' ); ?></th>
		<th style="min-width: 110px;"><?php esc_html_e( 'Lang', 'botblocker-security' ); ?></th>
		<th style="min-width: 200px;"><?php esc_html_e( 'User Agent', 'botblocker-security' ); ?></th>
		<th style="min-width: 300px;"><?php esc_html_e( 'Page', 'botblocker-security' ); ?>/<?php esc_html_e( 'Referer', 'botblocker-security' ); ?></th>
		<th style="min-width: 200px;"><?php esc_html_e( 'JS Info', 'botblocker-security' ); ?></th>
		<th style="min-width: 100px;"><i class="fa-solid fa-ban"></i></th>
	</tr>
	</thead>
	<?php
};
