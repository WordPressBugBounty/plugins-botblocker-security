<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SidebarViewModel $s ): void {
	$base = BOTBLOCKER_DIR . 'admin/templates/shared/sidebar/';

	( require $base . 'status-card.php' )( $s );

	if ( ! $s->cloud_api_active ) {
		( require $base . 'pro-features.php' )( $s );
	}

	( require $base . 'social-proof.php' )( $s );

	if ( $s->display_news ) {
		( require $base . 'news.php' )( $s );
	}

	( require $base . 'system-status.php' )( $s );

	if ( ! $s->contact_collected ) {
		( require $base . 'subscribe-card.php' )( $s );
	}
};
