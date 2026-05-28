<?php
// Phase 1: Modal de investimento é uma Candidatura (não requer pagamentos ativos)
?>

<!-- Investment Modal: Candidatura de Investimento (Wizard Compacto) -->
<div id="investModal" class="elite-modal-overlay" style="display: none;">
    <div class="elite-modal-card" style="max-width: 480px; margin: 1rem;">
        <button onclick="closeInvestModal()" class="elite-modal-close" type="button">
            <i class="fas fa-times"></i>
        </button>

        <div class="elite-modal-body" style="padding: 1.5rem 1.5rem 2rem;">
            <!-- Header -->
            <div style="margin-bottom: 1rem;">
                <span class="elite-label-micro" style="color: var(--elite-orange); font-size: 0.6rem; letter-spacing: 1px;">CANDIDATURA DE INVESTIMENTO</span>
                <h2 class="elite-modal-title" style="margin-top: 4px; font-size: 1.2rem; margin-bottom: 0;">Proposta de Investimento</h2>
                <p class="elite-modal-text" style="font-size: 0.8rem; margin-bottom: 0;">Projecto: <strong id="investProjectTitle" style="color: #fff;"></strong></p>
            </div>

            <!-- Indicador de Passos -->
            <div class="wizard-steps" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
                <div id="investStepIndicator1" style="flex: 1; height: 4px; background: var(--accent-orange); border-radius: 2px; transition: 0.3s;"></div>
                <div id="investStepIndicator2" style="flex: 1; height: 4px; background: var(--surface-8); border-radius: 2px; transition: 0.3s;"></div>
            </div>

            <form id="generateRefForm" class="elite-form">
                <input type="hidden" id="investProjectId" name="project_id">
                <?php echo getCSRFHiddenInput(); ?>
                
                <!-- PASSO 1: Estrutura Financeira -->
                <div id="investStep1">
                    <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 0.6rem;">
                        <i class="fas fa-money-bill-wave" style="color: #60a5fa; margin-top: 2px; font-size: 0.9rem; flex-shrink: 0;"></i>
                        <p style="margin: 0; font-size: 0.72rem; color: #93c5fd; font-weight: 600; line-height: 1.5;">
                            Defina o valor e a modalidade do seu aporte. Estes dados servem de base para negociação.
                        </p>
                    </div>

                    <!-- Valor + Moeda -->
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                        <div class="elite-input-group">
                            <label class="elite-label-micro" style="font-size: 0.6rem;">VALOR DO INVESTIMENTO</label>
                            <input type="number" id="investAmount" name="amount" placeholder="Ex: 50000" min="1" step="0.01" required class="elite-input-premium" style="padding: 0.7rem; font-size: 0.85rem;">
                        </div>
                        <div class="elite-input-group">
                            <label class="elite-label-micro" style="font-size: 0.6rem;">MOEDA</label>
                            <select id="investCurrency" name="currency" class="elite-input-premium" style="padding: 0.7rem; font-size: 0.85rem;">
                                <option value="AOA">AOA</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>

                    <!-- Modelo de Aporte -->
                    <div class="elite-input-group" style="margin-bottom: 1rem;">
                        <label class="elite-label-micro" style="font-size: 0.6rem;">MODELO DE APORTE</label>
                        <select id="investType" name="investment_type" onchange="toggleInvestFields(this.value)" class="elite-input-premium" style="padding: 0.7rem; font-size: 0.85rem;">
                            <option value="equity">Equity (Participação %)</option>
                            <option value="loan">Empréstimo (Com Retorno)</option>
                            <option value="donation">Apoio Filantrópico</option>
                        </select>
                    </div>

                    <!-- Equity Fields -->
                    <div id="equityFields" class="elite-input-group" style="display: block; margin-bottom: 1rem;">
                        <label class="elite-label-micro" style="font-size: 0.6rem;">EQUITY SOLICITADA (%)</label>
                        <input type="number" name="equity_percentage" id="investEquityInput" placeholder="Ex: 10.0" step="0.1" min="0" max="100" class="elite-input-premium" style="padding: 0.7rem; font-size: 0.85rem;" oninput="checkEquityWarning(this.value)">
                        <div id="equityAvailContext" style="display:none; margin-top:6px; padding:8px 10px; border-radius:8px; background:rgba(16,185,129,0.06); border:1px solid rgba(16,185,129,0.15);">
                            <span style="font-size:0.6rem; font-weight:800; color:#10b981; text-transform:uppercase; letter-spacing:0.3px;">
                                <i class="fas fa-info-circle" style="margin-right:4px;"></i>
                                Disponível até <strong id="equityAvailMax" style="font-size:0.8rem;">—</strong>% &nbsp;|&nbsp; Restante: <strong id="equityAvailLeft" style="font-size:0.8rem; color:#10b981;">—</strong>%
                            </span>
                        </div>
                        <div id="equityWarningMsg" style="display:none; margin-top:5px; padding:6px 10px; border-radius:6px; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); font-size:0.6rem; font-weight:800; color:#ef4444;">
                            <i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i>
                            A equity solicitada excede o limite definido pelo fundador.
                        </div>
                    </div>

                    <!-- Loan Fields -->
                    <div id="loanFields" style="display: none; margin-bottom: 1rem; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="elite-input-group">
                            <label class="elite-label-micro" style="font-size: 0.6rem;">TAXA DE JURO (%)</label>
                            <input type="number" name="expected_return_rate" placeholder="Ex: 8" step="0.1" class="elite-input-premium" style="padding: 0.7rem; font-size: 0.85rem;">
                        </div>
                        <div class="elite-input-group">
                            <label class="elite-label-micro" style="font-size: 0.6rem;">MATURIDADE</label>
                            <input type="date" name="maturity_date" class="elite-input-premium" style="padding: 0.7rem; font-size: 0.85rem;">
                        </div>
                    </div>

                    <button type="button" onclick="nextInvestStep()" class="btn-invest-elite" style="width: 100%; height: 46px; font-size: 0.85rem; border-radius: 12px;">
                        Continuar <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                    </button>
                </div>

                <!-- PASSO 2: Motivação e Envio -->
                <div id="investStep2" style="display: none;">
                    <div style="background: rgba(247, 148, 29, 0.08); border: 1px solid rgba(247, 148, 29, 0.2); padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 0.6rem;">
                        <i class="fas fa-lightbulb" style="color: #f7941d; margin-top: 2px; font-size: 0.9rem; flex-shrink: 0;"></i>
                        <p style="margin: 0; font-size: 0.72rem; color: #fbcfe8; font-weight: 600; line-height: 1.5;">
                            Partilhe a sua visão. A motivação e experiência que traz podem ser mais valiosas que o capital.
                        </p>
                    </div>

                    <!-- Motivação -->
                    <div class="elite-input-group" style="margin-bottom: 1.25rem;">
                        <label class="elite-label-micro" style="color: #f7941d; font-size: 0.6rem;">PORQUÊ INVESTIR? <span style="color: #ef4444;">*</span></label>
                        <textarea id="investorMotivation" name="investor_motivation" rows="4" placeholder="O que o atraiu neste projecto? Que valor ou expertise pode trazer além do capital financeiro?" class="elite-input-premium" style="resize: none; padding: 0.7rem; font-size: 0.85rem; line-height: 1.5;"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 0.5rem;">
                        <button type="button" onclick="prevInvestStep()" class="btn-primary" style="background: var(--surface-8); border: none; font-weight: 700; border-radius: 12px;">
                            Voltar
                        </button>
                        <button type="submit" class="btn-invest-elite" style="height: 46px; font-size: 0.85rem; border-radius: 12px;">
                            <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Submeter
                        </button>
                    </div>
                    
                    <p style="text-align: center; font-size: 0.65rem; color: rgba(255,255,255,0.3); margin-top: 1rem; line-height: 1.4;">
                        <i class="fas fa-lock" style="margin-right: 3px;"></i> 
                        Proposta sujeita a análise da equipa KALIYE.
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function nextInvestStep() {
    const amount = document.getElementById('investAmount').value;
    if (!amount || amount <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo Obrigatório',
            text: 'Indique um valor válido para avançar.',
            background: '#111827', color: '#fff'
        });
        return;
    }
    
    document.getElementById('investStep1').style.display = 'none';
    document.getElementById('investStep2').style.display = 'block';
    
    document.getElementById('investStepIndicator1').style.background = 'var(--surface-8)';
    document.getElementById('investStepIndicator2').style.background = 'var(--accent-orange)';
}

