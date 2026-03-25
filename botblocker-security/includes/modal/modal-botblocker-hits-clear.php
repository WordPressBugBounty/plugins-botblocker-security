<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="modal fade" id="confirmHitsClearModal" tabindex="-1" aria-labelledby="confirmHitsClearModalLabel" aria-hidden="true">
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="confirmHitsClearModalLabel"><?php esc_html_e('Clear Visitor Data', 'botblocker-security'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <p class="bbcs-black"><strong><?php esc_html_e('Attention!', 'botblocker-security'); ?></strong> <?php esc_html_e('All visitor and statistics data will be cleared.', 'botblocker-security'); ?></p>
            <ul class="bbcs-black bbcs-modal-ul">
                <li class="bbcs-modal-li"><?php esc_html_e('Regular hits will be removed from the database.', 'botblocker-security'); ?></li>
                <li class="bbcs-modal-li"><?php esc_html_e('Dashboard counters and daily statistics will be reset.', 'botblocker-security'); ?></li>
            </ul>
            <p class="bbcs-black"><?php esc_html_e('Continue clearing visitor data?', 'botblocker-security'); ?></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'botblocker-security'); ?></button>
            <button type="button" id="confirmHitsClearButton" class="btn btn-primary btn-xs"><?php esc_html_e('Clear visitors data', 'botblocker-security'); ?></button>
        </div>
    </div>
</div>
</div>
