<?php
/**
 * Aksanti Communication Engine (Elite Version)
 */
?>
<script>
let currentReceiver = AKSANTI_CONFIG.startReceiver;
let currentGroup = null;
let chatType = 'direct';
let lastMsgId = 0;
let emojiInitialized = false;
let chatSafetyCustomHandler = null;

function chatEsc(value) {
    return String(value ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[c]));
}

function chatAsset(path) {
    path = String(path || '');
    return path.match(/^https?:\/\//) ? path : AKSANTI_CONFIG.baseUrl + path;
}

window.onload = function() {
    if (currentReceiver) {
        fetch(`${AKSANTI_CONFIG.baseUrl}servicos/social/chat_sync.php?get_user_name=${currentReceiver}`)
            .then(res => res.json())
            .then(data => {
                if (data.success !== false) loadChat(currentReceiver, data.full_name, data.profile_pic, data);
            });
    }
};

function loadChat(contactId, contactName, profilePic = null, meta = null) {
    chatType = 'direct';
    currentReceiver = contactId;
    currentGroup = null;
    document.getElementById('receiver_id').value = contactId;
    document.getElementById('group_id').value = '';
    document.getElementById('chat_type').value = 'direct';
    
    // Highlight Active Contact
    document.querySelectorAll('.contact-item-elite').forEach(i => i.classList.remove('active'));
    const activeItem = document.getElementById('contact-item-' + contactId);
    if(activeItem) activeItem.classList.add('active');

    const pfp = profilePic && profilePic !== 'default_profile.png' ? chatAsset(profilePic) : AKSANTI_CONFIG.baseUrl + 'recursos/images/default_profile.png';
    const presenceLabel = meta?.presence_label || 'Ligacao protegida';
    const isOnline = !!meta?.is_online;
    
    document.getElementById('chatHeader').innerHTML = `
        <div style="width: 50px; height: 50px; border-radius: 14px; border: 2px solid #f7941d; overflow: hidden; background: rgba(0,0,0,0.3);">
            <img src="${pfp}" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div style="flex-grow: 1; text-align: left;">
            <h4 style="color: #fff; font-size: 1.1rem; font-weight: 800; margin: 0;">${chatEsc(contactName)}</h4>
            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: ${isOnline ? '#10b981' : 'rgba(148,163,184,0.85)'};"></span>
                <span style="color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">${chatEsc(presenceLabel)} - Canal protegido</span>
            </div>
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            <button type="button" onclick="reportCurrentChat()" title="Denunciar conversa" style="width:36px;height:36px;border-radius:10px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.04);color:#fbbf24;cursor:pointer;">
                <i class="fas fa-flag"></i>
            </button>
            <button type="button" onclick="blockCurrentUser()" title="Bloquear utilizador" style="width:36px;height:36px;border-radius:10px;border:1px solid rgba(239,68,68,0.25);background:rgba(239,68,68,0.08);color:#ef4444;cursor:pointer;">
                <i class="fas fa-ban"></i>
            </button>
        </div>
    `;

    document.getElementById('chatInputArea').style.display = 'block';
    document.getElementById('chatMessages').innerHTML = '<div style="margin-top:10rem; text-align:center; color:rgba(255,255,255,0.1);"><i class="fas fa-spinner fa-spin"></i><br>Canalizando comunicações...</div>';
    
    initEmojiPicker();
    fetchMessages();
}

function loadGroupChat(groupId, groupName) {
    chatType = 'group';
    currentGroup = groupId;
    currentReceiver = null;
    document.getElementById('group_id').value = groupId;
    document.getElementById('receiver_id').value = '';
    document.getElementById('chat_type').value = 'group';
    
    // Esconder o botão de Jitsi por defeito em grupos normais
    const jitsiBtn = document.getElementById('jitsiMeetBtn');
    if(jitsiBtn) jitsiBtn.style.display = 'none';
    
    document.getElementById('chatHeader').innerHTML = `
        <div style="width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg, #f7941d, #ffb347); display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(247,148,29,0.3);">
            <i class="fas fa-users" style="color: white; font-size: 1.2rem;"></i>
        </div>
        <div style="flex-grow: 1; text-align: left;">
            <h4 style="color: #fff; font-size: 1.1rem; font-weight: 800; margin: 0;">${chatEsc(groupName)}</h4>
            <span style="color: #f7941d; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Sinergia de Grupo</span>
        </div>
    `;
    document.getElementById('chatInputArea').style.display = 'block';
    fetchGroupMessages();
}

// INOVAÃ‡ÃƒO 1: Motor de Abertura de Salas VIP de Mentoria
function loadMentorGroupChat(groupId, groupName, mentorId) {
    chatType = 'mentor_group'; // Estado da máquina de estados altera-se para o novo protocolo.
    currentGroup = groupId;
    currentReceiver = null;
    document.getElementById('group_id').value = groupId;
    document.getElementById('receiver_id').value = '';
    document.getElementById('chat_type').value = 'mentor_group';
    
    // Realce do contacto na aba esquerda
    document.querySelectorAll('.contact-item-elite').forEach(i => i.classList.remove('active'));
    
    // Regulação de Permissões: Só o dono (Mentor) pode iniciar a Vídeo-Chamada.
    const jitsiBtn = document.getElementById('jitsiMeetBtn');
    if (jitsiBtn) {
        if (AKSANTI_CONFIG.userId == mentorId) {
            jitsiBtn.style.display = 'block'; // O botão verde de Vídeo aparece para o Mentor
        } else {
            jitsiBtn.style.display = 'none'; // Fica invisível para os alunos.
        }
    }

    document.getElementById('chatHeader').innerHTML = `
        <div style="width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg, #059669, #10b981); display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(16,185,129,0.4);">
            <i class="fas fa-gem" style="color: white; font-size: 1.2rem;"></i>
        </div>
        <div style="flex-grow: 1; text-align: left; min-width: 0;">
            <h4 style="color: #fff; font-size: 1.1rem; font-weight: 800; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${chatEsc(groupName)}</h4>
            <span style="color: #10b981; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">SALA DE MENTORIA VIP</span>
        </div>
        <button id="mentorGroupMembersBtn" onclick="openMembersModal()" style="background: rgba(16,185,129,0.2); border: 1px solid #10b981; color: #10b981; padding: 8px 15px; border-radius: 12px; cursor: pointer; font-size: 0.85rem; font-weight: 800; transition: 0.3s; display: flex; align-items: center; gap: 8px; white-space: nowrap; flex-shrink: 0;">
            <i class="fas fa-users"></i> Gerir Membros
        </button>
    `;
    
    // Responsividade para mobile
    if (window.innerWidth < 600) {
        const btn = document.getElementById('mentorGroupMembersBtn');
        if (btn) {
            btn.style.padding = '6px 10px';
            btn.style.fontSize = '0.75rem';
            btn.style.gap = '5px';
        }
    }
    
    document.getElementById('chatInputArea').style.display = 'block';
    fetchMentorGroupMessages();
}

// Criar Grupo de Mentoria On-The-Fly (AJAX Backend)
function createMentorGroup() {
    openChatTextModal({
        title: 'Criar sala VIP',
        text: 'Dê um nome inspirador à sua turma VIP de mentoria.',
        label: 'Nome da sala',
        placeholder: 'Ex: Turma de validação de projectos',
        submitText: 'Criar sala',
        icon: 'fas fa-plus',
        onSubmit: (groupName) => {
            fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/create_mentor_group.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ group_name: groupName })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) window.location.reload();
                else showChatToast(data.error || 'Não foi possível criar a sala.');
            });
        }
    });
}

