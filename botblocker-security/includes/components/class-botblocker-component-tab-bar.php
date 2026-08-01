<?php
declare(strict_types=1);

namespace BotBlocker\Component;

use BotBlocker\Component\TabItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TabBar extends Base {
	protected $tabs = array();

	public function withTabs( array $tabs ): self {
		$this->tabs = $tabs;
		return $this;
	}

	public function toHtmlAttrs(): array {
		return array_merge(
			array(
				'data-bbcs-tab-bar' => $this->id,
			),
			parent::toHtmlAttrs()
		);
	}

	public function cssClasses( string $base = '' ): array {
		return array_merge(
			parent::cssClasses( $base ),
			$this->class !== '' ? array( $this->class ) : array()
		);
	}

	public function render( bool $return = false ): string {
		$attrs = $this->toHtmlAttrs();

		$html = '<ul class="' . self::classes( $this->cssClasses( 'bbcs-tab-bar' ) ) . '"' . self::attrs( $attrs ) . '>';
		foreach ( $this->tabs as $tab ) {
			$id     = ltrim( $tab->id, '#' );
			$html  .= '<li class="' . self::classes( array( 'bbcs-tab-bar__item', $tab->item_class ) ) . '">';
			$html  .= '<a class="' . self::classes( array( 'bbcs-tab-bar__link', 'is-active' => $tab->active, $tab->class ) ) . '" data-bbcs-tab="' . self::escape( $id, 'attr' ) . '" href="' . self::escape( $tab->href, 'attr' ) . '">' . self::escape( $tab->label ) . '</a>';
			$html  .= '</li>';
		}
		$html .= '</ul>';

		return self::output( $html, $return );
	}
}
