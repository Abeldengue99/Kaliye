<?php
/**
 * get_project_details.php — Aksanti v2 (Resilient)
 * Provider de dados para o modal de detalhes de projecto.
 */
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/project_votes_schema.php';

$project_id = 0;
if (isset($_GET['id'])) {
    $project_id = (int)$_GET['id'];
} elseif (isset($_GET['project_id'])) {
    $project_id = (int)$_GET['project_id'];
}
if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'ID do projecto não fornecido.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
ensureProjectVotesTable($db);

// ── ENSURE SECURITY TABLES ──
function ensureSecurityTables($db) {
    try {
        $db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS content_hash VARCHAR(255) DEFAULT NULL;");
        $db->exec("CREATE TABLE IF NOT EXISTS project_views_log (
            view_id SERIAL PRIMARY KEY,
            project_id INT NOT NULL,
            viewer_id INT NOT NULL,
            ip_address VARCHAR(45) NULL,
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (project_id, viewer_id)
        );");
        $db->exec("CREATE TABLE IF NOT EXISTS project_nda_logs (
            nda_id SERIAL PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            ip_address VARCHAR(45) NULL,
            accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (project_id, user_id)
        );");
    } catch (Exception $e) {}
}
ensureSecurityTables($db);

try {
    // ── 1. DADOS BASE DO PROJECTO (SELECT * para evitar erros de coluna) ──
    $stmt = $db->prepare(
        "SELECT p.*, 
                u.full_name, u.profile_pic, u.user_type AS owner_type, 
                u.mentorship_status, u.verification_status, u.is_verified
         FROM projects p
         LEFT JOIN users u ON p.owner_id = u.user_id
         WHERE p.project_id = ?"
    );
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projecto não encontrado.']);
        exit;
    }

    // ── 2. IDENTIDADE DO VIEWER ──
    $viewer_id   = (int)($_SESSION['user_id'] ?? 0);
    $viewer_type = $_SESSION['user_type'] ?? '';

    $is_owner    = ($viewer_id > 0 && $viewer_id == (int)$project['owner_id']);
    $is_admin    = in_array($viewer_type, ['admin', 'superadmin']);
    $is_mentor   = ($viewer_type === 'mentor' || (isset($_SESSION['mentorship_status']) && $_SESSION['mentorship_status'] === 'approved'));
    $is_investor = ($viewer_type === 'investor');
    $is_student  = in_array($viewer_type, ['univ_student', 'high_student']);

    $owner_type       = $project['owner_type'] ?? '';
    $project_by_investor = in_array($owner_type, ['investor']);

    // ── 3. NÍVEL DE ACESSO (LÓGICA RESTAURADA E MELHORADA PARA O ADMIN) ──
    $access_level  = 'summary';
    $can_apply     = false;
    $investor_level = 0;

    // Verificar Admin ignorando maiúsculas/minúsculas
    $is_admin = in_array(strtolower($viewer_type), ['admin', 'superadmin', 'administrador']);

    if ($is_owner || $is_admin || $is_mentor) {
        // O Admin tem acesso total a TUDO sem restrições
        $access_level = 'full';
    } elseif ($is_student) {
        if ($project_by_investor) {
            $access_level = 'full';
            $can_apply    = true;
        } else {
            $access_level = 'summary';
        }
    } elseif ($is_investor) {
        $is_verified = in_array($_SESSION['verification_status'] ?? '', ['verified', 'approved']);
        if ($is_verified || (isset($_SESSION['is_verified']) && $_SESSION['is_verified'])) {
            $access_level = 'full';
            $investor_level = 1; // Default to allow
        } else {
            $access_level = 'summary';
            $investor_level = 0;
        }
    }

    // ── 3.5 SMART NDA & REGISTO DE ACESSOS ──
    $has_accepted_nda = false;
    if (!$is_owner && !$is_admin && in_array($access_level, ['full', 'preview']) && $viewer_id > 0) {
        // Verificar se já aceitou o NDA
        try {
            $nda_stmt = $db->prepare("SELECT 1 FROM project_nda_logs WHERE project_id = ? AND user_id = ?");
            $nda_stmt->execute([$project_id, $viewer_id]);
            if ($nda_stmt->fetchColumn()) {
                $has_accepted_nda = true;
            }
        } catch (Exception $e) {}

        if (!$has_accepted_nda) {
            // Requer assinatura do NDA antes de prosseguir
            $access_level = 'nda_required';
        } else {
            // Se já aceitou e tem acesso, registar a visualização
            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $db->prepare("INSERT INTO project_views_log (project_id, viewer_id, ip_address) VALUES (?, ?, ?) ON CONFLICT DO NOTHING")
                   ->execute([$project_id, $viewer_id, $ip]);
            } catch (Exception $e) {}
        }
    }

    // ── 4. VOTOS (com try-catch para tabelas que podem não existir) ──
    $vote_count = 0;
    $user_voted = false;
    $can_vote = false;
    try {
        $vote_count_stmt = $db->prepare("SELECT COUNT(*) FROM project_votes WHERE project_id = ?");
        $vote_count_stmt->execute([$project_id]);
        $vote_count = (int)$vote_count_stmt->fetchColumn();

        if ($viewer_id > 0) {
            $voted_stmt = $db->prepare("SELECT 1 FROM project_votes WHERE project_id = ? AND voter_id = ?");
            $voted_stmt->execute([$project_id, $viewer_id]);
            $user_voted = (bool)$voted_stmt->fetchColumn();
        }

        $can_vote = ($viewer_id > 0 && !$is_owner);
    } catch (Exception $e) {
        // Tabela project_votes pode não existir
    }

    // ── 5. MEDIA (always fetch, when available) ──
    $media = [];
    try {
        $media_stmt = $db->prepare("SELECT * FROM project_media WHERE project_id = ? ORDER BY media_id ASC");
        $media_stmt->execute([$project_id]);
        $media = $media_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // ── 6. TAGS (always fetch, when available) ──
    $tags = [];
    try {
        $tags_stmt = $db->prepare("SELECT s.name FROM project_tags pt JOIN skills s ON pt.skill_id = s.skill_id WHERE pt.project_id = ?");
        $tags_stmt->execute([$project_id]);
        $tags = $tags_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}

    // ── 7. MASK DATA BEFORE RESPONSE (Evitar Roubo de Ideias) ──
    if ($access_level === 'summary' || $access_level === 'nda_required') {
        $project['description'] = $access_level === 'nda_required' ? 'Termo de Confidencialidade pendente. Assine o NDA para desbloquear.' : 'Dossier Protegido: Acesso restrito a Mentores Verificados e Investidores.';
        $project['needs_to_advance'] = 'Conteúdo Protegido';
        $project['idea_origin'] = 'Conteúdo Protegido';
        $project['motivation'] = 'Conteúdo Protegido';
        $project['project_url'] = '';
        $project['video_url'] = '';
        $project['pitch_video_url'] = '';
        $project['budget_needed'] = 0;
        $project['funding_goal'] = 0;
        $project['minimum_investment'] = 0;
        $project['equity_available'] = null;
        $project['execution_time'] = 'Protegido';
        $media = []; // Ocultar galeria/videos
    } elseif ($access_level === 'preview') {
        // Preview: vê video e descrição, mas esconde dados financeiros e origens
        $project['needs_to_advance'] = 'Acesso restrito a Investidores ativos.';
        $project['idea_origin'] = 'Protegido';
        $project['motivation'] = 'Protegido';
        $project['budget_needed'] = 0;
        $project['funding_goal'] = 0;
        $project['minimum_investment'] = 0;
        $project['equity_available'] = null;
    }

    // Helper para aceder colunas com segurança (evita undefined index)
    $g = function($key, $default = '') use ($project) {
        return isset($project[$key]) ? $project[$key] : $default;
    };

    $response_project = array_merge($project, [
        'owner_type'        => $owner_type,
        'user_type'         => $owner_type,
        'owner_name'        => $g('full_name'),
        'owner_pic'         => $g('profile_pic'),
        'full_name'         => $g('full_name'),
        'profile_pic'       => $g('profile_pic'),
        'is_verified'       => $g('is_verified', false),
        'vote_count'        => $vote_count,
        'user_voted'        => $user_voted,
        'media'             => $media,
        'tags'              => $tags,
        'tags_csv'          => implode(', ', $tags),
        'video_url'         => $g('video_url'),
        'pitch_video_url'   => $g('pitch_video_url', $g('video_url')),
        'funding_goal'      => $g('funding_goal', $g('budget_needed', 0)),
        'budget_needed'     => $g('budget_needed', $g('funding_goal', 0)),
        'minimum_investment'=> $g('minimum_investment', 0),
        'equity_available'  => $g('equity_available'),
        'equity_committed'  => $g('equity_committed', 0),
        'total_invested'    => $g('total_invested', 0),
        'total_investors'   => $g('total_investors', 0),
        'campaign_end_date' => $g('campaign_end_date'),
        'project_url'       => $g('project_url'),
        'idea_origin'       => $g('idea_origin'),
        'motivation'        => $g('motivation'),
        'needs_to_advance'  => $g('needs_to_advance'),
        'execution_time'    => $g('execution_time'),
        'team_size'         => $g('team_size'),
        'description_short' => (!empty($project['description']) ? substr(implode(' ', array_slice(explode("\n", $project['description']), 0, 2)), 0, 160) . (strlen($project['description']) > 160 ? '…' : '') : ''),
        'market_score'      => $g('market_score', 0),
        'content_hash'      => $g('content_hash'),
        'project_fields'    => $project,
    ]);

    echo json_encode([
        'success'        => true,
        'project'        => $response_project,
        'access_level'   => $access_level,
        'investor_level' => $investor_level,
        'can_apply'      => $can_apply,
        'can_vote'       => $can_vote,
        'is_investor'    => $is_investor,
        'viewer_name'    => $_SESSION['full_name'] ?? 'Utilizador KALIYE',
    ]);

} catch (PDOException $e) {
    error_log("Get Project Details Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>
