<?php
/**
 * profile_kyc_content.php - Secção de Verificação Integrada
 * Migrado do antigo kyc_modal.php para a página de perfil unificada.
 */
$_utype = $user['user_type'] ?? 'student';
$_is_peer = $user['is_peer_mentor'] ?? false;
$_id_number = $user['id_number'] ?? '';
$is_verified = (($user['verification_status'] ?? 'unsubmitted') === 'verified');
?>

<div class="tab-content-section">
    <!-- Status Card -->
    <div class="content-card" style="border-left: 4px solid <?php echo $is_verified ? '#10b981' : '#f7941d'; ?>;">
        <div class="content-card-header">
            <h3><i class="fas fa-id-card"></i> Estado da Identidade</h3>
            <span class="elite-label-micro" style="background: <?php echo $is_verified ? 'rgba(16, 185, 129, 0.1)' : 'rgba(247, 148, 29, 0.1)'; ?>; color: <?php echo $is_verified ? '#10b981' : '#f7941d'; ?>;">
                <?php echo $is_verified ? 'VERIFICADO' : 'PENDENTE'; ?>
            </span>
        </div>
        <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-top: 10px;">
            <?php if($is_verified): ?>
                A sua conta possui o selo de confiança KALIYE. A sua identidade foi validada com sucesso.
            <?php else: ?>
                Para desbloquear funcionalidades financeiras e mentoria, por favor submeta o seu documento de identidade angolano.
            <?php endif; ?>
        </p>
    </div>

    <?php if(!$is_verified): ?>
    <!-- Form Card -->
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-upload"></i> Submissão de Dossier</h3>
        </div>
        
        <form id="profileKycUploadForm" class="elite-form-integrated" enctype="multipart/form-data" style="margin-top: 2rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 2rem;">
                <div class="elite-input-group">
                    <label style="color:rgba(255,255,255,0.4); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Nº de Identidade (BI)</label>
                    <input type="text" name="id_number" value="<?php echo htmlspecialchars($_id_number); ?>" class="kyc-input-v3" placeholder="Ex: 004123456LA041" style="background: rgba(255,255,255,0.02);">
                </div>
                <div class="elite-input-group">
                    <label style="color:rgba(255,255,255,0.4); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Tipo de Documento</label>
                    <select name="id_type" class="kyc-input-v3" style="background: rgba(255,255,255,0.02); padding-left: 1rem;">
                        <option value="bi">Bilhete de Identidade (AO)</option>
                        <option value="passport">Passaporte</option>
                        <option value="residence">Cartão de Residência</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 2.5rem;">
                <div class="kyc-upload-box-v3">
                    <label>Frente do BI</label>
                    <div class="upload-drop-zone">
                        <i class="fas fa-id-card main-icon"></i>
                        <input type="file" name="bi_front" accept="image/*" onchange="handleProfileKycPreview(this)">
                        <div class="file-preview"></div>
                    </div>
                </div>
                <div class="kyc-upload-box-v3">
                    <label>Verso do BI</label>
                    <div class="upload-drop-zone">
                        <i class="fas fa-id-card main-icon"></i>
                        <input type="file" name="bi_back" accept="image/*" onchange="handleProfileKycPreview(this)">
                        <div class="file-preview"></div>
                    </div>
                </div>
                <div class="kyc-upload-box-v3">
                    <label>Selfie Probatória</label>
                    <div class="upload-drop-zone">
                        <i class="fas fa-camera main-icon"></i>
                        <input type="file" name="selfie" accept="image/*" onchange="handleProfileKycPreview(this)">
                        <div class="file-preview"></div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-cover-primary" style="width: 100%; height: 60px; font-size: 1rem; border-radius: 18px;">
                <i class="fas fa-paper-plane"></i> SUBMETER PARA VALIDAÇÃO
            </button>
        </form>
    </div>
    <?php else: ?>
    <!-- History/Audit Card -->
    <div class="content-card" style="opacity: 0.7;">
        <div class="content-card-header">
            <h3><i class="fas fa-history"></i> Histórico de Auditoria</h3>
        </div>
        <div style="padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 12px; font-size: 0.85rem;">
            <p><i class="fas fa-check-double" style="color: #10b981;"></i> Documentação aprovada em <?php echo date('d/m/Y', strtotime($user['updated_at'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
/**
 * Inicializa os handlers de upload para a aba de KYC
 */
function initProfileKycHandlers() {
    console.log("[AKSANITI] Handlers de KYC inicializados.");
    const form = document.getElementById('profileKycUploadForm');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        const original = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A ENVIAR...';
        }

        try {
            const res = await fetch('../../interface_programacao/user/upload_kyc.php', {
                method: 'POST',
                body: new FormData(form)
            });
            const data = await res.json();

            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Dossier submetido!', background: '#0d1628', color: '#fff', timer: 1800, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1800);
                } else {
                    location.reload();
                }
            } else {
                throw new Error(data.message || data.error || 'Falha ao submeter KYC.');
            }
        } catch (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Erro', text: err.message, background: '#0d1628', color: '#fff' });
            } else {
                alert(err.message);
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }
    });
    // Aqui podemos adicionar lógicas específicas se necessário
}

function handleProfileKycPreview(input) {
    const box = input.closest('.upload-drop-zone');
    const preview = box.querySelector('.file-preview');
    const icon = box.querySelector('.main-icon');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.style.backgroundImage = `url(${e.target.result})`;
            preview.style.display = 'flex';
            if(icon) icon.style.opacity = '0.1';
            box.style.borderColor = 'var(--elite-orange)';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
initProfileKycHandlers();
</script>
