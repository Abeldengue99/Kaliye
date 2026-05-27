<!-- Profile Edit Modal: Elite multi-step Experience -->
<div id="profileEditModal" class="elite-modal-overlay" style="display: none; z-index: 20000;">
    <div class="elite-modal-card" style="max-width: 920px; max-height: 90vh; overflow-y: auto;">
        <!-- Botão de Fecho -->
        <button type="button" onclick="closeMyProfileEdit()" class="elite-modal-close">
            <i class="fas fa-times"></i>
        </button>

        <form id="profileEditForm" onsubmit="submitProfileEdit(event)" style="padding: 0;">
            <div class="elite-modal-body">
                <div id="profileEditContent" style="padding: 3rem;">
                    <!-- Steps injected via JS -->
                </div>
                
                <div class="elite-modal-footer" id="profileEditFooter" style="padding: 0 3rem 3rem; display: flex; justify-content: space-between; gap: 1rem;">
                    <!-- Control buttons injected here -->
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Estilos para o Modal de Edição de Perfil */
#profileEditModal.elite-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(2,6,23,0.85); backdrop-filter: blur(15px);
    display: none; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.4s ease;
}

#profileEditModal.elite-modal-overlay.active {
    display: flex; opacity: 1;
}

#profileEditModal .elite-modal-card {
    background: #0d1628; border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 28px; padding: 0; overflow: hidden; position: relative;
    box-shadow: 0 40px 100px rgba(0,0,0,0.6);
    transform: scale(0.85) translateY(30px); opacity: 0;
    transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease;
}

#profileEditModal.elite-modal-overlay.active .elite-modal-card {
    transform: scale(1) translateY(0); opacity: 1;
}

.profile-edit-input-group { margin-bottom: 1.5rem; text-align: left; }
.profile-edit-label {
    display: block; font-size: 0.7rem; font-weight: 800; color: #f7941d;
    text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.6rem;
}
.profile-edit-input {
    width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px; padding: 12px 16px; color: #fff; font-size: 0.9rem; outline: none;
    transition: 0.3s;
}
.profile-edit-input:focus { border-color: #f7941d; background: rgba(255,255,255,0.06); }
.profile-field-hint {
    display: block;
    margin-top: 0.45rem;
    color: rgba(255,255,255,0.42);
    font-size: 0.68rem;
    line-height: 1.4;
}

.profile-avatar-edit-wrapper {
    display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem;
    padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.05);
}
.profile-avatar-preview {
    width: 80px; height: 80px; border-radius: 50%; object-fit: cover;
    border: 3px solid #f7941d;
}
.upload-btn-profile {
    background: rgba(255,255,255,0.05); color: #fff; padding: 8px 16px;
    border-radius: 10px; font-size: 0.75rem; font-weight: 700; cursor: pointer;
    transition: 0.3s; display: inline-block;
}
.upload-btn-profile:hover { background: #f7941d; }
.view-profile-full-btn {
    background: #f7941d; color: #fff; border: none; padding: 12px 24px;
    border-radius: 14px; font-weight: 800; cursor: pointer; transition: 0.3s;
    font-size: 0.8rem; letter-spacing: 0.5px;
}
.view-profile-full-btn:hover { background: #ff9d2e; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(247,148,29,0.2); }

.profile-edit-input:disabled {
    background: rgba(255,255,255,0.01); border-color: rgba(255,255,255,0.03);
    color: rgba(255,255,255,0.25); cursor: not-allowed;
}
.trust-shield-card {
    background: rgba(247,148,29,0.03); border: 1px dashed rgba(247,148,29,0.2);
    border-radius: 20px; padding: 1.5rem; margin-top: 1.5rem; text-align: left;
}
.trust-shield-card h4 {
    font-size: 0.75rem; color: #f7941d; font-weight: 900; 
    text-transform: uppercase; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;
}
.shield-actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.btn-shield {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
    color: #fff; padding: 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;
    cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-shield i { font-size: 0.9rem; }
.btn-shield:hover { background: rgba(247,148,29,0.1); border-color: #f7941d; color: #f7941d; }
.btn-shield.verified { border-color: #10b981; color: #10b981; pointer-events: none; }

@media (max-width: 768px) {
    #profileEditModal .elite-modal-card {
        width: calc(100vw - 24px);
        max-height: 92vh;
    }
    #profileEditContent {
        padding: 2rem 1.2rem !important;
    }
    #profileEditFooter {
        padding: 0 1.2rem 1.5rem !important;
        flex-direction: column;
    }
    #profileEditContent > div[style*="repeat(4"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    #profileEditContent > div[style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    .profile-dossier-grid {
        grid-template-columns: 1fr !important;
    }
}

</style>
