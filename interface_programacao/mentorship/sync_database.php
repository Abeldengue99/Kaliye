<?php
/**
 * API de Sincronização de Mentorias Gratuitas
 * Acesso: POST /interface_programacao/mentorship/sync_database.php?action=full
 */

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/free_mentorship_schema.php';

header('Content-Type: application/json');

// Proteger contra acesso não autorizado
session_start();
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem sincronizar.']);
    exit;
}

$action = isset($_GET['action']) ? strtolower($_GET['action']) : 'check';

$response = [
    'success' => false,
    'message' => '',
    'timestamp' => date('Y-m-d H:i:s'),
    'action' => $action,
    'stats' => [],
    'logs' => []
];

try {
    $db = (new Database())->getConnection();
    
    // Garantir que as tabelas existem
    ensureFreeMentorshipTables($db);
    $response['logs'][] = '✓ Tabelas de mentoria verificadas';
    
    // 1. Adicionar campos faltantes
    $fieldsToAdd = [
        'free_mentorship_requests' => [
            'session_date TIMESTAMP',
            'duration_minutes INT DEFAULT 0',
            'meeting_link TEXT',
            'student_rating INT CHECK (student_rating >= 1 AND student_rating <= 5)',
            'student_feedback TEXT'
        ]
    ];
    
    foreach ($fieldsToAdd as $table => $fields) {
        foreach ($fields as $field) {
            try {
                $db->exec("ALTER TABLE $table ADD COLUMN IF NOT EXISTS $field");
            } catch (Exception $e) {
                // Campo já existe
            }
        }
    }
    $response['logs'][] = '✓ Campos verificados e adicionados (se necessário)';
    
    // 2. Sincronizar dados de sessões
    if ($action === 'full' || $action === 'fix') {
        $query = "
            UPDATE free_mentorship_requests r
            SET 
                session_date = s.session_date,
                duration_minutes = COALESCE(s.duration_minutes, 0),
                meeting_link = s.meeting_link,
                student_rating = s.student_rating,
                student_feedback = s.student_feedback
            FROM free_mentorship_sessions s
            WHERE r.request_id = s.request_id
              AND (r.session_date IS NULL 
                   OR r.duration_minutes = 0 
                   OR r.meeting_link IS NULL)
        ";
        
        $db->exec($query);
        $response['logs'][] = '✓ Dados de sessões sincronizados';
    }
    
    // 3. Remover registos órfãos se solicitado
    if ($action === 'fix') {
        $db->exec("DELETE FROM free_mentorship_applications WHERE NOT EXISTS (SELECT 1 FROM free_mentorship_requests r WHERE r.request_id = free_mentorship_applications.request_id)");
        $db->exec("DELETE FROM free_mentorship_sessions WHERE NOT EXISTS (SELECT 1 FROM free_mentorship_requests r WHERE r.request_id = free_mentorship_sessions.request_id)");
        $response['logs'][] = '✓ Registos órfãos removidos';
    }
    
    // 4. Criar índices
    $indexQueries = [
        'idx_free_mentorship_req_session_date' => "CREATE INDEX IF NOT EXISTS idx_free_mentorship_req_session_date ON free_mentorship_requests (session_date DESC NULLS LAST)",
        'idx_free_mentorship_req_completed_at' => "CREATE INDEX IF NOT EXISTS idx_free_mentorship_req_completed_at ON free_mentorship_requests (completed_at DESC NULLS LAST)",
        'idx_free_mentorship_req_status_session' => "CREATE INDEX IF NOT EXISTS idx_free_mentorship_req_status_session ON free_mentorship_requests (status, session_date)"
    ];
    
    foreach ($indexQueries as $name => $query) {
        try {
            $db->exec($query);
        } catch (Exception $e) {
            // Índice já pode existir
        }
    }
    $response['logs'][] = '✓ Índices de performance criados';
    
    // 5. Obter estatísticas
    $stats = [];
    
    $total = $db->query("SELECT COUNT(*) as c FROM free_mentorship_requests")->fetch()['c'];
    $stats['total_requests'] = (int)$total;
    
    $scheduled = $db->query("SELECT COUNT(*) as c FROM free_mentorship_requests WHERE session_date IS NOT NULL")->fetch()['c'];
    $stats['scheduled'] = (int)$scheduled;
    
    $completed = $db->query("SELECT COUNT(*) as c FROM free_mentorship_requests WHERE status = 'completed'")->fetch()['c'];
    $stats['completed'] = (int)$completed;
    
    $withRating = $db->query("SELECT COUNT(*) as c FROM free_mentorship_requests WHERE student_rating IS NOT NULL")->fetch()['c'];
    $stats['with_rating'] = (int)$withRating;
    
    $response['stats'] = $stats;
    
    $response['success'] = true;
    $response['message'] = 'Sincronização concluída com sucesso!';
    $response['logs'][] = '✓ Sincronização completa';
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Erro: ' . $e->getMessage();
    $response['logs'][] = '❌ Erro: ' . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