function fetchMessages() {
    if (!currentReceiver) return;
    refreshDirectPresence();
    fetch(`${AKSANTI_CONFIG.baseUrl}servicos/social/chat_sync.php?receiver_id=${currentReceiver}`)
        .then(res => res.json())
        .then(data => {
            if (Array.isArray(data)) renderMessages(data);
            else if (data.success === false) {
                document.getElementById('chatMessages').innerHTML = `<div class="chat-empty-state"><h3>${chatEsc(data.error || 'Conversa indisponivel')}</h3></div>`;
                document.getElementById('chatInputArea').style.display = 'none';
            }
        });
}

function refreshDirectPresence() {
    fetch(`${AKSANTI_CONFIG.baseUrl}servicos/social/chat_sync.php?get_user_name=${currentReceiver}`)
        .then(res => res.json())
        .then(data => {
            if (data.success === false) return;
            const statusEls = document.querySelectorAll('#chatHeader span');
            if (statusEls[0]) statusEls[0].style.background = data.is_online ? '#10b981' : 'rgba(148,163,184,0.85)';
            if (statusEls[1]) statusEls[1].textContent = `${data.presence_label || 'Ligacao protegida'} - Canal protegido`;
        })
        .catch(() => {});
}

function fetchGroupMessages() {
    if (!currentGroup) return;
    fetch(`${AKSANTI_CONFIG.baseUrl}servicos/social/get_group_messages.php?group_id=${currentGroup}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) renderMessages(data.messages);
        });
}

function fetchMentorGroupMessages() {
    if (!currentGroup) return;
    fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/get_mentor_group_messages.php?group_id=${currentGroup}`)
        .then(res => res.json())
        .then(data => {
            // Utilizamos a engine de renderização avançada com tags específicas de mentoria
            if (data.success) renderMentorMessages(data.messages);
        });
}

function renderMessages(data) {
    const container = document.getElementById('chatMessages');
    container.innerHTML = '';
    data.forEach(msg => {
        const isMine = msg.sender_id == AKSANTI_CONFIG.userId;
        const div = document.createElement('div');
        div.className = `msg-bubble-elite ${isMine ? 'msg-mine-elite' : 'msg-theirs-elite'}`;
        
        let mediaHtml = '';
        if (msg.media_url) {
            const path = (msg.media_url.startsWith('http') || msg.media_url.startsWith('/')) ? msg.media_url : AKSANTI_CONFIG.baseUrl + msg.media_url;
            if (msg.media_type === 'image') mediaHtml = `<img src="${path}" style="max-width: 100%; border-radius: 12px; margin-bottom: 0.8rem; cursor: zoom-in;" onclick="window.open(this.src, '_blank')">`;
            else if (msg.media_type === 'video') mediaHtml = `<video src="${path}" controls preload="metadata" playsinline style="max-width: 100%; border-radius: 12px; margin-bottom: 0.8rem;"></video>`;
            else mediaHtml = `<div style="padding: 1rem; background: rgba(0,0,0,0.2); border-radius: 12px; margin-bottom: 0.8rem; display: flex; align-items: center; gap: 12px; border: 1px solid rgba(255,255,255,0.05);"><i class="fas fa-file-pdf" style="font-size: 1.5rem; color: #ff3333;"></i><div style="flex:1;"><a href="${path}" target="_blank" style="color: #f7941d; font-weight:800; font-size:0.8rem;">DOC: ${msg.media_type.toUpperCase()}</a></div></div>`;
        }

        // Visto Logic
        const seenHtml = isMine ? `
            <span class="elite-visto ${msg.status === 'read' ? 'visto-read' : 'visto-delivered'}">
                <i class="fas ${msg.status === 'read' ? 'fa-check-double' : 'fa-check'}"></i>
            </span>
        ` : '';

        div.innerHTML = `
            ${mediaHtml}
            <div style="word-break: break-word;">${chatEsc(msg.content)}</div>
            ${!isMine ? `<button type="button" onclick="reportChatMessage(${Number(msg.sender_id)}, ${Number(msg.message_id || msg.id || 0)}, 'direct')" title="Denunciar mensagem" style="margin-top:8px;background:transparent;border:none;color:rgba(255,255,255,0.35);font-size:0.7rem;cursor:pointer;"><i class="fas fa-flag"></i> denunciar</button>` : ''}
            ${seenHtml}
        `;
        container.appendChild(div);
    });
    container.scrollTop = container.scrollHeight;
}

