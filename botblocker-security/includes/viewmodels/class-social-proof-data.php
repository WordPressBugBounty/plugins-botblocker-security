<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_SocialProofData {
	/** @var int */
	private $num_ratings;
	/** @var string */
	private $rating_value;
	/** @var int */
	private $full_stars;
	/** @var bool */
	private $has_half;
	/** @var int */
	private $installs;
	/** @var string */
	private $installs_label;

	public function __construct( array $stats ) {
		$rating_5    = ( $stats['rating'] / 100 ) * 5;
		$full        = (int) floor( $rating_5 );
		$half        = ( $rating_5 - $full ) >= 0.25 && ( $rating_5 - $full ) < 0.75;
		$installs    = $stats['active_installs'];

		$this->num_ratings    = $stats['num_ratings'];
		$this->rating_value   = number_format_i18n( $rating_5, 1 );
		$this->full_stars     = $full;
		$this->has_half       = $half;
		$this->installs       = $installs;
		$this->installs_label = $installs >= 1000
			? sprintf( /* translators: %s: number of active plugin installs */ __( '%s+ active installs', 'botblocker-security' ), number_format_i18n( $installs ) )
			: sprintf( /* translators: %s: number of active plugin installs */ __( '%s active installs', 'botblocker-security' ), number_format_i18n( $installs ) );
	}

	public function hasRatings(): bool {
		return $this->num_ratings > 0;
	}

	public function getNumRatings(): int {
		return $this->num_ratings;
	}

	public function getRatingValue(): string {
		return $this->rating_value;
	}

	public function getFullStars(): int {
		return $this->full_stars;
	}

	public function hasHalfStar(): bool {
		return $this->has_half;
	}

	public function hasInstalls(): bool {
		return $this->installs > 0;
	}

	public function getInstalls(): int {
		return $this->installs;
	}

	public function getInstallsLabel(): string {
		return $this->installs_label;
	}

	public function getRatingsLabel(): string {
		return sprintf(
			/* translators: %s: number of ratings */
			_n( '(%s rating)', '(%s ratings)', $this->num_ratings, 'botblocker-security' ),
			number_format_i18n( $this->num_ratings )
		);
	}
}