function prevInvestStep() {
    document.getElementById('investStep2').style.display = 'none';
    document.getElementById('investStep1').style.display = 'block';
    
    document.getElementById('investStepIndicator2').style.background = 'var(--surface-8)';
    document.getElementById('investStepIndicator1').style.background = 'var(--accent-orange)';
}

// Sobrescreve o openInvestModal para resetar os passos e garantir validação estrita
const originalOpenInvestModal = window.openInvestModal;

// Handler de submissão do formulário de investimento
document.addEventListener('DOMContentLoaded', function() {
    const generateRefForm = document.getElementById('generateRefForm');
    if (generateRefForm) {
        generateRefForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A enviar...';
            btn.disabled = true;

            const fd = new FormData(this);
            fetch((typeof BASE_URL !== 'undefined' ? BASE_URL : '../../') + 'interface_programacao/projects/invest_project.php', { method: 'POST', body: fd })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(txt => { throw new Error('HTTP ' + res.status + ': ' + txt.substring(0, 200)); });
                    }
                    return res.text();
                })
                .then(text => {
                    let data;
                    try { data = JSON.parse(text); } catch(e) { throw new Error('Resposta inválida do servidor: ' + text.substring(0, 300)); }
                    if (data.success) {
                        if (typeof closeInvestModal === 'function') closeInvestModal();
                        Swal.fire({
                            icon: 'success',
                            title: 'Proposta Enviada!',
                            html: `
                                <div style="text-align: left; padding: 0.5rem;">
                                    <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.6;">
                                        ${data.message}
                                    </p>
                                    <div style="margin-top: 1rem; padding: 1rem; background: rgba(16,185,129,0.08); border-radius: 12px; border: 1px solid rgba(16,185,129,0.2);">
                                        <p style="margin: 0; font-size: 0.8rem; color: #10b981; font-weight: 700;">
                                            <i class="fas fa-check-circle"></i> A equipa KALIYE irá avaliar a sua candidatura e entrar em contacto.
                                        </p>
                                    </div>
                                </div>
                            `,
                            background: '#0d1628',
                            color: '#fff',
                            confirmButtonColor: '#f7941d',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        Swal.fire({
                            title: 'Atenção',
                            text: data.message || 'Erro desconhecido.',
                            icon: 'warning',
                            background: '#1e293b',
                            color: '#fff'
                        });
                    }
                })
                .catch((err) => {
                    Swal.fire({
                        title: 'Erro',
                        text: err.message || 'Falha de comunicação com o servidor.',
                        icon: 'error',
                        background: '#1e293b',
                        color: '#fff'
                    });
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        });
    }
});

