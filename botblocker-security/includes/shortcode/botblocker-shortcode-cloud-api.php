<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Deprecated: Shortcode to generate the price list from Cloud API
// Delete in 1.7.0 version

/*
function bbcs_generatePriceList() {
    $cache_key = 'bbcs_price_list';
    if (BOTBLOCKER_CACHE_NEWS) {
        $cached_data = get_transient($cache_key);
        if ($cached_data) {
            return $cached_data;
        }
    }

    $cloud_api_endpoint = BOTBLOCKER_CLOUD_API_ENDPOINT;
    $domain = BOTBLOCKER_SITE_URL;
    $api_key = md5(BOTBLOCKER_SITE_NAME);
    $email = wp_get_current_user()->user_email;

        $args = [
            'method'    => 'GET', 
            'timeout'   => 10,
            'sslverify' => true,
        ];

        $response = wp_remote_post( BOTBLOCKER_PRICE_URL, $args );

        if ( is_wp_error( $response ) ) {
            $data = [];
        } else {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
        }

    $output = '<section class="card bbcs-price-item me-1">
        <div class="card-body bbcs-card-body">
            <h2 class="bbcs-price">' . esc_html__('FREE', 'botblocker-security') . '<span class="bbcs-price-duration"></span></h2>
            <p class="bbcs-description">' . esc_html__('Free Plan (Active)', 'botblocker-security') . '</p>
            <hr class="bbcs-divider">
            <ul class="bbcs-features">
                <li>' . esc_html__('Period: Unlimited', 'botblocker-security') . '</li>
                <li>' . esc_html__('Core protection included', 'botblocker-security') . '</li>
            </ul>
            <a class="bbcs-btn-primary-active" style="pointer-events:none;cursor:default;">' . esc_html__('Active', 'botblocker-security') . '</a>
        </div>
    </section>';

    if ($data && isset($data['success']) && $data['success'] === true) {
        $total_products = count($data['data']);
        $counter = 0;

        foreach ($data['data'] as $product) {
            $counter++;
            $product_id = $product['product_id'];
            $name = $product['name'];
            $cloud_api_duration = $product['license_duration'];
            $max_requests = (int)$product['max_requests'];
            $price = $product['price'];
            $buy_link = $cloud_api_endpoint . '?domain=' . $domain . '&api_key=' . $api_key . '&email=' . $email . '&tariff=' . $product_id;

            $card_class = ($counter === $total_products) ? 'card bbcs-price-item' : 'card bbcs-price-item me-1';

            $output .= '
            <section class="' . esc_attr($card_class) . '">
                <div class="card-body bbcs-card-body">
                    <h2 class="bbcs-price">$' . esc_html($price) . ' <span class="bbcs-price-duration">/ mo</span></h2>
                    <p class="bbcs-description">' . esc_html($name) . '</p>
                    <hr class="bbcs-divider">
                    <ul class="bbcs-features">
                        <li>Period: ' . esc_html($cloud_api_duration) . ' days</li>
                        <li>Cloud requests: ' . esc_html(number_format($max_requests)) . '</li>
                    </ul>
                    <a href="' . esc_url($buy_link) . '" class="bbcs-btn-primary" target="_blank">'. esc_html__('Buy Now', 'botblocker-security') .'</a>
                </div>
            </section>';
        }
    } else {
        $output .= '<p>'. esc_html__('Error: Could not fetch product data.', 'botblocker-security') .'</p>';
    }

    if (BOTBLOCKER_CACHE_NEWS) {
        set_transient($cache_key, $output, BOTBLOCKER_CACHE_NEWS_TIME);
    }

    return $output;
}
add_shortcode('bbcs_price_list', 'bbcs_generatePriceList');
*/