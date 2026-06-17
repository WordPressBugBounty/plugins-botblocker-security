<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
 
<h3 class="bbcs_guide_h3"><?php esc_html_e( 'Security Health Status', 'botblocker-security' ); ?></h3>
<hr class="bbcs-guide-hr">

<div class="bbcs-guide-row mb-4">
	<div class="bbcs-vertical-stack mb-2">
		<?php
			echo do_shortcode( '[bbcs_health_gauge id="health_gauge" value="' . bbcs_calculateSiteHealth() . '" max="100" ]' );
		?>
		<div class="d-flex justify-content-evenly">
			<a href="#" class="btn btn-sm bbcs-btn-primary-cta rounded-5" id="bbcsOpenOneClickSetup">
				<i class="fa-solid fa-wand-magic-sparkles"></i>
				<?php esc_html_e( 'One‑Click Setup', 'botblocker-security' ); ?>
			</a>
			<!--
			<a href="<?php //echo esc_url($BBCSA->pages_settings); ?>" class="btn btn-xs btn-default"><i class="fa-solid fa-gear"></i>&nbsp;
				<?php //esc_html_e('Go to Settings' ,'botblocker-security'); ?>
			</a>  --> 
		</div> 
	</div>

		<?php echo do_shortcode( '[bbcs_health_full cols="3"]' ); ?>
</div>
