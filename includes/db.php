<?php
// includes/db.php — PDO singleton, works with SQLite (local) and MySQL (production)
require_once __DIR__ . '/../config.php';

function intranet_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $pdo = new PDO(
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
        if (defined('DEBUG') && DEBUG) {
            die('<pre style="color:red">Intranet DB Error: ' . $e->getMessage() . '</pre>');
        }
        die('Intranet database connection failed.');
    }

    return $pdo;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        if (defined('DB_SQLITE') && DB_SQLITE !== '') {
            $dir = dirname(DB_SQLITE);
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $pdo = new PDO('sqlite:' . DB_SQLITE, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
            _sqlite_migrate($pdo);
        } else {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }
    } catch (PDOException $e) {
        if (defined('DEBUG') && DEBUG) {
            die('<pre style="color:red">DB Error: ' . $e->getMessage() . '</pre>');
        }
        die('Database connection failed.');
    }

    return $pdo;
}

function _sqlite_migrate(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dashboard_users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT NOT NULL,
            email      TEXT NOT NULL UNIQUE,
            password   TEXT NOT NULL,
            role       TEXT NOT NULL DEFAULT 'viewer',
            last_login TEXT,
            created_at TEXT DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS data_sources (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            slug       TEXT NOT NULL UNIQUE,
            label      TEXT NOT NULL,
            icon       TEXT DEFAULT 'circle',
            api_url    TEXT NOT NULL,
            api_token  TEXT NOT NULL,
            tab_order  INTEGER DEFAULT 99,
            active     INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS api_cache (
            source_id  INTEGER PRIMARY KEY,
            payload    TEXT NOT NULL,
            fetched_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (source_id) REFERENCES data_sources(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS access_log (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id     INTEGER,
            section     TEXT,
            ip          TEXT NOT NULL,
            accessed_at TEXT DEFAULT (datetime('now'))
        );
    ");

    $count = $pdo->query('SELECT COUNT(*) FROM dashboard_users')->fetchColumn();
    if ((int)$count === 0) {
        $pdo->prepare("INSERT INTO dashboard_users (name,email,password,role) VALUES (?,?,?,?)")
            ->execute([
                'Administrator',
                'admin@aifaesa.org',
                password_hash('Admin@1234', PASSWORD_BCRYPT),
                'admin'
            ]);
    }

    $count = $pdo->query('SELECT COUNT(*) FROM data_sources')->fetchColumn();
    if ((int)$count === 0) {
        $rows = [
            ['inspections', 'Inspections',   'clipboard-check',   'https://seksaunit.aifaesa.org/api/stats.php', 'CHANGE_TOKEN_1', 1],
            ['fines',      'Fines',         'exclamation-circle', 'https://seksaunit.aifaesa.org/api/fines.php', 'CHANGE_TOKEN_1', 2],
            ['hr',         'Human Resources', 'users',            'https://personalia.aifaesa.org/api/stats.php', 'CHANGE_TOKEN_2', 3],
            ['logistics',  'Logistics',     'truck',             'https://lojistika.aifaesa.org/api/stats.php', 'CHANGE_TOKEN_3', 4],
            ['it',         'IT',            'server',            'https://it.aifaesa.org/api/stats.php',        'CHANGE_TOKEN_4', 5],
            ['portal',     'S.I.P',         'globe',             'https://aifaesa.gov.tl/api/stats.php',        'CHANGE_TOKEN_5', 6],
        ];
        $st = $pdo->prepare("INSERT INTO data_sources (slug,label,icon,api_url,api_token,tab_order) VALUES (?,?,?,?,?,?)");
        foreach ($rows as $r) $st->execute($r);
    }

    // Keep the local SQLite seed in sync for existing databases.
    $pdo->prepare("UPDATE data_sources SET label = ? WHERE slug = ?")
        ->execute(['S.I.P', 'portal']);
}
