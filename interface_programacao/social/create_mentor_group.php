<?php
/**
 * create_mentor_group.php
 * API para o mentor criar o seu grupo de networking com todos os seus mentoreados.
 * Integração: Base de Dados (tabela mentor_chat_groups).
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/ChatSecurity.php';

// Define o cabeçalho de resposta para retornar dados no formato JSON (Padrão de API moderno).
header('Content-Type: application/json');

// Redireciona e encerra a execução se não estiver verificado na sessão global do sistema.
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mentor' || ($_SESSION['mentorship_status'] ?? 'unsubmitted') !== 'approved') {
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas mentores podem criar grupos de sinergia.']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $mentor_id = (int)$_SESSION['user_id'];
    ChatSecurity::touchPresence($db, $mentor_id);
    
    // Captura os dados brutos enviados do JavaScript pelo método POST nativo (AJAX estruturado).
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Filtro e purificação do nome do grupo, prevenindo ataques de injecção XSS simples.
    $group_name = ChatSecurity::normalizeText($data['group_name'] ?? 'Grupo VIP de Mentoria');
    $group_name = mb_substr($group_name, 0, 80);
    if ($group_name === '') {
        echo json_encode(['success' => false, 'error' => 'Informe um nome valido para a sala.']);
        exit();
    }
    
    // Verificamos se este mentor já possui um grupo de sinergia criado na base de dados (Lógica limitadora base).
    $check_stmt = $db->prepare("SELECT id FROM mentor_chat_groups WHERE mentor_id = ? LIMIT 1");
    $check_stmt->execute([$mentor_id]);
    
    if ($check_stmt->fetchColumn()) {
         // O sistema por enquanto foca num "Mega-Grupo" único inovador por mentor para não dispersar a comunidade.
         echo json_encode(['success' => false, 'error' => 'Já possui um grupo VIP activo criado. Explore as suas turmas neste mesmo grupo!']);
         exit();
    }
    
    // Inserção preparada na BD (Rule #4 - Prevenção contra SQL Injection) mapeando o mentor à estrutura de Chat.
    $returning = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? ' RETURNING id' : '';
    $stmt = $db->prepare("INSERT INTO mentor_chat_groups (mentor_id, name) VALUES (?, ?)" . $returning);
    $stmt->execute([$mentor_id, $group_name]);
    $new_group_id = $returning ? $stmt->fetchColumn() : $db->lastInsertId();

    // Resposta final de sucesso com as flags corretas para a interface da UI ler e renderizar imediatamente sem reload.
    echo json_encode([
        'success' => true, 
        'group_id' => $new_group_id, 
        'group_name' => $group_name
    ]);

} catch (Exception $e) {
    // Escudo global de manipulação de exceções para proteger os caminhos e lógicas ocultas do Backend.
    echo json_encode(['success' => false, 'error' => 'Erro interno ao processar o servidor: ' . $e->getMessage()]);
}
?>
