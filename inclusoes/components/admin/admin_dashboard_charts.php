<?php
/**
 * Component: Admin Dashboard Charts
 */
?>
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;" class="responsive-grid-3">
    <div class="admin-card-premium">
        <h4 style="margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-chart-line" style="color: #60a5fa;"></i> Crescimento da Rede
        </h4>
        <div style="height: 320px;">
            <canvas id="userGrowthChart"></canvas>
        </div>
    </div>
    <div class="admin-card-premium">
        <h4 style="margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-layer-group" style="color: #f7941d;"></i> Atividade Global
        </h4>
        <div style="height: 320px; display: flex; justify-content: center;">
            <canvas id="categoriesChart"></canvas>
        </div>
    </div>
</div>
