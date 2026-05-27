<script>
let allDoubts = [];
let doubtsSeenMarked = false;

async function loadDoubts() {
    try {
        const container = document.getElementById('doubts-container');
        if (!container) return;
        
        const response = await fetch('../../interface_programacao/social/get_doubts.php');
        const data = await response.json();
        
        if (data.success) {
            allDoubts = data.doubts;
            renderDoubts(allDoubts);
            updateStatsBar(allDoubts);
            await markDoubtsSeen();
        } else {
            container.innerHTML = `<div style="text-align: center; padding: 3rem; color: var(--text-secondary);"><i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i><p>${data.message || 'Erro ao carregar dúvidas'}</p></div>`;
        }
    } catch (error) {
        console.error('Erro:', error);
    }
}

async function markDoubtsSeen() {
    if (doubtsSeenMarked) return;
    doubtsSeenMarked = true;

    try {
        const response = await fetch('../../interface_programacao/social/mark_doubts_seen.php', {
            method: 'POST',
            cache: 'no-store'
        });
        const data = await response.json();

        if (data.success) {
            const badge = document.getElementById('doubtBadge');
            if (badge) {
                badge.textContent = '';
                badge.style.display = 'none';
            }

            if (typeof window.fetchRealtimeCounts === 'function') {
                window.fetchRealtimeCounts(true);
            }
        } else {
            doubtsSeenMarked = false;
        }
    } catch (error) {
        console.error('Erro ao marcar duvidas como vistas:', error);
        doubtsSeenMarked = false;
    }
}

function updateStatsBar(doubts) {
    const bar = document.getElementById('doubts-stats-bar');
    if (!bar) return;
    const total    = doubts.length;
    const open     = doubts.filter(d => d.status === 'open').length;
    const resolved = doubts.filter(d => d.status === 'resolved').length;
    const elTotal    = document.getElementById('stat-total');
    const elOpen     = document.getElementById('stat-open');
    const elResolved = document.getElementById('stat-resolved');
    if (elTotal)    elTotal.textContent    = total;
    if (elOpen)     elOpen.textContent     = open;
    if (elResolved) elResolved.textContent = resolved;
    bar.style.opacity = '1';
}

function renderDoubts(doubts) {
    const container = document.getElementById('doubts-container');
    if (!container) return;
    
    if (doubts.length === 0) {
        container.innerHTML = `
        <div class="dq-empty">
            <i class="fas fa-comments"></i>
            <p>Nenhuma dúvida encontrada</p>
            <button onclick="openDoubtModal()" class="dq-new-btn" style="margin: 1.5rem auto 0; font-size: 0.65rem;">
                <i class="fas fa-plus"></i> Publicar a Primeira Dúvida
            </button>
        </div>`;
        return;
    }
    
    const badgeMap = {
        'mentor': 'mentor', 'admin': 'admin',
        'univ_student': 'student', 'high_student': 'student', 'student': 'student'
    };

    container.innerHTML = doubts.map((doubt, index) => {
        const isOwner = AKSANTI_CONFIG.userId == doubt.user_id;
        const isAdmin = AKSANTI_CONFIG.userType === 'admin';
        const badgeClass = badgeMap[doubt.user_type] || '';
        const statusLabel = doubt.status === 'open' ? 'Aberta' : doubt.status === 'resolved' ? 'Resolvida' : 'Fechada';
        const timeAgo = timeElapsed(doubt.created_at);
        const picRaw = String(doubt.profile_pic || '').trim(); // Conversão defensiva para string — protege contra valores nulos da base de dados.
        const pic = picRaw && picRaw !== 'default_profile.png' // Verificamos se existe uma foto real personalizada definida para este utilizador.
            ? (picRaw.startsWith('http') ? picRaw // URL externo (ex: Google, Facebook) — usamos directamente.
                : picRaw.startsWith('carregamentos/') ? AKSANTI_CONFIG.baseUrl + picRaw // Caminho relativo completo já guardado na BD — adicionamos apenas o baseUrl.
                : AKSANTI_CONFIG.baseUrl + 'carregamentos/profiles/' + picRaw) // Só o nome do ficheiro — construímos o caminho completo para a pasta de perfis.
            : AKSANTI_CONFIG.baseUrl + 'recursos/images/default_profile.png'; // Fallback para o avatar padrão do sistema.

        return `
        <div class="dq-card" style="animation-delay: ${index * 0.05}s;" onclick="openDoubtDetail(${doubt.doubt_id})">
            <div class="dq-card-top">
                <div class="dq-card-author">
                    <img src="${pic}" alt="${doubt.full_name}" class="dq-card-avatar">
                    <div class="dq-card-author-info">
                        <div class="dq-card-name">
                            ${doubt.full_name}
                            <span class="dq-badge ${badgeClass}">${doubt.user_type_label}</span>
                        </div>
                        <div class="dq-card-meta">Publicado ${timeAgo}${doubt.city ? ' · ' + doubt.city : ''}</div>
                    </div>
                </div>
                <div class="dq-tags" style="pointer-events: auto;">
                    ${doubt.category ? `<span class="dq-tag">${doubt.category}</span>` : ''}
                    <span class="dq-tag ${doubt.status}">${statusLabel}</span>
                    ${(isOwner || isAdmin) ? `
                    <button onclick="event.stopPropagation(); deleteDoubt(${doubt.doubt_id})"
                        style="background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); width: 30px; height: 30px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.25s; flex-shrink: 0;"
                        onmouseover="this.style.background='rgba(239,68,68,0.25)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'"
                        title="Eliminar">
                        <i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i>
                    </button>` : ''}
                </div>
            </div>

            <h3 class="dq-card-title">${doubt.title}</h3>
            <p class="dq-card-excerpt">${doubt.description}</p>

            <div class="dq-card-footer">
                <div class="dq-card-counters">
                    <span class="dq-counter"><i class="fas fa-comment-dots"></i> ${doubt.comment_count || 0} respostas</span>
                    ${doubt.view_count ? `<span class="dq-counter"><i class="fas fa-eye"></i> ${doubt.view_count}</span>` : ''}
                </div>
                <a class="dq-see-link" onclick="event.stopPropagation(); openDoubtDetail(${doubt.doubt_id})" style="cursor:pointer;">
                    ${doubt.status === 'resolved' ? 'Ver solução' : 'Ver discussão'} <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>`;
    }).join('');
}

