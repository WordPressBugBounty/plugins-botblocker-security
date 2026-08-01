<?php
use BotBlocker\Component\NewsItem;

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
				<a href="<?php echo esc_url( $s->news_url ); ?>" target="_blank"><i class="fa-solid fa-globe bbcs-h-btn-gray"></i></a>
				<span class="bbcs-help-tip"><?php esc_html_e( 'BotBlocker News', 'botblocker-security' ); ?></span>
			</span>
			</div>
			<h2 class="card-title"><?php esc_html_e( 'News', 'botblocker-security' ); ?></h2>
		</header>
		<div class="card-body">
			<?php if ( ! empty( $s->news_items ) ) : ?>
				<ul class="bbcs_botblocker-news">
				<?php foreach ( $s->news_items as $item ) : ?>
					<?php NewsItem::make()->withItem( $item )->render(); ?>
				<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p><?php esc_html_e( 'No news items available', 'botblocker-security' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="card-footer">
			<small>
				<?php echo wp_kses_post( $s->database_update_text ); ?>
				<br>
				<?php echo wp_kses_post( $s->database_total_text ); ?>
			</small>
		</div>
	</section>
	<?php
};
