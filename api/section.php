<?php
// api/section.php — called via fetch() from dashboard.js
// Returns JSON stats for one data source slug.
// Priority: 1) local data/data_{slug}.php  2) DB-backed remote fetch

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/fetcher.php';

header('Content-Type: application/json');

// Must be logged in
session_start_safe();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$slug = trim($_GET['source'] ?? '');
if (!preg_match('/^[a-z0-9_\-]{1,50}$/', $slug)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid source']);
    exit;
}

// ── 1. Try local data file first ─────────────────────────────────────────
$dataDir  = realpath(__DIR__ . '/../data');
$safeSlug = preg_replace('/[^a-z0-9_\-]/i', '', $slug);
$localFile = $dataDir . DIRECTORY_SEPARATOR . 'data_' . $safeSlug . '.php';

// Verify the resolved path is still inside /data/ (path-traversal guard)
if ($dataDir !== false && is_file($localFile) && str_starts_with(realpath($localFile), $dataDir . DIRECTORY_SEPARATOR)) {
    $data = require $localFile;
    if (is_array($data) && !empty($data)) {
        echo json_encode($data);
        exit;
    }
}

// ── 2. Fall back to DB-backed source ─────────────────────────────────────
$st = db()->prepare('SELECT * FROM data_sources WHERE slug = ? AND active = 1');
$st->execute([$slug]);
$source = $st->fetch();

if (!$source) {
    http_response_code(404);
    echo json_encode(['error' => "Source '$slug' not found or inactive"]);
    exit;
}

$data = fetch_source($source);

echo json_encode($data);