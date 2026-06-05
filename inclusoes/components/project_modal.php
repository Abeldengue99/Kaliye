<!-- Project Submission Modal: Elite Premium (Full Sync with Database) -->
<div id="projectModal" class="elite-modal-overlay" style="display: none; position: fixed; inset: 0; z-index: 9999999; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.92); backdrop-filter: blur(22px); -webkit-backdrop-filter: blur(22px); padding: 1.5rem; overflow-y: auto;">
    <div class="elite-modal-card" style="max-width: 820px; max-height: 90vh; overflow-y: auto;">
        <button onclick="document.getElementById('projectModal').style.display='none'" class="elite-modal-close">
            <i class="fas fa-times"></i>
        </button>

        <div class="elite-modal-body" style="padding: 3rem;">
            <!-- Sucesso State (Hidden by default) -->
            <div id="projectSuccessState" style="display: none; text-align: center; padding: 4rem 2rem; animation: pulseFade 1s infinite alternate;">
                <div style="width: 100px; height: 100px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
                    <i class="fas fa-check-circle" style="font-size: 3.5rem; color: #10b981;"></i>
                </div>
                <h2 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: #fff; font-size: 2rem; margin-bottom: 1rem;">BRILHANTE!</h2>
                <p style="color: rgba(255,255,255,0.6); font-size: 1.1rem; line-height: 1.6; max-width: 400px; margin: 0 auto 2rem;">O seu projecto foi enviado com Sucesso. Receberá uma notificação assim que for aprovado.</p>
                <div style="font-size: 0.8rem; font-weight: 800; color: var(--elite-orange); letter-spacing: 2px;">A REDIRECIONAR...</div>
            </div>

            <!-- Form Content -->
            <div id="projectModalContent">
                <div class="modal-header-elite" style="margin-bottom: 2rem;">
                    <span class="elite-label-micro" style="color: var(--elite-orange);">SUBMISSÃO DE PROJETO</span>
                    <h2 id="modalTitleText" class="elite-modal-title" style="margin-top: 5px;">Lance a sua próxima Referência.</h2>
                    
                    <div class="step-indicator-wrapper" style="display: flex; gap: 8px; margin-top: 1.5rem;">
                        <div class="step-dot active" id="dot1" style="flex: 1; height: 4px; background: var(--elite-orange); border-radius: 2px; transition: 0.3s;"></div>
                        <div class="step-dot" id="dot2" style="flex: 1; height: 4px; background: var(--surface-10); border-radius: 2px; transition: 0.3s;"></div>
                        <div class="step-dot" id="dot3" style="flex: 1; height: 4px; background: var(--surface-10); border-radius: 2px; transition: 0.3s;"></div>
                        <div class="step-dot" id="dot4" style="flex: 1; height: 4px; background: var(--surface-10); border-radius: 2px; transition: 0.3s;"></div>
                    </div>

                    <!-- Legend -->
                    <p style="margin-top: 1rem; font-size: 0.65rem; color: var(--text-muted); font-weight: 600;">
                        <span style="color: #f87171;">*</span> Campos obrigatórios
                    </p>
                </div>

                <form id="projectForm" action="interface_programacao/projects/post_project.php" method="POST" enctype="multipart/form-data" class="elite-form" novalidate onsubmit="return false">
                    <input type="hidden" name="project_id" id="modal_project_id">
                    <input type="hidden" name="redirect" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                    <input type="hidden" name="json" value="1">

                    <!-- STEP 1: Basic Identity -->
                    <div class="form-step" id="step1">
                        <div class="elite-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="elite-input-group" style="grid-column: span 2;">
                                <label class="elite-label-micro">TÍTULO DISRUPTIVO <span class="req-star">*</span></label>
                                <input type="text" name="title" id="titleInput" placeholder="Ex: KALIYE Quantum Link" required maxlength="80" class="elite-input-premium" oninput="updateCounter('titleInput','titleCounter',80)">
                                <div class="char-counter-row">
                                    <span id="titleCounter" class="char-counter">0 / 80</span>
                                </div>
                            </div>

                            <div class="elite-input-group">
                                <label class="elite-label-micro">CATEGORIA / SETOR <span class="req-star">*</span></label>
                                <input type="text" name="category" placeholder="Ex: DeepTech, AgriLink" required class="elite-input-premium">
                            </div>

                            <div class="elite-input-group">
                                <label class="elite-label-micro">ESTÁGIO ATUAL <span class="req-star">*</span></label>
                                <select name="project_stage" class="elite-input-premium" style="appearance: none;">
                                    <option value="Projecto">Conceito Inicial</option>
                                    <option value="MVP">MVP Funcional</option>
                                    <option value="Operacional">Tracionando</option>
                                    <option value="Escala">Pronto para Escala</option>
                                </select>
                            </div>

                            <div class="elite-input-group">
                                <label class="elite-label-micro">EQUIPA (Nº DE MEMBROS)</label>
                                <input type="number" name="team_size" min="1" placeholder="Ex: 1" class="elite-input-premium">
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Vision & Strategy -->
                    <div class="form-step" id="step2" style="display: none;">
                        <div class="elite-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="elite-input-group" style="grid-column: span 2;">
                                <label class="elite-label-micro">RESUMO DO IMPACTO (PITCH TEXTUAL) <span class="req-star">*</span></label>
                                <textarea name="description" id="descriptionInput" rows="4" required placeholder="Qual é o problema real que o seu projeto resolve?" maxlength="300" class="elite-input-premium" style="resize: none;" oninput="updateCounter('descriptionInput','descriptionCounter',300)"></textarea>
                                <div class="char-counter-row">
                                    <span id="descriptionCounter" class="char-counter">0 / 300</span>
                                </div>
                            </div>

                            <div class="elite-input-group" style="grid-column: span 2;">
                                <label class="elite-label-micro">PÚBLICO-ALVO / MERCADO</label>
                                <input type="text" name="target_audience" placeholder="Ex: Pequenos agricultores, Jovens de Luanda" class="elite-input-premium">
                            </div>

                            <div class="elite-input-group" style="grid-column: span 2;">
                                <label class="elite-label-micro">O QUE FALTA PARA AVANÇAR? (ROADMAP)</label>
                                <textarea name="needs_to_advance" id="needsInput" rows="2" placeholder="Ex: Investimento de capital..." maxlength="300" class="elite-input-premium" style="resize: none;" oninput="updateCounter('needsInput','needsCounter',300)"></textarea>
                                <div class="char-counter-row">
                                    <span id="needsCounter" class="char-counter">0 / 300</span>
                                </div>
                            </div>

                            <div class="elite-input-group" style="grid-column: span 2;">
                                <label class="elite-label-micro">STACK TECNOLÓGICA / TAGS (CSV)</label>
                                <input type="text" name="tags" placeholder="React, Node.js, PostgreSQL" class="elite-input-premium">
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Origins & Team -->
                    <div class="form-step" id="step3" style="display: none;">
                        <div class="elite-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                             <div class="elite-input-group" style="grid-column: span 2;">
                                <label class="elite-label-micro">ORIGEM do projecto <span class="req-star">*</span></label>
                                <textarea name="idea_origin" id="originInput" rows="3" placeholder="Como surgiu este projecto?" maxlength="300" class="elite-input-premium" style="resize: none;" oninput="updateCounter('originInput','originCounter',300)"></textarea>
                                <div class="char-counter-row">
                                    <span id="originCounter" class="char-counter">0 / 300</span>
                                </div>
                            </div>

                            <div class="elite-input-group" style="grid-column: span 2;">
                                <label class="elite-label-micro">MOTIVAÇÃO DO FUNDADOR <span class="req-star">*</span></label>
                                <textarea name="motivation" id="motivationInput" rows="3" placeholder="Por que é que você é a pessoa certa?" maxlength="300" class="elite-input-premium" style="resize: none;" oninput="updateCounter('motivationInput','motivationCounter',300)"></textarea>
                                <div class="char-counter-row">
                                    <span id="motivationCounter" class="char-counter">0 / 300</span>
                                </div>
                            </div>

                            <div class="elite-input-group">
                                <label class="elite-label-micro">TEMPO DE EXECUÇÃO</label>
                                <input type="text" name="execution_time" placeholder="Ex: 12 meses" class="elite-input-premium">
                            </div>

                            <div class="elite-input-group">
                                <label class="elite-label-micro">WEBSITE / URL</label>
                                <input type="url" name="project_url" placeholder="https://..." class="elite-input-premium">
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: Financials & Media -->
                    <div class="form-step" id="step4" style="display: none;">
                        <div class="elite-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="elite-input-group">
                                <label class="elite-label-micro">VALOR TOTAL (AKZ)</label>
                                <input type="number" name="budget" placeholder="10000000" class="elite-input-premium" onkeydown="if(['.','e','E',',','+','-'].includes(event.key)) event.preventDefault();">
                            </div>

                            <div class="elite-input-group">
                                <label class="elite-label-micro">META DE FINANCIAMENTO (AKZ)</label>
                                <input type="number" name="funding_goal" placeholder="5000000" class="elite-input-premium" onkeydown="if(['.','e','E',',','+','-'].includes(event.key)) event.preventDefault();">
                            </div>

                            <div class="elite-input-group">
                                <label class="elite-label-micro">INVESTIMENTO MÍNIMO (AKZ)</label>
                                <input type="number" name="minimum_investment" placeholder="10000" class="elite-input-premium" onkeydown="if(['.','e','E',',','+','-'].includes(event.key)) event.preventDefault();">
                            </div>

                            <div class="elite-input-group">
                                <label class="elite-label-micro">EQUITY MÁXIMA DISPONIVEL (%)</label>
                                <div style="position:relative;">
                                    <input type="number" name="equity_available" id="equityAvailInput" placeholder="Ex: 30" min="1" max="100" step="0.5" class="elite-input-premium" style="padding-right:3rem;" oninput="document.getElementById('equityAvailDisplay').textContent=this.value||'0'">
                                    <span style="position:absolute; right:1rem; top:50%; transform:translateY(-50%); color:var(--brand-primary); font-weight:900; font-size:1.1rem;">%</span>
                                </div>
                                <p style="margin:6px 0 0; font-size:0.65rem; color:var(--text-muted); font-weight:600;">Percentagem máx. do projecto que está disposto a ceder. Exibida em destaque para investidores. Actual: <strong id="equityAvailDisplay" style="color:#10b981;">0</strong>%</p>
                            </div>

                            <div class="elite-input-group">
                                <label class="elite-label-micro">DATA FIM DA CAMPANHA</label>
                                <input type="date" name="campaign_end_date" class="elite-input-premium">
                            </div>

                            <div class="elite-input-group" style="grid-column: span 2;">
                                <label class="elite-label-micro">PITCH EM VÍDEO <span class="req-star">*</span> — OBRIGATÓRIO PARA PUBLICAR</label>

                                <!-- Video quality notice -->
                                <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 1rem; padding: 0.85rem 1rem; background: rgba(247, 148, 29, 0.07); border: 1px solid rgba(247, 148, 29, 0.3); border-radius: 12px;">
                                    <i class="fas fa-video" style="color: var(--elite-orange); margin-top: 2px; font-size: 0.95rem; flex-shrink: 0;"></i>
                                    <p style="margin: 0; font-size: 0.7rem; color: rgba(255,255,255,0.75); font-weight: 600; line-height: 1.6;">
                                        O vídeo é o <strong style="color: var(--elite-orange);">cartão de visita do seu projecto</strong> perante investidores e mentores. 
                                        Certifique-se de que tem <strong style="color:#fff;">boa qualidade de imagem e áudio</strong>, 
                                        duração entre <strong style="color:#fff;">10 segundos e 5 minutos</strong>, 
                                        e que o pitch é claro e convincente. 
                                        <strong style="color: #f87171;">Sem vídeo válido não é possível publicar.</strong>
                                    </p>
                                </div>

                                <div id="existing-video-container" style="display: none; margin-bottom: 1rem; padding: 1rem; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px;">
                                    <video id="existing-video-preview" style="width: 100%; height: 140px; object-fit: cover; border-radius: 8px; background: #000;" controls muted playsinline preload="metadata">
                                        <source id="existing-video-source" src="" type="video/mp4">
                                        O seu navegador não suporta pré-visualização de vídeo.
                                    </video>
                                </div>

                                <div class="elite-upload-box" id="videoUploadBox">
                                    <input type="file" name="project_video" id="projectFileVideo" accept="video/*" class="elite-file-input" onchange="window.handleProjectVideoUpload(this)">
                                    <div class="upload-vibe">
                                        <i class="fas fa-play-circle" style="font-size: 1.5rem; color: var(--elite-orange);"></i>
                                        <span>Clique para carregar o seu Pitch (Máx 5 mins)</span>
                                    </div>
                                </div>

                                <!-- Video status indicator -->
                                <div id="videoStatusRow" style="display: flex; align-items: center; gap: 8px; margin-top: 8px;">
                                    <span id="videoStatusDot" style="width: 8px; height: 8px; border-radius: 50%; background: #6b7280; flex-shrink: 0; transition: background 0.4s;"></span>
                                    <p id="videoRequirementHint" style="margin: 0; font-size: 0.72rem; color: var(--text-muted); font-weight: 600;">Nenhum vídeo carregado. O vídeo é obrigatório para publicar.</p>
                                </div>

                                <div id="uploadProgressContainer" style="display: none; margin-top: 15px;">
                                    <div style="width: 100%; height: 6px; background: var(--surface-10); border-radius: 3px; overflow: hidden;">
                                        <div id="uploadProgressBar" style="width: 100%; height: 100%; background: linear-gradient(90deg, var(--elite-orange), #f7941d); animation: eliteProgress 2s linear infinite;"></div>
                                    </div>
                                </div>
                                <p id="videoDurationMsg" style="margin-top: 8px; font-size: 0.7rem; font-weight: 700;"></p>
                            </div>

                            <div class="elite-terms-box" style="grid-column: span 2; padding: 1rem; background: rgba(16, 185, 129, 0.05); border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.1);">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" name="terms_accepted" required style="width: 18px; height: 18px; accent-color: #10b981;">
                                    <span style="font-size: 0.75rem; color: var(--surface-70);">Confirmo que as informações são verídicas. <span class="req-star">*</span></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- NAVIGATION BUTTONS -->
                    <div class="modal-nav-elite" style="margin-top: 3rem; display: flex; gap: 1rem;">
                        <button type="button" id="prevBtn" onclick="window.changeProjectStep(-1)" style="display: none; flex: 1; background: var(--surface-5); color: #fff; border: 1px solid var(--surface-10); padding: 1rem; border-radius: 16px; font-weight: 700;">
                            ANTERIOR
                        </button>
                        <button type="button" id="nextBtn" onclick="window.changeProjectStep(1)" style="flex: 2; background: var(--elite-orange); color: #fff; border: none; padding: 1.1rem; border-radius: 16px; font-weight: 800; box-shadow: 0 10px 25px rgba(247, 148, 29, 0.25);">
                            PRÓXIMO 🡒
                        </button>
                        <button type="submit" id="submitBtn" disabled style="display: none; flex: 2; background: #10b981; color: #fff; border: none; padding: 1.1rem; border-radius: 16px; font-weight: 900; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.25); opacity: 0.5; cursor: not-allowed; transition: opacity 0.3s, cursor 0.3s;">
                            FINALIZAR E PUBLICAR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes eliteProgress { from { transform: translateX(-100%); } to { transform: translateX(100%); } }
