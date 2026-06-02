<script>
console.log('🚀 DOUBTS_SCRIPTS.PHP CARREGADO - VERSION 3.0 - TIMESTAMP:' + new Date().getTime());
console.log('✅ NOVO CÓDIGO COM STEP VALIDATION');

// Flag para rastrear se estamos a processar um comentário
let isSubmittingComment = false;
let currentDoubtId = null; // Guardar o ID da dúvida aberta
let currentTab = 'open'; // Aba ativa: 'open' ou 'resolved'

let allDoubts = [];
let doubtsSeenMarked = false;

async function loadDoubts() {
    try {
        console.log('🔍 INICIANDO loadDoubts...');
        
        console.log('📡 Fetching get_doubts.php...');
        const response = await fetch('../../interface_programacao/social/get_doubts.php');
        console.log('📊 Response status:', response.status);
        
        const data = await response.json();
        console.log('✅ JSON parsed:', data);
        
        if (data.success) {
            console.log('✅ Sucesso! Dúvidas:', data.doubts.length);
            allDoubts = data.doubts;
            renderDoubts(allDoubts);
            updateStatsBar(allDoubts);
            await markDoubtsSeen();
        } else {
            console.error('❌ Erro na resposta:', data.message);
            const openContainer = document.getElementById('doubts-container-open');
            if (openContainer) {
                openContainer.innerHTML = `<div style="text-align: center; padding: 3rem; color: var(--text-secondary);"><i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i><p>${data.message || 'Erro ao carregar dúvidas'}</p></div>`;
            }
        }
    } catch (error) {
        console.error('❌ ERRO CRÍTICO:', error);
        console.error('Stack:', error.stack);
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
        console.error('Erro ao marcar dúvidas como vistas:', error);
        doubtsSeenMarked = false;
    }
}

function updateStatsBar(doubts) {
    const bar = document.getElementById('doubts-stats-bar');
    if (!bar) return;
    const total    = doubts.length;
    const open     = doubts.filter(d => d.status === 'open').length;
    const resolved = doubts.filter(d => d.status === 'answered' || d.status === 'mentorship_requested').length;
    const elTotal    = document.getElementById('stat-total');
    const elOpen     = document.getElementById('stat-open');
    const elResolved = document.getElementById('stat-resolved');
    if (elTotal)    elTotal.textContent    = total;
    if (elOpen)     elOpen.textContent     = open;
    if (elResolved) elResolved.textContent = resolved;
}

function renderDoubts(doubts) {
    // Separar dúvidas por status
    const openDoubts = doubts.filter(d => d.status === 'open');
    const resolvedDoubts = doubts.filter(d => d.status === 'answered' || d.status === 'mentorship_requested' || d.status === 'closed');
    
    renderOpenDoubts(openDoubts);
    renderResolvedDoubts(resolvedDoubts);
}

function renderOpenDoubts(doubts) {
    const container = document.getElementById('doubts-container-open');
    if (!container) return;
    
    if (doubts.length === 0) {
        container.innerHTML = `
        <div class="dq-empty">
            <i class="fas fa-comments"></i>
            <p>Nenhuma dúvida aberta</p>
            <button onclick="openDoubtModal()" class="dq-new-btn" style="margin: 1.5rem auto 0; font-size: 0.65rem;">
                <i class="fas fa-plus"></i> Publicar uma Dúvida
            </button>
        </div>`;
        return;
    }
    
    container.innerHTML = renderDoubtsList(doubts);
}

function renderResolvedDoubts(doubts) {
    const container = document.getElementById('doubts-container-resolved');
    if (!container) return;
    
    if (doubts.length === 0) {
        container.innerHTML = `
        <div class="dq-empty">
            <i class="fas fa-check-circle"></i>
            <p>Nenhuma dúvida resolvida</p>
        </div>`;
        return;
    }
    
    container.innerHTML = renderDoubtsList(doubts);
}

