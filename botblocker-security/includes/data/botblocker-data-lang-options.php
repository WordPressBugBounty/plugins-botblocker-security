<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bbcs_get_lang_options(): array {
	$mo_files = bbcs_find_mo_files();
	$options  = array();

	foreach ( $mo_files as $f ) {
		$data_lang = bbcs_extract_locale_from_mo( $f, 'botblocker-security' );
		if ( ! $data_lang ) {
			continue;
		}

		$lang_to_flag = array(
			'ja' => 'jp',
			'uk' => 'ua',
			'ar' => 'sa',
			'ko' => 'kr',
		);

		if ( preg_match( '/^[a-z]{2,3}_([A-Z]{2})$/i', $data_lang, $matches ) ) {
			$flag = strtolower( $matches[1] );
		} elseif ( isset( $lang_to_flag[ $data_lang ] ) ) {
			$flag = $lang_to_flag[ $data_lang ];
		} else {
			$flag = strtolower( $data_lang );
		}

		$options[] = array(
			'lang' => $data_lang,
			'flag' => $flag,
			'name' => bbcs_custom_locale_display_name( $data_lang ),
		);
	}

	usort($options, static function ($a, $b) {
		if ($a['lang'] === 'en_US') return -1;
		if ($b['lang'] === 'en_US') return 1;
		return strcoll($a['name'], $b['name']);
	});

	return $options;
}
