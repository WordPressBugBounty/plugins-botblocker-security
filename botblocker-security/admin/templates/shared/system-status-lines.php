<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( BotBlockerSystemInfoData $si ): void {
	?>
	<?php echo esc_html__( 'OS:', 'botblocker-security' ) . ' ' . esc_html( $si->os ); ?><br/>
	<?php echo esc_html__( 'Web:', 'botblocker-security' ) . ' ' . esc_html( $si->web ); ?><br/>
	<?php echo esc_html__( 'DB: v', 'botblocker-security' ) . esc_html( $si->db_version ); ?><br/>
	<?php echo esc_html__( 'PHP: v', 'botblocker-security' ) . esc_html( $si->php ); ?><br/>
	<?php echo esc_html__( 'WordPress: v', 'botblocker-security' ) . esc_html( $si->wp ); ?><br/>
	<?php echo esc_html__( 'BotBlocker: v', 'botblocker-security' ) . esc_html( $si->bb_version ); ?>
	<br/><?php echo esc_html__( 'PHP vars:', 'botblocker-security' ); ?><br/>
	<?php echo 'memory_limit: ' . esc_html( $si->memory ); ?><br/>
	<?php echo 'max_execution_time: ' . esc_html( $si->max_exec ); ?><br/>
	<?php echo 'post_max_size: ' . esc_html( $si->post_max ); ?><br/>
	<?php echo 'upload_max_filesize: ' . esc_html( $si->upload_max ); ?>
	<?php
};
