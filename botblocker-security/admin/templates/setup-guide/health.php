<?php

use BotBlocker\Component\Card;
use BotBlocker\Component\HealthItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_SetupGuideViewModel $data ): void {
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
	echo Card::make()
		->withClass( 'mb-4' )
		->withTitle( __( 'Security Health Status', 'botblocker-security' ) )
		->block(
			static function () use ( $data ): void {
				?>
	<div class="bbcs-vertical-stack mb-2">
		<?php $data->health_gauge->render(); ?>
		<div class="d-flex justify-content-evenly">
			<a href="#" class="bbcs-bbcs-btn bbcs-btn--sm bbcs-btn-primary-cta rounded-5" id="bbcsOpenOneClickSetup">
				<i class="fa-solid fa-wand-magic-sparkles"></i>
				<?php esc_html_e( 'One-Click Setup', 'botblocker-security' ); ?>
			</a>
		</div>
	</div>
	<div class="bbcs-health-full">
		<?php foreach ( $data->raw_items_chunked as $bbcs_col_items ) : ?>
			<div class="bbcs-health-col">
				<?php foreach ( $bbcs_col_items as $bbcs_it ) : ?>
					<?php HealthItem::make()->withItem( $bbcs_it )->render(); ?>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
				<?php
			}
		);
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
};
