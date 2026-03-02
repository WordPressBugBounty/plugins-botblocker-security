<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BotBlockerWpUser {

    public static function getAvatarPath($user_id)
    { 
        $avatar = get_avatar_url($user_id);
        if (!empty($avatar) && !empty(esc_url($avatar))) {
            return esc_url($avatar);
        } else {
            return BOTBLOCKER_EMPTY;
        }
    }

    public static function getDisplayName($user_id)
    {
        $user = get_userdata($user_id);
        if ($user) {
            return $user->display_name;
        } else {
            return 'Bot Blocker User';
        }
    }

    public static function getUserRole($user_id)
    {
        $user = get_userdata($user_id);
        if ($user) {
            return implode(', ', $user->roles);
        } else {
            return '';
        }
    }
}
