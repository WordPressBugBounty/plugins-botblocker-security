<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if (!defined('WPINC') || !defined('BOTBLOCKER')) {
    exit;
}

$BBCS = BotBlocker::getInstance();

?><!DOCTYPE html>
<html dir="<?php echo esc_attr('ltr'); ?>" lang="<?php echo esc_attr($BBCS->lang); ?>">

<head>
    <meta charset="utf-8" />
    <meta name="generator" content="BotBlocker v. <?php echo esc_attr($BBCS->version); ?>" />
    <meta name="author" content="BotBlocker project by GLOBUS.studio" />
    <meta name="referrer" content="unsafe-url" />
    <meta name="robots" content="noindex" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="icon" href="data:,">
    <title><?php echo esc_html__('BotBlocker security plugin', 'botblocker-security'); ?></title>
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

    /* Loading Spinner Style */
    .lds-dual-ring,
    .lds-dual-ring:after {
      box-sizing: border-box;
    }

    .lds-dual-ring {
      display: inline-block;
      width: 40px;
      height: 40px;
      color: #333;
    }

    .lds-dual-ring:after {
      content: " ";
      display: block;
      width: 32px;
      height: 32px;
      margin: 8px;
      border-radius: 50%;
      border: 3px solid currentColor;
      border-color: currentColor transparent currentColor transparent;
      animation: lds-dual-ring 1.2s linear infinite;
    }

    @keyframes lds-dual-ring {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }
  </style>
</head>

<body>
  <header class="header">
	<?php
    // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
    // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
    <img src="<?php echo esc_url($BBCS->media_logo_botblocker); ?>" alt="BotBlocker WordPress Plugin" class="logo">
  </header>

  <div class="content">
    <noscript>
      <h1><?php echo esc_html__('Please turn JavaScript on and reload the page', 'botblocker-security'); ?></h1>
    </noscript>

    <div class="lds-dual-ring"></div>
    <br />

    <h2><?php echo esc_html__('Checking your browser before accessing the site', 'botblocker-security'); ?></h2>
    <div class="block1">
      <div class="block2">
        <div class="info" id="content"><?php echo esc_html__('Loading...', 'botblocker-security'); ?></div>
      </div>
    </div>
    <div class="user-data">
      <span class=""><?php echo esc_html__('Connection info:', 'botblocker-security'); ?> <?php echo esc_html($BBCS->ip); ?></span>
      <span class="con-center"><?php echo esc_html__('Connection ID:', 'botblocker-security'); ?> <?php echo esc_html($BBCS->uid . ' ~ ' . $BBCS->cid); ?></span>
    </div>
  </div>

  <footer class="footer">
    <small><a href="https://botblocker.top/" title="BotBlocker plugin for WordPress" target="_blank">Protected by <b>BotBlocker</b> plugin</a></small>
    <small><a href="https://globus.studio" title="Project by GLOBUS.studio" target="_blank">BotBlocker is <b>GLOBUS.studio</b> project</a></small>
    <small><?php echo esc_html(gmdate('Y')); ?></small>
  </footer>

  <script>
    var userip = "<?php echo esc_html($BBCS->ip); ?>";
  </script>

</body><!--TPLJS-->

</html>
