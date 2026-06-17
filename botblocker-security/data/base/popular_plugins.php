<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Check if popular plugins are installed in WordPress
 *
 * @return array List of installed plugins
 */
function bbcs_checkPopularPlugins() {
	$plugins = array(
		'Akismet'                           => 'akismet/akismet.php',
		'Yoast SEO'                         => 'wordpress-seo/wp-seo.php',
		'Jetpack'                           => 'jetpack/jetpack.php',
		'WooCommerce'                       => 'woocommerce/woocommerce.php',
		'Contact Form 7'                    => 'contact-form-7/wp-contact-form-7.php',
		'Elementor'                         => 'elementor/elementor.php',
		'WPForms'                           => 'wpforms/wpforms.php',
		'UpdraftPlus'                       => 'updraftplus/updraftplus.php',
		'Wordfence'                         => 'wordfence/wordfence.php',
		'All in One SEO Pack'               => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
		'WP Super Cache'                    => 'wp-super-cache/wp-cache.php',
		'W3 Total Cache'                    => 'w3-total-cache/w3-total-cache.php',
		'Smush'                             => 'wp-smushit/wp-smush.php',
		'Redirection'                       => 'redirection/redirection.php',
		'Broken Link Checker'               => 'broken-link-checker/broken-link-checker.php',
		'Contact Form by WPForms'           => 'wpforms-lite/wpforms.php',
		'Ninja Forms'                       => 'ninja-forms/ninja-forms.php',
		'Mailchimp for WordPress'           => 'mailchimp-for-wp/mailchimp-for-wp.php',
		'Advanced Custom Fields'            => 'advanced-custom-fields/acf.php',
		'Yoast Duplicate Post'              => 'duplicate-post/duplicate-post.php',
		'Classic Editor'                    => 'classic-editor/classic-editor.php',
		'Google Analytics for WordPress'    => 'google-analytics-for-wordpress/googleanalytics.php',
		'WP Mail SMTP'                      => 'wp-mail-smtp/wp_mail_smtp.php',
		'TablePress'                        => 'tablepress/tablepress.php',
		'Really Simple SSL'                 => 'really-simple-ssl/really-simple-ssl.php',
		'Cookie Notice'                     => 'cookie-notice/cookie-notice.php',
		'WP-PageNavi'                       => 'wp-pagenavi/wp-pagenavi.php',
		'WP Rocket'                         => 'wp-rocket/wp-rocket.php',
		'WPML'                              => 'sitepress-multilingual-cms/sitepress.php',
		'NextGEN Gallery'                   => 'nextgen-gallery/nggallery.php',
		'WP User Avatar'                    => 'wp-user-avatar/wp-user-avatar.php',
		'WP-PostViews'                      => 'wp-postviews/wp-postviews.php',
		'WP Fastest Cache'                  => 'wp-fastest-cache/wpFastestCache.php',
		'Jetpack by WordPress.com'          => 'jetpack/jetpack.php',
		'All In One WP Security & Firewall' => 'all-in-one-wp-security-and-firewall/wp-security.php',
		'WP Statistics'                     => 'wp-statistics/wp-statistics.php',
		'WP Google Maps'                    => 'wp-google-maps/wpGoogleMaps.php',
		'WP Maintenance Mode'               => 'wp-maintenance-mode/wp-maintenance-mode.php',
		'WP File Manager'                   => 'wp-file-manager/wp-file-manager.php',
		'WP-Optimize'                       => 'wp-optimize/wp-optimize.php',
		'WP Migrate DB'                     => 'wp-migrate-db/wp-migrate-db.php',
		'WP Content Copy Protection'        => 'wp-content-copy-protection/wp-content-copy-protection.php',
		'WP RSS Aggregator'                 => 'wp-rss-aggregator/wp-rss-aggregator.php',
		'WP Live Chat Support'              => 'wp-live-chat-support/wp-live-chat-support.php',
		'WP Google Fonts'                   => 'wp-google-fonts/wp-google-fonts.php',
		'WP-PostRatings'                    => 'wp-postratings/wp-postratings.php',
	);

	$installedPlugins = array();

	foreach ( $plugins as $name => $pluginFile ) {
		if ( is_plugin_active( $pluginFile ) || ( is_multisite() && is_plugin_active_for_network( $pluginFile ) ) ) {
			$installedPlugins[] = $name;
		}
	}

	return $installedPlugins;
}
