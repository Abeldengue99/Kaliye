<?php

$_role = $user_data['user_type'] ?? ($_SESSION['user_type'] ?? 'student');
$_m_status = $user_data['mentorship_status'] ?? ($_SESSION['mentorship_status'] ?? 'unsubmitted');
$_is_student = (strpos($_role, 'student') !== false);
$_is_admin = (in_array($_role, ['admin', 'superadmin']));
$_is_mentor = ($_role === 'mentor' || ($_is_student && $_m_status === 'approved') || $_is_admin);
$_is_investor = ($_role === 'investor');
?>
<div class="quick-shortcuts">

    <?php if ($_is_investor): ?>
        <!-- ATALHOS ESPECÍFICOS PARA INVESTIDOR -->
        <a href="<?php echo $base_url; ?>paginas/plataforma/investor_dashboard.php" class="shortcut-card glass">
            <div class="icon-box" style="background: rgba(247,148,29,0.1); color: #f7941d;">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Pipeline</span>
                <span class="card-caption">Dashboard de investimento</span>
            </div>
        </a>

        <a href="<?php echo $base_url; ?>index.php#projectFeedContainer" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(16,185,129,0.1); color: #10b981;">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Explorar Projectos</span>
                <span class="card-caption">Ver oportunidades</span>
            </div>
        </a>

        <a href="<?php echo $base_url; ?>paginas/explorar/liked_projects.php" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                <i class="fas fa-heart"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Favoritos</span>
                <span class="card-caption">Projectos guardados</span>
            </div>
        </a>

        <a href="<?php echo $base_url; ?>paginas/explorar/project_analytics.php" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(139,92,246,0.1); color: #8b5cf6;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Radar de Mercado</span>
                <span class="card-caption">Análises e tendências</span>
            </div>
        </a>


        <a href="<?php echo $base_url; ?>paginas/social/profile.php" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.6);">
                <i class="fas fa-user-cog"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Meu Perfil</span>
                <span class="card-caption">Configurações e verificação</span>
            </div>
        </a>

    <?php else: ?>
        <!-- ATALHOS PARA OUTROS PERFIS (Estudante, Mentor, Admin) -->
        <a href="<?php echo $base_url; ?>index.php#projectFeedContainer" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(247,148,29,0.1); color: #f7941d;">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Projectos</span>
                <span class="card-caption">Explorar ecossistema</span>
            </div>
        </a>

        <?php if (!$_is_mentor): ?>
        <a href="javascript:void(0)" class="shortcut-card glass" onclick="if(!enforceKYC()) return false; openPostModal()">
            <div class="icon-box" style="background: rgba(16,185,129,0.1); color: #10b981;">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Novo Projecto</span>
                <span class="card-caption">Publicar novo projecto</span>
            </div>
        </a>
        <?php endif; ?>

        <a href="<?php echo $base_url; ?>paginas/explorar/my_projects.php" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                <i class="fas fa-folder-open"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Meus Projectos</span>
                <span class="card-caption">Gestão pessoal</span>
            </div>
        </a>

        <a href="<?php echo $base_url; ?>paginas/explorar/project_analytics.php" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(139,92,246,0.1); color: #8b5cf6;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Análise de Impacto</span>
                <span class="card-caption">Dados e resultados</span>
            </div>
        </a>

        <a href="<?php echo $base_url; ?>paginas/explorar/explore_mentors.php" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(247,148,29,0.1); color: #f7941d;">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Encontrar Mentores</span>
                <span class="card-caption">Apoio especializado</span>
            </div>
        </a>

        <?php if ($_is_mentor || $_is_admin): ?>
        <a href="<?php echo $base_url; ?>paginas/explorar/explore_students.php" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(16,185,129,0.1); color: #10b981;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Estudantes</span>
                <span class="card-caption">Apoio académico</span>
            </div>
        </a>
        <?php endif; ?>

        <a href="<?php echo $base_url; ?>paginas/mentoria/mentorship.php" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Meu Plano de Mentoria</span>
                <span class="card-caption">Sessões e tarefas</span>
            </div>
        </a>

        <?php if (!$_is_student): ?>
        <a href="<?php echo $base_url; ?>paginas/social/profile.php" class="shortcut-card glass" onclick="return enforceKYC(event)">
            <div class="icon-box" style="background: rgba(247,148,29,0.1); color: #f7941d;">
                <i class="fas fa-certificate"></i>
            </div>
            <div class="card-info">
                <span class="card-title">Quero ser Mentor</span>
                <span class="card-caption">Certificação KALIYE</span>
            </div>
        </a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
/* Ajuste fino para os ícones coloridos nos atalhos */
.quick-shortcuts .icon-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.shortcut-card:hover .icon-box {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 0 20px currentColor;
}
</style>
