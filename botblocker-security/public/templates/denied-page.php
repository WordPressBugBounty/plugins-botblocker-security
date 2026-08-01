<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$BBCS = BotBlocker::getInstance();
require_once __DIR__ . '/../class-botblocker-captcha-renderer.php';
?><!DOCTYPE html>
<html dir="<?php echo esc_attr( BotBlockerCaptchaRenderer::t( 'ltr' ) ); ?>" lang="<?php echo esc_html( $BBCS->lang ); ?>">

<head>
	<meta charset="utf-8" />
	<meta name="generator" content="<?php echo esc_attr( sprintf( BotBlockerCaptchaRenderer::t( 'BotBlocker v. %s' ), $BBCS->version ) ); ?>" />
	<meta name="author" content="<?php echo esc_attr( BotBlockerCaptchaRenderer::t( 'BotBlocker project by GLOBUS.studio' ) ); ?>" />
	<meta name="referrer" content="unsafe-url" />
	<meta name="robots" content="noindex" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<link rel="icon" href="data:,">
	<title><?php echo esc_html( $data['denied_title'] ); ?></title>
	<?php foreach ( $data['inline_assets']['styles'] as $bbcs_inline_style ) : ?>
		<?php if ( $bbcs_inline_style !== '' ) : ?>
	<style>
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
			echo wp_strip_all_tags( $bbcs_inline_style );
			?>
	</style>
	<?php endif; ?>
	<?php endforeach; ?>
</head>

<body>
	<header class="header">
	<img src="<?php echo esc_url( $BBCS->media_logo_botblocker ); ?>" alt="<?php echo esc_attr( BotBlockerCaptchaRenderer::t( 'BotBlocker WordPress Plugin' ) ); ?>" class="logo">
	</header>

	<div class="content">
	<noscript>
		<h1><?php echo esc_html( $data['h1_title'] ); ?></h1>
	</noscript>
	<div class="bbcs-icon">
		<img src="<?php echo esc_url( $BBCS->media_icon_stop ); ?>" alt="<?php echo esc_attr( BotBlockerCaptchaRenderer::t( 'Stop Visitor Icon' ) ); ?>" class="logo">
	</div>
	<br>

	<div class="container">
		<h1 class="info"><?php echo esc_html( $data['message'] ); ?></h1>
		<p class="info"><?php echo esc_html( BotBlockerCaptchaRenderer::t( 'Error Code:' ) ); ?> <?php echo esc_html( $BBCS->error_headers[ $BBCS->settings->header_error_code ] ); ?></p>
		<p class="info">
		<?php echo esc_html( $data['denied_data'] ); ?>
		</p>
	</div>

	<div class="user-data">
		<span class=""><?php echo esc_html( BotBlockerCaptchaRenderer::t( 'Connection info:' ) ); ?> <?php echo esc_html( $data['extra_data'] ); ?></span>
		<span class="con-center"><?php echo esc_html( BotBlockerCaptchaRenderer::t( 'Connection ID:' ) ); ?> <?php echo esc_html( $BBCS->uid . ' ~ ' . $BBCS->cid ); ?></span>
	</div>
	</div>

	<footer class="footer">
	<?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
	<small><a href="https://botblocker.top/" title="BotBlocker plugin for WordPress" target="_blank"><?php echo BotBlockerCaptchaRenderer::t( 'Protected by <b>BotBlocker</b> plugin' ); ?></a></small>
	<small><a href="https://globus.studio" title="Project by GLOBUS.studio" target="_blank"><?php echo BotBlockerCaptchaRenderer::t( 'BotBlocker is <b>GLOBUS.studio</b> project' ); ?></a></small>
	<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<small><?php echo esc_html( gmdate( 'Y' ) ); ?></small>
	</footer>

<?php foreach ( $data['inline_assets']['external_scripts'] as $bbcs_external_script ) : ?>
	<?php if ( $bbcs_external_script !== '' ) : ?>
	<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
<script src="<?php echo esc_url( $bbcs_external_script ); ?>"></script>
	<?php endif; ?>
<?php endforeach; ?>
<?php foreach ( $data['inline_assets']['scripts'] as $bbcs_inline_script ) : ?>
	<?php if ( $bbcs_inline_script !== '' ) : ?>
<script>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
		echo $bbcs_inline_script;
		?>
</script>
	<?php endif; ?>
<?php endforeach; ?>
</body>

</html>
