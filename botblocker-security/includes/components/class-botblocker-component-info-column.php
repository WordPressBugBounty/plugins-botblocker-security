<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * InfoColumn - Renders the bbcs-infocol header section used in settings and tools tab panels.
 *
 * Pattern: optional title + icon SVG + one or more description paragraphs + documentation links.
 *
 * Two icon styles are supported:
 *   - Simple sprite (settings tabs):  ->withIcon( 'bot' )
 *   - Full-featured (tools tabs):     ->withIconFull( 'wordpress-core' )
 */
final class InfoColumn extends Base {
	const NOTE_WARN   = 'warn';
	const NOTE_SUCCESS = 'success';
	const NOTE_INFO    = 'info';

	const ALLOWED_NOTE_TYPES = array(
		self::NOTE_WARN,
		self::NOTE_SUCCESS,
		self::NOTE_INFO,
	);

	/** @var string Custom SVG icon HTML - takes priority over all other icon methods if set. */
	private $icon_html = '';

	/** @var string SVG sprite icon name (without #bbcs-i- prefix). */
	private $icon_name = '';

	/** @var bool Whether to render the tools-style full SVG with explicit dimensions and stroke attrs. */
	private $icon_full = false;

	/** @var string URL to an old-style SVG image file (rendered as <img> tag). */
	private $icon_image_url = '';

	/** @var string Alt text for the old-style <img> icon. */
	private $icon_image_alt = '';

	/** @var string[] Description paragraphs (already translated). */
	private $descriptions = array();

	/** @var string Title heading text (already translated). */
	private $title = '';

	/** @var array<array{url: string, label: string}> Documentation links. */
	private $doc_links = array();

	/**
	 * @var array<array{content: string|callable, type: string}> Notes displayed as bbcs-infocol-note blocks.
	 */
	private $notes = array();

	/**
	 * Set the icon using a standard sprite reference (settings style).
	 * Renders: <svg class="bbcs-ico bbcs-ico--lg"><use href="#bbcs-i-{name}"/></svg>
	 *
	 * @param string $name SVG sprite icon name (without #bbcs-i- prefix).
	 */
	public function withIcon( string $name ): self {
		$this->icon_name = $name;
		$this->icon_full = false;
		$this->icon_html = '';
		return $this;
	}

	/**
	 * Set the icon using the full-featured SVG style (tools tabs).
	 * Renders: <svg class="bbcs-info-img" width="28" height="28" viewBox="0 0 24 24"
	 *          fill="none" stroke="currentColor" stroke-width="1.5"
	 *          stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
	 *          <use href="#bbcs-i-{name}"/></svg>
	 *
	 * @param string $name SVG sprite icon name (without #bbcs-i- prefix).
	 */
	public function withIconFull( string $name ): self {
		$this->icon_name = $name;
		$this->icon_full = true;
		$this->icon_html = '';
		return $this;
	}

	/**
	 * Set the icon using an old-style <img> tag pointing to an SVG file
	 * (matching the legacy render pattern from public/icons/).
	 *
	 * Usage: ->withIconImage( BOTBLOCKER_URL . 'public/icons/simple-bot-detection.svg', 'Simple Bot Detection' )
	 *
	 * Renders: <img src="{url}" alt="{alt}" class="bbcs-info-img">
	 *
	 * @param string $url  Full URL to the SVG image file.
	 * @param string $alt  Alt text for the image.
	 */
	public function withIconImage( string $url, string $alt = '' ): self {
		$this->icon_image_url  = $url;
		$this->icon_image_alt  = $alt;
		$this->icon_html       = '';
		$this->icon_name       = '';
		$this->icon_full       = false;
		return $this;
	}

	/**
	 * Set completely custom icon HTML (takes priority over withIcon/withIconFull/withIconImage).
	 *
	 * @param string $html Full SVG HTML string.
	 */
	public function withIconHtml( string $html ): self {
		$this->icon_html       = $html;
		$this->icon_image_url  = '';
		$this->icon_name       = '';
		$this->icon_full       = false;
		return $this;
	}

