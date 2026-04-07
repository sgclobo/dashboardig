<?php
// includes/data_fines.php — direct DB query for fines data
// Called by fetcher.php to bypass loopback HTTP limitations

function _fetch_fines(PDO $pdo): array
{
    $y25 = $pdo->query(
        "SELECT COUNT(*) AS c, COALESCE(SUM(total_value),0) AS t
         FROM fine_payments WHERE YEAR(payment_date)=2025"
    )->fetch();

    $y26 = $pdo->query(
        "SELECT COUNT(*) AS c, COALESCE(SUM(total_value),0) AS t
         FROM fine_payments WHERE YEAR(payment_date)=2026"
    )->fetch();

    $stats = $pdo->query(
        "SELECT MAX(total_value) AS mx, MIN(total_value) AS mn, AVG(total_value) AS av
         FROM fine_payments"
    )->fetch();

    $m25 = $pdo->query(
        "SELECT MONTH(payment_date) AS m, SUM(total_value) AS t, COUNT(*) AS c
         FROM fine_payments WHERE YEAR(payment_date)=2025
         GROUP BY m ORDER BY m"
    )->fetchAll();

    $m26 = $pdo->query(
        "SELECT MONTH(payment_date) AS m, SUM(total_value) AS t, COUNT(*) AS c
         FROM fine_payments WHERE YEAR(payment_date)=2026
         GROUP BY m ORDER BY m"
    )->fetchAll();

    $recent = $pdo->query(
        "SELECT payment_date, payer_name, business_name, total_value
         FROM fine_payments ORDER BY payment_date DESC LIMIT 10"
    )->fetchAll();

    return [
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
    ];
}
