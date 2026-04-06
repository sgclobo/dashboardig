<?php
// data/data_inspections.php
// Returns inspection stats as a JSON-compatible array.
// Replace the dummy values below with real DB queries once
// seksaunit.aifaesa.org database is accessible from this server.
//
// This file is called by fetcher.php when the source slug = 'inspections'
// and no remote API is reachable (or as the primary source).

require_once __DIR__ . '/../includes/db.php';

// ── Option A: query the LOCAL dashboard DB (for now, returns demo data) ──
// When seksaunit shares a DB or you migrate to a shared MySQL, swap this
// out for real queries.

return [
    'total_inspections'      => 142,
    'this_month'             => 18,
    'pending'                => 7,
    'completed'              => 135,
    'failed'                 => 12,
    'pass_rate'              => '91%',
];

// ── Option B (future): query seksaunit DB directly if on same MySQL server ──
// $pdo = new PDO('mysql:host=localhost;dbname=seksaunit_db', 'user', 'pass');
// return [
//     'total_inspections' => $pdo->query("SELECT COUNT(*) FROM inspections")->fetchColumn(),
//     'this_month'        => $pdo->query("SELECT COUNT(*) FROM inspections WHERE MONTH(created_at)=MONTH(NOW())")->fetchColumn(),
//     'pending'           => $pdo->query("SELECT COUNT(*) FROM inspections WHERE status='pending'")->fetchColumn(),
//     'completed'         => $pdo->query("SELECT COUNT(*) FROM inspections WHERE status='completed'")->fetchColumn(),
//     'failed'            => $pdo->query("SELECT COUNT(*) FROM inspections WHERE status='failed'")->fetchColumn(),
// ];
