<?php
// dashboard_hero.php - Hero Innovation Hub para o Dashboard
/**
 * @var array $user_data Dados do utilizador logado
 * @var string $greeting_word Saudação (Bom dia/etc)
 * @var int $global_projects
 * @var int $global_mentors
 * @var int $global_students
 * @var int $pending_items
 * @var string $header_user_pic
 * @var string $base_url
 */

$hero_bg_image = $base_url . 'recursos/images/mentorship_dashboard_bg.png';

$type_labels = [
    'univ_student' => 'Estudante Universitário',
    'high_student' => 'Estudante do Ensino Médio',
    'mentor'       => 'Mentor Verificado',
    'investor'     => 'Investidor Ativo',
    'admin'        => 'Administrador'
];
?>
<div class="hero-hub" style="--hero-bg: url('<?php echo $hero_bg_image; ?>');">
    <!-- Decorativos -->
    <div class="hero-grid-pattern"></div>
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>

    <!-- Estrutura interna -->
    <div class="hero-inner">

        <!-- ESQUERDA: Boas-vindas -->
        <div class="hero-left">
            <div class="hero-top-badge" style="margin-bottom: 0.8rem;">
                <span class="badge-dot"></span>
                <?php echo $type_labels[$_SESSION['user_type']] ?? 'Membro'; ?>
            </div>

            <h2 class="hero-title" style="margin: 0;">
                <span class="hero-greeting-word"><?php echo $greeting_word; ?>,</span>
                <span class="hero-name"><?php 
                    $full_name = $user_data['full_name'] ?? 'Membro';
                    echo htmlspecialchars(explode(' ', $full_name)[0]); 
                ?>.</span>
            </h2>

            <p class="hero-subtitle">Transforme projectos em impacto real na KALIYE.</p>
        </div>

        <!-- CENTRO: Hub de Informação e Ação -->
        <div class="hero-center-hub">
            <div class="hub-glass-card">
                <div class="hub-header">
                    <span class="hub-badge"><i class="fas fa-bolt"></i> Acesso Rápido</span>
                </div>
                
                <div class="hub-main">
                    <div class="hub-stat-item asset-card-premium">
                        <div class="asset-header">
                            <small>Saldo Disponível</small>
                            <button class="balance-toggle" onclick="toggleHeroBalance(this)">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                        <div class="hub-val-row" id="heroBalanceWrapper">
                            <span class="hub-value">
                                <span class="value-digits"><?php echo number_format($user_data['wallet_balance'] ?? 0, 2, ',', '.'); ?></span>
                            </span>
                            <span class="hub-currency">AOA</span>
                        </div>
                        <div class="asset-sparkline">
                            <div class="spark-pulse"></div>
                        </div>
                    </div>
                    
                    <script>
                    function toggleHeroBalance(btn) {
                        const wrapper = document.getElementById('heroBalanceWrapper');
                        const icon = btn.querySelector('i');
                        wrapper.classList.toggle('hidden');
                        if (wrapper.classList.contains('hidden')) {
                            icon.classList.replace('fa-eye-slash', 'fa-eye');
                        } else {
                            icon.classList.replace('fa-eye', 'fa-eye-slash');
                        }
                    }
                    </script>
                    
                    <div class="hub-divider"></div>

                    <div class="hub-status-item">
                        <small>Conta KALIYE</small>
                        <?php $dashboard_identity_verified = (($user_data['verification_status'] ?? 'unsubmitted') === 'verified'); ?>
                        <div class="status-indicator <?php echo $dashboard_identity_verified ? 'verified' : 'pending'; ?>">
                            <i class="fas <?php echo $dashboard_identity_verified ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                            <?php echo $dashboard_identity_verified ? 'Verificada' : 'Em Análise'; ?>
                        </div>
                    </div>
                </div>

                <div class="hub-footer">
                    <?php if ($_SESSION['user_type'] === 'admin'): ?>
                        <a href="administracao/moderation/moderation.php" class="<?php echo $pending_items > 0 ? 'hub-btn-alert' : 'hub-btn'; ?>">
                            <i class="fas fa-shield-alt"></i> <?php echo $pending_items; ?> Pendentes
                        </a>
                    <?php elseif (in_array($_SESSION['user_type'], ['mentor', 'investor'])): ?>
                        <a href="<?php echo $base_url; ?>paginas/explore_collat.php" class="hub-btn">
                            <i class="fas fa-search"></i> Explorar Oportunidades
                        </a>
                    <?php else: ?>
                        <button onclick="if(enforceKYC(false)) { openPostModal(); }" class="hub-btn">
                            <i class="fas fa-plus"></i> Novo Projecto
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Dica/Contexto Dinâmico -->
                <div class="hub-tip">
                    <i class="far fa-lightbulb"></i>
                    <span>
                        <?php 
                            if (!$dashboard_identity_verified) echo "Verifica a tua conta para ganhares confiança de investidores.";
                            elseif ($_SESSION['user_type'] === 'admin') echo "Existem projectos a aguardar a tua revisão de segurança.";
                            elseif ($_SESSION['user_type'] === 'mentor') echo "O teu perfil verificado está visível para estudantes.";
                            else echo "Partilha a tua ideia hoje e recebe feedback da comunidade.";
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- DIREITA: Estatísticas Globais -->
        <div class="hero-stats" style="flex-shrink: 0;">
            <div class="hero-stat-card">
                <div class="hero-stat-icon" style="background:rgba(247,148,29,0.12);color:#f7941d;">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="hero-stat-text">
                    <div class="hero-stat-value" style="font-size: 1.5rem;"><?php echo $global_projects; ?></div>
                    <div class="hero-stat-label" style="font-size: 0.6rem;">Projectos</div>
                </div>
            </div>
            <div class="hero-stat-card">
                <div class="hero-stat-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="hero-stat-text">
                    <div class="hero-stat-value" style="font-size: 1.5rem;"><?php echo $global_mentors; ?></div>
                    <div class="hero-stat-label" style="font-size: 0.6rem;">Mentores</div>
                </div>
            </div>
            <div class="hero-stat-card">
                <div class="hero-stat-icon" style="background:rgba(16,185,129,0.12);color:#10b981;">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="hero-stat-text">
                    <div class="hero-stat-value" style="font-size: 1.5rem;"><?php echo $global_students; ?></div>
                    <div class="hero-stat-label" style="font-size: 0.6rem;">Estudantes</div>
                </div>
            </div>
        </div>

    </div>
</div>
