<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SidebarNav - Shared vertical sidebar navigation (snav) used by Settings, Integrations, and Tools pages.
 *
 * Renders a collapsible sidebar nav with grouped items, search filter, and mobile toggle.
 *
 * Usage:
 *   SidebarNav::make()
 *       ->withGroups( $data->nav_groups )
 *       ->withAriaLabel( __( 'Settings sections', 'botblocker-security' ) )
 *       ->withSearchPlaceholder( __( 'Find setting…', 'botblocker-security' ) )
 *       ->withDefaultIcon( 'search' )
 *       ->withDefaultLabel( __( 'Bot Detection', 'botblocker-security' ) )
 *       ->render();
 */
final class SidebarNav extends Base {

	/** @var array<int, array{title: string, icon: string, items: TabItem[]}> */
	private $groups = array();

	/** @var string */
	private $aria_label = '';

	/** @var string */
	private $search_placeholder = '';

	/** @var string */
	private $default_icon = 'search';

	/** @var string */
	private $default_label = '';

	/** @var bool */
	private $show_mode_toggle = false;

	/** @var array */
	private $simple_nav_ids = array();

	/**
	 * Set the navigation groups.
	 *
	 * @param array<int, array{title: string, icon: string, items: TabItem[]}> $groups
	 */
	public function withGroups( array $groups ): self {
		$this->groups = $groups;
		return $this;
	}

	/**
	 * Set the aria-label for the nav element.
	 */
	public function withAriaLabel( string $label ): self {
		$this->aria_label = $label;
		return $this;
	}

	/**
	 * Set the search input placeholder text.
	 */
	public function withSearchPlaceholder( string $placeholder ): self {
		$this->search_placeholder = $placeholder;
		return $this;
	}

	/**
	 * Set the default icon name (used when no item is active).
	 */
	public function withDefaultIcon( string $icon ): self {
		$this->default_icon = $icon;
		return $this;
	}

	/**
	 * Set the default label text (used when no item is active).
	 */
	public function withDefaultLabel( string $label ): self {
		$this->default_label = $label;
		return $this;
	}

	/**
	 * Show the simple/complex mode toggle switch (Settings page only).
	 */
	public function withSimpleModeToggle( bool $show = true ): self {
		$this->show_mode_toggle = $show;
		return $this;
	}

	/**
	 * Set the nav item IDs that belong to the simple/general group.
	 * Items not in this list are considered advanced.
	 *
	 * @param array $ids List of nav item IDs.
	 */
	public function withSimpleNavIds( array $ids ): self {
		$this->simple_nav_ids = $ids;
		return $this;
	}

