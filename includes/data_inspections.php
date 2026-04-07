<?php
// includes/data_inspections.php — direct DB query for inspections data
// Called by fetcher.php to bypass loopback HTTP limitations

function _fetch_inspections(PDO $pdo): array
{
    $year  = (int) date('Y');
    $month = (int) date('m');

    $groups = [
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
        foreach ($groups as $group => $fields) {
            $expr = implode('+', array_map(fn($f) => "`{$prefix}_{$f}`", $fields));
            $row  = $pdo->query(
                "SELECT COALESCE(SUM($expr),0) AS total,
                        COALESCE(SUM(CASE WHEN MONTH(report_date)=$month
                            AND YEAR(report_date)=$year THEN $expr ELSE 0 END),0) AS month
                 FROM aifaesa_inspection_data"
            )->fetch();
            $out["{$label} {$group}"]         = (int) $row['total'];
            $out["{$label} {$group} (month)"] = (int) $row['month'];
        }
    }

    foreach (
        [
            'Temp Closures' => 'enseramentu_temporariu',
            'Perm Closures' => 'enseramentu_permanente',
            'Reactivated'   => 'estabelecimento_reativadu',
        ] as $label => $col
    ) {
        $row = $pdo->query(
            "SELECT COALESCE(SUM(`$col`),0) AS total,
                    COALESCE(SUM(CASE WHEN MONTH(report_date)=$month
                        AND YEAR(report_date)=$year THEN `$col` ELSE 0 END),0) AS month
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

    return $out;
}
