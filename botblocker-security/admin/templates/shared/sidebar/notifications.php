<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_NotificationsViewModel $card ): void {
	if ( empty( $card->rows ) ) {
		return;
	}
	?>
	<div class="bbcs-card bbcs-notif" id="bbcs-notif">
		<div class="bbcs-row bbcs-row--between bbcs-notif-head">
			<span class="bbcs-row bbcs-g-1h bbcs-rail-title"><svg class="bbcs-ico bbcs-ico--md bbcs-notif-bell"><use href="#bbcs-i-bell"></use></svg><?php esc_html_e( 'Notifications', 'botblocker-security' ); ?></span>
			<span class="bbcs-notif-count"><?php echo esc_html( (string) $card->total ); ?></span>
		</div>
		<div class="bbcs-notif-list">
		<?php foreach ( $card->rows as $i => $row ) : ?>
			<?php
			$class      = $card->rowClass( $row, $i );
			$hidden     = $card->hiddenAttribute( $i );
			$icon       = $row->iconName();
			$link_class = $row->linkClass();
			?>
			<div class="<?php echo esc_attr( $class ); ?>"<?php echo esc_attr( $hidden ); ?>>
				<span class="bbcs-notif-ico"><svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-<?php echo esc_attr( $icon ); ?>"></use></svg></span>
				<span class="bbcs-notif-body">
					<span class="bbcs-notif-line">
						<b class="bbcs-fs-xs bbcs-notif-title"><?php echo esc_html( $row->title ); ?></b>
						<?php if ( $row->pro ) : ?>
						<span class="bbcs-chip bbcs-chip--pro bbcs-notif-chip"><?php esc_html_e( 'PRO', 'botblocker-security' ); ?></span>
						<?php endif; ?>
					</span>
					<?php if ( '' !== $row->message ) : ?>
					<span class="bbcs-dim bbcs-fs-2xs bbcs-notif-msg"><?php echo esc_html( $row->message ); ?></span>
					<?php endif; ?>
				</span>
				<?php if ( '' !== $row->link ) : ?>
				<a class="<?php echo esc_attr( $link_class ); ?> bbcs-fs-2xs" href="<?php echo esc_url( $row->link ); ?>"><?php echo esc_html( $row->link_text ); ?> →</a>
				<?php endif; ?>
				<?php if ( '' !== $row->extra_link ) : ?>
				<a class="<?php echo esc_attr( $link_class ); ?> bbcs-fs-2xs" href="<?php echo esc_url( $row->extra_link ); ?>"><?php echo esc_html( $row->extra_link_text ); ?> →</a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		</div>
		<?php if ( $card->hasExtra() ) : ?>
		<button type="button" class="bbcs-btn bbcs-btn--ghost bbcs-notif-toggle" id="bbcs-notif-toggle" aria-expanded="false"
			data-more="<?php echo esc_attr( sprintf( /* translators: %d: number of notifications */ __( 'Show all (%d)', 'botblocker-security' ), $card->total ) ); ?>"
			data-less="<?php esc_attr_e( 'Show less', 'botblocker-security' ); ?>"><?php echo esc_html( sprintf( /* translators: %d: number of notifications */ __( 'Show all (%d)', 'botblocker-security' ), $card->total ) ); ?></button>
		<script>
		(function() {
			var btn = document.getElementById('bbcs-notif-toggle');
			var card = document.getElementById('bbcs-notif');
			if (!btn || !card) return;
			btn.addEventListener('click', function() {
				var open = btn.getAttribute('aria-expanded') === 'true';
				var extra = card.querySelectorAll('.bbcs-notif-row--extra');
				for (var i = 0; i < extra.length; i++) { extra[i].hidden = open; }
				btn.setAttribute('aria-expanded', open ? 'false' : 'true');
				btn.textContent = open ? btn.getAttribute('data-more') : btn.getAttribute('data-less');
			});
		})();
		</script>
		<?php endif; ?>
	</div>
	<?php
};
