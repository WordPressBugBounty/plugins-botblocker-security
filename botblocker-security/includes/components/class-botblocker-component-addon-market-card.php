<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AddonMarketCard extends Base {
	/** @var \Botblocker_AddonMarketCardData|null */
	private $card;
	/** @var bool */
	private $addons_locked = false;
	/** @var string */
	private $cloud_api_url = '';
	/** @var bool */
	private $local_mode = false;

	public function withCard( \Botblocker_AddonMarketCardData $card ): self {
		$this->card = $card;
		return $this;
	}

	public function withAddonsLocked( bool $locked ): self {
		$this->addons_locked = $locked;
		return $this;
	}

	public function withCloudApiUrl( string $url ): self {
		$this->cloud_api_url = $url;
		return $this;
	}

	public function withLocalMode( bool $local_mode ): self {
		$this->local_mode = $local_mode;
		return $this;
	}

	public function render( bool $return = false ): string {
		if ( ! $this->card ) {
			return self::output( '', $return );
		}

		$renderer = require BOTBLOCKER_DIR . 'admin/templates/addons/addon-market-card.php';

		return self::output(
			self::content(
				function () use ( $renderer ) {
					$renderer(
						$this->card,
						$this->addons_locked,
						$this->cloud_api_url,
						$this->local_mode
					);
				}
			),
			$return
		);
	}
}
