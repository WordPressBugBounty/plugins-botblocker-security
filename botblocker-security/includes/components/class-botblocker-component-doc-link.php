<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DocLink extends Base {

	private $url = '';

	private $label = '';

	public function withUrl( string $url ): self {
		$this->url = $url;
		return $this;
	}

	public function withLabel( string $label ): self {
		$this->label = $label;
		return $this;
	}

	public function render( bool $return = false ): string {
		if ( $this->url === '' ) {
			return self::output( '', $return );
		}

		$label = $this->label !== '' ? $this->label : __( 'Documentation', 'botblocker-security' );

		$html = '<a class="bbcs-doc-link" href="' . self::escape( $this->url, 'url' ) . '"'
			. ' target="_blank" rel="noopener noreferrer"'
			. ' aria-label="' . self::escape( $label, 'attr' ) . '"'
			. ' title="' . self::escape( $label, 'attr' ) . '">'
			. self::svg_icon( 'doc' )
			. '</a>';

		return self::output( $html, $return );
	}
}
