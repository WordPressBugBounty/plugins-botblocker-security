<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LanguageOptionItem extends Base {
	protected $option;

	public function withOption( \Botblocker_LanguageOptionData $option ): self {
		$this->option = $option;
		return $this;
	}

	public function render( bool $return = false ): string {
		$option = $this->option;
		$lang   = esc_attr( $option->lang ?? '' );
		$flag   = esc_attr( $option->flag ?? '' );
		$name   = esc_html( $option->name ?? '' );

		$html = '<li>';
		$html .= '<a href="#" class="language-option" data-lang="' . $lang . '">';
		$html .= '<div class="flag flag-' . $flag . '"></div>';
		$html .= '<span class="title">' . $name . '</span>';
		$html .= '</a></li>';

		return self::output( $html, $return );
	}
}
