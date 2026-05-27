<?php
/**
 * Component: Elite Chat Area (Aksanti Communication Hub)
 */
?>
<section class="chat-main-elite" id="chatMainArea">

    <!-- Header Elite -->
    <div id="chatHeader" class="chat-header-elite">
        <div class="chat-idle-header">
            <span><i class="fas fa-lock"></i> Mensagens privadas</span>
            <strong>Escolha uma conversa</strong>
        </div>
    </div>

    <!-- Messages Zone Elite -->
    <div id="chatMessages" class="chat-messages-elite">
        <div class="chat-empty-state">
            <div class="chat-empty-orbit" aria-hidden="true">
                <span></span>
                <i class="fas fa-comments"></i>
            </div>
            <span class="chat-empty-kicker">Canal KALIYE</span>
            <h3>Selecione uma conexao para continuar.</h3>
            <p>Organize conversas diretas, salas de mentoria e sinergias de projeto num espaco reservado para trocar ideias com contexto.</p>
            <div class="chat-empty-actions">
                <span><i class="fas fa-shield-alt"></i> Canal privado</span>
                <span><i class="fas fa-paperclip"></i> Anexos</span>
                <span><i class="fas fa-video"></i> Mentoria</span>
            </div>
        </div>
    </div>

    <!-- Multi-media Preview Elite -->
    <div id="mediaPreview" class="media-preview-elite" style="display: none;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div id="mediaIcon" style="width: 40px; height: 40px; border-radius: 10px; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; border: 1px solid #f7941d;">
                <i class="fas fa-file-alt" style="color: #f7941d;"></i>
            </div>
            <div>
                <span id="mediaFileName" style="font-size: 0.8rem; color: #fff; font-weight: 700; display: block;">ficheiro_anexo.png</span>
                <span id="mediaFileSize" style="font-size: 0.65rem; color: rgba(255,255,255,0.4);">Aguardando envio...</span>
            </div>
        </div>
        <button type="button" onclick="clearMedia()" style="background: none; border: none; color: #ef4444; font-size: 1rem; cursor: pointer;"><i class="fas fa-trash-alt"></i></button>
    </div>

    <!-- Input Area Elite -->
    <div id="chatInputArea" class="chat-input-area-elite" style="display: none;">
        <form id="chatForm" onsubmit="handleSendElite(event)" enctype="multipart/form-data">
            <input type="hidden" id="receiver_id" name="receiver_id">
            <input type="hidden" id="group_id" name="group_id">
            <input type="hidden" id="chat_type" value="direct">

            <div class="elite-input-wrapper">
                <!-- Zona Oculta Dinamica: Botao de Video-Chamada -->
                <button type="button" id="jitsiMeetBtn" class="elite-btn-icon" title="Iniciar Reuniao Video Jitsi" style="display: none; color: #10b981;" onclick="startMentorMeeting()">
                    <i class="fas fa-video"></i>
                </button>

                <button type="button" onclick="document.getElementById('mediaInput').click()" class="elite-btn-icon" title="Anexar dossier">
                    <i class="fas fa-paperclip"></i>
                </button>
                <input type="file" id="mediaInput" name="media" style="display: none;" onchange="previewMedia(this)">

                <button type="button" id="emojiBtn" class="elite-btn-icon" title="Emoji">
                    <i class="far fa-smile"></i>
                </button>

                <input type="text" id="message_content" name="content" placeholder="Escrever a sua mensagem..." autocomplete="off">

                <!-- Microfone WebRTC Nativo -->
                <button type="button" id="audioRecordBtn" class="elite-btn-icon" title="Gravar Nota de Voz" onmousedown="startRecording()" onmouseup="stopRecording()">
                    <i class="fas fa-microphone"></i>
                </button>

                <button type="submit" class="elite-btn-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Emoji Picker Logic -->
    <div id="emoji-picker-container" style="display: none; position: absolute; bottom: 100px; left: 100px; z-index: 1001;">
        <emoji-picker class="dark" style="border: 1px solid rgba(255,255,255,0.1); border-radius: 20px;"></emoji-picker>
    </div>

</section>

