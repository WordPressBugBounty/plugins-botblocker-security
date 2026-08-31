<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerLangOptions {

	public static function extractLocaleFromMo( string $path, string $text_domain = 'botblocker-security' ) {
		$pattern = '/' . preg_quote( $text_domain, '/' ) . '-([a-z]{2,3}_[A-Z]{2,3}|[a-z]{2,3})\.mo$/';
		if ( preg_match( $pattern, $path, $matches ) ) {
			return $matches[1];
		}
		return false;
	}

	public static function findMoFiles(): array {
		$seen  = array();
		$files = array();
		$dirs  = array( WP_LANG_DIR . '/plugins/', BOTBLOCKER_DIR . 'languages/' );

		foreach ( $dirs as $dir ) {
			$found = glob( $dir . 'botblocker-*.mo' );
			if ( ! is_array( $found ) ) {
				continue;
			}
			foreach ( $found as $f ) {
				$locale = self::extractLocaleFromMo( $f, 'botblocker-security' );
				if ( $locale && ! isset( $seen[ $locale ] ) ) {
					$seen[ $locale ] = true;
					$files[]         = $f;
				}
			}
		}

		return $files;
	}

	public static function customLocaleDisplayName( string $locale ): string {
		$names_native = array(
			'ru_RU' => 'Русский (Россия)',
			'uk'    => 'Українська (Україна)',
			'en_US' => 'English (United States)',
			'de_DE' => 'Deutsch (Deutschland)',
			'fr_FR' => 'Français (France)',
			'es_ES' => 'Español (España)',
			'it_IT' => 'Italiano (Italia)',
			'ja'    => '日本語 (日本)',
			'pl_PL' => 'Polski (Polska)',
			'pt_BR' => 'Português (Brasil)',
			'tr_TR' => 'Türkçe (Türkiye)',
			'zh_CN' => '中文 (中国)',
			'ar'    => 'العربية (السعودية)',
			'nl_NL' => 'Nederlands',
			'sv_SE' => 'Svenska',
			'ko_KR' => '한국어',
			'he_IL' => 'עברית',
		);

		if ( isset( $names_native[ $locale ] ) ) {
			return $names_native[ $locale ];
		}

		$translations = function_exists( 'wp_get_available_translations' )
			? wp_get_available_translations()
			: array();
		if ( isset( $translations[ $locale ]['native_name'] ) ) {
			return $translations[ $locale ]['native_name'];
		}

		if ( class_exists( 'Locale' ) ) {
			$display = Locale::getDisplayName( $locale, $locale );
			if ( $display !== $locale ) {
				return $display;
			}
		}

		return $locale;
	}

	public static function getOptions(): array {
		$mo_files = self::findMoFiles();
		$options  = array();

		foreach ( $mo_files as $f ) {
			$data_lang = self::extractLocaleFromMo( $f, 'botblocker-security' );
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
				'name' => self::customLocaleDisplayName( $data_lang ),
			);
		}

		usort($options, static function ($a, $b) {
			if ($a['lang'] === 'en_US') return -1;
			if ($b['lang'] === 'en_US') return 1;
			return strcoll($a['name'], $b['name']);
		});

		return $options;
	}

	public static function getOptionsHtml(): void {
		$mo_files = self::findMoFiles();
		$items    = array();

		$lang_to_flag = array(
			'ja' => 'jp',
			'uk' => 'ua',
			'ar' => 'sa',
			'ko' => 'kr',
		);

		foreach ( $mo_files as $f ) {
			$data_lang = self::extractLocaleFromMo( $f, 'botblocker-security' );
			if ( ! $data_lang ) {
				continue;
			}

			if ( preg_match( '/^[a-z]{2,3}_([A-Z]{2})$/i', $data_lang, $matches ) ) {
				$flag = strtolower( $matches[1] );
			} elseif ( isset( $lang_to_flag[ $data_lang ] ) ) {
				$flag = $lang_to_flag[ $data_lang ];
			} else {
				$flag = strtolower( $data_lang );
			}

			$items[] = array(
				'lang' => $data_lang,
				'flag' => $flag,
				'name' => self::customLocaleDisplayName( $data_lang ),
			);
		}

		usort($items, static function ($a, $b) {
			if ($a['lang'] === 'en_US') return -1;
			if ($b['lang'] === 'en_US') return 1;
			return strcoll($a['name'], $b['name']);
		});
		?>    
	<div class="content">
		<ul>
	<?php
	if ( ! empty( $items ) ) {
		foreach ( $items as $item ) {
			?>
			<li>
				<a href="#" class="language-option" data-lang="<?php echo esc_attr( $item['lang'] ); ?>">
					<div class="flag flag-<?php echo esc_attr( $item['flag'] ); ?>"></div>
					<span class="title"><?php echo esc_html( $item['name'] ); ?></span>
				</a>
			</li>
			<?php
		}
	} else {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Shortcode] Missing ".mo" files in "languages" directory, line[' . __LINE__ . '], File: ' . __FILE__ . ']' );
		}
		?>
		<li>
			<a href="#" class="language-option" data-lang="en_US">
				<div class="flag flag-us"></div>
				<span class="title"><?php esc_html_e( 'English', 'botblocker-security' ); ?></span>
			</a>
			</li>
			<li>
			<a href="#" class="language-option" data-lang="ru_RU">
				<div class="flag flag-ru"></div>
				<span class="title"><?php esc_html_e( 'Russian', 'botblocker-security' ); ?></span>
			</a>
			</li>
<?php } ?>
		</ul>
	</div>
	<?php
	}
}
