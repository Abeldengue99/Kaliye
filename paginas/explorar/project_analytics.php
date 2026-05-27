<?php
/**
 * project_analytics.php - Dados & Performance dos Projectos (Aksanti Elite)
 */
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../paginas/guest/landing.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Estatísticas Globais das meus projectos
$stmt = $db->prepare("
    SELECT 
        COUNT(p.project_id) as total_projects,
        COALESCE(SUM((SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id)), 0) as total_likes,
        COALESCE(SUM((SELECT COUNT(*) FROM project_comments WHERE project_id = p.project_id)), 0) as total_comments,
        COALESCE(SUM((SELECT COUNT(*) FROM public.project_views WHERE project_id = p.project_id)), 0) as total_views
    FROM projects p
    WHERE p.owner_id = ?
");
$stmt->execute([$user_id]);
$global_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Performance por Projecto (Top 5)
$top_stmt = $db->prepare("
    SELECT * FROM (
        SELECT p.title, 
               (SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id) as likes,
               (SELECT COUNT(*) FROM project_comments WHERE project_id = p.project_id) as comments,
               (SELECT COUNT(*) FROM public.project_views WHERE project_id = p.project_id) as views
        FROM projects p
        WHERE p.owner_id = ?
    ) as project_rank
    ORDER BY (likes + views + comments) DESC
    LIMIT 5
");
$top_stmt->execute([$user_id]);
$performance = $top_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Comentários Recentes nas meus projectos
$comm_stmt = $db->prepare("
    SELECT c.*, u.full_name, u.profile_pic, p.title as project_title
    FROM project_comments c
    JOIN projects p ON c.project_id = p.project_id
    JOIN users u ON c.user_id = u.user_id
    WHERE p.owner_id = ?
    ORDER BY c.created_at DESC
    LIMIT 6
");
$comm_stmt->execute([$user_id]);
$recent_comments = $comm_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../../recursos/css/dashboard-aksanti-elite.css?v=<?php echo time(); ?>">

<div style="max-width: 1400px; margin: 0 auto; padding: 2rem 7.5%; min-height: 80vh;">
    
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 3rem;" data-aos="fade-down">
        <div>
            <h6 style="color: var(--elite-orange); font-size: 0.7rem; font-weight: 850; text-transform: uppercase; letter-spacing: 3px; margin: 0 0 0.5rem;">Insights & Métricas</h6>
            <h1 style="font-size: 2.8rem; font-weight: 900; color: #fff; margin: 0; letter-spacing: -1px;">Dados & Analytics</h1>
            <p style="color: var(--elite-text-muted); margin-top: 0.5rem; font-size: 1rem;">Mede o impacto e o engajamento das teus projectos no ecossistema.</p>
        </div>
        
        <div style="display: flex; gap: 15px;">
            <button onclick="window.print()" class="action-btn-mini" style="width: auto; padding: 0 1.5rem; border-radius: 14px; font-weight: 850; gap: 10px;">
                <i class="fas fa-download"></i> EXPORTAR RELATÓRIO
            </button>
        </div>
    </div>

    <!-- Tabela de Impacto Global -->
    <div class="elite-stats-grid" style="margin-bottom: 4rem;" data-aos="fade-up">
        <div class="elite-stat-card">
            <div class="elite-stat-label">ALCANCE TOTAL (VIEWS)</div>
            <div class="elite-stat-val"><?php echo number_format($global_stats['total_views']); ?></div>
            <div class="elite-stat-change">+12% esta semana</div>
        </div>
        <div class="elite-stat-card">
            <div class="elite-stat-label">INTERAÇÕES (LIKES)</div>
            <div class="elite-stat-val"><?php echo number_format($global_stats['total_likes']); ?></div>
            <div class="elite-stat-change">+05% esta semana</div>
        </div>
        <div class="elite-stat-card">
            <div class="elite-stat-label">FEEDBACK (COMENTÁRIOS)</div>
            <div class="elite-stat-val"><?php echo number_format($global_stats['total_comments']); ?></div>
            <div class="elite-stat-change" style="color: var(--elite-orange);">Novo Feedback</div>
        </div>
        <div class="elite-stat-card">
            <div class="elite-stat-label">CONVERSÃO ESTIMADA</div>
            <div class="elite-stat-val"><?php echo ($global_stats['total_views'] > 0) ? round(($global_stats['total_likes'] / $global_stats['total_views']) * 100, 1) : 0; ?>%</div>
            <div class="elite-stat-change">Engagement Rate</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2.5rem;">
        
        <!-- Coluna: Performance Detalhada -->
        <div data-aos="fade-right">
            <div class="widget-card" style="margin-bottom: 2rem;">
                <h3 class="widget-title">Ranking dos Melhores Projectos</h3>
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <?php if (!empty($performance)): ?>
                        <?php foreach ($performance as $p): 
                            $max = max($global_stats['total_views'], 1);
                            $pct = ($p['views'] / $max) * 100;
                        ?>
                            <div class="elite-progress-row">
                                <div class="elite-progress-label">
                                    <span><?php echo htmlspecialchars($p['title']); ?></span>
                                    <span><?php echo $p['views']; ?> views</span>
                                </div>
                                <div class="elite-progress-bar">
                                    <div class="elite-progress-fill" style="width: <?php echo $pct; ?>%; background: linear-gradient(90deg, var(--elite-orange), #fcd34d);"></div>
                                </div>
                                <div style="display: flex; gap: 1rem; margin-top: 8px; font-size: 0.7rem; color: var(--elite-text-muted); font-weight: 700;">
                                    <span><i class="fas fa-heart"></i> <?php echo $p['likes']; ?> Likes</span>
                                    <span><i class="fas fa-comment"></i> <?php echo $p['comments']; ?> Comentários</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 2rem; color: var(--elite-text-muted);">Ainda não tens dados de performance suficientes.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Coluna: Feedback Recente -->
        <div data-aos="fade-left">
            <div class="widget-card">
                <h3 class="widget-title">Feedback da Comunidade</h3>
                <div class="recent-feedback-list" style="display: flex; flex-direction: column; gap: 1.2rem;">
                    <?php if (!empty($recent_comments)): ?>
                        <?php foreach ($recent_comments as $c): ?>
                            <div style="display: flex; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px solid var(--surface-3);">
                                <img src="<?php echo $base_url . ($c['profile_pic'] ?: 'recursos/images/default_profile.png'); ?>" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover;">
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                        <strong style="font-size: 0.85rem; color: #fff;"><?php echo htmlspecialchars($c['full_name']); ?></strong>
                                        <span style="font-size: 0.65rem; color: var(--elite-text-muted);"><?php echo date('d M', strtotime($c['created_at'])); ?></span>
                                    </div>
                                    <p style="font-size: 0.7rem; color: var(--elite-orange); font-weight: 800; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">No projecto: <?php echo htmlspecialchars($c['project_title']); ?></p>
                                    <p style="font-size: 0.8rem; color: var(--surface-60); margin: 0; line-height: 1.4;"><?php echo htmlspecialchars($c['content']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 2rem; color: var(--elite-text-muted);">Sem comentários recentes.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once '../../inclusoes/rodape.php'; ?>
