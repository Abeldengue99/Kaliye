<!-- Modais Centrais Premium: Explorar, Mentoria & Perfil (Aksanti Elite Designer Edition) -->

<!-- 1. MODAL EXPLORAR -->
<div id="explorarModal" class="elite-center-modal-overlay" style="display: none;" onclick="closeExplorarModal(event)">
    <div class="elite-center-modal-card command-modal-card explorar-command-card" onclick="event.stopPropagation()">
        <!-- Botão Fechar -->
        <button type="button" class="elite-center-modal-close" onclick="closeExplorarModal()">
            <i class="fas fa-times"></i>
        </button>

        <div class="elite-center-modal-header">
            <div class="header-icon-badge">
                <i class="fas fa-compass"></i>
            </div>
            <div>
                <h2>Explorar Ecossistema</h2>
                <p>Navegue pelas oportunidades, projectos inovadores e ferramentas de mercado da KALIYE.</p>
            </div>
        </div>

        <div class="elite-center-modal-grid">
            <!-- Coluna 1: Descoberta -->
            <div class="modal-grid-col">
                <div class="col-title-box">
                    <i class="fas fa-rocket text-glow-orange"></i>
                    <h3>Hub de Projectos</h3>
                </div>
                <div class="col-links-list">
                    <a href="<?php echo $base_url; ?>index.php#projectFeedContainer" onclick="closeExplorarModal()" class="modal-link-card">
                        <div class="link-card-icon"><i class="fas fa-search"></i></div>
                        <div class="link-card-body">
                            <strong>Explorar Feed</strong>
                            <span>Descubra projectos inovadores e pitches ativos</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-indicator"></i>
                    </a>
                    
                    <a href="<?php echo $base_url; ?>paginas/explorar/liked_projects.php" onclick="closeExplorarModal()" class="modal-link-card">
                        <div class="link-card-icon"><i class="fas fa-heart"></i></div>
                        <div class="link-card-body">
                            <strong>Projectos Favoritos</strong>
                            <span>Projectos guardados e que acompanha com interesse</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-indicator"></i>
                    </a>

                    <?php if ($is_student): ?>
                        <a href="javascript:void(0)" onclick="closeExplorarModal(); window.openPostModal();" class="modal-link-card highlight-glow-blue">
                            <div class="link-card-icon"><i class="fas fa-plus-circle"></i></div>
                            <div class="link-card-body">
                                <strong>Criar Novo Projecto</strong>
                                <span>Publique a sua visão no ecossistema hoje</span>
                            </div>
                            <i class="fas fa-plus arrow-indicator"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Coluna 2: Gestão / Portfólio -->
            <div class="modal-grid-col">
                <?php if ($is_investor): ?>
                    <div class="col-title-box">
                        <i class="fas fa-briefcase text-glow-blue"></i>
                        <h3>Portfólio do Investidor</h3>
                    </div>
                    <div class="col-links-list">
                        <?php 
                        $is_approved_investor = ($is_verified && ($user_data['investor_status'] ?? $_SESSION['investor_status'] ?? '') === 'approved');
                        if ($is_approved_investor): 
                        ?>
                            <a href="<?php echo $base_url; ?>paginas/plataforma/investor_dashboard.php?view=my_investments" onclick="closeExplorarModal()" class="modal-link-card">
                                <div class="link-card-icon"><i class="fas fa-file-signature"></i></div>
                                <div class="link-card-body">
                                    <strong>Minhas Propostas</strong>
                                    <span>Acompanhe o estado das propostas que enviou</span>
                                </div>
                                <i class="fas fa-chevron-right arrow-indicator"></i>
                            </a>
                        <?php else: ?>
                            <div class="modal-link-card" style="opacity: 0.5; cursor: not-allowed;" title="Aguardando aprovação de perfil">
                                <div class="link-card-icon"><i class="fas fa-lock"></i></div>
                                <div class="link-card-body">
                                    <strong>Minhas Propostas</strong>
                                    <span>Funcionalidade bloqueada (Valide o perfil)</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($is_mentor): ?>
                    <div class="col-title-box" style="margin-top: 1.5rem;">
                        <i class="fas fa-chalkboard-teacher text-glow-blue"></i>
                        <h3>Portfólio do Mentor</h3>
                    </div>
                    <div class="col-links-list">
                        <?php 
                        $is_approved_mentor = ($is_verified && ($user_data['mentorship_status'] ?? $_SESSION['mentorship_status'] ?? '') === 'approved');
                        if ($is_approved_mentor): 
                        ?>
                            <a href="<?php echo $base_url; ?>paginas/plataforma/mentor_dashboard.php?view=my_proposals" onclick="closeExplorarModal()" class="modal-link-card">
                                <div class="link-card-icon"><i class="fas fa-file-signature"></i></div>
                                <div class="link-card-body">
                                    <strong>Minhas Propostas</strong>
                                    <span>Acompanhe as suas candidaturas a projetos</span>
                                </div>
                                <i class="fas fa-chevron-right arrow-indicator"></i>
                            </a>
                        <?php else: ?>
                            <div class="modal-link-card" style="opacity: 0.5; cursor: not-allowed;" title="Aguardando aprovação de perfil">
                                <div class="link-card-icon"><i class="fas fa-lock"></i></div>
                                <div class="link-card-body">
                                    <strong>Minhas Propostas</strong>
                                    <span>Funcionalidade bloqueada (Valide o perfil)</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($is_student || $is_investor): ?>
                    <div class="col-title-box" <?php echo ($is_investor) ? 'style="margin-top: 1.5rem;"' : ''; ?>>
                        <i class="fas fa-tasks text-glow-blue"></i>
                        <h3>Planeamento & Gestão</h3>
                    </div>
                    <div class="col-links-list">
                        <a href="<?php echo $base_url; ?>paginas/explorar/my_projects.php" onclick="closeExplorarModal()" class="modal-link-card">
                            <div class="link-card-icon"><i class="fas fa-folder-open"></i></div>
                            <div class="link-card-body">
                                <strong>Meus Projectos</strong>
                                <span>Faça a gestão dos seus pitches e rascunhos</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                        
                        <a href="<?php echo $base_url; ?>paginas/explorar/project_analytics.php" onclick="closeExplorarModal()" class="modal-link-card">
                            <div class="link-card-icon"><i class="fas fa-chart-bar"></i></div>
                            <div class="link-card-body">
                                <strong>Impacto & Analytics</strong>
                                <span>Métricas de visualização e alcance do projecto</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Coluna 3: Rede & Social -->
            <div class="modal-grid-col">
                <div class="col-title-box">
                    <i class="fas fa-users-cog text-glow-emerald"></i>
                    <h3>Ecossistema</h3>
                </div>
                <div class="col-links-list">
                    <a href="<?php echo $base_url; ?>paginas/explorar/doubts.php" onclick="closeExplorarModal()" class="modal-link-card">
                        <div class="link-card-icon"><i class="fas fa-comments"></i></div>
                        <div class="link-card-body">
                            <strong>Dúvidas da Comunidade</strong>
                            <span>Esclareça tópicos e responda a fóruns</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-indicator"></i>
                    </a>

                    <a href="javascript:void(0)" onclick="closeExplorarModal(); Swal.fire({title: 'Em Breve', text: 'A carteira digital e gestão de saldos estarão disponíveis na Fase 2 do projeto KALIYE.', icon: 'info', background: '#0d1628', color: '#fff'});" class="modal-link-card">
                        <div class="link-card-icon" style="opacity: 0.5;"><i class="fas fa-wallet"></i></div>
                        <div class="link-card-body" style="opacity: 0.5;">
                            <strong>Saldo & Transações</strong>
                            <span>Aceda à sua Carteira Digital (Bloqueado)</span>
                        </div>
                        <i class="fas fa-lock arrow-indicator" style="opacity: 0.5;"></i>
                    </a>

                    <?php if ($is_admin): ?>
                        <a href="<?php echo $base_url; ?>administracao/moderation/moderation.php" onclick="closeExplorarModal()" class="modal-link-card highlight-glow-orange">
                            <div class="link-card-icon"><i class="fas fa-shield-alt"></i></div>
                            <div class="link-card-body">
                                <strong>Moderar Conteúdo</strong>
                                <span>Painel de Moderação KALIYE Hub</span>
                            </div>
                            <i class="fas fa-shield-halved arrow-indicator"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- 2. MODAL MENTORIA -->