	public function render( bool $return = false ): string {
		$mode_class = $this->show_mode_toggle ? ' is-simple-mode' : ' is-snav-search-only';
		$this->class = 'bbcs-snav' . $mode_class . ' is-collapsed';

		$active_icon       = $this->default_icon;
		$active_label      = $this->default_label;
		$active_icon_image = '';

		foreach ( $this->groups as $group ) {
			foreach ( $group['items'] as $item ) {
				if ( $item->active ) {
					$active_icon       = $item->icon;
					$active_label      = $item->label;
					$active_icon_image = $item->icon_image;
					break 2;
				}
			}
		}

		$html = '<nav id="bbcs-snav" class="' . self::classes( $this->class ) . '"'
			. ' aria-label="' . self::escape( $this->aria_label, 'attr' ) . '">';

		// Mobile toggle button
		$html .= '<button type="button" class="bbcs-snav-toggle" aria-expanded="false"'
			. ' aria-label="' . self::escape( __( 'Toggle navigation', 'botblocker-security' ), 'attr' ) . '">';

		if ( $active_icon_image !== '' ) {
			$html .= '<img src="' . self::escape( $active_icon_image, 'url' ) . '" alt="" class="bbcs-ico bbcs-ico--sm bbcs-snav-toggle-icon bbcs-snav-img">';
		} else {
			$html .= self::svg_icon( $active_icon, 'sm', 'bbcs-snav-toggle-icon' );
		}

		$html .= '<span class="bbcs-snav-toggle-label">' . self::escape( wp_strip_all_tags( $active_label ) ) . '</span>'
			. '<svg class="bbcs-ico bbcs-ico--sm bbcs-snav-toggle-chevron" aria-hidden="true"><use href="#bbcs-i-chevron"></use></svg>'
			. '</button>';

		// Header block (Search + Mode toggle)
		$html .= '<div class="bbcs-snav-header">';

		// Search
		$html .= '<div class="bbcs-snav-search">'
			. '<div class="bbcs-snav-search-field">'
			. self::svg_icon( 'search', 'sm', 'bbcs-snav-search-icon' )
			. '<input type="text" class="bbcs-snav-search-input"'
			. ' placeholder="' . self::escape( $this->search_placeholder, 'attr' ) . '" autocomplete="off">'
			. '<button type="button" class="bbcs-snav-search-clear"'
			. ' title="' . self::escape( __( 'Clear search', 'botblocker-security' ), 'attr' ) . '"'
			. ' aria-label="' . self::escape( __( 'Clear search', 'botblocker-security' ), 'attr' ) . '">'
			. '<svg class="bbcs-ico bbcs-ico--xs" aria-hidden="true"><use href="#bbcs-i-x"></use></svg>'
			. '</button>'
			. '</div>'
			. '<button type="button" class="bbcs-snav-collapse-btn"'
			. ' title="' . self::escape( __( 'Unfold all settings', 'botblocker-security' ), 'attr' ) . '"'
			. ' aria-label="' . self::escape( __( 'Unfold all settings', 'botblocker-security' ), 'attr' ) . '"'
			. ' data-collapsed="true">'
			. '<svg class="bbcs-ico bbcs-ico--xs bbcs-snav-collapse-chevron" aria-hidden="true"><use href="#bbcs-i-chevrons-up-down"></use></svg>'
			. '</button>'
			. '</div>';

		// Toggle switch for simple/complex mode (Settings page only)
		if ( $this->show_mode_toggle ) {
			$html .= '<div class="bbcs-snav-mode">'
				. '<label class="bbcs-snav-mode-toggle">'
				. '<span class="bbcs-snav-mode-label">' . self::escape( __( 'Simple mode', 'botblocker-security' ) ) . '</span>'
				. '<input type="checkbox" class="bbcs-snav-mode-checkbox" checked>'
				. '<span class="bbcs-snav-mode-slider"></span>'
				. '<span class="bbcs-snav-mode-sub">' . self::escape( __( 'Show fewer, essential settings', 'botblocker-security' ) ) . '</span>'
				. '</label>'
				. '</div>';
		}

		$html .= '</div>';

		// Groups
		$html .= '<div class="bbcs-snav-groups">';
		foreach ( $this->groups as $group ) {
			$has_simple = false;
			foreach ( $group['items'] as $item ) {
				if ( in_array( $item->id, $this->simple_nav_ids, true ) ) {
					$has_simple = true;
					break;
				}
			}

			$group_class = 'bbcs-snav-group';
			if ( ! $has_simple ) {
				$group_class .= ' is-advanced';
			}

			$html .= '<div class="' . self::classes( $group_class ) . '">'
				. '<div class="bbcs-snav-group-head">'
				. self::svg_icon( $group['icon'], 'sm' )
				. '<span>' . self::escape( $group['title'] ) . '</span>'
				. '</div>';

			foreach ( $group['items'] as $item ) {
				$item_class = 'bbcs-snav-item';
				if ( $item->active ) {
					$item_class .= ' is-active';
				}
				$is_simple = in_array( $item->id, $this->simple_nav_ids, true );
				if ( ! $is_simple ) {
					$item_class .= ' is-advanced';
				}

				$html .= '<button type="button"'
					. ' class="' . self::classes( $item_class ) . '"'
					. ' data-snav-tab="' . self::escape( $item->id, 'attr' ) . '"'
					. ' data-simple="' . ( $is_simple ? '1' : '0' ) . '"'
					. ' aria-current="' . ( $item->active ? 'true' : 'false' ) . '"'
					. ' data-snav-label="' . self::escape( wp_strip_all_tags( $item->label ), 'attr' ) . '"'
					. ' data-snav-icon="' . self::escape( $item->icon_image ?: $item->icon, 'attr' ) . '">';

				if ( $item->icon_image !== '' ) {
					$html .= '<img src="' . self::escape( $item->icon_image, 'url' ) . '" alt="" class="bbcs-ico bbcs-ico--sm bbcs-snav-img">';
				} else {
					$html .= self::svg_icon( $item->icon, 'sm' );
				}

				$html .= '<span class="bbcs-snav-label">' . $item->label . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped - safe HTML from translation
					. '</button>';
			}

			$html .= '</div>';
		}
		$html .= '</div>'; // .bbcs-snav-groups
		$html .= '</nav>';

		return self::output( $html, $return );
	}
}
