<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_migration_2_4_0() {
	global $wpdb;

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

	$new_search_engines = array(
		array(
			'priority' => 25,
			'search'   => 'Mail.RU_Bot',
			'data'     => '.mail.ru .smailru.net',
			'rule'     => 'allow',
			'comment'  => 'Mail.ru crawler',
			'disable'  => 0,
		),
		array(
			'priority' => 27,
			'search'   => 'TelegramBot',
			'data'     => 'asn:62041 asn:59930 asn:62014 asn:44907',
			'rule'     => 'allow',
			'comment'  => 'Telegram link preview (ASN-verified)',
			'disable'  => 0,
		),
		array(
			'priority' => 29,
			'search'   => 'Twitterbot',
			'data'     => '.twttr.com',
			'rule'     => 'allow',
			'comment'  => 'Twitter/X link preview',
			'disable'  => 0,
		),
		array(
			'priority' => 40,
			'search'   => 'Slackbot',
			'data'     => '.slack.com',
			'rule'     => 'allow',
			'comment'  => 'Slack link expander',
			'disable'  => 0,
		),
		array(
			'priority' => 42,
			'search'   => 'WhatsApp',
			'data'     => '.whatsapp.net .whatsapp.com',
			'rule'     => 'allow',
			'comment'  => 'WhatsApp link preview',
			'disable'  => 0,
		),
		array(
			'priority' => 44,
			'search'   => 'SkypeUriPreview',
			'data'     => '.skype.com',
			'rule'     => 'allow',
			'comment'  => 'Skype link preview',
			'disable'  => 0,
		),
	);

	$value_placeholders = array();
	$value_args         = array();
	foreach ( $new_search_engines as $se ) {
		$value_placeholders[] = '(%d, %s, %s, %s, %s, %d)';
		$value_args[]         = $se['priority'];
		$value_args[]         = $se['search'];
		$value_args[]         = $se['data'];
		$value_args[]         = $se['rule'];
		$value_args[]         = $se['comment'];
		$value_args[]         = $se['disable'];
	}

	$sql = $wpdb->prepare(
		"INSERT IGNORE INTO `{$wpdb->bbcs_se}`
            (`priority`, `search`, `data`, `rule`, `comment`, `disable`)
         VALUES " . implode( ', ', $value_placeholders ),
		$value_args
	);
	$wpdb->query( $sql );

	$asn_updates = array(
		array(
			'search' => 'Googlebot',
			'token'  => 'asn:15169',
		),
		array(
			'search' => 'bingbot',
			'token'  => 'asn:8075',
		),
		array(
			'search' => 'msnbot',
			'token'  => 'asn:8075',
		),
		array(
			'search' => 'yandex.com',
			'token'  => 'asn:13238',
		),
	);

	foreach ( $asn_updates as $upd ) {
		$current_data = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `data` FROM `{$wpdb->bbcs_se}` WHERE `search` = %s",
				$upd['search']
			)
		);

		if ( $current_data === null ) {
			continue;
		}

		$tokens = preg_split( '/\s+/', trim( (string) $current_data ) );
		if ( ! in_array( $upd['token'], $tokens, true ) ) {
			$new_data = trim( $current_data ) . ' ' . $upd['token'];
			$wpdb->update(
				$wpdb->bbcs_se,
				array( 'data' => $new_data ),
				array( 'search' => $upd['search'] ),
				array( '%s' ),
				array( '%s' )
			);
		}
	}

	BotBlockerDb::generateAllFiles();

    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
}
