<?php
/**
 * interface_programacao/admin/admin_process_project.php
 * 
 * Ficheiro crítico da Área Administrativa: Gere a moderação de projectos.
 * Este endpoint é o destino de todas as ações de Curadoria Elite, permitindo que os
 * administradores Aprova, Rejeitem ou Eliminem projectos do ecossistema.
 * Nota: Integra um sistema de notificações automáticas para manter o criador informado.
 */

// Iniciamos a sessão para validar a autoridade do utilizador.
@session_start();

// Dependências centrais: Base de dados e verificador de privilégios.
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Definimos o cabeçalho JSON pois este ficheiro é consumido estritamente via AJAX (Painel Admin).
header('Content-Type: application/json');

/**
 * VERIFICAÇÃO DE PRIVILÉGIOS (SECURITY GATE)
 * Bloqueio absoluto: Se o utilizador não tiver 'user_type' = 'admin', a execução morre aqui.
 */
if (!isAdmin() || !hasPermission('moderation')) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem moderar projectos.']);
    exit();
}

// Apenas aceitamos POST para garantir que as alterações de estado sejam intencionais e protegidas.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido. Utilize POST.']);
    exit();
}

// Captura segura e filtragem básica dos parâmetros de ação.
$action     = $_POST['action'] ?? '';
$project_id = intval($_POST['project_id'] ?? 0);

// Validação de Integridade: ID deve ser um número válido e a ação deve constar no nosso 'whitelist'.
if (!$project_id || !in_array($action, ['approve', 'reject', 'delete'])) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos ou projecto inexistente.']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    /**
     * AÇÃO: APROVAR (APPROVE)
     * Business Logic: Torna o projecto visível no feed global (`is_public = true`) e 
     * marca o momento exato e o administrador responsável pela curadoria.
     */
    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE projects SET approval_status = 'approved', is_public = true, approved_at = NOW(), approved_by = :admin_id WHERE project_id = :project_id");
        $stmt->execute([
            ':admin_id'   => $_SESSION['user_id'],
            ':project_id' => $project_id
        ]);

        // Notificação Humanizada para o Criador: "Parabéns, a tua ideia está viva!"
        $owner_stmt = $db->prepare("SELECT owner_id, title FROM projects WHERE project_id = ?");
        $owner_stmt->execute([$project_id]);
        $project = $owner_stmt->fetch(PDO::FETCH_ASSOC);

        if ($project) {
            $notif_title   = "Projecto Aprovado! 🚀";
            $notif_content = "Excelente notícia! A sua ideia '{$project['title']}' foi validada pela equipa administrativa e já está disponível para todo o ecossistema.";
            $notif_link    = "paginas/explorar/my_projects.php";

            // Inserção da notificação de sistema para o utilizador.
            $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link, created_at) VALUES (?, ?, ?, ?, 'system', ?, NOW())");
            $notif_stmt->execute([$project['owner_id'], $_SESSION['user_id'], $notif_title, $notif_content, $notif_link]);
        }

        echo json_encode(['success' => true, 'message' => 'Projecto aprovado com sucesso.']);
    }

    /**
     * AÇÃO: REJEITAR (REJECT)
     * Remove o projecto do feed global e marca como rejeitado, mas mantém os dados para 
     * que o utilizador possa editar e reenviar no futuro.
     */
    elseif ($action === 'reject') {
        $stmt = $db->prepare("UPDATE projects SET approval_status = 'rejected', is_public = false WHERE project_id = :project_id");
        $stmt->execute([':project_id' => $project_id]);
        
        echo json_encode(['success' => true, 'message' => 'Projecto rejeitado e removido do feed público.']);
    }

    /**
     * AÇÃO: ELIMINAR (DELETE)
     * Remoção definitiva: Remove o projecto e TODAS as suas dependências (tags, likes, media, investimentos).
     * Nota: Usamos Transaction para garantir que não fiquem dados órfãos se a limpeza falhar a meio.
     */
    elseif ($action === 'delete') {
        // Pré-cache dos dados do projecto antes de apagar, para podermos notificar o autor.
        $owner_stmt = $db->prepare("SELECT owner_id, title FROM projects WHERE project_id = ?");
        $owner_stmt->execute([$project_id]);
        $project_data = $owner_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project_data) {
            echo json_encode(['success' => false, 'error' => 'Projecto não encontrado para eliminação.']);
            exit;
        }

        $db->beginTransaction();

        // Mapa de dependências a limpar sequencialmente.
        $related = [
            'project_tags'         => 'project_id',
            'project_media'        => 'project_id',
            'project_likes'        => 'project_id',
            'project_comments'     => 'project_id',
            'project_investments'  => 'project_id',
            'project_endorsements' => 'project_id',
            'project_milestones'   => 'project_id',
        ];
        
        foreach ($related as $table => $col) {
            try {
                $db->prepare("DELETE FROM $table WHERE $col = ?")->execute([$project_id]);
            } catch (Exception $e) {
                // Algumas tabelas podem estar vazias ou em manutenção — logamos e continuamos a limpeza.
                error_log("Tentativa falhada de limpeza na tabela $table: " . $e->getMessage());
            }
        }

        // Finalmente, removemos o registo mestre do projecto.
        $db->prepare("DELETE FROM projects WHERE project_id = ?")->execute([$project_id]);

        // Feedback final de sistema para o ex-dono da ideia.
        try {
            $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, created_at) VALUES (?, ?, ?, ?, 'system', NOW())");
            $notif_stmt->execute([
                $project_data['owner_id'],
                $_SESSION['user_id'],
                'Projecto Removido',
                "O seu projecto '{$project_data['title']}' foi removido permanentemente pelo administrador."
            ]);
        } catch (Exception $e) {
            error_log("Falha ao notificar eliminação definitiva: " . $e->getMessage());
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Projecto e dependências eliminados permanentemente.']);
    }

} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    // Tratamento de exceções críticas para garantir que o Admin receba um erro JSON legível.
    error_log("Erro crítico na moderação administrativa: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno na base de dados durante o processamento.']);
}
?>
