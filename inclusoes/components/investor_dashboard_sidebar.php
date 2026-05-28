<?php
/**
 * Component: Investor Dashboard Sidebar (Compacto)
 * Renders the filter panel and transaction history sidebar.
 * 
 * Required variables from parent:
 * - $search_term, $category_filter, $budget_min, $budget_max
 * - $categories, $recent_transactions
 */
?>

<div class="sidebar-widget">
    <h4 style="margin: 0 0 1rem 0; font-size: 0.9rem; color: white;"><i class="fas fa-filter" style="color: var(--accent-orange); margin-right: 6px;"></i>Filtros</h4>
    <form method="GET" action="">
        <div style="margin-bottom: 1rem;">
            <label style="font-size: 0.65rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Buscar</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Buscar oportunidade..." style="width: 100%; padding: 0.65rem; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 10px; color: white; font-size: 0.8rem;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="font-size: 0.65rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Setor</label>
            <select name="category" style="width: 100%; padding: 0.65rem; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 10px; color: white; font-size: 0.8rem;">
                <option value="">Todos</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label style="font-size: 0.65rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Ticket (AKZ)</label>
            <div style="display: flex; gap: 0.4rem; align-items: center;">
                <input type="number" name="budget_min" placeholder="Mín" value="<?php echo $budget_min > 0 ? $budget_min : ''; ?>" style="width: 100%; padding: 0.55rem; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; font-size: 0.75rem;">
                <span style="color: var(--text-secondary); font-size: 0.8rem;">–</span>
                <input type="number" name="budget_max" placeholder="Máx" value="<?php echo $budget_max < PHP_INT_MAX * 0.1 ? $budget_max : ''; ?>" style="width: 100%; padding: 0.55rem; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; font-size: 0.75rem;">
            </div>
        </div>

        <button type="submit" class="btn-primary" style="width: 100%; padding: 0.75rem; background: var(--accent-orange); border: none; font-weight: 800; border-radius: 12px; font-size: 0.8rem;">
            FILTRAR
        </button>
        <a href="investor_dashboard.php" style="display: block; text-align: center; margin-top: 0.6rem; color: var(--text-secondary); font-size: 0.7rem; text-decoration: none;">Limpar</a>
    </form>
</div>

<?php if (count($recent_transactions) > 0): ?>
<div class="sidebar-widget">
    <h4 style="margin: 0 0 1rem 0; font-size: 0.9rem; color: white;"><i class="fas fa-history" style="color: var(--accent-orange); margin-right: 6px;"></i>Transações</h4>
    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
        <?php foreach ($recent_transactions as $t): ?>
            <div style="padding-bottom: 0.5rem; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 700; color: white;"><?php echo ucfirst($t['type']); ?></div>
                    <div style="font-size: 0.6rem; color: var(--text-secondary);"><?php echo date('d/m/Y', strtotime($t['created_at'])); ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.8rem; font-weight: 800; color: <?php echo $t['amount'] >= 0 ? '#10b981' : '#ef4444'; ?>;">
                        <?php echo ($t['amount'] >= 0 ? '+' : '') . number_format($t['amount'], 0, ',', '.'); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
