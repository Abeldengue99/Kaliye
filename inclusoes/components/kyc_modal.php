<?php
/**
 * Component: Elite KYC Wizard (Unificado: Identidade + Perfil)
 */
$_utype = $_SESSION['user_type'] ?? 'student';
$_is_peer = false;
$_id_number = '';

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../configuracoes/base_dados.php';
    $db_kyc = (new Database())->getConnection();
    
    try {
        // Buscar dados cruciais para o Wizard
        $stmt_u = $db_kyc->prepare("SELECT is_peer_mentor, id_number FROM users WHERE user_id = ?");
        $stmt_u->execute([$_SESSION['user_id']]);
        $u_kyc_data = $stmt_u->fetch(PDO::FETCH_ASSOC);
        
        if ($u_kyc_data) {
            $_is_peer = $u_kyc_data['is_peer_mentor'] ?? false;
            $_id_number = $u_kyc_data['id_number'] ?? '';
        }
    } catch (PDOException $e) {
        // Ignora erro silenciosamente para não quebrar o layout/DOM da página caso colunas faltem
        $_is_peer = false;
        $_id_number = '';
    }
}

$lbl_st = "color: rgba(255,255,255,0.5); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block;";
?>
<div id="kycModal" class="elite-modal-overlay" style="display: none; z-index: 110000; background: rgba(5, 10, 20, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
    <!-- Close logic (Absolute outside card) -->
    <button onclick="window.closeKYCModal()" style="position: absolute; top: 2rem; right: 2rem; width: 50px; height: 50px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; cursor: pointer; z-index: 110001; display: flex; align-items: center; justify-content: center; transition: 0.3s; font-size: 1.2rem;" onmouseover="this.style.background='rgba(239, 68, 68, 0.2)'; this.style.borderColor='rgba(239, 68, 68, 0.4)';" onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='rgba(255,255,255,0.1)';">
        <i class="fas fa-times"></i>
    </button>
    <div style="position: absolute; top: 10%; left: 20%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(247,148,29,0.05) 0%, transparent 70%); pointer-events: none;"></div>
    
    <div class="elite-modal-card kyc-premium-card" style="max-width: 620px; width: 95%; max-height: 94vh; overflow-y: auto; padding: 0; border: 1px solid rgba(255,255,255,0.08); background: rgba(13, 22, 40, 0.95); box-shadow: 0 40px 100px rgba(0,0,0,0.8);">
        
        <!-- Header -->
        <div style="padding: 2.5rem 2.5rem 1.5rem; position: relative; border-bottom: 1px solid rgba(255,255,255,0.05); background: linear-gradient(to bottom, rgba(255,255,255,0.02), transparent);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.8rem; font-weight: 900; color: #fff; letter-spacing: 2px;">VERIFICAÇÃO</h2>
                    <div style="display: flex; align-items: center; gap: 12px; margin-top: 10px;">
                        <div id="kycStepIndicator" style="display: flex; gap: 6px;">
                            <span class="step-dot active" style="width: 35px; height: 4px; border-radius: 2px; background: var(--elite-orange); transition: all 0.5s;"></span>
                            <span class="step-dot" style="width: 35px; height: 4px; border-radius: 2px; background: rgba(255,255,255,0.1); transition: all 0.5s;"></span>
                        </div>
                        <span id="kycStepText" style="font-size: 0.65rem; font-weight: 950; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px;">Evolução 1 de 2</span>
                    </div>
                </div>
                </div>
        </div>

        <div style="padding: 2.5rem;">
            <form id="kycUploadForm" enctype="multipart/form-data">
                
                <!-- PASSO 1: DOCUMENTAÇÃO LEGAL -->
                <div id="kycStep1" class="kyc-wizard-step" style="animation: fadeInUp 0.5s ease forwards;">
                    <div class="kyc-info-banner">
                        <div class="banner-icon"><i class="fas fa-user-shield"></i></div>
                        <div class="banner-text">
                            <strong>Privacidade de Nível Bancário</strong>
                            <p>Os seus documentos são encriptados e usados apenas para validação legal em Angola.</p>
                        </div>
                    </div>

                    <div class="elite-input-group" style="margin-bottom: 2rem;">
                        <label style="<?= $lbl_st ?>">Número de Identidade (BI / Passaporte)</label>
                        <div class="premium-field-wrapper" style="opacity: 0.7;">
                            <i class="fas fa-lock field-icon"></i>
                            <input type="text" name="id_number" value="<?= htmlspecialchars($_id_number) ?>" <?= $_id_number !== '' ? 'readonly' : 'required' ?> class="kyc-input-v3" style="<?= $_id_number !== '' ? 'cursor: not-allowed; background: rgba(255,255,255,0.01);' : '' ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="kyc-upload-box-v3">
                            <label>Frente do Documento</label>
                            <div class="upload-drop-zone">
                                <i class="fas fa-id-card-alt main-icon"></i>
                                <span class="upload-label">Selecionar Imagem</span>
                                <input type="file" name="bi_front" accept="image/*" onchange="handleKycPreview(this)">
                                <div class="file-preview"></div>
                            </div>
                        </div>
                        <div class="kyc-upload-box-v3">
                            <label>Verso do Documento</label>
                            <div class="upload-drop-zone">
                                <i class="fas fa-id-card-alt main-icon"></i>
                                <span class="upload-label">Selecionar Imagem</span>
                                <input type="file" name="bi_back" accept="image/*" onchange="handleKycPreview(this)">
                                <div class="file-preview"></div>
                            </div>
                        </div>
                    </div>

                    <div class="kyc-upload-box-v3" style="margin-bottom: 2.5rem;">
                        <label>Selfie Probatória</label>
                        <div class="upload-drop-zone" style="min-height: 120px;">
                            <i class="fas fa-camera-retro main-icon"></i>
                            <span class="upload-label">Tirar ou Enviar Foto</span>
                            <input type="file" name="selfie" accept="image/*" onchange="handleKycPreview(this)">
                            <div class="file-preview"></div>
                        </div>
                    </div>

                    <button type="button" onclick="window.goKycStep(2)" class="kyc-next-btn">AVANÇAR PARA PERFIL <i class="fas fa-chevron-right"></i></button>
                </div>

                <!-- PASSO 2: DADOS ESPECÍFICOS DE PERFIL -->
                <div id="kycStep2" class="kyc-wizard-step" style="display: none; animation: fadeInUp 0.5s ease forwards;">
                    
                    <?php if ($_utype === 'mentor'): ?>
                        <div class="profile-banner mentor-accent">
                            <h4 style="margin: 0; color: var(--elite-orange); font-size: 1rem; font-weight: 900;">Candidatura a Mentor Elite</h4>
                            <p style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin: 5px 0 0;">Certifique a sua experiência profissional.</p>
                        </div>
                        <div class="elite-input-group" style="margin-bottom: 1.5rem;">
                            <label style="<?= $lbl_st ?>">Especialidade Principal</label>
                            <input type="text" name="specialty" placeholder="Ex: Gestão Financeira, Marketing..." class="kyc-input">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1.5rem;">
                            <div class="elite-input-group">
                                <label style="<?= $lbl_st ?>">Anos de Experiência</label>
                                <input type="number" name="experience_years" class="kyc-input">
                            </div>
                            <div class="elite-input-group">
                                <label style="<?= $lbl_st ?>">LinkedIn Profissional</label>
                                <input type="url" name="linkedin_url" placeholder="https://..." class="kyc-input">
                            </div>
                        </div>
                        <div class="kyc-upload-box-v3" style="margin-bottom: 2rem;">
                            <label>Curriculum Vitae ou Portfólio (PDF)</label>
                            <div class="upload-drop-zone">
                                <i class="fas fa-file-pdf main-icon"></i>
                                <span class="upload-label">Anexar Documento</span>
                                <input type="file" name="cv_file" accept=".pdf" onchange="handleKycPreview(this)">
                                <div class="file-preview"></div>
                            </div>
                        </div>

                    <?php elseif ($_utype === 'univ_student' && $_is_peer): ?>
                        <div class="profile-banner peer-accent">
                            <h4 style="margin: 0; color: #60a5fa; font-size: 1rem; font-weight: 900;">Candidatura a Peer Mentor</h4>
                            <p style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin: 5px 0 0;">Veteranos apoiando caloiros na mesma jornada académica.</p>
                        </div>
                        <div class="elite-input-group" style="margin-bottom: 1.5rem;">
                            <label style="<?= $lbl_st ?>">Área / Curso e Ano Atual</label>
                            <input type="text" name="specialty" placeholder="Ex: Economia - 4º Ano" class="kyc-input">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1.5rem;">
                            <div class="kyc-upload-box-v3">
                                <label>Histórico (PDF)</label>
                                <div class="upload-drop-zone">
                                    <i class="fas fa-graduation-cap main-icon"></i>
                                    <input type="file" name="transcript_file" accept=".pdf" onchange="handleKycPreview(this)">
                                    <div class="file-preview"></div>
                                </div>
                            </div>
                            <div class="kyc-upload-box-v3">
                                <label>CV (PDF)</label>
                                <div class="upload-drop-zone">
                                    <i class="fas fa-file-pdf main-icon"></i>
                                    <input type="file" name="cv_file" accept=".pdf" onchange="handleKycPreview(this)">
                                    <div class="file-preview"></div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($_utype === 'investor'): ?>
                        <div class="profile-banner investor-accent">
                            <h4 style="margin: 0; color: #10b981; font-size: 1rem; font-weight: 900;">Acreditação de Investidor</h4>
                            <p style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin: 5px 0 0;">Certifique a sua capacidade financeira.</p>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1.5rem;">
                            <div class="elite-input-group">
                                <label style="<?= $lbl_st ?>">Rendimento Anual (AOA)</label>
                                <select name="annual_income" class="kyc-input">
                                    <option value="">Selecione...</option>
                                    <option value="Até 5M">Até 5M AOA</option>
                                    <option value="5M-20M">5M a 20M AOA</option>
                                    <option value="+20M">Acima de 20M AOA</option>
                                </select>
                            </div>
                            <div class="elite-input-group">
                                <label style="<?= $lbl_st ?>">Origem dos Fundos</label>
                                <input type="text" name="source_of_funds" placeholder="Ex: Salários, Negócios, Herança..." class="kyc-input" required>
                            </div>
                        </div>
                        <div class="kyc-upload-box-v3" style="margin-bottom: 2rem;">
                            <label>Comprovativo de Rendimentos (PDF/JPG)</label>
                            <div class="upload-drop-zone">
                                <i class="fas fa-file-invoice-dollar main-icon"></i>
                                <input type="file" name="income_proof" accept="image/*,.pdf" onchange="handleKycPreview(this)">
                                <div class="file-preview"></div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 2.5rem; border-radius: 20px; text-align: center; margin-bottom: 2rem;">
                            <i class="fas fa-check-circle" style="font-size: 2.5rem; color: #10b981; margin-bottom: 15px; display: block;"></i>
                            <h4 style="margin: 0; color: #fff; font-size: 1.1rem; font-weight: 800;">Tudo Pronto!</h4>
                            <p style="font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-top: 10px;">Submeta agora para validação administrativa final.</p>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 15px;">
                        <button type="button" onclick="window.goKycStep(1)" style="flex: 1; height: 60px; border-radius: 18px; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: #fff; font-weight: 800; cursor: pointer; transition: 0.3s;">VOLTAR</button>
                        <button type="submit" class="kyc-next-btn" style="flex: 2; background: var(--elite-orange);">
                            SUBMETER DOSSIER <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function handleKycPreview(input) {
    const box = input.closest('.upload-drop-zone');
    const preview = box.querySelector('.file-preview');
    const label = box.querySelector('.upload-label');
    const icon = box.querySelector('.main-icon');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (input.files[0].type === 'application/pdf') {
                preview.innerHTML = `<div style="display:flex; flex-direction:column; align-items:center; gap:5px; padding:10px;"><i class="fas fa-file-pdf" style="font-size:1.5rem; color:#ef4444;"></i><span style="font-size:0.6rem; color:#fff; word-break:break-all;">${input.files[0].name}</span></div>`;
            } else {
                preview.style.backgroundImage = `url(${e.target.result})`;
                preview.innerHTML = '';
            }
            preview.style.display = 'flex';
            if(label) label.style.opacity = '0.2';
            if(icon) icon.style.opacity = '0.2';
            box.style.borderColor = 'var(--elite-orange)';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<style>
.kyc-premium-card { font-family: 'Outfit', sans-serif; }
.kyc-close-btn { width: 38px; height: 38px; border-radius: 12px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: rgba(255,255,255,0.4); cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
.kyc-close-btn:hover { background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.2); }
.kyc-info-banner { display: flex; gap: 15px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.1); padding: 1.2rem; border-radius: 18px; margin-bottom: 2rem; align-items: center; }
.banner-icon { font-size: 1.4rem; color: #60a5fa; }
.banner-text strong { display: block; font-size: 0.85rem; color: #fff; margin-bottom: 2px; }
.banner-text p { font-size: 0.75rem; color: rgba(255,255,255,0.5); margin: 0; line-height: 1.4; }
.premium-field-wrapper { position: relative; }
.field-icon { position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.2); font-size: 1.1rem; }
.kyc-input-v3 { width: 100%; padding: 1.2rem 1.2rem 1.2rem 3.5rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; color: #fff; font-size: 0.95rem; font-weight: 600; transition: 0.3s; }
.kyc-input-v3:focus { border-color: var(--elite-orange); background: rgba(255,255,255,0.04); box-shadow: 0 0 20px rgba(247,148,29,0.05); outline: none; }
.upload-drop-zone { position: relative; border: 2px dashed rgba(255,255,255,0.05); border-radius: 20px; padding: 20px 10px; text-align: center; transition: 0.3s; background: rgba(255,255,255,0.01); overflow: hidden; min-height: 110px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.upload-drop-zone:hover { border-color: rgba(247,148,29,0.3); background: rgba(247,148,29,0.02); }
.upload-drop-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; z-index: 10; }
.upload-drop-zone .main-icon { font-size: 1.8rem; color: rgba(255,255,255,0.1); margin-bottom: 8px; transition: 0.3s; }
.upload-drop-zone .upload-label { font-size: 0.6rem; font-weight: 800; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1px; }
.file-preview { position: absolute; inset: 5px; border-radius: 15px; background-size: cover; background-position: center; display: none; align-items: center; justify-content: center; background-color: #0d1628; z-index: 5; }
.kyc-next-btn { width: 100%; height: 60px; border-radius: 18px; border: none; background: #fff; color: #111; font-weight: 900; font-family: 'Outfit', sans-serif; font-size: 0.9rem; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 12px; }
.kyc-next-btn:hover { background: var(--elite-orange); transform: translateY(-3px); box-shadow: 0 10px 25px rgba(247,148,29,0.3); }
.kyc-input { width: 100%; padding: 1.1rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; color: #fff; font-size: 0.95rem; font-family: 'Outfit', sans-serif;}
.kyc-input:focus { border-color: var(--elite-orange); outline: none; background: rgba(255,255,255,0.04); }
.step-dot.active { background: var(--elite-orange) !important; box-shadow: 0 0 15px rgba(247,148,29,0.5); width: 45px !important; }
.profile-banner { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 1.5rem; border-radius: 20px; margin-bottom: 1.5rem; border-left: 4px solid #fff; }
.mentor-accent { border-left-color: var(--elite-orange); background: rgba(247,148,29,0.05); }
.peer-accent { border-left-color: #60a5fa; background: rgba(96, 165, 250, 0.05); }
.investor-accent { border-left-color: #10b981; background: rgba(16, 185, 129, 0.05); }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
/* Garantir que SweetAlert apareça sempre à frente de tudo */
.swal2-container { z-index: 120000 !important; }

/* Melhorias de scroll no modal */
#kycModal .kyc-premium-card::-webkit-scrollbar { width: 6px; }
#kycModal .kyc-premium-card::-webkit-scrollbar-track { background: transparent; }
#kycModal .kyc-premium-card::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
#kycModal .kyc-premium-card::-webkit-scrollbar-thumb:hover { background: var(--elite-orange); }
</style>