window.openInvestModal = function(projectId, projectTitle) {
    // 1. Verificação Estrita de Perfil (Investidor Aprovado)
    const uType = window.AKSANITI_USER ? window.AKSANITI_USER.type : '';
    const vStatus = window.AKSANITI_USER ? window.AKSANITI_USER.verificationStatus : 'unsubmitted';
    
    if (uType !== 'admin') {
        if (vStatus !== 'verified' || uType !== 'investor') {
            Swal.fire({
                title: 'Acesso Restrito',
                html: '<p style="color: var(--surface-70);">O seu perfil precisa ser verificado e aprovado como Investidor para submeter propostas.</p>',
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Verificar Perfil',
                cancelButtonText: 'Fechar',
                confirmButtonColor: '#f7941d',
                background: '#111827',
                color: '#fff',
                borderRadius: '24px'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (typeof openKYCModal === 'function') openKYCModal();
                    else window.location.href = `${BASE_URL}paginas/social/profile.php`;
                }
            });
            return; // Bloqueia a abertura do modal
        }
    }

    // 2. Reset steps
    document.getElementById('investStep1').style.display = 'block';
    document.getElementById('investStep2').style.display = 'none';
    document.getElementById('investStepIndicator1').style.background = 'var(--accent-orange)';
    document.getElementById('investStepIndicator2').style.background = 'var(--surface-8)';
    
    // 3. Executa a função original
    if (originalOpenInvestModal) {
        originalOpenInvestModal(projectId, projectTitle);
    }
}
</script>
