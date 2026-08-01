<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SettingsGroup - Renders the bbcs-setgroup section wrapper used in settings and tools tabs.
 *
 * Pattern: section heading + items (via callback) + optional info/status block.
 */
final class SettingsGroup extends Base {

	/** @var string Section heading text (already translated). */
	private $title = '';

	/** @var callable|null Callback that renders the group's items. */
	private $items = null;

	/** @var string Optional info/status text (already formatted). */
	private $info = '';

	public function withTitle( string $title ): self {
		$this->title = $title;
		return $this;
	}

	public function withItems( callable $items ): self {
		$this->items = $items;
		return $this;
	}

	public function withInfo( string $info ): self {
		$this->info = $info;
		return $this;
	}

	public function render( bool $return = false ): string {
		$html = '<div class="bbcs-setgroup">';

		if ( $this->title !== '' ) {
			$html .= '<div class="bbcs-setgroup-head">' . self::escape( $this->title ) . '</div>';
		}

		if ( is_callable( $this->items ) ) {
			$html .= self::content( $this->items );
		}

		$html .= '</div>';

		if ( $this->info !== '' ) {
			$html .= '<div class="bbcs-settings-info">' . $this->info . '</div>';
		}

		return self::output( $html, $return );
	}
}
