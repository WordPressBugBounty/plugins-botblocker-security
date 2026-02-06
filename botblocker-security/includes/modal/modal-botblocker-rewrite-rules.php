<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="modal fade" id="confirmRewriteRulesModal" tabindex="-1" aria-labelledby="confirmRewriteRulesModalLabel" aria-hidden="true">
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="confirmRewriteRulesModalLabel"><?php esc_html_e('Confirmation of resetting rewrite rules', 'botblocker-security'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <p class="bbcs-black"><strong><?php esc_html_e('Attention!', 'botblocker-security'); ?></strong> <?php esc_html_e('You are about to reset the URL rewrite rules:', 'botblocker-security'); ?></p>
            <ul class="bbcs-black bbcs-modal-ul">
                <li class="bbcs-modal-li"><?php esc_html_e('Resetting will help fix 404 errors after changing the permalink structure.', 'botblocker-security'); ?></li>
                <li class="bbcs-modal-li"><?php esc_html_e('All rules will be automatically regenerated.', 'botblocker-security'); ?></li>
                <li class="bbcs-modal-li"><?php esc_html_e('A page reload may be required after the operation.', 'botblocker-security'); ?></li>
            </ul>
            <p class="bbcs-black"><?php esc_html_e('Continue resetting the rewrite rules?', 'botblocker-security'); ?></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'botblocker-security'); ?></button>
            <button type="button" id="confirmRewriteRulesButton" class="btn btn-primary btn-xs"><?php esc_html_e('Reset the rules', 'botblocker-security'); ?></button>
        </div>
    </div>
</div>
</div>
