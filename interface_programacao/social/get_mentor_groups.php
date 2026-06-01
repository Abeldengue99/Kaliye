<?php
/**
 * get_mentor_groups.php
 * Retorna os grupos de mentoria do usuário (como mentor ou membro)
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
    
    // Obter grupos que o usuário é mentor
    $mentor_stmt = $db->prepare("
        SELECT id, name, mentor_id, created_at
        FROM mentor_chat_groups
        WHERE mentor_id = ?
        ORDER BY created_at DESC
    ");
    $mentor_stmt->execute([$user_id]);
    $mentor_groups = $mentor_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obter grupos que o usuário é membro
    $member_stmt = $db->prepare("
        SELECT mg.id, mg.name, mg.mentor_id, mg.created_at
        FROM mentor_chat_groups mg
        JOIN mentor_group_members mgm ON mg.id = mgm.group_id
        WHERE mgm.user_id = ?
        ORDER BY mg.created_at DESC
    ");
    $member_stmt->execute([$user_id]);
    $member_groups = $member_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combinar e formatar
    $all_groups = [];
    
    foreach ($mentor_groups as $group) {
        // Contar membros
        $count_stmt = $db->prepare("SELECT COUNT(*) as cnt FROM mentor_group_members WHERE group_id = ?");
        $count_stmt->execute([$group['id']]);
        $member_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        $all_groups[] = [
            'id' => (int)$group['id'],
            'name' => htmlspecialchars($group['name']),
            'mentor_id' => (int)$group['mentor_id'],
            'is_owner' => true,
            'member_count' => $member_count,
            'created_at' => $group['created_at']
        ];
    }
    
    foreach ($member_groups as $group) {
        // Evitar duplicatas
        $exists = false;
        foreach ($all_groups as $g) {
            if ($g['id'] == $group['id']) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            // Contar membros
            $count_stmt = $db->prepare("SELECT COUNT(*) as cnt FROM mentor_group_members WHERE group_id = ?");
            $count_stmt->execute([$group['id']]);
            $member_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            
            // Obter nome do mentor
            $mentor_stmt2 = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
            $mentor_stmt2->execute([$group['mentor_id']]);
            $mentor = $mentor_stmt2->fetch(PDO::FETCH_ASSOC);
            
            $all_groups[] = [
                'id' => (int)$group['id'],
                'name' => htmlspecialchars($group['name']),
                'mentor_id' => (int)$group['mentor_id'],
                'mentor_name' => htmlspecialchars($mentor['full_name'] ?? 'Mentor'),
                'is_owner' => false,
                'member_count' => $member_count,
                'created_at' => $group['created_at']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'groups' => $all_groups
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
