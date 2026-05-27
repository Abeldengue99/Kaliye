<?php

/** @var array $post — Variável providenciada pelo loop no index.php */
$project = isset($post) ? $post : [];

if (empty($project)) return;
$viewer_id    = (int)($_SESSION['user_id'] ?? 0);
$viewer_role  = $_SESSION['user_type'] ?? '';

// Identidade do Autor
$author_id    = (int)($project['author_id'] ?? ($project['owner_id'] ?? 0));
$owner_role   = $project['author_role'] ?? ($project['owner_type'] ?? '');

// Tipo do dono do projecto
$project_by_investor = in_array($owner_role, ['investor']);
$project_by_student  = in_array($owner_role, ['univ_student', 'high_student']);

// Flags do viewer
$is_owner     = ($viewer_id > 0 && $author_id === $viewer_id);
$is_admin     = in_array($viewer_role, ['admin', 'superadmin']);
$is_investor  = ($viewer_role === 'investor');
$is_mentor    = ($viewer_role === 'mentor' || (isset($_SESSION['mentorship_status']) && $_SESSION['mentorship_status'] === 'approved'));
$is_student   = in_array($viewer_role, ['univ_student', 'high_student']);
$is_verified  = (isset($_SESSION['is_verified']) && ($_SESSION['is_verified'] === true || $_SESSION['is_verified'] == 1));

// ── MATRIZ DE ACESSO v2 ──────────────────────────────────────────────────
// 'full'    → vê tudo
// 'preview' → vê descrição + vídeo, sem financeiros
// 'summary' → só título, categoria, pequeno resumo
$access_level  = 'summary';
$can_apply     = false;  // Botão "Candidatar-se" (estudante em proj. investidor)

if ($is_owner || $is_admin || $is_mentor) {
    $access_level = 'full';

} elseif ($is_student) {
    if ($project_by_investor) {
        // Estudante vê projecto de investidor → acesso total + candidatura
        $access_level = 'full';
        $can_apply    = true;
    } else {
        // Estudante vê projecto de outro estudante → só resumo
        $access_level = 'summary';
    }

} elseif ($is_investor) {
    // Verificar nível de acesso do investidor
    // (usamos uma verificação rápida em sessão para não fazer query a cada card)
    if (!isset($_SESSION['investor_access_level'])) {
        global $db;
        if (isset($db)) {
            $inv_q = $db->prepare("SELECT status FROM project_investments WHERE investor_id = ? ORDER BY created_at DESC LIMIT 1");
            $inv_q->execute([$viewer_id]);
            $last_inv = $inv_q->fetch(PDO::FETCH_ASSOC);
            if (!$last_inv) {
                $_SESSION['investor_access_level'] = 0;
            } elseif (in_array($last_inv['status'], ['accepted', 'paid', 'confirmed'])) {
                $_SESSION['investor_access_level'] = 2;
            } else {
                $_SESSION['investor_access_level'] = 1;
            }
        } else {
            $_SESSION['investor_access_level'] = 0;
        }
    }
    $investor_level = (int)$_SESSION['investor_access_level'];
    if ($investor_level >= 2) {
        $access_level = 'full';
    } elseif ($investor_level >= 1) {
        $access_level = 'preview';
    } else {
        $access_level = 'summary';
    }
}

// Compatibilidade com código legado
$has_full_access = ($access_level === 'full');

// Votacao comunitaria: qualquer utilizador autenticado pode votar em ideias de outros autores.
$can_vote = ($viewer_id > 0 && !$is_owner);
$user_voted = (bool)($project['user_voted'] ?? false);
$vote_count = (int)($project['vote_count'] ?? 0);

// Configuração de Media
require_once __DIR__ . '/../ProjectMediaHelper.php';
$video_url = !empty($project['pitch_video_url']) ? $project['pitch_video_url'] : ($project['video_url'] ?? null);
$has_video = !empty($video_url);
$video_src = $has_video ? ((strpos($video_url, 'http') === 0) ? $video_url : $base_url . 'carregamentos/projects/' . $video_url) : '';
$media_path = ProjectMediaHelper::getCover($project, $base_url);
?>

