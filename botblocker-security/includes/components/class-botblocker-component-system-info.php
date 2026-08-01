<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-system-info-data.php';

final class SystemInfo extends Base {
	protected $info;

	public function __construct() {
		$this->info = \BotBlockerSystemInfoData::getInstance();
	}

	public function withInfo( \BotBlockerSystemInfoData $info ): self {
		$this->info = $info;
		return $this;
	}

	public function render( bool $return = false ): string {
		$i = $this->info;

		$html  = '<pre class="bbcs_pre">';
		$html .= 'OS: ' . esc_html( $i->os ) . "\n\n";
		$html .= 'Web: ' . esc_html( $i->web ) . "\n\n";
		$html .= 'DB v.' . esc_html( $i->db_version ) . "\n\n";
		$html .= 'PHP v.' . esc_html( $i->php ) . "\n\n";
		$html .= 'WordPress v.' . esc_html( $i->wp ) . "\n\n";
		$html .= 'BotBlocker v.' . esc_html( $i->bb_version ) . "\n\n";
		$html .= "PHP vars:\n\n";
		$html .= 'memory_limit: ' . esc_html( $i->memory ) . "\n\n";
		$html .= 'max_execution_time: ' . esc_html( $i->max_exec ) . "\n\n";
		$html .= 'post_max_size: ' . esc_html( $i->post_max ) . "\n\n";
		$html .= 'upload_max_filesize: ' . esc_html( $i->upload_max ) . "\n\n";
		$html .= '</pre>';

		return self::output( $html, $return );
	}
}
