<?php
/**
 * 🔄 SINCRONIZAÇÃO FORÇADA: Corrigir todos os links de notificações
 * 
 * Este script deve ser executado AGORA para sincronizar BD
 * Depois será executado automaticamente pelo validador
 */

require_once __DIR__ . '/../configuracoes/base_dados.php';

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$db = $database->getConnection();

try {
    // ===== PASSO 1: CONTAR QUANTOS LINKS ANTIGOS EXISTEM =====
    $count_query = "SELECT COUNT(*) as cnt FROM notifications WHERE link LIKE '%paginas/social/duvidas%'";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $old_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
    
    // ===== PASSO 2: CORRIGIR TODOS OS LINKS ANTIGOS =====
    if ($old_count > 0) {
        $fix_query = "UPDATE notifications 
                     SET link = REPLACE(link, 'paginas/social/duvidas', 'paginas/explorar/doubts')
                     WHERE link LIKE '%paginas/social/duvidas%'";
        $fix_stmt = $db->prepare($fix_query);
        $fix_stmt->execute();
        $affected = $db->lastInsertId() ?: $fix_stmt->rowCount();
    }
    
    // ===== PASSO 3: VERIFICAR SE FOI CORRIGIDO =====
    $verify_query = "SELECT COUNT(*) as cnt FROM notifications WHERE link LIKE '%paginas/social/duvidas%'";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute();
    $remaining = $verify_stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
    
    // ===== PASSO 4: MOSTRAR RESULTADO =====
    $response = [
        'success' => true,
        'message' => 'Sincronização concluída',
        'details' => [
            'links_antigos_encontrados' => $old_count,
            'links_corrigidos' => $old_count - $remaining,
            'links_ainda_antigos' => $remaining,
            'status' => $remaining === 0 ? '✅ TODOS OS LINKS CORRIGIDOS' : '⚠️ Ainda existem links antigos'
        ]
    ];
    
    // Log para registro
    error_log('[NOTIFICATION SYNC] ' . json_encode($response['details']));
    
    // Mostrar exemplos dos links agora corrigidos
    $example_query = "SELECT id, link, type FROM notifications WHERE link LIKE '%paginas/explorar/doubts%' ORDER BY created_at DESC LIMIT 3";
    $example_stmt = $db->prepare($example_query);
    $example_stmt->execute();
    $examples = $example_stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['examples_corrigidos'] = $examples;
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    $error = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    http_response_code(500);
    echo json_encode($error);
}
?>