@keyframes pulseFade { from { opacity: 0.8; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
.elite-input-premium {
    width: 100%; background: var(--surface-3); border: 1px solid var(--surface-8);
    border-radius: 16px; padding: 1rem 1.25rem; color: var(--text-primary); font-size: 0.9rem; transition: 0.3s;
}
.elite-input-premium::placeholder { color: rgba(255,255,255,0.2); }
.elite-input-premium:focus { border-color: var(--elite-orange); outline: none; background: var(--surface-5); }
.elite-upload-box {
    position: relative; height: 90px; border: 2px dashed var(--surface-10);
    border-radius: 16px; display: flex; align-items: center; justify-content: center;
    background: var(--surface-3); overflow: hidden; transition: border-color 0.3s;
}
.elite-upload-box.video-loaded { border-color: #10b981; border-style: solid; }
.elite-file-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; z-index: 5; }
.upload-vibe { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.upload-vibe span { font-size: 0.7rem; font-weight: 700; color: var(--text-secondary); }
/* Required asterisk */
.req-star { color: #f87171; font-weight: 900; margin-left: 2px; }
/* Character counter */
.char-counter-row { display: flex; justify-content: flex-end; margin-top: 4px; }
.char-counter { font-size: 0.62rem; font-weight: 700; color: var(--text-muted); transition: color 0.3s; }
.char-counter.near-limit { color: #f59e0b; }
.char-counter.at-limit { color: #f87171; }
/* Submit locked state */
#submitBtn:disabled { opacity: 0.45; cursor: not-allowed; }
#submitBtn:not(:disabled) { opacity: 1; cursor: pointer; }
</style>

<script>
// Character counter utility
function updateCounter(inputId, counterId, max) {
    var el = document.getElementById(inputId);
    var counter = document.getElementById(counterId);
    if (!el || !counter) return;
    var len = el.value.length;
    counter.textContent = len + ' / ' + max;
    counter.classList.remove('near-limit', 'at-limit');
    if (len >= max) {
        counter.classList.add('at-limit');
    } else if (len >= max * 0.85) {
        counter.classList.add('near-limit');
    }
}

// Video mandatory enforcement — runs after the existing handleProjectVideoUpload does its work
(function() {
    var _orig = window.handleProjectVideoUpload;
    function enforceVideoState(hasValidVideo) {
        var submitBtn = document.getElementById('submitBtn');
        var statusDot = document.getElementById('videoStatusDot');
        var hint = document.getElementById('videoRequirementHint');
        var uploadBox = document.getElementById('videoUploadBox');
        if (!submitBtn) return;
        if (hasValidVideo) {
            submitBtn.disabled = false;
            if (statusDot) { statusDot.style.background = '#10b981'; }
            if (hint) {
                hint.style.color = '#10b981';
                hint.textContent = 'Vídeo carregado com sucesso. Pode publicar o seu projecto.';
            }
            if (uploadBox) { uploadBox.classList.add('video-loaded'); }
        } else {
            submitBtn.disabled = true;
            if (statusDot) { statusDot.style.background = '#6b7280'; }
            if (hint) {
                hint.style.color = '';
                hint.textContent = 'Nenhum vídeo carregado. O vídeo é obrigatório para publicar.';
            }
            if (uploadBox) { uploadBox.classList.remove('video-loaded'); }
        }
    }

    // Wrap existing handler if it exists, or set up listener directly
    window.handleProjectVideoUpload = function(input) {
        if (typeof _orig === 'function') { _orig(input); }
        // Validate file selected
        if (!input || !input.files || !input.files[0]) {
            enforceVideoState(false);
            return;
        }
        // Use video element to verify it can play
        var tempVideo = document.createElement('video');
        tempVideo.preload = 'metadata';
        var url = URL.createObjectURL(input.files[0]);
        tempVideo.onloadedmetadata = function() {
            URL.revokeObjectURL(url);
            var dur = tempVideo.duration;
            var valid = (dur >= 10 && dur <= 300); // 10s min, 5min max
            enforceVideoState(valid);
        };
        tempVideo.onerror = function() {
            URL.revokeObjectURL(url);
            enforceVideoState(false);
        };
        tempVideo.src = url;
    };

    // Also watch if the form is reset (e.g. when modal opens for new project)
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('projectForm');
        if (form) {
            form.addEventListener('reset', function() { enforceVideoState(false); });
        }
        // Initialise on page load
        enforceVideoState(false);
    });
})();
</script>
