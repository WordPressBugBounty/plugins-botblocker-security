<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="modal fade" id="dbRepairInfoModal" tabindex="-1" aria-labelledby="dbRepairInfoModalLabel" aria-hidden="true">
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="dbRepairInfoModalLabel"><?php esc_html_e('Database Repair and Optimization', 'botblocker-security'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <p class="bbcs-black"><strong><?php esc_html_e('To enable database repair and optimization:', 'botblocker-security'); ?></strong></p>
            <ol>
                <li><?php esc_html_e('Open the file', 'botblocker-security'); ?> <code><?php echo('wp-config.php'); ?></code> <?php esc_html_e('in the root of your site', 'botblocker-security'); ?></li>
                <li><?php esc_html_e('Add the following line before', 'botblocker-security'); ?> <code><?php echo('/* That\'s all, stop editing! Happy publishing. */'); ?></code>:<br>
                <code><?php echo('define(\'WP_ALLOW_REPAIR\', true);'); ?></code></li>
                <li><?php esc_html_e('Save the file', 'botblocker-security'); ?></li>
                <li><?php esc_html_e('Follow the link:', 'botblocker-security'); ?> <a href="<?php echo esc_url(admin_url('maint/repair.php')); ?>" 
                target="_blank">
                <?php echo esc_url(admin_url('maint/repair.php')); ?></a></li>
                <li><?php esc_html_e('After the repair is completed,', 'botblocker-security'); ?> <strong><?php esc_html_e('delete', 'botblocker-security'); ?></strong> <?php esc_html_e('the added line from wp-config.php.', 'botblocker-security'); ?></li>
            </ol>
            <div class="alert alert-warning">
                <strong><?php esc_html_e('Attention!', 'botblocker-security'); ?></strong> <?php esc_html_e('The repair page is publicly accessible without authentication while enabled. Disable it after use.', 'botblocker-security'); ?>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e('Close', 'botblocker-security'); ?></button>
        </div>
    </div>
</div>
</div>
