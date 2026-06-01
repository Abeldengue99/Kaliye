<?php
/**
 * quick_check.php
 * Verificação rápida do status da coluna group_image
 */
require_once 'configuracoes/base_dados.php';

header('Content-Type: text/html; charset=utf-8');

// CSS inline para melhor visualização
$html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Status - group_image</title>
    <style>
        body { font-family: Arial; margin: 30px; background: #0d1117; color: #c9d1d9; }
        .container { max-width: 600px; margin: auto; }
        .status { padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success { background: #0d3922; border: 2px solid #1a6e3f; }
        .error { background: #3d1f1a; border: 2px solid #6e423f; }
        .info { background: #0d2d4a; border: 2px solid #1a4d6e; }
        h1 { color: #58a6ff; }
        code { background: #161b22; padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Verificação de Status</h1>
HTML;

try {
    $db = (new Database())->getConnection();
    $html .= '<div class="status info">Conexão à BD: <strong>OK</strong></div>';
    
    // Verificar tabela
    $table_exists = $db->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_name = 'mentor_chat_groups'
    )")->fetchColumn();
    
    if ($table_exists) {
        $html .= '<div class="status success">Tabela <code>mentor_chat_groups</code>: <strong>EXISTS ✓</strong></div>';
        
        // Verificar coluna
        $column_exists = $db->query("SELECT EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_name = 'mentor_chat_groups' AND column_name = 'group_image'
        )")->fetchColumn();
        
        if ($column_exists) {
            $html .= '<div class="status success">Coluna <code>group_image</code>: <strong>EXISTS ✓</strong></div>';
            $html .= '<div class="status success"><strong style="color: #3fb950;">🎉 TUDO PRONTO!</strong><br>Pode trocar imagens do grupo sem problemas.</div>';
        } else {
            $html .= '<div class="status error">Coluna <code>group_image</code>: <strong>MISSING ✗</strong><br>Recarregue a página e tente novamente.</div>';
        }
        
        // Listar colunas
        $columns = $db->query("
            SELECT column_name FROM information_schema.columns 
            WHERE table_name = 'mentor_chat_groups' ORDER BY ordinal_position
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        $html .= '<div class="status info"><strong>Colunas da tabela:</strong><br>';
        foreach ($columns as $col) {
            $html .= '• ' . htmlspecialchars($col) . '<br>';
        }
        $html .= '</div>';
        
    } else {
        $html .= '<div class="status error">Tabela <code>mentor_chat_groups</code>: <strong>NOT FOUND ✗</strong></div>';
    }
    
} catch (Exception $e) {
    $html .= '<div class="status error"><strong>Erro:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
}

$html .= '</div></body></html>';
echo $html;
?>