function reportCurrentChat() {
    if (!currentReceiver) return;
    openReportModal(currentReceiver, 0, 'direct');
}

function reportChatMessage(reportedUserId, messageId = 0, scope = 'direct') {
    openReportModal(reportedUserId, messageId, scope);
}

function openReportModal(reportedUserId, messageId = 0, scope = 'direct') {
    chatSafetyCustomHandler = null;
    document.getElementById('safetyMode').value = 'report';
    document.getElementById('safetyReportedUserId').value = reportedUserId;
    document.getElementById('safetyMessageId').value = messageId || '';
    document.getElementById('safetyScope').value = scope;
    document.getElementById('reportFields').style.display = 'block';
    document.getElementById('blockFields').style.display = 'none';
    document.getElementById('customTextFields').style.display = 'none';
    document.getElementById('chatSafetyIcon').innerHTML = '<i class="fas fa-flag"></i>';
    document.getElementById('chatSafetyKicker').textContent = 'Segurança do chat';
    document.getElementById('chatSafetyTitle').textContent = messageId ? 'Denunciar mensagem' : 'Denunciar conversa';
    document.getElementById('chatSafetyText').textContent = 'A equipa de moderacao tera acesso ao contexto necessario para analisar esta denuncia.';
    document.getElementById('chatSafetySubmit').textContent = 'Enviar denuncia';
    document.getElementById('chatSafetySubmit').className = 'chat-safety-btn danger';
    document.getElementById('chatSafetyModal').style.display = 'flex';
}

function closeChatSafetyModal() {
    document.getElementById('chatSafetyModal').style.display = 'none';
    document.getElementById('safetyDetails').value = '';
    document.getElementById('customTextInput').value = '';
    chatSafetyCustomHandler = null;
}

function blockCurrentUser() {
    if (!currentReceiver) return;
    chatSafetyCustomHandler = null;
    document.getElementById('safetyMode').value = 'block';
    document.getElementById('safetyReportedUserId').value = currentReceiver;
    document.getElementById('safetyMessageId').value = '';
    document.getElementById('safetyScope').value = 'direct';
    document.getElementById('reportFields').style.display = 'none';
    document.getElementById('blockFields').style.display = 'block';
    document.getElementById('customTextFields').style.display = 'none';
    document.getElementById('chatSafetyIcon').innerHTML = '<i class="fas fa-ban"></i>';
    document.getElementById('chatSafetyKicker').textContent = 'Controle de privacidade';
    document.getElementById('chatSafetyTitle').textContent = 'Bloquear utilizador';
    document.getElementById('chatSafetyText').textContent = 'Esta conversa deixara de aceitar novas mensagens entre voces. A acção fica registada para segurança.';
    document.getElementById('chatSafetySubmit').textContent = 'Bloquear';
    document.getElementById('chatSafetySubmit').className = 'chat-safety-btn danger';
    document.getElementById('chatSafetyModal').style.display = 'flex';
}

function openChatTextModal(options) {
    document.getElementById('safetyMode').value = 'custom';
    document.getElementById('reportFields').style.display = 'none';
    document.getElementById('blockFields').style.display = 'none';
    document.getElementById('customTextFields').style.display = 'block';
    document.getElementById('chatSafetyIcon').innerHTML = `<i class="${options.icon || 'fas fa-pen'}"></i>`;
    document.getElementById('chatSafetyKicker').textContent = options.kicker || 'Acção do chat';
    document.getElementById('chatSafetyTitle').textContent = options.title || 'Continuar';
    document.getElementById('chatSafetyText').textContent = options.text || '';
    document.getElementById('customTextLabel').textContent = options.label || 'Texto';
    document.getElementById('customTextInput').placeholder = options.placeholder || '';
    document.getElementById('customTextInput').value = '';
    
    // Mostrar input de arquivo se permitido
    const fileInput = document.getElementById('customFileInput');
    if (options.allowFile) {
        fileInput.style.display = 'block';
        fileInput.value = '';
    } else {
        fileInput.style.display = 'none';
    }
    
    document.getElementById('chatSafetySubmit').textContent = options.submitText || 'Continuar';
    document.getElementById('chatSafetySubmit').className = 'chat-safety-btn danger';
    chatSafetyCustomHandler = options.onSubmit || null;
    document.getElementById('chatSafetyModal').style.display = 'flex';
    setTimeout(() => document.getElementById('customTextInput').focus(), 50);
}

