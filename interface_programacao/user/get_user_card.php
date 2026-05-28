<?php
/**
 * interface_programacao/user/get_user_card.php - Motor de Visualização Pública (Aksanti Elite)
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
$db = (new Database())->getConnection();

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_user_id = $_SESSION['user_id'] ?? 0;

function ensureUserCardProfileColumns(PDO $db): void {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $columns = [
        'mentorship_status' => 'VARCHAR(30)',
        'institution' => 'VARCHAR(180)',
        'organization' => 'VARCHAR(180)',
        'focus_areas' => 'TEXT',
        'experience_summary' => 'TEXT',
        'website_url' => 'VARCHAR(255)',
        'linkedin_url' => 'VARCHAR(255)'
    ];

    foreach ($columns as $column => $definition) {
        try {
            if ($driver === 'pgsql') {
                $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS {$column} {$definition}");
            } else {
                $db->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
            }
        } catch (Throwable $e) {}
    }
}

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'ID de utilizador inválido.']);
    exit();
}

try {
    ensureUserCardProfileColumns($db);

    // 1. Busca de Dados Centrais do Utilizador
    $sql = "SELECT full_name, user_type, profile_pic, is_verified, verification_status, bio, specialization_tags,
                   location, academic_level, created_at, avaliacao, mentorship_status,
                   institution, organization, focus_areas, experience_summary, website_url, linkedin_url
            FROM users WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilizador não encontrado.']);
        exit();
    }

    // 2. Cálculo de Conexões (Networking Oficial)
    $sqlConn = "SELECT COUNT(*) as total FROM user_connections 
                WHERE (user_id_1 = ? OR user_id_2 = ?) AND status = 'accepted'";
    $stmtConn = $db->prepare($sqlConn);
    $stmtConn->execute([$userId, $userId]);
    $connections = $stmtConn->fetch();

    // 3. Verificação de Estado de Conexão (Para o botão de interatividade)
    $connectionStatus = 'none';
    if ($current_user_id && $current_user_id != $userId) {
        $sqlCheck = "SELECT status, requester_id FROM user_connections 
                     WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)";
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->execute([$current_user_id, $userId, $userId, $current_user_id]);
        $conn = $stmtCheck->fetch();
        if ($conn) {
            $connectionStatus = $conn['status'];
            // Se o status for pendente, precisamos saber quem pediu
            if ($connectionStatus === 'pending' && $conn['requester_id'] == $userId) {
                $connectionStatus = 'received';
            }
        }
    }

    // Processamento de Avatar
    $final_pic = getUserAvatarUrl($user['user_type'] ?? 'student', $user['mentorship_status'] ?? 'unsubmitted', $user['profile_pic'] ?? '');

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$userId,
            'name' => $user['full_name'],
            'role' => strtoupper($user['user_type'] ?? 'Inovador'),
            'user_type_raw' => $user['user_type'],
            'avatar' => $final_pic,
            'is_verified' => (($user['verification_status'] ?? 'unsubmitted') === 'verified'),
            'email_verified' => in_array($user['is_verified'] ?? false, [true, 1, '1', 't'], true),
            'bio' => $user['bio'] ?? 'Sem biografia disponível.',
            'skills' => $user['specialization_tags'] ?? '',
            'focus_areas' => $user['focus_areas'] ?? '',
            'experience_summary' => $user['experience_summary'] ?? '',
            'location' => $user['location'] ?? 'Angola',
            'level' => $user['academic_level'] ?? 'Membro Aksanti',
            'institution' => $user['institution'] ?? '',
            'organization' => $user['organization'] ?? '',
            'linkedin' => $user['linkedin_url'] ?? '',
            'website' => $user['website_url'] ?? '',
            'created_at' => $user['created_at'],
            'rating' => (float)($user['avaliacao'] ?? 0),
            'connections_count' => (int)($connections['total'] ?? 0),
            'connection_status' => $connectionStatus
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno ao processar o cartão: ' . $e->getMessage()]);
}
?>
