<!-- Modal: Deposit (Add Funds) -->
<?php if($user_type == 'investor' || $user_type == 'admin'): ?>
<div id="depositModal" class="modal-premium" style="display: none;">
    <div class="glass modal-content-premium">
        <div class="modal-header-premium">
            <h3 class="section-title" style="font-size: 1.2rem; margin:0;">
                <i class="fas fa-plus-circle" style="color: var(--brand-primary);"></i> Adicionar Saldo
            </h3>
            <button onclick="closeDepositModal()" class="btn-close-premium"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body-premium">
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 2rem; line-height: 1.6;">
                Adicione saldo à sua carteira para investir em novos projetos revolucionários e expandir o seu ecossistema.
            </p>
            <form id="depositForm">
                <div class="input-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.7rem; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.8rem;">
                        MONTANTE A DEPOSITAR (AKZ)
                    </label>
                    <input type="number" name="amount" placeholder="Ex: 50.000" required 
                           style="width: 100%; padding: 1.2rem; background: rgba(0,0,0,0.2); border: 1px solid var(--glass-border); border-radius: 14px; color: white; font-size: 1rem; outline: none; transition: 0.3s;">
                </div>
                <button type="submit" class="btn-primary-premium">
                    <i class="fas fa-qrcode"></i> GERAR REFERÊNCIA DE PAGAMENTO
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Solicitar Saque (Withdrawal) - Design Premium Fintech -->
<div id="withdrawModal" class="modal-premium" style="display: none;">
    <div class="glass modal-content-premium">
        <!-- Cabeçalho do Modal: Identidade Visual Fintech -->
        <div class="modal-header-premium">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 42px; height: 42px; background: rgba(244, 63, 94, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.2);">
                    <i class="fas fa-university" style="font-size: 1.2rem;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: white;">Levantamento</h3>
                    <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Transferência Bancária</p>
                </div>
            </div>
            <button onclick="closeWithdrawModal()" class="btn-close-premium"><i class="fas fa-times"></i></button>
        </div>

        <div class="modal-body-premium">
            <!-- Informativo de Saldo: Transparência e Confiança -->
            <div style="background: linear-gradient(135deg, rgba(244, 63, 94, 0.08), rgba(244, 63, 94, 0.02)); padding: 1.5rem; border-radius: 18px; margin-bottom: 2rem; border: 1px solid rgba(244, 63, 94, 0.15); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; margin-bottom: 4px;">Saldo Disponível</span>
                    <strong style="font-size: 1.4rem; color: white; display: flex; align-items: center; gap: 5px;">
                        <?php echo number_format($current_balance, 0, ',', '.'); ?> <span style="font-size: 0.8rem; color: #f43f5e;">AKZ</span>
                    </strong>
                </div>
                <div style="opacity: 0.5;">
                    <i class="fas fa-wallet fa-2x"></i>
                </div>
            </div>
            
            <form id="withdrawForm">
                <!-- BLoco 1: Levantamento -->
                <div class="withdraw-section-block" style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 18px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--elite-orange); text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px;">
                        <i class="fas fa-coins" style="margin-right: 8px;"></i> Montante a Levantar
                    </label>
                    <div style="position: relative;">
                        <input type="number" name="amount" id="withdrawAmountInput" placeholder="Ex: 25.000" required min="1000" max="<?php echo $current_balance; ?>"
                               style="width: 100%; padding: 1.2rem 1.2rem 1.2rem 3rem; background: rgba(0,0,0,0.3); border: 1px solid var(--glass-border); border-radius: 14px; color: white; font-size: 1.2rem; font-weight: 800; outline: none; transition: 0.3s; font-family: 'Outfit', sans-serif;">
                        <span style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.3); font-size: 1.2rem; font-weight: 900;">$</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                        <small style="color: var(--text-muted); font-size: 0.7rem;">Mínimo: 1.000 AKZ • Taxa: 0%</small>
                        <small style="color: #ef4444; font-size: 0.7rem; cursor:pointer;" onclick="setWithdrawMax()">Usar Máximo</small>
                    </div>
                </div>

                <!-- Bloco 2: Destino Bancário -->
                <div class="withdraw-section-block" style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 18px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 2rem;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--elite-orange); text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px;">
                        <i class="fas fa-university" style="margin-right: 8px;"></i> Coordenadas Bancárias (IBAN)
                    </label>
                    
                    <?php if (!empty($user_iban)): ?>
                        <!-- IBAN já configurado e bloqueado -->
                        <div class="iban-locked-display" style="background: rgba(0,0,0,0.4); border: 1px solid rgba(16, 185, 129, 0.2); padding: 1.2rem; border-radius: 14px; position: relative; overflow: hidden;">
                            <textarea name="bank_details" readonly required
                                      style="width: 100%; background: none; border: none; color: #10b981; font-size: 1rem; font-weight: 800; outline: none; height: 50px; resize: none; font-family: 'Courier New', monospace; margin: 0;"><?php echo htmlspecialchars($user_iban); ?></textarea>
                            <div style="position: absolute; right: 1rem; top: 1rem; color: #10b981; opacity: 0.6;">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 8px; margin-top: 12px;">
                            <i class="fas fa-info-circle" style="color: var(--brand-primary); font-size: 0.8rem; margin-top: 2px;"></i>
                            <span style="font-size: 0.7rem; color: var(--text-muted); line-height: 1.4;">
                                <strong>IBAN Verificado</strong>. Por razões de segurança, o IBAN é permanente. Para alteração, contacte o suporte oficial da <strong>KALIYE Admin</strong>.
                            </span>
                        </div>
                    <?php else: ?>
                        <!-- IBAN pendente de configuração (Primeira vez) -->
                        <textarea name="bank_details" placeholder="AO06.0040.0000.XXXXXXXXXX.XX" required
                                  style="width: 100%; padding: 1.2rem; background: rgba(0,0,0,0.3); border: 1px solid var(--glass-border); border-radius: 14px; color: white; font-size: 0.95rem; font-weight: 600; outline: none; height: 70px; resize: none; font-family: 'Courier New', monospace;"></textarea>
                        <div style="display: flex; align-items: flex-start; gap: 8px; margin-top: 12px;">
                            <i class="fas fa-exclamation-triangle" style="color: #fbbf24; font-size: 0.8rem; margin-top: 2px;"></i>
                            <span style="font-size: 0.7rem; color: var(--text-muted); line-height: 1.4;">
                                Atenção: Verifique o IBAN cuidadosamente. Uma vez gravado, este será o seu destino de ganhos permanente até solicitação manual ao admin.
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Ação Final -->
                <button type="submit" class="btn-primary-premium" style="height: 64px; background: linear-gradient(135deg, #f43f5e, #e11d48); box-shadow: 0 15px 35px rgba(244, 63, 94, 0.3); border: none; font-size: 1rem; letter-spacing: 0.5px;">
                    <i class="fas fa-paper-plane" style="margin-right: 10px;"></i> EFECTUAR PEDIDO DE LEVANTAMENTO
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Upload Proof -->
<div id="uploadProofModal" class="modal-premium" style="display: none;">
    <div class="glass modal-content-premium">
        <div class="modal-header-premium">
            <h3 class="section-title" style="font-size: 1.2rem; margin:0;">
                <i class="fas fa-cloud-upload-alt" style="color: var(--brand-blue);"></i> Enviar Comprovativo
            </h3>
            <button onclick="closeUploadProofModal()" class="btn-close-premium"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body-premium">
            <div class="payment-summary-box">
                <div class="summary-item">
                    <span>Referência</span>
                    <strong id="modalReference" style="color: white;">---</strong>
                </div>
                <div class="summary-item">
                    <span>Valor total</span>
                    <strong id="modalAmount" style="color: var(--brand-primary); font-size: 1.1rem;">---</strong>
                </div>
            </div>
            
            <form id="walletUploadProofForm" enctype="multipart/form-data">
                <input type="hidden" id="modalInvestmentId" name="investment_id">
                <div class="input-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.7rem; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.8rem;">
                        Anexar Comprovativo (PDF ou Imagem)
                    </label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="proof_doc" accept="image/*,application/pdf" required id="proofInput">
                        <div class="file-upload-placeholder">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Clique para selecionar ou arraste o arquivo</span>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-primary-premium" style="background: linear-gradient(135deg, var(--brand-green), #059669);">
                    <i class="fas fa-check-circle"></i> CONFIRMAR ENVIO
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Payment Success (Reference Generated) -->
<div id="paymentSuccessModal" class="modal-premium" style="display: none;">
    <div class="glass modal-content-premium">
        <div class="modal-header-premium">
            <h3 class="modal-title-premium">
                <i class="fas fa-clipboard-check" style="color: var(--brand-primary);"></i> Referência de Depósito
            </h3>
            <button onclick="closePaymentSuccessModal()" class="btn-close-premium"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="modal-body-premium" style="text-align: center;">
            <div style="width: 64px; height: 64px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; border: 2px solid var(--brand-green); box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);">
                <i class="fas fa-check" style="color: var(--brand-green); font-size: 1.8rem;"></i>
            </div>
            
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 2rem; line-height: 1.6;">Use os dados abaixo para concluir o seu depósito. O saldo será creditado assim que o pagamento for confirmado.</p>
            
            <div class="payment-summary-box" style="margin-bottom: 2rem;">
                <div class="summary-item">
                    <span>Entidade</span>
                    <strong style="color: var(--brand-primary); font-size: 1.1rem;">00991</strong>
                </div>
                <div class="summary-item">
                    <span>Referência</span>
                    <strong id="successPaymentRef" style="color: white; font-size: 1.4rem; font-family: 'Courier New', monospace; letter-spacing: 2px;">--- --- ---</strong>
                </div>
                <div class="summary-item">
                    <span>Montante</span>
                    <strong id="successPaymentAmount" style="color: var(--brand-primary); font-size: 1.1rem;">--- AKZ</strong>
                </div>
            </div>
            
            <div style="background: rgba(59, 130, 246, 0.08); padding: 1rem; border-radius: 14px; border: 1px solid rgba(59, 130, 246, 0.15); margin-bottom: 2rem; display: flex; gap: 12px; align-items: flex-start; text-align: left;">
                <i class="fas fa-info-circle" style="color: var(--brand-blue); margin-top: 3px;"></i>
                <p style="font-size: 0.75rem; color: var(--surface-70); margin: 0; line-height: 1.5;">O processamento é automático para pagamentos via Multicaixa ou Express.</p>
            </div>

            <button onclick="closePaymentSuccessModal()" class="btn-primary-premium">
                 ENTENDIDO, VOU PAGAR
            </button>
        </div>
    </div>
</div>

<script>
    // --- CONTROLE GLOBAL DE MODAIS (WALLET) ---
    window.downloadLegalPDF = function() {
        window.print();
    }

    window.setWithdrawMax = function() {
        const input = document.getElementById('withdrawAmountInput');
        if(input) {
            input.value = "<?php echo (int)$current_balance; ?>";
            input.dispatchEvent(new Event('change'));
        }
    }
    window.openDepositModal = function() { 
        const m = document.getElementById('depositModal');
        if(m) m.style.display = 'flex';
        else console.error('Modal de depósito não encontrado no DOM');
    }
    
    window.closeDepositModal = function() { 
        const m = document.getElementById('depositModal');
        if(m) m.style.display = 'none'; 
    }

    window.openWithdrawModal = function() { 
        const m = document.getElementById('withdrawModal');
        if(m) m.style.display = 'flex'; 
    }
    
    window.closeWithdrawModal = function() { 
        const m = document.getElementById('withdrawModal');
        if(m) m.style.display = 'none'; 
    }

    window.openUploadProofModal = function(investmentId, reference, amount, currency) {
        const modal = document.getElementById('uploadProofModal');
        if(!modal) return;
        
        const elId = document.getElementById('modalInvestmentId');
        const elRef = document.getElementById('modalReference');
        const elAmt = document.getElementById('modalAmount');
        
        if(elId) elId.value = investmentId;
        if(elRef) elRef.innerText = reference || 'Pendente';
        if(elAmt) elAmt.innerText = amount + ' ' + (currency || 'AKZ');
        
        modal.style.display = 'flex';
    }
    
    window.closeUploadProofModal = function() { 
        const m = document.getElementById('uploadProofModal');
        if(m) m.style.display = 'none'; 
    }

    window.openPaymentSuccessModal = function(reference, amount) {
        const modal = document.getElementById('paymentSuccessModal');
        if (!modal) return;
        
        const elRef = document.getElementById('successPaymentRef');
        const elAmt = document.getElementById('successPaymentAmount');
        
        if(elRef) elRef.innerText = reference;
        if(elAmt) elAmt.innerText = amount + ' AKZ';
        
        modal.style.display = 'flex';
    }

    window.closePaymentSuccessModal = function() {
        const modal = document.getElementById('paymentSuccessModal');
        if(modal) modal.style.display = 'none';
    }
</script>
