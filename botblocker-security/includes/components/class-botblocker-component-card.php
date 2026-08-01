<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Card extends Base {
	protected $title = '';
	protected $title_suffix = '';
	protected $subtitle = '';
	protected $actions = '';
	protected $content = '';
	protected $badge = '';
	protected $pro_banner = false;

	public function withTitle( string $title ): self {
		$this->title = $title;
		return $this;
	}

	public function withActions( $actions ): self {
		$this->actions = $actions;
		return $this;
	}

	public function withContent( $content ): self {
		$this->content = $content;
		return $this;
	}

	public function withBadge( ?string $badge ): self {
		$this->badge = (string) $badge;
		return $this;
	}

	public function withTitleSuffix( string $html ): self {
		$this->title_suffix = $html;
		return $this;
	}

	public function withSubtitle( string $html ): self {
		$this->subtitle = $html;
		return $this;
	}

	public function withProState( bool $has_pro ): self {
		$this->pro_banner = ! $has_pro;
		return $this;
	}

	public function cssClasses( string $base = '' ): array {
		$classes = array( 'bbcs-card' );
		if ( $base !== '' ) {
			$classes[] = $base;
		}
		if ( $this->class !== '' ) {
			$classes[] = $this->class;
		}
		return $classes;
	}

	public function render( bool $return = false ): string {
		$title    = $this->title;
		$actions  = self::content( $this->actions );
		$content  = self::content( $this->content );

		$html = '<section class="' . self::classes( $this->cssClasses( 'bbcs-card-pad' ) ) . '">';
		if ( $title !== '' || $actions !== '' || $this->badge !== '' || $this->subtitle !== '' ) {
			$html .= '<div class="bbcs-section-head">';
			$html .= '<div class="bbcs-fill">';
			if ( $title !== '' ) {
				$html .= '<h2 class="bbcs-section-title">' . self::escape( $title );
				if ( $this->title_suffix !== '' ) {
					$html .= $this->title_suffix;
				}
				if ( $this->badge !== '' ) {
					$html .= $this->badge;
				}
				$html .= '</h2>';
			}
			if ( $this->subtitle !== '' ) {
				$html .= '<div class="bbcs-fs-sm bbcs-muted bbcs-mt-xs">' . $this->subtitle . '</div>';
			}
			$html .= '</div>';
			if ( $actions !== '' ) {
				$html .= '<div>' . $actions . '</div>';
			}
			$html .= '</div>';
		}
		$html .= '<div>' . $content . '</div></section>';

		return self::output( $html, $return );
	}
}
