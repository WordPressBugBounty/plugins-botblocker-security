<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return [
    // Bots pending special handling (not yet added to bbcs_se table).
    // All other known bots are managed via bbcs_se (search_engines.php).
    'bbcs_good_bots' => [
        // Pending: Telegram does not publish PTR records - requires separate IP-based handling.
        'TelegramBot' => ['.telegram.org'],
    ],
];
