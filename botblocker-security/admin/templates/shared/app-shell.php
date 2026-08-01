<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_Layout_View $layout, string $data_page, callable $content ): void {
	?>
<div class="bbcs-app">
  <div class="bbcs-content">
	<?php $layout->header(); ?>
    <div class="bbcs-scroll">
      <div class="bbcs-wrap">
        <section class="bbcs-page" data-page="<?php echo esc_attr( $data_page ); ?>">
			<?php
			$GLOBALS['bbcs_suppress_legacy_layout'] = true;
			$content( $layout );
			$GLOBALS['bbcs_suppress_legacy_layout'] = false;
			?>
        </section>
      </div>
    </div>
  </div>
</div>
	<?php
};