<div id="mentoriaModal" class="elite-center-modal-overlay" style="display: none;" onclick="closeMentoriaModal(event)">
    <div class="elite-center-modal-card command-modal-card mentoria-command-card" onclick="event.stopPropagation()">
        <!-- Botão Fechar -->
        <button type="button" class="elite-center-modal-close" onclick="closeMentoriaModal()">
            <i class="fas fa-times"></i>
        </button>

        <div class="elite-center-modal-header">
            <div class="header-icon-badge">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div>
                <h2>Central de Mentoria</h2>
                <p>Educação de alto rendimento, sessões de acompanhamento e rede profissional.</p>
            </div>
        </div>

        <div class="elite-center-modal-grid">
            <!-- Coluna 1: Directório / Rede -->
            <div class="modal-grid-col">
                <div class="col-title-box">
                    <i class="fas fa-users text-glow-orange"></i>
                    <h3>Directório & Rede</h3>
                </div>
                <div class="col-links-list">
                    <?php if ($is_admin || $is_investor): ?>
                        <a href="<?php echo $base_url; ?>paginas/explorar/explore_mentors.php" onclick="closeMentoriaModal()" class="modal-link-card">
                            <div class="link-card-icon"><i class="fas fa-user-tie"></i></div>
                            <div class="link-card-body">
                                <strong>Directório de Mentores</strong>
                                <span>Lista oficial de mentores e assessores</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                        <a href="<?php echo $base_url; ?>paginas/explorar/explore_students.php" onclick="closeMentoriaModal()" class="modal-link-card">
                            <div class="link-card-icon"><i class="fas fa-user-graduate"></i></div>
                            <div class="link-card-body">
                                <strong>Rede de Estudantes</strong>
                                <span>Explore talentos e novos pesquisadores</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                    <?php elseif ($is_mentor): ?>
                        <a href="<?php echo $base_url; ?>paginas/explorar/explore_students.php" onclick="closeMentoriaModal()" class="modal-link-card">
                            <div class="link-card-icon"><i class="fas fa-street-view"></i></div>
                            <div class="link-card-body">
                                <strong>Rede de Talentos</strong>
                                <span>Conecte-se com mentes académicas ativas</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $base_url; ?>paginas/explorar/explore_mentors.php" onclick="closeMentoriaModal()" class="modal-link-card">
                            <div class="link-card-icon"><i class="fas fa-user-tie"></i></div>
                            <div class="link-card-body">
                                <strong>Rede de Mentores</strong>
                                <span>Encontre o tutor ideal para a sua jornada</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                        <a href="<?php echo $base_url; ?>paginas/explorar/explore_students.php" onclick="closeMentoriaModal()" class="modal-link-card highlight-glow-blue">
                            <div class="link-card-icon"><i class="fas fa-user-graduate"></i></div>
                            <div class="link-card-body">
                                <strong>Rede de Talentos</strong>
                                <span>Apenas estudantes cadastrados na plataforma</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Coluna 2: Minha Actividade -->
            <div class="modal-grid-col">
                <div class="col-title-box">
                    <i class="fas fa-calendar-check text-glow-blue"></i>
                    <h3>Minha Atividade</h3>
                </div>
                <div class="col-links-list">
                    <a href="<?php echo $base_url; ?>paginas/mentoria/mentorship.php" onclick="closeMentoriaModal()" class="modal-link-card">
                        <div class="link-card-icon"><i class="fas fa-handshake"></i></div>
                        <div class="link-card-body">
                            <strong>Gestão de Mentorias</strong>
                            <span>Acompanhe as suas sessões e pedidos ativos</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-indicator"></i>
                    </a>
                    
                    <a href="<?php echo $base_url; ?>paginas/mentoria/meeting.php" onclick="closeMentoriaModal()" class="modal-link-card">
                        <div class="link-card-icon"><i class="fas fa-video"></i></div>
                        <div class="link-card-body">
                            <strong>Sessões & Conferências</strong>
                            <span>Aceda à sala de aula ou chamada de vídeo</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-indicator"></i>
                    </a>

                    <a href="<?php echo $base_url; ?>paginas/mentoria/free_mentorship_requests.php" onclick="closeMentoriaModal()" class="modal-link-card">
                        <div class="link-card-icon"><i class="fas fa-bolt"></i></div>
                        <div class="link-card-body">
                            <strong>Mentoria Rápida</strong>
                            <span>Esclarecimento rápido de dúvidas académicas</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-indicator"></i>
                    </a>
                </div>
            </div>

            <!-- Coluna 3: Profissional -->
            <div class="modal-grid-col">
                <div class="col-title-box">
                    <i class="fas fa-gem text-glow-emerald"></i>
                    <h3>Área Profissional</h3>
                </div>
                <div class="col-links-list">
                    <?php if ($is_mentor): ?>
                        <a href="<?php echo $base_url; ?>paginas/mentoria/free_mentorship_requests.php" onclick="closeMentoriaModal()" class="modal-link-card">
                            <div class="link-card-icon"><i class="fas fa-inbox"></i></div>
                            <div class="link-card-body">
                                <strong>Pedidos de Estudantes</strong>
                                <span>Pedidos de ajuda urgentes ou estruturados</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                    <?php elseif ($is_student && !in_array($_SESSION['mentor_status'] ?? 'unsubmitted', ['approved', 'pending', 'under_review', 'shortlisted'])): ?>
                        <a href="javascript:void(0)" onclick="closeMentoriaModal(); openMentorAppModal();" class="modal-link-card highlight-glow-orange">
                            <div class="link-card-icon"><i class="fas fa-crown"></i></div>
                            <div class="link-card-body">
                                <strong>SER MENTOR OFICIAL</strong>
                                <span>Partilhe saberes e impulsione a sua carreira</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- 3. MODAL PERFIL/UTILIZADOR -->
