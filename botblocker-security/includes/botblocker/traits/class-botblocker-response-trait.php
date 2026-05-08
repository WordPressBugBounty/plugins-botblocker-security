<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerResponseTrait {
    
    public function process_headers() : void
    {
        if ($this->settings->noarchive == 1) {
            $this->x_robots_tag['noarchive'] = 'noarchive';
        }
        if (isset($_GET['utm_referrer']) && $this->settings->utm_noindex == 1) {
            $this->x_robots_tag['noindex'] = 'noindex';
        }
    }
 
    public function select_request_mode()
    { 
        $this->select_request_mode = 'x' . md5($this->settings->cloud_api_email . BOTBLOCKER_SHORT_NAME);
        if ($this->request_method == 'POST' && isset($_POST[$this->select_request_mode])) {
            check_ajax_referer('botblocker_nonce', 'nonce');
            $mode = sanitize_text_field( wp_unslash( $_POST[$this->select_request_mode] ) );
            if ($mode === 'botblocker-security') {
                $this->set_work_headers();
                $this->processLocalRequest();
                $this->process_die();
            } elseif ($mode === 'post') {
                $this->set_work_headers();
                $this->processPostRequest();
                $this->process_die();
            } elseif ($mode === 'img') {
                if (isset($_POST['img'])) {
                    $img_raw = absint( wp_unslash( $_POST['img'] ) );
                } else {
                    if ($this->settings->botblocker_log_error == 1) {
                        bbcs_storeData('BBCS Image Error - Empty', 20);
                    }
                    bbcs_process_hit(20);
                    $this->process_die('BBCS Image Error - Empty');
                }
                $image_id = (int) preg_replace('/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['img'] ) ) );
                $imagePath = $this->dirs['public'] . 'img/' . $this->settings->bbcs_captcha_img_pack . '/' . $image_id . '.jpg';

                if (file_exists($imagePath)) {
                    header('Content-Type: image/jpeg');
                    header('Content-Length: ' . filesize($imagePath));
                    header('X-Content-Type-Options: nosniff');
                    // REVIEWER NOTE: Direct file output used intentionally for performance; WP_Filesystem not suitable for real-time image delivery.
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
                    readfile($imagePath);
                } else {
                    if ($this->settings->botblocker_log_error == 1) {
                        bbcs_storeData('BBCS File Error - Not Found', 80);
                    }
                    bbcs_process_hit(80);
                    $this->process_die('404 - Image not found');
                }
            }
            $this->process_die();
        } else {
            return true;
        }
    }

    /**
     * Define WP no-cache constants to prevent caching plugins from storing this response.
     */
    private function define_no_cache_constants(): void {
        // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
        if (!defined('DONOTCACHEPAGE'))   define('DONOTCACHEPAGE', true);
        if (!defined('DONOTCACHEOBJECT')) define('DONOTCACHEOBJECT', true);
        if (!defined('DONOTCACHEDB'))     define('DONOTCACHEDB', true);
        // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound

        $this->register_wpfc_no_cache_filter();
        $this->register_wp_rocket_no_cache_filter();
    }

    /**
     * Make WP Fastest Cache respect DONOTCACHEPAGE.
     */
    private function register_wpfc_no_cache_filter(): void {
        if (defined('BBCS_WPFC_COMPAT')) return;
        define('BBCS_WPFC_COMPAT', true);

        add_filter('wpfc_buffer_callback_filter', static function ($buffer) {
            if (defined('DONOTCACHEPAGE') && DONOTCACHEPAGE) {
                return '';
            }
            return $buffer;
        }, 1);
    }

    /**
     * Prevent WP Rocket from caching BotBlocker security pages.
     */
    private function register_wp_rocket_no_cache_filter(): void {
        if (defined('BBCS_WPROCKET_COMPAT')) return;
        define('BBCS_WPROCKET_COMPAT', true);

        add_filter('do_rocket_generate_caching_files', '__return_false', PHP_INT_MAX);
    }

    public function perform_check() : void
    {
        $guard = null;
        if ((int) $this->settings->secure_mode === self::SECURE_MODE_FULL) {
            $this->csp_nonce = base64_encode(random_bytes(16));
            $guard = $this->start_output_guard();
        }
        $this->define_no_cache_constants();
        $this->reset_post_headers();
        $this->set_iframe_headers(); 
        $this->set_check_headers();
        $this->set_check_page();
        if ($guard !== null) {
            $this->end_output_guard($guard);
        }
        if ($this->settings->botblocker_log_tests == 1) {
            bbcs_storeData('Check page for visitor', 0);
        }
        bbcs_process_hit(0);
    }

    public function redirect_to_denied($code = null, $message = null) : void
    {
        if ($this->settings->botblocker_log_block == 1) {
            bbcs_storeData($message, $code);
        }
        bbcs_process_hit($code);
        $this->show_denied_page($message);
    }

    public function show_denied_page($message = null) : void
    {
        $this->define_no_cache_constants();
        $guard = null;
        if ((int) $this->settings->secure_mode === self::SECURE_MODE_FULL) {
            $guard = $this->start_output_guard();
        }
        $this->set_denied_headers();
        $this->set_denied_page($message); 
        if ($guard !== null) {
            $this->end_output_guard($guard);
        }
        if ($this->settings->secure_mode == self::SECURE_MODE_FULL) $this->process_die();
    }

    public function redirect_to_block($code = null, $message = null, $ip_test = null) : void
    {
        if ($this->settings->botblocker_log_block == 1) {
            bbcs_storeData($message, $code);
        }
        bbcs_process_hit($code);
        $this->show_block_page($ip_test);
        if ($this->settings->secure_mode == self::SECURE_MODE_FULL) $this->process_die();
    }

    public function show_block_page($bbcs_ip_test = null) : void
    {
        $this->define_no_cache_constants();
        $guard = null;
        if ((int) $this->settings->secure_mode === self::SECURE_MODE_FULL) {
            $guard = $this->start_output_guard();
        }
        $this->set_iframe_headers();
        $this->set_denied_headers();
        $this->set_block_page($bbcs_ip_test);
        if ($guard !== null) {
            $this->end_output_guard($guard);
        }
    }

    public function set_gray_status($result) : void
    {
        $this->suspect_status = 1;
        $this->result_of_action = $result;
    }

    public function finish_execution($echo = 0) : void
    {
        $exec_time = time() - $this->time;
        if (!empty($echo) && $echo === 1) echo '<!-- BotBlocker Execution Time: ' . esc_html($exec_time) . ' -->';
    }

    public function set_denied_page($message = null)
    {
        if ($this->settings->secure_mode == self::SECURE_MODE_FRONTEND){
            $this->should_show_denied_page = true;
            if (BBCS_BLOCK_REASON_VIEW) {
                if (!empty($message) && $message !== null) {
                    $this->denied_data = $message;
                } else {
                    $this->denied_data = $this->denied_data . 'REASON_CODE: [' . $this->reason_for_action . '] / CHECK_RESULT: [' . $this->result_of_action . ']';
                }
            }
        }
        if ($this->settings->secure_mode == self::SECURE_MODE_FULL){
            $template_file = $this->dirs['public'] . 'template-botblocker-denied.php';
            if (!file_exists($template_file)) {
                echo '<h1>Access Denied</h1><p>' . esc_html($this->ip) . ' ' . esc_html(gmdate('d.m.Y H:i:s', $this->time)) . '</p>';
                return;
            }

            ob_start();
            require_once($template_file);
            $error_tpl = ob_get_clean();
            $error_tpl = str_replace('<!--error-->', esc_html($this->ip) . ' ' . esc_html(gmdate('d.m.Y H:i:s', $this->time)), $error_tpl);

            $denied_message = esc_html(__('Sorry, your request has been denied', 'botblocker-security'));
            $error_tpl = str_replace('<!--denied_message-->', $denied_message, $error_tpl);

            $error_code_text = 'Error Code: ' . esc_html($this->error_headers[$this->settings->header_error_code]);
            $error_tpl = str_replace('<!--error_code-->', $error_code_text, $error_tpl);

            $error_tpl = str_replace('<!--ip_ban_msg-->', '', $error_tpl);

            if (BBCS_BLOCK_REASON_VIEW) {
                if (!empty($message) && $message !== null) {
                    $error_tpl = str_replace('<!--reason_message-->', esc_html($message), $error_tpl);
                } else {
                    $error_tpl = str_replace('<!--reason_message-->', 'REASON_CODE: [' . esc_html($this->reason_for_action) . '] / CHECK_RESULT: [' . esc_html($this->result_of_action) . ']', $error_tpl);
                }
            } else {
                $error_tpl = str_replace('<!--reason_message-->', '', $error_tpl);
            }
            // All variables inside $error_tpl are already escaped (esc_html/esc_url/esc_attr/wp_kses) or static
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $error_tpl;
            unset($error_tpl);
        }
    }

    public function set_block_page($this_ip_test = null)
    {
        if ($this->settings->secure_mode == self::SECURE_MODE_FRONTEND){
            $this->should_show_block_page = true;

            $this->block_wait_seconds = 0;
            if (isset($this_ip_test['expires']) && is_numeric($this_ip_test['expires']) && $this_ip_test['expires'] - $this->time < 86401) {
                $this->block_wait_seconds = (int) ($this_ip_test['expires'] - $this->time + 4);
            }

            $this->block_data = '';
            if (BBCS_BLOCK_REASON_VIEW) {
                $this->block_data = 'REASON_CODE: [' . $this->reason_for_action . '] / CHECK_RESULT: [' . $this->result_of_action . ']';
            }
        }
        if ($this->settings->secure_mode == self::SECURE_MODE_FULL){
            $template_file = $this->dirs['public'] . 'template-botblocker-denied.php';
            if (!file_exists($template_file)) {
            // error_log('BotBlocker: Block template file not found: ' . $template_file);
                echo '<h1>Access Denied</h1><p>' . esc_html($this->ip) . ' ' . esc_html(gmdate('d.m.Y H:i:s', $this->time)) . '</p>';
                return;
            }

            ob_start();
            require_once($template_file);
            $error_tpl = ob_get_clean();
            $error_tpl = str_replace('<!--error-->', esc_html($this->ip) . ' ' . esc_html(gmdate('d.m.Y H:i:s', $this->time)), $error_tpl);

            $error_tpl = str_replace('<!--denied_message-->', '', $error_tpl);

            $error_tpl = str_replace('<!--error_code-->', '', $error_tpl);

            if (isset($this_ip_test['expires']) && is_numeric($this_ip_test['expires']) && $this_ip_test['expires'] - $this->time < 86401) { // TODO time
                $waitTime = (int)($this_ip_test['expires'] - $this->time + 4);
                $accessBlocked = esc_html(__('Access has been blocked', 'botblocker-security'));
                $secondsLeft = esc_html(__('Seconds until unlock:', 'botblocker-security'));
                $ban_message = '<center><h1 class="info info-block">' . $accessBlocked . '</h1>
                    <h5 class="block-string">' . $secondsLeft . ' <span id="countdownTimer"><b>' . $waitTime . '</b></span></h5></center>
                    <script>
                    (function() {
                        var endTime = Date.now() + ' . $waitTime . ' * 1000;
                        function updateCounter() {
                            var timeLeft = Math.ceil((endTime - Date.now()) / 1000);
                            var t = document.getElementById("countdownTimer");
                            if (t) {
                                var b = t.querySelector("b");
                                if (b) {
                                    b.textContent = timeLeft > 0 ? timeLeft : 0;
                                } else {
                                    t.innerHTML = "<b>" + (timeLeft > 0 ? timeLeft : 0) + "</b>";
                                }
                            }
                            if (timeLeft <= 0) {
                                location.reload();
                                return;
                            }
                            requestAnimationFrame(updateCounter);
                        }
                        requestAnimationFrame(updateCounter);
                    })();
                    </script>';
        
                $error_tpl = str_replace('<!--ip_ban_msg-->', $ban_message, $error_tpl);
            } else {
                $error_tpl = str_replace('<!--ip_ban_msg-->', '', $error_tpl);
            }
            if (BBCS_BLOCK_REASON_VIEW) {
                $error_tpl = str_replace('<!--reason_message-->', 'REASON_CODE: [' . esc_html($this->reason_for_action) . '] / CHECK_RESULT: [' . esc_html($this->result_of_action) . ']', $error_tpl);
            } else {
                $error_tpl = str_replace('<!--reason_message-->', '', $error_tpl);
            }
            // All variables inside $error_tpl are already escaped (esc_html/esc_url/esc_attr/wp_kses) or static
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $error_tpl;
            unset($error_tpl);            
        }
    }  
    
    public function redirect_to_dark($result) : void
    {
        $this->suspect_status = 2;
        $this->result_of_action = $result;
        $this->perform_check();
        if ($this->settings->secure_mode == self::SECURE_MODE_FULL) $this->process_die();
    }

    public function set_check_page() 
    {
        if ($this->settings->secure_mode == self::SECURE_MODE_FRONTEND){
            if ($this->test_page_language == BOTBLOCKER_EMPTY) {
                $this->test_page_language = $this->lang;
            }
            $this->should_show_check_page = true;
        }
        if ($this->settings->secure_mode == self::SECURE_MODE_FULL){
            if ($this->test_page_language == BOTBLOCKER_EMPTY) {
                $this->test_page_language = $this->lang;
            }
        
            $tpl = '';
            $tpl_js = '';
        
            try {
                ob_start();
                $html_template = $this->dirs['public'] . 'template-botblocker-html.php';
                if (file_exists($html_template)) {
                    require_once($html_template);
                    $tpl = ob_get_clean();
                } else {
                    ob_end_clean();
                    return false;
                }
            } catch (Exception $e) {
                if (ob_get_level() > 0) ob_end_clean();
                return false;
            }
        
            try {
                ob_start();
                $js_template = $this->dirs['public'] . 'template-botblocker-js.php';
                if (file_exists($js_template)) {
                    require_once($js_template);
                    $tpl_js = ob_get_clean();
                } else {
                    ob_end_clean();
                    $tpl_js = '';
                }
            } catch (Exception $e) {
                if (ob_get_level() > 0) ob_end_clean();
                $tpl_js = '';
            }
        
            if (strpos($tpl, '</body><!--TPLJS-->') !== false) {
                $tpl = str_replace('</body><!--TPLJS-->', $tpl_js . '</body>', $tpl);
            } else {
                // All variables inside $tpl_js are already escaped (esc_html/esc_url/esc_attr/wp_kses).
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                $tpl = str_replace('</body>', $tpl_js . '</body>', $tpl);
            }
            
            $time = $this->time ?? time();
            $replacements = [
                'botblocker-btn-success' => 's' . md5('botblocker-btn-success' . $time),
                'botblocker-btn-color' => 's' . md5('botblocker-btn-color' . $time)
            ];
            
            foreach ($replacements as $search => $replace) {
                $tpl = str_replace($search, $replace, $tpl);
            }
            // All variables inside $tpl are already escaped (esc_html/esc_url/esc_attr/wp_kses).
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $tpl;
            unset($tpl);
            
            return true;
        }    
    }

    public function init_visitor_pages() {
        add_action('template_redirect', array($this, 'render_check_page'), 3);
        add_action('template_redirect', array($this, 'render_denied_page'), 2);
        add_action('template_redirect', array($this, 'render_block_page'), 1);
    }
}