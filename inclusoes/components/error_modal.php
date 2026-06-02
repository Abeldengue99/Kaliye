<?php
/**
 * COMPONENTE: MODAL DE TRATAMENTO DE ERROS
 * Kaliye Platform - 02 de Junho de 2026
 * 
 * Renderiza um modal reutilizável para exibir erros de forma amigável
 * Uso: incluir em todas as páginas e usar ErrorUI.show() no JavaScript
 */
?>

<!-- MODAL DE ERRO CENTRALIZADO -->
<div id="errorModalBackdrop" class="error-modal-backdrop" style="display: none;"></div>
<div id="errorModal" class="error-modal" style="display: none;">
    <div class="error-modal-content">
        <!-- Ícone de erro -->
        <div class="error-modal-icon" id="errorModalIcon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        
        <!-- Título do erro -->
        <h2 class="error-modal-title" id="errorModalTitle">Ocorreu um erro</h2>
        
        <!-- Mensagem do erro -->
        <p class="error-modal-message" id="errorModalMessage">
            Por favor, tenta novamente ou contacta o suporte.
        </p>
        
        <!-- Detalhes adicionais (expandível) -->
        <div class="error-modal-details" id="errorModalDetailsContainer" style="display: none;">
            <button class="error-modal-details-toggle" id="errorModalDetailsToggle">
                <i class="fas fa-chevron-down"></i> Mais detalhes
            </button>
            <div class="error-modal-details-content" id="errorModalDetailsContent" style="display: none;">
                <p id="errorModalDetails"></p>
            </div>
        </div>
        
        <!-- Opções de ação -->
        <div class="error-modal-actions">
            <button class="error-modal-btn error-modal-btn-primary" id="errorModalBtnRetry" style="display: none;">
                <i class="fas fa-redo"></i> Tentar Novamente
            </button>
            <button class="error-modal-btn error-modal-btn-secondary" id="errorModalBtnClose">
                <i class="fas fa-times"></i> Fechar
            </button>
            <button class="error-modal-btn error-modal-btn-secondary" id="errorModalBtnContact" style="display: none;">
                <i class="fas fa-life-ring"></i> Contactar Suporte
            </button>
        </div>
        
        <!-- Referência do erro (para suporte) -->
        <div class="error-modal-reference" id="errorModalReference" style="display: none;">
            <small>Código de referência: <code id="errorModalRefCode"></code></small>
        </div>
    </div>
</div>

<style>
/* ===== ESTILOS DO MODAL DE ERRO ===== */

.error-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    animation: fadeIn 0.3s ease-out;
}

.error-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.95);
    z-index: 10000;
    animation: slideIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

.error-modal-content {
    background: linear-gradient(135deg, #1a1f35 0%, #0f1628 100%);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 16px;
    padding: 2rem;
    max-width: 520px;
    width: 90vw;
    box-shadow: 
        0 20px 60px rgba(0, 0, 0, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    max-height: 80vh;
    overflow-y: auto;
}

.error-modal-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 1rem;
    background: rgba(239, 68, 68, 0.15);
    border: 2px solid rgba(239, 68, 68, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #ef4444;
}

.error-modal-icon.success {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.3);
    color: #10b981;
}

.error-modal-icon.warning {
    background: rgba(251, 191, 36, 0.15);
    border-color: rgba(251, 191, 36, 0.3);
    color: #fbbf24;
}

.error-modal-icon.info {
    background: rgba(59, 130, 246, 0.15);
    border-color: rgba(59, 130, 246, 0.3);
    color: #3b82f6;
}

.error-modal-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: #ffffff;
    text-align: center;
    margin: 0 0 0.75rem;
}

.error-modal-message {
    color: #cbd5e1;
    text-align: center;
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0 0 1.5rem;
}

.error-modal-details {
    margin: 1.5rem 0;
}

.error-modal-details-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.5rem 0;
    transition: color 0.2s;
}

.error-modal-details-toggle:hover {
    color: #cbd5e1;
}

.error-modal-details-toggle i {
    transition: transform 0.3s;
}

.error-modal-details-toggle.expanded i {
    transform: rotate(180deg);
}

.error-modal-details-content {
    margin-top: 0.75rem;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.3);
    border-left: 3px solid #ef4444;
    border-radius: 6px;
}

.error-modal-details-content p {
    margin: 0;
    color: #cbd5e1;
    font-size: 0.85rem;
    font-family: 'Courier New', monospace;
    word-break: break-all;
    line-height: 1.5;
}

.error-modal-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.error-modal-btn {
    flex: 1;
    min-width: 120px;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s;
    font-family: 'Inter', sans-serif;
}

.error-modal-btn-primary {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}

.error-modal-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
}

.error-modal-btn-secondary {
    background: rgba(255, 255, 255, 0.08);
    color: #cbd5e1;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.error-modal-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.2);
}

.error-modal-reference {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
    color: #94a3b8;
    font-size: 0.8rem;
}

.error-modal-reference code {
    background: rgba(0, 0, 0, 0.3);
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    color: #f7941d;
    font-family: 'Courier New', monospace;
    word-break: break-all;
}

/* Scroll personalizado para o modal */
.error-modal-content::-webkit-scrollbar {
    width: 6px;
}

.error-modal-content::-webkit-scrollbar-track {
    background: transparent;
}

.error-modal-content::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.error-modal-content::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Responsivo para ecrãs pequenos */
@media (max-width: 560px) {
    .error-modal-content {
        padding: 1.5rem;
        width: 95vw;
        max-height: 90vh;
    }
    
    .error-modal-title {
        font-size: 1.1rem;
    }
    
    .error-modal-btn {
        min-width: 100px;
        font-size: 0.85rem;
    }
}
</style>

