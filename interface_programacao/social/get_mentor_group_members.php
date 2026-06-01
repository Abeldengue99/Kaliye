<?php
/**
 * get_mentor_group_members.php
 * Retorna lista de membros de uma sala VIP de mentoria
 */
session_start();
require_once '../../configuracoes/base_dados.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado.']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $user_id = (int)$_SESSION['user_id'];
    $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    
    if ($group_id <= 0) {
        throw new Exception('Grupo não especificado.');
    }
    
    // Verificar acesso ao grupo
    $access_stmt = $db->prepare("
        SELECT mg.id, mg.mentor_id
        FROM mentor_chat_groups mg
        LEFT JOIN mentor_group_members mgm ON mg.id = mgm.group_id AND mgm.user_id = ?
        WHERE mg.id = ? AND (mg.mentor_id = ? OR mgm.user_id = ?)
        LIMIT 1
    ");
    $access_stmt->execute([$user_id, $group_id, $user_id, $user_id]);
    $group = $access_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$group) {
        throw new Exception('Sem acesso a este grupo.');
    }
    
    $is_owner = ($group['mentor_id'] == $user_id);
    
    // Obter mentor do grupo
    $mentor_stmt = $db->prepare("
        SELECT u.user_id, u.full_name, u.email, u.profile_pic, u.user_type
        FROM users u
        WHERE u.user_id = ?
    ");
    $mentor_stmt->execute([$group['mentor_id']]);
    $mentor = $mentor_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obter membros
    $members_stmt = $db->prepare("
        SELECT mgm.*, u.full_name, u.email, u.profile_pic, u.user_type, u.mentorship_status
        FROM mentor_group_members mgm
        JOIN users u ON mgm.user_id = u.user_id
        WHERE mgm.group_id = ?
        ORDER BY u.full_name ASC
    ");
    $members_stmt->execute([$group_id]);
    $members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatar resposta
    $formatted_members = [];
    if ($mentor) {
        $formatted_members[] = [
            'user_id' => (int)$mentor['user_id'],
            'full_name' => htmlspecialchars($mentor['full_name']),
            'email' => htmlspecialchars($mentor['email']),
            'profile_pic' => $mentor['profile_pic'] ?? null,
            'role' => 'mentor',
            'user_type' => $mentor['user_type'],
            'mentorship_status' => null
        ];
    }
    
    foreach ($members as $member) {
        $formatted_members[] = [
            'user_id' => (int)$member['user_id'],
            'full_name' => htmlspecialchars($member['full_name']),
            'email' => htmlspecialchars($member['email']),
            'profile_pic' => $member['profile_pic'] ?? null,
            'role' => htmlspecialchars($member['role'] ?? 'member'),
            'user_type' => $member['user_type'],
            'mentorship_status' => $member['mentorship_status'],
            'joined_at' => $member['joined_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'is_owner' => $is_owner,
        'members' => $formatted_members,
        'member_count' => count($formatted_members)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