<div id="perfilMenuModal" class="elite-center-modal-overlay" style="display: none;" onclick="closePerfilMenuModal(event)">
    <?php
        $__labels = isset($user_type_labels) ? $user_type_labels : [];
        $perfil_user_type = $user_data['user_type'] ?? 'student';
        $perfil_display_name = str_ireplace('Aksanti', 'KALIYE', $user_data['full_name'] ?? 'Membro KALIYE');
        $perfil_role_label = $__labels[$perfil_user_type] ?? ucfirst($perfil_user_type);
        $perfil_status = $_SESSION['verification_status'] ?? ($user_data['verification_status'] ?? 'unsubmitted');
        $perfil_is_verified = in_array($perfil_status, ['verified', 'approved'], true) || !empty($_SESSION['is_verified']);
    ?>
    <div class="elite-center-modal-card command-modal-card perfil-command-card" onclick="event.stopPropagation()">
        <!-- Botão Fechar -->
        <button type="button" class="elite-center-modal-close" onclick="closePerfilMenuModal()">
            <i class="fas fa-times"></i>
        </button>

        <div class="elite-center-modal-header perfil-command-hero">
            <div class="profile-hero-ambient"></div>
            <div class="header-icon-badge perfil-avatar-orbit">
                <img src="<?php echo $base_url . htmlspecialchars($display_pic); ?>" alt="Avatar" loading="eager" decoding="async" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div class="perfil-hero-copy">
                <span class="perfil-eyebrow">Centro de comando</span>
                <h2><?php echo htmlspecialchars($perfil_display_name); ?></h2>
                <div class="perfil-status-row">
                    <span class="perfil-chip perfil-chip-role"><i class="fas fa-crown"></i> <?php echo htmlspecialchars($perfil_role_label); ?></span>
                    <span class="perfil-chip <?php echo $perfil_is_verified ? 'perfil-chip-ok' : 'perfil-chip-wait'; ?>">
                        <i class="fas <?php echo $perfil_is_verified ? 'fa-shield-alt' : 'fa-clock'; ?>"></i>
                        <?php echo $perfil_is_verified ? 'Verificado' : 'Verificacao pendente'; ?>
                    </span>
                </div>
            </div>
            <div class="perfil-session-meter" aria-hidden="true">
                <span></span><span></span><span></span>
            </div>
        </div>

        <div class="perfil-signal-strip">
            <div><strong><?php echo $perfil_is_verified ? 'Ativo' : 'Pendente'; ?></strong><span>Confianca</span></div>
            <div><strong>KALIYE</strong><span>Ecossistema</span></div>
            <div><strong><?php echo date('Y'); ?></strong><span>Sessão</span></div>
        </div>

        <div class="elite-center-modal-grid">
            <!-- Coluna 1: Dossier de Identidade -->
            <div class="modal-grid-col">
                <div class="col-title-box">
                    <i class="fas fa-user-circle text-glow-orange"></i>
                    <h3>Dossier de Identidade</h3>
                </div>
                <div class="col-links-list">
                    <a href="javascript:void(0)" onclick="closePerfilMenuModal(); openMyProfileEdit();" class="modal-link-card highlight-glow-orange">
                        <div class="link-card-icon"><i class="fas fa-id-card"></i></div>
                        <div class="link-card-body">
                            <strong>O Meu Perfil</strong>
                            <span>Atualize os seus dados e dossier de competências</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-indicator"></i>
                    </a>
                    
                    <?php if ($perfil_is_verified || (isset($user_data['user_type']) && in_array($user_data['user_type'], ['admin', 'superadmin']))): ?>
                        <a href="<?php echo $base_url; ?>paginas/social/rede.php" onclick="closePerfilMenuModal()" class="modal-link-card highlight-glow-blue">
                            <div class="link-card-icon"><i class="fas fa-users"></i></div>
                            <div class="link-card-body">
                                <strong>A Minha Rede</strong>
                                <span>Faça a gestão das suas conexões e pedidos pendentes</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                    <?php else: ?>
                        <a href="javascript:void(0)" onclick="closePerfilMenuModal(); if(typeof openKYCModal === 'function') { openKYCModal(); } else { window.location.href='<?php echo $base_url; ?>index.php?kyc_required=1'; }" class="modal-link-card highlight-glow-blue" style="opacity: 0.6;">
                            <div class="link-card-icon"><i class="fas fa-users"></i></div>
                            <div class="link-card-body">
                                <strong>A Minha Rede</strong>
                                <span>Conta precisa ser verificada para aceder à rede</span>
                            </div>
                            <i class="fas fa-lock arrow-indicator"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Coluna 2: Finanças & Consola -->
            <div class="modal-grid-col">
                <div class="col-title-box">
                    <i class="fas fa-wallet text-glow-blue"></i>
                    <h3>Finanças & Gestão</h3>
                </div>
                <div class="col-links-list">
                    <a href="javascript:void(0)" onclick="closePerfilMenuModal(); showWalletDevMessage(event);" class="modal-link-card">
                        <div class="link-card-icon"><i class="fas fa-wallet"></i></div>
                        <div class="link-card-body">
                            <strong>Carteira Digital</strong>
                            <span>Aceda ao seu saldo e transações (Em Breve)</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-indicator"></i>
                    </a>

                    <?php if (isset($user_data['user_type']) && ($user_data['user_type'] == 'admin' || $user_data['user_type'] == 'superadmin')): ?>
                        <a href="<?php echo $base_url; ?>administracao/index.php" onclick="closePerfilMenuModal()" class="modal-link-card highlight-glow-blue">
                            <div class="link-card-icon"><i class="fas fa-shield-alt"></i></div>
                            <div class="link-card-body">
                                <strong>Painel Administrador</strong>
                                <span>Consola administrativa e moderação global</span>
                            </div>
                            <i class="fas fa-chevron-right arrow-indicator"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Coluna 3: Sessão Ativa -->
            <div class="modal-grid-col">
                <div class="col-title-box">
                    <i class="fas fa-power-off text-glow-emerald"></i>
                    <h3>Sessão Ativa</h3>
                </div>
                <div class="col-links-list">
                    <a href="<?php echo $base_url; ?>autenticacao/sair.php" onclick="closePerfilMenuModal()" class="modal-link-card hover-glow-red" style="border-color: rgba(239, 68, 68, 0.15); background: rgba(239, 68, 68, 0.02);">
                        <div class="link-card-icon" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); color: #ef4444;"><i class="fas fa-sign-out-alt"></i></div>
                        <div class="link-card-body">
                            <strong style="color: #ef4444;">Finalizar Sessão</strong>
                            <span>Desconecte-se com segurança do ecossistema</span>
                        </div>
                        <i class="fas fa-sign-out-alt arrow-indicator" style="color: #ef4444;"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
