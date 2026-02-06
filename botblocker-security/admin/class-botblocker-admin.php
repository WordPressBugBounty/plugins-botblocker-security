<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (defined('BOTBLOCKER_WIDGETS') && BOTBLOCKER_WIDGETS) {
    include('partials/botblocker-admin-dashboard-widgets.php');
}

require_once BOTBLOCKER_DIR . 'admin/class-botblocker-admin-settings.php';

class Botblocker_Admin
{
    use BotBlockerAdminSettingsTrait;

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->register_admin_bar();
    }
    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        global $pagenow;
        $screen = get_current_screen();

        if ($pagenow === 'admin.php' && in_array($screen->id, [
            'toplevel_page_bbcs_dashboard',
            'botblocker_page_bbcs_settings',
            'botblocker_page_bbcs_integrations',
            'botblocker_page_bbcs_rules',
            'botblocker_page_bbcs_tools',
            'botblocker_page_bbcs_reports',
            'botblocker_page_bbcs_cloud_api',
            'botblocker_page_bbcs_setup_guide',
            'botblocker_page_bbcs_about',
            'botblocker_page_bbcs_addons'
        ])) {
            wp_enqueue_style('google-fonts-poppins', 'https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap', [], BOTBLOCKER_VERSION);
            wp_enqueue_style(BOTBLOCKER_SHORT_NAME . '-bootstrap', plugin_dir_url(__FILE__) . 'css/bootstrap/bootstrap.min.css', array(), BOTBLOCKER_VERSION, 'all');
            wp_enqueue_style(BOTBLOCKER_SHORT_NAME . '-theme', plugin_dir_url(__FILE__) . 'css/theme.css', array(), BOTBLOCKER_VERSION, 'all');
            wp_enqueue_style(BOTBLOCKER_SHORT_NAME . '-default', plugin_dir_url(__FILE__) . 'css/default.css', array(), BOTBLOCKER_VERSION, 'all');
            wp_enqueue_style(BOTBLOCKER_SHORT_NAME . '-fa', plugin_dir_url(__FILE__) . 'css/all.min.css', array(), BOTBLOCKER_VERSION, 'all');
            wp_enqueue_style(BOTBLOCKER_SHORT_NAME . '-flags', plugin_dir_url(__FILE__) . 'css/flags/flags.css', array(), BOTBLOCKER_VERSION, 'all');
            wp_enqueue_style(BOTBLOCKER_SHORT_NAME . '-datatables', plugin_dir_url(__FILE__) . 'css/datatables/datatables.min.css', array(), BOTBLOCKER_VERSION, 'all');
            wp_enqueue_style(BOTBLOCKER_SHORT_NAME . '-admin', plugin_dir_url(__FILE__) . 'css/botblocker-admin.css', array(), BOTBLOCKER_VERSION, 'all');
            wp_enqueue_style(BOTBLOCKER_SHORT_NAME . '-admin-mobile', plugin_dir_url(__FILE__) . 'css/botblocker-admin-mobile.css', array(), BOTBLOCKER_VERSION, 'all');
        }
        if ($screen->id === 'toplevel_page_bbcs_dashboard') {
            wp_enqueue_style(BOTBLOCKER_SHORT_NAME . '-jsvectormap', plugin_dir_url(__FILE__) . 'css/jsvectormap/jsvectormap.min.css', [], BOTBLOCKER_VERSION);
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        global $pagenow;
        $screen = get_current_screen();
        $locale = determine_locale();

        if ($pagenow === 'admin.php' && in_array($screen->id, [
            'toplevel_page_bbcs_dashboard',
            'botblocker_page_bbcs_settings',
            'botblocker_page_bbcs_integrations',
            'botblocker_page_bbcs_reports',
            'botblocker_page_bbcs_rules',
            'botblocker_page_bbcs_tools',
            'botblocker_page_bbcs_cloud_api',
            'botblocker_page_bbcs_setup_guide',
            'botblocker_page_bbcs_about',
            'botblocker_page_bbcs_addons'
        ])) {
            wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-modernizr', plugin_dir_url(__FILE__) . 'js/modernizr/modernizr.min.js', array('jquery'), BOTBLOCKER_VERSION, false);
            wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-bootstrap-js', plugin_dir_url(__FILE__) . 'js/bootstrap/bootstrap.bundle.min.js', array('jquery'), BOTBLOCKER_VERSION, false);
            wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-chartjs', plugin_dir_url(__FILE__) . 'js/chartjs/chart.umd.js', array(), BOTBLOCKER_VERSION, false);
            wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-datatables-js', plugin_dir_url(__FILE__) . 'js/datatables/datatables.min.js', array('jquery'), BOTBLOCKER_VERSION, false);
            wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-common-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-common.js', array('jquery'), BOTBLOCKER_VERSION, true);
            wp_localize_script(BOTBLOCKER_SHORT_NAME . '-common-js', 'botblockerData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce("botblocker_nonce"),
            ));
            wp_localize_script(BOTBLOCKER_SHORT_NAME . '-common-js', 'botblockerRedisMemcachedAvailability', array(
                'redisAvailable' => bbcs_checkRedisAvailability(),
                'memcachedAvailable' => bbcs_checkMemcachedAvailability()
            ));
            wp_localize_script(BOTBLOCKER_SHORT_NAME . '-common-js', 'botblockerCurrentLocale', array(
                'locale' => self::$instance->check_translate_for_this_locale($locale)
            ));

            if ($screen->id === 'toplevel_page_bbcs_dashboard' || $screen->id === 'botblocker_page_bbcs_setup_guide') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-health-gauge-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-health-gauge.js', array('jquery', BOTBLOCKER_SHORT_NAME . '-chartjs'), BOTBLOCKER_VERSION, true);
            }

            if ($screen->id === 'botblocker_page_bbcs_reports') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-hits-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-hits.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_localize_script(BOTBLOCKER_SHORT_NAME . '-hits-js', 'botblockerData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce("botblocker_nonce")
                ));
            }

            if ($screen->id === 'botblocker_page_bbcs_rules') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-rules-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-rules.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-rules-ipv4-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-rules-ipv4.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-rules-ipv6-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-rules-ipv6.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-rules-white-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-white.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-rules-path-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-path.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-proxy-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-proxy.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-chart-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-charts.js', array('jquery'), BOTBLOCKER_VERSION, false);

                wp_localize_script(BOTBLOCKER_SHORT_NAME . '-rules-js', 'botblockerData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce("botblocker_nonce")
                ));
                wp_localize_script(BOTBLOCKER_SHORT_NAME . '-rules-ipv4-js', 'botblockerData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce("botblocker_nonce")
                ));
                wp_localize_script(BOTBLOCKER_SHORT_NAME . '-rules-ipv6-js', 'botblockerData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce("botblocker_nonce")
                ));
                wp_localize_script(BOTBLOCKER_SHORT_NAME . '-rules-white-js', 'botblockerData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce("botblocker_nonce")
                ));
                wp_localize_script(BOTBLOCKER_SHORT_NAME . '-rules-path-js', 'botblockerData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce("botblocker_nonce")
                ));
            }

            if ($screen->id === 'botblocker_page_bbcs_cloud_api') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-cloud-api-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-cloud-api.js', array('jquery'), BOTBLOCKER_VERSION, true);
            }

            if ($screen->id === 'botblocker_page_bbcs_setup_guide') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-chart-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-charts.js', array('jquery'), BOTBLOCKER_VERSION, false);
            }

            if ($screen->id === 'botblocker_page_bbcs_integrations') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-integrations-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-integrations.js', array('jquery'), BOTBLOCKER_VERSION, true);                
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-2fa-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-2fa.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_localize_script(BOTBLOCKER_SHORT_NAME . '-2fa-js', 'botblockerData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce("botblocker_nonce")
                ));
            }

            if ($screen->id === 'botblocker_page_bbcs_settings') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-settings-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-settings.js', array('jquery'), BOTBLOCKER_VERSION, true);
            }

            if ($screen->id === 'botblocker_page_bbcs_tools') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-tools-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-tools.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-maintenance-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-maintenance.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_localize_script(BOTBLOCKER_SHORT_NAME . '-maintenance-js', 'botblockerData', array(
                    'adminUrl' => admin_url(),
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce("botblocker_nonce")
                ));
            }

            if ($screen->id === 'botblocker_page_bbcs_addons') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-addons-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-addons.js', array('jquery'), BOTBLOCKER_VERSION, true);
            }

            if ($screen->id === 'toplevel_page_bbcs_dashboard') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-jsvectormap', plugin_dir_url(__FILE__) . 'js/jsvectormap/jsvectormap.js', [], BOTBLOCKER_VERSION, false);
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-jsvectormap-world', plugin_dir_url(__FILE__) . 'js/jsvectormap/maps/world.js', [BOTBLOCKER_SHORT_NAME . '-jsvectormap'], BOTBLOCKER_VERSION, false);

                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-chart-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-charts.js', array('jquery'), BOTBLOCKER_VERSION, false);

                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-dash-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-dashboard.js', array('jquery'), BOTBLOCKER_VERSION, true);
                wp_localize_script(BOTBLOCKER_SHORT_NAME . '-dash-js', 'botblockerData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce("botblocker_nonce")
                ));
            }

            if ($screen->id === 'botblocker_page_bbcs_setup_guide') {
                wp_enqueue_script(BOTBLOCKER_SHORT_NAME . '-setup-js', plugin_dir_url(__FILE__) . 'js/bbcs-js/bbcs-setup.js', array('jquery'), BOTBLOCKER_VERSION, true);
            }
        }
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'BotBlocker',
            'BotBlocker',
            'manage_options',
            'bbcs_dashboard',
            array($this, 'dashboard_page'),
            'dashicons-shield-alt',
            6
        );

        // 1) Dashboard
        add_submenu_page(
            'bbcs_dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'bbcs_dashboard',
            array($this, 'dashboard_page')
        );

        // 2) Health Status
        add_submenu_page(
            'bbcs_dashboard',
            'Health Status',
            'Health Status',
            'manage_options',
            'bbcs_setup_guide',
            array($this, 'setup_guide_page')
        );

        // 3) Settings
        add_submenu_page(
            'bbcs_dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'bbcs_settings',
            array($this, 'settings_page')
        );

        // 4) Rules
        add_submenu_page(
            'bbcs_dashboard',
            'Rules',
            'Rules',
            'manage_options',
            'bbcs_rules',
            array($this, 'rules_page')
        );

        // 5) Integrations
        add_submenu_page(
            'bbcs_dashboard',
            'Integrations',
            'Integrations',
            'manage_options',
            'bbcs_integrations',
            array($this, 'integrations_page')
        );

        // 6) Tools
        add_submenu_page(
            'bbcs_dashboard',
            'Tools',
            'Tools',
            'manage_options',
            'bbcs_tools',
            array($this, 'tools_page')
        );

        // 7) Reports
        add_submenu_page(
            'bbcs_dashboard',
            'Reports',
            'Reports',
            'manage_options',
            'bbcs_reports',
            array($this, 'reports_page')
        );

        // 8) Addons
        add_submenu_page(
            'bbcs_dashboard',
            'Addons',
            'Addons',
            'manage_options',
            'bbcs_addons',
            array($this, 'addons_page')
        );

        // 9) PRO
        add_submenu_page(
            'bbcs_dashboard',
            'PRO',
            'PRO',
            'manage_options',
            'bbcs_cloud_api',
            array($this, 'cloud_api_page')
        );

        // 10) About
        add_submenu_page(
            'bbcs_dashboard',
            'About',
            'About',
            'manage_options',
            'bbcs_about',
            array($this, 'about_page')
        );

        // // Setup Wizard (скрытая страница, без пункта в меню)
        // add_submenu_page(
        //     null, // parent_slug = null означает что не показывается в меню
        //     __('Setup Wizard', 'botblocker-security'),
        //     __('Setup Wizard', 'botblocker-security'),
        //     'manage_options',
        //     'bbcs_setup_wizard',
        //     '__return_false' // callback пустой, т.к. визард сам загружает контент
        // );

        add_menu_page(
            __('Setup Wizard', 'botblocker-security'),
            __('Setup Wizard', 'botblocker-security'),
            'manage_options',
            'bbcs_setup_wizard',
            'bbcs_render_setup_wizard',
            '',
            null
        );

        remove_menu_page('bbcs_setup_wizard');
    }

    public function dashboard_page()
    {
        require plugin_dir_path(__FILE__) . 'partials/botblocker-admin-display-dashboard.php';
    }

    public function settings_page()
    {
        require plugin_dir_path(__FILE__) . 'partials/botblocker-admin-display-settings.php';
    }

    public function reports_page()
    {
        require plugin_dir_path(__FILE__) . 'partials/botblocker-admin-display-reports.php';
    }

    public function rules_page()
    {
        require plugin_dir_path(__FILE__) . 'partials/botblocker-admin-display-rules.php';
    }

    public function tools_page()
    {
        require plugin_dir_path(__FILE__) . 'partials/botblocker-admin-display-tools.php';
    }

    public function integrations_page()
    {
        require plugin_dir_path(__FILE__) . 'partials/botblocker-admin-display-integrations.php';
    }

    public function cloud_api_page()
    {
        require plugin_dir_path(__FILE__) . 'partials/botblocker-admin-display-cloud-api.php';
    }

    public function addons_page()
    {
        require plugin_dir_path(__FILE__) . 'partials/botblocker-admin-display-addons.php';
    }

    public function setup_guide_page()
    {
        require plugin_dir_path(__FILE__) . 'partials/botblocker-admin-display-setup-guide.php';
    }

    public function about_page()
    {
        require plugin_dir_path(__FILE__) . 'partials/botblocker-admin-display-about.php';
    }

    public function add_to_admin_bar($wp_admin_bar)
    {
        $wp_admin_bar->add_node([
            'id'    => 'bbcs_admin_bar',
            'title' => '<span class="ab-icon dashicons-shield-alt"></span> BotBlocker',
            'href'  => admin_url('admin.php?page=bbcs_dashboard'),
            'meta'  => [
                'title' => __('Go to BotBlocker Dashboard', 'botblocker-security'),
            ],
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'bbcs_admin_bar_dashboard',
            'parent' => 'bbcs_admin_bar',
            'title'  => __('Dashboard', 'botblocker-security'),
            'href'   => admin_url('admin.php?page=bbcs_dashboard'),
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'bbcs_admin_bar_settings',
            'parent' => 'bbcs_admin_bar',
            'title'  => __('Settings', 'botblocker-security'),
            'href'   => admin_url('admin.php?page=bbcs_settings'),
        ]);
    }

    public function register_admin_bar()
    {
        add_action('admin_bar_menu', [$this, 'add_to_admin_bar'], 100);
    }

    public function plugin_action_links($links)
    {
        $dashboard_link = '<a href="' . esc_url(admin_url('admin.php?page=bbcs_dashboard')) . '" style="color: #2271b1; font-weight: 600;">' . esc_html__('Dashboard', 'botblocker-security') . '</a>';
        array_unshift($links, $dashboard_link);
        return $links;
    }

    public function run()
    {
        $this->load_settings();
        add_filter('plugin_action_links_' . BOTBLOCKER_BASENAME, [$this, 'plugin_action_links']);
    }

    private function check_translate_for_this_locale($locale)
    {
        $mo_file = BOTBLOCKER_DIR . 'languages/botblocker-security-' . $locale . '.mo';
        return file_exists($mo_file) ? $locale : '';
    }
}
