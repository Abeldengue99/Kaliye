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
        try {
            $inv_check = $db->prepare("SELECT status FROM project_investments WHERE investor_id = ? ORDER BY created_at DESC LIMIT 1");
            $inv_check->execute([$viewer_id]);
            $last_investment = $inv_check->fetch(PDO::FETCH_ASSOC);
            if (!$last_investment) {
                $investor_level = 0;
                $access_level   = 'summary';
            } elseif (in_array($last_investment['status'], ['accepted', 'paid', 'confirmed'])) {
                $investor_level = 2;
                $access_level   = 'full';
            } else {
                $investor_level = 1;
                $access_level   = 'preview';
            }
        } catch (Exception $e) {
            $investor_level = 0;
            $access_level = 'preview';
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

    // ── 7. CONSTRUIR RESPOSTA ──
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
        'project_fields'    => $project,
    ]);

    echo json_encode([
        'success'        => true,
        'project'        => $response_project,
        'access_level'   => $access_level,
        'investor_level' => $investor_level,
        'can_apply'      => $can_apply,
        'can_vote'       => $can_vote,
    ]);

} catch (PDOException $e) {
    error_log("Get Project Details Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>
