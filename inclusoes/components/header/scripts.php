<?php

?>
<script>
    window.BASE_URL = window.BASE_URL || '<?php echo $base_url; ?>';
    var BASE_URL = window.BASE_URL;
    /**
     * 1. FLAGS DE AUTORIDADE (Contexto PHP -> JS)
     * Injetamos o estado de verificação e o tipo de utilizador no JavaScript global.
     * Isto permite que funções front-end tomem decisões de UX sem precisar de novas queries AJAX.
     */
    var IS_VERIFIED       = <?php echo (isset($is_verified) && $is_verified) ? 'true' : 'false'; ?>;
    var KYC_STATUS        = '<?php echo isset($kyc_status) ? $kyc_status : 'unsubmitted'; ?>';
    var HAS_FULL_ACCESS   = <?php echo (isset($has_full_access) && $has_full_access) ? 'true' : 'false'; ?>;
    var MENTORSHIP_STATUS = '<?php echo isset($mentorship_status) ? $mentorship_status : 'unsubmitted'; ?>';
    var IS_ADMIN          = <?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') ? 'true' : 'false'; ?>;
    var CURRENT_USER_ID   = <?php echo $_SESSION['user_id'] ?? 0; ?>;
    window.CSRF_TOKEN = window.CSRF_TOKEN || '<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>';

    (function installCsrfFetchGuardFallback() {
        if (!window.fetch || window.__aksantiCsrfFetchGuardInstalled) return;
        window.__aksantiCsrfFetchGuardInstalled = true;
        const nativeFetch = window.fetch.bind(window);
        window.fetch = function(resource, options) {
            options = options || {};
            const method = String(options.method || (resource && resource.method) || 'GET').toUpperCase();
            if (!['GET', 'HEAD', 'OPTIONS'].includes(method) && window.CSRF_TOKEN) {
                const url = typeof resource === 'string' ? resource : (resource && resource.url) || '';
                const target = new URL(url, window.location.href);
                if (target.origin === window.location.origin) {
                    const headers = new Headers(options.headers || {});
                    if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', window.CSRF_TOKEN);
                    options.headers = headers;
                }
            }
            return nativeFetch(resource, options);
        };
    })();

    /**
     * 2. COMMAND CENTER (ATALHOS RÁPIDOS)
     * Sistema inspirado no Spotlight/Raycast. Permite navegar na plataforma via comandos de texto
     * (ex: /perfil, /novo) ou busca global por voz/teclado.
     */
    var QUICK_ACTIONS = [
        { label: 'Ir para Dashboard',      icon: 'fas fa-th-large',     url: 'index.php',                              cmd: '/home' },
        { label: 'Meus Projectos',         icon: 'fas fa-folder-open',  url: 'paginas/explorar/my_projects.php',       cmd: '/meus' },
        { label: 'Submeter Novo Projecto',    icon: 'fas fa-plus-circle',  action: 'openPostModal',                        cmd: '/novo' },
        { label: 'Dados & Analytics',      icon: 'fas fa-chart-line',   url: 'paginas/explorar/project_analytics.php',  cmd: '/dados' },
        { label: 'Minhas Comissões',       icon: 'fas fa-coins',        url: 'paginas/mentoria/my_commissions.php',    cmd: '/ganhos' },
        { label: 'Ver Meu Perfil',         icon: 'fas fa-user-circle',  url: 'paginas/social/profile.php',             cmd: '/perfil' }
    ];

    function showCommandCenter() {
        const dropdown = document.getElementById('commandCenterDropdown');
        dropdown.classList.add('active');
        renderQuickActions();
    }

    /**
     * RENDERIZAÇÃO DE ATALHOS
     * Filtra a lista QUICK_ACTIONS baseado no que o utilizador digita na busca.
     */
    function renderQuickActions(filter = '') {
        const list = document.getElementById('quickActionsList');
        list.innerHTML = '';
        const filtered = QUICK_ACTIONS.filter(a => 
            a.label.toLowerCase().includes(filter.toLowerCase()) || 
            a.cmd.includes(filter.toLowerCase())
        );

        if(filtered.length === 0) {
            list.innerHTML = '<div class="command-item empty">Sem comandos encontrados...</div>';
            return;
        }

        filtered.forEach(a => {
            const item = document.createElement('div');
            item.className = 'command-item';
            item.innerHTML = `
                <i class="${a.icon}"></i>
                <span>${a.label}</span>
                <span class="command-shortcut">${a.cmd}</span>
            `;
            item.onclick = () => {
                // Roteia para URL ou dispara uma função JS definida no action.
                if(a.url) window.location.href = '<?php echo $base_url; ?>' + a.url;
                else if(a.action && typeof window[a.action] === 'function') window[a.action]();
                closeCommandCenter();
            };
            list.appendChild(item);
        });
    }

    function closeCommandCenter() {
        document.getElementById('commandCenterDropdown').classList.remove('active');
    }

    /**
     * 3. BUSCA GLOBAL INTELIGENTE
     * Implementa um 'Debounce' de 400ms. Se o utilizador digita no feed, filtramos os projectos.
     * Se digita um comando ('/'), abrimos o Command Center.
     */
    let searchTimeout;
    function handleGlobalSearch(e) {
        const query = e.target.value.trim();
        const dropdown = document.getElementById('commandCenterDropdown');

        if(e.key === 'Escape') {
            closeCommandCenter();
            return;
        }

        if(query.startsWith('/')) {
            renderQuickActions(query);
            return;
        }

        if(!query) {
            renderQuickActions();
            return;
        }

        // Se estivermos numa página com Feed (index), aplicamos o filtro AJAX em tempo real.
        if (typeof applyFeedFilters === 'function') {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFeedFilters(1);
            }, 400);
        } else {
            // Em outras páginas, o Enter leva o utilizador para a busca no index.php
            if (e.key === 'Enter') {
                window.location.href = '<?php echo $base_url; ?>index.php?search=' + encodeURIComponent(query);
            }
        }
    }

    // Fechar dropdowns ao clicar em áreas vazias da página.
    document.addEventListener('click', (e) => {
        const searchBox = document.querySelector('.header-search');
        if (searchBox && !searchBox.contains(e.target)) {
            closeCommandCenter();
        }

        // Fechar Dropdowns de Perfil e Notificações se clicar fora
        const profileBox = document.querySelector('.profile-container');
        const notifBox = document.querySelector('.btn-action--notif');
        const profileDd = document.getElementById('profileDropdown');
        const notifDd = document.getElementById('notifContent');

        if (profileBox && !profileBox.contains(e.target)) {
            if(profileDd) profileDd.classList.remove('active');
        }
        if (notifBox && !notifBox.contains(e.target) && notifDd && !notifDd.contains(e.target)) {
            if(notifDd) notifDd.classList.remove('active');
        }
    });

    /**
     * 3b. GESTÃO DE DROPDOWNS (PERFIL & NOTIFICAÇÕES)
     * Controla a abertura e o fecho dos menus de acção rápida da direita.
     */
    function openMobileProfileMenu() {
        if (typeof openPerfilMenuModal === 'function') {
            openPerfilMenuModal();
        }
    }

    function openMobileExploreMenu() {
        if (typeof openExplorarModal === 'function') {
            openExplorarModal();
        }
    }

    function openMobileMentorshipMenu() {
        if (typeof openMentoriaModal === 'function') {
            openMentoriaModal();
            return;
        }
        window.location.href = '<?php echo $base_url; ?>paginas/explorar/explore_mentors.php';
    }

    window.openMentorAppModal = window.openMentorAppModal || function() {
        const modal = document.getElementById('mentorAppModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            return;
        }
        Swal.fire({
            icon: 'error',
            title: 'Candidatura indisponivel',
            text: 'Não foi possível abrir o formulario de candidatura nesta pagina.',
            background: '#111827',
            color: '#fff'
        });
    };

    window.closeMentorAppModal = window.closeMentorAppModal || function() {
        const modal = document.getElementById('mentorAppModal');
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
    };

    function toggleMobileSearch() {
        Swal.fire({
            title: 'Pesquisar na KALIYE',
            input: 'text',
            inputPlaceholder: 'Projectos, Mentores ou Pessoas...',
            showCancelButton: true,
            confirmButtonText: 'Procurar',
            cancelButtonText: 'Fechar',
            background: '#0d1628',
            color: '#fff',
            confirmButtonColor: '#f7941d',
            customClass: {
                popup: 'glass-effect',
                input: 'swal-input-elite'
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                // Redireciona ou acciona a busca global com o valor
                window.location.href = '<?php echo $base_url; ?>index.php?search=' + encodeURIComponent(result.value);
            }
        });
    }

    function toggleProfile(e) {
        if(e) e.stopPropagation();
        const dp  = document.getElementById('profileDropdown');
        const nt  = document.getElementById('notifContent');
        const cnt = document.querySelector('.profile-container');
        if(nt) nt.classList.remove('active');
        if(dp) {
            dp.classList.toggle('active');
            if(cnt) cnt.classList.toggle('active', dp.classList.contains('active'));
        }
    }

    function toggleNotifs(e) {
        if(e) e.stopPropagation();
        const nt = document.getElementById('notifContent');
        const dp = document.getElementById('profileDropdown');
        if(dp) dp.classList.remove('active');
        if(nt) {
            nt.classList.toggle('active');
            if(nt.classList.contains('active')) {
                loadNotifications({ markSeen: true });
            }
        }
    }

    function markAllRead(options = {}) {
        const formData = new FormData();
        formData.append('notification_id', 'all');
        fetch('<?php echo $base_url; ?>interface_programacao/social/mark_notification_read.php', { 
            method: 'POST',
            body: formData,
            cache: 'no-store'
        })
            .then(res => res.json())
            .then(data => {
                if(data.success && options.reload !== false) loadNotifications();
                const badge = document.getElementById('notifBadge');
                if(badge) badge.style.display = 'none';
                lastCounts.notifications = 0;
            })
            .catch(err => console.error('[NOTIF ERROR]', err));
    }

    // Atalho Premium Ctrl + K (Padrão da Indústria) para focar na busca global.
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('globalSearchInput').focus();
        }
    });

    /**
     * 4. MOTOR DE GATING DE IDENTIDADE (KYC ENFORCEMENT)
     * Centraliza a verificação documental. Se o utilizador não tiver verificação total,
     * abre o modal de KYC e retorna false para bloquear a ação original.
     */
    function enforceKYC() {
        if (IS_ADMIN || HAS_FULL_ACCESS) return true;
        
        if (typeof openKYCModal === 'function') {
            openKYCModal();
        } else {
            Swal.fire({
                title: 'Verificação Necessária',
                text: 'Para aceder a esta funcionalidade, precisas de validar a tua identidade.',
                icon: 'info',
                confirmButtonText: 'Verificar Agora',
                confirmButtonColor: '#f7941d',
                background: '#111827',
                color: '#fff'
            }).then(() => {
                window.location.href = '<?php echo $base_url; ?>paginas/social/profile.php?kyc_required=1';
            });
        }
        return false;
    }

    /**
     * 4b. GESTÃO DE ACESSO A MENTORIA
     * Regra de Negócio: Bloqueia o acesso a áreas de mentoria se o perfil não estiver 'approved'.
     */
    function enforceMentor() {
        if (IS_ADMIN) return true;
        
        // Primeiro garantimos que a identidade básica está validada
        if (!enforceKYC()) return false;
        
        if (MENTORSHIP_STATUS !== 'approved') {
            Swal.fire({
                title: 'Acesso Restrito ao Mentor',
                html: '<p style="color: var(--surface-70);">Esta área é exclusiva para Mentores Oficiais KALIYE. Queres juntar-te ao programa?</p>',
                showCancelButton: true,
                confirmButtonText: 'Candidatar-me',
                cancelButtonText: 'Fechar',
                confirmButtonColor: '#f7941d',
                background: '#111827',
                color: '#fff',
                borderRadius: '32px'
            }).then((result) => {
                if (result.isConfirmed) {
                    openMentorAppModal();
                }
            });
            return false;
        }
        return true;
    }

    /**
     * 5. SISTEMA DE NOTIFICAÇÕES (AJAX POLLING)
     * Carrega e renderiza as notificações sociais do utilizador em tempo real. 
     * Inclui ações rápidas como Aceitar/Recusar amizades diretamente na Navbar.
     */
    function escapeNotifHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function(ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
        });
    }

    function loadNotifications(options = {}) {
        fetch('<?php echo $base_url; ?>interface_programacao/social/get_notifications.php', { cache: 'no-store' })
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById('notifList');
                if(!list) return;
                list.innerHTML = '';
                if (data.success && typeof data.unread_count !== 'undefined') {
                    const unread = parseInt(data.unread_count, 10) || 0;
                    updateBadge('notifBadge', unread);
                    lastCounts.notifications = unread;
                }
                
                if (!data.success || !data.notifications || data.notifications.length === 0) {
                    list.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-secondary); font-size: 0.8rem;">Sem novas notificações</div>';
                    return;
                }
                
                data.notifications.forEach(n => {
                    const isUnread = n.is_read == 0;
                    const item = document.createElement('div');
                    item.className = 'notif-item';
                    item.style.background = isUnread ? 'rgba(255, 107, 53, 0.08)' : 'transparent';
                    
                    // Renderizamos botões de ação se for um pedido de conexão pendente.
                    let actionsHtml = '';
                    if (n.has_actions && (n.type === 'connection_request' || n.title.includes('pediu conexão'))) {
                        actionsHtml = `
                            <div style="display: flex; gap: 8px; margin-top: 10px;">
                                <button onclick="event.stopPropagation(); handleGlobalConnection(${n.sender_id}, 'accept', this, ${n.notification_id})" style="padding: 6px 14px; border-radius: 8px; border: none; background: #10b981; color: white; font-size: 0.72rem; font-weight:700; cursor: pointer;">Aceitar</button>
                                <button onclick="event.stopPropagation(); handleGlobalConnection(${n.sender_id}, 'reject', this, ${n.notification_id})" style="padding: 6px 14px; border-radius: 8px; border: none; background: #ef4444; color: white; font-size: 0.72rem; font-weight:700; cursor: pointer;">Recusar</button>
                            </div>
                        `;
                    }

                    // Lógica blindada e dinâmica do ícone de perfil (Evita erros de renderização de fotografia)
                    let pfp = '<?php echo $base_url; ?>recursos/images/default_profile.png';
                    if (n.type === 'system' || n.type === 'admin') {
                        pfp = '<?php echo $base_url; ?>recursos/images/marca/YALIYE.png';
                    } else if (n.sender_pic && n.sender_pic !== 'default_profile.png') {
                        let rawPath = n.sender_pic;
                        if (rawPath.startsWith('http')) {
                            pfp = rawPath;
                        } else if (rawPath.startsWith('carregamentos/')) {
                            pfp = '<?php echo $base_url; ?>' + rawPath;
                        } else {
                            pfp = '<?php echo $base_url; ?>carregamentos/profiles/' + rawPath;
                        }
                    }

                    item.innerHTML = `
                        <div class="notif-pfp-container">
                            <img src="${pfp}" style="width: 100%; height: 100%; object-fit: cover; padding: ${ (n.type === 'system' || n.type === 'admin') ? '8px' : '0' };" onerror="this.src='<?php echo $base_url; ?>recursos/images/default_profile.png'">
                        </div>
                        <div style="flex-grow: 1; min-width: 0;">
                            <div class="notif-title">${n.title || 'Notificação'}</div>
                            <div class="notif-body">${n.content || n.message || ''}</div>
                            ${actionsHtml}
                        </div>
                    `;
                    
                    // Lógica inteligente de Redireccionamento e Conversão Dinâmica
                    item.onclick = (e) => {
                        if(e.target.tagName === 'BUTTON') return; // Bloqueia clique subjacente se foi clicado Aceitar/Recusar

                        const formData = new FormData();
                        formData.append('notification_id', n.notification_id);
                        fetch('<?php echo $base_url; ?>interface_programacao/social/mark_notification_read.php', { method: 'POST', body: formData, cache: 'no-store' })
                            .then(() => {
                                item.style.background = 'transparent';
                                if (typeof window.fetchRealtimeCounts === 'function') window.fetchRealtimeCounts(true);
                            });
                        
                        let destUrl = n.link;
                        
                        // PRIORIDADE: Se for perfil, tentamos abrir modal primeiro (Melhor UX)
                        if (n.type === 'connection_request' || n.type === 'connection_accepted' || (destUrl && destUrl.includes('profile.php'))) {
                            if (typeof window.openUserCard === 'function') {
                                // Tenta obter ID do sender ou extrair da URL
                                let targetId = n.sender_id;
                                if (!targetId && destUrl && destUrl.includes('id=')) {
                                    targetId = destUrl.split('id=')[1].split('&')[0];
                                }
                                
                                if (targetId) {
                                    window.openUserCard(targetId);
                                    return;
                                }
                            }
                        }

                        // PRIORIDADE: Notificações de projecto → abrir modal directamente
                        // Apanha project_id=, comment_project_id= e o formato legado id=
                        if (destUrl && destUrl.includes('index.php?') && (destUrl.includes('project_id=') || destUrl.includes('comment_project_id=') || destUrl.includes('id='))) {
                            if (typeof window.openProjectDetails === 'function') {
                                let pId = null;
                                const match = destUrl.match(/(?:project_id|comment_project_id|id)=(\d+)/);
                                if (match) pId = parseInt(match[1], 10);
                                if (pId) {
                                    window.openProjectDetails(pId);
                                    return;
                                }
                            }
                        }

                        if (destUrl && destUrl.trim() !== '') {
                            window.location.href = destUrl.startsWith('http') ? destUrl : '<?php echo $base_url; ?>' + destUrl;
                        }
                    };
                    list.appendChild(item);
                });

                if (options.markSeen && (parseInt(data.unread_count, 10) || 0) > 0) {
                    setTimeout(() => markAllRead({ reload: false }), 350);
                }
            });
    }

    /**
     * Motor Global de Conexões (Aksanti Network)
     * Processa aceitação/recusa de rede de qualquer lugar (Navbar ou Feed)
     */
    function handleGlobalConnection(targetId, action, btn, notificationId) {
        if (!targetId || !action) return;
        
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = '...';

        const formData = new FormData();
        formData.append('target_id', targetId);
        formData.append('action', action);
        if(notificationId) formData.append('notification_id', notificationId);

        fetch('<?php echo $base_url; ?>interface_programacao/user/connection_action.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const parent = btn.parentElement;
                if (parent) {
                    parent.innerHTML = `<span style="color: ${action === 'accept' ? '#10b981' : '#ef4444'}; font-size: 0.72rem; font-weight:700;">${action === 'accept' ? 'Conectado!' : 'Recusado'}</span>`;
                }
                // Recarrega contadores se necessário
                if (typeof startRealtimePolling === 'function') setTimeout(loadNotifications, 1000);
            } else {
                Swal.fire({ title: 'Erro', text: data.error || 'Falha na conexão', icon: 'error' });
                btn.disabled = false;
                btn.innerText = originalText;
            }
        }).catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerText = originalText;
        });
    }

    /**
     * 6. REAL-TIME POLLING (CORAÇÃO PULSANTE COM ÁUDIO)
     * A cada 5 segundos, o sistema verifica novos dados.
     * Implementa feedback sonoro e visual se o contador aumentar.
     */
    var lastCounts = { 
        messages: <?php echo isset($m_count) ? (int)$m_count : 0; ?>, 
        notifications: <?php echo isset($n_count) ? (int)$n_count : 0; ?>, 
        doubts: <?php echo isset($open_doubts) ? (int)$open_doubts : 0; ?> 
    };
    var realtimeReady = false;
    var audioUnlocked = false;
    var audioCtx = null;
    var notifAudio = { play: function() { return Promise.resolve(); } };

    function unlockNotifAudio() {
        if (audioUnlocked) return;
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) return;
            audioCtx = audioCtx || new AudioContextClass();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            audioUnlocked = true;
        } catch (e) {
            audioUnlocked = false;
        }
    }

    ['click', 'keydown', 'touchstart'].forEach(function(evt) {
        document.addEventListener(evt, unlockNotifAudio, { once: true, passive: true });
    });

    function startRealtimePolling() {
        if (window.__aksantiRealtimePollingStarted) return;
        window.__aksantiRealtimePollingStarted = true;
        window.fetchRealtimeCounts = (silent = false) => {
            fetch('<?php echo $base_url; ?>interface_programacao/get_unread_counts.php', { cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    // Verificar se houve aumento para tocar som
                    if (realtimeReady && !silent && (data.notifications > lastCounts.notifications || data.messages > lastCounts.messages)) {
                        playNotifSound();
                    }

                    // Atualizar referências
                    lastCounts = { 
                        messages: data.messages, 
                        notifications: data.notifications, 
                        doubts: data.doubts 
                    };

                    // Atualização das Badges
                    updateBadge('msgBadge', data.messages);
                    updateBadge('notifBadge', data.notifications);
                    updateBadge('doubtBadge', data.doubts);
                    const notifPanel = document.getElementById('notifContent');
                    if (notifPanel && notifPanel.classList.contains('active')) {
                        loadNotifications({ markSeen: true });
                    }
                    realtimeReady = true;
                }
            }).catch(e => console.error('[POLLING ERROR]', e));
        };
        
        const initialDelay = 2500;
        const pollingInterval = 15000;
        setTimeout(() => window.fetchRealtimeCounts(true), initialDelay);
        setInterval(() => window.fetchRealtimeCounts(false), pollingInterval);
    }
    
    function playNotifSound() {
        try {
            unlockNotifAudio();
            if (!audioCtx || audioCtx.state === 'suspended') {
                console.warn("Audio bloqueado pelo navegador. Interaja com a pagina primeiro.");
            } else {
                const now = audioCtx.currentTime;
                const gain = audioCtx.createGain();
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.22, now + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.45);
                gain.connect(audioCtx.destination);

                [880, 1174].forEach(function(freq, idx) {
                    const osc = audioCtx.createOscillator();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, now + idx * 0.12);
                    osc.connect(gain);
                    osc.start(now + idx * 0.12);
                    osc.stop(now + idx * 0.12 + 0.18);
                });
            }
        } catch (e) {
            console.warn("Falha ao tocar som de notificacao.", e);
        }
        notifAudio.play().catch(e => console.warn("Áudio bloqueado pelo navegador. Interaja com a página primeiro."));
        const bell = document.querySelector('.btn-action--notif i');
        if (bell) {
            bell.style.animation = 'bellShake 0.5s ease';
            setTimeout(() => bell.style.animation = '', 500);
        }
    }

    function updateBadge(id, count) {
        const badge = document.getElementById(id);
        if(!badge) return;
        if(count > 0) {
            const currentVal = parseInt(badge.innerText) || 0;
            badge.innerText = count;
            badge.style.display = 'flex';
            
            // Se o valor mudou para cima, damos um "pop" visual
            if (count > currentVal) {
                badge.style.animation = 'none';
                badge.offsetHeight; // trigger reflow
                badge.style.animation = 'badgePop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            }
        } else {
            badge.style.display = 'none';
        }
    }

    // Animação de vibração do sino
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes bellShake {
            0%, 100% { transform: rotate(0); }
            20% { transform: rotate(15deg); }
            40% { transform: rotate(-15deg); }
            60% { transform: rotate(10deg); }
            80% { transform: rotate(-10deg); }
        }
    `;
    document.head.appendChild(style);

    document.addEventListener('DOMContentLoaded', function() { 
        const bootRealtime = () => startRealtimePolling();
        if (document.readyState === 'complete') {
            bootRealtime();
        } else {
            window.addEventListener('load', bootRealtime, { once: true });
        }
        
        const CLOSE_DELAY = 150; 
        
        // --- MOTOR DE DROPDOWNS PRINCIPAIS ---
        document.querySelectorAll('.nav-dropdown').forEach(function(dropdown) {
            let closeTimer = null; // Crio uma variável de controlo temporal para o fecho suave.
            
            // Suporte a Rato (Desktop Experience)
            dropdown.addEventListener('mouseenter', () => { // Escuto a entrada do ponteiro no elemento.
                clearTimeout(closeTimer); // Cancelo qualquer ordem de fecho pendente.
                dropdown.classList.add('dd-open'); // Injecto a classe de visibilidade no menu.
            });
            dropdown.addEventListener('mouseleave', () => { // Escuto a saída do ponteiro do elemento.
                closeTimer = setTimeout(() => dropdown.classList.remove('dd-open'), CLOSE_DELAY); // Programo o fecho após o atraso definido.
            });
            
            // Suporte a Clique (Mobile/Click Experience)
            const linkTrigger = dropdown.querySelector('.nav-link'); // Identifico o gatilho principal da categoria.
            if(linkTrigger) { // Se o gatilho existir, activo a interatividade táctil.
                linkTrigger.addEventListener('click', (e) => { // Escuto o evento de clique directo.
                    e.preventDefault(); // Impeço o comportamento padrão para gerir a visibilidade via JS.
                    e.stopPropagation(); // Travo a propagação do evento para evitar o fecho imediato pelo listener global.
                    const isOpen = dropdown.classList.contains('dd-open'); // Verifico o estado actual da visibilidade.
                    document.querySelectorAll('.nav-dropdown').forEach(d => d.classList.remove('dd-open')); // Fecho outros menus para manter o foco.
                    if(!isOpen) dropdown.classList.add('dd-open'); // Se estava fechado, abro agora.
                });
            }
        });

        // --- MOTOR DE SUB-DROPDOWNS (Mega Menu Elite) ---
        // Resolve o problema de os botões internos não mostrarem as sub-opções.
        document.querySelectorAll('.nav-sub-dropdown').forEach(function(sub) { 
            sub.addEventListener('mouseenter', () => { 
                sub.classList.add('sub-open'); 
            });
            sub.addEventListener('mouseleave', () => { 
                sub.classList.remove('sub-open'); 
            });
            
            sub.addEventListener('click', (e) => { 
                if (e.target.closest('.sub-dropdown-panel')) { return; } // Valido se o clique ocorreu no painel de links expansivo para permitir a navegação.
                
                // Motor Inteligente: Valida se o utilizador clicou diretamente num link frontal (ex: Atalho diretos que criámos s/ Dropdown)
                const clickTarget = e.target.closest('a');
                if (clickTarget && clickTarget.getAttribute('href') !== 'javascript:void(0)') {
                    return; // Abandona a inibição JS restritiva e permite ao navegador processar a hiperligação (href) normalmente.
                }

                e.preventDefault(); // Bloqueio o salto de página apenas para os elementos estéreis ("void(0)") encarregados expandir menus HTML.
                e.stopPropagation(); // Isolei o evento para garantir que a visibilidade do menu pai não seja subvertida por reações indesejadas laterais.
                sub.classList.toggle('sub-open'); // Alterno a classe de visibilidade conforme o estado actual investigando propriedades do flex contentor.
            });
        });
    });

    /**
     * 8. PWA SERVICE WORKER
     * Registo do motor de Offline e Cache para que a aplicação possa ser instalada 
     * nos telemóveis dos utilizadores como uma aplicação nativa (A2HS).
     */
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            setTimeout(() => {
                navigator.serviceWorker.register('<?php echo $base_url; ?>sw.js')
                    .catch(() => {});
            }, 5000);
        });
    }
    /** // Comentário da nova funcionalidade
     * 9. GESTÃO DE ACESSO À CARTEIRA (Versão 1.0) // Título técnico da gestão de carteira
     * Bloqueia o acesso de Estudantes e Mentores à Carteira nesta versão. // Explicação da regra de negócio aplicada
     */ // Fim do comentário
    function showWalletDevMessage(e) { // Define a função de bloqueio visual
        if (e) e.preventDefault(); // Impede o redirecionamento padrão do link HTML
        Swal.fire({ // Dispara o motor Elite de alertas (SweetAlert2)
            title: 'Funcionalidade em Breve', // Título premium do alerta informativo
            html: '<p style="color: var(--surface-70);">A Carteira Digital estará disponível para o seu perfil na próxima versão da plataforma.</p>', // Mensagem explicativa amigável
            icon: 'info', // Ícone de informação para o utilizador
            confirmButtonText: 'Entendido', // Texto de confirmação para fechar o modal
            confirmButtonColor: '#f7941d', // Cor laranja da marca para o botão
            background: '#111827', // Fundo escuro premium condizente com o sistema
            color: '#fff', // Cor branca para os textos
            borderRadius: '32px' // Cantos arredondados no estilo Elite
        }); // Fim da configuração do alerta Elite
    }
    /**
     * 10. TRADUÇÃO INSTANTÂNEA (FEED INTERNACIONAL)
     * Utiliza o Google Translate para converter o conteúdo do card em tempo real.
     */
    function translateCard(btn, projectId) {
        const titleEl = document.querySelector(`.translatable-title-${projectId}`);
        // Procuramos a descrição dentro do card (ela geralmente não tem classe específica no post_card, vamos inferir)
        const card = btn.closest('.project-card-premium');
        const descEl = card ? card.querySelector('.project-description-short') : null;

        if (!titleEl) return;

        const originalTitle = titleEl.innerText;
        const originalDesc = descEl ? descEl.innerText : '';

        // Feedback visual de carregamento
        const icon = btn.querySelector('i');
        icon.className = 'fas fa-spinner fa-spin';
        btn.style.borderColor = '#f7941d';

        // Lógica: Se já traduzimos, voltamos ao original (Toggle)
        if (btn.dataset.translated === 'true') {
            titleEl.innerText = btn.dataset.origTitle;
            if (descEl) descEl.innerText = btn.dataset.origDesc;
            btn.dataset.translated = 'false';
            icon.className = 'fas fa-globe';
            btn.style.borderColor = 'rgba(255,255,255,0.08)';
            return;
        }

        // Tradução via API Gratuita (Google Translate Proxy)
        const targetLang = 'en'; // Padrão internacional
        const textToTranslate = encodeURIComponent(originalTitle + " ||| " + originalDesc);
        
        fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=${targetLang}&dt=t&q=${textToTranslate}`)
            .then(res => res.json())
            .then(data => {
                let translatedFull = "";
                data[0].forEach(item => { translatedFull += item[0]; });
                
                const parts = translatedFull.split(" ||| ");
                
                // Guardamos os originais para o Toggle
                btn.dataset.origTitle = originalTitle;
                btn.dataset.origDesc = originalDesc;
                btn.dataset.translated = 'true';

                titleEl.innerText = parts[0] || originalTitle;
                if (descEl && parts[1]) descEl.innerText = parts[1];

                icon.className = 'fas fa-check';
                btn.title = "Ver Original";
                
                setTimeout(() => { icon.className = 'fas fa-undo'; }, 2000);
            })
            .catch(err => {
                console.error("Translation Error:", err);
                icon.className = 'fas fa-exclamation-triangle';
                setTimeout(() => { icon.className = 'fas fa-globe'; }, 2000);
            });
    }
</script>