<script>
/**
 * INTERFACE DE UTILIZADOR DE ERROS (ErrorUI)
 * Gerencia a exibição do modal de erro
 */

const ErrorUI = {
    // Estado
    retryCallback: null,
    isShowing: false,
    
    /**
     * Mostra o modal com mensagem de erro
     * 
     * @param {string} message Mensagem para o utilizador
     * @param {Object} options Opções adicionais
     */
    show(message, options = {}) {
        const {
            title = 'Ocorreu um erro',
            type = 'error', // error, warning, info, success
            details = null,
            showRetry = false,
            showContact = true,
            retryCallback = null,
            contactUrl = '/suporte'
        } = options;
        
        this.retryCallback = retryCallback;
        this.isShowing = true;
        
        // Elementos
        const backdrop = document.getElementById('errorModalBackdrop');
        const modal = document.getElementById('errorModal');
        const icon = document.getElementById('errorModalIcon');
        const titleEl = document.getElementById('errorModalTitle');
        const messageEl = document.getElementById('errorModalMessage');
        const detailsContainer = document.getElementById('errorModalDetailsContainer');
        const detailsContent = document.getElementById('errorModalDetailsContent');
        const detailsText = document.getElementById('errorModalDetails');
        const btnRetry = document.getElementById('errorModalBtnRetry');
        const btnClose = document.getElementById('errorModalBtnClose');
        const btnContact = document.getElementById('errorModalBtnContact');
        const referenceContainer = document.getElementById('errorModalReference');
        const referenceCode = document.getElementById('errorModalRefCode');
        
        // Atualizar ícone
        icon.className = `error-modal-icon ${type}`;
        const icons = { error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle', success: 'fa-check-circle' };
        icon.innerHTML = `<i class="fas ${icons[type] || icons.error}"></i>`;
        
        // Atualizar conteúdo
        titleEl.textContent = title;
        messageEl.textContent = message;
        
        // Detalhes (se fornecidos)
        if (details) {
            detailsContainer.style.display = 'block';
            detailsText.textContent = details;
        } else {
            detailsContainer.style.display = 'none';
        }
        
        // Botões
        btnRetry.style.display = showRetry ? 'flex' : 'none';
        btnContact.style.display = showContact ? 'flex' : 'none';
        
        // Referência (para suporte)
        const refCode = `ERR_${Date.now()}_${Math.random().toString(36).substr(2, 9).toUpperCase()}`;
        referenceContainer.style.display = showContact ? 'block' : 'none';
        referenceCode.textContent = refCode;
        
        // Mostrar modal
        backdrop.style.display = 'block';
        modal.style.display = 'block';
        
        // Prevenir scroll da página
        document.body.style.overflow = 'hidden';
    },
    
    /**
     * Fecha o modal
     */
    close() {
        const backdrop = document.getElementById('errorModalBackdrop');
        const modal = document.getElementById('errorModal');
        
        backdrop.style.display = 'none';
        modal.style.display = 'none';
        
        document.body.style.overflow = '';
        this.isShowing = false;
    },
    
    /**
     * Mostra erro de validação com campos específicos
     */
    showValidationErrors(errors, message = 'Validação de dados falhou') {
        let detailsText = '';
        
        if (typeof errors === 'object' && errors !== null) {
            detailsText = Object.entries(errors)
                .map(([field, error]) => `• ${field}: ${error}`)
                .join('\n');
        } else {
            detailsText = errors;
        }
        
        this.show(message, {
            title: 'Erro de Validação',
            type: 'warning',
            details: detailsText,
            showContact: false
        });
    },
    
    /**
     * Mostra erro de rede
     */
    showNetworkError() {
        this.show(
            'Problema de conectividade. Verifica a tua ligação à internet e tenta novamente.',
            {
                title: 'Erro de Conexão',
                type: 'warning',
                showRetry: true,
                showContact: true
            }
        );
    },
    
    /**
     * Mostra erro genérico do servidor
     */
    showServerError() {
        this.show(
            'Ocorreu um erro no servidor. A equipa técnica foi notificada e está a trabalhar para resolver.',
            {
                title: 'Erro do Servidor',
                type: 'error',
                showContact: true
            }
        );
    }
};

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const btnClose = document.getElementById('errorModalBtnClose');
    const btnRetry = document.getElementById('errorModalBtnRetry');
    const btnContact = document.getElementById('errorModalBtnContact');
    const backdrop = document.getElementById('errorModalBackdrop');
    const detailsToggle = document.getElementById('errorModalDetailsToggle');
    const detailsContent = document.getElementById('errorModalDetailsContent');
    
    // Fechar modal
    if (btnClose) btnClose.addEventListener('click', () => ErrorUI.close());
    
    // Tentar novamente
    if (btnRetry) btnRetry.addEventListener('click', () => {
        ErrorUI.close();
        if (ErrorUI.retryCallback) ErrorUI.retryCallback();
    });
    
    // Contactar suporte
    if (btnContact) btnContact.addEventListener('click', () => {
        window.location.href = '/suporte?tipo=erro';
    });
    
    // Fechar ao clicar no backdrop
    if (backdrop) backdrop.addEventListener('click', () => ErrorUI.close());
    
    // Toggle detalhes
    if (detailsToggle) {
        detailsToggle.addEventListener('click', function() {
            detailsContent.style.display = detailsContent.style.display === 'none' ? 'block' : 'none';
            this.classList.toggle('expanded');
        });
    }
    
    // Fechar com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ErrorUI.isShowing) {
            ErrorUI.close();
        }
    });
});
</script>
