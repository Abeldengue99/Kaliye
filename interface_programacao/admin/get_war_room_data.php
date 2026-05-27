<?php
/**
 * interface_programacao/admin/get_war_room_data.php
 * Fornece dados geográficos para o mapa administrativo (War Room).
 */
header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

try {
    $db = (new Database())->getConnection();

    // 1. Localização dos Utilizadores (Últimos Logins únicos com GPS)
    // Filtramos apenas entradas que tenham latitude/longitude válida.
    $users_query = "
        SELECT DISTINCT ON (user_id) 
               user_id, latitude, longitude, city, country, last_login_at
        FROM login_logs
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL
          AND country = 'Angola'
        ORDER BY user_id, last_login_at DESC
        LIMIT 500
    ";
    $users = $db->query($users_query)->fetchAll();

    // 2. Localização dos Projetos (Mapeamos pelo owner_id se o projeto não tiver GPS próprio)
    $projects_query = "
        SELECT p.project_id, p.title, p.category, p.budget_needed,
               l.latitude, l.longitude
        FROM projects p
        JOIN (
            SELECT DISTINCT ON (user_id) user_id, latitude, longitude 
            FROM login_logs 
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
            ORDER BY user_id, last_login_at DESC
        ) l ON p.owner_id = l.user_id
        WHERE p.approval_status = 'published' OR p.approval_status = 'pending'
    ";
    $projects = $db->query($projects_query)->fetchAll();

    echo json_encode([
        'success' => true,
        'users' => $users,
        'projects' => $projects
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
