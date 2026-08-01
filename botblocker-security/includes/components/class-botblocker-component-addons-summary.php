<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AddonsSummary extends Base {
	private $addons        = array();
	private $active        = array();
	private $addons_locked = false;
	private $has_cloud_api = false;
	private $tools_url     = '';
	private $addons_url    = '';
	private $cloud_api_url = '';

	public function withAddons( array $addons ): self {
		$this->addons = $addons;
		return $this;
	}

	public function withActiveAddons( array $active ): self {
		$this->active = $active;
		return $this;
	}

	public function withAddonsLocked( bool $locked ): self {
		$this->addons_locked = $locked;
		return $this;
	}

	public function withHasCloudApi( bool $has_cloud_api ): self {
		$this->has_cloud_api = $has_cloud_api;
		return $this;
	}

	public function withToolsUrl( string $url ): self {
		$this->tools_url = $url;
		return $this;
	}

	public function withAddonsUrl( string $url ): self {
		$this->addons_url = $url;
		return $this;
	}

	public function withCloudApiUrl( string $url ): self {
		$this->cloud_api_url = $url;
		return $this;
	}

	public function render( bool $return = false ): string {
		$html = '<div class="bbcs-addons-dash">';

		if ( $this->addons_locked ) {
			$html .= '<div class="alert alert-warning p-2 mb-2 bbcs-addons-off-text">' . esc_html__( 'Add-ons locked. Activate Cloud API to use marketplace features.', 'botblocker-security' ) . '</div>';
		}

		if ( empty( $this->addons ) ) {
			$html .= '<div class="bbcs-addons-empty border rounded p-3 text-center">'
				. '<p class="mb-2 mbcs-empty-text">' . esc_html__( 'Enhance speed, security and user experience with official BotBlocker add-ons.', 'botblocker-security' ) . '</p>'
				. '<a href="' . esc_url( $this->addons_url ) . '" class="btn btn-xs btn-primary"><i class="fa-solid fa-puzzle-piece"></i> ' . esc_html__( 'Browse Add-ons', 'botblocker-security' ) . '</a>'
				. '</div>';
			$html .= '</div>';

			return self::output( $html, $return );
		}

		$html .= $this->renderAddonList();

		if ( ! $this->has_cloud_api && $this->cloud_api_url !== '' ) {
			$html .= '<a class="btn btn-xs btn-default mt-2" href="' . esc_url( $this->cloud_api_url ) . '"><i class="fa-solid fa-crown"></i> ' . esc_html__( 'Connect Cloud API now!', 'botblocker-security' ) . '</a>';
		}

		$html .= '</div>';

		return self::output( $html, $return );
	}

	private function renderAddonList(): string {
		$html = '<ul class="list-unstyled m-0">';
		foreach ( $this->addons as $slug => $addon ) {
			$name     = $addon['name'] ?: $slug;
			$isActive = in_array( $slug, $this->active, true );
			$ver      = $addon['version'] ?? '';

			$icon_classes = 'fa-solid fa-circle ' . ( $isActive ? 'text-success' : 'text-danger' ) . ' me-2';
			$has_link     = $isActive && $this->tools_url !== '';

			$html .= '<li class="d-flex align-items-center mb-1 bbcs-dash-addon-li">';
			$html .= '<i class="' . esc_attr( $icon_classes ) . '"></i>';

			if ( $has_link ) {
				$html .= '<a href="' . esc_url( $this->tools_url . '#addon-' . rawurlencode( (string) $slug ) ) . '" class="bbcs-addon-link">';
			}

			$html .= esc_html( $name );
			if ( $ver !== '' ) {
				$html .= ' <small class="text-muted">' . esc_html( ' (' . $ver . ')' ) . '</small>';
			}

			if ( $has_link ) {
				$html .= '</a>';
			}

			$html .= '</li>';
		}
		$html .= '</ul>';

		return $html;
	}
}