<div id="chatSafetyModal" class="chat-safety-modal" style="display:none;">
    <div class="chat-safety-panel">
        <button type="button" class="chat-safety-close" onclick="closeChatSafetyModal()" aria-label="Fechar">
            <i class="fas fa-times"></i>
        </button>
        <div class="chat-safety-icon" id="chatSafetyIcon"><i class="fas fa-flag"></i></div>
        <span class="chat-safety-kicker" id="chatSafetyKicker">Seguranca do chat</span>
        <h3 id="chatSafetyTitle">Denunciar conversa</h3>
        <p id="chatSafetyText">Ajude a equipa KALIYE a analisar comportamentos suspeitos.</p>

        <form id="chatSafetyForm" onsubmit="submitChatSafetyModal(event)">
            <input type="hidden" id="safetyMode" value="report">
            <input type="hidden" id="safetyReportedUserId" value="">
            <input type="hidden" id="safetyMessageId" value="">
            <input type="hidden" id="safetyScope" value="direct">

            <div id="reportFields">
                <label class="chat-safety-label" for="safetyCategory">Motivo</label>
                <select id="safetyCategory" class="chat-safety-control">
                    <option value="fraud">Fraude / golpe</option>
                    <option value="phishing">Phishing / link suspeito</option>
                    <option value="spam">Spam</option>
                    <option value="harassment">Assedio</option>
                    <option value="abuse">Abuso ou ameaca</option>
                    <option value="other">Outro</option>
                </select>

                <label class="chat-safety-label" for="safetyDetails">Detalhes</label>
                <textarea id="safetyDetails" class="chat-safety-control" rows="4" placeholder="Descreva rapidamente o que aconteceu..."></textarea>
            </div>

            <div id="blockFields" style="display:none;">
                <label class="chat-safety-label" for="blockReason">Motivo do bloqueio</label>
                <select id="blockReason" class="chat-safety-control">
                    <option value="manual">Preferencia pessoal</option>
                    <option value="spam">Spam</option>
                    <option value="fraud">Suspeita de fraude</option>
                    <option value="harassment">Assedio</option>
                </select>
            </div>

            <div id="customTextFields" style="display:none;">
                <label class="chat-safety-label" for="customTextInput" id="customTextLabel">Detalhes</label>
                <input type="text" id="customTextInput" class="chat-safety-control" autocomplete="off">
            </div>

            <div class="chat-safety-actions">
                <button type="button" class="chat-safety-btn ghost" onclick="closeChatSafetyModal()">Cancelar</button>
                <button type="submit" class="chat-safety-btn danger" id="chatSafetySubmit">Enviar denuncia</button>
            </div>
        </form>
    </div>
</div>

<style>
    .chat-safety-modal {
        position: fixed;
        inset: 0;
        z-index: 5000;
        background: rgba(3, 7, 18, 0.72);
        backdrop-filter: blur(12px);
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
    }
    .chat-safety-panel {
        position: relative;
        width: min(520px, 100%);
        background: #070d1a;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 22px;
        box-shadow: 0 30px 90px rgba(0, 0, 0, 0.45);
        padding: 2rem;
        color: #fff;
    }
    .chat-safety-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.04);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
    }
    .chat-safety-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        background: rgba(247, 148, 29, 0.12);
        color: #f7941d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        margin-bottom: 1rem;
    }
    .chat-safety-kicker, .chat-safety-label {
        display: block;
        font-size: 0.7rem;
        font-weight: 900;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: #f7941d;
    }
    .chat-safety-panel h3 {
        margin: 0.35rem 0 0.5rem;
        font-size: 1.4rem;
        font-weight: 900;
    }
    .chat-safety-panel p {
        margin: 0 0 1.4rem;
        color: rgba(255, 255, 255, 0.55);
        line-height: 1.55;
    }
    .chat-safety-label {
        margin: 1rem 0 0.5rem;
        color: rgba(255, 255, 255, 0.58);
    }
    .chat-safety-control {
        width: 100%;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(0, 0, 0, 0.28);
        color: #fff;
        border-radius: 12px;
        padding: 0.85rem 1rem;
        outline: none;
    }
    .chat-safety-control:focus {
        border-color: rgba(247, 148, 29, 0.55);
    }
    .chat-safety-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.8rem;
        margin-top: 1.5rem;
    }
    .chat-safety-btn {
        border: 0;
        border-radius: 12px;
        padding: 0.85rem 1.2rem;
        font-weight: 900;
        cursor: pointer;
    }
    .chat-safety-btn.ghost {
        background: rgba(255, 255, 255, 0.06);
        color: rgba(255, 255, 255, 0.72);
    }
    .chat-safety-btn.danger {
        background: #ef4444;
        color: #fff;
    }
</style>

<!-- Import Emoji Element -->
<script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js"></script>
