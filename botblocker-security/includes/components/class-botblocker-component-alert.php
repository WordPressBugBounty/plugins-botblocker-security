<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Alert extends Base {
	const TYPE_SUCCESS = 'success';
	const TYPE_DANGER  = 'danger';
	const TYPE_WARNING = 'warning';
	const TYPE_INFO    = 'info';
	const TYPE_ERROR   = 'error';
	const TYPE_UPDATED = 'updated';

	const ALLOWED_TYPES = array(
		self::TYPE_SUCCESS,
		self::TYPE_DANGER,
		self::TYPE_WARNING,
		self::TYPE_INFO,
		self::TYPE_ERROR,
		self::TYPE_UPDATED,
	);

	protected $type = self::TYPE_INFO;
	protected $message = '';

	public function withType( string $type ): self {
		$this->type = $type;
		return $this;
	}

	public function withMessage( $message ): self {
		$this->message = $message;
		return $this;
	}

	public function cssClasses( string $base = '' ): array {
		$type = in_array( $this->type, self::ALLOWED_TYPES, true ) ? $this->type : self::TYPE_INFO;
		$type = $type === self::TYPE_ERROR ? self::TYPE_DANGER : $type;
		return array_merge(
			parent::cssClasses( $base ),
			array( 'bbcs-alert--' . $type ),
			$this->class !== '' ? array( $this->class ) : array()
		);
	}

	public function render( bool $return = false ): string {
		$message = self::content( $this->message );
		if ( $message === '' ) {
			return self::output( '', $return );
		}

		return self::output( '<div class="' . self::classes( $this->cssClasses( 'bbcs-alert' ) ) . '" role="alert">' . wp_kses_post( $message ) . '</div>', $return );
	}
}