function submitChatSafetyModal(e) {
    e.preventDefault();
    const mode = document.getElementById('safetyMode').value;
    if (mode === 'custom') {
        const value = document.getElementById('customTextInput').value.trim();
        if (!value) {
            document.getElementById('chatSafetyText').textContent = 'Preencha este campo para continuar.';
            return;
        }
        const file = document.getElementById('customFileInput').files[0] || null;
        const handler = chatSafetyCustomHandler;
        closeChatSafetyModal();
        if (handler) handler(value, file);
        return;
    }
    const reportedUserId = document.getElementById('safetyReportedUserId').value;
    const submitBtn = document.getElementById('chatSafetySubmit');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'A processar...';

    const fd = new FormData();
    let endpoint = `${AKSANTI_CONFIG.baseUrl}interface_programacao/social/report_chat.php`;

    if (mode === 'block') {
        endpoint = `${AKSANTI_CONFIG.baseUrl}interface_programacao/social/block_user.php`;
        fd.append('user_id', reportedUserId);
        fd.append('reason', document.getElementById('blockReason').value);
    } else {
        fd.append('reported_user_id', reportedUserId);
        fd.append('message_id', document.getElementById('safetyMessageId').value || '');
        fd.append('scope', document.getElementById('safetyScope').value || 'direct');
        fd.append('category', document.getElementById('safetyCategory').value);
        fd.append('details', document.getElementById('safetyDetails').value || '');
    }

    fetch(endpoint, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            if (!data.success) {
                document.getElementById('chatSafetyText').textContent = data.error || 'Não foi possível processar o pedido.';
                return;
            }
            closeChatSafetyModal();
            if (mode === 'block') {
                document.getElementById('chatInputArea').style.display = 'none';
                fetchMessages();
            }
            showChatToast(data.message || 'Pedido registado com sucesso.');
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            document.getElementById('chatSafetyText').textContent = 'Não foi possível contactar o servidor.';
        });
}

function showChatToast(message) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = 'position:fixed;right:24px;bottom:24px;z-index:6000;background:#0f172a;color:#fff;border:1px solid rgba(247,148,29,.35);border-radius:14px;padding:1rem 1.2rem;font-weight:800;box-shadow:0 20px 50px rgba(0,0,0,.35);';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3200);
}

// INOVAÃ‡ÃƒO 2: Renderização Multimodal VIP (Com suporte nativo a Jitsi e Ãudios WebRTC)
function renderMentorMessages(data) {
    const container = document.getElementById('chatMessages');
    container.innerHTML = '';
    data.forEach(msg => {
        const isMine = msg.sender_id == AKSANTI_CONFIG.userId;
        const div = document.createElement('div');
        div.className = `msg-bubble-elite ${isMine ? 'msg-mine-elite' : 'msg-theirs-elite'}`;
        
        // Fabrico Dinâmico de Formatos Complexos HTML
        let mediaHtml = '';
        
        // Notas de Voz (Audio)
        if (msg.message_type === 'audio' && msg.file_url) {
            mediaHtml = `
            <div style="margin-bottom: 10px;">
                <audio controls src="${AKSANTI_CONFIG.baseUrl}${msg.file_url}" style="height: 40px; border-radius: 20px; outline: none; width: 100%; max-width: 300px;"></audio>
            </div>`;
        } 
        // Videochamadas Jitsi
        else if (msg.message_type === 'meeting' && msg.file_url) {
            const meetingData = typeof msg.message === 'string' ? JSON.parse(msg.message) : msg.message;
            mediaHtml = `
            <div style="background: rgba(16,185,129,0.1); border-left: 3px solid #10b981; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                <i class="fas fa-video" style="color:#10b981; font-size: 1.5rem; margin-bottom:10px;"></i>
                <h5 style="color:#10b981; margin:0 0 5px 0; font-size:0.9rem;">${chatEsc(meetingData.title || 'Sessão de Mentoria Iniciada')}</h5>
                <p style="font-size: 0.75rem; color: rgba(255,255,255,0.7); margin-bottom: 10px;">Clique para entrar na vídeo-chamada segura e criptografada.</p>
                <a href="${msg.file_url}" target="_blank" style="background: #10b981; color: #fff; padding: 8px 15px; border-radius: 20px; text-decoration: none; font-size: 0.8rem; font-weight: 800; display: inline-block;">
                    <i class="fas fa-arrow-right" style="margin-right: 5px;"></i> Entrar na Sessão
                </a>
            </div>`;
        }
        // Recursos/Materiais
        else if (msg.message_type === 'resource' && msg.file_url) {
            const resourceData = typeof msg.message === 'string' ? JSON.parse(msg.message) : msg.message;
            const fileExt = msg.file_url.split('.').pop().toLowerCase();
            let fileIcon = 'fas fa-file';
            let fileColor = '#94a3b8';
            
            if (['pdf'].includes(fileExt)) { fileIcon = 'fas fa-file-pdf'; fileColor = '#ef4444'; }
            else if (['doc', 'docx'].includes(fileExt)) { fileIcon = 'fas fa-file-word'; fileColor = '#3b82f6'; }
            else if (['xls', 'xlsx'].includes(fileExt)) { fileIcon = 'fas fa-file-excel'; fileColor = '#10b981'; }
            else if (['ppt', 'pptx'].includes(fileExt)) { fileIcon = 'fas fa-file-powerpoint'; fileColor = '#f59e0b'; }
            else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) { fileIcon = 'fas fa-image'; fileColor = '#8b5cf6'; }
            else if (['mp4', 'webm', 'avi'].includes(fileExt)) { fileIcon = 'fas fa-film'; fileColor = '#ec4899'; }
            
            mediaHtml = `
            <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <i class="${fileIcon}" style="font-size: 1.8rem; color: ${fileColor};"></i>
                <div style="flex: 1;">
                    <div style="color: #fff; font-weight: 700; font-size: 0.9rem; margin-bottom: 4px;">${chatEsc(resourceData.title || 'Arquivo')}</div>
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem;">${fileExt.toUpperCase()}</div>
                </div>
                <a href="${msg.file_url}" target="_blank" download style="background: ${fileColor}; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.7rem; font-weight: 800; white-space: nowrap;">
                    <i class="fas fa-download"></i>
                </a>
            </div>`;
        }

        // Output com nome da entidade emissora exposto
        div.innerHTML = `
            ${!isMine ? `<div style="font-size: 0.65rem; color: ${msg.sender_type === 'mentor' ? '#10b981' : 'rgba(255,255,255,0.4)'}; letter-spacing: 0.5px; margin-bottom: 3px; font-weight: 800;">${chatEsc(msg.sender_name).toUpperCase()} ${msg.sender_type === 'mentor' ? '<i class="fas fa-crown" style="color: #f7941d;"></i>' : ''}</div>` : ''}
            ${mediaHtml}
            ${msg.message && (msg.message_type === 'text' || !msg.message_type) ? `<div style="word-break: break-word; font-size:0.9rem;">${chatEsc(msg.message)}</div>` : ''}
            <div style="text-align: right; font-size: 0.6rem; color: rgba(255,255,255,0.4); margin-top: 5px;">${chatEsc(msg.time)}</div>
        `;
        container.appendChild(div);
    });
    container.scrollTop = container.scrollHeight;
}


