<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?><div class="modal fade" id="confirmObjectCacheModal" tabindex="-1" aria-labelledby="confirmObjectCacheModalLabel" aria-hidden="true">
<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<h5 class="modal-title" id="confirmObjectCacheModalLabel"><?php esc_html_e( 'Clear Object Cache', 'botblocker-security' ); ?></h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		</div>
		<div class="modal-body">
			<p class="bbcs-black"><strong><?php esc_html_e( 'Attention!', 'botblocker-security' ); ?></strong> <?php esc_html_e( 'The WordPress object cache will be cleared:', 'botblocker-security' ); ?></p>
			<ul class="bbcs-black bbcs-modal-ul">
				<li class="bbcs-modal-li"><?php esc_html_e( 'Resets the internal WordPress cache.', 'botblocker-security' ); ?></li>
				<li class="bbcs-modal-li"><?php esc_html_e( 'External caching systems (Redis, Memcached) will also be cleared.', 'botblocker-security' ); ?></li>
				<li class="bbcs-modal-li"><?php esc_html_e( 'Performance may decrease temporarily until the cache rebuilds.', 'botblocker-security' ); ?></li>
				<li class="bbcs-modal-li"><?php esc_html_e( 'Safe for production use.', 'botblocker-security' ); ?></li>
			</ul>
			<p class="bbcs-black"><?php esc_html_e( 'Continue clearing the object cache?', 'botblocker-security' ); ?></p>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'botblocker-security' ); ?></button>
			<button type="button" id="confirmObjectCacheButton" class="btn btn-primary btn-xs"><?php esc_html_e( 'Clear the cache', 'botblocker-security' ); ?></button>
		</div>
	</div>
</div>
</div>
