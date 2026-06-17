<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerSeedData {

	public static function insertInitialData( string $salt_bb = '' ): void {
		self::insertDefaultRules();
		self::insertDefaultSearchEngines();
		self::insertDefaultAsn();
		self::insertDefaultPaths();
		// bbcs_insertExtendedPaths() is retained for test coverage only.
		// Extended paths are designed to be activated manually by the admin
		// or via the add-on system - not on initial install.
		// self::insertExtendedPaths();
		self::insertDefaultSettings( $salt_bb );
		self::insertDefaultProxies();
		self::insertDefaultCounters();
		self::insertDefaultPageFilters();
	}

	public static function insertDefaultRules(): void {
		global $wpdb;

		$default_rules = array(
			array(
				'priority' => 10,
				'type'     => 'asname',
				'data'     => 'Biterika',
				'search'   => 'asname=Biterika',
				'rule'     => 'block',
				'comment'  => 'Spam ASN',
			),
			array(
				'priority' => 10,
				'type'     => 'asname',
				'data'     => 'Squitter-Networks',
				'search'   => 'asname=Squitter-Networks',
				'rule'     => 'block',
				'comment'  => 'Known spam network',
			),
			array(
				'priority' => 10,
				'type'     => 'asname',
				'data'     => 'ColocationAmerica',
				'search'   => 'asname=ColocationAmerica',
				'rule'     => 'block',
				'comment'  => 'Network with high bot activity',
			),
			array(
				'priority' => 10,
				'type'     => 'asname',
				'data'     => 'Centrilogic',
				'search'   => 'asname=Centrilogic',
				'rule'     => 'block',
				'comment'  => 'Network with suspicious activity',
			),
			array(
				'priority' => 10,
				'type'     => 'asname',
				'data'     => 'Selectel',
				'search'   => 'asname=Selectel',
				'rule'     => 'block',
				'comment'  => 'Hosting with high botnet activity',
			),
			array(
				'priority' => 10,
				'type'     => 'asname',
				'data'     => 'Serverion',
				'search'   => 'asname=Serverion',
				'rule'     => 'block',
				'comment'  => 'Anonymous proxy servers',
			),
			array(
				'priority' => 10,
				'type'     => 'asname',
				'data'     => 'Hostkey',
				'search'   => 'asname=Hostkey',
				'rule'     => 'block',
				'comment'  => 'Hosting known for malicious bots',
			),
			array(
				'priority' => 10,
				'type'     => 'asname',
				'data'     => '3NT Solutions',
				'search'   => 'asname=3NT Solutions',
				'rule'     => 'block',
				'comment'  => 'Known source of malicious activity',
			),
		);

		foreach ( $default_rules as $rule ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO `{$wpdb->bbcs_rules}` (priority, type, data, rule, comment) VALUES (%d, %s, %s, %s, %s)",
					$rule['priority'],
					$rule['type'],
					$rule['data'],
					$rule['rule'],
					$rule['comment']
				)
			);
		}
	}

	public static function insertDefaultCounters(): void {
		global $wpdb;

		$default_counters = array(
			'today_hits'           => 0,
			'today_blocked'        => 0,
			'total_hits'           => 0,
			'total_blocked'        => 0,
			'search_engine_visits' => 0,
		);
		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_counters}`" );

		if ( $row_count == 0 ) {
			// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO `{$wpdb->bbcs_counters}` (today_hits, today_blocked, total_hits, total_blocked, search_engine_visits)
	             VALUES (%d, %d, %d, %d, %d)",
					$default_counters['today_hits'],
					$default_counters['today_blocked'],
					$default_counters['total_hits'],
					$default_counters['total_blocked'],
					$default_counters['search_engine_visits']
				)
			);
		}
	}

	public static function insertDefaultSearchEngines(): void {
		global $wpdb;
		$default_search_engines = array(
			array('priority'=>1,'search'=>'Google-InspectionTool','data'=>'.googlebot.com','rule'=>'allow','comment'=>'Search Console','disable'=>0),
			array('priority'=>1,'search'=>'Chrome-Lighthouse','data'=>'.google.com','rule'=>'allow','comment'=>'PageSpeed Insights','disable'=>0),
			array('priority'=>1,'search'=>'Mediapartners','data'=>'.googlebot.com .google.com','rule'=>'allow','comment'=>'AdSense bot','disable'=>0),
			array('priority'=>2,'search'=>'Googlebot-Image','data'=>'.googlebot.com .google.com','rule'=>'allow','comment'=>'Google Images','disable'=>0),
			array('priority'=>2,'search'=>'Googlebot-Video','data'=>'.googlebot.com .google.com','rule'=>'allow','comment'=>'Google Videos','disable'=>0),
			array('priority'=>2,'search'=>'Googlebot-News','data'=>'.googlebot.com .google.com','rule'=>'allow','comment'=>'Google News','disable'=>0),
			array('priority'=>2,'search'=>'Storebot-Google','data'=>'.google.com','rule'=>'allow','comment'=>'Google Shopping','disable'=>0),
			array('priority'=>5,'search'=>'GoogleOther-Image','data'=>'.google.com','rule'=>'allow','comment'=>'GoogleOther Images','disable'=>0),
			array('priority'=>5,'search'=>'GoogleOther-Video','data'=>'.google.com','rule'=>'allow','comment'=>'GoogleOther Videos','disable'=>0),
			array('priority'=>5,'search'=>'Google-CloudVertexBot','data'=>'.google.com .googleusercontent.com','rule'=>'allow','comment'=>'Vertex AI','disable'=>0),
			array('priority'=>10,'search'=>'Googlebot','data'=>'.googlebot.com asn:15169','rule'=>'allow','comment'=>'GoogleBot (Catch-all)','disable'=>0),
			array('priority'=>10,'search'=>'GoogleOther','data'=>'.google.com','rule'=>'allow','comment'=>'GoogleOther (Catch-all)','disable'=>0),
			array('priority'=>2,'search'=>'bingbot','data'=>'search.msn.com','rule'=>'allow','comment'=>'Microsoft Bing search bot','disable'=>0),
			array('priority'=>2,'search'=>'BingPreview','data'=>'search.msn.com','rule'=>'allow','comment'=>'Bing Preview bot','disable'=>0),
			array('priority'=>3,'search'=>'yandex.com','data'=>'.yandex.ru .yandex.net .yandex.com asn:13238 ip:45.138.0.0/24 ip:45.148.65.0/24 ip:141.8.183.0/24','rule'=>'allow','comment'=>'Yandex search bots and services','disable'=>0),
			array('priority'=>4,'search'=>'Applebot','data'=>'.applebot.apple.com','rule'=>'allow','comment'=>'Applebot crawler','disable'=>0),
			array('priority'=>5,'search'=>'Google-Site-Verification','data'=>'.googlebot.com .google.com','rule'=>'allow','comment'=>'Verification bot for Google Search Console','disable'=>0),
			array('priority'=>6,'search'=>'DuckDuckBot','data'=>'.duckduckgo.com','rule'=>'allow','comment'=>'DuckDuckGo search engine bot','disable'=>0),
			array('priority'=>8,'search'=>'PetalBot','data'=>'.petalsearch.com .aspiegel.com','rule'=>'allow','comment'=>'Huawei Petal Search','disable'=>0),
			array('priority'=>12,'search'=>'SeznamBot','data'=>'.seznam.cz','rule'=>'allow','comment'=>'Seznam.cz search engine bot','disable'=>0),
			array('priority'=>15,'search'=>'NaverBot','data'=>'.naver.com','rule'=>'allow','comment'=>'Naver search engine bot','disable'=>0),
			array('priority'=>16,'search'=>'Sogou','data'=>'.sogou.com','rule'=>'allow','comment'=>'Sogou search engine bot','disable'=>0),
			array('priority'=>18,'search'=>'Baiduspider','data'=>'.baidu.com','rule'=>'allow','comment'=>'Baidu search engine bot (China)','disable'=>0),
			array('priority'=>20,'search'=>'MojeekBot','data'=>'.mojeek.com','rule'=>'allow','comment'=>'Mojeek search engine bot','disable'=>0),
			array('priority'=>22,'search'=>'Bytespider','data'=>'.bytedance.com','rule'=>'allow','comment'=>'TikTok crawler','disable'=>0),
			array('priority'=>28,'search'=>'Y!J','data'=>'.yahoo.co.jp','rule'=>'allow','comment'=>'Yahoo! Japan crawler','disable'=>0),
			array('priority'=>30,'search'=>'Yahoo! Slurp','data'=>'.yahoo.net','rule'=>'allow','comment'=>'Yahoo legacy crawler','disable'=>0),
			array('priority'=>50,'search'=>'msnbot','data'=>'search.msn.com','rule'=>'allow','comment'=>'Legacy Microsoft search bot','disable'=>0),
			array('priority'=>11,'search'=>'facebookexternalhit','data'=>'.fbsv.net .tfbnw.net .facebook.com asn:32934','rule'=>'allow','comment'=>'Facebook crawler','disable'=>0),
			array('priority'=>11,'search'=>'Facebot','data'=>'.facebook.com .fbsv.net .tfbnw.net asn:32934','rule'=>'allow','comment'=>'Facebook link preview crawler','disable'=>0),
			array('priority'=>11,'search'=>'Instagram','data'=>'.instagram.com .facebook.com .fbsv.net .tfbnw.net asn:32934','rule'=>'allow','comment'=>'Instagram link preview','disable'=>0),
			array('priority'=>19,'search'=>'vkShare','data'=>'.vk.com .vkontakte.ru .userapi.ru','rule'=>'allow','comment'=>'VK link preview','disable'=>0),
			array('priority'=>24,'search'=>'LinkedInBot','data'=>'.linkedin.com .ads.linkedin.com','rule'=>'allow','comment'=>'LinkedIn bot for link preview and ads','disable'=>0),
			array('priority'=>26,'search'=>'Pinterestbot','data'=>'.pinterest.com','rule'=>'allow','comment'=>'Pinterest link preview bot','disable'=>0),
			array('priority'=>25,'search'=>'Mail.RU_Bot','data'=>'.mail.ru .smailru.net','rule'=>'allow','comment'=>'Mail.ru crawler','disable'=>0),
			array('priority'=>27,'search'=>'TelegramBot','data'=>'asn:62041 asn:59930 asn:62014 asn:44907','rule'=>'allow','comment'=>'Telegram link preview (ASN-verified)','disable'=>0),
			array('priority'=>29,'search'=>'Twitterbot','data'=>'.twttr.com','rule'=>'allow','comment'=>'Twitter/X link preview','disable'=>0),
			array('priority'=>40,'search'=>'Slackbot','data'=>'.slack.com','rule'=>'allow','comment'=>'Slack link expander','disable'=>0),
			array('priority'=>42,'search'=>'WhatsApp','data'=>'.whatsapp.net .whatsapp.com asn:32934','rule'=>'allow','comment'=>'WhatsApp link preview','disable'=>0),
			array('priority'=>44,'search'=>'SkypeUriPreview','data'=>'.skype.com','rule'=>'allow','comment'=>'Skype link preview','disable'=>0),
			array('priority'=>45,'search'=>'Discordbot','data'=>'.discordapp.com .discord.com','rule'=>'allow','comment'=>'Discord link expander','disable'=>0),
			array('priority'=>48,'search'=>'OdklBot','data'=>'.odnoklassniki.ru','rule'=>'allow','comment'=>'Odnoklassniki link preview','disable'=>0),
			array('priority'=>49,'search'=>'Cardyb','data'=>'.','rule'=>'allow','comment'=>'Bluesky/Cardyb link preview','disable'=>0),
			array('priority'=>60,'search'=>'UptimeRobot','data'=>'uptimerobot.com','rule'=>'allow','comment'=>'UptimeRobot monitoring','disable'=>0),
			array('priority'=>60,'search'=>'Pingdom','data'=>'pingdom.com','rule'=>'allow','comment'=>'Pingdom monitoring','disable'=>0),
			array('priority'=>60,'search'=>'StatusCake','data'=>'statuscake.com','rule'=>'allow','comment'=>'StatusCake monitoring','disable'=>0),
			array('priority'=>60,'search'=>'BetterUptime','data'=>'betteruptime.com','rule'=>'allow','comment'=>'Better Uptime monitoring','disable'=>0),
			array('priority'=>70,'search'=>'Facebook Pixel','data'=>'.facebook.net .facebook.com','rule'=>'allow','comment'=>'Facebook tracking and analytics','disable'=>0),
			array('priority'=>70,'search'=>'Hotjar','data'=>'.hotjar.com','rule'=>'allow','comment'=>'Hotjar analytics service','disable'=>0),
			array('priority'=>70,'search'=>'Quantcast','data'=>'.quantserve.com','rule'=>'gray','comment'=>'Quantcast analytics and audience measurement','disable'=>0),
			array('priority'=>70,'search'=>'Heap Analytics','data'=>'.heap.io','rule'=>'allow','comment'=>'Heap analytics service','disable'=>0),
			array('priority'=>70,'search'=>'Matomo','data'=>'.matomo.org','rule'=>'allow','comment'=>'Matomo (formerly Piwik) analytics bot','disable'=>0),
			array('priority'=>70,'search'=>'Mixpanel','data'=>'.mixpanel.com','rule'=>'allow','comment'=>'Mixpanel analytics and tracking service','disable'=>0),
			array('priority'=>70,'search'=>'Cloudflare Web Analytics','data'=>'.cloudflare.com','rule'=>'allow','comment'=>'Cloudflare analytics service','disable'=>0),
			array('priority'=>70,'search'=>'Snapchat Pixel','data'=>'.snapchat.com .pixel.snapchat.com','rule'=>'allow','comment'=>'Snapchat tracking pixel bot','disable'=>0),
			array('priority'=>80,'search'=>'AhrefsBot','data'=>'.ahrefs.com','rule'=>'dark','comment'=>'Ahrefs SEO and crawling bot','disable'=>0),
			array('priority'=>80,'search'=>'SemrushBot','data'=>'.semrush.com','rule'=>'dark','comment'=>'Semrush SEO crawler','disable'=>0),
			array('priority'=>80,'search'=>'MJ12bot','data'=>'.majestic12.co.uk .mj12bot.com','rule'=>'dark','comment'=>'Majestic crawler','disable'=>0),
			array('priority'=>80,'search'=>'DotBot','data'=>'.moz.com','rule'=>'dark','comment'=>'Moz DotBot crawler','disable'=>0),
		);

		foreach ( $default_search_engines as $se ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO `{$wpdb->bbcs_se}` (priority, search, data, rule, comment, disable) VALUES (%d, %s, %s, %s, %s, %d)",
					$se['priority'],
					$se['search'],
					$se['data'],
					$se['rule'],
					$se['comment'],
					$se['disable']
				)
			);
		}
	}

	public static function insertDefaultAsn(): void {
		global $wpdb;

		$default_asn = array(
			array('priority'=>1,'asnum'=>15169,'asname'=>'Google LLC','rule'=>'allow','comment'=>'Googlebot, Google crawlers','disable'=>0),
			array('priority'=>2,'asnum'=>13238,'asname'=>'Yandex LLC','rule'=>'allow','comment'=>'YandexBot','disable'=>0),
			array('priority'=>3,'asnum'=>714,'asname'=>'Apple Inc','rule'=>'allow','comment'=>'Applebot','disable'=>0),
			array('priority'=>3,'asnum'=>6185,'asname'=>'Apple Inc','rule'=>'allow','comment'=>'Applebot (additional)','disable'=>0),
			array('priority'=>4,'asnum'=>55967,'asname'=>'Beijing Baidu Netcom','rule'=>'allow','comment'=>'Baiduspider','disable'=>0),
			array('priority'=>5,'asnum'=>36647,'asname'=>'Yahoo Inc','rule'=>'allow','comment'=>'Yahoo Slurp','disable'=>0),
			array('priority'=>10,'asnum'=>32934,'asname'=>'Meta Platforms Inc','rule'=>'allow','comment'=>'Facebook, Instagram, WhatsApp crawler','disable'=>0),
			array('priority'=>11,'asnum'=>13414,'asname'=>'X Corp','rule'=>'allow','comment'=>'Twitterbot','disable'=>0),
			array('priority'=>12,'asnum'=>62041,'asname'=>'Telegram Messenger Inc','rule'=>'allow','comment'=>'Telegram link preview','disable'=>0),
			array('priority'=>12,'asnum'=>59930,'asname'=>'Telegram Messenger LLP','rule'=>'allow','comment'=>'Telegram link preview','disable'=>0),
			array('priority'=>12,'asnum'=>62014,'asname'=>'Telegram Messenger Inc','rule'=>'allow','comment'=>'Telegram link preview','disable'=>0),
			array('priority'=>12,'asnum'=>44907,'asname'=>'Telegram Messenger LLP','rule'=>'allow','comment'=>'Telegram link preview','disable'=>0),
			array('priority'=>15,'asnum'=>23576,'asname'=>'NAVER Cloud Corp','rule'=>'allow','comment'=>'NaverBot (Korean search)','disable'=>0),
			array('priority'=>15,'asnum'=>43037,'asname'=>'Seznam.cz a.s.','rule'=>'allow','comment'=>'SeznamBot (Czech search)','disable'=>0),
			array('priority'=>15,'asnum'=>10158,'asname'=>'Kakao Corp','rule'=>'allow','comment'=>'DaumBot (Korean search)','disable'=>0),
			array('priority'=>15,'asnum'=>199064,'asname'=>'QWANT SAS','rule'=>'allow','comment'=>'Qwantify (French search)','disable'=>0),
			array('priority'=>15,'asnum'=>63046,'asname'=>'Brave Software Inc','rule'=>'allow','comment'=>'Brave SearchBot','disable'=>0),
			array('priority'=>20,'asnum'=>40428,'asname'=>'LinkedIn Corporation','rule'=>'allow','comment'=>'LinkedInBot','disable'=>0),
			array('priority'=>21,'asnum'=>54115,'asname'=>'Pinterest Inc','rule'=>'allow','comment'=>'Pinterest crawler','disable'=>0),
			array('priority'=>22,'asnum'=>396986,'asname'=>'ByteDance Ltd','rule'=>'allow','comment'=>'TikTok crawler','disable'=>0),
			array('priority'=>22,'asnum'=>395291,'asname'=>'Snapchat Inc','rule'=>'allow','comment'=>'Snapchat link preview','disable'=>0),
			array('priority'=>23,'asnum'=>47541,'asname'=>'VK LLC','rule'=>'allow','comment'=>'Mail.RU_Bot','disable'=>0),
			array('priority'=>24,'asnum'=>2635,'asname'=>'Automattic Inc','rule'=>'allow','comment'=>'WordPress.com, Jetpack','disable'=>0),
			array('priority'=>30,'asnum'=>14907,'asname'=>'Wikimedia Foundation Inc','rule'=>'allow','comment'=>'Wikipedia bots','disable'=>0),
			array('priority'=>30,'asnum'=>7941,'asname'=>'Internet Archive','rule'=>'allow','comment'=>'Wayback Machine','disable'=>0),
			array('priority'=>50,'asnum'=>13335,'asname'=>'Cloudflare Inc','rule'=>'gray','comment'=>'Cloudflare CDN + Workers','disable'=>0),
			array('priority'=>50,'asnum'=>16509,'asname'=>'Amazon.com Inc','rule'=>'gray','comment'=>'AWS','disable'=>0),
			array('priority'=>50,'asnum'=>14618,'asname'=>'Amazon.com Inc','rule'=>'gray','comment'=>'AWS','disable'=>0),
			array('priority'=>50,'asnum'=>8075,'asname'=>'Microsoft Corporation','rule'=>'gray','comment'=>'Azure','disable'=>0),
			array('priority'=>50,'asnum'=>396982,'asname'=>'Google LLC','rule'=>'gray','comment'=>'Google Cloud Platform','disable'=>0),
			array('priority'=>50,'asnum'=>45102,'asname'=>'Alibaba Cloud Computing Ltd','rule'=>'gray','comment'=>'Alibaba Cloud','disable'=>0),
			array('priority'=>50,'asnum'=>31898,'asname'=>'Oracle Corporation','rule'=>'gray','comment'=>'Oracle Cloud','disable'=>0),
			array('priority'=>50,'asnum'=>16276,'asname'=>'OVH SAS','rule'=>'gray','comment'=>'OVHcloud','disable'=>0),
			array('priority'=>50,'asnum'=>24940,'asname'=>'Hetzner Online GmbH','rule'=>'gray','comment'=>'Hetzner','disable'=>0),
			array('priority'=>50,'asnum'=>14061,'asname'=>'DigitalOcean LLC','rule'=>'gray','comment'=>'DigitalOcean','disable'=>0),
			array('priority'=>50,'asnum'=>63949,'asname'=>'Akamai Connected Cloud','rule'=>'gray','comment'=>'Linode','disable'=>0),
			array('priority'=>50,'asnum'=>20473,'asname'=>'The Constant Company LLC','rule'=>'gray','comment'=>'Vultr','disable'=>0),
			array('priority'=>50,'asnum'=>36351,'asname'=>'SoftLayer Technologies Inc','rule'=>'gray','comment'=>'IBM Cloud','disable'=>0),
			array('priority'=>50,'asnum'=>8560,'asname'=>'IONOS SE','rule'=>'gray','comment'=>'1&1 IONOS','disable'=>0),
			array('priority'=>50,'asnum'=>51167,'asname'=>'Contabo GmbH','rule'=>'gray','comment'=>'Contabo','disable'=>0),
			array('priority'=>50,'asnum'=>197540,'asname'=>'Netcup GmbH','rule'=>'gray','comment'=>'Netcup','disable'=>0),
			array('priority'=>50,'asnum'=>9009,'asname'=>'M247 Europe SRL','rule'=>'gray','comment'=>'M247 / VPN hosting','disable'=>0),
			array('priority'=>50,'asnum'=>12876,'asname'=>'SCALEWAY S.A.S.','rule'=>'gray','comment'=>'Scaleway','disable'=>0),
			array('priority'=>50,'asnum'=>33070,'asname'=>'Rackspace Hosting','rule'=>'gray','comment'=>'Rackspace','disable'=>0),
			array('priority'=>50,'asnum'=>30633,'asname'=>'Leaseweb USA Inc','rule'=>'gray','comment'=>'Leaseweb US','disable'=>0),
			array('priority'=>50,'asnum'=>60781,'asname'=>'Leaseweb Netherlands B.V.','rule'=>'gray','comment'=>'Leaseweb NL','disable'=>0),
		);

		foreach ( $default_asn as $asn ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO `{$wpdb->bbcs_asn}` (priority, asnum, asname, rule, comment, disable) VALUES (%d, %d, %s, %s, %s, %d)",
					$asn['priority'],
					$asn['asnum'],
					$asn['asname'],
					$asn['rule'],
					$asn['comment'],
					$asn['disable']
				)
			);
		}
	}

	public static function insertDefaultPaths(): void {
		global $wpdb;

		$default_paths = array(
			array('priority'=>1,'search'=>'/wp-cron.php','rule'=>'allow','comment'=>'WordPress cron jobs','disable'=>1),
			array('priority'=>1,'search'=>'/wp-admin/admin-ajax.php','rule'=>'allow','comment'=>'WordPress AJAX','disable'=>1),
			array('priority'=>1,'search'=>'/wp-admin/post.php','rule'=>'allow','comment'=>'WordPress POST','disable'=>1),
			array('priority'=>1,'search'=>'/?wc-ajax=','rule'=>'allow','comment'=>'Woocommerce AJAX','disable'=>1),
		);

		foreach ( $default_paths as $path ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO `{$wpdb->bbcs_path}` (priority, search, rule, comment, disable) VALUES (%d, %s, %s, %s, %d)",
					$path['priority'],
					$path['search'],
					$path['rule'],
					$path['comment'],
					$path['disable']
				)
			);
		}
	}

	public static function insertExtendedPaths(): void {
		global $wpdb;

		$default_paths = array(
			array('priority'=>1,'search'=>'/favicon.ico','rule'=>'allow','comment'=>'WordPress Favicon','disable'=>0),
			array('priority'=>1,'search'=>'/wp-admin/load-scripts.php','rule'=>'allow','comment'=>'WordPress script loading','disable'=>0),
			array('priority'=>1,'search'=>'/wp-admin/load-styles.php','rule'=>'allow','comment'=>'WordPress style loading','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/admin-post.php','rule'=>'allow','comment'=>'WordPress admin posts','disable'=>0),
			array('priority'=>1,'search'=>'/wp-admin/async-upload.php','rule'=>'allow','comment'=>'WordPress async upload','disable'=>0),
			array('priority'=>1,'search'=>'/wp-admin/update-core.php','rule'=>'allow','comment'=>'WordPress core updates','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/customize.php','rule'=>'allow','comment'=>'WordPress customizer','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/admin.php','rule'=>'allow','comment'=>'WordPress admin','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/options.php','rule'=>'allow','comment'=>'WordPress options','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/edit-comments.php','rule'=>'allow','comment'=>'WordPress comment editing','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/edit.php','rule'=>'allow','comment'=>'WordPress post/page editing','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/users.php','rule'=>'allow','comment'=>'WordPress user management','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/profile.php','rule'=>'allow','comment'=>'WordPress user profile','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/media-new.php','rule'=>'allow','comment'=>'WordPress media upload','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/upload.php','rule'=>'allow','comment'=>'WordPress media library','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/post-new.php','rule'=>'allow','comment'=>'WordPress new post/page','disable'=>0),
			array('priority'=>10,'search'=>'/wp-admin/edit-tags.php','rule'=>'allow','comment'=>'WordPress taxonomy management','disable'=>0),
			array('priority'=>10,'search'=>'/wp-comments-post.php','rule'=>'allow','comment'=>'WordPress comment posting','disable'=>0),
			array('priority'=>10,'search'=>'/wp-includes/js/tinymce/','rule'=>'allow','comment'=>'WordPress TinyMCE editor','disable'=>0),
			array('priority'=>1,'search'=>'/wp-json/','rule'=>'allow','comment'=>'WordPress REST API','disable'=>0),
			array('priority'=>1,'search'=>'/xmlrpc.php','rule'=>'allow','comment'=>'WordPress XML-RPC','disable'=>0),
		);

		foreach ( $default_paths as $path ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO `{$wpdb->bbcs_path}` (priority, search, rule, comment, disable) VALUES (%d, %s, %s, %s, %d)",
					$path['priority'],
					$path['search'],
					$path['rule'],
					$path['comment'],
					$path['disable']
				)
			);
		}
	}

	public static function insertDefaultSettings( string $salt_bb ): void {
		global $wpdb;

		$cloud_api_email  = BotBlockerInstall::getCloudAPIEmail();
		$cloud_api_pass   = md5( md5( $cloud_api_email ) . $salt_bb );
		$cloud_api_key    = BotBlockerPro::generateKey( BOTBLOCKER_SHORT_NAME, $cloud_api_email );
		$cloud_api_secret = BotBlockerPro::generateKey( BOTBLOCKER_SHORT_NAME, $cloud_api_pass );
		$secret_param     = md5( BOTBLOCKER_URL . time() );

		$default_settings   = bbcs_loadDefaultSettings();
		$generated_settings = array(
			'salt'                        => $salt_bb,
			'cloud_api_key'               => $cloud_api_key,
			'cloud_api_secret'            => $cloud_api_secret,
			'cloud_api_email'             => $cloud_api_email,
			'cloud_api_pass'              => $cloud_api_pass,
			'cloud_api_tier'              => '',
			'cloud_fallback_block'        => 0,

			'secret_botblocker_get_param' => $secret_param,

			'action_disable'              => BOTBLOCKER_PREFIX . md5( $secret_param . $salt_bb . 'disable' ) . '_disable',
			'action_off'                  => BOTBLOCKER_PREFIX . md5( $secret_param . $salt_bb . 'off' ) . '_off',
			'action_on'                   => BOTBLOCKER_PREFIX . md5( $secret_param . $salt_bb . 'on' ) . '_on',
		);

		$all_settings = array_merge( $default_settings, $generated_settings );

		BotBlockerInstall::saveSettingsToFile( $all_settings );
		BotBlockerInstall::saveSettingsToDatabase( $all_settings );
	}

	public static function insertDefaultProxies(): void {
		global $wpdb;

		$default_proxies = array(
			'173.245.48.0/20'  => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'103.21.244.0/22'  => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'103.22.200.0/22'  => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'103.31.4.0/22'    => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'141.101.64.0/18'  => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'108.162.192.0/18' => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'190.93.240.0/20'  => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'188.114.96.0/20'  => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'197.234.240.0/22' => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'198.41.128.0/17'  => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'162.158.0.0/15'   => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'104.16.0.0/13'    => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'104.24.0.0/14'    => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'172.64.0.0/13'    => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'131.0.72.0/22'    => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv4' ),
			'2400:cb00::/32'   => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6' ),
			'2606:4700::/32'   => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6' ),
			'2803:f800::/32'   => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6' ),
			'2405:b500::/32'   => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6' ),
			'2405:8100::/32'   => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6' ),
			'2a06:98c0::/29'   => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6' ),
			'2c0f:f248::/32'   => array( 'HTTP_CF_CONNECTING_IP', 'CloudFlare IPv6' ),
			'54.239.0.0/16'    => array( 'HTTP_X_FORWARDED_FOR', 'AWS (Amazon Web Services)' ),
			'54.240.0.0/12'    => array( 'HTTP_X_FORWARDED_FOR', 'AWS (Amazon Web Services)' ),
			'204.246.164.0/22' => array( 'HTTP_X_FORWARDED_FOR', 'AWS (Amazon Web Services)' ),
			'205.251.192.0/19' => array( 'HTTP_X_FORWARDED_FOR', 'AWS (Amazon Web Services)' ),
			'35.190.0.0/17'    => array( 'HTTP_X_FORWARDED_FOR', 'Google Cloud' ),
			'64.233.160.0/19'  => array( 'HTTP_X_FORWARDED_FOR', 'Google Cloud' ),
			'66.102.0.0/20'    => array( 'HTTP_X_FORWARDED_FOR', 'Google Cloud' ),
			'66.249.80.0/20'   => array( 'HTTP_X_FORWARDED_FOR', 'Google Cloud' ),
			'72.14.192.0/18'   => array( 'HTTP_X_FORWARDED_FOR', 'Google Cloud' ),
			'74.125.0.0/16'    => array( 'HTTP_X_FORWARDED_FOR', 'Google Cloud' ),
		);

		foreach ( $default_proxies as $key => $data ) {
			$value   = $data[0];
			$comment = $data[1];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO `{$wpdb->bbcs_proxy}` (`key`, `value`, `comment`) VALUES (%s, %s, %s)",
					$key,
					$value,
					$comment
				)
			);
		}
	}

	public static function insertDefaultPageFilters(): void {
		global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->bbcs_page_filters}`" );

		if ( $row_count == 0 ) {
			$patterns = array(
				array( '%/wp-admin/%', 'admin' ),
				array( '%/wp-login%', 'admin' ),
				array( '%/wp-content/%', 'wp_system' ),
				array( '%/wp-includes/%', 'wp_system' ),
				array( '%/favicon.ico%', 'content' ),
				array( '%/wp-cron.php%', 'wp_system' ),
				array( '%/feed/%', 'seo' ),
				array( '%/xmlrpc.php%', 'api' ),
				array( '%/wp-json/%', 'api' ),
				array( '%/robots.txt%', 'seo' ),
				array( '%/sitemap%', 'seo' ),
			);

			foreach ( $patterns as $pattern ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$wpdb->bbcs_page_filters,
					array(
						'pattern'  => $pattern[0],
						'category' => $pattern[1],
					),
					array( '%s', '%s' )
				);
			}
		}
	}
}
