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
define('DB_HOST', 'localhost');
define('DB_NAME', 'u781534777_dashboard');
define('DB_USER', 'u781534777_dashboarduser');
define('DB_PASS', 'S3rgio@1060');
define('DB_SQLITE', '');

// ── Source DB (inspections & fines — aifaesa.gov.tl) ──────
define('SOURCE_DB_HOST', 'localhost');
define('SOURCE_DB_NAME', 'u781534777_aifaesa');
define('SOURCE_DB_USER', 'u781534777_SL_aifaesa');
define('SOURCE_DB_PASS', 'S3rgio@1060');

// ── Internal API tokens ───────────────────────────────────
define('TOKEN_INSPECTIONS', 'AIFAESA_INSP_API_2026');
define('TOKEN_FINES',       'AIFAESA_KOIMAS_API_2026');

define('SITE_NAME', 'AIFAESA Dashboard');
define('CACHE_TTL',  5 * 60);
define('SESSION_NAME', 'aifaesa_dash');

date_default_timezone_set('Asia/Dili');

define('DEBUG', false);
ini_set('display_errors', 0);
error_reporting(0);
