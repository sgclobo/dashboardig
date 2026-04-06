<?php
// includes/fetcher.php
// Priority:
//   1. Local data file  → data/data_{slug}.php  (returns an array)
//   2. Remote API       → source api_url with token
//   3. Cached response  → last successful fetch stored in api_cache table
require_once __DIR__ . '/db.php';

/**
 * Get data for one source row from data_sources.
 * Returns a plain associative array (never throws).
 */
function fetch_source(array $source): array {
    $slug = $source['slug'];

    // ── 1. Local data file (highest priority) ──────────────
    $localFile = __DIR__ . '/../data/data_' . $slug . '.php';
    if (file_exists($localFile)) {
        $data = require $localFile;
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }

    // ── 2. Remote API ───────────────────────────────────────
    $pdo = db();
    $id  = (int) $source['id'];

    // Check cache freshness
    $st = $pdo->prepare('SELECT payload, fetched_at FROM api_cache WHERE source_id = ?');
    $st->execute([$id]);
    $cached = $st->fetch();

    if ($cached && (time() - strtotime($cached['fetched_at'])) < CACHE_TTL) {
        $decoded = json_decode($cached['payload'], true);
        if (is_array($decoded)) return $decoded;
    }

    // Fetch from remote
    $ctx = stream_context_create(['http' => [
        'method'        => 'GET',
        'header'        => "X-Api-Token: {$source['api_token']}\r\nAccept: application/json\r\n",
        'timeout'       => 6,
        'ignore_errors' => true,
    ]]);

    $raw  = @file_get_contents($source['api_url'], false, $ctx);
    $data = $raw ? (json_decode($raw, true) ?? ['error' => 'invalid JSON']) : ['error' => 'unreachable'];

    // Only cache successful responses
    if (!isset($data['error'])) {
        $pdo->prepare(
            'INSERT INTO api_cache (source_id, payload, fetched_at)
             VALUES (?, ?, datetime(\'now\'))
             ON CONFLICT(source_id) DO UPDATE SET payload=excluded.payload, fetched_at=excluded.fetched_at'
        )->execute([$id, json_encode($data)]);
    }

    // ── 3. Fall back to stale cache if remote failed ────────
    if (isset($data['error']) && $cached) {
        $decoded = json_decode($cached['payload'], true);
        if (is_array($decoded)) {
            $decoded['_stale'] = true;
            return $decoded;
        }
    }

    return $data;
}

/**
 * Load all active sources from DB ordered by tab_order.
 */
function all_sources(): array {
    return db()
        ->query('SELECT * FROM data_sources WHERE active = 1 ORDER BY tab_order')
        ->fetchAll();
}