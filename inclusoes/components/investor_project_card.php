<?php
/**
 * Component: Investor Project Card
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
                <span class="badge-premium" style="background: #3b82f6; color: white;">Novo Deal</span>
            <?php endif; ?>
        </div>
        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 80px; background: linear-gradient(transparent, var(--secondary-bg));"></div>
    </div>

    <div style="padding: 1.5rem; flex-grow: 1; display: flex; flex-direction: column;">
        <h3 style="font-size: 1.35rem; font-weight: 800; margin: 0 0 1rem 0; color: white;"><?php echo htmlspecialchars($project['title']); ?></h3>
        
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem;">
            <div style="width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--accent-orange); overflow: hidden; background: #1e293b; display: flex; align-items: center; justify-content: center;">
                <?php if($project['profile_pic'] && $project['profile_pic'] != 'default_profile.png'): ?>
                    <img src="../<?php echo htmlspecialchars($project['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user" style="font-size: 0.8rem; color: var(--accent-orange);"></i>
                <?php endif; ?>
            </div>
            <span style="font-size: 0.9rem; color: var(--text-secondary);"><?php echo htmlspecialchars($project['owner_name']); ?></span>
            <?php if(($project['owner_verification_status'] ?? '') == 'verified'): ?>
                <i class="fas fa-check-circle" style="color: #3b82f6; font-size: 0.8rem;" title="Verificado"></i>
            <?php endif; ?>
        </div>

        <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem; flex-grow: 1;">
            <?php echo substr(htmlspecialchars($project['description']), 0, 140); ?>...
        </p>

        <div class="financial-pill">
            <div class="pill-data">
                <span class="pill-label">Captação Alvo</span>
                <span class="pill-value"><?php echo number_format($project['budget_needed'], 0, ',', '.'); ?> <small style="font-size: 0.6rem;">AKZ</small></span>
            </div>
            <div class="pill-data" style="text-align: right;">
                <span class="pill-label">Estágio</span>
                <span style="font-weight: 700; color: white; font-size: 0.9rem;"><i class="fas fa-chart-line" style="color: var(--accent-orange); margin-right: 4px;"></i> <?php echo htmlspecialchars($project['project_stage'] ?: 'Seed'); ?></span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
            <button onclick="openInvestorProjectDetails(<?php echo $project['project_id']; ?>)" class="btn-primary" style="background: var(--surface-5); border-color: var(--glass-border); padding: 0.8rem; font-size: 0.85rem;">
                <i class="fas fa-eye"></i> Analisar
            </button>
            <button onclick="openInvestModal(<?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars(addslashes($project['title'])); ?>')" class="btn-primary" style="background: var(--accent-orange); border: none; color: black; font-weight: 800; padding: 0.8rem; font-size: 0.85rem;">
                <i class="fas fa-hand-holding-usd"></i> Investir
            </button>
        </div>

        <div style="margin-top: 1rem; text-align: center;">
            <?php if ($project['is_read'] > 0): ?>
                <span style="font-size: 0.7rem; color: #10b981; font-weight: 700;"><i class="fas fa-check"></i> ANALISADO</span>
            <?php else: ?>
                <button onclick="markAsRead(<?php echo $project['project_id']; ?>)" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 0.7rem; text-decoration: underline; text-transform: uppercase; font-weight: 600;">
                    <i class="fas fa-archive"></i> Arquivar
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