function timeElapsed(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60) return 'agora mesmo';
    if (diff < 3600) return `há ${Math.floor(diff/60)} min`;
    if (diff < 86400) return `há ${Math.floor(diff/3600)} h`;
    if (diff < 2592000) return `há ${Math.floor(diff/86400)} dias`;
    return new Date(dateStr).toLocaleDateString('pt-PT');
}

function filterDoubts() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const category = document.getElementById('categoryFilter')?.value || '';
    const status = document.getElementById('statusFilter')?.value || '';
    
    const filtered = allDoubts.filter(doubt => {
        const matchSearch = doubt.title.toLowerCase().includes(searchTerm) || doubt.description.toLowerCase().includes(searchTerm);
        const matchCategory = !category || doubt.category === category;
        const matchStatus = !status || doubt.status === status;
        return matchSearch && matchCategory && matchStatus;
    });
    renderDoubts(filtered);
}

function openDoubtModal() { document.getElementById('doubtModal').style.display = 'flex'; }
function closeDoubtModal() { document.getElementById('doubtModal').style.display = 'none'; document.getElementById('doubtForm').reset(); }

async function submitDoubt(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const res = await fetch('../../interface_programacao/social/post_doubt.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Sucesso!', background: '#1e293b', color: '#fff', timer: 2000 });
            closeDoubtModal();
            loadDoubts();
        } else {
            Swal.fire({ icon: 'error', title: 'Erro', text: data.message, background: '#1e293b', color: '#fff' });
        }
    } catch (err) { console.error(err); }
}

