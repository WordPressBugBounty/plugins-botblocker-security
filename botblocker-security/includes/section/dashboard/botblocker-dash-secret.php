<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><section class="card">
<header class="card-header">
    <div class="card-actions">
        <a id="bbcs-send-email" href="#" data-bs-toggle="tooltip" data-bs-html="true"
            data-bs-placement="top"
            title="<?php esc_attr_e('Send email with BotBlocker management links', 'botblocker-security'); ?>">
            <i class="fa-regular fa-envelope bbcs-h-btn-gray"></i>
        </a>
    </div>
    <h2 class="card-title"><?php esc_html_e('Security action links', 'botblocker-security'); ?></h2>
</header>
<div class="card-body">
    <div class="bbcs_text_input mb-2">
        <div class="bbcs_label_input_box">
            <span
                class="bbcs-label-input-small"><?php esc_html_e('Secret link to disable the current BotBlocker check', 'botblocker-security'); ?></span>
            <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                data-bs-placement="top"
                title="<?php esc_attr_e('Use this link to temporarily disable BotBlocker checks for the current session.', 'botblocker-security'); ?>">
            </i>
        </div>
        <div class="bbcs_text_input_inner" style="position: relative;">
            <input type="text" class="bbcs_text_input_input bbcs_small_input" name="slinkd"
                value="<?php echo esc_url(bbcs_getDisableURL()); ?>" readonly>
            <button class="bbcs_copy_button" onclick="copyToClipboard(this)">
                <i class="fa-regular fa-copy" data-bs-toggle="tooltip"
                    title="<?php esc_attr_e('Copy to clipboard', 'botblocker-security'); ?>"></i>
            </button>
        </div> 
    </div>
    <div class="bbcs_text_input mb-2">
        <div class="bbcs_label_input_box">
            <span
                class="bbcs-label-input-small"><?php esc_html_e('Secret link to fully disable BotBlocker', 'botblocker-security'); ?></span>
            <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                data-bs-placement="top"
                title="<?php esc_attr_e('Use this link to completely disable BotBlocker.', 'botblocker-security'); ?>">
            </i>
        </div>
        <div class="bbcs_text_input_inner" style="position: relative;">
            <input type="text" class="bbcs_text_input_input bbcs_small_input" name="slinkf"
                value="<?php echo esc_url(bbcs_getOffURL()); ?>" readonly>
            <button class="bbcs_copy_button" onclick="copyToClipboard(this)">
                <i class="fa-regular fa-copy" data-bs-toggle="tooltip"
                    title="<?php esc_attr_e('Copy to clipboard', 'botblocker-security'); ?>"></i>
            </button>
        </div>
    </div>
    <div class="bbcs_text_input mb-2">
        <div class="bbcs_label_input_box">
            <span
                class="bbcs-label-input-small"><?php esc_html_e('Secret link to re-enable BotBlocker', 'botblocker-security') ?></span>
            <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                data-bs-placement="top"
                title="<?php esc_attr_e('Use this link to re-enable BotBlocker.', 'botblocker-security'); ?>">
            </i>
        </div>
        <div class="bbcs_text_input_inner" style="position: relative;">
            <input type="text" class="bbcs_text_input_input bbcs_small_input" name="slinko"
                value="<?php echo esc_url(bbcs_getOnURL()); ?>" readonly>
            <button class="bbcs_copy_button" onclick="copyToClipboard(this)">
                <i class="fa-regular fa-copy" data-bs-toggle="tooltip"
                    title="<?php esc_attr_e('Copy to clipboard', 'botblocker-security'); ?>"></i>
            </button>
        </div>
    </div>
</div>
</section>