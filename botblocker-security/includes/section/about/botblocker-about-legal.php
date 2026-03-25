<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="row">
        <div class="col-xxl-6 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">
            <div class="bbcs-info-inner">
				<?php
                // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                <img src="<?php echo esc_url(BOTBLOCKER_URL . 'public/icons/copyright.svg'); ?>"
                    alt="<?php esc_attr_e('Copyright', 'botblocker-security'); ?>" class="img-fluid bbcs-info-image mb-3">

                <p class="bbcs-info-text">
                    <?php esc_html_e('BotBlocker uses third-party libraries selected for security, compatibility, and open-source license compliance.', 'botblocker-security'); ?>
                </p>
                <p class="bbcs-info-text">
                    <?php esc_html_e('Libraries and resources used in BotBlocker, with licenses and sources.', 'botblocker-security'); ?>
                </p>
                
                <hr class="bbcs-info-hr">
                <div class="bbcs-info-footer">
                    <i class="fa-regular fa-circle-question"></i>
                    <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/privacy-policy/" target="_blank" 
                        class="bbcs-info-footer-a"><?php esc_html_e('Privacy policy', 'botblocker-security'); ?></a>
                    <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/terms-of-service/" target="_blank" 
                        class="bbcs-info-footer-a"><?php esc_html_e('Terms of Service', 'botblocker-security'); ?></a>   
                    <a href="<?php echo esc_url(BOTBLOCKER_DOCS_URL)?>/refund_returns/" target="_blank" 
                        class="bbcs-info-footer-a"><?php esc_html_e('Refund', 'botblocker-security'); ?></a>                                              
                </div>
            </div>
        </div>

        <div class="col-xxl-6 col-xl-6 col-lg-6 col-sm-12 col-md-12">
            <div class="botblocker-legal-section">
                <h3 class="bbcs_settings_h3"><?php esc_html_e('Third-party', 'botblocker-security'); ?></h3>

                <h4 class="bbcs_settings_h4">Icons:</h4>
                <ul class="bbcs_settings_ul">
                    <li class="bbcs_settings_li"><strong class="bbcs_settings_strong">Font Awesome</strong> - Licensed under the <a
                            href="https://fontawesome.com/license/free" target="_blank">Font Awesome Free License</a>.
                    </li>
                </ul>

                <h4 class="bbcs_settings_h4">CSS Framework:</h4>
                <ul class="bbcs_settings_ul">
                    <li class="bbcs_settings_li"><strong class="bbcs_settings_strong">Bootstrap CSS Framework</strong> - Licensed under the <a
                            href="https://getbootstrap.com/docs/5.3/about/license/" target="_blank">MIT License</a>.
                    </li>
                </ul>

                <h4 class="bbcs_settings_h4">JavaScript Library:</h4>
                <ul class="bbcs_settings_ul">
                    <li class="bbcs_settings_li"><strong class="bbcs_settings_strong">jQuery</strong> - Licensed under the <a href="https://jquery.org/license/"
                            target="_blank">MIT License</a>.</li>
                </ul>

                <h4 class="bbcs_settings_h4">Images and Icons:</h4>
                <ul class="bbcs_settings_ul">
                    <li class="bbcs_settings_li"><strong class="bbcs_settings_strong">SVG Repo</strong> - Licensed under <a href="https://www.svgrepo.com" target="_blank">CC0
                            Public Domain Dedication</a>.</li>
                    <li class="bbcs_settings_li"><strong class="bbcs_settings_strong">Flaticon Icons</strong> - Icons designed by <a href="https://www.freepik.com"
                            target="_blank">Freepik</a> from <a href="https://www.flaticon.com"
                            target="_blank">Flaticon</a> (used with attribution).</li>
                </ul>

                <h4 class="bbcs_settings_h4">Geolocation Database:</h4>
                <ul class="bbcs_settings_ul">
                    <li class="bbcs_settings_li"><strong class="bbcs_settings_strong">SypexGeo Lite</strong> - Licensed under BSD License, available at <a
                            href="https://sypexgeo.net" target="_blank">sypexgeo.net</a>.</li>
                </ul>

                <h4 class="bbcs_settings_h4">Device Detection:</h4>
                <ul class="bbcs_settings_ul">
                    <li class="bbcs_settings_li"><strong class="bbcs_settings_strong">Mobile Detect Library</strong> - Licensed under the <a
                            href="https://github.com/serbanghita/Mobile-Detect" target="_blank">MIT License</a>.</li>
                </ul>

            </div>

        </div>
    </div>
