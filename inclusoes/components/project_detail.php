<?php
/**
 * Componente: Vista de Detalhes do Projeto (Aksanti 2026)
 * Matriz de acesso v2:
 *   - Estudante vê projecto de estudante → só resumo (bloqueado aqui com aviso)
 *   - Estudante vê projecto de investidor → acesso total + candidatura
 *   - Investidor L0 (sem proposta) → preview
 *   - Investidor L1/L2 → acesso total
 */

// ── Identidade do viewer ──────────────────────────────────────────────
$_viewer_id   = (int)($_SESSION['user_id'] ?? 0);
$_viewer_role = $_SESSION['user_type'] ?? '';
$_owner_role  = $project['owner_type'] ?? ($project['author_role'] ?? '');
$_is_owner    = ($_viewer_id > 0 && $_viewer_id == (int)($project['owner_id'] ?? 0));
$_is_admin    = in_array($_viewer_role, ['admin', 'superadmin']);
$_is_mentor   = ($_viewer_role === 'mentor' || (isset($_SESSION['mentorship_status']) && $_SESSION['mentorship_status'] === 'approved'));
$_is_investor = ($_viewer_role === 'investor');
$_is_student  = in_array($_viewer_role, ['univ_student', 'high_student']);
$_proj_by_inv = ($_owner_role === 'investor');

// ── Nível de acesso ───────────────────────────────────────────────────
$_detail_access = 'summary';
$_can_apply = false;
if ($_is_owner || $_is_admin || $_is_mentor) {
    $_detail_access = 'full';
} elseif ($_is_student) {
    $_detail_access = $_proj_by_inv ? 'full' : 'summary';
    $_can_apply     = $_proj_by_inv;
} elseif ($_is_investor) {
    $lvl = (int)($_SESSION['investor_access_level'] ?? 0);
    $_detail_access = $lvl >= 2 ? 'full' : ($lvl >= 1 ? 'preview' : 'summary');
}

// ── Votos ─────────────────────────────────────────────────────────────
$_vote_count = 0; $_user_voted = false; $_can_vote = false;
if (isset($db) && isset($project['project_id'])) {
    try {
        $vs = $db->prepare("SELECT COUNT(*) FROM project_votes WHERE project_id = ?");
        $vs->execute([$project['project_id']]);
        $_vote_count = (int)$vs->fetchColumn();
        if ($_viewer_id > 0) {
            $uv = $db->prepare("SELECT 1 FROM project_votes WHERE project_id = ? AND voter_id = ?");
            $uv->execute([$project['project_id'], $_viewer_id]);
            $_user_voted = (bool)$uv->fetchColumn();
        }
        $_can_vote = ($_viewer_id > 0 && !$_is_owner);
    } catch (Exception $e) {}
}

// ── Cálculo de financiamento ──────────────────────────────────────────
$percent = ($project['funding_goal'] > 0) ? min(100, ($project['total_invested'] / $project['funding_goal']) * 100) : 0;
$_equity_avail     = isset($project['equity_available'])    ? (float)$project['equity_available']    : null;
$_equity_committed = isset($project['equity_committed'])    ? (float)$project['equity_committed']    : 0;
$_equity_remaining = $_equity_avail !== null ? max(0, $_equity_avail - $_equity_committed) : null;

