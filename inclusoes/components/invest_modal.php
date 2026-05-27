<?php
// If payments are disabled globally, render a minimal placeholder and exit
$payments_config_path = __DIR__ . '/../../configuracoes/pagamentos.php';
if (file_exists($payments_config_path)) {
    $payments_cfg = require $payments_config_path;
    if (isset($payments_cfg['payments_enabled']) && $payments_cfg['payments_enabled'] === false) {
        echo "<!-- Investment modal disabled by pagamentos.php (payments_enabled = false) -->";
        return;
    }
}
?>

<!-- Investment Modal: Elite Capital Hub -->
<div id="investModal" class="elite-modal-overlay" style="display: none;">
    <div class="elite-modal-card" style="max-width: 650px;">
        <button onclick="closeInvestModal()" class="elite-modal-close">
            <i class="fas fa-times"></i>
        </button>

        <div class="elite-modal-body" style="padding: 3rem;">
            <div class="modal-header-elite" style="margin-bottom: 2.5rem;">
                <span class="elite-label-micro" style="color: var(--elite-orange);">CAPITAL HUB</span>
                <h2 class="elite-modal-title" style="margin-top: 5px;">Proposta de Investimento.</h2>
                <p class="elite-modal-text" style="font-size: 0.9rem; margin-bottom: 0;">A investir em: <strong id="investProjectTitle" style="color: #fff;"></strong></p>
            </div>

            <!-- Steps Progress -->
            <div class="elite-steps-progress" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 3rem;">
                <div class="elite-step active" id="step1Circle">1</div>
                <div class="elite-step-line"></div>
                <div class="elite-step" id="step2Circle">2</div>
                <span class="elite-label-micro" style="margin-left: auto; letter-spacing: 1px;">PROCESSO DE APORTE</span>
            </div>

            <div id="investStep1">
                <form id="generateRefForm" class="elite-form">
                    <input type="hidden" id="investProjectId" name="project_id">
                    
                    <div class="elite-form-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="elite-input-group">
                            <label class="elite-label-micro">VALOR DO INVESTIMENTO</label>
                            <input type="number" id="investAmount" name="amount" placeholder="Ex: 50.000" min="1" step="0.01" required class="elite-input-premium">
                        </div>
                        <div class="elite-input-group">
                            <label class="elite-label-micro">MOEDA</label>
                            <select id="investCurrency" name="currency" class="elite-input-premium">
                                <option value="AOA">AOA</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>

                    <div class="elite-input-group" style="margin-bottom: 1.5rem;">
                        <label class="elite-label-micro">MODELO DE APORTE</label>
                        <select id="investType" name="investment_type" onchange="toggleInvestFields(this.value)" class="elite-input-premium">
                            <option value="equity">Equity (Participação %)</option>
                            <option value="loan">Empréstimo (Com Retorno)</option>
                            <option value="donation">Apoio Filantrópico</option>
                        </select>
                    </div>

                    <div id="equityFields" class="elite-input-group" style="display: block; margin-bottom: 1.5rem;">
                        <label class="elite-label-micro">EQUITY SOLICITADA (%)</label>
                        <input type="number" name="equity_percentage" id="investEquityInput" placeholder="Ex: 10.0" step="0.1" min="0" max="100" class="elite-input-premium" oninput="checkEquityWarning(this.value)">
                        <!-- Contexto de equity disponível (preenchido via JS quando o modal abre) -->
                        <div id="equityAvailContext" style="display:none; margin-top:8px; padding:10px 14px; border-radius:10px; background:rgba(16,185,129,0.06); border:1px solid rgba(16,185,129,0.15);">
                            <span style="font-size:0.65rem; font-weight:800; color:#10b981; text-transform:uppercase; letter-spacing:0.5px;">
                                <i class="fas fa-info-circle" style="margin-right:5px;"></i>
                                O fundador disponibiliza até <strong id="equityAvailMax" style="font-size:0.9rem;">—</strong>% &nbsp;|&nbsp; Ainda disponível: <strong id="equityAvailLeft" style="font-size:0.9rem; color:#10b981;">—</strong>%
                            </span>
                        </div>
                        <div id="equityWarningMsg" style="display:none; margin-top:6px; padding:8px 12px; border-radius:8px; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); font-size:0.65rem; font-weight:800; color:#ef4444;">
                            <i class="fas fa-exclamation-triangle" style="margin-right:5px;"></i>
                            A equity solicitada excede o limite definido pelo fundador. Ajusta a tua proposta.
                        </div>
                    </div>

                    <div id="loanFields" style="display: none; margin-bottom: 1.5rem; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="elite-input-group">
                            <label class="elite-label-micro">TAXA DE JURO (%)</label>
                            <input type="number" name="expected_return_rate" placeholder="Ex: 8" step="0.1" class="elite-input-premium">
                        </div>
                        <div class="elite-input-group">
                            <label class="elite-label-micro">MATURIDADE</label>
                            <input type="date" name="maturity_date" class="elite-input-premium">
                        </div>
                    </div>

                    <div class="elite-input-group" style="margin-bottom: 2rem;">
                        <label class="elite-label-micro">TERMOS E CONDIÇÕES ADICIONAIS</label>
                        <textarea name="terms" rows="3" placeholder="Ex: Investimento sujeito a auditoria técnica..." class="elite-input-premium" style="resize: none;"></textarea>
                    </div>

                    <button type="submit" class="btn-invest-elite" style="width: 100%; height: 60px;">
                        <i class="fas fa-receipt" style="margin-right: 10px;"></i> GERAR REFERÊNCIA DE PAGAMENTO
                    </button>
                </form>
            </div>

            <div id="investStep2" style="display: none;">
                <div class="elite-payment-slip" style="background: #fff; padding: 2rem; border-radius: 20px; margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
                    <div style="text-align: center; margin-bottom: 1.5rem; border-bottom: 1px dashed #ddd; padding-bottom: 1.5rem;">
                        <img src="recursos/images/multicaixa_logo.png" style="height: 25px; filter: grayscale(1);" onerror="this.style.display='none'">
                        <h4 style="margin: 0.5rem 0 0; font-size: 0.8rem; color: #1e293b; font-weight: 900;">PAGAMENTO DE SERVIÇOS</h4>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span style="font-size: 0.65rem; color: #888; letter-spacing: 1px; font-weight: 700;">ENTIDADE</span>
                        <span style="font-weight: 900; font-family: 'Courier New', monospace; font-size: 1.2rem; color: #000;">00001</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span style="font-size: 0.65rem; color: #888; letter-spacing: 1px; font-weight: 700;">REFERÊNCIA</span>
                        <span id="paymentRef" style="font-weight: 900; font-family: 'Courier New', monospace; font-size: 1.2rem; color: #000; letter-spacing: 1.5px;">000 000 000</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-size: 0.65rem; color: #888; letter-spacing: 1px; font-weight: 700;">MONTANTE</span>
                        <span id="paymentAmountText" style="font-weight: 900; font-size: 1.2rem; color: #000;">0,00 AOA</span>
                    </div>
                </div>

                <form id="submitProofForm" class="elite-form">
                    <input type="hidden" id="proofInvestmentId" name="investment_id">
                    <div class="elite-input-group" style="margin-bottom: 2rem;">
                        <label class="elite-label-micro">SUBMETE O COMPROVATIVO DE APORTE</label>
                        <div class="elite-upload-box">
                            <input type="file" name="proof_file" accept="image/*,application/pdf" required class="elite-file-input">
                            <div class="upload-vibe">
                                <i class="fas fa-cloud-upload" style="font-size: 2rem; color: var(--elite-orange);"></i>
                                <span>Anexar Comprovativo</span>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-invest-elite" style="width: 100%; height: 60px; background: var(--elite-orange); border-color: var(--elite-orange); color: #000;">
                        <i class="fas fa-check-circle" style="margin-right: 10px;"></i> CONFIRMAR SUBMISSÃO ELITE
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.elite-steps-progress { position: relative; }
.elite-step {
    width: 32px; height: 32px; border-radius: 50%; background: var(--elite-card-border);
    color: var(--elite-text-muted); display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem; font-weight: 800; border: 2px solid transparent; transition: 0.3s;
}
.elite-step.active { background: var(--elite-orange); color: #000; border-color: var(--surface-20); }
.elite-step-line { flex: 0.5; height: 2px; background: var(--elite-card-border); }
</style>
