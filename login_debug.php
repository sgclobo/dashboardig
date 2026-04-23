<?php
// login_debug.php — TEMPORARY diagnostic. DELETE after fixing login.
// Only accessible with the correct debug token.
define('DEBUG_TOKEN', 'AIFAESA_DBG_2026');

if (($_GET['token'] ?? '') !== DEBUG_TOKEN) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

// 1. Test intranet DB connection
echo "=== Intranet DB Connection ===\n";
try {
    $db = intranet_db();
    echo "OK: Connected to " . SOURCE_DB_NAME . "\n\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    exit;
}

// 2. Check if 'users' table exists and show columns
echo "=== Table: users (columns) ===\n";
try {
    $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll();
    foreach ($cols as $c) {
        echo "  " . $c['Field'] . " [" . $c['Type'] . "]" .
            ($c['Null'] === 'YES' ? ' NULL' : '') . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n\n";
}

// 3. Show sample usernames (no passwords)
echo "=== Sample usernames (first 5 rows) ===\n";
try {
    $rows = $db->query("SELECT user_id, username FROM users LIMIT 5")->fetchAll();
    foreach ($rows as $r) {
        echo "  user_id=" . $r['user_id'] . " username=" . $r['username'] . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n\n";
}

// 4. Show password hash prefix for one user (first 10 chars only — enough to detect algorithm)
$testUser = $_GET['u'] ?? '';
if ($testUser !== '') {
    echo "=== Password hash prefix for user: " . htmlspecialchars($testUser) . " ===\n";
    try {
        $st = $db->prepare("SELECT password FROM users WHERE LOWER(username) = LOWER(?)");
        $st->execute([trim($testUser)]);
        $row = $st->fetch();
        if ($row) {
            $hash = $row['password'];
            echo "  Hash prefix (first 10 chars): " . substr($hash, 0, 10) . "\n";
            echo "  Hash length: " . strlen($hash) . "\n";
            echo "  bcrypt ($2y$): " . (str_starts_with($hash, '$2y$') ? 'YES' : 'NO') . "\n";
            echo "  MD5 (32 hex): " . (preg_match('/^[a-f0-9]{32}$/', $hash) ? 'YES' : 'NO') . "\n";
            echo "  SHA1 (40 hex): " . (preg_match('/^[a-f0-9]{40}$/', $hash) ? 'YES' : 'NO') . "\n";
        } else {
            echo "  User not found.\n";
        }
    } catch (Exception $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
    }
}
