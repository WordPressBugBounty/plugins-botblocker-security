<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Support Button Component for BotBlocker Security Plugin
 */

class BBCS_Support_Button {    
    public function __construct() {
        add_action('bbcs_show_support_button', array($this, 'render_support_button'));
        add_action('wp_ajax_bbcs_send_support', array($this, 'handle_support_request'));
        add_action('wp_ajax_nopriv_bbcs_send_support', array($this, 'handle_support_request'));
    }

    public function render_support_button() {
        ?>
        <div id="bbcs-support-component">
            <button id="bbcs-support-btn" class="bbcs-float-btn" aria-label="<?php echo esc_attr__('Support', 'botblocker-security'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </button>
            
            <div id="bbcs-support-panel" class="bbcs-panel bbcs-hidden">
                <div class="bbcs-panel-header">
                    <h3><?php echo esc_html__('Support', 'botblocker-security'); ?></h3>
                    <button id="bbcs-close-btn" class="bbcs-close-btn" aria-label="<?php echo esc_attr__('Close', 'botblocker-security'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                
                <div class="bbcs-panel-body">
                    <form id="bbcs-support-form">
                        <div class="bbcs-form-group">
                            <label for="bbcs-name"><?php echo esc_html__('Name', 'botblocker-security'); ?></label>
                            <input type="text" id="bbcs-name" name="name" required placeholder="<?php echo esc_attr__('Enter your name', 'botblocker-security'); ?>">
                        </div>
                        
                        <div class="bbcs-form-group">
                            <label for="bbcs-email"><?php echo esc_html__('Email', 'botblocker-security'); ?></label>
                            <input type="email" id="bbcs-email" name="email" required placeholder="<?php echo esc_attr__('Enter your email', 'botblocker-security'); ?>">
                        </div>
                        
                        <div class="bbcs-form-group">
                            <label for="bbcs-question"><?php echo esc_html__('Question', 'botblocker-security'); ?></label>
                            <textarea id="bbcs-question" name="question" rows="4" required placeholder="<?php echo esc_attr__('Describe your question', 'botblocker-security'); ?>"></textarea>
                        </div>

                        <button type="submit" class="bbcs-submit-btn"><?php echo esc_html__('Send', 'botblocker-security'); ?></button>
                        
                        <div id="bbcs-message" class="bbcs-message bbcs-hidden"></div>
                    </form>
                    
                    <div class="bbcs-footer">
                        <a href="<?php echo esc_url('https://t.me/botblocker_support'); ?>" target="_blank" class="bbcs-telegram-link">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                            </svg>
                            <span><?php echo esc_html__('Chat', 'botblocker-security'); ?> <span class="bbcs-pro-badge"><?php echo esc_html__('PRO', 'botblocker-security'); ?></span></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function handle_support_request() {
        check_ajax_referer('botblocker_support_nonce', 'nonce');
        
        $domain = '';

        $origin_raw = filter_input( INPUT_SERVER, 'HTTP_ORIGIN', FILTER_UNSAFE_RAW );
        if ( false !== $origin_raw && ! empty( $origin_raw ) ) {
            $origin_clean = sanitize_text_field( wp_unslash( $origin_raw ) );
            if ( ! empty( $origin_clean ) ) {
                $host = wp_parse_url( $origin_clean, PHP_URL_HOST );
                if ( $host ) {
                    $domain = sanitize_text_field( $host );
                }
            }
        }

        if ( empty( $domain ) ) {
            $referer_raw = filter_input( INPUT_SERVER, 'HTTP_REFERER', FILTER_UNSAFE_RAW );
            if ( false !== $referer_raw && ! empty( $referer_raw ) ) {
                $referer_clean = sanitize_text_field( wp_unslash( $referer_raw ) );
                if ( ! empty( $referer_clean ) ) {
                    $host = wp_parse_url( $referer_clean, PHP_URL_HOST );
                    if ( $host ) {
                        $domain = sanitize_text_field( $host );
                    }
                }
            }
        }

        if ( empty( $domain ) ) {
            $host_header = filter_input( INPUT_SERVER, 'HTTP_HOST', FILTER_UNSAFE_RAW );
            if ( false !== $host_header && ! empty( $host_header ) ) {
                $domain = sanitize_text_field( wp_unslash( $host_header ) );
            }
        }

        $domain = trim( (string) $domain );

        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $question = isset( $_POST['question'] ) ? sanitize_textarea_field( wp_unslash( $_POST['question'] ) ) : '';
        
        if (empty($name) || empty($email) || empty($question)) {
            wp_send_json_error(array('message' => __('All fields are required', 'botblocker-security')));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Invalid email address', 'botblocker-security')));
        }

        $data = array(
            'domain'   => $domain,
            'name'     => $name,
            'email'    => $email,
            'message'  => $question
        );
        
        $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_GS_URL, 'helpdesk');
        if ($cloud === false) {
            $cloud = BotBlockerWpRequest::send_to_cloud($data, BOTBLOCKER_API_URL, 'helpdesk');
        }

        if ( $cloud === false ) {
            wp_send_json_error( array( 'message' => __( 'Sending failed. Please try again later.', 'botblocker-security' ) ) );
        }

        wp_send_json_success( array( 'message' => __( 'Your request has been sent successfully!', 'botblocker-security' ) ) );
    }
}

// Initialize the support button
new BBCS_Support_Button();
?>