function renderDoubtsList(doubts) {
    const badgeMap = {
        'mentor': 'mentor', 'admin': 'admin',
        'univ_student': 'student', 'high_student': 'student', 'student': 'student'
    };

    return doubts.map((doubt, index) => {
        const isOwner = AKSANTI_CONFIG.userId == doubt.user_id;
        const isAdmin = AKSANTI_CONFIG.userType === 'admin';
        const badgeClass = badgeMap[doubt.user_type] || '';
        const statusLabel = doubt.status === 'open' ? 'Aberta' : doubt.status === 'answered' ? 'Respondida' : doubt.status === 'mentorship_requested' ? 'Mentoria' : 'Fechada';
        const timeAgo = timeElapsed(doubt.created_at);
        const picRaw = String(doubt.profile_pic || '').trim();
        const pic = picRaw && picRaw !== 'default_profile.png'
            ? (picRaw.startsWith('http') ? picRaw
                : picRaw.startsWith('carregamentos/') ? AKSANTI_CONFIG.baseUrl + picRaw
                : AKSANTI_CONFIG.baseUrl + 'carregamentos/profiles/' + picRaw)
            : AKSANTI_CONFIG.baseUrl + 'recursos/images/default_profile.png';

        return `
        <div class="dq-card" onclick="openDoubtDetail(${doubt.doubt_id})">
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
                        style="background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); width: 30px; height: 30px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"
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
                    ${doubt.status === 'answered' || doubt.status === 'mentorship_requested' ? 'Ver solução' : 'Ver discussão'} <i class="fas fa-arrow-right"></i>
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

function switchTab(tab) {
    currentTab = tab;
    
    // Atualizar botões
    const tabOpen = document.getElementById('tab-open');
    const tabResolved = document.getElementById('tab-resolved');
    
    if (tab === 'open') {
        tabOpen.style.color = '#f7941d';
        tabOpen.style.borderBottom = '2px solid #f7941d';
        tabResolved.style.color = 'var(--surface-30)';
        tabResolved.style.borderBottom = 'none';
        
        document.getElementById('doubts-container-open').style.display = 'block';
        document.getElementById('doubts-container-resolved').style.display = 'none';
    } else {
        tabResolved.style.color = '#f7941d';
        tabResolved.style.borderBottom = '2px solid #f7941d';
        tabOpen.style.color = 'var(--surface-30)';
        tabOpen.style.borderBottom = 'none';
        
        document.getElementById('doubts-container-open').style.display = 'none';
        document.getElementById('doubts-container-resolved').style.display = 'block';
    }
}

function filterDoubts() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const category = document.getElementById('categoryFilter')?.value || '';
    
    const filtered = allDoubts.filter(doubt => {
        const matchSearch = doubt.title.toLowerCase().includes(searchTerm) || doubt.description.toLowerCase().includes(searchTerm);
        const matchCategory = !category || doubt.category === category;
        return matchSearch && matchCategory;
    });
    renderDoubts(filtered);
}

function openDoubtModal() { document.getElementById('doubtModal').style.display = 'flex'; }
function closeDoubtModal() { document.getElementById('doubtModal').style.display = 'none'; document.getElementById('doubtForm').reset(); }

async function submitDoubt(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    // Adicionar token CSRF (em caso do formulário não ter)
    if (window.CSRF_TOKEN && !formData.get('csrf_token')) {
        formData.append('csrf_token', window.CSRF_TOKEN);
    }
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
    console.log('🔓 openDoubtDetail chamado para id:', id);
    const modal = document.getElementById('doubtDetailModal');
    const content = document.getElementById('doubtDetailContent');
    
    if (!modal) {
        console.error('❌ Modal não encontrado no DOM!');
        return;
    }
    
    console.log('📺 Modal encontrado, mostrando...');
    modal.style.display = 'flex';
    
    // Mostrar loader com espaçamento fixo para evitar layout shift
    content.innerHTML = `
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 2rem;gap:1rem;min-height:400px;">
            <div style="width:44px;height:44px;border-radius:50%;border:3px solid rgba(247,148,29,0.2);border-top-color:#f7941d;animation:dqSpin 0.75s linear infinite;"></div>
            <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:2px;color:var(--surface-20);margin:0;">A carregar detalhes...</p>
        </div>`;

    try {
        // Caminho unificado para evitar bugs de resolução.
        const url = '../../interface_programacao/social/get_doubt_detail.php?doubt_id=' + id;
        
        console.log('🌐 Carregando: ' + url);
        
        const res = await fetch(url);
        console.log('📡 Resposta recebida:', res.status);
        
        if (!res.ok) throw new Error('Caminho não encontrado (404/500)');
        
        const data = await res.json();
        console.log('✅ Dados carregados:', data);
        
        if (data.success) {
            console.log('🎯 Renderizando detalhes...');
            renderDoubtDetail(data.doubt, data.comments);
            console.log('✅ Detalhes renderizados com sucesso');
        } else {
            console.error('❌ Resposta não bem-sucedida:', data.message);
            content.innerHTML = `<div style="text-align:center;padding:4rem;color:var(--surface-20);">
                <i class="fas fa-exclamation-triangle" style="font-size:2.5rem;display:block;margin-bottom:1rem;color:rgba(239,68,68,0.4);"></i>
                <p style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin:0;">${data.message || 'Erro ao carregar a dúvida'}</p>
            </div>`;
        }
    } catch (err) {
        console.error('💥 ERRO em openDoubtDetail:', err.message);
        console.error('Stack:', err.stack);
        
        content.innerHTML = `<div style="text-align:center;padding:3rem 2rem;color:var(--surface-20);">
            <i class="fas fa-bug" style="font-size:2rem;display:block;margin-bottom:1rem;color:rgba(239,68,68,0.6);"></i>
            <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin:0 0 1rem;">ERRO DETECTADO:</p>
            <p style="font-size:0.8rem;color:#f87171;background:rgba(239,68,68,0.1);padding:1rem;border-radius:8px;word-break:break-all;text-align:left;">${err.message || String(err)}</p>
            <p style="font-size:0.65rem;color:var(--surface-15);margin-top:0.5rem;">URL: ../../interface_programacao/social/get_doubt_detail.php?doubt_id=${id}</p>
            <button onclick="openDoubtDetail(${id})" style="margin-top:1.5rem;background:rgba(247,148,29,0.1);border:1px solid rgba(247,148,29,0.2);color:#f7941d;padding:0.6rem 1.5rem;border-radius:10px;cursor:pointer;font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;">Tentar novamente</button>
        </div>`;
    }
}

function closeDoubtDetailModal() { 
    if (isSubmittingComment) {
        console.warn('⚠️ Tentativa de fechar modal enquanto submete comentário - BLOQUEADO');
        return false;
    }
    console.log('🔓 Fechando modal normalmente');
    document.getElementById('doubtDetailModal').style.display = 'none'; 
}

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
    currentDoubtId = doubt.doubt_id; // Guardar ID da dúvida aberta
    console.log('📌 Dúvida aberta - ID armazenado:', currentDoubtId);
    
    const isOwner = AKSANTI_CONFIG.userId == doubt.user_id;
    const canConvert = isOwner && doubt.status === 'open' && !doubt.is_converted_to_request;
    
    const statusMap = {
        'open':     { label: 'Aberta',    color: '#f59e0b', bg: 'rgba(245,158,11,0.1)',   border: 'rgba(245,158,11,0.25)',   icon: 'fa-circle-notch' },
        'answered': { label: 'Respondida', color: '#10b981', bg: 'rgba(16,185,129,0.1)',   border: 'rgba(16,185,129,0.25)',   icon: 'fa-check-circle' },
        'closed':   { label: 'Fechada',   color: '#64748b', bg: 'rgba(100,116,139,0.1)',  border: 'rgba(100,116,139,0.25)',  icon: 'fa-lock' },
        'mentorship_requested': { label: 'Mentoria', color: '#8b5cf6', bg: 'rgba(139,92,246,0.1)', border: 'rgba(139,92,246,0.25)', icon: 'fa-handshake' },
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
        ${doubt.media_url ? `<div style="margin-bottom: 2rem;"><img src="${AKSANTI_CONFIG.baseUrl + doubt.media_url}" style="max-width: 100%; border-radius: 16px; cursor: zoom-in;" onclick="window.open(this.src,'_blank')"></div>` : ''}

        <!-- ACTIONS -->
        ${(isOwner || canConvert) ? `
        <div class="dq-action-btns">
            ${isOwner && doubt.status === 'open' ? `
            <button onclick="resolveDoubt(${doubt.doubt_id})" class="dq-action-btn-sm" style="background: rgba(16,185,129,0.08); color: #10b981; border-color: rgba(16,185,129,0.2);">
                <i class="fas fa-check-double"></i> Marcar como Respondida
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
            <div style="font-size: 0.65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color: var(--surface-25); margin-bottom: 1rem;">A tua resposta <span style="color: #f7941d;">*</span></div>
            <form id="commentForm">
                <input type="hidden" name="parent_id" id="replyParentId" value="">
                <textarea name="content" id="commentContent" required rows="4" placeholder="Partilha o teu conhecimento ou experiência..." data-tipo="comentario" data-tamanho-maximo="250" data-obrigatorio="true"></textarea>
                <div class="contador-caracteres normal" id="commentContent_contador">0/250 caracteres</div>
                <button type="submit" class="dq-reply-submit"><i class="fas fa-paper-plane" style="margin-right:8px;"></i>Responder</button>
                <div style="clear:both;"></div>
            </form>
        </div>` : `
        <div style="text-align:center; margin-top:2rem; padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 14px; font-size: 0.75rem; color: var(--surface-20); font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px;">
            <i class="fas fa-lock" style="margin-right: 8px;"></i> Esta dúvida está ${doubt.status === 'answered' ? 'respondida' : 'fechada'}
        </div>`}
    `;
    
    // Adicionar listener ao formulário após renderizar
    setTimeout(() => {
        const form = document.getElementById('commentForm');
        if (form) {
            console.log('✅ Formulário encontrado no DOM');
            
            // Remover qualquer listener anterior
            form.removeEventListener('submit', submitCommentHandler);
            
            // Adicionar novo listener correto
            form.addEventListener('submit', submitCommentHandler);
            console.log('✅ Listener submit adicionado ao formulário');
        } else {
            console.warn('⚠️ Formulário NÃO encontrado no DOM');
        }
    }, 50);
}

// Handler separado para submit - não dependendo de e.target
function submitCommentHandler(e) {
    console.log('📝 SUBMIT HANDLER CHAMADO');
    e.preventDefault();
    
    const form = e.currentTarget;
    const doubtId = currentDoubtId;
    
    console.log('📝 Form OK:', !!form, 'Doubt ID:', doubtId);
    
    if (!form || !doubtId) {
        alert('Erro: Formulário ou dúvida não encontrados');
        return;
    }
    
    submitComment(form, doubtId);
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

async function submitComment(form, doubtId) {
    console.log('🚀 START submitComment');
    
    try {
        // STEP 1: Validar inputs
        if (!form) {
            console.error('❌ Form é null');
            alert('Erro: Formulário inválido');
            return;
        }
        if (!doubtId) {
            console.error('❌ DoubtId é null');
            alert('Erro: Dúvida inválida');
            return;
        }
        console.log('✓ Inputs validados');

        // STEP 2: Obter textarea manualmente
        const textareas = form.getElementsByTagName('textarea');
        if (!textareas || textareas.length === 0) {
            console.error('❌ Nenhum textarea encontrado');
            alert('Erro: Textarea não encontrado');
            return;
        }
        const textarea = textareas[0];
        const content = textarea.value ? textarea.value.trim() : '';
        console.log('✓ Content length:', content.length);

        // STEP 3: Validar conteúdo
        if (!content || content.length === 0) {
            alert('Escreve um comentário!');
            return;
        }
        console.log('✓ Content válido:', content.substring(0, 30));

        // STEP 4: Obter botão manualmente
        const buttons = form.getElementsByTagName('button');
        let btn = null;
        if (buttons && buttons.length > 0) {
            btn = buttons[0];
        }
        console.log('✓ Botão encontrado:', !!btn);

        // STEP 5: Desabilitar botão
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A enviar...';
        }
        console.log('✓ Botão desabilitado');

        // STEP 6: Preparar FormData
        const formData = new FormData();
        formData.append('doubt_id', String(doubtId));
        formData.append('content', content);
        
        // Adicionar CSRF se disponível
        if (window.CSRF_TOKEN) {
            formData.append('csrf_token', window.CSRF_TOKEN);
            console.log('✓ CSRF token adicionado');
        } else {
            console.warn('⚠️ CSRF token não disponível');
        }

        // STEP 7: Fazer fetch
        const url = '../../interface_programacao/social/post_doubt_comment.php';
        console.log('📡 Enviando para:', url);
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        console.log('📡 Response status:', response.status);

        // STEP 8: Parse response
        let result;
        try {
            result = await response.json();
            console.log('✓ JSON parsed:', result);
        } catch (e) {
            console.error('❌ Erro ao fazer parse do JSON:', e);
            alert('Erro: Resposta inválida do servidor');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Responder';
            }
            return;
        }

        // STEP 9: Verificar sucesso
        if (result && result.success === true) {
            console.log('🎉 SUCESSO CONFIRMADO!');
            
            // Limpar formulário
            textarea.value = '';
            console.log('✓ Formulário limpo');
            
            // Mostrar sucesso visual
            if (btn) {
                btn.innerHTML = '<i class="fas fa-check"></i> Enviado!';
                btn.style.background = '#10b981';
                btn.style.color = '#fff';
            }
            console.log('✓ UI atualizada');

            // Aguardar 1 segundo e recarregar
            setTimeout(() => {
                console.log('🔄 Recarregando dúvida...');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Responder';
                    btn.style.background = '';
                    btn.style.color = '';
                }
                openDoubtDetail(doubtId);
            }, 1000);
        } else {
            // Erro na resposta
            const errorMsg = (result && result.message) ? result.message : 'Erro desconhecido';
            console.error('❌ Erro na resposta:', errorMsg);
            alert('Erro: ' + errorMsg);
            
            // Restaurar botão
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Responder';
                btn.style.background = '';
                btn.style.color = '';
            }
        }
    } catch (err) {
        console.error('💥 ERRO CRÍTICO:', err.message);
        console.error('Stack:', err.stack);
        alert('ERRO: ' + err.message);
        
        // Tentar restaurar botão mesmo em erro
        try {
            const buttons = form.getElementsByTagName('button');
            if (buttons && buttons[0]) {
                buttons[0].disabled = false;
                buttons[0].innerHTML = '<i class="fas fa-paper-plane"></i> Responder';
                buttons[0].style.background = '';
                buttons[0].style.color = '';
            }
        } catch (e) {
            console.error('⚠️ Erro ao restaurar botão:', e);
        }
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
        // Adicionar token CSRF
        if (window.CSRF_TOKEN) {
            fd.append('csrf_token', window.CSRF_TOKEN);
        }
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
        const fd = new FormData(); 
        fd.append('doubt_id', id);
        // Adicionar token CSRF
        if (window.CSRF_TOKEN) {
            fd.append('csrf_token', window.CSRF_TOKEN);
        }
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
        // Adicionar token CSRF
        if (window.CSRF_TOKEN) {
            fd.append('csrf_token', window.CSRF_TOKEN);
        }
        
        const res = await fetch('../../interface_programacao/social/mark_comment_helpful.php', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        
        if (data.success) {
            Swal.fire({ 
                icon: 'success', 
                title: 'Excelente!', 
                text: data.message || 'Dúvida resolvida com sucesso.',
                background: '#1e293b', 
                color: '#fff', 
                timer: 2500,
                confirmButtonColor: '#10b981'
            });
            openDoubtDetail(doubtId);
            loadDoubts();
        } else {
            Swal.fire({ icon: 'error', title: 'Erro', text: data.message, background: '#1e293b', color: '#fff' });
        }
    } catch (err) { console.error(err); }
}

async function resolveDoubt(id) {
    const res = await Swal.fire({
        title: 'Marcar como Respondida?',
        text: 'Isso indicará que o problema foi respondido.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, Respondida!',
        cancelButtonText: 'Ainda não',
        background: '#1e293b',
        color: '#fff',
        confirmButtonColor: '#10b981'
    });

    if (res.isConfirmed) {
        try {
            const fd = new FormData();
            fd.append('doubt_id', id);
            fd.append('status', 'answered');
            // Adicionar token CSRF
            if (window.CSRF_TOKEN) {
                fd.append('csrf_token', window.CSRF_TOKEN);
            }
            
            const r = await fetch('../../interface_programacao/social/edit_doubt.php', { method: 'POST', body: fd });
            const d = await r.json();
            
            if (d.success) {
                Swal.fire({ icon: 'success', title: 'Parabéns!', text: 'Dúvida marcada como respondida.', background: '#1e293b', color: '#fff', timer: 2000 });
                openDoubtDetail(id);
                loadDoubts();
            } else {
                Swal.fire({ icon: 'error', title: 'Erro', text: d.message || 'Erro ao atualizar', background: '#1e293b', color: '#fff' });
            }
        } catch (err) { console.error(err); }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // FORÇA NOVA VERSÃO - SOBREESCREVE QUALQUER CACHE
    window.submitComment = async function(form, doubtId) {
        try {
            if (!form || !doubtId) {
                alert('Erro: Form ou doubtId inválido');
                return;
            }
            
            const textareas = form.getElementsByTagName('textarea');
            if (!textareas || textareas.length === 0) {
                alert('Erro: Textarea não encontrado');
                return;
            }
            
            const textarea = textareas[0];
            const content = (textarea.value || '').trim();
            
            if (!content) {
                alert('Escreve um comentário!');
                return;
            }
            
            const buttons = form.getElementsByTagName('button');
            let btn = buttons && buttons.length > 0 ? buttons[0] : null;
            
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A enviar...';
            }
            
            const formData = new FormData();
            formData.append('doubt_id', String(doubtId));
            formData.append('content', content);
            if (window.CSRF_TOKEN) {
                formData.append('csrf_token', window.CSRF_TOKEN);
            }
            
            const response = await fetch('../../interface_programacao/social/post_doubt_comment.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result && result.success) {
                console.log('🎉 SUCESSO!');
                textarea.value = '';
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Enviado!';
                    btn.style.background = '#10b981';
                }
                setTimeout(() => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Responder';
                        btn.style.background = '';
                    }
                    openDoubtDetail(doubtId);
                }, 1000);
            } else {
                alert('Erro: ' + (result?.message || 'Desconhecido'));
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Responder';
                }
            }
        } catch (err) {
            alert('ERRO: ' + err.message);
            const buttons = form.getElementsByTagName('button');
            if (buttons && buttons[0]) {
                buttons[0].disabled = false;
                buttons[0].innerHTML = '<i class="fas fa-paper-plane"></i> Responder';
            }
        }
    };
    
    console.log('✅ NOVA submitComment FORÇADA');
    
    // AUTO-OPEN DOUBT FROM URL PARAMETER
    function autoOpenDoubtFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const doubtId = params.get('doubt_id');
        
        if (doubtId) {
            console.log('🔗 Parâmetro doubt_id detectado na URL:', doubtId);
            // Aguardar um pouco para garantir que o modal está no DOM
            setTimeout(() => {
                openDoubtDetail(parseInt(doubtId, 10));
            }, 300);
        }
    }
    
    loadDoubts();
    autoOpenDoubtFromUrl();
    document.getElementById('searchInput')?.addEventListener('input', filterDoubts);
    setInterval(() => {
        doubtsSeenMarked = false;
        loadDoubts();
    }, 30000);
});
</script>

