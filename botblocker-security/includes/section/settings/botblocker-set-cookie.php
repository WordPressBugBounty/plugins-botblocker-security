<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="tab-pane container fade show active" id="cookie">
    <div class="row">

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
            <div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                <img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/cookie.svg'); ?>"
                    alt="<?php esc_attr_e('Cookie Settings', 'botblocker-security'); ?>" class="img-fluid bbcs-info-image mb-3">

                <p class="bbcs-info-text">
                    <?php esc_html_e('Control BotBlocker verification cookies: name, lifetime, salt, and security attributes.', 'botblocker-security'); ?>
                </p>
                <p class="bbcs-info-text">
                    <?php esc_html_e('SameSite policy controls cross-site cookie behavior to protect against CSRF attacks.', 'botblocker-security'); ?>
                </p>
                <hr class="bbcs-info-hr">
                <div class="bbcs-info-footer">
                    <i class="fa-regular fa-circle-question"></i>
                    <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/what-data-does-botblocker-collect-and-how-is-it-stored-and-deleted/" target="_blank"
                        class="bbcs-info-footer-a"><?php esc_html_e('What Data Does BotBlocker Collect', 'botblocker-security'); ?></a>
                    <a href="https://en.wikipedia.org/wiki/HTTP_cookie" target="_blank"
                        class="bbcs-info-footer-a"><?php esc_html_e('Cookies', 'botblocker-security'); ?></a>
                    <a href="https://en.wikipedia.org/wiki/Salt_(cryptography)" target="_blank"
                        class="bbcs-info-footer-a"><?php esc_html_e('Salt', 'botblocker-security'); ?></a>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <h3 class="bbcs_settings_h3"><?php esc_html_e('Cookies Settings', 'botblocker-security'); ?></h3>
            <div class="bbcs_text_input mb-2">
                <div class="bbcs_label_input_box">
                    <span class="bbcs-label-input"><?php esc_html_e('Cookie Name', 'botblocker-security'); ?></span>
                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top"
                        data-bs-original-title="<?php esc_attr_e('Cookie name used by BotBlocker. Changing it resets all existing cookies.', 'botblocker-security'); ?>">
                    </i>
                </div>
                <div class="bbcs_text_input_inner">
                    <input type="text" class="bbcs_text_input_input" name="cookie"
                        value="<?php echo isset($bbcs_settings['cookie']) ? esc_html($bbcs_settings['cookie']) : 'BotBlocker'; ?>">
                </div>
            </div>
            <div class="bbcs_text_input mb-2">
                <div class="bbcs_label_input_box">
                    <span class="bbcs-label-input"><?php esc_html_e('Cookie SameSite Policy', 'botblocker-security'); ?></span>
                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top"
                        data-bs-original-title="<?php esc_attr_e('Set SameSite attribute (Lax, Strict, None).', 'botblocker-security'); ?>"></i>
                </div>
                <div class="bbcs_text_input_inner">
                    <select class="bbcs_select_input_input" name="samesite">
                        <option value="Lax"
                            <?php selected('Lax', isset($bbcs_settings['samesite']) ? $bbcs_settings['samesite'] : 'Lax'); ?>>
                            <?php esc_html_e('Lax', 'botblocker-security'); ?></option>
                        <option value="Strict"
                            <?php selected('Strict', isset($bbcs_settings['samesite']) ? $bbcs_settings['samesite'] : 'Lax'); ?>>
                            <?php esc_html_e('Strict', 'botblocker-security'); ?></option>
                        <option value="None"
                            <?php selected('None', isset($bbcs_settings['samesite']) ? $bbcs_settings['samesite'] : 'Lax'); ?>>
                            <?php esc_html_e('None', 'botblocker-security'); ?></option>
                    </select>
                </div>
            </div>

            <div class="bbcs_select_input mb-2">
                <div class="bbcs_label_input_box">
                    <span class="bbcs-label-input"><?php esc_html_e('Cookie Lifetime', 'botblocker-security'); ?></span>
                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top"
                        data-bs-original-title="<?php esc_attr_e('How long the verification cookie remains valid.', 'botblocker-security'); ?>"></i>
                </div>
                <div class="bbcs_text_input_inner">
                    <select class="bbcs_select_input_input" name="cookie_lifetime">
                        <?php foreach (bbcs_get_cookie_lifetimes() as $bbcs_seconds => $bbcs_label) : ?>
                        <option value="<?php echo esc_attr($bbcs_seconds); ?>"
                            <?php selected($bbcs_seconds, isset($bbcs_settings['cookie_lifetime']) ? $bbcs_settings['cookie_lifetime'] : 86400); ?>>
                            <?php echo esc_html($bbcs_label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="bbcs_text_input mb-2">
                <div class="bbcs_label_input_box">
                    <span class="bbcs-label-input"><?php esc_html_e('Salt', 'botblocker-security'); ?></span>
                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top"
                        data-bs-original-title="<?php esc_attr_e('Random string that makes cookie values unpredictable.', 'botblocker-security'); ?>"></i>
                </div>
                <div class="bbcs_text_input_inner">
                    <input type="text" class="bbcs_text_input_input" name="salt"
                        value="<?php echo isset($bbcs_settings['salt']) ? esc_html($bbcs_settings['salt']) : ''; ?>">
                </div>
            </div>

            <h3 class="bbcs_settings_h3"><?php esc_html_e('Cache Compatibility', 'botblocker-security'); ?></h3>
            <div class="bbcs_text_input mb-2">
                <div class="bbcs_label_input_box">
                    <span class="bbcs-label-input"><?php esc_html_e('Cloud API Timeout', 'botblocker-security'); ?></span>
                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top"
                        data-bs-original-title="<?php esc_attr_e('Timeout in seconds for cloud API requests. Increase if your server has slow outbound connections.', 'botblocker-security'); ?>"></i>
                </div>
                <div class="bbcs_text_input_inner">
                    <select class="bbcs_select_input_input" name="cloud_api_timeout">
                        <?php
                        $timeout_options = [2 => '2s', 3 => '3s', 5 => '5s (default)', 7 => '7s', 10 => '10s', 15 => '15s'];
                        $current_timeout = isset($bbcs_settings['cloud_api_timeout']) ? (int) $bbcs_settings['cloud_api_timeout'] : 5;
                        foreach ($timeout_options as $val => $label) : ?>
                        <option value="<?php echo esc_attr($val); ?>"
                            <?php selected($val, $current_timeout); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="bbcs_text_input mb-2">
                <div class="bbcs_label_input_box">
                    <span class="bbcs-label-input"><?php esc_html_e('Send Vary: Cookie Header', 'botblocker-security'); ?></span>
                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true"
                        data-bs-placement="top"
                        data-bs-original-title="<?php esc_attr_e('Add Vary: Cookie header for CDN compatibility. May reduce cache hit ratio.', 'botblocker-security'); ?>"></i>
                </div>
                <div class="bbcs_text_input_inner">
                    <select class="bbcs_select_input_input" name="vary_cookie">
                        <option value="0"
                            <?php selected(0, isset($bbcs_settings['vary_cookie']) ? (int)$bbcs_settings['vary_cookie'] : 0); ?>>
                            <?php esc_html_e('Disabled', 'botblocker-security'); ?></option>
                        <option value="1"
                            <?php selected(1, isset($bbcs_settings['vary_cookie']) ? (int)$bbcs_settings['vary_cookie'] : 0); ?>>
                            <?php esc_html_e('Enabled', 'botblocker-security'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
