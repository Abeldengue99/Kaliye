<script>
/**
 * profile_scripts.php - Motor de Interação de Perfil Aksanti
 * Focado na sincronização com o Modal Elite (Hub de Confiança).
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log("[AKSANTI] Scripts de Perfil Unificados ativos.");
    
    // Mentor Application Form
    function initMentorForm() {
        const mentorAppForm = document.getElementById('mentorAppForm');
        if (mentorAppForm) {
            mentorAppForm.onsubmit = function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Enviando...';
                btn.disabled = true;
                
                fetch(`${BASE_URL}interface_programacao/mentorship/submit_mentor_application.php`, { 
                    method: 'POST', 
                    body: new FormData(this) 
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({ icon: 'success', title: 'Sucesso!', text: data.message, background: '#0d1628', color: '#fff' })
                        .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro', text: data.message });
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                });
            };
        }
    }
    initMentorForm();
});

/**
 * Função Global para tratar conexões sociais
 */
function handleConnection(targetId, action, btn) {
    if(typeof enforceKYC === 'function' && !enforceKYC()) return;
    
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('target_id', targetId);
    formData.append('action', action);
    
    fetch(`${BASE_URL}interface_programacao/user/connection_action.php`, { 
        method: 'POST', 
        body: formData 
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            Swal.fire({ icon: 'success', title: 'Concluído', text: data.message, background: '#0d1628', color: '#fff', timer: 1500, showConfirmButton: false })
            .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Erro', text: data.message });
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    });
}
</script>
