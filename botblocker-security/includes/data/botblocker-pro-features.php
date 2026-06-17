<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bbcs_get_pro_features' ) ) {
	function bbcs_get_pro_features(): array {
		return array(
			__( 'Cloud bot detection (live signatures)', 'botblocker-security' ),
			__( 'Behavioral & AI traffic analysis', 'botblocker-security' ),
			__( 'VPN, Tor and proxy blocking', 'botblocker-security' ),
			__( 'Hide login URL & admin path', 'botblocker-security' ),
			__( 'Security headers management (HSTS, CSP, X-Frame, Permissions-Policy)', 'botblocker-security' ),
			__( 'Early initialization - block before WordPress loads', 'botblocker-security' ),
			__( 'WordPress speed optimizations', 'botblocker-security' ),
			__( 'Threat intelligence & zero-day botnet feeds', 'botblocker-security' ),
			__( 'Daily signature updates (5M+ patterns)', 'botblocker-security' ),
			__( 'Auto-update of PTR and User-Agent databases', 'botblocker-security' ),
			__( 'Advanced reporting, analytics & forensics', 'botblocker-security' ),
			__( 'Custom security rules engine', 'botblocker-security' ),
			__( 'SEO bots whitelist management', 'botblocker-security' ),
			__( 'All premium add-ons included', 'botblocker-security' ),
			__( 'Priority support & emergency help (24h)', 'botblocker-security' ),
		);
	}
}

if ( ! function_exists( 'bbcs_get_pro_comparison' ) ) {
	function bbcs_get_pro_comparison(): array {
		return array(
			array(
				'feature' => __( 'Simple bot detection (UA, headers, JS, language)', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'IP, ASN, GEO and proxy rules', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Built-in CAPTCHA', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Logs & basic reports', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'reCAPTCHA, Redis, Memcached integrations', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Two-Factor Authentication (2FA)', 'botblocker-security' ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Cloud bot verification (live)', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Behavioral & AI analysis', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'VPN / Tor / proxy blocking', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Hide login URL & admin path', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Security headers management', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Early initialization (pre-WordPress block)', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'WordPress speed optimizations', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Daily signature updates & threat feeds', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Premium add-ons access', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Advanced analytics & forensics', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Priority support (24h response)', 'botblocker-security' ),
				'free'    => false,
				'pro'     => true,
			),
		);
	}
}

if ( ! function_exists( 'bbcs_render_pro_features_list' ) ) {
	function bbcs_render_pro_features_list( string $ul_class = 'bbcs-cloud-api-features' ): void {
		$features = bbcs_get_pro_features();
		echo '<ul class="' . esc_attr( $ul_class ) . '">';
		foreach ( $features as $f ) {
			echo '<li><i class="fa-solid fa-check me-1"></i>' . esc_html( $f ) . '</li>';
		}
		echo '</ul>';
	}
}

if ( ! function_exists( 'bbcs_render_pro_comparison_table' ) ) {
	function bbcs_render_pro_comparison_table(): void {
		$rows = bbcs_get_pro_comparison();
		?>
		<div class="table-responsive bbcs-pro-comparison-wrap">
			<table class="table table-sm bbcs-pro-comparison">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Feature', 'botblocker-security' ); ?></th>
						<th scope="col" class="text-center"><?php esc_html_e( 'Free', 'botblocker-security' ); ?></th>
						<th scope="col" class="text-center bbcs-cloud-api-color">
							<i class="fa-solid fa-crown"></i>&nbsp;<?php esc_html_e( 'PRO', 'botblocker-security' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $r['feature'] ); ?></td>
							<td class="text-center">
								<?php if ( $r['free'] ) : ?>
									<i class="fa-solid fa-check bbcs_color_green" aria-label="<?php esc_attr_e( 'Included', 'botblocker-security' ); ?>"></i>
								<?php else : ?>
									<i class="fa-solid fa-minus bbcs-text-muted" aria-label="<?php esc_attr_e( 'Not included', 'botblocker-security' ); ?>"></i>
								<?php endif; ?>
							</td>
							<td class="text-center">
								<?php if ( $r['pro'] ) : ?>
									<i class="fa-solid fa-check bbcs_color_green" aria-label="<?php esc_attr_e( 'Included', 'botblocker-security' ); ?>"></i>
								<?php else : ?>
									<i class="fa-solid fa-minus bbcs-text-muted" aria-label="<?php esc_attr_e( 'Not included', 'botblocker-security' ); ?>"></i>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