// INOVAÃ‡ÃƒO 3: Motores Globais de Media (WebRTC Recording Stream)
let mediaRecorder;
let audioChunks = [];

async function startRecording() {
    if(chatType !== 'mentor_group') return; // Bloqueio se não for grupo VIP
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        mediaRecorder.ondataavailable = e => { if(e.data.size > 0) audioChunks.push(e.data); };
        mediaRecorder.start();
        
        let btn = document.getElementById('audioRecordBtn');
        btn.style.color = '#ef4444'; // Pisca vermelho indicando gravação em curso!
    } catch (err) {
        showChatToast('Permissão de microfone negada. Necessario para enviar notas de voz.');
    }
}

function stopRecording() {
    if(mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(t => t.stop()); // Liberta o HW Microfone da thread
        
        let btn = document.getElementById('audioRecordBtn');
        btn.style.color = 'var(--text-primary)';

        mediaRecorder.onstop = () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            audioChunks = []; // Drena o Buffer
            
            // Construção do pacote Ajax (Multi Part File Data)
            const formData = new FormData();
            formData.append('group_id', currentGroup);
            formData.append('message_type', 'audio');
            formData.append('audio_file', audioBlob, 'voicenote.webm');
            
            fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/send_mentor_group_message.php`, {
                method: 'POST', body: formData
            }).then(r => r.json()).then(data => {
                if(data.success) fetchMentorGroupMessages(); // Refresh rápido do Chat VIP
            });
        };
    }
}

// INOVAÃ‡ÃƒO 4: Lançamento de Instância Vídeo (Jitsi Meet P2P Bridge)
function startMentorMeeting() {
    openChatTextModal({
        title: 'Iniciar videochamada',
        text: 'Defina o topico desta sessão de mentoria.',
        label: 'Topico da sessão',
        placeholder: 'Ex: Validação do modelo financeiro',
        submitText: 'Criar reuniao',
        icon: 'fas fa-video',
        onSubmit: (title) => {
            const formData = new FormData();
            formData.append('group_id', currentGroup);
            formData.append('message_type', 'meeting');
            formData.append('meeting_title', title);
            formData.append('scheduled_at', new Date().toISOString().slice(0, 19).replace('T', ' ')); 

            fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/send_mentor_group_message.php`, {
                method: 'POST', body: formData
            }).then(r => r.json()).then(data => {
                if(data.success) {
                    fetchMentorGroupMessages();
                    window.open(data.file_url, '_blank', 'noopener,noreferrer'); 
                } else {
                    showChatToast(data.error || 'Não foi possível criar a reuniao.');
                }
            });
        }
    });
}
function previewMedia(input) {
    const preview = document.getElementById('mediaPreview');
    const name = document.getElementById('mediaFileName');
    const icon = document.getElementById('mediaIcon').querySelector('i');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'video/mp4', 'video/quicktime', 'audio/webm'];
        if (!allowed.includes(file.type) || file.size > 25 * 1024 * 1024) {
            showChatToast('Anexo invalido. Use JPG, PNG, WEBP, PDF, MP4, MOV ou WEBM ate 25MB.');
            clearMedia();
            return;
        }
        name.innerText = file.name;
        document.getElementById('mediaFileSize').innerText = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        
        if (file.type.includes('image')) icon.className = 'fas fa-image';
        else if (file.type.includes('video')) icon.className = 'fas fa-video';
        else icon.className = 'fas fa-file-alt';
        
        preview.style.display = 'flex';
    }
}

function clearMedia() {
    document.getElementById('mediaInput').value = '';
    document.getElementById('mediaPreview').style.display = 'none';
}