/* ==========================================================================
   ESTILOS PREMIUM: MODAIS CENTRAIS NAVBAR (EXPLORAR, MENTORIA & PERFIL)
   ========================================================================== */

.elite-center-modal-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(2, 6, 23, 0.75);
    backdrop-filter: blur(25px) saturate(180%);
    -webkit-backdrop-filter: blur(25px) saturate(180%);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    opacity: 0;
    transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.elite-center-modal-overlay.active {
    opacity: 1;
}

.elite-center-modal-card {
    background: rgba(13, 22, 40, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 28px;
    width: 100%;
    max-width: 1100px;
    padding: 2.5rem;
    position: relative;
    box-shadow: 0 40px 100px rgba(0, 0, 0, 0.7),
                inset 0 1px 1px rgba(255, 255, 255, 0.1);
    transform: scale(0.92) translateY(20px);
    transition: transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.elite-center-modal-overlay.active .elite-center-modal-card {
    transform: scale(1) translateY(0);
}

.command-modal-card {
    width: min(960px, calc(100vw - 48px));
    max-width: 960px;
    padding: 1.45rem !important;
    background:
        linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(8, 16, 31, 0.92)),
        radial-gradient(circle at 14% 18%, rgba(247, 148, 29, 0.16), transparent 28%),
        radial-gradient(circle at 88% 12%, rgba(59, 130, 246, 0.14), transparent 30%) !important;
    overflow: hidden;
}

.command-modal-card:not(.perfil-command-card) .elite-center-modal-header {
    gap: 1rem;
    margin-bottom: 1.15rem;
    padding-bottom: 1rem;
}

.command-modal-card:not(.perfil-command-card) .header-icon-badge {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    font-size: 1.45rem;
}

.command-modal-card:not(.perfil-command-card) .elite-center-modal-header p {
    font-size: 0.82rem;
    line-height: 1.35;
}

.perfil-command-card {
    width: min(960px, calc(100vw - 48px));
    max-width: 960px;
    padding: 1.45rem !important;
}

.perfil-command-card::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.035) 1px, transparent 1px);
    background-size: 42px 42px;
    mask-image: linear-gradient(to bottom, rgba(0,0,0,0.72), transparent 62%);
}

