<?php
/**
 * wallet_scripts.php - Interactive Logic for Wallet Dashboard
 */
?>
<script>
// --- MODAL CONTROLS ARE HANDLED GLOBALLY BY wallet_modals.php ---


document.getElementById('withdrawForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    const originalHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESSANDO...';

    fetch('../interface_programacao/wallet/request_withdrawal.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeWithdrawModal();
            Swal.fire({
                title: 'Solicitação Enviada!',
                text: data.message,
                icon: 'success',
                background: 'rgba(13, 22, 40, 0.98)',
                color: '#fff',
                confirmButtonColor: 'var(--brand-primary)'
            }).then(() => location.reload());
        } else {
            Swal.fire('Erro', data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(err => {
        Swal.fire('Erro', 'Falha ao comunicar com o servidor.', 'error');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});

// --- DEPOSIT FLOW ---
document.getElementById('depositForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    const originalHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> GERANDO...';

    fetch('../interface_programacao/wallet/generate_payment.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeDepositModal();
            // Usar o novo modal premium de sucesso em vez do Swal genérico
            if (typeof openPaymentSuccessModal === 'function') {
                openPaymentSuccessModal(data.reference, data.amount);
            } else {
                // Fallback caso o modal não esteja no DOM
                Swal.fire({
                    title: 'Referência Gerada!',
                    html: `<div style="text-align: left; padding: 1rem; background: rgba(0,0,0,0.2); border-radius: 16px;">
                            Referência: <b style="color: white;">${data.reference}</b>
                           </div>`,
                    icon: 'success'
                }).then(() => location.reload());
            }
        } else {
            Swal.fire('Erro', data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});

// --- PROOF UPLOAD FLOW ---
document.getElementById('walletUploadProofForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> ENVIANDO...';

    fetch('../interface_programacao/projects/upload_investment_proof.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Sucesso!',
                text: data.message,
                icon: 'success',
                background: 'rgba(13, 22, 40, 0.98)',
                color: '#fff',
                confirmButtonColor: 'var(--brand-green)'
            }).then(() => location.reload());
        } else {
            Swal.fire('Erro', data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = 'CONFIRMAR ENVIO DO DOCUMENTO';
        }
    })
    .catch(err => {
        Swal.fire('Erro', 'Erro ao processar upload.', 'error');
        btn.disabled = false;
    });
});

// --- FILTERS ---
function filterTransactions(type, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const items = document.querySelectorAll('.transaction-item');
    items.forEach(item => {
        if (type === 'all' || item.getAttribute('data-type') === type) {
            item.style.display = 'grid';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>
