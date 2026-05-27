<?php
/**
 * profile_settings_content.php - Configurações de Perfil Integradas
 */
?>
<div class="tab-content-section">
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-user-cog"></i> Configurações de Conta</h3>
        </div>
        
        <form id="integratedProfileForm" class="elite-form-integrated" style="margin-top: 2rem;">
            <input type="hidden" name="action" value="update_profile">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 2rem;">
                <div class="elite-input-group">
                    <label style="color:rgba(255,255,255,0.4); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Nome Completo</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="kyc-input-v3" style="background: rgba(255,255,255,0.02); padding-left: 1.5rem;">
                </div>
                <div class="elite-input-group">
                    <label style="color:rgba(255,255,255,0.4); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Organização / Instituição</label>
                    <input type="text" name="organization" value="<?php echo htmlspecialchars($user['organization'] ?? ''); ?>" class="kyc-input-v3" style="background: rgba(255,255,255,0.02); padding-left: 1.5rem;">
                </div>
            </div>

            <div class="elite-input-group" style="margin-bottom: 2rem;">
                <label style="color:rgba(255,255,255,0.4); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Biografia Profissional</label>
                <textarea name="bio" rows="5" class="kyc-input-v3" style="background: rgba(255,255,255,0.02); padding-left: 1.5rem; resize: vertical;"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 2.5rem;">
                <div class="elite-input-group">
                    <label style="color:rgba(255,255,255,0.4); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Localização (Província)</label>
                    <select name="location" class="kyc-input-v3" style="background: rgba(255,255,255,0.02); padding-left: 1rem;">
                        <option value="Luanda" <?php echo ($user['location'] == 'Luanda') ? 'selected' : ''; ?>>Luanda</option>
                        <option value="Benguela" <?php echo ($user['location'] == 'Benguela') ? 'selected' : ''; ?>>Benguela</option>
                        <option value="Huíla" <?php echo ($user['location'] == 'Huíla') ? 'selected' : ''; ?>>Huíla</option>
                        <option value="Cabinda" <?php echo ($user['location'] == 'Cabinda') ? 'selected' : ''; ?>>Cabinda</option>
                        <!-- Outras províncias podem ser adicionadas aqui -->
                    </select>
                </div>
                <div class="elite-input-group">
                    <label style="color:rgba(255,255,255,0.4); font-size:0.7rem; font-weight:800; text-transform:uppercase;">LinkedIn</label>
                    <input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($user['linkedin_url'] ?? ''); ?>" class="kyc-input-v3" placeholder="https://linkedin.com/in/..." style="background: rgba(255,255,255,0.02); padding-left: 1.5rem;">
                </div>
            </div>

            <button type="submit" class="btn-cover-primary" style="width: 100%; height: 60px; font-size: 1rem; border-radius: 18px;">
                <i class="fas fa-save"></i> GUARDAR ALTERAÇÕES
            </button>
        </form>
    </div>

    <!-- Segurança Avançada -->
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-lock"></i> Segurança & Credenciais</h3>
        </div>
        <p style="color: rgba(255,255,255,0.5); font-size: 0.85rem; margin-bottom: 1.5rem;">Altere a sua palavra-passe ou gerencie sessões ativas.</p>
        <button class="btn-cover-ghost" style="width: 100%; padding: 1rem; border-color: rgba(255,255,255,0.1);">
            ALTERAR PALAVRA-PASSE
        </button>
    </div>
</div>

<script>
/**
 * Handler de Submissão Integrada
 */
document.getElementById('integratedProfileForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const original = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SINCRONIZANDO...';
    btn.disabled = true;

    const fd = new FormData(this);
    fetch('../../interface_programacao/user/update_profile.php', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            Swal.fire({ icon: 'success', title: 'Perfil Atualizado!', background: '#0d1628', color: '#fff', timer: 2000, showConfirmButton: false });
            setTimeout(() => location.reload(), 1500);
        } else {
            Swal.fire({ icon: 'error', title: 'Erro', text: data.message, background: '#0d1628', color: '#fff' });
            btn.innerHTML = original;
            btn.disabled = false;
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'Erro de Conexão', background: '#0d1628', color: '#fff' });
        btn.innerHTML = original;
        btn.disabled = false;
    });
});
</script>
