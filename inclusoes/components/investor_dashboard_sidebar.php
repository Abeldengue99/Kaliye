<?php
/**
 * Component: Investor Dashboard Sidebar
 * Renders the filter panel and transaction history sidebar.
 * 
 * Required variables from parent:
 * - $search_term, $category_filter, $budget_min, $budget_max
 * - $categories, $recent_transactions
 */
?>

<div class="sidebar-widget">
    <h4 style="margin: 0 0 1.5rem 0; font-size: 1rem; color: white;"><i class="fas fa-filter" style="color: var(--accent-orange);"></i> Filtros de Negócio</h4>
    <form method="GET" action="">
        <div class="input-group" style="margin-bottom: 1.25rem;">
            <label style="font-size: 0.75rem;">Palavra-passe / Setor</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Buscar oportunidade..." style="width: 100%; padding: 0.8rem; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 12px; color: white;">
        </div>

        <div class="input-group" style="margin-bottom: 1.25rem;">
            <label style="font-size: 0.75rem;">Setor de Atuação</label>
            <select name="category" style="width: 100%; padding: 0.8rem; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 12px; color: white;">
                <option value="">Todos os Setores</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="input-group" style="margin-bottom: 2rem;">
            <label style="font-size: 0.75rem;">Ticket Médio (AKZ)</label>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="number" name="budget_min" placeholder="Mín" value="<?php echo $budget_min > 0 ? $budget_min : ''; ?>" style="width: 100%; padding: 0.6rem; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; font-size: 0.8rem;">
                <span style="color: var(--text-secondary);">–</span>
                <input type="number" name="budget_max" placeholder="Máx" value="<?php echo $budget_max < PHP_INT_MAX * 0.1 ? $budget_max : ''; ?>" style="width: 100%; padding: 0.6rem; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; font-size: 0.8rem;">
            </div>
        </div>

        <button type="submit" class="btn-primary" style="width: 100%; padding: 1rem; background: var(--accent-orange); border: none; font-weight: 800; border-radius: 14px;">
            REFINAR BUSCA
        </button>
        <a href="investor_dashboard.php" style="display: block; text-align: center; margin-top: 1rem; color: var(--text-secondary); font-size: 0.8rem; text-decoration: none;">Limpar Tudo</a>
    </form>
</div>

<div class="sidebar-widget">
    <h4 style="margin: 0 0 1.5rem 0; font-size: 1rem; color: white;"><i class="fas fa-history" style="color: var(--accent-orange);"></i> Histórico Direto</h4>
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (count($recent_transactions) > 0): ?>
            <?php foreach ($recent_transactions as $t): ?>
                <div style="padding-bottom: 0.75rem; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.8rem; font-weight: 700; color: white;"><?php echo ucfirst($t['type']); ?></div>
                        <div style="font-size: 0.65rem; color: var(--text-secondary);"><?php echo date('d/m/Y', strtotime($t['created_at'])); ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.85rem; font-weight: 800; color: <?php echo $t['amount'] >= 0 ? '#10b981' : '#ef4444'; ?>;">
                            <?php echo ($t['amount'] >= 0 ? '+' : '') . number_format($t['amount'], 0, ',', '.'); ?>
                        </div>
                        <span style="font-size: 0.6rem; color: var(--text-secondary); text-transform: uppercase;"><?php echo $t['status']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <a href="wallet.php" style="display: block; text-align: center; margin-top: 1rem; color: var(--accent-orange); font-size: 0.75rem; text-decoration: none; font-weight: 700;">Ver Extrato Completo</a>
        <?php else: ?>
            <p style="text-align: center; font-size: 0.8rem; color: var(--text-secondary); padding: 1rem 0;">Sem transações registradas</p>
        <?php endif; ?>
    </div>

    <div class="glass" style="margin-top: 2rem; padding: 1.25rem; border-radius: 16px; border-left: 4px solid #10b981; background: rgba(16, 185, 129, 0.05);">
        <h5 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; font-weight: 800; color: white;">Compliance OK</h5>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-check-shield" style="color: #10b981; font-size: 1.2rem;"></i>
            <p style="margin: 0; font-size: 0.75rem; color: var(--text-secondary);">Conta verificada para operações.</p>
        </div>
    </div>
</div>
