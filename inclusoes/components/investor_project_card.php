<?php
/**
 * Component: Investor Project Card (Compacto)
 * Renders a single project card in the investor dashboard feed.
 * 
 * Required variables from parent (loop):
 * - $project (single project row from the query)
 */
?>

<div class="investor-project-card glass">
    <div class="card-banner">
        <?php 
            require_once __DIR__ . '/../ProjectMediaHelper.php';
            $media_path = ProjectMediaHelper::getCover($project, '../'); 
        ?>
        <img loading="lazy" src="<?php echo $media_path; ?>" style="width: 100%; height: 100%; object-fit: cover;">
        
        <div class="card-badges">
            <span class="badge-premium"><?php echo htmlspecialchars($project['category']); ?></span>
            <?php if ($project['is_new'] > 0): ?>
                <span class="badge-premium" style="background: #3b82f6; color: white;">Novo</span>
            <?php endif; ?>
        </div>
        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 60px; background: linear-gradient(transparent, var(--secondary-bg));"></div>
    </div>

    <div style="padding: 1.25rem; flex-grow: 1; display: flex; flex-direction: column;">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0 0 0.6rem 0; color: white; line-height: 1.3;"><?php echo htmlspecialchars($project['title']); ?></h3>
        
        <!-- Owner -->
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
            <div style="width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--accent-orange); overflow: hidden; background: #1e293b; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <?php if($project['profile_pic'] && $project['profile_pic'] != 'default_profile.png'): ?>
                    <img src="../<?php echo htmlspecialchars($project['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user" style="font-size: 0.6rem; color: var(--accent-orange);"></i>
                <?php endif; ?>
            </div>
            <span style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo htmlspecialchars($project['owner_name']); ?></span>
            <?php if(($project['owner_verification_status'] ?? '') == 'verified'): ?>
                <i class="fas fa-check-circle" style="color: #3b82f6; font-size: 0.7rem;" title="Verificado"></i>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <p style="color: var(--text-secondary); font-size: 0.8rem; line-height: 1.5; margin-bottom: 0.75rem; flex-grow: 1;">
            <?php echo substr(htmlspecialchars($project['description']), 0, 100); ?>...
        </p>

        <!-- Financial Pill -->
        <div class="financial-pill">
            <div class="pill-data">
                <span class="pill-label">Captação</span>
                <span class="pill-value"><?php echo number_format($project['budget_needed'], 0, ',', '.'); ?> <small style="font-size: 0.55rem;">AKZ</small></span>
            </div>
            <div class="pill-data" style="text-align: right;">
                <span class="pill-label">Estágio</span>
                <span style="font-weight: 700; color: white; font-size: 0.8rem;"><?php echo htmlspecialchars($project['project_stage'] ?: 'Seed'); ?></span>
            </div>
        </div>

        <?php if ($view_mode === 'my_investments'): ?>
            <!-- Estado do investimento existente -->
            <div style="background: rgba(16,185,129,0.05); border: 1px solid rgba(16,185,129,0.2); border-radius: 10px; padding: 0.75rem; margin-top: 0.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <span style="font-size: 0.65rem; color: var(--text-secondary); text-transform: uppercase;">Estado</span>
                    <?php 
                        $inv_status = $project['my_investment_status'] ?? 'pending';
                        $status_styles = [
                            'pending' => 'color: #f7941d; background: rgba(247,148,29,0.1);',
                            'confirmed' => 'color: #10b981; background: rgba(16,185,129,0.1);',
                            'rejected' => 'color: #ef4444; background: rgba(239,68,68,0.1);',
                            'completed' => 'color: #3b82f6; background: rgba(59,130,246,0.1);'
                        ];
                        $style = $status_styles[$inv_status] ?? $status_styles['pending'];
                    ?>
                    <span style="padding: 3px 7px; border-radius: 5px; font-size: 0.6rem; font-weight: 800; text-transform: uppercase; <?php echo $style; ?>"><?php echo $inv_status; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.65rem; color: var(--text-secondary);">Valor</span>
                    <span style="font-weight: 800; color: white; font-size: 0.85rem;"><?php echo number_format($project['my_investment_amount'] ?? 0, 0, ',', '.'); ?> <small style="font-size: 0.55rem;">AKZ</small></span>
                </div>
            </div>
        <?php else: ?>
            <!-- Botões de ação -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-top: 0.5rem;">
                <button onclick="if(enforceKYC()) openInvestorProjectDetails(<?php echo $project['project_id']; ?>)" class="btn-primary" style="background: var(--surface-5); border-color: var(--glass-border); padding: 0.6rem; font-size: 0.75rem;">
                    <i class="fas fa-eye"></i> Analisar
                </button>
                <button onclick="if(enforceKYC()) openInvestModal(<?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars(addslashes($project['title'])); ?>')" class="btn-primary" style="background: var(--accent-orange); border: none; color: black; font-weight: 800; padding: 0.6rem; font-size: 0.75rem;">
                    <i class="fas fa-hand-holding-usd"></i> Investir
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
