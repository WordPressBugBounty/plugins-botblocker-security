<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
trait BotBlockerHeaderTrait {
    
    public function set_iframe_headers() {
        if ($this->settings->iframe_stop == 1) {
            header('X-Frame-Options: SAMEORIGIN');
        }    
    }

    public function set_work_headers() {
        header('Content-Type: text/html; charset=UTF-8');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: *');
        header('X-Robots-Tag: noindex');
    }

    public function set_check_headers() {
        // TODO HEADERS ALREADY SENT ON doing_wp_cron
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex');
        header($this->protocol . ' ' . $this->error_headers[$this->settings->header_test_code]);
        header('Status: ' . $this->error_headers[$this->settings->header_test_code]);
    }

    public function set_denied_headers() {
        header('X-Robots-Tag: noindex, noarchive');
        header($this->protocol . ' ' . $this->error_headers[$this->settings->header_error_code]);
        header('Status: ' . $this->error_headers[$this->settings->header_error_code]);
    }

    public function reset_post_headers() {
        if ($this->request_method == 'POST') {
            header('Location: ' . $this->uri);
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

        if (count($this->x_robots_tag) > 0) {
            header('X-Robots-Tag: ' . implode(', ', $this->x_robots_tag));
        }
    }
}
