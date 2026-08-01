<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ToggleOption - Renders the bbcs-option toggle switch pattern used in settings tabs.
 *
 * Replaces the procedural bbcs_render_toggle() function.
 * Pattern: toggle switch button + hidden input + label + optional PRO badge + help tooltip.
 * JS interactivity is handled by bbcs-multipage.js which binds to the .bbcs-toggle DOM structure.
 */
final class ToggleOption extends Base {

	/** @var string Violet toggle color (for PRO features). */
	const COLOR_VIOLET = 'bbcs-toggle--violet';

	/** @var string Amber toggle color (for Ultimate features). */
	const COLOR_AMBER = 'bbcs-toggle--amber';

	/** PRO badge - violet pill, pro option styling, violet toggle. */
	const BADGE_PRO = 'pro';

	/** Ultimate badge - amber pill, pro option styling. */
	const BADGE_ULTIMATE = 'ultimate';

	/** Warning badge (e.g. Extension Missing) - amber pill, generic. */
	const BADGE_WARNING = 'warning';

	/** Add-on badge - soft blue pill, indicates an add-on plugin is required. */
	const BADGE_ADDON = 'addon';

	/** @var string Input name attribute. */
	private $field_name = '';

	/** @var bool Whether the toggle is on (checked). */
	private $checked = false;

	/** @var string Option label text (already translated). */
	private $label = '';

	/** @var string Tooltip text (already translated). */
	private $tooltip = '';

	/** @var array<int, array{text: string, type: string}> Badge entries. */
	private $badges = array();

	/** @var string Color class override for toggle. */
	private $color_cls = '';

	/** @var bool Whether the toggle is disabled. */
	private $disabled = false;

	/** @var string AJAX action name (when set, enables data-bbcs-toggle="1" behaviour). */
	private $ajax_action = '';

	/** @var string AJAX setting key sent to the server. */
	private $ajax_setting = '';

	public function withName( string $name ): self {
		$this->field_name = $name;
		return $this;
	}

	public function withChecked( bool $checked ): self {
		$this->checked = $checked;
		return $this;
	}

	public function withLabel( string $label ): self {
		$this->label = $label;
		return $this;
	}

	public function withTooltip( string $tooltip ): self {
		$this->tooltip = $tooltip;
		return $this;
	}

	/**
	 * Add a badge next to the label (e.g. 'PRO', 'Extension Missing').
	 * Can be called multiple times to stack badges.
	 *
	 * @param string $text Badge text (already translated). Empty string = no badge.
	 * @param string $type One of the BADGE_* constants (BADGE_PRO, BADGE_ULTIMATE, BADGE_WARNING, BADGE_ADDON).
	 */
	public function withBadge( string $text, string $type = self::BADGE_WARNING ): self {
		if ( $text !== '' ) {
			$this->badges[] = array( 'text' => $text, 'type' => $type );
		}
		return $this;
	}

	/**
	 * @param string $color_class Toggle color class from COLOR_* constants.
	 */
	public function withColor( string $color_class ): self {
		$this->color_cls = $color_class;
		return $this;
	}

	public function withDisabled( bool $disabled = true ): self {
		$this->disabled = $disabled;
		return $this;
	}

	/**
	 * Enable AJAX toggle behaviour (data-bbcs-toggle="1").
	 * The toggle will send an AJAX request on click instead of just updating a hidden input.
	 *
	 * @param string $action  AJAX action name (e.g. 'bbcs_toggle_early_phase_in_db').
	 * @param string $setting Setting key sent to the server (e.g. 'mu_enable').
	 */
	public function withAjax( string $action, string $setting ): self {
		$this->ajax_action  = $action;
		$this->ajax_setting = $setting;
		return $this;
	}

	public function render( bool $return = false ): string {
		$option_class = 'bbcs-option bbcs-hoverbg';
		$has_pro_badge = false;
		foreach ( $this->badges as $b ) {
			if ( $b['type'] === self::BADGE_PRO || $b['type'] === self::BADGE_ULTIMATE ) {
				$has_pro_badge = true;
				break;
			}
		}
		if ( $has_pro_badge ) {
			$option_class .= ' bbcs-option--pro';
		}

		$toggle_class = 'bbcs-toggle';
		if ( $this->checked ) {
			$toggle_class .= ' is-on';
		}
		if ( $this->color_cls !== '' ) {
			$toggle_class .= ' ' . $this->color_cls;
		} elseif ( $has_pro_badge ) {
			$toggle_class .= ' ' . self::COLOR_VIOLET;
		}

		$html = '<div class="' . $option_class . '"' . $this->anchor_attr() . '>';

		// Build toggle button attributes.
		$btn_attrs = ' class="' . $toggle_class . '" role="switch"'
			. ' aria-checked="' . ( $this->checked ? 'true' : 'false' ) . '"';

		if ( $this->ajax_action !== '' ) {
			// AJAX toggle – send to server on click (handled by bbcs-multipage.js).
			$btn_attrs .= ' data-bbcs-toggle="1"'
				. ' data-action="' . self::escape( $this->ajax_action, 'attr' ) . '"'
				. ' data-setting="' . self::escape( $this->ajax_setting, 'attr' ) . '"'
				. ' data-value="' . ( $this->checked ? '1' : '0' ) . '"';
			if ( $this->disabled ) {
				$btn_attrs .= ' disabled';
			}
		} else {
			// Visual-only toggle – updates hidden input for form submission.
			$btn_attrs .= ' data-field="' . self::escape( $this->field_name, 'attr' ) . '"';
			if ( $this->disabled ) {
				$btn_attrs .= ' disabled';
			}
		}

		$btn_attrs .= ' type="button"';

		$html .= '<button' . $btn_attrs . '>'
			. '<span class="bbcs-toggle-knob"></span>'
			. '</button>';

		// Hidden input only for form-based toggles; AJAX toggles persist via the API response.
		if ( $this->ajax_action === '' ) {
			$html .= '<input type="hidden" name="' . self::escape( $this->field_name, 'attr' )
				. '" value="' . ( $this->checked ? '1' : '0' ) . '"'
				. ( $this->disabled ? ' disabled' : '' )
				. '>';
		}

		$html .= '<span class="bbcs-option-label">' . self::escape( $this->label ) . '</span>';

		foreach ( $this->badges as $b ) {
			switch ( $b['type'] ) {
				case self::BADGE_PRO:
					$pill_class = 'bbcs-pill--violet';
					break;
				case self::BADGE_ULTIMATE:
					$pill_class = 'bbcs-pill--amber';
					break;
				case self::BADGE_WARNING:
				default:
					$pill_class = 'bbcs-pill--amber';
					break;
				case self::BADGE_ADDON:
					$pill_class = 'bbcs-pill--addon';
					break;
			}
			$html .= ' <span class="bbcs-pill ' . $pill_class . ' bbcs-pill--pro">' . self::escape( $b['text'] ) . '</span>';
		}

		if ( $this->tooltip !== '' ) {
			$html .= '<span class="bbcs-help">';
			$html .= '<span class="bbcs-help-q">?</span>';
			$html .= '<span class="bbcs-help-tip">' . self::escape( $this->tooltip ) . '</span>';
			$html .= '</span>';
		}

		$html .= '</div>';

		return self::output( $html, $return );
	}
}
