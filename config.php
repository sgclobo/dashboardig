<?php
// config.php — smart loader
// On localhost: loads config.local.php (SQLite, no MySQL needed)
// On production: uses the constants defined below

$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true)
        || ($_SERVER['SERVER_PORT'] ?? '') === '8000';

if ($isLocal && file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
    return;
}

// ── PRODUCTION settings ────────────────────────────────────
// Set these as environment variables in Hostinger (recommended),
// OR replace the getenv() fallback values directly below.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'aifaesa_dashboard');
define('DB_USER', getenv('DB_USER') ?: 'your_db_user');   // ← set via env var or edit here
define('DB_PASS', getenv('DB_PASS') ?: 'your_db_password'); // ← set via env var or edit here
define('DB_SQLITE', '');

define('SITE_NAME', 'AIFAESA Dashboard');
define('CACHE_TTL',  5 * 60);
define('SESSION_NAME', 'aifaesa_dash');

date_default_timezone_set('Asia/Dili');

define('DEBUG', false);
ini_set('display_errors', 0);
error_reporting(0);
