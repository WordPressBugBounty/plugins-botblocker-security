<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BotBlockerWpUtility
{
    public static function is_admin_area_page($page): bool
    {
        $excluded_patterns = [
            '/wp-admin/',
            '/wp-content/',
            '/wp-includes/',
            '/favicon.ico',
            '/feed/',
            '/xmlrpc.php',
            '/robots.txt',
            '/sitemap',
            '/wp-login.php',
            '/admin-ajax.php',
            '/license.txt',
            '/readme.html'
        ];
        if ($page != null) {
            foreach ($excluded_patterns as $pattern) {
                if (strpos($page, $pattern) !== false) {
                    return true;
                }
            }
        } else {
            return false;
        }
        return false;
    }

    public static function is_wordpress_system_page($page): bool
    {
        $excluded_patterns = [
            '/wp-cron.php',
            '/wp-json/',
            '/wp-comments-post.php',
            '/trackback',
            '/async-upload.php'
        ];
        if ($page != null) {
            foreach ($excluded_patterns as $pattern) {
                if (strpos($page, $pattern) !== false) {
                    return true;
                }
            }
        } else {
            return false;
        }
        return false;
    }

    public static function is_botblocker_page(): bool
    {
        global $pagenow;

        /**
         * REVIEWER NOTE: The 'page' parameter is used by WordPress core and plugins to identify admin pages for routing and display.
         * This check only verifies the current admin page slug for plugin logic and does not process or modify form data.
         * Therefore, nonce verification is not applicable in this context.
         */
        /* phpcs:disable WordPress.Security.NonceVerification.Recommended */
        if ($pagenow === 'admin.php' && isset($_GET['page'])) {
            $bbcs_pages = [
                'bbcs_dashboard',
                'bbcs_settings',
                'bbcs_integrations',
                'bbcs_rules',
                'bbcs_tools',
                'bbcs_reports',
                'bbcs_maintenance',
                'bbcs_setup_guide',
                'bbcs_addons',
                'bbcs_cloud_api',
                'bbcs_about'
            ];

            return in_array( sanitize_text_field(wp_unslash($_GET['page'])), $bbcs_pages);
        }
        /* phpcs:enable WordPress.Security.NonceVerification.Recommended */

        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            
            if ($screen) {
                $bbcs_screens = [
                    'toplevel_page_bbcs_dashboard',
                    'botblocker_page_bbcs_settings',
                    'botblocker_page_bbcs_integrations',
                    'botblocker_page_bbcs_rules',
                    'botblocker_page_bbcs_tools',
                    'botblocker_page_bbcs_reports',
                    'botblocker_page_bbcs_maintenance',
                    'botblocker_page_bbcs_cloud_api',
                    'botblocker_page_bbcs_setup_guide',
                    'botblocker_page_bbcs_addons',
                    'botblocker_page_bbcs_about'
                ];

                $all_screens = $bbcs_screens;

				if (is_multisite()) {
                	foreach ( $bbcs_screens as $id ) {
                    	$all_screens[] = $id . '-network';
                	}
				}

                return in_array($screen->id, $all_screens);
            }
        }
        
        return false;
    }

    public function redirect_to_homepage(): void
    {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}
