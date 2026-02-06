<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_get_email($user_id)
{
    $user = get_userdata($user_id);
    if ($user) {
        return $user->user_email;
    } else {
        return '';
    }
}

function bbcs_getDisableURL()
{
    $BBCS = BotBlocker::getInstance();
    return BOTBLOCKER_SITE_URL . '/?' . $BBCS->settings->secret_botblocker_get_param . '=' . $BBCS->action_disable;
}

function bbcs_getOffURL()
{
    $BBCS = BotBlocker::getInstance();
    return BOTBLOCKER_SITE_URL . '/?' . $BBCS->settings->secret_botblocker_get_param . '=' . $BBCS->action_off;
}

function bbcs_getOnURL()
{
    $BBCS = BotBlocker::getInstance();
    return BOTBLOCKER_SITE_URL . '/?' . $BBCS->settings->secret_botblocker_get_param . '=' . $BBCS->action_on;
}

function bbcs_sendAdminLinksEmail()
{
    $admin_email = bbcs_getCloudAPIEmail();
    if (empty($admin_email)) {
        return false;
    }

    $disable_url = bbcs_getDisableURL();
    $off_url = bbcs_getOffURL();
    $on_url = bbcs_getOnURL();

    $subject = 'BotBlocker Management Links';

    $message = "
        <html>
        <head>
            <title>Manage BotBlocker Settings</title>
        </head>
        <body>
            <p>Hello,</p>
            <p>Below are the links to manage the BotBlocker settings on your site:</p>
            <ul>
                <li><b>Temporary Disable:</b> You can temporarily disable BotBlocker for the current request. Add this parameter to any site URL: <br>
                    <a href='{$disable_url}'>{$disable_url}</a></li>
                <li><b>Turn Off BotBlocker:</b> Use this link to permanently disable BotBlocker: <br>
                    <a href='{$off_url}'>{$off_url}</a></li>
                <li><b>Turn On BotBlocker:</b> Use this link to enable BotBlocker again: <br>
                    <a href='{$on_url}'>{$on_url}</a></li>
            </ul>
            <p>Best regards,</p>
            <p>BotBlocker Team</p>
        </body>
        </html>
    ";

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: BotBlocker <no-reply@' . wp_parse_url(BOTBLOCKER_SITE_URL, PHP_URL_HOST) . '>'
    ];

    return wp_mail($admin_email, $subject, $message, $headers);
}

function bbcs_send_expiration_email($message, $expired = false) {
    $admin_email = bbcs_getCloudAPIEmail();
    if (empty($admin_email)) {
        return false;
    }

    $subject = $expired ? 'Your BotBlocker Cloud API connection has Expired' : 'Your BotBlocker Cloud API connection is Expiring Soon';
    $message = "
        <html>
        <head>
            <title>BotBlocker Cloud API Connection Expiration Notice</title>
        </head>
        <body>
            <p>Hello,</p>
            <p>{$message}</p>
            <p>Best regards,</p>
            <p>BotBlocker Team</p>
        </body>
        </html>
    ";

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: BotBlocker <no-reply@' . wp_parse_url(BOTBLOCKER_SITE_URL, PHP_URL_HOST) . '>'
    ];

    return wp_mail($admin_email, $subject, $message, $headers);
}