async function openDoubtDetail(id) {
    const modal = document.getElementById('doubtDetailModal');
    const content = document.getElementById('doubtDetailContent');
    modal.style.display = 'flex';
    content.innerHTML = `
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 2rem;gap:1rem;">
            <div style="width:44px;height:44px;border-radius:50%;border:3px solid rgba(247,148,29,0.2);border-top-color:#f7941d;animation:dqSpin 0.75s linear infinite;"></div>
            <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:2px;color:var(--surface-20);margin:0;">A carregar...</p>
        </div>`;

    try {
        // Caminho unificado para evitar bugs de resolução.
        const url = '../../interface_programacao/social/get_doubt_detail.php?doubt_id=' + id;
        
        console.log('Fetching doubt detail from:', url); // Log técnico para rastreamento.
        
        const res = await fetch(url); // Pedido assíncrono à nossa API otimizada.
        if (!res.ok) throw new Error('Caminho não encontrado (404/500)'); // Gestão de erros de ligação ao servidor.
        
        const data = await res.json(); // Processamento da resposta JSON do PHP.
        if (data.success) {
            renderDoubtDetail(data.doubt, data.comments);
        } else {
            content.innerHTML = `<div style="text-align:center;padding:4rem;color:var(--surface-20);">
                <i class="fas fa-exclamation-triangle" style="font-size:2.5rem;display:block;margin-bottom:1rem;color:rgba(239,68,68,0.4);"></i>
                <p style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin:0;">${data.message || 'Erro ao carregar a dúvida'}</p>
            </div>`;
        }
    } catch (err) {
        // MODO DE DIAGNÓSTICO: Exibimos o erro real no modal para identificar a causa exacta da falha.
        content.innerHTML = `<div style="text-align:center;padding:3rem 2rem;color:var(--surface-20);">
            <i class="fas fa-bug" style="font-size:2rem;display:block;margin-bottom:1rem;color:rgba(239,68,68,0.6);"></i>
            <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin:0 0 1rem;">ERRO DETECTADO:</p>
            <p style="font-size:0.8rem;color:#f87171;background:rgba(239,68,68,0.1);padding:1rem;border-radius:8px;word-break:break-all;text-align:left;">${err.message || String(err)}</p>
            <p style="font-size:0.65rem;color:var(--surface-15);margin-top:0.5rem;">URL: ../../interface_programacao/social/get_doubt_detail.php?doubt_id=${id}</p>
            <button onclick="openDoubtDetail(${id})" style="margin-top:1.5rem;background:rgba(247,148,29,0.1);border:1px solid rgba(247,148,29,0.2);color:#f7941d;padding:0.6rem 1.5rem;border-radius:10px;cursor:pointer;font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;">Tentar novamente</button>
        </div>`;
    }
}

function closeDoubtDetailModal() { document.getElementById('doubtDetailModal').style.display = 'none'; }

function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.querySelector('img').src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    } else preview.style.display = 'none';
}

// Função que transforma a lista plana de comentários numa estrutura hierárquica (pai → filhos).
// É necessária porque a tabela doubt_comments usa o campo 'parent_id' para repostas encadeadas.
function buildCommentTree(comments) {
    const map = {}; // Mapa indexado por comment_id para acesso rápido O(1) a cada nó.
    const roots = []; // Lista de comentários raiz (sem parent_id — nível topo da discussão).

    // Primeiro passo: indexamos todos os comentários pelo seu ID único.
    comments.forEach(c => {
        map[c.comment_id] = { ...c, children: [] }; // Cada comentário recebe um array vazio de filhos.
    });

    // Segundo passo: associamos cada comentário ao seu pai ou à lista de raízes.
    comments.forEach(c => {
        if (c.parent_id && map[c.parent_id]) { // Se o comentário tem um pai válido na discussão.
            map[c.parent_id].children.push(map[c.comment_id]); // Adicionamos o comentário como filho do seu pai.
        } else { // Se não tem pai (ou o pai não existe), é um comentário de nível raiz.
            roots.push(map[c.comment_id]); // Adicionamos à lista principal de comentários visíveis.
        }
    });

    return roots; // Devolvemos a árvore completa pronta para renderização recursiva.
}

