<?php
// interface_programacao/admin/admin_delete_user.php
@session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de utilizador inválido']);
    exit;
}

// Impedir auto-eliminação
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Não pode eliminar a sua própria conta']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Verificar se o utilizador existe
    $check = $db->prepare("SELECT user_id, user_type FROM users WHERE user_id = :id");
    $check->execute([':id' => $user_id]);
    $user = $check->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilizador não encontrado']);
        exit;
    }

    // Impedir eliminação de superadmin (user_id = 1 ou o próprio admin criador)
    if ($user['user_type'] === 'admin' && !hasPermission('users')) {
        echo json_encode(['success' => false, 'message' => 'Sem permissão para eliminar administradores']);
        exit;
    }

    $db->beginTransaction();

    // Eliminar dados relacionados (ordem: FK dependencies primeiro)
    $tables_to_clean = [
        ['table' => 'notifications',            'col' => 'user_id'],
        ['table' => 'notifications',            'col' => 'sender_id'],
        ['table' => 'user_connections',         'col' => 'user_id_1'],
        ['table' => 'user_connections',         'col' => 'user_id_2'],
        ['table' => 'user_connections',         'col' => 'requester_id'],
        ['table' => 'support_messages',         'col' => 'user_id'],
        ['table' => 'free_mentorship_requests', 'col' => 'student_id'],
        ['table' => 'free_mentorship_requests', 'col' => 'selected_mentor_id'],
        ['table' => 'project_investments',      'col' => 'investor_id'],
        ['table' => 'social_comments',          'col' => 'user_id'],
        ['table' => 'social_likes',             'col' => 'user_id'],
        ['table' => 'doubts',                   'col' => 'user_id'],
        ['table' => 'doubt_comments',           'col' => 'user_id'],
        ['table' => 'doubt_comment_votes',      'col' => 'user_id'],
        ['table' => 'project_votes',            'col' => 'voter_id'],
        ['table' => 'ad_metrics',               'col' => 'user_id'],
        ['table' => 'ads',                      'col' => 'owner_id'],
        ['table' => 'publicidades',             'col' => 'user_id'],
        ['table' => 'investments',              'col' => 'investor_id'],
        ['table' => 'kyc_verifications',        'col' => 'user_id'],
        ['table' => 'payments',                 'col' => 'user_id'],
        ['table' => 'transactions',             'col' => 'user_id'],
        ['table' => 'payouts',                  'col' => 'recipient_id'],
    ];

    foreach ($tables_to_clean as $entry) {
        try {
            // No PostgreSQL, se um comando falhar dentro de uma transação, toda a transação é bloqueada.
            // Usamos SAVEPOINT para permitir ignorar falhas individuais (ex: tabela não existe).
            $db->exec("SAVEPOINT sp_delete");
            $stmt = $db->prepare("DELETE FROM \"{$entry['table']}\" WHERE \"{$entry['col']}\" = :id");
            $stmt->execute([':id' => $user_id]);
            $db->exec("RELEASE SAVEPOINT sp_delete");
        } catch (Exception $e) {
            $db->exec("ROLLBACK TO SAVEPOINT sp_delete");
            error_log("Aviso limpeza tabela {$entry['table']}: " . $e->getMessage());
        }
    }

    // Projetos do utilizador
    try {
        $db->exec("SAVEPOINT sp_projects");
        $stmt = $db->prepare("DELETE FROM projects WHERE owner_id = :id");
        $stmt->execute([':id' => $user_id]);
        $db->exec("RELEASE SAVEPOINT sp_projects");
    } catch (Exception $e) {
        $db->exec("ROLLBACK TO SAVEPOINT sp_projects");
        error_log("Aviso limpeza projects: " . $e->getMessage());
    }

    // Eliminar o utilizador
    $stmt = $db->prepare("DELETE FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $user_id]);

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Utilizador eliminado com sucesso']);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Erro admin delete user: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao eliminar utilizador: ' . $e->getMessage()]);
}