// Registar Visualização
if (isset($db) && isset($project['project_id'])) {
    try {
        $view_stmt = $db->prepare("INSERT INTO project_views (project_id, viewer_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $view_stmt->execute([$project['project_id'], $_SESSION['user_id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
    } catch (Exception $e) {}
}
?>

<div class="project-details-container" style="margin-top: 1rem; padding-bottom: 5rem;">
    
    <!-- Barra de Navegação e Ações de Proprietário -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <a href="projects.php" style="color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 0.8rem; font-size: 0.9rem; font-weight: 700; transition: 0.3s;" onmouseover="this.style.color='white'">
            <i class="fas fa-arrow-left"></i> VOLTAR AO MARKETPLACE
        </a>
        
        <?php if ($project['owner_id'] == $_SESSION['user_id']): ?>
        <div style="display: flex; gap: 12px;">
            <button onclick="editProject(<?php echo $project['project_id']; ?>)" class="btn-primary" style="background: var(--surface-5); border: 1px solid var(--surface-10); padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 700;">
                <i class="fas fa-edit"></i> Editar Projecto
            </button>
            <button onclick="deleteProject(<?php echo $project['project_id']; ?>)" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; padding: 0.8rem 1.5rem; border-radius: 12px; cursor: pointer; font-weight: 700;">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Seção Principal: Vídeo Pitch e Resumo -->
    <div class="glass" style="border-radius: 32px; overflow: hidden; margin-bottom: 2.5rem; border: 1px solid var(--surface-5); background: #0d1628;">
        <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; min-height: 480px;">
            
            <!-- Área do Vídeo de Pitch -->
            <div style="background: #000; position: relative; display: flex; align-items: center; justify-content: center; border-right: 1px solid var(--surface-5);">
                <?php if (!empty($project['video_url'])): ?>
                    <video src="<?php echo $base_url . $project['video_url']; ?>" controls style="width: 100%; height: 100%; object-fit: cover;"></video>
                <?php else: ?>
                    <?php 
                        require_once __DIR__ . '/../ProjectMediaHelper.php';
                        $media_path = ProjectMediaHelper::getCover($project, $base_url); 
                    ?>
                    <img src="<?php echo $media_path; ?>" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.4;">
                    <div style="position: absolute; text-align: center; color: white;">
                        <i class="fas fa-video-slash" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p style="font-weight: 700; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;">Vídeo em Processamento</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Painel de Informações Rápidas -->
            <div style="padding: 3rem; display: flex; flex-direction: column; background: linear-gradient(135deg, rgba(30, 41, 59, 0.4) 0%, rgba(13, 22, 40, 1) 100%);">
                <div style="margin-bottom: auto;">
                    <div style="display: flex; gap: 8px; margin-bottom: 1.5rem;">
                        <span style="background: var(--brand-primary); color: black; padding: 6px 14px; border-radius: 8px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">
                            <?php echo htmlspecialchars($project['category'] ?: 'TECNOLOGIA'); ?>
                        </span>
                        <span style="background: var(--surface-10); color: white; padding: 6px 14px; border-radius: 8px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase;">
                            AUDITADO POR IA
                        </span>
                    </div>

                    <h1 style="font-size: 2.2rem; margin: 0 0 1rem; color: white; font-family: 'Outfit', sans-serif; font-weight: 800; line-height: 1.1;">
                        <?php echo htmlspecialchars($project['title']); ?>
                    </h1>
                    
                    <div style="display: flex; align-items: center; gap: 1rem; color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">
                        <span><i class="fas fa-map-marker-alt" style="color: var(--brand-primary);"></i> Luanda, Angola</span>
                        <span style="opacity: 0.3;">|</span>
                        <span><i class="far fa-calendar-alt"></i> Outubro 2026</span>
                    </div>
                </div>

                <!-- Barra de Progresso de Financiamento -->
                <div style="margin: 2.5rem 0;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px;">
                        <div>
                            <span style="font-size: 2.2rem; font-weight: 900; color: white; font-family: 'Outfit', sans-serif;">
                                <?php echo number_format($project['total_invested'], 0, ',', '.'); ?>
                            </span>
                            <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: 700;"> AKZ</span>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">Meta: <?php echo number_format($project['funding_goal'] ?: 0, 0, ',', '.'); ?> AKZ</div>
                        </div>
                    </div>
                    
                    <div style="background: var(--surface-5); height: 12px; border-radius: 6px; overflow: hidden; border: 1px solid var(--surface-5);">
                        <div style="width: <?php echo $percent; ?>%; background: linear-gradient(90deg, var(--brand-primary), #fbbf24); height: 100%; box-shadow: 0 0 20px rgba(247, 148, 29, 0.4);"></div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-top: 12px; font-size: 0.8rem; font-weight: 700;">
                        <span style="color: var(--brand-primary);"><?php echo number_format($percent, 1); ?>% DO TOTAL CAPTADO</span>
                        <span style="color: white;"><?php echo $project['total_investors'] ?? 0; ?> INVESTIDORES</span>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div style="display: flex; gap: 1rem; margin-top: auto; flex-wrap: wrap;">
                    <?php if ($_can_apply): ?>
                    <button onclick="openProjectApplication(<?php echo $project['project_id']; ?>)" class="btn-primary" style="flex: 1; height: 60px; justify-content: center; font-weight: 900; background: linear-gradient(135deg,#10b981,#059669); color: white; border-radius: 18px; border: none; font-size: 0.9rem; cursor: pointer; transition: 0.3s;">
                        <i class="fas fa-paper-plane" style="margin-right:8px;"></i> CANDIDATAR-ME
                    </button>
                    <?php elseif ($_is_investor && $_detail_access === 'full'): ?>
                    <button onclick="openInvestmentFlow(<?php echo $project['project_id']; ?>)" class="btn-primary" style="flex: 1; height: 60px; justify-content: center; font-weight: 900; background: white; color: black; border-radius: 18px; border: none; font-size: 0.9rem; cursor: pointer; transition: 0.3s;">
                        FAZER PROPOSTA DE INVESTIMENTO
                    </button>
                    <?php elseif ($_is_investor && $_detail_access !== 'full'): ?>
                    <button onclick="openInvestmentFlow(<?php echo $project['project_id']; ?>)" class="btn-primary" style="flex: 1; height: 60px; justify-content: center; font-weight: 900; background: linear-gradient(135deg,#f7941d,#f59e0b); color: black; border-radius: 18px; border: none; font-size: 0.85rem; cursor: pointer; transition: 0.3s;">
                        <i class="fas fa-lock-open" style="margin-right:8px;"></i> FAZER PROPOSTA E DESBLOQUEAR
                    </button>
                    <?php endif; ?>
                    <?php if ($_detail_access === 'full'): ?>
                    <button type="button" onclick="event.stopPropagation(); toggleLike(this, <?php echo $project['project_id']; ?>)" style="background: var(--surface-5); border: 1px solid var(--surface-10); color: <?php echo ($project['is_liked'] ?? ($project['user_liked'] ?? 0)) ? '#ef4444' : 'white'; ?>; border-radius: 18px; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; cursor: pointer; transition: 0.3s;">
                        <i class="<?php echo ($project['is_liked'] ?? ($project['user_liked'] ?? 0)) ? 'fas' : 'far'; ?> fa-heart"></i>
                    </button>
                    <?php if ($_can_vote): ?>
                    <button type="button" id="vote-detail-<?php echo $project['project_id']; ?>" onclick="event.stopPropagation(); toggleProjectVote(this, <?php echo $project['project_id']; ?>)" title="Votar neste projecto" style="background: <?php echo $_user_voted ? 'rgba(234,179,8,0.15)' : 'var(--surface-5)'; ?>; border: 1px solid <?php echo $_user_voted ? 'rgba(234,179,8,0.4)' : 'var(--surface-10)'; ?>; color: <?php echo $_user_voted ? '#facc15' : 'white'; ?>; border-radius: 18px; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; cursor: pointer; transition: 0.3s; position: relative;">
                        <i class="<?php echo $_user_voted ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php if ($_vote_count > 0): ?><span style="position:absolute;top:-4px;right:-4px;background:#facc15;color:#000;font-size:0.5rem;font-weight:900;border-radius:20px;padding:2px 5px;"><?php echo $_vote_count; ?></span><?php endif; ?>
                    </button>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($_detail_access === 'summary'): ?>
    <!-- BLOQUEIO: Estudante vê projecto de outro estudante -->
    <div class="glass" style="padding: 3rem; border-radius: 32px; background: rgba(13,22,40,0.8); border: 1px dashed rgba(255,255,255,0.08); text-align: center; margin-bottom: 2rem;">
        <i class="fas fa-shield-alt" style="font-size:3rem; color: rgba(255,255,255,0.15); margin-bottom:1.5rem; display:block;"></i>
        <h3 style="color:white; font-weight:800; margin:0 0 1rem;">Dossier Protegido</h3>
        <p style="color:var(--text-muted); font-size:0.95rem; max-width:400px; margin:0 auto 2rem; line-height:1.6;">O dossier completo desta projecto está reservado a Investidores verificados. Como estudante, podes ver o resumo público.</p>
        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:20px; padding:1.5rem; max-width:500px; margin:0 auto; text-align:left;">
            <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.7; margin:0;"><?php echo htmlspecialchars(substr($project['description'] ?? '', 0, 200)); ?>…</p>
        </div>
        <?php if ($_can_vote): ?>
        <button type="button" id="vote-detail-<?php echo $project['project_id']; ?>" onclick="event.stopPropagation(); toggleProjectVote(this, <?php echo $project['project_id']; ?>)" title="Votar neste projecto" style="margin-top:1.5rem; background: <?php echo $_user_voted ? 'rgba(234,179,8,0.15)' : 'rgba(255,255,255,0.06)'; ?>; border: 1px solid <?php echo $_user_voted ? 'rgba(234,179,8,0.4)' : 'rgba(255,255,255,0.12)'; ?>; color: <?php echo $_user_voted ? '#facc15' : 'white'; ?>; border-radius: 16px; min-width: 180px; height: 52px; display: inline-flex; align-items: center; justify-content: center; gap: 0.6rem; font-size: 0.86rem; font-weight: 900; cursor: pointer; transition: 0.3s; position: relative;">
            <i class="<?php echo $_user_voted ? 'fas' : 'far'; ?> fa-star"></i>
            Votar neste projecto
            <?php if ($_vote_count > 0): ?><span style="position:absolute;top:-6px;right:-6px;background:#facc15;color:#000;font-size:0.62rem;font-weight:900;border-radius:20px;padding:2px 6px;"><?php echo $_vote_count; ?></span><?php endif; ?>
        </button>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Conteúdo Detalhado: Descrição e Métricas -->
    <div style="display: grid; grid-template-columns: 2fr 1.1fr; gap: 2.5rem;">
        
        <!-- Coluna Esquerda: Texto e Estratégia -->
        <div style="display: flex; flex-direction: column; gap: 2.5rem;">
            
            <!-- Descrição Completa -->
            <div class="glass" style="padding: 3rem; border-radius: 32px; background: rgba(13, 22, 40, 0.6); border: 1px solid rgba(247, 148, 29, 0.05);">
                <h3 style="margin-top: 0; color: white; display: flex; align-items: center; gap: 1rem; font-size: 1.3rem; margin-bottom: 2rem;">
                    <i class="fas fa-file-alt" style="color: var(--brand-primary);"></i> Tese do Projeto
                </h3>
                <div style="line-height: 1.8; color: var(--text-muted); font-size: 1.05rem; white-space: pre-wrap;">
                    <?php echo htmlspecialchars($project['description']); ?>
                </div>
            </div>

            <!-- Estágio e Necessidades -->
            <div class="glass" style="padding: 3rem; border-radius: 32px; background: rgba(13, 22, 40, 0.6); border: 1px solid rgba(59, 130, 246, 0.05);">
                <h3 style="margin-top: 0; color: white; display: flex; align-items: center; gap: 1rem; font-size: 1.3rem; margin-bottom: 2rem;">
                    <i class="fas fa-rocket" style="color: var(--brand-blue);"></i> Plano de Execução
                </h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem;">
                    <div>
                        <label style="display:block; font-size:0.65rem; font-weight:900; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.8rem; letter-spacing:1px;">FASE DE DESENVOLVIMENTO</label>
                        <div style="font-weight: 800; font-size: 1.2rem; color: white;">
                            <i class="fas fa-layer-group" style="color: var(--brand-blue); margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($project['project_stage'] ?: 'PROJECTO INICIAL'); ?>
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.65rem; font-weight:900; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.8rem; letter-spacing:1px;">CAPITAL HUMANO</label>
                        <div style="font-weight: 800; font-size: 1.2rem; color: white;">
                            <i class="fas fa-users" style="color: var(--brand-blue); margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($project['team_size'] ?: '1'); ?> ESPECIALISTA(S)
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.65rem; font-weight:900; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.8rem; letter-spacing:1px;">TEMPO DE EXECUÇÃO ESTIMADO</label>
                        <div style="font-weight: 800; font-size: 1.2rem; color: white;">
                            <i class="fas fa-history" style="color: var(--brand-blue); margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($project['execution_time'] ?: 'A DEFINIR'); ?>
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.65rem; font-weight:900; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.8rem; letter-spacing:1px;">PÚBLICO-ALVO / MERCADO</label>
                        <div style="font-weight: 800; font-size: 1.2rem; color: white;">
                            <i class="fas fa-bullseye" style="color: var(--brand-blue); margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($project['target_audience'] ?: 'GERAL'); ?>
                        </div>
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display:block; font-size:0.65rem; font-weight:900; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.8rem; letter-spacing:1px;">OBSTÁCULOS CRÍTICOS</label>
                        <div style="padding: 1.5rem; background: rgba(239, 68, 68, 0.05); border: 1px dashed rgba(239, 68, 68, 0.2); border-radius: 16px; color: #fecaca; font-size: 0.95rem; font-weight: 600; line-height: 1.4;">
                            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($project['needs_to_advance'] ?: 'Necessita de capital para validação de mercado e equipa técnica.'); ?>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

            <!-- Origem e Motivação -->
            <div class="glass" style="padding: 3rem; border-radius: 32px; background: rgba(13, 22, 40, 0.6); border: 1px solid rgba(16, 185, 129, 0.05);">
                <h3 style="margin-top: 0; color: white; display: flex; align-items: center; gap: 1rem; font-size: 1.3rem; margin-bottom: 2rem;">
                    <i class="fas fa-seedling" style="color: #10b981;"></i> Génese do projecto
                </h3>
                <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
                    <div>
                        <label style="display:block; font-size:0.65rem; font-weight:900; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.8rem; letter-spacing:1px;">COMO TUDO COMEÇOU</label>
                        <div style="line-height: 1.6; color: var(--text-muted); font-size: 1rem; white-space: pre-wrap; font-style: italic;">
                            "<?php echo htmlspecialchars($project['idea_origin'] ?: 'O empreendedor ainda não detalhou a origem desta projecto.'); ?>"
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.65rem; font-weight:900; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.8rem; letter-spacing:1px;">PORQUÊ ESTE FUNDADOR?</label>
                        <div style="line-height: 1.6; color: var(--text-muted); font-size: 1rem; white-space: pre-wrap;">
                            <?php echo htmlspecialchars($project['motivation'] ?: 'Não detalhado.'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Coluna Direita: Equipa e Financeiro -->
        <div style="display: flex; flex-direction: column; gap: 2.5rem;">
            
            <!-- Card de Perfil do Empreendedor -->
            <div class="glass" style="padding: 2.5rem; border-radius: 32px; background: rgba(30, 41, 59, 0.3); text-align: center; border: 1px solid var(--surface-5);">
                <div style="width: 100px; height: 100px; margin: 0 auto 1.5rem; position: relative; cursor: pointer;" onclick="openUserCard(<?php echo $project['owner_id']; ?>)">
                    <img src="<?php echo $base_url . getUserAvatarUrl($project['owner_type'] ?? 'student', $project['mentorship_status'] ?? 'unsubmitted', $project['profile_pic'] ?? $project['owner_pic'] ?? ''); ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid var(--brand-primary); box-shadow: 0 0 30px rgba(247, 148, 29, 0.2);">
                    <?php if(($project['is_verified'] ?? 0)): ?>
                    <div style="position: absolute; bottom: 0; right: 0; background: var(--brand-blue); color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #0d1628;">
                        <i class="fas fa-check" style="font-size: 0.8rem;"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <h3 style="margin: 0; font-size: 1.2rem; color: white; font-weight: 800; cursor: pointer;" onclick="openUserCard(<?php echo $project['owner_id']; ?>)"><?php echo htmlspecialchars($project['full_name']); ?></h3>
                <p style="color: var(--brand-primary); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; margin: 8px 0 20px; letter-spacing: 1px;">Empreendedor KALIYE</p>
                
                <a href="profile.php?user_id=<?php echo $project['owner_id']; ?>" class="btn-primary" style="width: 100%; height: 50px; background: var(--surface-5); color: white; border-radius: 14px; border: 1px solid var(--surface-10); display: flex; align-items: center; justify-content: center; font-weight: 700; text-decoration: none; transition: 0.3s;" onmouseover="this.style.background='var(--surface-10)'">
                    VER PERFIL COMPLETO
                </a>
            </div>

            <!-- Dados Estratégicos Financeiros -->
            <div class="glass" style="padding: 2.5rem; border-radius: 32px; background: #0d1628; border: 1px solid var(--surface-5);">
                <h4 style="margin: 0 0 1.5rem; color: var(--brand-primary); font-size: 0.8rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1.5px;">ESTRUTURA DE NEGÓCIO</h4>
                <div style="display: flex; flex-direction: column; gap: 1.2rem;">

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid var(--surface-5);">
                        <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">INVESTIMENTO MÍNIMO</span>
                        <span style="color: white; font-weight: 800; font-family: 'Outfit';"><?php echo number_format($project['minimum_investment'] ?: 0, 0, ',', '.'); ?> AKZ</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid var(--surface-5);">
                        <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">MODALIDADE</span>
                        <span style="color: var(--brand-blue); font-weight: 800; text-transform: uppercase; font-size: 0.8rem;">EQUITY / PARTICIPAÇÃO</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid var(--surface-5);">
                        <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">FECHO DA RONDA</span>
                        <span style="color: white; font-weight: 800;"><?php echo $project['campaign_end_date'] ? date('d/m/Y', strtotime($project['campaign_end_date'])) : 'EM NEGOCIAÇÃO'; ?></span>
                    </div>

                    <?php if ($_equity_avail !== null): ?>
                    <!-- GAUGE DE EQUITY -->
                    <div style="padding-bottom: 1rem; border-bottom: 1px solid var(--surface-5);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <span style="color:var(--text-muted); font-size:0.85rem; font-weight:600;">EQUITY DISPONÍVEL</span>
                            <span style="color:#10b981; font-weight:900; font-size:1.1rem; font-family:'Outfit';"><?php echo number_format($_equity_remaining, 1); ?>%</span>
                        </div>
                        <div style="background:var(--surface-5); height:8px; border-radius:4px; overflow:hidden; margin-bottom:6px;">
                            <?php $pct_used = $_equity_avail > 0 ? min(100, ($_equity_committed / $_equity_avail) * 100) : 0; ?>
                            <div style="width:<?php echo $pct_used; ?>%; background:linear-gradient(90deg,#f7941d,#ef4444); height:100%;"></div>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.65rem; font-weight:800; color:var(--text-muted);">
                            <span><?php echo number_format($_equity_committed,1); ?>% comprometido</span>
                            <span>Máx <?php echo number_format($_equity_avail,1); ?>%</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($project['project_url'])): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; padding-top: 1rem; border-top: 1px solid var(--surface-5);">
                        <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">WEBSITE OFICIAL</span>
                        <a href="<?php echo htmlspecialchars($project['project_url']); ?>" target="_blank" style="color: var(--brand-primary); font-weight: 800; text-decoration: none; font-size: 0.85rem;">
                            VISITAR <i class="fas fa-external-link-alt" style="margin-left:5px; font-size:0.7rem;"></i>
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Votos da Comunidade -->
                    <?php if ($_vote_count > 0 || $_can_vote): ?>
                    <div style="padding-top:1rem; border-top:1px solid var(--surface-5); display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-muted); font-size:0.85rem; font-weight:600;">ENDOSSOS DA COMUNIDADE</span>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="color:#facc15; font-weight:900; font-family:'Outfit'; font-size:1rem;"><?php echo $_vote_count; ?></span>
                            <i class="fas fa-star" style="color:#facc15; font-size:0.8rem;"></i>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
    <?php endif; // fim do bloco de acesso 'summary' ?>
</div>
<?php // Componente de Detalhes Finalizado ?>