function handleSendElite(e) {
    e.preventDefault();
    const form = document.getElementById('chatForm');
    const formData = new FormData(form);
    const textInput = document.getElementById('message_content');
    
    if (textInput.value.trim() === '' && !document.getElementById('mediaInput').files[0] && formData.get('message_type') !== 'audio' && formData.get('message_type') !== 'meeting') return;

    let endpoint = `${AKSANTI_CONFIG.baseUrl}servicos/social/chat_sync.php`; // Default para Rede Direta
    if (chatType === 'group') {
        endpoint = `${AKSANTI_CONFIG.baseUrl}servicos/social/send_group_message.php`;
    } else if (chatType === 'mentor_group') {
        endpoint = `${AKSANTI_CONFIG.baseUrl}interface_programacao/social/send_mentor_group_message.php`; // API que criámos agora!
        if (!formData.get('message_type')) formData.append('message_type', 'text');
    }
    
    const sendBtn = form.querySelector('.elite-btn-send');
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(endpoint, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showChatToast(data.error || 'Não foi possível enviar a mensagem.');
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                return;
            }
            textInput.value = '';
            clearMedia();
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            // Auto refrescar as pipelines
            if (chatType === 'group') fetchGroupMessages(); 
            else if (chatType === 'mentor_group') fetchMentorGroupMessages(); 
            else fetchMessages();
        })
        .catch(() => {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
}

function initEmojiPicker() {
    if (emojiInitialized) return;
    const picker = document.querySelector('emoji-picker');
    const container = document.getElementById('emoji-picker-container');
    const btn = document.getElementById('emojiBtn');
    const input = document.getElementById('message_content');

    btn.onclick = (e) => {
        e.stopPropagation();
        container.style.display = container.style.display === 'none' ? 'block' : 'none';
    };

    picker.addEventListener('emoji-click', event => {
        input.value += event.detail.unicode;
        container.style.display = 'none';
        input.focus();
    });

    document.addEventListener('click', (e) => {
        if (!container.contains(e.target) && e.target !== btn) {
            container.style.display = 'none';
        }
    });

    emojiInitialized = true;
}

// Auto-refresh Pulse (O Sistema Nervoso do Chat)
setInterval(() => {
    if (chatType === 'group' && currentGroup) fetchGroupMessages();
    else if (chatType === 'mentor_group' && currentGroup) fetchMentorGroupMessages();
    else if (chatType === 'direct' && currentReceiver) fetchMessages();
}, 4000);

// FUNCIONALIDADE: Enviar Recurso/Material para a Sala VIP
function sendMentorGroupResource() {
    if (chatType !== 'mentor_group' || !currentGroup) {
        showChatToast('Selecione uma sala de mentoria primeiro.');
        return;
    }
    
    openChatTextModal({
        title: 'Enviar Material',
        text: 'Adicione um título e opcionalmente um arquivo para compartilhar com os mentorandos.',
        label: 'Título do Material',
        placeholder: 'Ex: Apresentação do Modelo de Negócio',
        submitText: 'Enviar Material',
        icon: 'fas fa-file',
        allowFile: true,
        onSubmit: (title, file) => {
            const formData = new FormData();
            formData.append('group_id', currentGroup);
            formData.append('title', title);
            formData.append('description', '');
            if (file) formData.append('resource_file', file);
            
            fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/send_mentor_group_resource.php`, {
                method: 'POST', body: formData
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    showChatToast('Material enviado com sucesso!');
                    fetchMentorGroupMessages();
                } else {
                    showChatToast(data.error || 'Erro ao enviar material.');
                }
            });
        }
    });
}

// FUNCIONALIDADE: Adicionar Mentorado à Sala VIP
function addMemberToMentorGroup() {
    if (chatType !== 'mentor_group' || !currentGroup) {
        showChatToast('Selecione uma sala de mentoria primeiro.');
        return;
    }
    
    openChatTextModal({
        title: 'Adicionar Mentorado',
        text: 'Digite o ID ou email do mentorado que deseja adicionar à sala.',
        label: 'ID ou Email do Mentorado',
        placeholder: 'Ex: 12345 ou mentorado@example.com',
        submitText: 'Adicionar',
        icon: 'fas fa-user-plus',
        onSubmit: (identifier) => {
            const formData = new FormData();
            formData.append('group_id', currentGroup);
            formData.append('identifier', identifier);
            
            fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/add_member_to_group.php`, {
                method: 'POST', body: formData
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    showChatToast(data.message || 'Mentorado adicionado com sucesso!');
                    // Atualizar lista de membros se necessário
                } else {
                    showChatToast(data.error || 'Erro ao adicionar mentorado.');
                }
            });
        }
    });
}

// Search Filter
function filterChats() {
    const term = document.getElementById('chatSearchInput').value.toLowerCase();
    document.querySelectorAll('.contact-item-elite').forEach(item => {
        const name = item.querySelector('h4').innerText.toLowerCase();
        item.style.display = name.includes(term) ? 'flex' : 'none';
    });
}

// ============ GERENCIAMENTO DE MEMBROS DA SALA VIP ============
function openMembersModal() {
    if (chatType !== 'mentor_group' || !currentGroup) {
        showChatToast('Selecione uma sala VIP primeiro.');
        return;
    }
    
    const modal = document.getElementById('membersModal');
    if (!modal) return;
    
    // Limpar conteúdo anterior
    document.getElementById('currentMembersList').innerHTML = '<p style="color: #888; text-align: center;">Carregando membros...</p>';
    document.getElementById('availableStudentsList').innerHTML = '<p style="color: #888; text-align: center;">Carregando mentorados...</p>';
    
    // Carregar membros atuais
    fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/get_mentor_group_members.php?group_id=${currentGroup}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.members) {
                let html = '<h4 style="color: var(--elite-orange); margin-bottom: 1rem; font-size: 0.95rem; text-transform: uppercase;">Membros Atuais (' + data.members.length + ')</h4>';
                
                if (data.members.length === 0) {
                    html += '<p style="color: #888; text-align: center; margin: 1rem 0;">Ainda nenhum membro adicionado</p>';
                } else {
                    html += '<div style="display: flex; flex-direction: column; gap: 10px;">';
                    data.members.forEach(member => {
                        html += `
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: rgba(255,255,255,0.05); border-radius: 12px; border-left: 3px solid var(--elite-orange);">
                                <div>
                                    <p style="color: #fff; font-weight: 600; margin: 0 0 4px 0;">${chatEsc(member.full_name)}</p>
                                    <p style="color: #888; font-size: 0.8rem; margin: 0;">${chatEsc(member.email)}</p>
                                </div>
                                <span style="background: rgba(16,185,129,0.2); color: #10b981; padding: 4px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 800;">${member.role.toUpperCase()}</span>
                            </div>
                        `;
                    });
                    html += '</div>';
                }
                document.getElementById('currentMembersList').innerHTML = html;
            }
        })
        .catch(e => {
            console.error(e);
            document.getElementById('currentMembersList').innerHTML = '<p style="color: #ef4444;">Erro ao carregar membros</p>';
        });
    
    // Carregar mentorados disponíveis com busca
    loadAvailableStudents();
    
    modal.style.display = 'flex';
}