<div class="project-card-premium shine-on-hover <?php echo $vote_count > 0 ? 'project-card-vote-highlight' : ''; ?>" data-votes="<?php echo $vote_count; ?>" oncontextmenu="return false;" style="user-select: none;">
    <?php if ($is_owner): ?>
        <div class="owner-actions-trigger">
            <button onclick="toggleProjectMenu(event, <?php echo $project['project_id']; ?>)"
                    style="background: rgba(0,0,0,0.5); border: none; color: white; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 1rem; backdrop-filter: blur(4px);">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div id="dropdown-<?php echo $project['project_id']; ?>" class="owner-dropdown">
                <a href="javascript:void(0)" onclick="editProject(<?php echo $project['project_id']; ?>)">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="javascript:void(0)" onclick="deleteProject(<?php echo $project['project_id']; ?>)" style="color: #ef4444;">
                    <i class="fas fa-trash"></i> Eliminar
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="project-image-wrapper <?php echo (!$has_full_access && $has_video) ? 'media-locked' : ''; ?>"
         onclick="<?php echo $has_full_access ? ($has_video ? 'openProjectDetails('.$project['project_id'].', 0)' : 'openProjectDetails('.$project['project_id'].', 1)') : 'showVerificationRequired()'; ?>">

        <!-- Overlay de Protecção de Media -->
        <div style="position: absolute; inset:0; z-index:5; pointer-events: none;"></div>

        <?php if ($has_video): ?>
            <?php if ($has_full_access): ?>
                <video src="<?php echo htmlspecialchars($video_src); ?>"
                       muted loop playsinline preload="none" controlsList="nodownload"
                       poster="<?php echo htmlspecialchars($media_path); ?>"
                       onmouseover="this.play()"
                       onmouseout="this.pause()"
                       style="width: 100%; height: 100%; object-fit: cover;"></video>
                <div class="video-play-indicator" style="background: rgba(247, 148, 29, 0.9);">
                    <i class="fas fa-play" style="margin-left: 3px;"></i>
                </div>
            <?php else: ?>
                <div style="width: 100%; height: 100%; position: relative; overflow: hidden;">
                    <img src="<?php echo htmlspecialchars($media_path); ?>" alt="Projeto"
                         loading="lazy" decoding="async"
                         style="filter: blur(14px); transform: scale(1.06); width: 100%; height: 100%; object-fit: cover;">
                    <div class="content-locked-overlay">
                        <div class="lock-badge">
                            <i class="fas fa-lock"></i>
                            <span>Acesso Restrito</span>
                        </div>
                        <p style="font-size: 0.65rem; opacity: 0.8; margin-top: 8px;">Vídeo Pitch Protegido pela KALIYE</p>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($has_full_access): ?>
                <img src="<?php echo htmlspecialchars($media_path); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" loading="lazy" decoding="async" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <img src="<?php echo htmlspecialchars($media_path); ?>" alt="Projeto"
                     loading="lazy" decoding="async"
                     style="filter: blur(12px); transform: scale(1.05); width: 100%; height: 100%; object-fit: cover;">
                <div class="content-locked-overlay content-locked-overlay--light">
                    <div class="lock-badge">
                        <i class="fas fa-lock"></i>
                        <span>Acesso Restrito</span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="category-badge-img">
            <i class="fas fa-microchip" style="font-size: 0.6rem; margin-right: 5px;"></i>
            <?php echo htmlspecialchars($project['category'] ?: 'Ideia'); ?>
        </div>

        <?php if ($vote_count > 0): ?>
        <div class="community-vote-badge" title="<?php echo $vote_count; ?> voto<?php echo $vote_count === 1 ? '' : 's'; ?> da comunidade">
            <i class="fas fa-star"></i>
            <span><?php echo $vote_count; ?></span>
        </div>
        <?php endif; ?>

        <!-- 🚀 MARKET READINESS BADGE -->
        <?php 
            $score = isset($project['market_score']) ? (int)$project['market_score'] : 0;
            // Cor baseada na prontidão (Vermelho -> Amarelo -> Verde)
            $score_color = $score >= 75 ? '#10b981' : ($score >= 45 ? '#f7941d' : '#ef4444');
        ?>
        <div class="market-score-badge" title="Market Readiness Score: Análise automática de maturidade KALIYE" 
             style="position: absolute; top: 12px; right: 12px; background: <?php echo $score_color; ?>; color: #fff; padding: 5px 12px; border-radius: 10px; font-size: 0.65rem; font-weight: 950; box-shadow: 0 4px 15px rgba(0,0,0,0.4); z-index: 6; display: flex; align-items: center; gap: 6px; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(5px);">
            <i class="fas fa-rocket"></i> <?php echo $score; ?>%
        </div>

        <div class="project-verified-chip" style="position: absolute; bottom: 12px; left: 12px; background: rgba(30, 64, 175, 0.9); color: white; padding: 5px 12px; border-radius: 8px; font-size: 0.55rem; font-weight: 950; text-transform: uppercase; letter-spacing: 0.8px; backdrop-filter: blur(8px); z-index: 6; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);">
            <i class="fas fa-shield-alt" style="color: #60a5fa;"></i> Verificado
        </div>
    </div>

    <div class="project-info-block">
        <div class="project-card-topline">
            <div class="project-author-row" onclick="event.stopPropagation(); openUserCard(<?php echo $author_id; ?>)" style="cursor: pointer;">
                <img src="<?php echo $base_url . getUserAvatarUrl($project['author_role'] ?? 'student', $project['mentorship_status'] ?? 'unsubmitted', $project['profile_pic'] ?? $project['owner_pic'] ?? ''); ?>"
                     alt="autor" class="author-avatar-small" loading="lazy" decoding="async" style="border: 1.5px solid #f7941d;">
                <div>
                    <span class="author-name-small"><?php echo htmlspecialchars($project['full_name']); ?></span>
                    <span class="author-type-small"><?php echo $project['is_verified'] ? '<i class="fas fa-check-circle" style="color: #3b82f6;"></i> ' : ''; ?><?php echo ucfirst($project['author_role'] ?? 'Membro'); ?></span>
                </div>
            </div>
            <div class="project-card-tools">
                <span style="font-size: 0.6rem; color: rgba(255,255,255,0.3); font-weight: 800; text-transform: uppercase;"><?php echo $project['project_stage'] ?: 'Protótipo'; ?></span>
                
                <!-- 🌍 BOTÃO DE TRADUÇÃO (FEED INTERNACIONAL) -->
                <div class="translate-trigger" onclick="translateCard(this, <?php echo $project['project_id']; ?>)" title="Traduzir para Inglês/Francês" style="background: rgba(255,255,255,0.05); color: #94a3b8; width: 24px; height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; border: 1px solid rgba(255,255,255,0.08);">
                    <i class="fas fa-globe" style="font-size: 0.75rem;"></i>
                </div>
            </div>
        </div>

        <h3 class="project-title-premium translatable-title-<?php echo $project['project_id']; ?>"
            onclick="<?php echo $has_full_access ? ($has_video ? 'openProjectDetails('.$project['project_id'].', 0)' : 'openProjectDetails('.$project['project_id'].', 1)') : 'showVerificationRequired()'; ?>"
            style="<?php echo !$has_full_access ? 'filter: blur(3px); user-select: none;' : ''; ?> font-size: 1.02rem; line-height: 1.2; margin-bottom: 0.25rem;">
            <?php echo $has_full_access ? htmlspecialchars($project['title']) : htmlspecialchars(substr($project['title'], 0, 15)) . '•••'; ?>
        </h3>

        <?php if ($has_full_access && !empty($project['description'])): ?>
        <p class="project-description-premium project-description-compact">
            <?php echo htmlspecialchars(substr($project['description'], 0, 82)); ?><?php echo strlen($project['description']) > 82 ? '...' : ''; ?>
        </p>
        <?php endif; ?>

        <div class="project-signal-row">
            <span>
                <i class="fas fa-layer-group"></i>
                <?php echo htmlspecialchars($project['project_stage'] ?: 'Ideia'); ?>
            </span>
            <span>
                <i class="fas fa-coins"></i>
                <?php if ($has_full_access): ?>
                    <?php echo number_format((float)($project['budget_needed'] ?? 0), 0, ',', '.'); ?> AKZ
                <?php else: ?>
                    Meta privada
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div class="project-card-actions">

        <?php if ($access_level === 'full' || $access_level === 'preview'): ?>

            <!-- Botão principal: Dossier ou Preview -->
            <a href="javascript:void(0)" 
               onclick="openProjectDetails(<?php echo $project['project_id']; ?>, <?php echo $has_video ? '0' : '1'; ?>)" 
               class="btn-view-details shine-on-hover" 
               style="flex: 2; text-align: center; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 800; font-size: 0.72rem; text-decoration: none;">
                <?php echo $access_level === 'full' ? 'Ver Dossier' : 'Ver Preview'; ?>
            </a>

            <?php if ($can_apply): ?>
                <!-- Estudante em projecto de investidor → Candidatar-se -->
                <a href="javascript:void(0)" 
                   onclick="openProjectApplication(<?php echo $project['project_id']; ?>)" 
                   class="btn-view-details shine-on-hover" 
                   style="flex: 1.2; text-align: center; background: linear-gradient(135deg, #10b981, #059669); color: #fff; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 900; font-size: 0.7rem; text-decoration: none; gap: 5px;">
                    <i class="fas fa-paper-plane" style="font-size: 0.7rem;"></i> Candidatar
                </a>
            <?php elseif ($is_investor && $access_level === 'full'): ?>
                <a href="javascript:void(0)" 
                   onclick="openInvestmentFlow(<?php echo $project['project_id']; ?>)" 
                   class="btn-view-details shine-on-hover" 
                   style="flex: 1.2; text-align: center; background: #fff; color: #000; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 900; font-size: 0.75rem; text-decoration: none;">
                    <i class="fas fa-hand-holding-usd"></i>
                </a>
            <?php elseif ($is_investor && $access_level === 'preview'): ?>
                <!-- Investidor L1: incentivo a fazer proposta -->
                <a href="javascript:void(0)" 
                   onclick="openInvestmentFlow(<?php echo $project['project_id']; ?>)" 
                   class="btn-view-details shine-on-hover" 
                   style="flex: 1.2; text-align: center; background: linear-gradient(135deg, #f7941d, #f59e0b); color: #000; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 900; font-size: 0.65rem; text-decoration: none; gap: 4px;">
                    <i class="fas fa-lock-open" style="font-size: 0.65rem;"></i> Propor
                </a>
            <?php elseif ($is_mentor): ?>
                <a href="javascript:void(0)" 
                   onclick="applyForMentorship(<?php echo $project['project_id']; ?>)" 
                   class="btn-view-details shine-on-hover" 
                   style="flex: 1.2; text-align: center; background: #3b82f6; color: #fff; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 900; font-size: 0.75rem; text-decoration: none;">
                    <i class="fas fa-chalkboard-teacher"></i>
                </a>
            <?php endif; ?>

            <!-- Like -->
            <?php if ($access_level === 'full'): ?>
            <button type="button" class="btn-like-action" onclick="event.stopPropagation(); toggleLike(this, <?php echo $project['project_id']; ?>)" 
                    style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: <?php echo ($project['user_liked'] ?? 0) ? '#ef4444' : 'rgba(255,255,255,0.3)'; ?>; width: 42px; height: 42px; border-radius: 10px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center;">
                <i class="<?php echo ($project['user_liked'] ?? 0) ? 'fas' : 'far'; ?> fa-heart"></i>
            </button>
            <?php endif; ?>

            <!-- ⭐ Voto de Estudante -->
            <?php if ($can_vote): ?>
            <button type="button" 
                    id="vote-btn-<?php echo $project['project_id']; ?>"
                    onclick="event.stopPropagation(); toggleProjectVote(this, <?php echo $project['project_id']; ?>)"
                    title="Votar nesta ideia"
                    style="background: <?php echo $user_voted ? 'rgba(234,179,8,0.15)' : 'rgba(255,255,255,0.05)'; ?>; border: 1px solid <?php echo $user_voted ? 'rgba(234,179,8,0.4)' : 'rgba(255,255,255,0.1)'; ?>; color: <?php echo $user_voted ? '#facc15' : 'rgba(255,255,255,0.3)'; ?>; width: 42px; height: 42px; border-radius: 10px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; position: relative;">
                <i class="<?php echo $user_voted ? 'fas' : 'far'; ?> fa-star" style="font-size: 0.85rem;"></i>
                <?php if ($vote_count > 0): ?>
                <span style="position: absolute; top: -6px; right: -6px; background: #facc15; color: #000; font-size: 0.5rem; font-weight: 900; border-radius: 20px; padding: 1px 4px; min-width: 14px; text-align: center;"><?php echo $vote_count; ?></span>
                <?php endif; ?>
            </button>
            <?php endif; ?>

        <?php else: ?>
            <!-- Acesso Bloqueado -->
            <?php if ($is_investor): ?>
                <!-- Investidor L0: nunca fez proposta -->
                <a href="javascript:void(0)" 
                   onclick="openInvestmentFlow(<?php echo $project['project_id']; ?>)" 
                   class="btn-view-details shine-on-hover" 
                   style="flex: 1; text-align: center; background: linear-gradient(135deg, #f7941d, #f59e0b); color: #000; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 900; font-size: 0.72rem; text-decoration: none; gap: 6px;">
                    <i class="fas fa-lock" style="font-size: 0.65rem;"></i> Fazer 1ª Proposta para Desbloquear
                </a>
            <?php elseif ($is_student): ?>
                <!-- Estudante: projecto de outro estudante -->
                <div style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; background: rgba(255,255,255,0.03); border: 1px dashed rgba(255,255,255,0.08); border-radius: 10px; height: 42px; font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.25); text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fas fa-shield-alt" style="font-size: 0.65rem;"></i> Dossier Protegido
                </div>
                <?php if ($can_vote): ?>
                <button type="button" 
                        id="vote-btn-<?php echo $project['project_id']; ?>"
                        onclick="event.stopPropagation(); toggleProjectVote(this, <?php echo $project['project_id']; ?>)"
                        title="Votar nesta ideia"
                        style="background: <?php echo $user_voted ? 'rgba(234,179,8,0.15)' : 'rgba(255,255,255,0.05)'; ?>; border: 1px solid <?php echo $user_voted ? 'rgba(234,179,8,0.4)' : 'rgba(255,255,255,0.1)'; ?>; color: <?php echo $user_voted ? '#facc15' : 'rgba(255,255,255,0.3)'; ?>; width: 42px; height: 42px; border-radius: 10px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; position: relative; flex: 0 0 42px;">
                    <i class="<?php echo $user_voted ? 'fas' : 'far'; ?> fa-star" style="font-size: 0.85rem;"></i>
                    <?php if ($vote_count > 0): ?>
                    <span style="position: absolute; top: -6px; right: -6px; background: #facc15; color: #000; font-size: 0.5rem; font-weight: 900; border-radius: 20px; padding: 1px 4px; min-width: 14px; text-align: center;"><?php echo $vote_count; ?></span>
                    <?php endif; ?>
                </button>
                <?php endif; ?>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="showVerificationRequired()" class="btn-view-details btn-view-details--locked shine-on-hover" style="flex: 1; text-align: center; background: #f7941d; color: #fff; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 900; font-size: 0.75rem; text-decoration: none;">
                    <i class="fas fa-lock" style="font-size: 0.7rem; margin-right: 8px;"></i> Desbloquear Acesso
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
