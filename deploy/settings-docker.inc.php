<?php

/**
 * Site Settings for Docker Environment
 *
 * This file is copied to htdocs/includes/settings.inc.php when running in Docker.
 */

// The current session (as defined by Richmond Sunlight's database)
define('SESSION_ID', 31);

// Is this the main session or a special session?
define('SESSION_SUFFIX', '');

// As defined by the GA LIS' database
define('SESSION_LIS_ID', '251');

// As defined by the year
define('SESSION_YEAR', 2025);

// Start and end of this session
define('SESSION_START', '2025-01-10');
define('SESSION_END', '2025-02-25');

// Set the FTP auth pair for legislative data (not needed for API)
define('LIS_FTP_USERNAME', '');
define('LIS_FTP_PASSWORD', '');

// Database connection - uses Docker service name 'db'
define('PDO_DSN', 'mysql:host=db;dbname=richmondsunlight');
define('PDO_SERVER', 'db');
define('PDO_USERNAME', 'ricsun');
define('PDO_PASSWORD', 'password');
define('MYSQL_DATABASE', 'richmondsunlight');

// The API URL (self-referential in this context)
define('API_URL', 'http://localhost:5001/');

// Memcached connection - uses Docker service name 'memcached'
define('MEMCACHED_SERVER', 'memcached');
define('MEMCACHED_PORT', '11211');

// Configure PHP sessions to use Memcached
ini_set('session.save_handler', 'memcached');
ini_set('session.save_path', MEMCACHED_SERVER . ':' . MEMCACHED_PORT);

// House Speaker IDs (used for vote translation)
define('HOUSE_SPEAKER_LIS_ID', 'H322');
define('HOUSE_SPEAKER_ID', '455');

// Cache directory (not heavily used by API)
define('CACHE_DIR', '/tmp/cache/');

// API keys (not needed for basic API functionality)
define('GMAPS_KEY', '');
define('OPENSTATES_KEY', '');
define('OPENVA_KEY', '');
define('VA_DECODED_KEY', '');
define('MAPBOX_TOKEN', '');
define('LIS_KEY', '');
define('SLACK_WEBHOOK', '');
define('OPENAI_KEY', '');
define('AWS_ACCESS_KEY', '');
define('AWS_SECRET_KEY', '');

// Logging verbosity (1-8)
define('LOG_VERBOSITY', 3);

// Banned words list (for content moderation)
$GLOBALS['banned_words'] = array('fuvg','shpx','nffubyr','chffl','phag','shpxre','zbgureshpxre',
    'shpxvat','pbpxfhpxre','gjng','qvpxurnq');
foreach ($GLOBALS['banned_words'] as &$word) {
    $word = str_rot13($word);
}

// Locale and timezone
setlocale(LC_MONETARY, 'en_US');
date_default_timezone_set('America/New_York');

// Dynamically determine session status
if (
    time() >= strtotime(SESSION_START)
    &&
    time() <= strtotime(SESSION_END)
) {
    define('IN_SESSION', true);
} else {
    define('IN_SESSION', false);
}

if (date('n') >= 11 || date('n') <= 4) {
    define('LEGISLATIVE_SEASON', true);
} else {
    define('LEGISLATIVE_SEASON', false);
}