.perfil-command-hero {
    position: relative;
    margin: -0.25rem -0.25rem 0.9rem;
    padding: 1rem 1.15rem 1.1rem !important;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(255,255,255,0.055), rgba(255,255,255,0.015));
    overflow: hidden;
}

.profile-hero-ambient {
    position: absolute;
    inset: auto 8% -80px auto;
    width: 320px;
    height: 180px;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(247,148,29,0.22), rgba(59,130,246,0.18));
    filter: blur(45px);
    pointer-events: none;
}

.perfil-avatar-orbit {
    position: relative;
    width: 62px !important;
    height: 62px !important;
    padding: 0 !important;
    border-radius: 18px !important;
    overflow: hidden;
    border: 1px solid rgba(247,148,29,0.85) !important;
    box-shadow: 0 0 0 5px rgba(247,148,29,0.08), 0 14px 30px rgba(247,148,29,0.18) !important;
}

.perfil-hero-copy {
    position: relative;
    min-width: 0;
    flex: 1;
}

.perfil-eyebrow {
    display: inline-flex;
    color: rgba(255,255,255,0.42);
    font-size: 0.62rem;
    font-weight: 900;
    letter-spacing: 1.6px;
    text-transform: uppercase;
    margin-bottom: 0.25rem;
}

.perfil-status-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin-top: 0.5rem;
}

