<?php
/**
 * Component: Admin Dashboard Stats Cards
 * Populated via JS for real-time feel.
 */
?>
<div class="stats-grid">
    <a href="users/manage_users.php" class="admin-card-premium">
        <div class="stat-card-inner">
            <div class="stat-icon" style="color: #60a5fa;"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-value" id="totalUsers">-</div>
                <div style="font-size: 0.75rem; color: var(--surface-40); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Utilizadores</div>
            </div>
        </div>
    </a>
    <a href="moderation/moderation.php" class="admin-card-premium">
        <div class="stat-card-inner">
            <div class="stat-icon" style="color: #f7941d;"><i class="fas fa-rocket"></i></div>
            <div>
                <div class="stat-value" id="totalProjects">-</div>
                <div style="font-size: 0.75rem; color: var(--surface-40); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Projetos Pendentes</div>
            </div>
        </div>
    </a>
    <a href="users/kyc_requests.php" class="admin-card-premium">
        <div class="stat-card-inner">
            <div class="stat-icon" style="color: #34d399;"><i class="fas fa-user-check"></i></div>
            <div>
                <div class="stat-value" id="totalMentorships">-</div>
                <div style="font-size: 0.75rem; color: var(--surface-40); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Verificações Pendentes</div>
            </div>
        </div>
    </a>
    <a href="<?= $admin_base ?>marketing/manage_ads.php" class="admin-card-premium">
        <div class="stat-card-inner">
            <div class="stat-icon" style="color: #fbbf24;"><i class="fas fa-ad"></i></div>
            <div>
                <div class="stat-value" id="totalAdViews">-</div>
                <div style="font-size: 0.75rem; color: var(--surface-40); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Ad Views</div>
            </div>
        </div>
    </a>
</div>
