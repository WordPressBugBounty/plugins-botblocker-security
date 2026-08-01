<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_AddonsViewModel $data): void {
	?>
	<div class="bbcs-card bbcs-card-pad bbcs-mb-5h" id="bbcs-upload-section" hidden>
		<div class="bbcs-section-title bbcs-mb-3"><?php esc_html_e('Install addon from ZIP', 'botblocker-security'); ?></div>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="bbcs-row bbcs-g-3">
			<input type="hidden" name="action" value="bbcs_upload_addon">
			<?php wp_nonce_field('bbcs_upload_addon', 'bbcs_upload_addon_nonce'); ?>
			<label class="bbcs-btn bbcs-btn--pri" for="bbcs_addon_zip" id="bbcs-upload-label">
				<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-upload"></use></svg>
				<?php esc_html_e('Choose ZIP', 'botblocker-security'); ?>
			</label>
			<input type="file" accept=".zip,application/zip" id="bbcs_addon_zip" name="bbcs_addon_zip" style="display:none">
			<span class="bbcs-dim bbcs-fs-sm" id="bbcs-zip-name"><?php esc_html_e('No file selected', 'botblocker-security'); ?></span>
			<button type="submit" id="bbcs-install-package-btn" class="bbcs-btn bbcs-btn--pri" disabled><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-check"></use></svg><?php esc_html_e('Install package', 'botblocker-security'); ?></button>
		</form>
	</div>
	<?php
};
