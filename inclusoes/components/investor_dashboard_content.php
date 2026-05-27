<?php
/**
 * Component: Investor Dashboard Content
 * Renders the main dashboard layout: hero section, sidebar filters, and project feed.
 * 
 * Required variables from parent:
 * - $balance, $invested, $active_deals
 * - $search_term, $category_filter, $budget_min, $budget_max
 * - $categories, $recent_transactions, $projects
 */
?>

<div id="investorLegalAlert"></div>

<div class="investor-dashboard">
    <!-- Premium Header -->
    <div class="investor-hero">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 2rem;">
            <div>
                <h1 style="font-size: 2.5rem; font-weight: 900; letter-spacing: -1.5px; margin: 0;">
                    Pipeline de Investimento <span style="color: var(--accent-orange);">Premium</span>
                </h1>
                <p style="color: var(--text-secondary); font-size: 1.1rem; margin-top: 0.5rem; max-width: 600px;">
                    Acesso exclusivo a projectos curados pela inteligência KALIYE. Cada oportunidade nesta lista passou por um processo de validação estratégica.
                </p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <button onclick="location.reload()" class="btn-primary" style="width: auto; padding: 0.75rem 1.25rem; background: var(--surface-5); border-color: var(--glass-border);">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
            </div>
        </div>

        <div class="metrics-container">
            <div class="metric-card">
                <span class="metric-label">Capital em Carteira</span>
                <div class="metric-value"><?php echo number_format($balance, 0, ',', '.'); ?> <small style="font-size: 0.8rem; opacity: 0.6;">AKZ</small></div>
            </div>
            <div class="metric-card">
                <span class="metric-label">Total em Ativos</span>
                <div class="metric-value"><?php echo number_format($invested, 0, ',', '.'); ?> <small style="font-size: 0.8rem; opacity: 0.6;">AKZ</small></div>
            </div>
            <div class="metric-card">
                <span class="metric-label">Projectos em Portfólio</span>
                <div class="metric-value"><?php echo $active_deals; ?> <small style="font-size: 0.8rem; opacity: 0.6;">Deals</small></div>
            </div>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="filter-panel">
            <?php include __DIR__ . '/investor_dashboard_barra_lateral.php'; ?>
        </aside>

        <!-- Projects Feed -->
        <div class="projects-feed">
            <?php if (count($projects) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 2rem;">
                    <?php foreach ($projects as $project): ?>
                        <?php include __DIR__ . '/investor_project_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="glass" style="padding: 5rem 2rem; text-align: center; border-radius: 24px;">
                    <i class="fas fa-database" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3; margin-bottom: 1.5rem;"></i>
                    <h3>Nenhuma oportunidade encontrada</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem;">Ajuste os filtros de busca para encontrar outros projectos validados.</p>
                    <a href="investor_dashboard.php" class="btn-primary" style="width: auto; padding: 0.8rem 2rem; display: inline-block; text-decoration: none;">Limpar Filtros</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

