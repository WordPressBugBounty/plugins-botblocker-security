<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
trait BotBlockerHeaderTrait {
    protected function start_output_guard(): int {
        $level = ob_get_level();
        ob_start();
        return $level;
    }

    protected function end_output_guard(int $guard_level): void {
        while (ob_get_level() > $guard_level) {
            ob_end_flush();
        }
    }

    protected function dispatch_security_headers_addon(): void {
        if (class_exists('Botblocker_Security_Headers')) {
            Botblocker_Security_Headers::get_instance()->dispatch_headers();
        }
    }

    public function set_iframe_headers() {
        if ($this->settings->iframe_stop == 1 && !headers_sent()) {
            if (class_exists('Botblocker_Security_Headers') && $this->is_addon_basic_headers_active()) {
                return;
            }
            header('X-Frame-Options: SAMEORIGIN');
        }
    }

    private function is_addon_basic_headers_active(): bool {
        $settings = get_option('botblocker_tools_headers_settings', []);
        if (!is_array($settings)) {
            return false;
        }
        $enabled = isset($settings['security_headers_enable']) && in_array($settings['security_headers_enable'], [1, '1'], true);
        $basic   = !array_key_exists('security_headers_basic', $settings) || in_array($settings['security_headers_basic'], [1, '1'], true);
        return $enabled && $basic;
    }

    /**
     * Prevent caching of security-critical pages (check, denied, block, AJAX).
     * Mirrors MU-phase headers from mu-botblocker-header.php.
     */
    private function send_no_cache_headers(): void {
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Thu, 10 Aug 2000 06:00:00 GMT');
            header('X-LiteSpeed-Cache-Control: no-cache');
        }
    }

    /**
     * Send Vary: Cookie header so caches distinguish responses by cookie set.
     * Controlled by the vary_cookie plugin setting.
     */
    public function send_vary_cookie_header(): void {
        if (isset($this->settings->vary_cookie) && $this->settings->vary_cookie == 1) {
            if (!headers_sent()) {
                header('Vary: Cookie', false);
            }
        }
    }

    public function set_work_headers() {
        if (headers_sent()) return;
        $this->send_no_cache_headers();
        header('Content-Type: text/html; charset=UTF-8');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: *');
        header('X-Robots-Tag: noindex');
        $this->dispatch_security_headers_addon();
    }

    public function set_check_headers() {
        if (headers_sent()) return;
        $this->send_no_cache_headers();
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex');
        header($this->protocol . ' ' . $this->error_headers[$this->settings->header_test_code]);
        header('Status: ' . $this->error_headers[$this->settings->header_test_code]);
        $this->dispatch_security_headers_addon();
    }

    public function set_denied_headers() {
        if (headers_sent()) return;
        $this->send_no_cache_headers();
        header('X-Robots-Tag: noindex, noarchive');
        header($this->protocol . ' ' . $this->error_headers[$this->settings->header_error_code]);
        header('Status: ' . $this->error_headers[$this->settings->header_error_code]);
        $this->dispatch_security_headers_addon();
    }

    public function reset_post_headers() {
        if ($this->request_method == 'POST') {
            if (!headers_sent()) {
                header('Location: ' . $this->uri);
            }
            $this->process_die();
        }    
    }

    public function set_x_robot_headers() {
        if (!isset($this->x_robots_tag) || !is_array($this->x_robots_tag)) {
            $this->x_robots_tag = [];
        }

        $auto_directives = $this->x_robots_tag;
        $this->x_robots_tag = [];

        $available_directives = bbcs_get_x_robot_tags();

        if (isset($this->settings->x_robots_directives) && !empty($this->settings->x_robots_directives)) {
            $user_directives = is_array($this->settings->x_robots_directives) ? 
                              $this->settings->x_robots_directives : 
                              json_decode($this->settings->x_robots_directives, true);
            
            if (is_array($user_directives)) {
                foreach ($user_directives as $directive) {
                    $directive = trim($directive);
                    if (!empty($directive) && isset($available_directives[$directive])) {
                        if (!empty($available_directives[$directive])) {
                            $this->x_robots_tag[] = $directive . ':' . $available_directives[$directive];
                        } else {
                            $this->x_robots_tag[] = $directive;
                        }
                    }
                }
            }
        }

        if ($this->settings->noarchive == 1 && !in_array('noarchive', $this->x_robots_tag)) {
            $this->x_robots_tag[] = 'noarchive';
        }

        foreach ($auto_directives as $auto_directive) {
            $directive_name = explode(':', $auto_directive)[0];

            $already_added = false;
            foreach ($this->x_robots_tag as $added_directive) {
                if (strpos($added_directive, $directive_name . ':') === 0 || $added_directive === $directive_name) {
                    $already_added = true;
                    break;
                }
            }

            if (!$already_added) {
                $this->x_robots_tag[] = $auto_directive;
            }
        }

        if (count($this->x_robots_tag) > 0 && !headers_sent()) {
            header('X-Robots-Tag: ' . implode(', ', $this->x_robots_tag));
        }
    }
}
