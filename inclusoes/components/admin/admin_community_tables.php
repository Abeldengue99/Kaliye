<?php
/**
 * Component: Admin Community Tables (Top Users & Birthdays)
 */
?>
<div class="responsive-grid-3">
    <div class="admin-card-premium">
        <h4 style="margin-bottom: 1.5rem; color: #f7941d;"><i class="fas fa-crown"></i> Top Criadores</h4>
        <div class="table-container">
            <table class="aksanti-table">
                <tbody id="topPostersTable"></tbody>
            </table>
        </div>
    </div>
    <div class="admin-card-premium">
        <h4 style="margin-bottom: 1.5rem; color: #60a5fa;"><i class="fas fa-bolt"></i> Top Interatores</h4>
        <div class="table-container">
            <table class="aksanti-table">
                <tbody id="topCommentersTable"></tbody>
            </table>
        </div>
    </div>
    <div class="admin-card-premium">
        <h4 style="margin-bottom: 1.5rem; color: #f43f5e;"><i class="fas fa-cake-candles"></i> Aniversários</h4>
        <div id="birthdayList" style="display: flex; flex-direction: column; gap: 0.75rem;"></div>
    </div>
</div>