function renderDoubtDetail(doubt, comments) {
    const isOwner = AKSANTI_CONFIG.userId == doubt.user_id;
    const canConvert = isOwner && doubt.status === 'open' && !doubt.is_converted_to_request;
    
    const statusMap = {
        'open':     { label: 'Aberta',    color: '#f59e0b', bg: 'rgba(245,158,11,0.1)',   border: 'rgba(245,158,11,0.25)',   icon: 'fa-circle-notch' },
        'resolved': { label: 'Resolvida', color: '#10b981', bg: 'rgba(16,185,129,0.1)',   border: 'rgba(16,185,129,0.25)',   icon: 'fa-check-circle' },
        'closed':   { label: 'Fechada',   color: '#64748b', bg: 'rgba(100,116,139,0.1)',  border: 'rgba(100,116,139,0.25)',  icon: 'fa-lock' },
    };
    const st = statusMap[doubt.status] || statusMap['open'];
    const commentTree = buildCommentTree(comments);

    const picRaw = String(doubt.profile_pic || '').trim(); // Conversão defensiva para string — protege contra TypeError em valores nulos.
    const pic = picRaw && picRaw !== 'default_profile.png' // Verificamos se o utilizador tem uma foto real definida no seu perfil.
        ? (picRaw.startsWith('http') ? picRaw // URL externo — usamos directamente sem modificação.
            : picRaw.startsWith('carregamentos/') ? AKSANTI_CONFIG.baseUrl + picRaw // Caminho já completo na BD — adicionamos apenas o baseUrl da configuração.
            : AKSANTI_CONFIG.baseUrl + 'carregamentos/profiles/' + picRaw) // Apenas nome do ficheiro — construímos o URL completo até à pasta de perfis.
        : AKSANTI_CONFIG.baseUrl + 'recursos/images/default_profile.png'; // Imagem padrão como fallback visual garantido.

    document.getElementById('doubtDetailContent').innerHTML = `
        <!-- HEADER -->
        <div class="dq-detail-header">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem;">
                <h2 class="dq-detail-title">${doubt.title}</h2>
                <span style="flex-shrink: 0; font-size: 0.6rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 5px 12px; border-radius: 10px; background: ${st.bg}; color: ${st.color}; border: 1px solid ${st.border}; display: flex; align-items: center; gap: 6px; margin-top: 4px;">
                    <i class="fas ${st.icon}"></i> ${st.label}
                </span>
            </div>
            <div class="dq-detail-author">
                <img src="${pic}" alt="${doubt.full_name}" class="dq-detail-avatar">
                <div>
                    <div class="dq-detail-name">${doubt.full_name}
                        <span style="font-size: 0.55rem; font-weight: 800; color: var(--surface-30); text-transform: uppercase; letter-spacing: 1px; margin-left: 8px;">${doubt.user_type_label || ''}</span>
                    </div>
                    <div class="dq-detail-meta">${timeElapsed(doubt.created_at)} ${doubt.category ? '· ' + doubt.category : ''}</div>
                </div>
            </div>
        </div>

        <!-- BODY -->
        <div class="dq-detail-body">${doubt.description}</div>
        ${doubt.image_path ? `<div style="margin-bottom: 2rem;"><img src="${AKSANTI_CONFIG.baseUrl + doubt.image_path}" style="max-width: 100%; border-radius: 16px; cursor: zoom-in;" onclick="window.open(this.src,'_blank')"></div>` : ''}

        <!-- ACTIONS -->
        ${(isOwner || canConvert) ? `
        <div class="dq-action-btns">
            ${isOwner && doubt.status === 'open' ? `
            <button onclick="resolveDoubt(${doubt.doubt_id})" class="dq-action-btn-sm" style="background: rgba(16,185,129,0.08); color: #10b981; border-color: rgba(16,185,129,0.2);">
                <i class="fas fa-check-double"></i> Marcar como Resolvida
            </button>` : ''}
            ${canConvert ? `
            <button onclick="convertToRequest(${doubt.doubt_id})" class="dq-action-btn-sm" style="background: rgba(247,148,29,0.08); color: #f7941d; border-color: rgba(247,148,29,0.2);">
                <i class="fas fa-handshake"></i> Converter em Pedido de Mentoria
            </button>` : ''}
        </div>` : ''}

        <!-- COMMENTS -->
        <div>
            <div class="dq-comments-title"><i class="fas fa-comments" style="margin-right:8px;"></i>${comments.length} ${comments.length === 1 ? 'Resposta' : 'Respostas'}</div>
            <div id="comments-list">
                ${comments.length === 0
                    ? '<div style="text-align:center; padding: 2.5rem; color: var(--surface-15); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Ainda sem respostas. Sê o primeiro!</div>'
                    : renderRecursiveComments(commentTree, doubt.doubt_id, isOwner)
                }
            </div>
        </div>

        <!-- REPLY FORM -->
        ${doubt.status === 'open' ? `
        <div class="dq-reply-form" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--surface-5);">
            <div style="font-size: 0.65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color: var(--surface-25); margin-bottom: 1rem;">A tua resposta</div>
            <form id="commentForm" onsubmit="submitComment(event, ${doubt.doubt_id})">
                <input type="hidden" name="parent_id" id="replyParentId" value="">
                <textarea name="content" id="commentContent" required rows="4" placeholder="Partilha o teu conhecimento ou experiência..."></textarea>
                <button type="submit" class="dq-reply-submit"><i class="fas fa-paper-plane" style="margin-right:8px;"></i>Responder</button>
                <div style="clear:both;"></div>
            </form>
        </div>` : `
        <div style="text-align:center; margin-top:2rem; padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 14px; font-size: 0.75rem; color: var(--surface-20); font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px;">
            <i class="fas fa-lock" style="margin-right: 8px;"></i> Esta dúvida está fechada
        </div>`}
    `;
}

