<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$BBCS = BotBlocker::getInstance();
?><!DOCTYPE html>
<html dir="<?php esc_html_e('ltr', 'botblocker-security'); ?>" lang="<?php echo esc_html($BBCS->lang); ?>">

<head> 
    <meta charset="utf-8" />
    <meta name="generator" content="BotBlocker v. <?php echo esc_html($BBCS->version); ?>" />
    <meta name="author" content="BotBlocker project by GLOBUS.studio" />
    <meta name="referrer" content="unsafe-url" />
    <meta name="robots" content="noindex" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="icon" href="data:,">
    <title><?php echo esc_html($plugin_name); ?></title>
    <?php 
    wp_print_styles();
    wp_print_head_scripts(); 
?>
</head>

<body>
  <header class="header">
    <img src="<?php echo esc_url($BBCS->media_logo_botblocker); ?>" alt="BotBlocker Wordpress Plugin" class="logo">
  </header>

  <div class="content"> 
    <noscript>
      <h1><?php echo esc_html($h1_title); ?></h1>
    </noscript>

    <div class="lds-dual-ring"></div>
    <br />

    <h2><?php echo esc_html($h2_title); ?></h2>
    <div class="block1">
      <div class="block2">
        <div class="info" id="content">Loading...</div> 
      </div>
    </div>
    <div class="user-data">
      <span class="">Connection info: <?php echo esc_html($extra_data); ?></span>
      <span class="con-center">Connection ID: <?php echo esc_html($BBCS->uid . ' ~ ' . $BBCS->cid); ?></span>
    </div>
  </div>

  <footer class="footer">
    <small><a href="https://botblocker.top/" title="BotBlocker plugin for Wordpress" target="_blank">Protected by <b>BotBlocker</b> plugin</a></small>
    <small><a href="https://globus.studio" title="Project by GLOBUS.studio" target="_blank">BotBlocker is <b>GLOBUS.studio</b> project</a></small>
    <small><?php echo esc_html(gmdate('Y')); ?></small>
  </footer>

<?php 
  wp_print_footer_scripts();
?>
</body>
</html>