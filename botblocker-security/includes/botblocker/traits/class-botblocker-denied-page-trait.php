<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerDeniedPageTrait {

    public function render_denied_page() {
        if ($this->should_show_denied_page === true) {
            $title_string   = $this->ip . ' ' . gmdate('d.m.Y H:i:s', $this->time);
            $this->template_data_denied = array(
                'extra_data' => $this->ip,
                'h1_title' => __('Please enable JavaScript and reload the page', 'botblocker-security'),
                'message' => __('Sorry, your request has been denied', 'botblocker-security'),
                'denied_title' => __('BotBlocker security plugin', 'botblocker-security') . $title_string,
                'denied_data' => $this->denied_data
            );
            $this->enqueue_denied_assets();
            $this->load_denied_template();
            exit;
        }
    }

    private function enqueue_denied_assets() {
        wp_enqueue_style(
            'bbcs-denied-style',
            BOTBLOCKER_URL . 'public/css/template.css',
            array(),
            BOTBLOCKER_VERSION
        );
    }
    
    private function load_denied_template() {
        $template_path = BOTBLOCKER_DIR . 'public/templates/denied-page.php';
        if (file_exists($template_path)) {
            extract($this->template_data_denied);
            include $template_path;
        }
    }    

}    