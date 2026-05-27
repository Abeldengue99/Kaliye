<?php
/**
 * Component: Mentor Application Modal
 */
?>
<div id="mentorAppModal" class="auth-container" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 100000; justify-content: center; align-items: center;">
    <div class="login-card glass" style="max-width: 550px; width: 100%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h3 style="margin: 0; color: var(--accent-gold);"><i class="fas fa-user-graduate"></i> Candidatura a Mentor</h3>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">Torne-se uma referência na KALIYE.</p>
            </div>
            <button onclick="closeMentorAppModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5rem;">&times;</button>
        </div>

        <div style="background: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <p style="font-size: 0.85rem; color: var(--accent-gold); margin: 0; line-height: 1.5;">
                <i class="fas fa-award"></i> <strong>Padrão de Qualidade:</strong> Na KALIYE, mentores são exemplos. Solicitamos estas informações para validar a sua autoridade no mercado angolano.
            </p>
        </div>

        <form id="mentorAppForm" method="POST" enctype="multipart/form-data">
            <div class="input-group" style="margin-bottom: 1.2rem;">
                <label>Área de Especialidade Principal</label>
                <input type="text" name="specialty" placeholder="Ex: Engenharia de Software, Gestão de Projetos..." required style="width: 100%; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; padding: 0.8rem;">
            </div>

            <div class="input-group" style="margin-bottom: 1.2rem;">
                <label>Anos de Experiência Relevante</label>
                <input type="number" name="experience_years" min="1" required style="width: 100%; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; padding: 0.8rem;">
            </div>

            <div class="input-group" style="margin-bottom: 1.2rem;">
                <label>Perfil do LinkedIn (Obrigatório para Referência)</label>
                <input type="url" name="linkedin_url" placeholder="https://www.linkedin.com/in/seu-perfil" required style="width: 100%; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; padding: 0.8rem;">
            </div>

            <div class="input-group" style="margin-bottom: 1.5rem;">
                <label>Curriculum Vitae / Portfólio (PDF)</label>
                <div style="border: 2px dashed var(--glass-border); padding: 1.5rem; border-radius: 12px; text-align: center;">
                    <i class="fas fa-file-pdf" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 0.5rem;"></i>
                    <input type="file" name="cv_file" accept=".pdf" required style="display: block; margin: 0 auto; color: var(--text-secondary); font-size: 0.85rem;">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 1rem; background: var(--accent-gold); color: black; font-weight: 700;">
                Submeter para Aprovação <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
function openMentorAppModal() {
    const modal = document.getElementById('mentorAppModal');
    if (!modal) return;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeMentorAppModal() {
    const modal = document.getElementById('mentorAppModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function() {
    const mentorAppForm = document.getElementById('mentorAppForm');
    if (!mentorAppForm || mentorAppForm.dataset.bound === '1') return;

    mentorAppForm.dataset.bound = '1';
    mentorAppForm.onsubmit = function(e) {
        e.preventDefault();

        const btn = this.querySelector('button[type="submit"]');
        const originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Enviando...';
            btn.disabled = true;
        }

        fetch('<?php echo $base_url; ?>interface_programacao/mentorship/submit_mentor_application.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: data.message,
                    background: '#0d1628',
                    color: '#fff'
                }).then(() => location.reload());
                return;
            }

            Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Nao foi possivel enviar a candidatura.' });
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha de comunicacao ao enviar a candidatura.' });
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
    };
});
</script>
