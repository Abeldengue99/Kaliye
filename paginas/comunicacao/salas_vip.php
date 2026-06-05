<?php
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';
require_once '../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$room_id_param = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
?>
<link rel="stylesheet" href="../../recursos/css/style.css">
<style>
    .vip-chat-container {
        display: flex;
        height: 80vh;
        max-width: 1200px;
        margin: 20px auto;
        background: #050a15;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.05);
        overflow: hidden;
    }
    .vip-sidebar {
        width: 300px;
        background: #0d1628;
        border-right: 1px solid rgba(255,255,255,0.05);
        display: flex;
        flex-direction: column;
    }
    .vip-sidebar-header {
        padding: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        font-weight: 800;
        color: #f59e0b;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .room-list {
        flex: 1;
        overflow-y: auto;
    }
    .room-item {
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.02);
        cursor: pointer;
        transition: 0.3s;
    }
    .room-item:hover, .room-item.active {
        background: rgba(245, 158, 11, 0.1);
        border-left: 4px solid #f59e0b;
    }
    .room-title {
        font-weight: 700;
        color: #fff;
        margin-bottom: 5px;
    }
    .room-desc {
        font-size: 0.75rem;
        color: rgba(255,255,255,0.5);
    }
    
    .vip-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #020617;
    }
    .chat-header {
        padding: 20px;
        background: #0d1628;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .msg-box {
        max-width: 70%;
        display: flex;
        flex-direction: column;
    }
    .msg-box.mine {
        align-self: flex-end;
        align-items: flex-end;
    }
    .msg-box.theirs {
        align-self: flex-start;
        align-items: flex-start;
    }
    .msg-sender {
        font-size: 0.7rem;
        color: rgba(255,255,255,0.5);
        margin-bottom: 3px;
        font-weight: 700;
    }
    .msg-bubble {
        padding: 12px 18px;
        border-radius: 12px;
        color: #fff;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    .mine .msg-bubble {
        background: #f59e0b;
        border-bottom-right-radius: 2px;
    }
    .theirs .msg-bubble {
        background: #1e293b;
        border-bottom-left-radius: 2px;
    }
    .chat-input-area {
        padding: 15px 20px;
        background: #0d1628;
        border-top: 1px solid rgba(255,255,255,0.05);
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .chat-input-area input[type="text"] {
        flex: 1;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        padding: 12px 15px;
        color: #fff;
    }
    .btn-file {
        background: transparent;
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.5);
        border-radius: 8px;
        padding: 0 15px;
        height: 45px;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-file:hover { background: rgba(245, 158, 11, 0.1); }
    .chat-input-area button[type="submit"] {
        background: #f59e0b;
        color: #000;
        border: none;
        border-radius: 8px;
        padding: 0 20px;
        height: 45px;
        font-weight: 800;
        cursor: pointer;
        transition: 0.2s;
    }
    .chat-input-area button[type="submit"]:hover {
        opacity: 0.8;
    }
    .file-attachment {
        margin-top: 5px;
        padding: 8px;
        background: rgba(0,0,0,0.2);
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
        display: inline-block;
    }
    .file-attachment a {
        color: #60a5fa;
        text-decoration: none;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .file-attachment img {
        max-width: 200px;
        border-radius: 8px;
        margin-top: 5px;
    }
</style>

<div class="vip-chat-container">
    <div class="vip-sidebar">
        <div class="vip-sidebar-header">
            <i class="fas fa-crown"></i> Salas VIP
        </div>
        <div class="room-list" id="roomList">
            <div style="padding: 20px; color: rgba(255,255,255,0.5); text-align: center;">A carregar salas...</div>
        </div>
    </div>
    
    <div class="vip-main">
        <div id="chatInterface" style="display: none; height: 100%; flex-direction: column;">
            <div class="chat-header">
                <div>
                    <h3 id="activeRoomTitle" style="margin: 0; color: #fff;">Sala</h3>
                    <small id="activeRoomDesc" style="color: rgba(255,255,255,0.5);"></small>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <!-- Mensagens aparecerão aqui -->
            </div>
            
            <form class="chat-input-area" id="chatForm">
                <input type="file" id="fileInput" style="display:none;" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.png,.jpg,.jpeg,.gif,.zip,.rar,.txt">
                <button type="button" class="btn-file" onclick="document.getElementById('fileInput').click()" title="Anexar Ficheiro">
                    <i class="fas fa-paperclip"></i>
                </button>
                <div id="filePreview" style="display:none; color:#f59e0b; font-size:0.8rem; margin-right:5px; max-width: 100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></div>
                
                <input type="text" id="msgInput" placeholder="Escreva uma mensagem..." autocomplete="off">
                <button type="submit"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
        
        <div id="emptyState" class="empty-state">
            <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
            Selecione uma Sala VIP
        </div>
    </div>
</div>

<script>
    let activeChatId = <?php echo $room_id_param; ?>;
    let lastMessageId = 0;
    let currentUserId = <?php echo $_SESSION['user_id']; ?>;
    let pollingInterval = null;

    document.getElementById('fileInput').addEventListener('change', function(e) {
        if(this.files.length > 0) {
            document.getElementById('filePreview').innerText = this.files[0].name;
            document.getElementById('filePreview').style.display = 'block';
        } else {
            document.getElementById('filePreview').style.display = 'none';
        }
    });

    function loadRooms() {
        fetch('../../interface_programacao/vip_chat/get_user_rooms.php')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('roomList');
            if(!data.success || data.rooms.length === 0) {
                list.innerHTML = '<div style="padding:20px; text-align:center; color:rgba(255,255,255,0.5);">Nenhuma sala disponível.</div>';
                return;
            }
            
            list.innerHTML = '';
            data.rooms.forEach(r => {
                const div = document.createElement('div');
                div.className = 'room-item ' + (activeChatId == r.id ? 'active' : '');
                div.innerHTML = `
                    <div class="room-title">${r.title}</div>
                    <div class="room-desc">${r.total_participants} Membros</div>
                `;
                div.onclick = () => selectRoom(r.id, r.title, r.description);
                list.appendChild(div);
                
                // Se havia um room_id no URL, abrimos logo essa sala
                if(activeChatId == r.id && !pollingInterval) {
                    selectRoom(r.id, r.title, r.description);
                }
            });
        });
    }

    function selectRoom(id, title, desc) {
        activeChatId = id;
        lastMessageId = 0;
        
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('chatInterface').style.display = 'flex';
        
        document.getElementById('activeRoomTitle').innerText = title;
        document.getElementById('activeRoomDesc').innerText = desc || 'Sala de Networking Restrita';
        document.getElementById('chatMessages').innerHTML = '';
        
        document.querySelectorAll('.room-item').forEach(el => el.classList.remove('active'));
        loadRooms();

        if(pollingInterval) clearInterval(pollingInterval);
        fetchMessages();
        pollingInterval = setInterval(fetchMessages, 3000);
    }

    function fetchMessages() {
        if(!activeChatId) return;
        
        fetch(`../../interface_programacao/vip_chat/get_room_messages.php?chat_id=${activeChatId}&last_id=${lastMessageId}`)
        .then(res => res.json())
        .then(data => {
            if(data.success && data.messages.length > 0) {
                const container = document.getElementById('chatMessages');
                let shouldScroll = (container.scrollTop + container.clientHeight >= container.scrollHeight - 50);
                
                data.messages.forEach(m => {
                    const isMine = (m.sender_id == currentUserId);
                    const div = document.createElement('div');
                    div.className = 'msg-box ' + (isMine ? 'mine' : 'theirs');
                    
                    let fileHtml = '';
                    if(m.file_path) {
                        const isImage = ['png','jpg','jpeg','gif'].includes(m.file_type);
                        if(isImage) {
                            fileHtml = `<div class="file-attachment"><a href="../../${m.file_path}" target="_blank"><img src="../../${m.file_path}"></a></div>`;
                        } else {
                            fileHtml = `<div class="file-attachment"><a href="../../${m.file_path}" target="_blank" download><i class="fas fa-file-download"></i> ${m.file_name}</a></div>`;
                        }
                    }

                    div.innerHTML = `
                        <div class="msg-sender">${isMine ? 'Eu' : m.full_name}</div>
                        <div class="msg-bubble">${m.message_text} ${fileHtml}</div>
                    `;
                    container.appendChild(div);
                    lastMessageId = m.id;
                });
                
                if(shouldScroll || data.messages.length > 0) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        });
    }

    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('msgInput');
        const fileInput = document.getElementById('fileInput');
        const text = input.value.trim();
        const hasFile = fileInput.files.length > 0;
        
        if(!text && !hasFile) return;
        if(!activeChatId) return;
        
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        const formData = new FormData();
        formData.append('chat_id', activeChatId);
        formData.append('message', text);
        if(hasFile) formData.append('file', fileInput.files[0]);
        
        fetch('../../interface_programacao/vip_chat/send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            if(data.success) {
                input.value = '';
                fileInput.value = '';
                document.getElementById('filePreview').style.display = 'none';
                fetchMessages();
            } else {
                alert(data.error);
            }
        }).catch(err => {
            submitBtn.disabled = false;
        });
    });

    loadRooms();
</script>

<?php require_once '../../inclusoes/rodape.php'; ?>
