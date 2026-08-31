<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (): void {
	$reasons = array(
		'temporary'          => __( 'Temporary - I will turn it back on later', 'botblocker-security' ),
		'too_complex'        => __( 'Too complex or hard to configure', 'botblocker-security' ),
		'performance'        => __( 'Site performance issues', 'botblocker-security' ),
		'false_positives'    => __( 'Blocked legitimate traffic (false positives)', 'botblocker-security' ),
		'better_alternative' => __( 'Found a better alternative', 'botblocker-security' ),
		'missing_feature'    => __( 'Missing a feature I need', 'botblocker-security' ),
		'technical_issue'    => __( 'Technical issues or bugs', 'botblocker-security' ),
		'other'              => __( 'Other', 'botblocker-security' ),
	);
	?>
	<div class="bbcs-modal-overlay" id="bbcsDeactivationFeedbackModal" style="display:none;">
		<div class="bbcs-modal">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title"><?php esc_html_e( 'Before you go', 'botblocker-security' ); ?></div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<p><?php esc_html_e( 'Help us improve BotBlocker. Why are you deactivating the plugin?', 'botblocker-security' ); ?></p>
				<div class="bbcs-col bbcs-g-2" id="bbcs-deactivation-reasons">
					<?php foreach ( $reasons as $key => $label ) : ?>
						<label class="bbcs-row bbcs-g-2 bbcs-align-center">
							<input type="radio" name="bbcs_deactivation_reason" value="<?php echo esc_attr( $key ); ?>" />
							<span><?php echo esc_html( $label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<div class="bbcs-col bbcs-g-1 bbcs-mt-3">
					<label for="bbcs-deactivation-details"><?php esc_html_e( 'Additional details (optional)', 'botblocker-security' ); ?></label>
					<textarea id="bbcs-deactivation-details" class="bbcs-input" rows="3" maxlength="2000"></textarea>
				</div>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn bbcs-btn--ghost" id="bbcs-deactivation-skip"><?php esc_html_e( 'Skip and deactivate', 'botblocker-security' ); ?></button>
				<button type="button" class="bbcs-btn bbcs-btn--danger" id="bbcs-deactivation-submit"><?php esc_html_e( 'Send and deactivate', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
	<?php
};
