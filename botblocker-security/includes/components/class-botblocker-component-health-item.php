<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HealthItem extends Base {
	const TYPE_CORE           = 'core';
	const TYPE_CLOUD_EXTENDED = 'cloud_extended';
	const TYPE_NEUTRAL        = 'neutral';
	const TYPE_NEGATIVE       = 'negative';

	private $label;
	private $enabled;
	private $type;

	public function withItem( HealthItemData $item ): self {
		$this->label   = $item->label;
		$this->enabled = $item->enabled;
		$this->type    = $item->type;
		return $this;
	}

	public function getLabel(): string {
		return $this->label;
	}

	public function isEnabled(): bool {
		return $this->enabled;
	}

	public function getType(): string {
		return $this->type;
	}

	public function isCore(): bool {
		return $this->type === self::TYPE_CORE;
	}

	public function isCloudExtended(): bool {
		return $this->type === self::TYPE_CLOUD_EXTENDED;
	}

	public function isNeutral(): bool {
		return $this->type === self::TYPE_NEUTRAL;
	}

	public function isNegative(): bool {
		return $this->type === self::TYPE_NEGATIVE;
	}

	public function getIconClass(): string {
		if ( $this->isNegative() ) {
			return $this->enabled ? 'fa-regular fa-circle-xmark' : 'fa-regular fa-circle-check';
		}
		return $this->enabled ? 'fa-regular fa-circle-check' : 'fa-regular fa-circle-xmark';
	}

	public function getCssClass(): string {
		if ( $this->isNegative() ) {
			return $this->enabled ? 'text-warning' : 'text-success';
		}
		if ( $this->isNeutral() ) {
			return 'text-muted';
		}
		return $this->enabled ? 'text-success' : 'text-danger';
	}

	public function getSuffix(): string {
		if ( $this->isNegative() ) {
			return $this->enabled ? ' (' . __( 'may reduce protection', 'botblocker-security' ) . ')' : '';
		}
		return $this->enabled ? '' : ' (' . __( 'disabled', 'botblocker-security' ) . ')';
	}

	public function render( bool $return = false ): string {
		$html = '<span class="bbcs-health-list-item ' . esc_attr( $this->getCssClass() ) . '">'
			. '<i class="' . esc_attr( $this->getIconClass() ) . '"></i> '
			. esc_html( $this->getLabel() . $this->getSuffix() )
			. '</span>';

		return self::output( $html, $return );
	}
}
