<?php
// api/inspections.php — Inspections statistics JSON endpoint
// Called by fetcher.php with X-Api-Token header

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if (!defined('TOKEN_INSPECTIONS') || $token !== TOKEN_INSPECTIONS) {
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

$year  = (int) date('Y');
$month = (int) date('m');

$columnGroups = [
    'Komersial'  => [
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
        'pastelaria',
    ],
    'Industrial' => [
        'industria_batako',
        'industria_bee',
        'industria_kafe',
        'industria_tahu',
        'industria_tempe',
        'industria_alimentar_industrial',
        'industria_bebidas_alkolikas',
        'industria_bebidas_laos_alkolikas',
    ],
    'Turistiku'  => [
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
        'barbeiru',
    ],
];

$out = [];

foreach (['insp' => 'Inspected', 'infra' => 'Infractions'] as $prefix => $label) {
    foreach ($columnGroups as $group => $fields) {
        $expr = implode('+', array_map(fn($f) => "`{$prefix}_{$f}`", $fields));
        $row  = $pdo->query(
            "SELECT COALESCE(SUM($expr), 0) AS total,
                    COALESCE(SUM(CASE WHEN MONTH(report_date) = $month
                        AND YEAR(report_date) = $year THEN $expr ELSE 0 END), 0) AS month
             FROM aifaesa_inspection_data"
        )->fetch();
        $out["{$label} {$group}"]          = (int) $row['total'];
        $out["{$label} {$group} (month)"]  = (int) $row['month'];
    }
}

foreach (
    [
        'Temp Closures'  => 'enseramentu_temporariu',
        'Perm Closures'  => 'enseramentu_permanente',
        'Reactivated'    => 'estabelecimento_reativadu',
    ] as $label => $col
) {
    $row = $pdo->query(
        "SELECT COALESCE(SUM(`$col`), 0) AS total,
                COALESCE(SUM(CASE WHEN MONTH(report_date) = $month
                    AND YEAR(report_date) = $year THEN `$col` ELSE 0 END), 0) AS month
         FROM aifaesa_inspection_data"
    )->fetch();
    $out[$label]             = (int) $row['total'];
    $out["{$label} (month)"] = (int) $row['month'];
}

$out['_recent'] = $pdo->query(
    'SELECT report_date, munisipiu, inspection_type
     FROM aifaesa_inspection_data
     ORDER BY report_date DESC LIMIT 10'
)->fetchAll();

echo json_encode($out);
