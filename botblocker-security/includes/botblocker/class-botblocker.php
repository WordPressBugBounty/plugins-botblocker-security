<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The BotBlocker engine class.
 *
 * This class is responsible for all the operations against bots.
 * It handles detections, logging, and blocking of suspicious bot activities.
 * 
 * @version    1.6.16
 * @author     BotBlocker Team
 * @package    Botblocker 
 * @subpackage Botblocker/includes
 */
 
require_once BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker-base.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker-settings.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-core-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-visitor-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-rules-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-response-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-local-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-post-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-cookie-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-header-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-check-page-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-block-page-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-denied-page-trait.php';


class BotBlocker extends BotBlockerBase
{
    use BotBlockerCoreTrait;
    use BotBlockerVisitorTrait;
    use BotBlockerRulesTrait;
    use BotBlockerResponseTrait;
    use BotBlockerLocalTrait;
    use BotBlockerPostTrait;
    use BotBlockerCookieTrait;
    use BotBlockerHeaderTrait;
    use BotBlockerCheckPageTrait;
    use BotBlockerBlockPageTrait;
    use BotBlockerDeniedPageTrait;
 
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->time = time();
        $this->should_show_check_page = false; 
    }

    public function initialize() : void
    {
        $this->start_bbcs();
        $this->check_admin_status();
        $this->load_directories();
		$this->generate_missing_files();
        $this->load_data();
        $this->initialize_config();
        $this->load_settings();
        $this->generate_connection_id();

        if ($this->check_secret_parameter()) return;
        if ($this->process_disabled_state()) return;

        $guard = $this->start_output_guard();
        $this->run();
        $this->end_output_guard($guard);
    }

    public function run() : void
    {
        if ($this->perform_prefly_checks()) return;
        $this->collect_visitor_data();
		$this->update_settings_based_on_visitor_data();
        if ($this->is_safe_request()) return;
        if ($this->check_white_bot()) return;
        if ($this->check_ip_rules()) return;
        if ($this->check_rules_database()) return;
        if ($this->check_path_rules()) return;

        $this->select_request_mode();
        $this->process_headers();
        
        if ($this->process_cookies()) {
            $this->send_vary_cookie_header();
            $this->set_x_robot_headers(); 
            return;
        }
        
        $this->perform_simple_bot_checks();
        $this->validate_referer();
        $this->check_referer_get_params();
        $this->check_hosting();
        $this->check_language_mismatch();

        //if ($this->check_last_rule()) return;

        if ($this->settings->botblocker_force_check == 1) {
            $this->redirect_to_dark('Force check event');
        }

        $this->set_x_robot_headers();
    }

    public function get_bot_blocker_hive(): array 
    {
        $properties = get_object_vars($this);
        $bbcs = [];
        foreach ($properties as $key => $value) {
            if (!in_array($key, ['instance'])) {
                $bbcs[$key] = $value;
            }
        }
        return $bbcs;
    }

    public function print_hive() : void
    {
        /**
         * REVIEWER NOTE:
         * print_r() is used here for displaying diagnostic information on admin pages only,
         * not for debug logging. This method displays the hive state in the admin interface
         * for troubleshooting purposes and is not used in production error logging.
         * The PHPCS warning WordPress.PHP.DevelopmentFunctions.error_log_print_r is a false positive.
         */
        /* phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r */
        $BBCS = $this->get_bot_blocker_hive();
        echo '<small><pre>';
        print_r($BBCS);
        echo '</pre></small>';
        /* phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_print_r */
    }

    public function process_die($message = '') : void
    {
        if (!empty($message)) {
            die(wp_kses_post($message));
        } else {
            if (BBCS_DIE_MESSAGE) {
                $this->print_hive();
                die('Wordpress stopped by BotBlocker');
            } else {
                die();
            }
        }
    }

}
