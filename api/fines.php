<?php
// api/fines.php — Fines statistics JSON endpoint
// Called by fetcher.php with X-Api-Token header

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if (!defined('TOKEN_FINES') || $token !== TOKEN_FINES) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

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
    http_response_code(503);
    echo json_encode(['error' => 'Source database unavailable']);
    exit;
}

$y25 = $pdo->query(
    "SELECT COUNT(*) AS c, COALESCE(SUM(total_value), 0) AS t
     FROM fine_payments WHERE YEAR(payment_date) = 2025"
)->fetch();

$y26 = $pdo->query(
    "SELECT COUNT(*) AS c, COALESCE(SUM(total_value), 0) AS t
     FROM fine_payments WHERE YEAR(payment_date) = 2026"
)->fetch();

$stats = $pdo->query(
    "SELECT MAX(total_value) AS mx, MIN(total_value) AS mn, AVG(total_value) AS av
     FROM fine_payments"
)->fetch();

$m25 = $pdo->query(
    "SELECT MONTH(payment_date) AS m, SUM(total_value) AS t, COUNT(*) AS c
     FROM fine_payments WHERE YEAR(payment_date) = 2025
     GROUP BY m ORDER BY m"
)->fetchAll();

$m26 = $pdo->query(
    "SELECT MONTH(payment_date) AS m, SUM(total_value) AS t, COUNT(*) AS c
     FROM fine_payments WHERE YEAR(payment_date) = 2026
     GROUP BY m ORDER BY m"
)->fetchAll();

$recent = $pdo->query(
    "SELECT payment_date, payer_name, business_name, total_value
     FROM fine_payments ORDER BY payment_date DESC LIMIT 10"
)->fetchAll();

echo json_encode([
    'Fines 2025 (count)' => (int) $y25['c'],
    'Fines 2025 ($)'     => round((float) $y25['t'], 2),
    'Fines 2026 (count)' => (int) $y26['c'],
    'Fines 2026 ($)'     => round((float) $y26['t'], 2),
    'Max Fine ($)'       => round((float) ($stats['mx'] ?? 0), 2),
    'Min Fine ($)'       => round((float) ($stats['mn'] ?? 0), 2),
    'Avg Fine ($)'       => round((float) ($stats['av'] ?? 0), 2),
    '_monthly_2025'      => $m25,
    '_monthly_2026'      => $m26,
    '_recent'            => $recent,
]);
