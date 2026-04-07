<?php
// data/data_fines.php
// Connects directly to the aifaesa.gov.tl MySQL database (same Hostinger server).
// Called by fetcher.php when the 'fines' tab is loaded.

$host   = 'localhost';
$dbname = 'u781534777_aifaesa';
$user   = 'u781534777_SL_aifaesa';
$pass   = 'S3rgio@1060';   // ← update after changing it in hPanel

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    return ['error' => 'DB connection failed: ' . $e->getMessage()];
}

$year  = (int) date('Y');
$month = (int) date('m');

// ── Yearly totals ───────────────────────────────────────────
$stYear = $pdo->prepare(
    "SELECT COUNT(*) as count, COALESCE(SUM(total_value), 0) as total
     FROM fine_payments WHERE YEAR(payment_date) = ?"
);

$stYear->execute([2025]);
$y2025 = $stYear->fetch();

$stYear->execute([2026]);
$y2026 = $stYear->fetch();

// ── This month ──────────────────────────────────────────────
$stMonth = $pdo->prepare(
    "SELECT COUNT(*) as count, COALESCE(SUM(total_value), 0) as total
     FROM fine_payments WHERE YEAR(payment_date) = ? AND MONTH(payment_date) = ?"
);
$stMonth->execute([$year, $month]);
$thisMonth = $stMonth->fetch();

// ── Overall stats ───────────────────────────────────────────
$stats = $pdo->query(
    "SELECT COUNT(*) as total_records,
            COALESCE(SUM(total_value), 0)  as grand_total,
            COALESCE(MAX(total_value), 0)  as highest_fine,
            COALESCE(MIN(total_value), 0)  as lowest_fine,
            COALESCE(AVG(total_value), 0)  as average_fine
     FROM fine_payments"
)->fetch();

// ── Monthly breakdown current year ──────────────────────────
$monthly = $pdo->prepare(
    "SELECT MONTH(payment_date) as month,
            SUM(total_value)    as monthly_total,
            COUNT(*)            as fine_count
     FROM fine_payments
     WHERE YEAR(payment_date) = ?
     GROUP BY MONTH(payment_date)
     ORDER BY month"
);
$monthly->execute([$year]);
$monthlyRows = $monthly->fetchAll();

// Build a full 12-month array (0 for missing months)
$monthNames  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthlyData = array_fill(1, 12, ['total' => 0, 'count' => 0]);
foreach ($monthlyRows as $r) {
    $monthlyData[(int)$r['month']] = [
        'total' => (float) $r['monthly_total'],
        'count' => (int)   $r['fine_count'],
    ];
}

// ── Return structured data for the dashboard ────────────────
return [
    // Stat cards (shown as tiles)
    'fines_this_month'        => (int)   $thisMonth['count'],
    'value_this_month_USD'    => (float) $thisMonth['total'],
    'fines_2025'              => (int)   $y2025['count'],
    'total_value_2025_USD'    => (float) $y2025['total'],
    'fines_2026'              => (int)   $y2026['count'],
    'total_value_2026_USD'    => (float) $y2026['total'],
    'all_time_total'          => (int)   $stats['total_records'],
    'grand_total_USD'         => (float) $stats['grand_total'],
    'highest_fine_USD'        => (float) $stats['highest_fine'],
    'average_fine_USD'        => round((float) $stats['average_fine'], 2),

    // Chart data (prefixed with _ so stat cards ignore them)
    '_monthly_chart'          => array_values(array_map(
        fn($m, $d) => ['month' => $monthNames[$m-1], 'total' => $d['total'], 'count' => $d['count']],
        array_keys($monthlyData), $monthlyData
    )),
];