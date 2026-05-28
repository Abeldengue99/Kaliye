<?php
// paginas/plataforma/analytics.php - Dashboard de Analytics para Estudantes
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

// Apenas estudantes podem acessar
if (!in_array($_SESSION['user_type'], ['univ_student', 'high_student'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Buscar estatísticas do utilizador
$stats_query = "
    SELECT 
        COUNT(DISTINCT p.project_id) as total_projects,
        COUNT(DISTINCT CASE WHEN p.is_public = true THEN p.project_id END) as approved_projects,
        AVG(p.originality_score) as avg_score,
        SUM((SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id)) as total_likes,
        SUM((SELECT COUNT(*) FROM project_comments WHERE project_id = p.project_id)) as total_comments
    FROM projects p
    WHERE p.owner_id = :user_id
";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([':user_id' => $user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Buscar investimentos recebidos
$investment_query = "
    SELECT COALESCE(SUM(amount), 0) as total_invested
    FROM project_investments pi
    JOIN projects p ON pi.project_id = p.project_id
    WHERE p.owner_id = :user_id AND pi.status = 'approved'
";
$inv_stmt = $db->prepare($investment_query);
$inv_stmt->execute([':user_id' => $user_id]);
$investment = $inv_stmt->fetch(PDO::FETCH_ASSOC);

// Buscar histórico de projectos
$projects_query = "
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id) as likes,
        (SELECT COUNT(*) FROM project_comments WHERE project_id = p.project_id) as comments
    FROM projects p
    WHERE p.owner_id = :user_id
    ORDER BY p.created_at DESC
    LIMIT 10
";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute([':user_id' => $user_id]);
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/animations-2026.css">

<style>
    .analytics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        border-color: var(--accent-orange);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--accent-orange);
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .chart-container {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .project-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
        gap: 1rem;
        padding: 1rem;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        margin-bottom: 0.75rem;
        transition: all 0.3s ease;
    }

    .project-row:hover {
        border-color: var(--accent-orange);
        transform: translateX(5px);
    }

    @media (max-width: 768px) {
        .project-row {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
    }
</style>

<div class="container" style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
    <div class="animate-fade-in">
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-chart-line" style="color: var(--accent-orange);"></i>
            Meu Dashboard de Analytics
        </h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
            Acompanhe o desempenho das seus projectos e projectos
        </p>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="analytics-grid stagger-container">
        <div class="stat-card stagger-item card-hover">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total_projects'] ?? 0; ?></div>
            <div class="stat-label">Projectos Criados</div>
        </div>

        <div class="stat-card stagger-item card-hover">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo $stats['approved_projects'] ?? 0; ?></div>
            <div class="stat-label">Projectos Aprovados</div>
        </div>

        <div class="stat-card stagger-item card-hover">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-orange);">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-value"><?php echo round($stats['avg_score'] ?? 0); ?>%</div>
            <div class="stat-label">Score Médio IA</div>
        </div>

        <div class="stat-card stagger-item card-hover">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total_likes'] ?? 0; ?></div>
            <div class="stat-label">Total de Likes</div>
        </div>

        <div class="stat-card stagger-item card-hover">
            <div class="stat-icon" style="background: rgba(168, 85, 247, 0.1); color: #a855f7;">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total_comments'] ?? 0; ?></div>
            <div class="stat-label">Total de Comentários</div>
        </div>

        <div class="stat-card stagger-item card-hover">
            <div class="stat-icon" style="background: rgba(212, 175, 55, 0.1); color: var(--accent-gold);">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-value"><?php echo number_format($investment['total_invested'] ?? 0, 0, ',', '.'); ?></div>
            <div class="stat-label">Investimento Recebido (AOA)</div>
        </div>
    </div>

    <!-- Histórico de Projectos -->
    <div class="chart-container animate-slide-up">
        <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-history" style="color: var(--accent-orange);"></i>
            Histórico de Projectos
        </h2>

        <?php if (count($projects) > 0): ?>
            <div style="overflow-x: auto;">
                <div class="project-row" style="background: var(--secondary-bg); font-weight: 600;">
                    <div>Projecto</div>
                    <div>Score IA</div>
                    <div>Likes</div>
                    <div>Comentários</div>
                    <div>Status</div>
                </div>

                <?php foreach ($projects as $project): ?>
                    <div class="project-row transition-smooth">
                        <div>
                            <a href="projects.php?id=<?php echo $project['project_id']; ?>" 
                               style="color: var(--text-primary); text-decoration: none; font-weight: 500;">
                                <?php echo htmlspecialchars($project['title']); ?>
                            </a>
                            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                <?php echo date('d/m/Y', strtotime($project['created_at'])); ?>
                            </div>
                        </div>
                        <div>
                            <span style="color: <?php echo $project['originality_score'] >= 70 ? '#10b981' : ($project['originality_score'] >= 50 ? 'var(--accent-orange)' : '#ef4444'); ?>; font-weight: 600;">
                                <?php echo $project['originality_score'] ?? 'N/A'; ?>%
                            </span>
                        </div>
                        <div>
                            <i class="fas fa-heart" style="color: #ef4444; margin-right: 0.5rem;"></i>
                            <?php echo $project['likes']; ?>
                        </div>
                        <div>
                            <i class="fas fa-comment" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                            <?php echo $project['comments']; ?>
                        </div>
                        <div>
                            <?php if ($project['is_public']): ?>
                                <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                                    <i class="fas fa-check"></i> APROVADO
                                </span>
                            <?php elseif ($project['ai_status'] == 'analyzed'): ?>
                                <span style="background: rgba(245, 158, 11, 0.2); color: var(--accent-orange); padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                                    <i class="fas fa-clock"></i> PENDENTE
                                </span>
                            <?php else: ?>
                                <span style="background: rgba(148, 163, 184, 0.2); color: #94a3b8; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                                    <i class="fas fa-hourglass"></i> ANÃLISE
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <p>Ainda não tens projectos criados.</p>
                <a href="../index.php" class="btn-primary" style="display: inline-block; margin-top: 1rem; width: auto; padding: 0.75rem 2rem;">
                    Criar primeiro projecto
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Dicas de Melhoria -->
    <div class="chart-container animate-slide-up" style="animation-delay: 0.2s;">
        <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-lightbulb" style="color: var(--accent-orange);"></i>
            Dicas para Melhorar
        </h2>

        <div style="display: grid; gap: 1rem;">
            <?php if (($stats['avg_score'] ?? 0) < 70): ?>
                <div style="padding: 1rem; background: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--accent-orange); border-radius: 8px;">
                    <strong>ðŸ’¡ Melhore o Score IA:</strong> Adicione mais detalhes sobre a execução, público-alvo e diferencial da seu projecto.
                </div>
            <?php endif; ?>

            <?php if (($stats['total_projects'] ?? 0) < 3): ?>
                <div style="padding: 1rem; background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; border-radius: 8px;">
                    <strong>ðŸš€ Continue Criando:</strong> Quanto mais projectos submeter, maior a chance de aprovação e investimento!
                </div>
            <?php endif; ?>

            <?php if (($stats['approved_projects'] ?? 0) == 0): ?>
                <div style="padding: 1rem; background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; border-radius: 8px;">
                    <strong>âœ¨ Busque Aprovação:</strong> Revise seus projectos com base no feedback da IA e aguarde a análise do admin.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../inclusoes/rodape.php'; ?>