.perfil-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    min-height: 26px;
    padding: 0.28rem 0.6rem;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.045);
    color: rgba(255,255,255,0.74);
    font-size: 0.62rem;
    font-weight: 900;
    letter-spacing: 0.8px;
    text-transform: uppercase;
}

.perfil-chip-role {
    border-color: rgba(247,148,29,0.28);
    color: #f7941d;
}

.perfil-chip-ok {
    border-color: rgba(16,185,129,0.28);
    color: #34d399;
}

.perfil-chip-wait {
    border-color: rgba(251,191,36,0.26);
    color: #fbbf24;
}

.perfil-session-meter {
    position: relative;
    display: flex;
    gap: 0.35rem;
    align-self: flex-start;
    margin-right: 2.5rem;
}

.perfil-session-meter span {
    width: 8px;
    border-radius: 999px;
    background: linear-gradient(to top, rgba(247,148,29,0.2), #f7941d);
}

.perfil-session-meter span:nth-child(1) { height: 18px; opacity: 0.5; }
.perfil-session-meter span:nth-child(2) { height: 28px; opacity: 0.78; }
.perfil-session-meter span:nth-child(3) { height: 38px; }

.perfil-signal-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.7rem;
    margin-bottom: 1rem;
}

.perfil-signal-strip div {
    padding: 0.65rem 0.8rem;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.07);
    background: rgba(255,255,255,0.025);
}

.perfil-signal-strip strong,
.perfil-signal-strip span {
    display: block;
}

.perfil-signal-strip strong {
    color: #fff;
    font-size: 0.82rem;
    font-weight: 900;
}

.perfil-signal-strip span {
    color: rgba(255,255,255,0.38);
    font-size: 0.6rem;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-top: 0.18rem;
}

/* Botão Fechar */
.elite-center-modal-close {
    position: absolute;
    top: 1.5rem; right: 1.5rem;
    width: 40px; height: 40px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 50%;
    color: rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.1rem;
    transition: all 0.3s;
    outline: none;
}

