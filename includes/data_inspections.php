<?php
// data/data_inspections.php
// Mirrors aifaesa.gov.tl/inspesoens/index.php
// Connects directly to the shared Hostinger MySQL server.

$host   = 'localhost';
$dbname = 'u781534777_aifaesa';
$user   = 'u781534777_SL_aifaesa';
$pass   = 'S3rgio@1060';   // ← paste new password here after changing in hPanel

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

// ── Column definitions (mirrors getColumns() in index.php) ──
$groups = [
    'Komersial' => [
        'distributor',
        'loja_elektronika',
        'loja_material_konstrusaun',
        'farmasia',
        'kioske',
        'loja_jenerika',
        'loja_agrikultura',
        'loja_alimentar',
        'loja_bens_konsumu',
        'loja_vestuariu',
        'ofisina',
        'supermerkadu',
        'armazen',
        'alfaiate',
        'pastelaria'
    ],
    'Industrial' => [
        'industria_batako',
        'industria_bee',
        'industria_kafe',
        'industria_tahu',
        'industria_tempe',
        'industria_alimentar_industrial',
        'industria_bebidas_alkolikas',
        'industria_bebidas_laos_alkolikas'
    ],
    'Turistiku' => [
        'agensia_viagem',
        'alojamentu',
        'artistika',
        'kafeteria',
        'catering',
        'diving',
        'espetaculo',
        'estabelecimento_bebidas',
        'fitnes',
        'hotelaria',
        'masajen',
        'restaurante',
        'salon',
        'barbeiru'
    ],
];

// ── Helper: build SUM expression for a group + prefix ───────
function sumExpr(string $prefix, array $fields): string
{
    return implode(' + ', array_map(fn($f) => "{$prefix}_{$f}", $fields));
}

// ── Inspections and Infractions totals per group ─────────────
$inspTotals  = [];
$infraTotals = [];

foreach ($groups as $groupName => $fields) {
    $exprInsp  = sumExpr('insp',  $fields);
    $exprInfra = sumExpr('infra', $fields);

    $row = $pdo->query("
        SELECT
            COALESCE(SUM($exprInsp), 0)  AS insp_all,
            COALESCE(SUM(CASE WHEN MONTH(report_date)=$month AND YEAR(report_date)=$year
                          THEN $exprInsp ELSE 0 END), 0) AS insp_month,
            COALESCE(SUM($exprInfra), 0) AS infra_all,
            COALESCE(SUM(CASE WHEN MONTH(report_date)=$month AND YEAR(report_date)=$year
                          THEN $exprInfra ELSE 0 END), 0) AS infra_month
        FROM aifaesa_inspection_data
    ")->fetch();

    $inspTotals[$groupName]  = ['total' => (int)$row['insp_all'],  'this_month' => (int)$row['insp_month']];
    $infraTotals[$groupName] = ['total' => (int)$row['infra_all'], 'this_month' => (int)$row['infra_month']];
}

// ── Actions (closures / reactivations) ───────────────────────
$actionCols = [
    'temporary_closure'  => 'enseramentu_temporariu',
    'permanent_closure'  => 'enseramentu_permanente',
    'reactivated'        => 'estabelecimento_reativadu',
];

$actions = [];
foreach ($actionCols as $key => $col) {
    $row = $pdo->query("
        SELECT
            COALESCE(SUM($col), 0) AS total,
            COALESCE(SUM(CASE WHEN MONTH(report_date)=$month AND YEAR(report_date)=$year
                          THEN $col ELSE 0 END), 0) AS this_month
        FROM aifaesa_inspection_data
    ")->fetch();
    $actions[$key] = ['total' => (int)$row['total'], 'this_month' => (int)$row['this_month']];
}

// ── Grand totals across all groups ───────────────────────────
$totalInspAll   = array_sum(array_column($inspTotals,  'total'));
$totalInspMonth = array_sum(array_column($inspTotals,  'this_month'));
$totalInfraAll  = array_sum(array_column($infraTotals, 'total'));
$totalInfraMonth = array_sum(array_column($infraTotals, 'this_month'));

// ── Monthly trend for current year ───────────────────────────
$allFields = array_merge(...array_values($groups));
$exprAll   = sumExpr('insp', $allFields);

$monthlyRows = $pdo->query("
    SELECT MONTH(report_date) as month,
           COALESCE(SUM($exprAll), 0) as inspections,
           COUNT(*) as reports
    FROM aifaesa_inspection_data
    WHERE YEAR(report_date) = $year
    GROUP BY MONTH(report_date)
    ORDER BY month
")->fetchAll();

$monthNames  = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthlyData = array_fill(1, 12, ['inspections' => 0, 'reports' => 0]);
foreach ($monthlyRows as $r) {
    $monthlyData[(int)$r['month']] = [
        'inspections' => (int) $r['inspections'],
        'reports'     => (int) $r['reports'],
    ];
}

// ── Return structured data ────────────────────────────────────
return [
    // ── Stat cards ──────────────────────────────────────────
    'total_inspections'          => $totalInspAll,
    'inspections_this_month'     => $totalInspMonth,
    'total_infractions'          => $totalInfraAll,
    'infractions_this_month'     => $totalInfraMonth,

    // Per-group inspections
    'komersial_inspections'      => $inspTotals['Komersial']['total'],
    'komersial_this_month'       => $inspTotals['Komersial']['this_month'],
    'industrial_inspections'     => $inspTotals['Industrial']['total'],
    'industrial_this_month'      => $inspTotals['Industrial']['this_month'],
    'turistiku_inspections'      => $inspTotals['Turistiku']['total'],
    'turistiku_this_month'       => $inspTotals['Turistiku']['this_month'],

    // Actions
    'temporary_closures'         => $actions['temporary_closure']['total'],
    'temporary_closures_month'   => $actions['temporary_closure']['this_month'],
    'permanent_closures'         => $actions['permanent_closure']['total'],
    'permanent_closures_month'   => $actions['permanent_closure']['this_month'],
    'establishments_reactivated' => $actions['reactivated']['total'],
    'reactivated_this_month'     => $actions['reactivated']['this_month'],

    // ── Chart data (prefixed _ so stat cards skip them) ─────
    '_monthly_chart' => array_values(array_map(
        fn($m, $d) => [
            'month'        => $monthNames[$m - 1],
            'inspections'  => $d['inspections'],
            'reports'      => $d['reports'],
        ],
        array_keys($monthlyData),
        $monthlyData
    )),

    '_group_breakdown' => [
        'Komersial'  => $inspTotals['Komersial']['total'],
        'Industrial' => $inspTotals['Industrial']['total'],
        'Turistiku'  => $inspTotals['Turistiku']['total'],
    ],
];