function renderRecursiveComments(comments, doubtId, isDoubtOwner, level = 0) {
    return comments.map(c => {
        const cPicRaw = String(c.profile_pic || '').trim(); // Conversão defensiva para string — protege contra TypeError em valores nulos dos comentários.
        const cPic = cPicRaw && cPicRaw !== 'default_profile.png' // Verificamos se o comentador tem uma foto real associada ao seu perfil.
            ? (cPicRaw.startsWith('http') ? cPicRaw // URL externo de terceiros — usamos directamente.
                : cPicRaw.startsWith('carregamentos/') ? AKSANTI_CONFIG.baseUrl + cPicRaw // Caminho completo já na BD — adicionamos apenas o baseUrl.
                : AKSANTI_CONFIG.baseUrl + 'carregamentos/profiles/' + cPicRaw) // Apenas nome do ficheiro — construímos o URL completo para a pasta de perfis.
            : AKSANTI_CONFIG.baseUrl + 'recursos/images/default_profile.png'; // Avatar padrão do sistema como garantia de integridade visual.
        return `
        <div class="dq-comment-item ${c.is_helpful ? 'dq-helpful-comment' : ''}" style="${level > 0 ? 'margin-left: 2.5rem; border-left: 2px solid rgba(247,148,29,0.15); padding-left: 1.25rem;' : ''}">
            <img src="${cPic}" alt="${c.full_name}" class="dq-comment-avatar">
            <div class="dq-comment-bubble">
                <div>
                    <span class="dq-comment-name">${c.full_name}</span>
                    <span class="dq-comment-date">${timeElapsed(c.created_at)}</span>
                    ${c.is_helpful ? '<span class="dq-comment-solution"><i class="fas fa-check-circle"></i> Solução</span>' : ''}
                </div>
                <p class="dq-comment-text">${c.content}</p>
                <div class="dq-comment-actions">
                    <button onclick="replyToComment(${c.comment_id}, '${c.full_name.replace(/'/g, "&apos;")}')" class="dq-comment-btn">
                        <i class="fas fa-reply"></i> Responder
                    </button>
                    ${isDoubtOwner ? `
                        <button id="vote-btn-${c.comment_id}" onclick="voteComment(${c.comment_id})" class="dq-comment-btn">
                            <i class="fas fa-thumbs-up"></i> <span id="vote-count-${c.comment_id}">${c.helpful_count || 0}</span> útil
                        </button>
                        ${!c.is_helpful ? `
                        <button onclick="markAsHelpful(${doubtId}, ${c.comment_id})" class="dq-comment-btn helpful">
                            <i class="fas fa-check"></i> Solução
                        </button>` : '<span class="dq-comment-solution"><i class="fas fa-check-circle"></i> Solução</span>'}
                    ` : ''}
                </div>
                ${c.children?.length ? renderRecursiveComments(c.children, doubtId, isDoubtOwner, level + 1) : ''}
            </div>
        </div>`;
    }).join('');
}

function replyToComment(id, name) {
    const p = document.getElementById('replyParentId'); if (!p) return;
    p.value = id;
    const txt = document.getElementById('commentContent');
    txt.placeholder = `Respondendo a ${name}...`;
    txt.focus();
}

async function submitComment(e, id) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const txt = document.getElementById('commentContent');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i>A enviar...';

    const formData = new FormData(e.target);
    formData.append('doubt_id', id);
    try {
        const res = await fetch('../../interface_programacao/social/post_doubt_comment.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            e.target.reset();
            document.getElementById('replyParentId').value = '';
            if (txt) txt.placeholder = 'Partilha o teu conhecimento ou experiência...';
            // Feedback visual rápido
            btn.innerHTML = '<i class="fas fa-check" style="margin-right:8px;"></i>Enviado!';
            btn.style.background = '#10b981';
            setTimeout(() => {
                btn.innerHTML = original;
                btn.style.background = '';
                btn.disabled = false;
                openDoubtDetail(id); // recarrega comentários
            }, 900);
        } else {
            btn.innerHTML = original;
            btn.disabled = false;
            alert(data.message || 'Erro ao enviar resposta.');
        }
    } catch (err) {
        console.error(err);
        btn.innerHTML = original;
        btn.disabled = false;
    }
}

