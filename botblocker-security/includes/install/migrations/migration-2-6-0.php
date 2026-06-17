<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_migration_2_6_0() {
	global $wpdb;

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	BotBlockerSeedData::insertDefaultAsn();

	$preview_bots = array(
		array(
			'priority' => 11,
			'search'   => 'facebookexternalhit',
			'data'     => '.fbsv.net .tfbnw.net .facebook.com asn:32934',
			'rule'     => 'allow',
			'comment'  => 'Facebook crawler',
			'disable'  => 0,
		),
		array(
			'priority' => 11,
			'search'   => 'Facebot',
			'data'     => '.facebook.com .fbsv.net .tfbnw.net asn:32934',
			'rule'     => 'allow',
			'comment'  => 'Facebook link preview crawler',
			'disable'  => 0,
		),
		array(
			'priority' => 11,
			'search'   => 'Instagram',
			'data'     => '.instagram.com .facebook.com .fbsv.net .tfbnw.net asn:32934',
			'rule'     => 'allow',
			'comment'  => 'Instagram link preview',
			'disable'  => 0,
		),
		array(
			'priority' => 42,
			'search'   => 'WhatsApp',
			'data'     => '.whatsapp.net .whatsapp.com asn:32934',
			'rule'     => 'allow',
			'comment'  => 'WhatsApp link preview',
			'disable'  => 0,
		),
		array(
			'priority' => 49,
			'search'   => 'Cardyb',
			'data'     => '.',
			'rule'     => 'allow',
			'comment'  => 'Bluesky/Cardyb link preview',
			'disable'  => 0,
		),
	);

	foreach ( $preview_bots as $bot ) {
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT `id`, `data` FROM `{$wpdb->bbcs_se}` WHERE `search` = %s",
				$bot['search']
			),
			ARRAY_A
		);

		if ( $existing ) {
			$tokens = preg_split( '/\s+/', trim( (string) $existing['data'] ) );
			$tokens = array_filter( (array) $tokens, 'strlen' );
			foreach ( preg_split( '/\s+/', trim( $bot['data'] ) ) as $token ) {
				if ( $token !== '' && ! in_array( $token, $tokens, true ) ) {
					$tokens[] = $token;
				}
			}
			$wpdb->update(
				$wpdb->bbcs_se,
				array( 'data' => implode( ' ', $tokens ) ),
				array( 'id' => $existing['id'] ),
				array( '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$wpdb->bbcs_se,
				array(
					'priority' => $bot['priority'],
					'search'   => $bot['search'],
					'data'     => $bot['data'],
					'rule'     => $bot['rule'],
					'comment'  => $bot['comment'],
					'disable'  => $bot['disable'],
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d' )
			);
		}
	}

	BotBlockerDb::generateAllFiles();

    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
