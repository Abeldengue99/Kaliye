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
if (!$project_id || !in_array($action, ['approve', 'reject', 'delete', 'deactivate'])) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos ou projecto inexistente.']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS approval_status VARCHAR(30) DEFAULT 'pending'");
    $db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS status VARCHAR(30) DEFAULT 'pending'");
    $db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS is_public BOOLEAN DEFAULT FALSE");
    $db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL");
    $db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS approved_by INTEGER NULL");

    /**
     * AÇÃO: APROVAR (APPROVE)
     * Business Logic: Torna o projecto visível no feed global (`is_public = true`) e 
     * marca o momento exato e o administrador responsável pela curadoria.
     */
    if ($action === 'approve') {
        $owner_stmt = $db->prepare("SELECT owner_id, title, approval_status, is_public, status FROM projects WHERE project_id = ?");
        $owner_stmt->execute([$project_id]);
        $project = $owner_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            echo json_encode(['success' => false, 'error' => 'Projecto não encontrado.']);
            exit;
        }

        $was_approved = strtolower(trim((string)($project['approval_status'] ?? ''))) === 'approved'
            || strtolower(trim((string)($project['status'] ?? ''))) === 'analyzed'
            || filter_var($project['is_public'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $stmt = $db->prepare("
            UPDATE projects
            SET approval_status = 'approved',
                status = 'analyzed',
                is_public = true,
                approved_at = COALESCE(approved_at, NOW()),
                approved_by = COALESCE(approved_by, :admin_id)
            WHERE project_id = :project_id
        ");
        $stmt->execute([
            ':admin_id'   => $_SESSION['user_id'],
            ':project_id' => $project_id
        ]);

        // Notificação Humanizada para o Criador: "Parabéns, o teu projecto está viva!"
        if ($project && !$was_approved) {
            $notif_title   = "Projecto Aprovado! 🚀";
            $notif_content = "Excelente notícia! o seu projecto '{$project['title']}' foi validada pela equipa administrativa e já está disponível para todo o ecossistema.";
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
        $stmt = $db->prepare("
            UPDATE projects
            SET approval_status = 'rejected',
                status = 'rejected',
                is_public = false
            WHERE project_id = :project_id
        ");
        $stmt->execute([':project_id' => $project_id]);
        
        echo json_encode(['success' => true, 'message' => 'Projecto rejeitado e removido do feed público.']);
    }

    /**
     * AÇÃO: DESATIVAR (DEACTIVATE)
     * Desativa o projecto e retira do feed público (mas não rejeita a tese).
     */
    elseif ($action === 'deactivate') {
        $stmt = $db->prepare("
            UPDATE projects
            SET status = 'inactive',
                is_public = false,
                approval_status = 'pending'
            WHERE project_id = :project_id
        ");
        $stmt->execute([':project_id' => $project_id]);
        
        echo json_encode(['success' => true, 'message' => 'Projecto desativado do feed público.']);
    }

    /**
     * AÇÃO: ELIMINAR (DELETE)
     * Remoção definitiva: Remove o projecto e TODAS as suas dependências.
     * 
     * FIX POSTGRESQL: Usamos SAVEPOINT por cada tabela de dependência.
     * No PostgreSQL, quando um DELETE falha (ex: tabela inexistente) dentro de uma transaction,
     * a transaction inteira entra em estado 'aborted' e TODAS as queries seguintes falham,
     * mesmo dentro de try/catch. O SAVEPOINT isola cada operação: se uma falhar,
     * fazemos ROLLBACK TO SAVEPOINT e a transaction principal continua intacta.
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

        // Lista de tabelas de dependência a limpar antes de eliminar o projecto principal.
        $related_tables = [
            'project_tags',
            'project_media',
            'project_likes',
            'project_comments',
            'project_views',
            'project_votes',
            'project_investments',
            'project_endorsements',
            'project_milestones',
        ];
        
        foreach ($related_tables as $table) {
            try {
                $db->exec("SAVEPOINT sp_del_{$table}");
                $db->prepare("DELETE FROM {$table} WHERE project_id = ?")->execute([$project_id]);
                $db->exec("RELEASE SAVEPOINT sp_del_{$table}");
            } catch (Exception $e) {
                // Tabela não existe ou outro erro isolado — revertemos só este passo.
                $db->exec("ROLLBACK TO SAVEPOINT sp_del_{$table}");
                error_log("Limpeza ignorada na tabela {$table} (project_id={$project_id}): " . $e->getMessage());
            }
        }

        // Com todas as dependências limpas, eliminamos o registo mestre do projecto.
        $db->prepare("DELETE FROM projects WHERE project_id = ?")->execute([$project_id]);

        // Notificação ao criador sobre a eliminação.
        try {
            $db->exec("SAVEPOINT sp_del_notif");
            $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, created_at) VALUES (?, ?, ?, ?, 'system', NOW())");
            $notif_stmt->execute([
                $project_data['owner_id'],
                $_SESSION['user_id'],
                'Projecto Removido',
                "O seu projecto '{$project_data['title']}' foi removido permanentemente pelo administrador."
            ]);
            $db->exec("RELEASE SAVEPOINT sp_del_notif");
        } catch (Exception $e) {
            $db->exec("ROLLBACK TO SAVEPOINT sp_del_notif");
            error_log("Falha ao notificar eliminação: " . $e->getMessage());
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Projecto eliminado permanentemente.']);
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
