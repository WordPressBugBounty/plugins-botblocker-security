<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( array $data ): void {
	$dashboard_url  = $data['dashboard_url'];
	$settings_url  = $data['settings_url'];
	$reset_nonce    = $data['reset_nonce'];
	$ajax_url       = $data['ajax_url'];

	$icons_file = BOTBLOCKER_DIR . 'admin/templates/shared/icons-sprite.php';
	$icons = require $icons_file;
	ob_start();
	$icons();
	$sprite = ob_get_clean();
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php esc_html_e( 'BotBlocker Security - Setup completed', 'botblocker-security' ); ?></title>
		<?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- standalone page, cannot use wp_enqueue_style() ?>
		<link rel="preconnect" href="https://fonts.googleapis.com" />
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
		<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
		<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', BOTBLOCKER_VERSION, BOTBLOCKER_URL . 'admin/css/bbcs-tokens.css' ) ); ?>">
		<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', BOTBLOCKER_VERSION, BOTBLOCKER_URL . 'admin/css/bbcs.css' ) ); ?>">
		<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', BOTBLOCKER_VERSION, BOTBLOCKER_URL . 'admin/css/bbcs-setup-wizard-new.css' ) ); ?>">
		<?php // phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
	</head>
	<body class="bbcs-app" style="align-items:center;justify-content:center;background:var(--bbcs-bg)">
		<?php echo $sprite; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="bbcs-wiz" style="margin:0">
			<div class="bbcs-wizsuccess">
				<div class="bbcs-wizsuccess-ic">
					<svg class="bbcs-ico"><use href="#bbcs-i-check"></use></svg>
				</div>
				<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Setup completed', 'botblocker-security' ); ?></h2>
				<p class="bbcs-wizsub"><?php esc_html_e( 'All good! BotBlocker is configured and protecting your site.', 'botblocker-security' ); ?></p>
			</div>

			<div class="bbcs-wizcta">
				<a href="<?php echo esc_url( $dashboard_url ); ?>" class="bbcs-btn bbcs-btn--pri bbcs-btn--lg">
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-home"></use></svg>
					<?php esc_html_e( 'Go to Dashboard', 'botblocker-security' ); ?>
				</a>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="bbcs-btn bbcs-btn--lg">
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg>
					<?php esc_html_e( 'Settings', 'botblocker-security' ); ?>
				</a>
				<button type="button" class="bbcs-btn bbcs-btn--ghost bbcs-btn--lg" id="bbcs-wiz-reset-btn">
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-refresh"></use></svg>
					<?php esc_html_e( 'Reset and Re-run Wizard', 'botblocker-security' ); ?>
				</button>
			</div>
		</div>

		<script>
		(function(){
			var btn = document.getElementById('bbcs-wiz-reset-btn');
			if (!btn) return;
			btn.addEventListener('click', function(){
				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to reset and restart the setup wizard?', 'botblocker-security' ) ); ?>')) return;
				btn.disabled = true;
				btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> <?php echo esc_js( __( 'Resetting...', 'botblocker-security' ) ); ?>';
				var data = new URLSearchParams();
				data.append('action', 'bbcs_wizard_reset');
				data.append('nonce', '<?php echo esc_js( $reset_nonce ); ?>');
				fetch('<?php echo esc_url( $ajax_url ); ?>', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: data
				}).then(function(r){ return r.json(); }).then(function(){
					try { localStorage.removeItem('bbcs_wizard_progress'); localStorage.removeItem('bbcs_wizard_contact_email'); } catch(e){}
					window.location.reload();
				}).catch(function(){
					window.location.reload();
				});
			});
		})();
		</script>
	</body>
	</html>
	<?php
};
