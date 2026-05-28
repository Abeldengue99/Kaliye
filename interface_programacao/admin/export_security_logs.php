<?php
/**
 * interface_programacao/admin/export_security_logs.php
 * Exporta os logs de NDA e Visualizações para CSV.
 */
session_start();

require_once '../../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

// Validar acesso de Administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Acesso negado. Privilégios de Administrador requeridos.');
}

$type = $_GET['type'] ?? 'nda';
$filename = "relatorio_seguranca_{$type}_" . date('Y-md-His') . ".csv";

// Configurar cabeçalhos para download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Abre output
$output = fopen('php://output', 'w');

// Adiciona BOM para o Excel ler acentos corretamente
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

try {
    if ($type === 'nda') {
        fputcsv($output, ['ID', 'Data de Assinatura', 'Nome do Utilizador', 'Tipo de Perfil', 'Ideia/Projecto Protegido', 'Endereço IP', 'Hash de Conteúdo']);
        
        $stmt = $db->prepare("
            SELECT n.nda_id, n.accepted_at, u.full_name, u.user_type, p.title, n.ip_address, p.content_hash
            FROM project_nda_logs n
            JOIN users u ON n.user_id = u.user_id
            JOIN projects p ON n.project_id = p.project_id
            ORDER BY n.accepted_at DESC
        ");
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['nda_id'],
                $row['accepted_at'],
                $row['full_name'],
                strtoupper($row['user_type']),
                $row['title'],
                $row['ip_address'],
                $row['content_hash'] ?? 'N/D'
            ]);
        }
    } else {
        fputcsv($output, ['ID', 'Data de Acesso', 'Nome do Espectador', 'Tipo de Perfil', 'Projecto Acedido', 'Endereço IP']);
        
        $stmt = $db->prepare("
            SELECT v.view_id, v.viewed_at, u.full_name, u.user_type, p.title, v.ip_address
            FROM project_views_log v
            JOIN users u ON v.viewer_id = u.user_id
            JOIN projects p ON v.project_id = p.project_id
            ORDER BY v.viewed_at DESC
        ");
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['view_id'],
                $row['viewed_at'],
                $row['full_name'],
                strtoupper($row['user_type']),
                $row['title'],
                $row['ip_address']
            ]);
        }
    }

} catch (PDOException $e) {
    error_log("Admin Security Logs Export Error: " . $e->getMessage());
    fputcsv($output, ['Erro ao gerar relatorio', $e->getMessage()]);
}

fclose($output);
exit();
