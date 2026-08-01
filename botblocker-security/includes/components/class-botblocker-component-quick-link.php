<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QuickLink extends Base {
	protected $url = '';
	protected $title = '';
	protected $sub = '';
	protected $icon = '';
	protected $acc = '';

	public function withUrl( string $url ): self {
		$this->url = $url;
		return $this;
	}

	public function withTitle( string $title ): self {
		$this->title = $title;
		return $this;
	}

	public function withSub( string $sub ): self {
		$this->sub = $sub;
		return $this;
	}

	public function withIcon( string $icon ): self {
		$this->icon = $icon;
		return $this;
	}

	public function withAcc( string $acc ): self {
		$this->acc = $acc;
		return $this;
	}

	public function cssClasses( string $base = '' ): array {
		return array_merge(
			parent::cssClasses( $base ),
			$this->class !== '' ? array( $this->class ) : array()
		);
	}

	public function render( bool $return = false ): string {
		$html = '<a class="' . self::classes( array( 'bbcs-card', 'bbcs-ql', 'bbcs-acc-' . $this->acc, $this->class ) ) . '" href="' . self::escape( $this->url, 'url' ) . '">';
		$html .= '<span class="bbcs-ql-ic"><svg class="bbcs-ico"><use href="#bbcs-i-' . self::escape( $this->icon, 'attr' ) . '"></use></svg></span>';
		$html .= '<div class="bbcs-ql-body">';
		$html .= '<span class="bbcs-ql-title">' . self::escape( $this->title ) . '</span>';
		$html .= '<span class="bbcs-ql-sub">' . self::escape( $this->sub ) . '</span>';
		$html .= '<span class="bbcs-dim bbcs-ql-arr"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg></span>';
		$html .= '</div></a>';

		return self::output( $html, $return );
	}
}
