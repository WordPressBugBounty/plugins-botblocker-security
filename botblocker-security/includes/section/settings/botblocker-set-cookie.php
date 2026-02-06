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
                    <?php esc_html_e('Cookie configuration is essential for maintaining secure user sessions and protecting against bot attacks. BotBlocker uses advanced cookie-based verification to distinguish between legitimate users and automated threats. These settings control how the security cookies behave, including their lifetime, security attributes, and encryption parameters.', 'botblocker-security'); ?>
                </p>
                <p class="bbcs-info-text">
                    <?php esc_html_e('Proper cookie configuration ensures that verified users enjoy seamless browsing while suspicious traffic is continuously monitored. The salt value adds an extra layer of security by making cookie values unpredictable, while SameSite policies protect against cross-site request forgery attacks.', 'botblocker-security'); ?>
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
                        data-bs-original-title="<?php esc_attr_e('Set a unique name for the protection plugin cookie. If you change the cookie name, all previously set cookies will be reset.', 'botblocker-security'); ?>">
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
                        data-bs-original-title="<?php esc_attr_e('Defines SameSite attribute (Lax, Strict, None) for BotBlocker cookie.', 'botblocker-security'); ?>"></i>
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
                        data-bs-original-title="<?php esc_attr_e('Select the duration for the cookie’s lifetime.', 'botblocker-security'); ?>"></i>
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
                        data-bs-original-title="<?php esc_attr_e('Enter a unique salt value for added security.', 'botblocker-security'); ?>"></i>
                </div>
                <div class="bbcs_text_input_inner">
                    <input type="text" class="bbcs_text_input_input" name="salt"
                        value="<?php echo isset($bbcs_settings['salt']) ? esc_html($bbcs_settings['salt']) : ''; ?>">
                </div>
            </div>
        </div>
    </div>
</div>
