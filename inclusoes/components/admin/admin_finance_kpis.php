<?php
/**
 * Component: Admin Finance KPIs
 * Expected Variable: $financial_stats (array)
 */
?>
<div style="display: flex; gap: 1.5rem; margin-bottom: 3rem; flex-wrap: wrap;">
    <?php foreach($financial_stats as $stat): ?>
        <div class="financial-stat-card glass-premium" style="flex: 1; min-width: 250px; padding: 1.5rem; border-left: 4px solid var(--accent-gold);">
            <h4 style="margin: 0; color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px;">
                Total Processado (<?= $stat['currency'] ?>)
            </h4>
            <div style="font-size: 2rem; font-weight: 900; margin: 0.5rem 0; color: white;">
                <?= number_format($stat['total_held'] + $stat['total_disbursed'], 2, ',', '.') ?>
            </div>
            <div style="font-size: 0.85rem; display: flex; gap: 1rem;">
                <span style="color: #60a5fa;"><i class="fas fa-hand-holding-usd"></i> Retido: <?= number_format($stat['total_held'], 2, ',', '.') ?></span>
                <span style="color: #fbbf24;"><i class="fas fa-clock"></i> Pipeline: <?= number_format($stat['potential_pipeline'], 2, ',', '.') ?></span>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if(empty($financial_stats)): ?>
        <div class="glass" style="flex: 1; padding: 1.5rem; text-align: center;">
            <p style="color: var(--text-secondary);">Sem movimentações financeiras no momento.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.glass-premium {
    background: var(--surface-3);
    backdrop-filter: blur(10px);
    border: 1px solid var(--surface-5);
    border-radius: 16px;
}
</style>
