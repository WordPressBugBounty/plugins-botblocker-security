<?php
use BotBlocker\Component\LanguageOptionItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_HeaderViewModel $header ): void {
	$h = $header;
	?>
	<li>
		<a href="#" class="dropdown-toggle notification-icon" data-bs-toggle="dropdown">
			<i class="fa fa-globe"></i>
		</a>
		<div class="dropdown-menu notification-menu">
			<div class="notification-title">
				<?php esc_html_e( 'Select Language', 'botblocker-security' ); ?>
			</div>
			<div class="content">
				<ul>
			<?php if ( ! empty( $h->lang_options ) ) : ?>
				<?php foreach ( $h->lang_options as $opt ) : ?>
					<?php LanguageOptionItem::make()->withOption( $opt )->render(); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<li>
					<a href="#" class="language-option" data-lang="en_US">
						<div class="flag flag-us"></div>
						<span class="title"><?php esc_html_e( 'English', 'botblocker-security' ); ?></span>
					</a>
				</li>
				<li>
					<a href="#" class="language-option" data-lang="ru_RU">
						<div class="flag flag-ru"></div>
						<span class="title"><?php esc_html_e( 'Russian', 'botblocker-security' ); ?></span>
					</a>
				</li>
				<li>
					<a href="#" class="language-option" data-lang="ar">
						<div class="flag flag-sa"></div>
						<span class="title"><?php esc_html_e( 'Arabic', 'botblocker-security' ); ?></span>
					</a>
				</li>
			<?php endif; ?>
				</ul>
			</div>
		</div>
	</li>
	<?php
};
