<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

include('botblocker-section-header.php');

$bbcs_geo_countries = bbcs_get_option('bbcs_blocked_countries', []);
if (is_string($bbcs_geo_countries)) {
    $decoded = json_decode($bbcs_geo_countries, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $bbcs_geo_countries = $decoded;
    } else {
        $bbcs_geo_countries = array_filter(array_map('trim', explode(',', $bbcs_geo_countries)));
    }
}
if (is_array($bbcs_geo_countries)) {
    $bbcs_geo_countries = array_map('strtoupper', array_filter($bbcs_geo_countries, function ($item) {
        return preg_match('/^[A-Z]{2}$/', (string) $item);
    }));
    $bbcs_geo_countries = array_values(array_unique($bbcs_geo_countries));
} else {
    $bbcs_geo_countries = [];
}
$bbcs_geo_countries_count = count($bbcs_geo_countries);

?><section role="main" class="content-body">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-7 сol-lg-7 col-xl-7 col-xxl-7">
            <section class="card mb-2">
                <header class="card-header">
                    <div class="card-actions">

                    </div>
                    <h2 class="card-title"><?php esc_html_e('Rules and IP lists', 'botblocker-security'); ?></h2>
                </header>
                <div class="card-body">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab"
                                href="#bbcs_rules"><?php esc_html_e('Rules', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab"
                                href="#bbcs_path"><?php esc_html_e('Paths', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab"
                                href="#bbcs_white_bots"><?php esc_html_e('White Bots', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab"
                                href="#bbcs_IPv4_list"><?php esc_html_e('IPv4 List', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab"
                                href="#bbcs_IPv6_list"><?php esc_html_e('IPv6 List', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab"
                                href="#bbcs_proxy_list"><?php esc_html_e('Proxy', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab"
                                href="#bbcs_asn_list"><?php esc_html_e('ASN', 'botblocker-security'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab"
                                href="#bbcs_geo_list"><?php esc_html_e('GEO', 'botblocker-security'); ?></a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane container active" id="bbcs_rules">
                            <div class="bbcs_control_panel">
                                <?php include_once BOTBLOCKER_DIR . 'includes/section/controls/botblocker-rule-controls.php'; ?>
                            </div>
                            <table class="table table-bordered table-striped compact mb-0" id="botblocker-rules"
                                style="width:100%; font-size: 11px;">
                                <thead>
                                    <tr>
                                        <th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
                                        <th style="min-width: 80px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
                                        <th style="min-width: 80px;"><?php esc_html_e('Type', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Data', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Expires', 'botblocker-security'); ?></th>
                                        <th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="tab-pane container fade" id="bbcs_path">
                            <div class="bbcs_control_panel">
                                <?php include_once BOTBLOCKER_DIR . 'includes/section/controls/botblocker-path-controls.php'; ?>
                                <table class="table table-bordered table-striped compact mb-0" id="botblocker-paths"
                                    style="width:100%; font-size: 11px;">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
                                            <th style="min-width: 80px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
                                            <th style="min-width: 100px;"><?php esc_html_e('Data', 'botblocker-security'); ?></th>
                                            <th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
                                            <th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
                                            <th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane container fade" id="bbcs_white_bots">
                            <div class="bbcs_control_panel">
                                <?php include_once BOTBLOCKER_DIR . 'includes/section/controls/botblocker-white-controls.php'; ?>
                                <table class="table table-bordered table-striped compact mb-0" id="botblocker-white"
                                    style="width:100%; font-size: 11px;">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
                                            <th style="min-width: 80px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
                                            <th style="min-width: 80px;"><?php esc_html_e('Search', 'botblocker-security'); ?></th>
                                            <th style="min-width: 100px;"><?php esc_html_e('Data', 'botblocker-security'); ?></th>
                                            <th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
                                            <th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
                                            <th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane container fade" id="bbcs_IPv4_list">
                            <div class="bbcs_control_panel">
                                <?php include_once BOTBLOCKER_DIR . 'includes/section/controls/botblocker-ipv4-controls.php'; ?>
                            </div>
                            <table class="table table-bordered table-striped compact mb-0" id="botblocker-ipv4-rules"
                                style="width:100%; font-size: 11px;">
                                <thead>
                                    <tr>
                                        <th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
                                        <th style="min-width: 50px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
                                        <th style="min-width: 80px;"><?php esc_html_e('Data', 'botblocker-security'); ?></th>
                                        <th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Expires', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
                                    </tr>
                                </thead>
                            </table>

                        </div>
                        <div class="tab-pane container fade" id="bbcs_IPv6_list">
                            <div class="bbcs_control_panel">
                                <?php include_once BOTBLOCKER_DIR . 'includes/section/controls/botblocker-ipv6-controls.php'; ?>
                            </div>
                            <table class="table table-bordered table-striped compact mb-0" id="botblocker-ipv6-rules"
                                style="width:100%; font-size: 11px;">
                                <thead>
                                    <tr>
                                        <th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
                                        <th style="min-width: 50px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
                                        <th style="min-width: 80px;"><?php esc_html_e('Data', 'botblocker-security'); ?></th>
                                        <th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Expires', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="tab-pane container fade" id="bbcs_proxy_list">
                            <div class="bbcs_control_panel">
                                <?php include_once BOTBLOCKER_DIR . 'includes/section/controls/botblocker-proxy-controls.php'; ?>
                            </div>
                            <table class="table table-bordered table-striped compact mb-0" id="botblocker-proxy-rules"
                                style="width:100%; font-size: 11px;">
                                <thead>
                                    <tr>
                                        <th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
                                        <th style="min-width: 150px;"><?php esc_html_e('Network Mask', 'botblocker-security'); ?></th>
                                        <th style="min-width: 150px;"><?php esc_html_e('HTTP Header', 'botblocker-security'); ?></th>
                                        <th style="min-width: 150px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="tab-pane container fade" id="bbcs_asn_list">
                            <div class="bbcs_control_panel">
                                <?php include_once BOTBLOCKER_DIR . 'includes/section/controls/botblocker-asn-controls.php'; ?>
                            </div>
                            <table class="table table-bordered table-striped compact mb-0" id="botblocker-asn-rules"
                                style="width:100%; font-size: 11px;">
                                <thead>
                                    <tr>
                                        <th style="min-width: 50px;"><?php esc_html_e('ID', 'botblocker-security'); ?></th>
                                        <th style="min-width: 80px;"><?php esc_html_e('Priority', 'botblocker-security'); ?></th>
                                        <th style="min-width: 80px;"><?php esc_html_e('ASN', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Name', 'botblocker-security'); ?></th>
                                        <th style="min-width: 80px;"><?php esc_html_e('Rule', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Comment', 'botblocker-security'); ?></th>
                                        <th style="min-width: 100px;"><?php esc_html_e('Actions', 'botblocker-security'); ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <!-- GEO SECTION -->
                        <div class="tab-pane container fade" id="bbcs_geo_list">
                            <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                                <div>
                                    <strong><?php esc_html_e('Blocked countries:', 'botblocker-security'); ?></strong>
                                    <strong class="ms-1" id="bbcs_geo_count"><?php echo esc_html($bbcs_geo_countries_count); ?></strong>
                                </div>
                                <div>
                                    <button type="button" id="bbcs_geo_refresh" class="btn btn-outline-secondary btn-sm">
                                        <i class="dashicons dashicons-update me-1"></i>
                                        <?php esc_html_e('Reload', 'botblocker-security'); ?>
                                    </button>
                                    <button type="button" id="bbcs_geo_save" class="btn btn-primary btn-sm ms-2">
                                        <i class="bbcs-card-action fa-regular fa-xl fa-floppy-disk"></i>
                                        <?php esc_html_e('Save list', 'botblocker-security'); ?>
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <div id="bbcs_geo_alert" class="alert mt-2 alert-success" role="alert" style="display: none;"><?php esc_html_e('Country list saved.', 'botblocker-security'); ?></div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <?php esc_html_e('Add country', 'botblocker-security'); ?>
                                </label>
                                <div class="d-flex gap-2">
                                    <select id="geoCountrySelect" class="form-select">
                                        <option value=""><?php esc_html_e('Select country', 'botblocker-security'); ?></option>
                                        <?php foreach (BBCS_COUNTRIES as $key => $country_name) : ?>
                                            <option value="<?php echo esc_attr(strtoupper($key)); ?>">
                                                <?php echo esc_html($country_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" id="bbcs_geo_add_country" class="btn btn-outline-primary">
                                        + <?php esc_html_e('Add', 'botblocker-security'); ?>
                                    </button>
                                    <button type="button" id="bbcs_geo_clear_all" class="btn btn-outline-danger">
                                        <?php esc_html_e('Clear all', 'botblocker-security'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <?php esc_html_e('Selected countries', 'botblocker-security'); ?>
                                </label>
                                <div id="geoTags"
                                    class="bbcs-geo-tags"
                                    style="min-height:48px; border:1px solid #ced4da; border-radius:6px; padding:10px; background:#f8f9fa; display:flex; flex-wrap:wrap; gap:8px;">
                                </div>
                            </div>
                            <div class="mb-3" style="display: none;">
                                <label class="form-label fw-semibold">
                                    <?php esc_html_e('Codes for config', 'botblocker-security'); ?>
                                </label>
                                <textarea id="geoCountryCodes" class="form-control" rows="2" readonly>
                                    <?php echo esc_textarea(implode(',', $bbcs_geo_countries)); ?>
                                </textarea>
                            </div>
                            <div class="alert alert-warning mb-0">
                                <span class="dashicons dashicons-warning me-2"></span>
                                <span>
                                    <?php esc_html_e('Visitors from selected countries will not be able to access the site.', 'botblocker-security'); ?>
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-2 сol-lg-2 col-xl-2 col-xxl-3 mb-1">
            <!--
            <section class="card">
                <header class="card-header">
                    <div class="card-actions">

                    </div>
                    <h2 class="card-title"><?php //esc_html_e('Tools', 'botblocker-security'); 
                                            ?></h2>
                </header>
                <div class="card-body">
                    <div class="bbcs_settings_button">
                        <button type="button" id="bbcs-reinstall-xxx" class="mb-1 btn btn-xs btn-danger">
                            <i class="fas fa-sync"></i>
                            <?php //esc_html_e('Database', 'botblocker-security'); 
                            ?>
                        </button>
                        <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                            data-bs-placement="top"
                            data-bs-original-title="<?php //esc_html_e('Clear all tables of BotBlocker and install initial settings', 'botblocker-security'); 
                                                    ?>"></i>
                    </div>
                </div>
            </section>
-->
            <section class="card bbcs-help-card mb-2">
                <header class="card-header bbcs_small_header">
                    <div class="card-actions"></div>
                    <h2 class="card-title"><?php esc_html_e('Quick Guide', 'botblocker-security'); ?></h2>
                </header>
                <div class="card-body" style="font-size:12px; line-height:1.4;">
                    <?php
                    $BBCSA = class_exists('Botblocker_Admin') ? Botblocker_Admin::getInstance() : null;
                    $bbcs_rules_page = $BBCSA && isset($BBCSA->pages_rules) ? $BBCSA->pages_rules : '';
                    $bbcs_l_rules = '<a href="' . esc_url($bbcs_rules_page . '#bbcs_rules') . '">' . esc_html__('Rules', 'botblocker-security') . '</a>';
                    $bbcs_l_paths = '<a href="' . esc_url($bbcs_rules_page . '#bbcs_path') . '">' . esc_html__('Paths', 'botblocker-security') . '</a>';
                    $bbcs_l_white = '<a href="' . esc_url($bbcs_rules_page . '#bbcs_white_bots') . '">' . esc_html__('White Bots', 'botblocker-security') . '</a>';
                    $bbcs_l_ipv4  = '<a href="' . esc_url($bbcs_rules_page . '#bbcs_IPv4_list') . '">' . esc_html__('IPv4', 'botblocker-security') . '</a>';
                    $bbcs_l_ipv6  = '<a href="' . esc_url($bbcs_rules_page . '#bbcs_IPv6_list') . '">' . esc_html__('IPv6', 'botblocker-security') . '</a>';
                    $bbcs_l_proxy = '<a href="' . esc_url($bbcs_rules_page . '#bbcs_proxy_list') . '">' . esc_html__('Proxy', 'botblocker-security') . '</a>';
                    ?>
                    <?php
                    /* translators: %s: Link to the Paths tab URL. */
                    $bbcs_i18n_paths_text = __('<strong>Paths:</strong> Add payment gateway callback endpoints under %s to prevent them from being blocked.', 'botblocker-security');
                    ?>
                    <p class="mb-2 bbcs-rules-guide"><?php echo wp_kses_post(sprintf($bbcs_i18n_paths_text, $bbcs_l_paths)); ?></p>

                    <?php
                    /* translators: 1: Link to the IPv4 tab URL, 2: Link to the IPv6 tab URL. */
                    $bbcs_i18n_ipv4v6_text = __('<strong>IPv4 / IPv6 Lists:</strong> Import allow/block lists on the %1$s and %2$s tabs, or download sample templates.', 'botblocker-security');
                    ?>
                    <p class="mb-2 bbcs-rules-guide"><?php echo wp_kses_post(sprintf($bbcs_i18n_ipv4v6_text, $bbcs_l_ipv4, $bbcs_l_ipv6)); ?></p>

                    <?php
                    /* translators: %s: Link to the Rules tab URL. */
                    $bbcs_i18n_rules_text = __('<strong>Rules:</strong> Create rules in %s for IP, User-Agent, ASN, country, referrer, path, cookie, header, request method, or bot score.', 'botblocker-security');
                    ?>
                    <p class="mb-2 bbcs-rules-guide"><?php echo wp_kses_post(sprintf($bbcs_i18n_rules_text, $bbcs_l_rules)); ?></p>

                    <?php
                    /* translators: %s: Link to the White Bots tab URL. */
                    $bbcs_i18n_white_text = __('<strong>White Bots:</strong> Add trusted crawler domains (search engines, social networks, uptime monitors) under %s.', 'botblocker-security');
                    ?>
                    <p class="mb-2 bbcs-rules-guide"><?php echo wp_kses_post(sprintf($bbcs_i18n_white_text, $bbcs_l_white)); ?></p>

                    <?php
                    /* translators: %s: Link to the Proxy tab URL. */
                    $bbcs_i18n_proxy_text = __('<strong>Proxy:</strong> Add network masks or header patterns (e.g., Cloudflare ranges) in %s.', 'botblocker-security');
                    ?>
                    <p class="mb-0 bbcs-rules-guide"><?php echo wp_kses_post(sprintf($bbcs_i18n_proxy_text, $bbcs_l_proxy)); ?></p>
                </div>
            </section>

            <section class="card">
                <header class="card-header">
                    <div class="card-actions">
                    </div>
                    <h2 class="card-title"><?php esc_html_e('Rules and IP lists statistics', 'botblocker-security'); ?></h2>
                </header>
                <div class="card-body">
                    <?php echo do_shortcode('[botblocker_rules_stats show_chart="yes" chart_height="200"]'); ?>
                </div>
            </section>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-3 сol-lg-3 col-xl-3 col-xxl-2 mb-1">
            <?php include('botblocker-section-right-sidebar.php'); ?>
        </div>
    </div>

    <?php
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-edit.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-add.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-ipv4-edit.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-ipv4-add.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-ipv6-edit.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-ipv6-add.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-path-edit.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-path-add.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-white-edit.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-white-add.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-proxy-edit.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-proxy-add.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-asn-edit.php';
    include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-asn-add.php';
    ?>