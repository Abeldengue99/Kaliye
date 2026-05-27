<!-- User Profile Modal: Elite Multi-Step Experience -->
<div id="userCardModal" class="elite-modal-overlay" style="display: none; z-index: 20000;">
    <div class="elite-modal-card" style="max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <!-- Botão de Fecho -->
        <button onclick="closeUserCard()" class="elite-modal-close">
            <i class="fas fa-times"></i>
        </button>

        <div class="elite-modal-body" style="padding: 0;">
            <!-- Header dinâmico com Avatar e Banner -->
            <div id="userCardHeaderZone">
                <!-- Injected via AJAX -->
            </div>

            <!-- Conteúdo em Blocos (Injected via AJAX) -->
            <div id="userCardContent" style="padding: 2.5rem;">
                <!-- Steps injected here -->
            </div>
            
            <!-- Controles de Navegação do Modal -->
            <div id="userCardFooter" style="padding: 0 2.5rem 2.5rem; display: flex; justify-content: space-between; gap: 1rem;">
                <!-- Buttons injected here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos Elevados para a Experiência Multi-Step de Perfil */
.step-indicator-user {
    display: flex; gap: 6px; margin-bottom: 2rem; justify-content: center;
}
.step-dot-user {
    width: 30px; height: 4px; border-radius: 2px;
    background: rgba(255,255,255,0.1); transition: 0.4s;
}
.step-dot-user.active { background: #f7941d; box-shadow: 0 0 10px rgba(247,148,29,0.3); }

/* Estilos específicos para o Modal de Utilizador para evitar conflitos */
#userCardModal.elite-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(2,6,23,0.85); backdrop-filter: blur(15px);
    display: none; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.4s ease;
}

#userCardModal.elite-modal-overlay.active {
    display: flex; opacity: 1;
}

#userCardModal .elite-modal-card {
    background: #0d1628; border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 28px; padding: 0; overflow: hidden; position: relative;
    box-shadow: 0 40px 100px rgba(0,0,0,0.6);
    transform: scale(0.85) translateY(30px); opacity: 0;
    transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease;
}

#userCardModal.elite-modal-overlay.active .elite-modal-card {
    transform: scale(1) translateY(0); opacity: 1;
}

.user-block-title {
    font-size: 0.65rem; font-weight: 800; color: #f7941d;
    text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 0.5rem;
}

.user-info-box {
    background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);
    border-radius: 16px; padding: 1.25rem; margin-bottom: 1rem;
}

.skill-tag-elite {
    background: rgba(247,148,29,0.1); color: #f7941d;
    padding: 6px 12px; border-radius: 8px; font-size: 0.7rem;
    font-weight: 700; display: inline-block; margin: 0 4px 6px 0;
}
</style>
