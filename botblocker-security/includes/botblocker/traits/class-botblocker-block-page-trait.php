<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerBlockPageTrait {

    public function render_block_page() {
        if ($this->should_show_block_page === true) {
            $title_string   = $this->ip . ' ' . gmdate('d.m.Y H:i:s', $this->time);
            $this->template_data_block = array(
                'extra_data' => $this->ip,
                'h1_title' => __('Please turn JavaScript on and reload the page', 'botblocker-security'),
                //'message' => __('Sorry, your request has been blocked', 'botblocker-security'),
                'block_title' => __('BotBlocker security plugin', 'botblocker-security') . $title_string,
                'block_data' => $this->block_data
            );
            $this->prepare_block_js_data();
            $this->enqueue_block_assets();
            $this->load_block_template();
            exit;
        }
    }

    private function prepare_block_js_data() {
        $wait = isset($this->block_wait_seconds) ? (int) $this->block_wait_seconds : 0;
        $reason_view = (defined('BBCS_BLOCK_REASON_VIEW') && BBCS_BLOCK_REASON_VIEW);
        $this->block_js_data = array(
            'hasCountdown' => ($wait > 0),
            'waitSeconds' => $wait,
            'accessBlocked' => __('Access has been blocked', 'botblocker-security'),
            'secondsLeft' => __('Seconds left until the unlock:', 'botblocker-security'),
            'reasonView' => $reason_view,
            'reasonText' => $reason_view ? $this->block_data : '',
        );
    }

    private function enqueue_block_assets() {
        wp_enqueue_style(
            'bbcs-block-style',
            BOTBLOCKER_URL . 'public/css/template.css',
            array(),
            BOTBLOCKER_VERSION
        );

        wp_enqueue_script(
            'bbcs-block-script',
            BOTBLOCKER_URL . 'public/js/block.js',
            array(),
            BOTBLOCKER_VERSION,
            true
        );

        if (! empty($this->block_js_data)) {
            wp_localize_script(
                'bbcs-block-script',
                'bbcsBlockData',
                $this->block_js_data
            );
        }
    }
    
    private function load_block_template() {
        $template_path = BOTBLOCKER_DIR . 'public/templates/block-page.php';
        if (file_exists($template_path)) {
            extract($this->template_data_block);
            include $template_path;
        }
    }    

}