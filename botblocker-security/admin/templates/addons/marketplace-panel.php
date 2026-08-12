<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (Botblocker_AddonsViewModel $data, bool $isActive): void {
	$render_card = require BOTBLOCKER_DIR . 'admin/templates/addons/marketplace-card.php';
?>
	<?php if (! empty($data->market_lazy)) : ?>
		<div class="bbcs-market-notice" id="bbcs-market-notice" hidden></div>
	<?php endif; ?>
	<div class="bbcs-grid bbcs-grid--3" id="bbcs-market-grid"<?php echo ! empty($data->market_lazy) ? ' data-bbcs-market-lazy="1"' : ''; ?>>
		<?php if (! empty($data->market_lazy)) : ?>
			<?php foreach ($data->addons as $slug => $bbcs_addon) : ?>
				<?php $render_card($data, $slug, null, $bbcs_addon); ?>
			<?php endforeach; ?>
			<?php for ($i = 0; $i < 6; $i++) : ?>
				<div class="bbcs-card bbcs-addon bbcs-addon-skeleton"<?php echo 0 === $i ? ' id="bbcs-market-catalog-pending"' : ''; ?> aria-hidden="true">
					<div class="bbcs-addon-head">
						<span class="bbcs-tile bbcs-skel-tile"></span>
						<div class="bbcs-addon-info">
							<div class="bbcs-skel-line bbcs-skel-line--name"></div>
							<div class="bbcs-skel-line bbcs-skel-line--ver"></div>
						</div>
					</div>
					<div class="bbcs-addon-body">
						<div class="bbcs-skel-line bbcs-skel-line--desc"></div>
						<div class="bbcs-skel-line bbcs-skel-line--desc-short"></div>
					</div>
					<div class="bbcs-addon-divider"></div>
					<div class="bbcs-addon-footer">
						<div class="bbcs-skel-line bbcs-skel-line--btn"></div>
					</div>
				</div>
			<?php endfor; ?>
			<span class="bbcs-sr-only" aria-live="polite"><?php esc_html_e('Loading add-on catalog…', 'botblocker-security'); ?></span>
		<?php else : ?>
			<?php
			foreach ($data->marketplace_installed_cards as $bbcs_card) :
				$slug  = $bbcs_card->slug;
				$bbcs_item = isset($data->marketBySlug[$slug]) ? $data->marketBySlug[$slug] : null;
				$local     = isset($data->addons[$slug]) ? $data->addons[$slug] : null;
				$render_card($data, $slug, $bbcs_item, $local);
			endforeach;

			foreach ($data->marketplace_available_cards as $bbcs_card) :
				$slug  = $bbcs_card->slug;
				$bbcs_item = isset($data->marketBySlug[$slug]) ? $data->marketBySlug[$slug] : null;
				$render_card($data, $slug, $bbcs_item, null);
			endforeach;
			?>
		<?php endif; ?>
	</div>
	<?php
};
