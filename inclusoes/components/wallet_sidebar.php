<?php
/**
 * Componente: Barra Lateral da Carteira (Design Fintech Premium)
 * Otimizado para o Sistema de Design Aksanti 2026.
 */
?>
<aside class="wallet-sidebar">
    <!-- Action Nav -->
    <a href="javascript:history.back()" class="wallet-back-btn">
        <i class="fas fa-chevron-left"></i>
        <span>Voltar ao Painel</span>
    </a>

    <!-- Card: Core Balance -->
    <div class="balance-card">
        <div class="balance-header">
            <div class="wallet-aura-wrap">
                <div class="wallet-aura">
                    <i class="fas fa-wallet"></i>
                </div>
                <!-- Mini pulse effect -->
                <div class="aura-pulse"></div>
            </div>
            
            <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <span class="label">Saldo Disponível</span>
                <button class="balance-visibility-toggle" onclick="toggleWalletBalance(this)">
                    <i class="fas fa-eye-slash"></i>
                </button>
            </div>

            <h2 class="amount-display" id="walletAmountWrapper">
                <span class="amount-digits"><?php echo number_format($current_balance, 0, ',', '.'); ?></span>
                <span class="ccy">AKZ</span>
            </h2>
        </div>
        
        <script>
        function toggleWalletBalance(btn) {
            const wrapper = document.getElementById('walletAmountWrapper');
            const icon = btn.querySelector('i');
            wrapper.classList.toggle('hidden-balance');
            if (wrapper.classList.contains('hidden-balance')) {
                icon.classList.replace('fa-eye-slash', 'fa-eye');
                localStorage.setItem('wallet_balance_hidden', 'true');
            } else {
                icon.classList.replace('fa-eye', 'fa-eye-slash');
                localStorage.setItem('wallet_balance_hidden', 'false');
            }
        }
        
        // Auto-apply preference
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('wallet_balance_hidden') === 'true') {
                const btn = document.querySelector('.balance-visibility-toggle');
                if (btn) toggleWalletBalance(btn);
            }
        });
        </script>
        
        <!-- Quick Actions -->
        <div class="balance-actions">
            <?php if($user_type == 'investor' || $user_type == 'admin'): ?>
                <button onclick="openDepositModal()" class="btn-primary">
                    <i class="fas fa-arrow-up"></i> ADICIONAR FUNDOS
                </button>
            <?php endif; ?>
            
            <button onclick="openWithdrawModal()" class="btn-secondary">
                <i class="fas fa-paper-plane"></i> SOLICITAR SAQUE
            </button>
        </div>

        <!-- Ecosystem Stats -->
        <div class="balance-footer">
            <div class="footer-stat">
                <div class="stat-content">
                    <span class="stat-label">Total Investido</span>
                    <span class="stat-value"><?php echo number_format($total_inv_stats, 0, ',', '.'); ?> <b>AKZ</b></span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>
</aside>
