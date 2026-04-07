<?php
// includes/fetcher.php — fetches data for each source, caches result
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/data_inspections.php';
require_once __DIR__ . '/data_fines.php';

// Slugs served directly from this server's own DB (no loopback HTTP needed)
const INTERNAL_SLUGS = ['inspections', 'fines'];

/**
 * Get data for one source slug.
 * Returns decoded array or ['error' => '...'].
 */
function fetch_source(array $source): array
{
    $pdo = db();
    $id  = (int) $source['id'];

    // Check cache first
    $st = $pdo->prepare('SELECT payload, fetched_at FROM api_cache WHERE source_id = ?');
    $st->execute([$id]);
    $cached = $st->fetch();

    if ($cached && (time() - strtotime($cached['fetched_at'])) < CACHE_TTL) {
        return json_decode($cached['payload'], true) ?? ['error' => 'bad cache'];
    }

    // Internal sources: query source DB directly (avoids loopback HTTP blocks)
    if (in_array($source['slug'], INTERNAL_SLUGS, true)) {
        $data = _fetch_internal($source);
    } else {
        // External: HTTP fetch
        $ctx  = stream_context_create(['http' => [
            'method'        => 'GET',
            'header'        => "X-Api-Token: {$source['api_token']}\r\nAccept: application/json\r\n",
            'timeout'       => 6,
            'ignore_errors' => true,
        ]]);
        $raw  = @file_get_contents($source['api_url'], false, $ctx);
        $data = $raw
            ? (json_decode($raw, true) ?? ['error' => 'invalid JSON'])
            : ['error' => 'unreachable'];
    }

    // Upsert cache
    $pdo->prepare(
        'INSERT INTO api_cache (source_id, payload, fetched_at)
         VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE payload = VALUES(payload), fetched_at = NOW()'
    )->execute([$id, json_encode($data)]);

    return $data;
}

/**
 * Connect to the source DB and dispatch to the appropriate query function.
 */
function _fetch_internal(array $source): array
{
    if (!defined('SOURCE_DB_HOST') || SOURCE_DB_HOST === '') {
        return ['error' => 'Source DB not configured'];
    }

    try {
        $srcPdo = new PDO(
            'mysql:host=' . SOURCE_DB_HOST . ';dbname=' . SOURCE_DB_NAME . ';charset=utf8mb4',
            SOURCE_DB_USER,
            SOURCE_DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        return ['error' => 'Source database unavailable'];
    }

    switch ($source['slug']) {
        case 'inspections':
            return _fetch_inspections($srcPdo);
        case 'fines':
            return _fetch_fines($srcPdo);
        default:
            return ['error' => 'Unknown internal source'];
    }
}

/**
 * Load all active sources from DB.
 */
function all_sources(): array
{
    return db()
        ->query('SELECT * FROM data_sources WHERE active = 1 ORDER BY tab_order')
        ->fetchAll();
}