	/**
	 * Append a description paragraph.
	 *
	 * @param string $text Already translated text.
	 */
	public function withDescription( string $text ): self {
		$this->descriptions[] = $text;
		return $this;
	}

	/**
	 * Set the title heading displayed above descriptions.
	 *
	 * @param string $text Already translated title.
	 */
	public function withTitle( string $text ): self {
		$this->title = $text;
		return $this;
	}

	/**
	 * Append a documentation link.
	 *
	 * @param string $url   External documentation URL.
	 * @param string $label Link text (already translated).
	 */
	public function withDocLink( string $url, string $label ): self {
		$this->doc_links[] = array( 'url' => $url, 'label' => $label );
		return $this;
	}

	/**
	 * Append an info note inside the body column.
	 *
	 * Renders as: <div class="bbcs-infocol-note bbcs-infocol-note--{type}">{content}</div>
	 *
	 * @param string|callable $content Plain text (auto-escaped) or callable that echoes escaped HTML.
	 * @param string          $type    One of the NOTE_* constants. Default NOTE_WARN.
	 */
	public function withNote( $content, string $type = self::NOTE_WARN ): self {
		$this->notes[] = array(
			'content' => $content,
			'type'    => in_array( $type, self::ALLOWED_NOTE_TYPES, true ) ? $type : self::NOTE_WARN,
		);
		return $this;
	}

	public function render( bool $return = false ): string {
		$html = '<div class="bbcs-infocol">';

		if ( $this->icon_html !== '' ) {
			$html .= '<div class="bbcs-infocol-ic">' . $this->icon_html . '</div>';
		} elseif ( $this->icon_image_url !== '' ) {
			$html .= '<div class="bbcs-infocol-ic">'
				. '<img src="' . self::escape( $this->icon_image_url, 'url' ) . '"'
				. ' alt="' . self::escape( $this->icon_image_alt, 'attr' ) . '"'
				. ' class="bbcs-info-img" />'
				. '</div>';
		} elseif ( $this->icon_name !== '' ) {
			$html .= '<div class="bbcs-infocol-ic">';
			if ( $this->icon_full ) {
				$html .= '<svg class="bbcs-info-img" width="28" height="28" viewBox="0 0 24 24"'
					. ' fill="none" stroke="currentColor" stroke-width="1.5"'
					. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
					. '<use href="#bbcs-i-' . self::escape( $this->icon_name, 'attr' ) . '"/>'
					. '</svg>';
			} else {
				$html .= '<svg class="bbcs-ico bbcs-ico--lg" aria-hidden="true">'
					. '<use href="#bbcs-i-' . self::escape( $this->icon_name, 'attr' ) . '"/>'
					. '</svg>';
			}
			$html .= '</div>';
		}

		$html .= '<div class="bbcs-infocol-body">';

		if ( $this->title !== '' ) {
			$html .= '<div class="bbcs-infocol-title">' . self::escape( $this->title ) . '</div>';
		}

		foreach ( $this->descriptions as $desc ) {
			$html .= '<div class="bbcs-infocol-desc">' . self::escape( $desc ) . '</div>';
		}

		foreach ( $this->notes as $note ) {
			$content = self::content( $note['content'] );
			if ( $content !== '' ) {
				$html .= '<div class="bbcs-infocol-note bbcs-infocol-note--' . $note['type'] . '">'
					. wp_kses_post( $content )
					. '</div>';
			}
		}

		if ( ! empty( $this->doc_links ) ) {
			$html .= '<div class="bbcs-doclist">';
			$html .= '<div class="bbcs-doclist-head"><span class="bbcs-help-q">?</span>' . self::escape( __( 'Documentation', 'botblocker-security' ) ) . '</div>';
			foreach ( $this->doc_links as $link ) {
				$html .= '<a class="bbcs-link bbcs-fs-xs" href="' . self::escape( $link['url'], 'url' ) . '" target="_blank">'
					. self::escape( $link['label'] )
					. '</a>';
			}
			$html .= '</div>';
		}

		$html .= '</div>'; // .bbcs-infocol-body
		$html .= '</div>'; // .bbcs-infocol

		return self::output( $html, $return );
	}
}
