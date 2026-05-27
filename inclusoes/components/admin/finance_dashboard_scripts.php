<script>
/**
 * Admin Finance Dashboard Scripts
 * Handles Investment processing and UI feedback.
 */
function rejectInvestment(id) {
    Swal.fire({
        title: 'Rejeitar Investimento',
        text: 'Selecione o motivo da rejeição:',
        input: 'select',
        inputOptions: {
            'fraud': 'Comprovativo Falso / Inválido',
            'other': 'Outros Motivos'
        },
        inputPlaceholder: 'Selecione um motivo',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Rejeitar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const reason = result.value;
            if(!reason) return;

            const formData = new FormData();
            formData.append('investment_id', id);
            formData.append('status', 'rejected');
            formData.append('reason_code', reason);

            fetch('../../interface_programacao/admin/admin_process_investment.php', { method: 'POST', body: formData })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(data => {
                    if(data.success) {
                        Swal.fire('Rejeitado', 'Investimento rejeitado e notificações enviadas.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('Erro', err.message || 'Falha de comunicacao com o servidor.', 'error'));
        }
    });
}

function processInvestment(id, status) {
    let msg = status === 'approved' ? 'aprovar' : 'marcar como pago';
    
    Swal.fire({
        title: 'Tem certeza?',
        text: `Deseja realmente ${msg} este investimento?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: status === 'approved' ? '#10b981' : '#3b82f6',
        confirmButtonText: 'Sim, confirmar!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('investment_id', id);
            formData.append('status', status);

            fetch('../../interface_programacao/admin/admin_process_investment.php', { method: 'POST', body: formData })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(data => {
                    if(data.success) {
                        Swal.fire('Sucesso!', 'Ação realizada com sucesso.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('Erro', err.message || 'Falha de comunicacao com o servidor.', 'error'));
        }
    });
}

// AI Analysis Wrapper
async function analyzeProof(id, amount, currency, filePath, projectName) {
    const row = document.getElementById(`analysis-row-${id}`);
    const box = document.getElementById(`analysis-box-${id}`);
    
    row.style.display = 'table-row';
    box.style.display = 'block';
    box.innerHTML = '<div style="display: flex; align-items: center; gap: 10px; color: #8b5cf6;"><i class="fas fa-circle-notch fa-spin"></i> A IA está a analisar o documento (OCR em progresso)...</div>';

    try {
        const fullPath = '../' + filePath;
        // analyzeProofLogic defined in admin_ai_engine.js
        if (typeof analyzeProofLogic === 'undefined') {
            throw new Error("Motor de IA não carregado corretamente.");
        }
        
        const result = await analyzeProofLogic(id, amount, currency, fullPath);
        window[`result_${id}`] = result;

        let html = `
            <div style="display: flex; gap: 20px; width: 100%; color: white;">
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <h4 style="margin: 0; color: #a78bfa;"><i class="fas fa-robot"></i> Resultado da Análise IA</h4>
                        <button onclick="downloadAnalysisReport(${id}, window['result_${id}'], '${projectName.replace(/'/g, "\\'")}')" class="btn-action" style="width: auto; padding: 0 10px; font-size: 0.7rem;">
                            <i class="fas fa-download"></i> Baixar Relatório
                        </button>
                    </div>
                    <div style="grid-template-columns: 1fr 1fr; display: grid; gap: 10px; font-size: 0.85rem;">
                        <div><strong style="color: #94a3b8;">Banco:</strong> ${result.bank}</div>
                        <div><strong style="color: #94a3b8;">Confiança:</strong> ${result.confidence}%</div>
                        <div><strong style="color: #94a3b8;">Valor Detetado:</strong> ${result.amount ? result.amount.toLocaleString('pt-AO') + ' ' + currency : 'N/D'}</div>
                        <div><strong style="color: #94a3b8;">Data:</strong> ${result.date || 'N/D'}</div>
                    </div>
                </div>
                <div style="flex: 1; border-left: 1px solid var(--surface-10); padding-left: 20px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 0.9rem;"><i class="fas fa-tasks"></i> Verificações</h4>
                    <ul style="margin: 0; padding: 0 0 0 15px; font-size: 0.8rem; line-height: 1.6;">
                        ${result.matches.map(m => `<li style="color: #10b981;">${m}</li>`).join('')}
                        ${result.warnings.map(w => `<li style="color: #fbbf24;">${w}</li>`).join('')}
                    </ul>
                </div>
            </div>
        `;
        box.innerHTML = html;
        box.style.borderColor = result.confidence > 80 ? '#10b981' : '#f59e0b';

    } catch (error) {
        box.innerHTML = `<div style="color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Erro: ${error.message}</div>`;
    }
}
</script>