// Carregar lista de mentorados disponíveis com busca
function loadAvailableStudents(search = '') {
    const container = document.getElementById('availableStudentsList');
    if (!container) {
        console.error('availableStudentsList element not found');
        return;
    }
    
    let url = `${AKSANTI_CONFIG.baseUrl}interface_programacao/social/get_mentor_students.php?group_id=${currentGroup}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    
    fetch(url)
        .then(r => r.json())
        .then(data => {
            console.log('loadAvailableStudents response:', data); // Debug log
            
            let html = `
                <div style="padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <h4 style="color: var(--elite-orange); margin-bottom: 1rem; font-size: 0.95rem; text-transform: uppercase;">Pesquisar e Adicionar Mentorados</h4>
                    <div style="display: flex; gap: 10px; margin-bottom: 1rem;">
                        <input type="text" id="memberSearchInput" placeholder="Pesquisar por nome ou email..." value="${chatEsc(search)}" oninput="loadAvailableStudents(this.value)" style="flex: 1; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-size: 0.9rem;">
                    </div>
            `;
            
            if (!data.success) {
                html += `<p style="color: #f97316; text-align: center; margin: 1rem 0;"><strong>⚠️ Erro ao carregar mentorados:</strong> ${chatEsc(data.error || 'Erro desconhecido')}</p>`;
            } else if (!data.students || data.students.length === 0) {
                html += '<p style="color: #888; text-align: center; margin: 1rem 0;">Nenhum mentorado disponível</p>';
            } else {
                html += '<div style="display: flex; flex-direction: column; gap: 8px; max-height: 250px; overflow-y: auto;">';
                data.students.forEach(student => {
                    html += `
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: rgba(255,165,0,0.08); border-radius: 8px; border: 1px solid rgba(255,165,0,0.2);">
                            <div style="flex: 1; min-width: 0;">
                                <p style="color: #fff; font-weight: 600; margin: 0; overflow: hidden; text-overflow: ellipsis;">${chatEsc(student.full_name)}</p>
                                <p style="color: #888; font-size: 0.75rem; margin: 0; overflow: hidden; text-overflow: ellipsis;">${chatEsc(student.email)}</p>
                            </div>
                            <button onclick="addSpecificStudent(${student.user_id})" style="background: var(--elite-orange); color: #fff; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 0.8rem; white-space: nowrap; margin-left: 10px;">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    `;
                });
                html += '</div>';
            }
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(e => {
            console.error('Error in loadAvailableStudents:', e);
            container.innerHTML = `<div style="padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1);"><h4 style="color: var(--elite-orange); margin-bottom: 1rem; font-size: 0.95rem; text-transform: uppercase;">Pesquisar e Adicionar Mentorados</h4><p style="color: #f97316;"><strong>❌ Erro de rede:</strong> ${chatEsc(e.message)}</p></div>`;
        });
}

// Adicionar um mentorado específico da lista
function addSpecificStudent(studentId) {
    if (chatType !== 'mentor_group' || !currentGroup) {
        showChatToast('Selecione uma sala de mentoria primeiro.');
        return;
    }
    
    const formData = new FormData();
    formData.append('group_id', currentGroup);
    formData.append('user_id', studentId);
    
    fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/add_member_to_group.php`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showChatToast('✅ Mentorado adicionado com sucesso!');
            // Recarregar as listas
            setTimeout(() => openMembersModal(), 500);
        } else {
            showChatToast('❌ ' + (data.error || 'Erro ao adicionar mentorado.'));
        }
    })
    .catch(e => {
        console.error(e);
        showChatToast('❌ Erro na requisição.');
    });
}

