<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

$BBCS = BotBlocker::getInstance();
require_once __DIR__ . '/class-botblocker-captcha-renderer.php';

?>
<!DOCTYPE html>
<html dir="<?php echo esc_attr( 'ltr' ); ?>" lang="<?php echo esc_attr( $BBCS->lang ); ?>">

<head>
	<meta charset="utf-8" />
	<meta name="generator" content="<?php echo esc_attr( sprintf( BotBlockerCaptchaRenderer::t( 'BotBlocker v. %s' ), $BBCS->version ) ); ?>" />
	<meta name="author" content="<?php echo esc_attr( BotBlockerCaptchaRenderer::t( 'BotBlocker project by GLOBUS.studio' ) ); ?>" />
	<meta name="referrer" content="unsafe-url" />
	<meta name="robots" content="noindex" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<link rel="icon" href="data:,">
	<title><?php echo esc_html( BotBlockerCaptchaRenderer::t( 'BotBlocker security plugin' ) ); ?></title>
	<style>
		html,
		body {
			height: 100%;
			margin: 0;
			padding: 0;
			font-family: Arial, sans-serif;
			background-color: #ffffff;
		}

		body {
			display: flex;
			flex-direction: column;
		}

		h1 {
			color: #bd2426;
		}

		h2 {
			text-align: center;
		}

		.header {
			height: 85px;
			background-color: #f0f5f7;
			box-shadow: 0px 3px 7px 3px rgba(0, 0, 0, 0.1);
			display: flex;
			justify-content: center;
			align-items: center;
		}

		.logo {
			height: 65px;
		}

		.content {
			flex-grow: 1;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			padding: 20px;
		}

		.footer {
			height: 50px;
			background-color: #f0f5f7;
			box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
			display: flex;
			justify-content: center;
			align-items: center;
			flex-direction: row;
			flex-wrap: wrap;
			align-content: center;
			font-size: 13px;
		}

		.footer small a {
			text-decoration: none;
			color: #2f2f2f;
			margin: 0 10px;
		}

		.info {
			display: flex;
			text-align: center;
			font-size: 20px;
			flex-direction: column;
			flex-wrap: nowrap;
			align-content: center;
			justify-content: center;
			align-items: center;
			margin: 0;
		}

		.info:empty,
		.info-container:empty {
			display: none;
		}

		.container {
			margin: 20px 0;
		}

		.info-container {
			text-align: center;
		}

		/* Button Styles Fallback */
		.botblocker-btn-success {
			border: 1px solid transparent;
			background: #7785ef;
			color: #ffffff;
			font-size: 16px;
			line-height: 15px;
			padding: 10px 15px;
			text-decoration: none;
			text-shadow: none;
			border-radius: 5px;
			box-shadow: none;
			transition: 0.25s;
			display: block;
			margin: 0 auto;
			font-weight: 600;
		}

		.botblocker-btn-success:hover {
			background-color: #bfc7ff;
		}

		.botblocker-btn-color {
			cursor: pointer;
			padding: 14px 14px;
			text-decoration: none;
			display: inline-block;
			width: 16px;
			height: 16px;
			border-radius: 80px;
		}

		.botblocker-btn-color:hover {
			width: 16px;
			height: 16px;
		}
		/* Button Styles Fallback */

		.block1 {
			display: flex;
			flex-direction: row;
			flex-wrap: wrap;
			align-content: center;
			justify-content: center;
			align-items: center;
		}

		.block2 {
			display: flex;
			align-content: center;
			justify-content: center;
			align-items: center;
			flex-wrap: wrap;
		}

		.user-data {
			display: flex;
			padding-top: 14px;
			font-size: 10px;
			font-weight: 600;
			flex-direction: column;
			flex-wrap: nowrap;
			align-content: center;
			justify-content: center;
			align-items: center;
		}

		.con-center {
			text-align: center;
			padding-top: 3px;
		}

		.info-block {
			font-size: 26px;
			margin: 0;
		}

		.block-string {
			font-weight: normal !important;
		}

		p.info {
			margin: 1em 0;
		}

		.reason-message {
			margin: 0;
			font-size: 20px;
			text-align: center;
		}
	</style>
</head>

<body>
	<header class="header">
		<?php
		// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
    	// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
		<img src="<?php echo esc_url( $BBCS->media_logo_botblocker ); ?>" alt="<?php echo esc_attr( BotBlockerCaptchaRenderer::t( 'BotBlocker WordPress Plugin' ) ); ?>" class="logo">
	</header>

	<div class="content">
		<noscript>
			<h1><?php echo esc_html( BotBlockerCaptchaRenderer::t( 'Please enable JavaScript and reload the page' ) ); ?></h1>
		</noscript>

		<div class="bbcs-icon">
		<?php
		// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
    	// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
		<img src="<?php echo esc_url( $BBCS->media_icon_stop ); ?>" alt="<?php echo esc_attr( BotBlockerCaptchaRenderer::t( 'Stop Visitor Icon' ) ); ?>" class="logo">
		</div>
		<br />

		<div class="container">
			<h1 class="info"><!--denied_message--></h1>
			<p class="info"><!--error_code--></p>
			<div class="info info-container"><!--ip_ban_msg--></div>
			<p class="reason-message"><!--reason_message--></p>
		</div>

		<div class="user-data">
			<span class=""><?php echo esc_html( BotBlockerCaptchaRenderer::t( 'Connection info:' ) ); ?> <?php echo esc_html( $BBCS->ip ); ?></span>
			<span class="con-center"><?php echo esc_html( BotBlockerCaptchaRenderer::t( 'Connection ID:' ) ); ?> <?php echo esc_html( $BBCS->uid . ' ~ ' . $BBCS->cid ); ?></span>
		</div>
	</div>

	<footer class="footer">
		<?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
		<small><a href="https://botblocker.top/" title="BotBlocker plugin for WordPress" target="_blank"><?php echo BotBlockerCaptchaRenderer::t( 'Protected by <b>BotBlocker</b> plugin' ); ?></a></small>
		<small><a href="https://globus.studio" title="Project by GLOBUS.studio" target="_blank"><?php echo BotBlockerCaptchaRenderer::t( 'BotBlocker is <b>GLOBUS.studio</b> project' ); ?></a></small>
		<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		//TODO PRO version motto
		/* if (isset($BBCS->pro_motto)) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
			echo $BBCS->pro_motto;
		}*/
		?>
		<small><?php echo esc_html( gmdate( 'Y' ) ); ?></small>
	</footer>

	<script>
		var userip = "<?php echo esc_html( $BBCS->ip ); ?>";
	</script>
</body>

</html>
