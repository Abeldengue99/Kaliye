<?php
/**
 * Component: Edit Profile Modal — Premium Tabbed Redesign
 * Expected Variables: $user (array com todos os dados do utilizador)
 */
?>
<!-- ═══════════════════════════════════════════════════════
     MODAL: EDITAR PERFIL — Versão Compacta com Abas
     ═══════════════════════════════════════════════════════ -->
<div id="editProfileModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(5, 10, 20, 0.9); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); z-index: 100000; justify-content: center; align-items: center; padding: 1rem;">

    <div style="background: rgba(13, 22, 40, 0.98); border: 1px solid rgba(247,148,29,0.25); border-radius: 28px; width: 100%; max-width: 600px; box-shadow: 0 50px 100px -20px rgba(0,0,0,1); overflow: hidden; position: relative; animation: modalAppear 0.4s cubic-bezier(0.16, 1, 0.3, 1);">

        <!-- ── CABEÇALHO DO MODAL ── -->
        <div style="padding: 1.5rem 2rem; background: rgba(7, 13, 26, 0.8); border-bottom: 1px solid var(--surface-8); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.8rem;">
                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #f7941d, #e07b0e); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-edit" style="color: white; font-size: 0.9rem;"></i>
                </div>
                <h4 style="margin: 0; color: white; font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 800;"><?php echo __('edit_profile'); ?></h4>
            </div>
            <button onclick="document.getElementById('editProfileModal').style.display='none'" style="background: none; border: none; color: var(--surface-40); font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <!-- ── NAVEGAÇÃO POR ABAS ── -->
        <div style="display: flex; background: rgba(0,0,0,0.2); padding: 0.5rem; gap: 0.5rem; border-bottom: 1px solid var(--surface-5);">
            <button type="button" onclick="switchProfileTab('tab-geral', this)" class="p-tab-btn active">Dados Gerais</button>
            <button type="button" onclick="switchProfileTab('tab-contacto', this)" class="p-tab-btn">Contacto</button>
            <button type="button" onclick="switchProfileTab('tab-estudos', this)" class="p-tab-btn">Profissional/Académico</button>
        </div>

        <!-- ── FORMULÁRIO ── -->
        <form id="editProfileForm" enctype="multipart/form-data" style="padding: 2rem;">
            <input type="hidden" name="target_user_id" value="<?php echo $user['user_id']; ?>">

            <!-- ══ SEÇÃO 1: GERAL ══ -->
            <div id="tab-geral" class="p-tab-content active">
                <div style="display: flex; gap: 1.5rem; align-items: center; margin-bottom: 2rem;">
                    <div style="position: relative;">
                        <?php if(!empty($user['profile_pic']) && $user['profile_pic'] != 'default_profile.png'): ?>
                            <img src="<?php echo $base_url . htmlspecialchars($user['profile_pic']); ?>" style="width: 90px; height: 90px; border-radius: 20px; object-fit: cover; border: 2px solid #f7941d;" id="editAvatarPreview">
                        <?php else: ?>
                            <div style="width: 90px; height: 90px; border-radius: 20px; background: var(--surface-5); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #f7941d;" id="editAvatarPreview">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <label for="profile_pic_input" style="position: absolute; bottom: -8px; right: -8px; background: #f7941d; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #0d1628;">
                            <i class="fas fa-camera" style="font-size: 0.7rem; color: white;"></i>
                        </label>
                        <input type="file" id="profile_pic_input" name="profile_pic" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                    </div>
                    <div style="flex: 1;">
                        <label class="p-label">Nome Completo</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required class="p-input">
                    </div>
                </div>

                <div style="margin-bottom: 0;">
                    <label class="p-label">Bio / Sobre Mim</label>
                    <textarea name="academic_info" rows="5" class="p-input" style="resize: none;"><?php echo htmlspecialchars($user['academic_info'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- ══ SEÇÃO 2: CONTACTO & LOCAL ══ -->
            <div id="tab-contacto" class="p-tab-content">
                <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                    <div>
                        <label class="p-label">Telemóvel / WhatsApp</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="p-input" placeholder="+244 9..">
                    </div>
                    <div>
                        <label class="p-label">Província</label>
                        <select name="location" class="p-input">
                            <option value="">Selecionar Província</option>
                            <?php
                            $provincias = ['Bengo','Benguela','Bié','Cabinda','Cuando Cubango','Cuanza Norte','Cuanza Sul','Cunene','Huambo','Huíla','Luanda','Lunda Norte','Lunda Sul','Malanje','Moxico','Namibe','Uíge','Zaire'];
                            $loc_atual = $user['location'] ?? '';
                            foreach($provincias as $p) {
                                $sel = ($loc_atual === $p) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($p) . "\" $sel>" . htmlspecialchars($p) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="p-label">Data de Nascimento</label>
                        <input type="date" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>" class="p-input" style="color-scheme: dark;">
                    </div>
                </div>
            </div>

            <!-- ══ SEÇÃO 3: ACADÉMICO / PROFISSIONAL ══ -->
            <div id="tab-estudos" class="p-tab-content">
                <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                    <div>
                        <label class="p-label">Instituição de Ensino</label>
                        <input type="text" name="institution" value="<?php echo htmlspecialchars($user['institution'] ?? ''); ?>" class="p-input" placeholder="Ex: UAN, ISUTIC...">
                    </div>
                    <div>
                        <label class="p-label">Organização / Empresa</label>
                        <input type="text" name="organization" value="<?php echo htmlspecialchars($user['organization'] ?? ''); ?>" class="p-input" placeholder="Onde trabalha atualmente">
                    </div>
                    <div style="background: rgba(247,148,29,0.05); border: 1px dashed rgba(247,148,29,0.2); padding: 1rem; border-radius: 12px; font-size: 0.8rem; color: var(--surface-50);">
                        <i class="fas fa-info-circle"></i> Os dados do curso e especialidades principais são geridos no sistema de <b>Skills & Expertise</b> da página de perfil.
                    </div>
                </div>
            </div>

            <!-- ── AÇÕES ── -->
            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="button" onclick="document.getElementById('editProfileModal').style.display='none'" style="flex: 1; padding: 0.8rem; border-radius: 12px; border: 1px solid var(--surface-10); background: transparent; color: white; cursor: pointer; font-weight: 600;">Cancelar</button>
                <button type="submit" style="flex: 2; padding: 0.8rem; border-radius: 12px; border: none; background: linear-gradient(135deg, #f7941d, #e07b0e); color: white; cursor: pointer; font-weight: 700; box-shadow: 0 10px 20px rgba(247,148,29,0.2);">Guardar Alterações</button>
            </div>
        </form>
    </div>
</div>

<style>
.p-tab-btn {
    flex: 1;
    padding: 0.7rem;
    background: transparent;
    border: none;
    color: var(--surface-50);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    border-radius: 8px;
    transition: 0.3s;
}
.p-tab-btn.active {
    background: rgba(247,148,29,0.1);
    color: #f7941d;
}
.p-tab-content { display: none; }
.p-tab-content.active { display: block; animation: fadeIn 0.3s ease; }

.p-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--surface-30);
    margin-bottom: 0.5rem;
    letter-spacing: 1px;
}
.p-input {
    width: 100%;
    background: rgba(0,0,0,0.2);
    border: 1px solid var(--surface-10);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    color: white;
    font-size: 0.9rem;
    font-family: 'Inter', sans-serif;
    outline: none;
    transition: 0.3s;
}
.p-input option {
    background: #0d1628;
    color: #ffffff;
}
.p-input:focus {
    border-color: #f7941d;
    background: rgba(247,148,29,0.05);
}

@keyframes modalAppear {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
function switchProfileTab(tabId, btn) {
    document.querySelectorAll('.p-tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.p-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('editAvatarPreview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'width:90px;height:90px;border-radius:20px;object-fit:cover;border:2px solid #f7941d;';
                img.id = 'editAvatarPreview';
                preview.parentNode.replaceChild(img, preview);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
