<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerRkn {

	public static function getDataFiles(): array {
		$default = BotBlockerRugov::uploadsFile();
		$files   = $default !== '' ? array( $default ) : array();
		$files   = apply_filters( 'bbcs_rkn_data_files', $files );
		if ( ! is_array( $files ) ) {
			return array();
		}
		return array_values( array_filter( $files, 'is_string' ) );
	}

	public static function loadNetworks(): array {
		foreach ( self::getDataFiles() as $file ) {
			if ( $file === '' || ! file_exists( $file ) ) {
				continue;
			}

			$data = BotBlockerDataFile::safeLoad( $file );
			if ( ! is_array( $data ) ) {
				continue;
			}

			if ( isset( $data['bbcs_rugov'] ) && is_array( $data['bbcs_rugov'] ) ) {
				return $data['bbcs_rugov'];
			}
		}

		return array();
	}

	public static function isIpInNetworks( string $ip, array $networks ): bool {
		foreach ( $networks as $network ) {
			$network = trim( (string) $network );
			if ( $network === '' ) {
				continue;
			}
			if ( BotBlockerIp::netMatch( $network, $ip ) === true ) {
				return true;
			}
		}

		return false;
	}

	public static function isRknIp( string $ip ): bool {
		return self::isIpInNetworks( $ip, self::loadNetworks() );
	}
}