async function convertToRequest(id) {
    const res = await Swal.fire({ 
        title: 'Converter em Mentoria?', 
        text: 'Isso criará um pedido de mentoria gratuito com base nesta dúvida.',
        icon: 'question', 
        showCancelButton: true, 
        confirmButtonText: 'Sim, converter',
        cancelButtonText: 'Cancelar',
        background: '#1e293b', 
        color: '#fff',
        confirmButtonColor: '#f7941d'
    });
    if (res.isConfirmed) {
        window.location.href = AKSANTI_CONFIG.baseUrl + `paginas/mentoria/free_mentorship_requests.php?from_doubt=${id}`;
    }
}

async function voteComment(commentId) {
    const btn = document.getElementById(`vote-btn-${commentId}`);
    const countEl = document.getElementById(`vote-count-${commentId}`);
    if (btn) { btn.disabled = true; btn.style.opacity = '0.5'; }
    try {
        const fd = new FormData();
        fd.append('comment_id', commentId);
        const res = await fetch('../../interface_programacao/social/vote_doubt_comment.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success && countEl) {
            countEl.textContent = data.new_count;
            if (btn) { btn.style.background = 'rgba(247,148,29,0.25)'; btn.style.color = '#f7941d'; btn.style.borderColor = 'rgba(247,148,29,0.5)'; }
        }
    } catch (err) { console.error(err); }
    finally { if (btn) btn.disabled = false; }
}

async function deleteDoubt(id) {
    const res = await Swal.fire({ title: 'Eliminar?', icon: 'warning', showCancelButton: true, background: '#1e293b', color: '#fff' });
    if (res.isConfirmed) {
        const fd = new FormData(); fd.append('doubt_id', id);
        try {
            const r = await fetch('../../interface_programacao/social/delete_doubt.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) { Swal.fire({ icon: 'success', title: 'Eliminado!', background: '#1e293b', color: '#fff' }); loadDoubts(); }
        } catch (err) { console.error(err); }
    }
}

async function markAsHelpful(doubtId, commentId) {
    try {
        const fd = new FormData();
        fd.append('doubt_id', doubtId);
        fd.append('comment_id', commentId);
        
        const res = await fetch('../../interface_programacao/social/mark_comment_helpful.php', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Excelente!', text: 'Dúvida resolvida com sucesso.', background: '#1e293b', color: '#fff', timer: 2000 });
            openDoubtDetail(doubtId);
            loadDoubts();
        } else {
            Swal.fire({ icon: 'error', title: 'Erro', text: data.message, background: '#1e293b', color: '#fff' });
        }
    } catch (err) { console.error(err); }
}

async function resolveDoubt(id) {
    const res = await Swal.fire({
        title: 'Marcar como Solucionada?',
        text: 'Isso indicará que o problema foi resolvido.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, Resolvido!',
        cancelButtonText: 'Ainda não',
        background: '#1e293b',
        color: '#fff',
        confirmButtonColor: '#10b981'
    });

    if (res.isConfirmed) {
        try {
            const fd = new FormData();
            fd.append('doubt_id', id);
            fd.append('status', 'resolved');
            
            // Re-using a generic update doubt endpoint if exists, or adding one
            const r = await fetch('../../interface_programacao/social/edit_doubt.php', { method: 'POST', body: fd });
            const d = await r.json();
            
            if (d.success) {
                Swal.fire({ icon: 'success', title: 'Parabéns!', text: 'Dúvida marcada como resolvida.', background: '#1e293b', color: '#fff', timer: 2000 });
                openDoubtDetail(id);
                loadDoubts();
            }
        } catch (err) { console.error(err); }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadDoubts();
    document.getElementById('searchInput')?.addEventListener('input', filterDoubts);
    setInterval(() => {
        doubtsSeenMarked = false;
        loadDoubts();
    }, 30000);
});
</script>

