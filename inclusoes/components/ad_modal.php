<!-- Ad Detail Modal: Elite Premium -->
<div id="adModal" class="elite-modal-overlay" style="display: none;">
    <div class="elite-modal-card ad-premium-card">
        <button onclick="document.getElementById('adModal').style.display='none'; document.body.style.overflow='auto';" class="elite-modal-close">
            <i class="fas fa-times"></i>
        </button>
        
        <div id="adModalImage" class="elite-ad-banner">
            <div class="elite-ad-overlay-gradient"></div>
        </div>
        
        <div class="elite-modal-body">
            <div class="elite-meta-row">
                <span id="adModalType" class="elite-badge-mini tag-green">VAGA</span>
                <span class="elite-label-micro">OPORTUNIDADE ELITE</span>
            </div>
            
            <h2 id="adModalTitle" class="elite-modal-title"></h2>
            
            <div class="elite-modal-description-wrapper">
                <p id="adModalDesc" class="elite-modal-text"></p>
            </div>
            
            <div class="elite-modal-actions">
                <a id="adModalLink" href="#" target="_blank" class="btn-ad-action" style="text-decoration: none; width: 100%;">
                    <i class="fas fa-external-link-alt"></i> ACEDER AGORA
                </a>
                <a id="adModalLinkText" href="#" target="_blank" rel="noopener" class="elite-ad-url-preview"></a>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS INDEPENDENTE E DE ELITE PARA O MODAL DE ANÚNCIOS */
.elite-modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    background: rgba(2, 6, 23, 0.9) !important; /* Fundo escuro profundo */
    backdrop-filter: blur(15px) !important;
    z-index: 9999999 !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 20px !important;
    animation: fadeInModal 0.4s ease !important;
}

.elite-modal-overlay[style*="display: flex"] {
    display: flex !important;
}

.elite-modal-overlay.active {
    display: flex !important;
}

.ad-premium-card {
    background: #0d1628 !important;
    width: 100% !important;
    max-width: 560px !important;
    max-height: 88vh !important;
    border-radius: 18px !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    overflow: hidden !important;
    position: relative !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
    animation: slideUpModal 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.elite-modal-close {
    position: absolute !important;
    top: 14px !important;
    right: 14px !important;
    width: 36px !important;
    height: 36px !important;
    background: rgba(255,255,255,0.05) !important;
    border: none !important;
    border-radius: 50% !important;
    color: #fff !important;
    cursor: pointer !important;
    z-index: 10 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: 0.3s !important;
}

.elite-modal-close:hover { background: #ef4444 !important; transform: rotate(90deg); }

.elite-ad-banner { 
    width: 100%; 
    height: 230px; 
    background-color: #050a15;
    background-size: contain; 
    background-repeat: no-repeat;
    background-position: center; 
    position: relative;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.elite-ad-overlay-gradient { 
    position: absolute; 
    inset: 0; 
    background: linear-gradient(to top, #0d1628 0%, transparent 100%); 
}

.elite-modal-body { padding: 24px !important; }
.elite-meta-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.elite-badge-mini { 
    background: #f7941d !important; 
    color: #000 !important;
    padding: 5px 12px; 
    border-radius: 8px; 
    font-size: 0.65rem; 
    font-weight: 900; 
    letter-spacing: 1px; 
}

.elite-modal-title { 
    font-size: 1.45rem !important; 
    font-weight: 800 !important; 
    color: #fff !important; 
    margin-bottom: 10px !important; 
    line-height: 1.2 !important; 
}

.elite-modal-text { 
    font-size: 0.92rem !important; 
    color: rgba(255,255,255,0.7) !important; 
    line-height: 1.6 !important; 
    margin-bottom: 20px !important; 
    white-space: pre-wrap !important;
}

.btn-invest-elite,
.btn-ad-action {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 10px !important;
    background: #f7941d !important;
    color: #000 !important;
    padding: 14px !important;
    border-radius: 10px !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 1px !important;
    transition: 0.3s !important;
}

.btn-invest-elite:hover,
.btn-ad-action:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(247,148,29,0.3); }

.elite-ad-url-preview {
    display: none;
    margin-top: 0.8rem;
    color: rgba(255,255,255,0.68) !important;
    font-size: 0.82rem;
    line-height: 1.35;
    text-align: center;
    text-decoration: none;
    word-break: normal;
    font-weight: 800;
    letter-spacing: 0.2px;
}
.elite-ad-url-preview:hover { color: #f7941d !important; }
.elite-ad-url-preview i { margin-right: 0.45rem; color: #f7941d; }

@media (max-width: 700px) {
    .ad-premium-card {
        max-width: 94vw !important;
    }
    .elite-ad-banner {
        height: 210px;
    }
    .elite-modal-body {
        padding: 22px !important;
    }
    .elite-modal-title {
        font-size: 1.35rem !important;
    }
}

@keyframes fadeInModal { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUpModal { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
