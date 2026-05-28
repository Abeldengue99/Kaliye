<?php
/**
 * Component: Investor Dashboard Content
 * Renders the main dashboard layout: hero section, sidebar filters, and project feed.
 * Optimized for mobile and investor profile.
 * 
 * Required variables from parent:
 * - $balance, $invested, $active_deals
 * - $search_term, $category_filter, $budget_min, $budget_max
 * - $categories, $recent_transactions, $projects
 * - $user_data (from session/header)
 */

$is_verified_investor = (($user_data['verification_status'] ?? 'unsubmitted') === 'verified');
?>

<div id="investorLegalAlert"></div>

<div class="investor-dashboard">

    <!-- Aviso de verificação para investidores não verificados -->
    <?php if (!$is_verified_investor): ?>
    <div style="background: rgba(247,148,29,0.06); border: 1px solid rgba(247,148,29,0.2); border-radius: 16px; padding: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(247,148,29,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i class="fas fa-shield-alt" style="color: #f7941d; font-size: 1.2rem;"></i>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <h4 style="margin: 0 0 4px; font-size: 0.9rem; color: #fff; font-weight: 800;">Verificação Pendente</h4>
            <p style="margin: 0; font-size: 0.78rem; color: rgba(255,255,255,0.6); line-height: 1.5;">
                Complete a verificação de identidade para submeter propostas de investimento e aceder a todas as funcionalidades.
            </p>
        </div>
        <a href="../../paginas/social/profile.php#kyc" style="padding: 0.6rem 1.25rem; background: var(--accent-orange, #f7941d); color: #000; border-radius: 10px; font-size: 0.8rem; font-weight: 800; text-decoration: none; white-space: nowrap;">
            <i class="fas fa-id-card" style="margin-right: 5px;"></i> VERIFICAR AGORA
        </a>
    </div>
    <?php endif; ?>

    <!-- Header Compacto -->
    <div class="investor-hero">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.6rem; font-weight: 900; letter-spacing: -0.5px; margin: 0;">
                    <?= $view_mode == 'my_investments' ? 'Minhas Propostas' : 'Pipeline de Investimento' ?>
                </h1>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.3rem; max-width: 500px;">
                    <?= $view_mode == 'my_investments' ? 'Acompanhe o estado das propostas que enviou aos projetos.' : 'Projectos curados pela inteligência KALIYE.' ?>
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <div style="background: rgba(0,0,0,0.2); padding: 3px; border-radius: 10px; border: 1px solid var(--glass-border); display: flex;">
                    <a href="?view=explore" style="padding: 0.45rem 0.85rem; border-radius: 7px; text-decoration: none; font-size: 0.78rem; font-weight: 700; color: <?= $view_mode == 'explore' ? '#fff' : 'rgba(255,255,255,0.5)' ?>; background: <?= $view_mode == 'explore' ? 'var(--accent-orange)' : 'transparent' ?>; transition: 0.2s;">Explorar</a>
                    <a href="?view=my_investments" style="padding: 0.45rem 0.85rem; border-radius: 7px; text-decoration: none; font-size: 0.78rem; font-weight: 700; color: <?= $view_mode == 'my_investments' ? '#fff' : 'rgba(255,255,255,0.5)' ?>; background: <?= $view_mode == 'my_investments' ? 'var(--accent-orange)' : 'transparent' ?>; transition: 0.2s;">Propostas</a>
                </div>
            </div>
        </div>

        <!-- Métricas compactas apenas no modo Explorar -->
        <?php if ($view_mode === 'explore'): ?>
        <div class="metrics-container">
            <div class="metric-card">
                <span class="metric-label">Capital em Carteira</span>
                <div class="metric-value"><?php echo number_format($balance, 0, ',', '.'); ?> <small style="font-size: 0.7rem; opacity: 0.6;">AKZ</small></div>
            </div>
            <div class="metric-card">
                <span class="metric-label">Total em Ativos</span>
                <div class="metric-value"><?php echo number_format($invested, 0, ',', '.'); ?> <small style="font-size: 0.7rem; opacity: 0.6;">AKZ</small></div>
            </div>
            <div class="metric-card">
                <span class="metric-label">Deals Ativos</span>
                <div class="metric-value"><?php echo $active_deals; ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Main Layout -->
    <div class="dashboard-layout">
        <!-- Sidebar apenas no modo Explorar -->
        <?php if ($view_mode === 'explore'): ?>
        <aside class="filter-panel">
            <?php include __DIR__ . '/investor_dashboard_sidebar.php'; ?>
        </aside>
        <?php endif; ?>

        <!-- Projects Feed -->
        <div class="projects-feed" <?= $view_mode === 'my_investments' ? 'style="width: 100%; max-width: 1200px; margin: 0 auto;"' : '' ?>>
            <?php if (count($projects) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($projects as $project): ?>
                        <?php include __DIR__ . '/investor_project_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="glass" style="padding: 3rem 1.5rem; text-align: center; border-radius: 20px;">
                    <i class="fas fa-search" style="font-size: 2.5rem; color: var(--text-secondary); opacity: 0.25; margin-bottom: 1rem;"></i>
                    <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Nenhuma oportunidade encontrada</h3>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Ajuste os filtros para encontrar projectos.</p>
                    <a href="investor_dashboard.php" class="btn-primary" style="width: auto; padding: 0.7rem 1.5rem; display: inline-block; text-decoration: none; font-size: 0.85rem;">Limpar Filtros</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
