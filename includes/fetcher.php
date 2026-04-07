<?php
// includes/fetcher.php — calls each subdomain API, caches result
require_once __DIR__ . '/db.php';

/**
 * Get data for one source slug (e.g. 'hr').
 * Returns decoded array or ['error' => '...'].
 */
function fetch_source(array $source): array {
    $pdo = db();
    $id  = (int) $source['id'];

    // Check cache first
    $st = $pdo->prepare('SELECT payload, fetched_at FROM api_cache WHERE source_id = ?');
    $st->execute([$id]);
    $cached = $st->fetch();

    if ($cached && (time() - strtotime($cached['fetched_at'])) < CACHE_TTL) {
        return json_decode($cached['payload'], true) ?? ['error' => 'bad cache'];
    }

    // Fetch fresh
    $ctx = stream_context_create(['http' => [
        'method'  => 'GET',
        'header'  => "X-Api-Token: {$source['api_token']}\r\nAccept: application/json\r\n",
        'timeout' => 6,
        'ignore_errors' => true,
    ]]);

    $raw  = @file_get_contents($source['api_url'], false, $ctx);
    $data = $raw ? (json_decode($raw, true) ?? ['error' => 'invalid JSON']) : ['error' => 'unreachable'];

    // Upsert cache
    $pdo->prepare(
        'INSERT INTO api_cache (source_id, payload, fetched_at)
         VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE payload = VALUES(payload), fetched_at = NOW()'
    )->execute([$id, json_encode($data)]);

    return $data;
}

/**
 * Load all active sources from DB.
 */
function all_sources(): array {
    return db()
        ->query('SELECT * FROM data_sources WHERE active = 1 ORDER BY tab_order')
        ->fetchAll();
}