// Editar nome do grupo
function editGroupName() {
    if (!currentGroup) {
        showChatToast('Selecione uma sala primeiro.');
        return;
    }
    
    openChatTextModal({
        title: 'Editar Nome da Sala',
        text: 'Digite o novo nome para a sala VIP de mentoria.',
        label: 'Novo Nome',
        placeholder: 'Ex: Turma Advanced React',
        submitText: 'Atualizar',
        icon: 'fas fa-edit',
        onSubmit: (newName) => {
            if (!newName.trim()) {
                showChatToast('Nome não pode estar vazio.');
                return;
            }
            
            const formData = new FormData();
            formData.append('group_id', currentGroup);
            formData.append('group_name', newName.trim());
            
            fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/update_mentor_group.php`, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showChatToast('✅ ' + (data.message || 'Sala atualizada com sucesso!'));
                    // Recarregar lista de grupos
                    loadMentorGroups();
                } else {
                    // Mostrar mensagem do servidor ou fallback
                    const errorMsg = data.error || 'Erro ao atualizar sala.';
                    showChatToast(errorMsg);
                }
            })
            .catch(e => {
                console.error(e);
                showChatToast('❌ Erro de conexão. Verifique sua internet.');
            });
        }
    });
}

// Trocar imagem do grupo
function changeGroupImage() {
    if (!currentGroup) {
        showChatToast('Selecione uma sala primeiro.');
        return;
    }
    
    // Criar input de arquivo
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.onchange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validar tipo
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            showChatToast('❌ Tipo de arquivo inválido. Use JPG, PNG, GIF ou WebP.');
            return;
        }
        
        // Validar tamanho (máx 2MB)
        if (file.size > 2 * 1024 * 1024) {
            showChatToast('❌ Arquivo muito grande. Máximo 2MB.');
            return;
        }
        
        const formData = new FormData();
        formData.append('group_id', currentGroup);
        formData.append('group_image', file);
        
        fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/update_mentor_group.php`, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showChatToast('✅ Imagem atualizada com sucesso!');
                // Recarregar lista de grupos
                loadMentorGroups();
            } else {
                // Mostrar mensagem do servidor ou fallback
                const errorMsg = data.error || 'Erro ao atualizar imagem.';
                showChatToast(errorMsg);
            }
        })
        .catch(e => {
            console.error(e);
            showChatToast('❌ Erro de conexão. Verifique sua internet.');
        });
    };
    fileInput.click();
}

// Excluir grupo
function deleteGroup() {
    if (!currentGroup) {
        showChatToast('Selecione uma sala primeiro.');
        return;
    }
    
    // Confirmar exclusão
    if (!confirm('⚠️ Tem a certeza que deseja EXCLUIR esta sala?\n\nEsta ação não pode ser desfeita e todos os membros e mensagens serão removidos permanentemente.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('group_id', currentGroup);
    
    fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/delete_mentor_group.php`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showChatToast('✅ Sala excluída com sucesso!');
            closeMembersModal();
            currentGroup = null;
            chatType = 'direct';
            // Recarregar lista de grupos
            loadMentorGroups();
        } else {
            showChatToast('❌ ' + (data.error || 'Erro ao excluir sala.'));
        }
    })
    .catch(e => {
        console.error(e);
        showChatToast('❌ Erro na requisição.');
    });
}

function closeMembersModal() {
    const modal = document.getElementById('membersModal');
    if (modal) modal.style.display = 'none';
}

// Fechar modal quando clicar fora
document.addEventListener('click', (e) => {
    const modal = document.getElementById('membersModal');
    if (modal && e.target === modal) {
        closeMembersModal();
    }
});

// Adaptação: Adicionar membro usando o input do modal
function addMemberToMentorGroupFromModal() {
    const identifier = document.getElementById('newMemberInput')?.value?.trim();
    if (!identifier) {
        showChatToast('Digite um ID ou email válido.');
        return;
    }
    
    if (chatType !== 'mentor_group' || !currentGroup) {
        showChatToast('Selecione uma sala de mentoria primeiro.');
        return;
    }
    
    const formData = new FormData();
    formData.append('group_id', currentGroup);
    formData.append('identifier', identifier);
    
    fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/add_member_to_group.php`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showChatToast('✅ ' + (data.message || 'Membro adicionado com sucesso!'));
            document.getElementById('newMemberInput').value = '';
            // Recarregar a lista de membros
            setTimeout(() => openMembersModal(), 500);
        } else {
            showChatToast('❌ ' + (data.error || 'Erro ao adicionar membro.'));
        }
    })
    .catch(e => {
        console.error(e);
        showChatToast('❌ Erro na requisição.');
    });
}

// Override: Tornar addMemberToMentorGroup compatível com ambos os contextos
window.addMemberToMentorGroup_original = addMemberToMentorGroup;
function addMemberToMentorGroup() {
    // Se estamos no modal de gerenciamento, usa a versão do modal
    if (document.getElementById('membersModal').style.display === 'flex') {
        addMemberToMentorGroupFromModal();
    } else {
        // Se estamos na interface de chat, abre o modal de texto
        if (chatType !== 'mentor_group' || !currentGroup) {
            showChatToast('Selecione uma sala de mentoria primeiro.');
            return;
        }
        
        openChatTextModal({
            title: 'Adicionar Mentorado',
            text: 'Digite o ID ou email do mentorado que deseja adicionar à sala.',
            label: 'ID ou Email do Mentorado',
            placeholder: 'Ex: 12345 ou mentorado@example.com',
            submitText: 'Adicionar',
            icon: 'fas fa-user-plus',
            onSubmit: (identifier) => {
                const formData = new FormData();
                formData.append('group_id', currentGroup);
                formData.append('identifier', identifier);
                
                fetch(`${AKSANTI_CONFIG.baseUrl}interface_programacao/social/add_member_to_group.php`, {
                    method: 'POST', body: formData
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        showChatToast(data.message || 'Mentorado adicionado com sucesso!');
                        // Atualizar lista de membros se necessário
                    } else {
                        showChatToast(data.error || 'Erro ao adicionar mentorado.');
                    }
                });
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('message_content');
    if (!input) return;
    let lastTypingPing = 0;
    input.addEventListener('input', () => {
        if (chatType !== 'direct' || !currentReceiver) return;
        const now = Date.now();
        if (now - lastTypingPing < 2500) return;
        lastTypingPing = now;
        const fd = new FormData();
        fd.append('receiver_id', currentReceiver);
        fetch(`${AKSANTI_CONFIG.baseUrl}servicos/social/update_typing_status.php`, { method: 'POST', body: fd }).catch(() => {});
    });
});
</script>


