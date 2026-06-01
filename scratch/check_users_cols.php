<?php
// Test script to check what columns exist in the users table
// Access via browser: localhost/aksanti/scratch/check_users_cols.php
session_start();
require_once __DIR__ . '/../configuracoes/base_dados.php';
header('Content-Type: text/plain; charset=utf-8');

$db = (new Database())->getConnection();

echo "=== COLUNAS DA TABELA USERS ===\n\n";
$cols = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['column_name'] . ' | ' . $c['data_type'] . "\n";
}

echo "\n=== COLUNAS NECESSÁRIAS PARA KYC ===\n";
$needed = ['id_number', 'bi_front_path', 'bi_back_path', 'selfie_path', 'verification_status', 'submitted_at', 'annual_income', 'source_of_funds', 'investor_status', 'cv_path', 'academic_transcript_path', 'mentorship_status', 'specialty', 'experience_years', 'linkedin_url'];
$existing = array_column($cols, 'column_name');

foreach ($needed as $col) {
    $status = in_array($col, $existing) ? '✓ EXISTE' : '✗ FALTA';
    echo "$status: $col\n";
}
