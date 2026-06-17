<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$bbcs_has_pro = BotBlockerPro::isActive();
?>
 
<div class="tab-pane container fade" id="connect_types"> 
	<div class="row">

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/connections-types.svg' ); ?>" 
					alt="<?php esc_attr_e( 'Connection Types', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Connection filtering blocks suspicious connection methods used by bots and scrapers.', 'botblocker-security' ); ?>				
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Restrict proxy servers, data centers, VPNs, and legacy protocols to reduce automated threats.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-an-ip-address-understanding-ipv4-and-ipv6/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'IP protocols', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-asn-how-autonomous-systems-help-identify-threats-to-your-website/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'ASN', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-http-understanding-protocol-versions-and-blocking-http-1-0-in-botblocker/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'HTTP', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/hosting-detection-why-botblocker-identifies-hosting-providers-and-what-it-means-for-security/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Detect hosting', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-a-vpn-how-virtual-private-networks-work-and-why-they-matter/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'VPN', 'botblocker-security' ); ?></a>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/what-is-tor-how-botblocker-detects-and-blocks-connections-from-the-tor-network/" target="_blank" class="bbcs-info-footer-a"><?php esc_html_e( 'Tor', 'botblocker-security' ); ?></a>
				</div>				
			</div>
		</div>

		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Connection Types', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_proxy_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_proxy_users'] ) ? $bbcs_settings['block_proxy_users'] : 0 ); ?>>        			
						<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Classic Proxy', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Block HTTP proxy IP ranges.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_cf_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_cf_users'] ) ? $bbcs_settings['block_cf_users'] : 0 ); ?>>        			
						<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Cloudflare Origin IPs', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Block unauthenticated requests from Cloudflare IPs.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_ipv6_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_ipv6_users'] ) ? $bbcs_settings['block_ipv6_users'] : 0 ); ?>>        			
						<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'IPv6 Connections', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Block access via IPv6 protocol.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_http10_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_http10_users'] ) ? $bbcs_settings['block_http10_users'] : 0 ); ?>>        			
						<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'HTTP/1.0 Protocol', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Block legacy HTTP/1.0 protocol.', 'botblocker-security' ); ?>">
				</i>
			</div>


		<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Extra Connection Types', 'botblocker-security' ); ?></h3>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="hosting_block" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['hosting_block'] ) ? $bbcs_settings['hosting_block'] : 0 ); ?>
						<?php
						if ( ! $bbcs_has_pro ) {
							echo 'disabled';}
						?>
						>
					<span class="bbcs-cloud-api-column">
						<span class="bbcs_label_input_checkbox bbcs-cloud-api-color"><?php esc_html_e( 'Hosting Provider IPs', 'botblocker-security' ); ?></span>
					<small class="text-muted bbcs-ps-5" <?php echo $bbcs_has_pro ? 'hidden' : ''; ?>>
							<?php esc_html_e( 'PRO option', 'botblocker-security' ); ?> (<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>"><?php esc_html_e( 'Connect now!', 'botblocker-security' ); ?></a>)
						</small>
					</span>
				</div>
				<i class="fa-regular fa-circle-question" 
				data-bs-toggle="tooltip" 
				data-bs-html="true"  
				data-bs-placement="top" 
				data-bs-original-title="<?php esc_attr_e( 'Block data center IPs (VPS, AWS, DigitalOcean, etc.). Search engines are always whitelisted.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_vpn_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_vpn_users'] ) ? $bbcs_settings['block_vpn_users'] : 0 ); ?>
						<?php
						if ( ! $bbcs_has_pro ) {
							echo 'disabled';}
						?>
						>
					<span class="bbcs-cloud-api-column">        			
						<span class="bbcs_label_input_checkbox bbcs-cloud-api-color"><?php esc_html_e( 'VPN Connections', 'botblocker-security' ); ?></span>
					<small class="text-muted bbcs-ps-5" <?php echo $bbcs_has_pro ? 'hidden' : ''; ?>>
							<?php esc_html_e( 'PRO option', 'botblocker-security' ); ?> (<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>"><?php esc_html_e( 'Connect now!', 'botblocker-security' ); ?></a>)
						</small>
					</span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Block traffic from known VPN service IP addresses.', 'botblocker-security' ); ?>">
				</i>
			</div>
			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="block_tor_users" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['block_tor_users'] ) ? $bbcs_settings['block_tor_users'] : 0 ); ?>
						<?php
						if ( ! $bbcs_has_pro ) {
							echo 'disabled';}
						?>
						>
						<span class="bbcs-cloud-api-column">        			
							<span class="bbcs_label_input_checkbox bbcs-cloud-api-color"><?php esc_html_e( 'Tor Exit Nodes', 'botblocker-security' ); ?></span>
					<small class="text-muted bbcs-ps-5" <?php echo $bbcs_has_pro ? 'hidden' : ''; ?>>
							<?php esc_html_e( 'PRO option', 'botblocker-security' ); ?> (<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>"><?php esc_html_e( 'Connect now!', 'botblocker-security' ); ?></a>)
						</small>
					</span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Block traffic from known Tor exit nodes.', 'botblocker-security' ); ?>">
				</i>
			</div>

			<h3 class="bbcs_settings_h3"><?php esc_html_e( 'Self Connections', 'botblocker-security' ); ?></h3>
			<div class="bbcs_checkbox_input mb-2">
				<div class="bbcs_label_checkbox_box">
					<input type="checkbox" name="allow_self_ip_req" class="bbcs_checkbox_input_input" value="1"
						<?php checked( 1, isset( $bbcs_settings['allow_self_ip_req'] ) ? $bbcs_settings['allow_self_ip_req'] : 0 ); ?>>        			
						<span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Allow requests from your server IP', 'botblocker-security' ); ?></span>
				</div>
				<i class="fa-regular fa-circle-question"
					data-bs-toggle="tooltip" data-bs-html="true" 
					data-bs-placement="top"
					data-bs-original-title="<?php esc_attr_e( 'Allow your server IP to bypass security checks for updates and automated tasks.', 'botblocker-security' ); ?>">
				</i>
			</div>

		</div>
	</div>
</div>
