<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_extract_locale_from_mo( string $path, string $text_domain = 'botblocker-security' ) {
	$pattern = '/' . preg_quote( $text_domain, '/' ) . '-([a-z]{2,3}_[A-Z]{2,3}|[a-z]{2,3})\.mo$/';
	if ( preg_match( $pattern, $path, $matches ) ) {
		return $matches[1];
	}
	return false;
}

/**
 * Custom locale
 * @param $locale string - 'ru_RU', 'en_US', etc.
 * @return string - Display name of the locale
 */
function bbcs_custom_locale_display_name( string $locale ): string {
	$names_native = array(
		'ru_RU' => 'Русский (Россия)',
		'uk'    => 'Українська (Україна)',
		'en_US' => 'English (United States)',
		'de_DE' => 'Deutsch (Deutschland)',
		'fr_FR' => 'Français (France)',
		'es_ES' => 'Español (España)',
		'pl_PL' => 'Polski (Polska)',
	);

	return $names_native[ $locale ] ?? $locale;
}

function bbcs_get_lang_options_html(): void {
	$mo_files = glob( BOTBLOCKER_DIR . 'languages/botblocker-*.mo' );
	?>    
	<div class="content">
		<ul>
	<?php
	if ( is_array( $mo_files ) ) {
		foreach ( $mo_files as $f ) {
			$data_lang = bbcs_extract_locale_from_mo( $f, $text_domain = 'botblocker-security' );

			if ( preg_match( '/^[a-z]{2,3}_([A-Z]{2})$/i', $data_lang, $matches ) ) {
				$flag = strtolower( $matches[1] );
			} else {
				$flag = strtolower( $data_lang );
			}
			?>
			<li>
				<a href="#" class="language-option" data-lang="<?php echo esc_attr( $data_lang ); ?>">
					<div class="flag flag-<?php echo esc_attr( $flag ); ?>"></div>
					<span class="title"><?php echo esc_html( bbcs_custom_locale_display_name( $data_lang ) ); ?></span>
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
add_shortcode( 'bbcs_lang_options', 'bbcs_get_lang_options_html' );
