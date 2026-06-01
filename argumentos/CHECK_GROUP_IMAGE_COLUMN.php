<?php
/**
 * argumentos/CHECK_GROUP_IMAGE_COLUMN.php
 * Simples verificação se coluna group_image existe
 * Aceso: http://localhost/kaliye/argumentos/CHECK_GROUP_IMAGE_COLUMN.php
 */

require_once '../configuracoes/base_dados.php';

try {
    $db = (new Database())->getConnection();
    
    // Verificar se coluna existe
    $column_exists = $db->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'mentor_chat_groups' 
        AND column_name = 'group_image'
    )")->fetchColumn();
    
    if ($column_exists) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'OK',
            'message' => '✅ Coluna group_image existe e pode usar-se!',
            'ready' => true
        ]);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'ERROR', 
            'message' => '❌ Coluna group_image ainda não existe',
            'ready' => false
        ]);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Erro de conexão: ' . $e->getMessage(),
        'ready' => false
    ]);
}
?>
