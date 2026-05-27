<?php
// interface_programacao/admin/export_terms_logs.php
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Auth check
if (!isAdmin() || !hasPermission('legal')) {
    die("Acesso negado.");
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch data
$query = "SELECT user_id, full_name, email, user_type, 
                 terms_accepted, terms_accepted_at, acceptance_ip,
                 privacy_accepted, privacy_accepted_at,
                 created_at
          FROM users 
          ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=rastreio_termos_'.date('Y-m-d').'.csv');

// Create file handle
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header row
fputcsv($output, [
    'ID', 
    'Nome Completo', 
    'Email', 
    'Tipo de Usuário', 
    'Termos Aceitos', 
    'Data Aceitação Termos', 
    'Privacidade Aceita', 
    'Data Aceitação Privacidade', 
    'Endereço IP', 
    'Data de Registo'
]);

// Data rows
foreach ($users as $u) {
    fputcsv($output, [
        $u['user_id'],
        $u['full_name'],
        $u['email'],
        $u['user_type'],
        $u['terms_accepted'] ? 'Sim' : 'Não',
        $u['terms_accepted_at'] ?: 'N/A',
        $u['privacy_accepted'] ? 'Sim' : 'Não',
        $u['privacy_accepted_at'] ?: 'N/A',
        $u['acceptance_ip'] ?: 'N/A',
        $u['created_at']
    ]);
}

fclose($output);
exit();

