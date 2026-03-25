<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="modal fade" id="confirmTransientClearModal" tabindex="-1" aria-labelledby="confirmTransientClearModalLabel" aria-hidden="true">
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="confirmTransientClearModalLabel"><?php esc_html_e('Clear Transient Data', 'botblocker-security'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <p class="bbcs-black"><strong><?php esc_html_e('Attention!', 'botblocker-security'); ?></strong> <?php esc_html_e('All WordPress transients will be cleared:', 'botblocker-security'); ?></p>
            <ul class="bbcs-black bbcs-modal-ul">
                <li class="bbcs-modal-li"><?php esc_html_e('Transients are temporary database data used by plugins and themes.', 'botblocker-security'); ?></li>
                <li class="bbcs-modal-li"><?php esc_html_e('The site may be slower temporarily until data is re-cached.', 'botblocker-security'); ?></li>
                <li class="bbcs-modal-li"><?php esc_html_e('Does not affect core site data.', 'botblocker-security'); ?></li>
            </ul>
            <p class="bbcs-black"><?php esc_html_e('Continue clearing transients?', 'botblocker-security'); ?></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'botblocker-security'); ?></button>
            <button type="button" id="confirmTransientClearButton" class="btn btn-primary btn-xs"><?php esc_html_e('Clear transients', 'botblocker-security'); ?></button>
        </div>
    </div>
</div>
</div>