.elite-center-modal-close:hover {
    background: #ef4444;
    border-color: #f87171;
    color: #fff;
    box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
    transform: rotate(90deg);
}

/* Cabeçalho */
.elite-center-modal-header {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-bottom: 2.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    padding-bottom: 1.5rem;
}

.header-icon-badge {
    width: 60px; height: 60px;
    background: rgba(247, 148, 29, 0.1);
    border: 1px solid rgba(247, 148, 29, 0.25);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #f7941d;
    box-shadow: 0 0 20px rgba(247, 148, 29, 0.2);
}

.elite-center-modal-header h2 {
    font-family: 'Outfit', sans-serif;
    color: #fff;
    font-size: 1.75rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.5px;
}

.command-modal-card .elite-center-modal-header h2 {
    font-size: 1.45rem;
    letter-spacing: 0;
}

.elite-center-modal-header p {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.95rem;
    margin: 4px 0 0;
    font-weight: 500;
}

/* Grid de Links */
.elite-center-modal-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.command-modal-card .elite-center-modal-grid {
    gap: 1rem;
}

.command-modal-card .modal-grid-col {
    gap: 0.8rem;
}

.command-modal-card .col-title-box {
    gap: 0.6rem;
    padding-bottom: 0.4rem;
}

.command-modal-card .col-title-box i {
    font-size: 1.05rem;
}

.command-modal-card .col-title-box h3 {
    font-size: 0.95rem;
}

.command-modal-card .col-links-list {
    gap: 0.55rem;
}

.command-modal-card .modal-link-card {
    gap: 0.75rem;
    min-height: 76px;
    padding: 0.75rem 0.85rem;
    border-radius: 14px;
}

.command-modal-card .link-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    font-size: 0.95rem;
    flex: 0 0 36px;
}

.command-modal-card .link-card-body strong {
    font-size: 0.78rem;
}

.command-modal-card .link-card-body span {
    font-size: 0.66rem;
}

.command-modal-card .arrow-indicator {
    font-size: 0.72rem;
}

.command-modal-card .elite-center-modal-close {
    top: 1rem;
    right: 1rem;
    width: 34px;
    height: 34px;
    font-size: 0.95rem;
}

.modal-grid-col {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
}

.col-title-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid rgba(255, 255, 255, 0.03);
}

.col-title-box i {
    font-size: 1.25rem;
}

.col-title-box h3 {
    font-family: 'Outfit', sans-serif;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
}

.col-links-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Link Cards */
.modal-link-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    text-decoration: none !important;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
}

.modal-link-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, transparent, rgba(255,255,255,0.075), transparent);
    transform: translateX(-120%);
    transition: transform 0.55s ease;
}

.modal-link-card:hover::before {
    transform: translateX(120%);
}

.link-card-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    color: #94a3b8;
    transition: all 0.3s;
}

