<?php
/**
 * get_mentor_students.php
 * Retorna lista de mentorados do mentor para adição em grupo
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
    $mentor_id = (int)$_SESSION['user_id'];
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    
    // Validar que o usuário é realmente mentor
    $mentor_check = $db->prepare("SELECT user_type FROM users WHERE id = ?");
    $mentor_check->execute([$mentor_id]);
    $user = $mentor_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $user['user_type'] !== 'mentor') {
        throw new Exception('Apenas mentores podem usar esta funcionalidade.');
    }
    
    // Buscar mentorados (estudantes da plataforma que podem ser adicionados ao grupo)
    $query = "
        SELECT DISTINCT u.id, u.full_name, u.email, u.profile_pic
        FROM users u
        WHERE u.user_type IN ('univ_student', 'high_student')
        AND u.id != ?
    ";
    
    $params = [$mentor_id];
    
    // Se houver busca, filtrar por nome ou email
    if (!empty($search)) {
        $query .= " AND (LOWER(u.full_name) LIKE LOWER(?) OR LOWER(u.email) LIKE LOWER(?))";
        $search_term = '%' . $search . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Se houver group_id, excluir membros já adicionados
    if ($group_id > 0) {
        $query .= " AND u.id NOT IN (
            SELECT user_id FROM mentor_group_members WHERE group_id = ?
        )";
        $params[] = $group_id;
    }
    
    $query .= " ORDER BY u.full_name ASC LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatar resposta
    $formatted_students = [];
    foreach ($students as $student) {
        $formatted_students[] = [
            'user_id' => (int)$student['id'],
            'full_name' => htmlspecialchars($student['full_name']),
            'email' => htmlspecialchars($student['email']),
            'profile_pic' => $student['profile_pic'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'students' => $formatted_students,
        'count' => count($formatted_students)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