.link-card-body {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.link-card-body strong {
    color: #fff;
    font-size: 0.9rem;
    font-weight: 700;
    transition: color 0.3s;
}

.link-card-body span {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.75rem;
    line-height: 1.3;
    transition: color 0.3s;
}

.arrow-indicator {
    color: rgba(255, 255, 255, 0.2);
    font-size: 0.8rem;
    transition: all 0.3s;
}

/* Hover Effects */
.modal-link-card:hover {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(247, 148, 29, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-link-card:hover .link-card-icon {
    background: rgba(247, 148, 29, 0.1);
    border-color: rgba(247, 148, 29, 0.3);
    color: #f7941d;
    box-shadow: 0 0 10px rgba(247, 148, 29, 0.15);
}

.modal-link-card:hover .link-card-body strong {
    color: #f7941d;
}

.modal-link-card:hover .link-card-body span {
    color: rgba(255, 255, 255, 0.6);
}

.modal-link-card:hover .arrow-indicator {
    color: #f7941d;
    transform: translateX(3px);
}

/* Custom Highlight Cards */
.highlight-glow-orange {
    background: rgba(247, 148, 29, 0.04);
    border-color: rgba(247, 148, 29, 0.15);
}
.highlight-glow-orange:hover {
    border-color: rgba(247, 148, 29, 0.4);
    box-shadow: 0 10px 25px rgba(247, 148, 29, 0.1);
}

.highlight-glow-blue {
    background: rgba(59, 130, 246, 0.04);
    border-color: rgba(59, 130, 246, 0.15);
}
.highlight-glow-blue:hover {
    border-color: rgba(59, 130, 246, 0.4);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.1);
}
.highlight-glow-blue:hover .link-card-icon {
    background: rgba(59, 130, 246, 0.1);
    border-color: rgba(59, 130, 246, 0.3);
    color: #3b82f6;
}
.highlight-glow-blue:hover .link-card-body strong {
    color: #3b82f6;
}

/* Custom Exit Button Glow */
.hover-glow-red:hover {
    background: rgba(239, 68, 68, 0.05) !important;
    border-color: rgba(239, 68, 68, 0.4) !important;
    box-shadow: 0 10px 25px rgba(239, 68, 68, 0.15) !important;
}
.hover-glow-red:hover .link-card-icon {
    background: #ef4444 !important;
    border-color: #f87171 !important;
    color: #fff !important;
    box-shadow: 0 0 10px rgba(239, 68, 68, 0.3);
}
.hover-glow-red:hover .link-card-body strong {
    color: #ef4444 !important;
}
.hover-glow-red:hover .arrow-indicator {
    color: #ef4444 !important;
    transform: translateX(3px);
}

/* Neon Text Glow Colors */
.text-glow-orange { color: #f7941d; text-shadow: 0 0 10px rgba(247,148,29,0.3); }
.text-glow-blue { color: #3b82f6; text-shadow: 0 0 10px rgba(59,130,246,0.3); }
.text-glow-emerald { color: #10b981; text-shadow: 0 0 10px rgba(16,185,129,0.3); }

/* ==========================================================================
   RESPONSIVIDADE (MOBILE-ELITE INTEGRATION - PHONE, TABLET & DESKTOP)
   ========================================================================== */

/* Tablets / Medium Screens */
@media (max-width: 992px) {
    .elite-center-modal-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
    .elite-center-modal-card {
        padding: 2.2rem;
        max-width: 800px;
        max-height: 85vh;
        overflow-y: auto;
    }
}

/* Mobile Devices / Small Screens */
@media (max-width: 768px) {
    .elite-center-modal-grid {
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    .elite-center-modal-overlay {
        padding: 1rem;
    }
    .elite-center-modal-card {
        padding: 1.8rem 1.4rem;
        border-radius: 24px;
        max-height: 90vh;
    }
    .elite-center-modal-header {
        margin-bottom: 2rem;
        gap: 1rem;
    }
    .perfil-command-hero {
        align-items: flex-start;
        margin: -0.25rem -0.25rem 1rem;
        padding: 1rem !important;
    }
    .perfil-avatar-orbit {
        width: 58px !important;
        height: 58px !important;
        border-radius: 18px !important;
    }
    .perfil-status-row {
        gap: 0.4rem;
    }
    .perfil-chip {
        font-size: 0.58rem;
        min-height: 26px;
        padding: 0.3rem 0.55rem;
    }
    .perfil-session-meter {
        display: none;
    }
    .perfil-signal-strip {
        grid-template-columns: 1fr;
        gap: 0.55rem;
    }
    .header-icon-badge {
        width: 50px; height: 50px;
        font-size: 1.5rem;
    }
    .elite-center-modal-header h2 {
        font-size: 1.4rem;
    }
    .elite-center-modal-header p {
        font-size: 0.85rem;
    }
}
</style>

<?php include_once __DIR__ . '/mentor_app_modal.php'; ?>

<script>
/* ==========================================================================
   INTERATIVIDADE JAVASCRIPT: CONTROLE DOS MODAIS CENTRAIS
   ========================================================================== */

function openExplorarModal() {
    if (typeof enforceKYC === 'function' && !enforceKYC()) return;
    const modal = document.getElementById('explorarModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.offsetHeight; // force reflow
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Lock scrolling behind
    }
}

function closeExplorarModal(event) {
    const modal = document.getElementById('explorarModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 350);
    }
}

function openMentoriaModal() {
    if (typeof enforceKYC === 'function' && !enforceKYC()) return;
    const modal = document.getElementById('mentoriaModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.offsetHeight;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeMentoriaModal(event) {
    const modal = document.getElementById('mentoriaModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 350);
    }
}

function openPerfilMenuModal() {
    const modal = document.getElementById('perfilMenuModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.offsetHeight; // force reflow
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Lock scrolling behind
    }
}

function closePerfilMenuModal(event) {
    const modal = document.getElementById('perfilMenuModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 350);
    }
}

// Fecho universal no clique Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeExplorarModal();
        closeMentoriaModal();
        closePerfilMenuModal();
    }
});
</script>
