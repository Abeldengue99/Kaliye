<script>
    window.AKSANTI_DEBUG = false;
    const aksantiLog = (...args) => { if (window.AKSANTI_DEBUG) console.log(...args); };

    window.onerror = function(msg, url, lineNo, columnNo, error) {
        console.error('[AKSANITI-FATAL-ERROR]:', msg, 'at', url, ':', lineNo);
        return false;
    };

    // 1. VariÃ¡veis Globais de Contexto (Definidas no topo para evitar ReferenceErrors)
    window.BASE_URL = window.BASE_URL || '<?php echo $base_url; ?>';
    window.AKSANITI_USER = {
        type: '<?php echo isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'student'; ?>',
        isInvestor: <?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'investor') ? 'true' : 'false'; ?>,
        isPrivileged: <?php echo (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['mentor', 'admin'])) ? 'true' : 'false'; ?>,
        isVerified: <?php echo (isset($_SESSION['is_verified']) && $_SESSION['is_verified']) ? 'true' : 'false'; ?>,
        verificationStatus: '<?php echo isset($_SESSION['verification_status']) ? $_SESSION['verification_status'] : 'unsubmitted'; ?>',
        lang: '<?php echo isset($lang) ? $lang : 'pt'; ?>'
    };

    /**
     * index_scripts.php â€” Aksanti Intelligence Core JS (v3.1-Humanized-Fixed)
     * 
     * Motor de interatividade da plataforma KALIYE.
     * Cuida da seguranÃ§a (KYC), filtragem assÃ­ncrona (AJAX), likes e do Modal Elite.
     */
    /**
     * ESTILOS CUSTOMIZADOS PARA SWEETALERT (ELITE MODALS)
     * Garante que o alerta fique sempre no topo e com fundo ofuscado.
     */
    var aksantiSwalStyle = document.createElement('style');
    aksantiSwalStyle.innerHTML = `
        .swal2-container { z-index: 999999 !important; backdrop-filter: blur(15px) !important; -webkit-backdrop-filter: blur(15px) !important; }
        .swal2-popup { border: 1px solid rgba(255,255,255,0.1) !important; border-radius: 24px !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important; }
    `;
    document.head.appendChild(aksantiSwalStyle);

    aksantiLog("[AKSANITI] Core Scripts Carregados com Sucesso.");

    // --- DELEGAÃ‡ÃƒO DE EVENTOS PARA ANÃšNCIOS (DASHBOARD) ---
    (function initAdClickDelegation() {
        document.addEventListener('click', function(e) {
            const slide = e.target.closest('[data-ad-id]');
            if (!slide) return;
            
            const adId   = slide.getAttribute('data-ad-id');
            const adJson = slide.getAttribute('data-ad-json');
            
            aksantiLog('[AKSANTI-ADS] Clique detectado no anÃºncio ID:', adId);
            
            if (!adJson || adJson === 'null' || adJson === '') {
                console.warn('[AKSANTI-ADS] Aviso: AnÃºncio clicado nÃ£o possui metadados JSON vÃ¡lidos.');
                return;
            }
            
            e.stopPropagation();

            try {
                const ad = JSON.parse(adJson);
                const internalBaseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : './';
                
                // Exibir modal de anÃºncio
                const modal = document.getElementById('adModal');
                if (modal) {
                    const titleEl = document.getElementById('adModalTitle');
                    const descEl  = document.getElementById('adModalDesc');
                    const imgEl   = document.getElementById('adModalImage');
                    const btnEl   = document.getElementById('adModalLink');
                    const typeEl  = document.getElementById('adModalType');

                    if (titleEl) titleEl.innerText = ad.title || 'InovaÃ§Ã£o KALIYE';
                    if (descEl)  descEl.innerText  = ad.description || '';
                    if (typeEl)  typeEl.innerText  = (ad.type || 'ANÃšNCIO').toUpperCase();

                    let adImg = ad.image_url || '';
                    if (adImg && !adImg.startsWith('http')) {
                        adImg = internalBaseUrl + adImg.replace(/^\//, '');
                    }
                    if (imgEl) imgEl.style.backgroundImage = `url('${adImg}')`;

                    if (btnEl) {
                        if (ad.link_url) {
                            btnEl.href = ad.link_url;
                            btnEl.target = '_blank';
                            btnEl.rel = 'noopener noreferrer';
                            btnEl.style.display = 'flex';
                            btnEl.innerHTML = '<i class="fas fa-external-link-alt"></i> ACEDER AO SITE';
                        } else if (ad.contact_info) {
                            btnEl.href = 'https://wa.me/' + ad.contact_info.replace(/[^0-9]/g, '');
                            btnEl.target = '_blank';
                            btnEl.rel = 'noopener noreferrer';
                            btnEl.style.display = 'flex';
                            btnEl.innerHTML = '<i class="fab fa-whatsapp"></i> WHATSAPP';
                        } else {
                            btnEl.style.display = 'none';
                        }
                    }

                    document.body.style.overflow = 'hidden';
                    modal.style.setProperty('display', 'flex', 'important');
                    aksantiLog('[AKSANTI-ADS] Modal exibido com sucesso.');
                }
            } catch(err) {
                console.error('[AKSANTI-ADS] Erro ao abrir modal de publicidade:', err);
            }
        }, true);
    })();

    // 2. UtilitÃ¡rios de UI para Projectos
    // 2. UtilitÃ¡rios de UI para Navbar & Perfis
    window.toggleProfile = function(e) {
        if(e) e.stopPropagation();
        const dd = document.getElementById('profileDropdown');
        if(dd) dd.classList.toggle('active');
        const container = document.querySelector('.profile-container');
        if(container) container.classList.toggle('active');
        // Fecha notificaÃ§Ãµes se abertas
        const notif = document.getElementById('notifContent');
        if(notif) notif.classList.remove('active');
    };

    window.toggleNotifs = function(e) {
        if(e) e.stopPropagation();
        const dd = document.getElementById('notifContent');
        if(dd) {
            dd.classList.toggle('active');
            if(dd.classList.contains('active')) {
                loadNotifications();
            }
        }
        // Fecha perfil se aberto
        const profile = document.getElementById('profileDropdown');
        if(profile) profile.classList.remove('active');
    };

    window.closeMyProfileEdit = function() {
        const modal = document.getElementById('profileEditModal');
        if(modal) {
            modal.classList.remove('active');
            setTimeout(() => { modal.style.display = 'none'; }, 400);
        }
    };

    // Fecha dropdowns ao clicar fora
    document.addEventListener('click', function() {
        const pDd = document.getElementById('profileDropdown');
        const nDd = document.getElementById('notifContent');
        const pCont = document.querySelector('.profile-container');
        if(pDd) pDd.classList.remove('active');
        if(nDd) nDd.classList.remove('active');
        if(pCont) pCont.classList.remove('active');
    });

    window.toggleProjectMenu = function(event, projectId) {
        if(event) event.stopPropagation();
        const dropdown = document.getElementById('dropdown-' + projectId);
        document.querySelectorAll('.owner-dropdown').forEach(d => {
            if (d !== dropdown) d.style.display = 'none';
        });
        if(dropdown) dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    };

    // 2b. VerificaÃ§Ã£o de Identidade (KYC) - Motor de SeguranÃ§a Visual
    window.enforceKYC = function(arg = false) {
        const isEvent = arg && typeof arg === 'object' && typeof arg.preventDefault === 'function';
        const silent = isEvent ? false : !!arg;
        const isVerified = window.AKSANITI_USER.isVerified === true || window.AKSANITI_USER.verificationStatus === 'verified';
        if (window.AKSANITI_USER.type === 'admin' || isVerified) return true;

        if (isEvent) {
            arg.preventDefault();
            arg.stopPropagation();
        }

        if (!silent) {
            if (typeof openKYCModal === 'function') {
                openKYCModal();
            } else {
                // Fallback de seguranÃ§a caso o modal nÃ£o esteja no DOM (raro)
                window.location.href = `${BASE_URL}paginas/social/profile.php?verify_required=1`;
            }
        }
        return false;
    };

    /**
     * MOTOR GLOBAL DE KYC (Identidade & Perfil)
     * Gere a abertura, navegaÃ§Ã£o entre etapas e submissÃ£o asÃ­ncrona.
     */
    window.goKycStep = function(step) {
        document.querySelectorAll('.kyc-wizard-step').forEach(s => s.style.display = 'none');
        const target = document.getElementById('kycStep' + step);
        if (target) {
            target.style.display = 'block';
            
            // Atualizar indicadores visuais
            document.getElementById('kycStepText').innerText = `Passo ${step} de 2`;
            const dots = document.querySelectorAll('.step-dot');
            dots.forEach((dot, idx) => {
                if (idx < step) dot.classList.add('active');
                else dot.classList.remove('active');
            });

            // Dar scroll ao topo caso seja necessÃ¡rio
            const card = document.querySelector('#kycModal .elite-modal-card');
            if (card) card.scrollTop = 0;
        }
    };

    window.openKYCModal = function() {
        const modal = document.getElementById('kycModal');
        if (modal) {
            aksantiLog("[AKSANITI] Abrindo Modal de VerificaÃ§Ã£o...");
            modal.style.display = 'flex';
            modal.style.zIndex = '150000'; // ForÃ§a topo absoluto
            
            window.goKycStep(1); // Reset para o passo 1
            
            document.body.style.overflow = 'hidden'; // Evita scroll por trÃ¡s
            
            const main = document.querySelector('.main-content-wrapper');
            if (main) {
                main.style.filter = 'blur(10px) brightness(0.6)';
                main.style.transition = 'filter 0.4s ease';
            }
            
            const nav = document.querySelector('.nav-container');
            if (nav) nav.style.filter = 'blur(10px)';
        } else {
            console.error("[AKSANITI] Erro: Elemento #kycModal nÃ£o encontrado no DOM.");
        }
    };

    window.closeKYCModal = function() {
        const modal = document.getElementById('kycModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restaura scroll
            
            // LÃ³gica de SeguranÃ§a (Redirecionamento se for pÃ¡gina restrita)
            const currentPage = window.location.pathname.split('/').pop();
            const restrictedPages = ['messages.php', 'wallet.php', 'doubts.php', 'investor_dashboard.php', 'analytics.php', 'meeting.php', 'projects.php', 'explore_mentors.php', 'explore_students.php', 'mentorship.php'];
            
            if (restrictedPages.includes(currentPage)) {
                window.location.href = `${BASE_URL}index.php`;
                return;
            }

            const main = document.querySelector('.main-content-wrapper');
            if (main) main.style.filter = 'none';

            const navbar = document.querySelector('.nav-container');
            if (navbar) {
                navbar.style.filter = 'none';
                navbar.style.pointerEvents = 'auto';
            }
        }
    };

    // Handler de SubmissÃ£o do Novo KYC Wizard (AJAX AtÃ³mico)
    document.addEventListener('DOMContentLoaded', () => {
        const kycForm = document.getElementById('kycUploadForm');
        if (kycForm) {
            kycForm.onsubmit = function(e) {
                e.preventDefault();

                // 1. ValidaÃ§Ã£o Manual (Evita bloqueios de campos ocultos no browser)
                const fd = new FormData(this);
                const utype = window.AKSANITI_USER.type;
                let errors = [];

                if (!fd.get('bi_front') || fd.get('bi_front').size === 0) errors.push("Frente do Documento");
                if (!fd.get('bi_back') || fd.get('bi_back').size === 0) errors.push("Verso do Documento");
                if (!fd.get('selfie') || fd.get('selfie').size === 0) errors.push("Selfie ProbatÃ³ria");

                // ValidaÃ§Ã£o de Perfil
                if (utype === 'mentor') {
                    if (!fd.get('cv_file') || fd.get('cv_file').size === 0) errors.push("Curriculum Vitae (PDF)");
                    if (!fd.get('specialty')) errors.push("Especialidade");
                } else if (utype === 'investor') {
                    if (!fd.get('annual_income')) errors.push("Rendimento Anual");
                }

                if (errors.length > 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campos em Falta',
                        html: `<p style="color:rgba(255,255,255,0.6); font-size:0.8rem;">Por favor, preencha os seguintes campos obrigatÃ³rios:</p>
                               <ul style="color:#f7941d; text-align:left; font-size:0.75rem; margin-top:10px;">
                                 ${errors.map(err => `<li>${err}</li>`).join('')}
                               </ul>`,
                        background: '#0d1628',
                        color: '#fff',
                        confirmButtonColor: '#f7941d'
                    });
                    return;
                }
                
                const btn = this.querySelector('button[type="submit"]');
                const originalHtml = btn.innerHTML;
                
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> SINCRONIZANDO DOSSIÃŠ...';
                btn.disabled = true;

                const targetUrl = typeof BASE_URL !== 'undefined' ? `${BASE_URL}interface_programacao/user/upload_kyc.php` : '/interface_programacao/user/upload_kyc.php';

                fetch(targetUrl, { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.closeKYCModal();
                        Swal.fire({ 
                            icon: 'success', 
                            title: 'Candidatura Recebida!', 
                            text: 'A sua identidade e perfil estÃ£o sob revisÃ£o administrativa.', 
                            background: '#0d1628', 
                            color: '#fff', 
                            timer: 4000, 
                            showConfirmButton: false 
                        }).then(() => {
                            window.location.href = `${BASE_URL}paginas/social/profile.php`;
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro de SubmissÃ£o', text: data.message, background: '#0d1628', color: '#fff' });
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error('[KYC ATOMIC ERROR]:', err);
                    Swal.fire({ icon: 'error', title: 'Falha TÃ©cnica', text: 'NÃ£o foi possÃ­vel enviar a sua candidatura completa.', background: '#0d1628', color: '#fff' });
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
            };
        }
    });

    /**
     * FIREWALL GLOBAL AKSANTI: RestriÃ§Ã£o Total para NÃ£o-Verificados
     * Captura todos os cliques em links e botÃµes para garantir que 
     * o utilizador nÃ£o interaja com o ecossistema sem BI validado.
     * REMOVIDO: Este listener estava a bloquear cliques mesmo com as exceÃ§Ãµes
     */
    // LISTENER REMOVIDO - As proteÃ§Ãµes KYC agora sÃ£o apenas nas funÃ§Ãµes especÃ­ficas (openProjectDetails, toggleLike, etc)


    // 3. Motor de Filtragem â€” Feed de Projectos
    function getFeedFilterForm() {
        return document.getElementById('filterFormFeed');
    }

    function getFeedParams(pageArg) {
        var page = (typeof pageArg === 'number' && pageArg > 0) ? pageArg : 1;
        var params = new URLSearchParams();
        params.set('f_page', page);

        var form = getFeedFilterForm();
        if (form) {
            var fields = form.querySelectorAll('select, input[type="hidden"]');
            for (var i = 0; i < fields.length; i++) {
                if (!fields[i].name || fields[i].name === 'f_page') continue;
                if (fields[i].value && fields[i].value !== '') {
                    params.set(fields[i].name, fields[i].value);
                }
            }
        }

        return params;
    }

    function setFeedFormValues(values) {
        var form = getFeedFilterForm();
        if (!form) return;

        ['category', 'budget', 'stage', 'sort'].forEach(function(name) {
            var field = form.querySelector('[name="' + name + '"]');
            if (field) field.value = values && values[name] ? values[name] : '';
        });
    }

    function updateFeedQuickTabs() {
        var form = getFeedFilterForm();
        var category = form && form.querySelector('[name="category"]') ? form.querySelector('[name="category"]').value : '';
        var budget = form && form.querySelector('[name="budget"]') ? form.querySelector('[name="budget"]').value : '';
        var stage = form && form.querySelector('[name="stage"]') ? form.querySelector('[name="stage"]').value : '';
        var sort = form && form.querySelector('[name="sort"]') ? (form.querySelector('[name="sort"]').value || 'trending') : 'trending';

        document.querySelectorAll('[data-feed-quick]').forEach(function(btn) {
            var key = btn.getAttribute('data-feed-quick');
            var active = false;

            if (key === 'trending') active = !category && !budget && !stage && sort === 'trending';
            if (key === 'recent') active = !category && !budget && !stage && sort === 'recent';
            if (key === 'top') active = !category && !budget && !stage && sort === 'top';
            if (key === 'all') active = !category && !budget && !stage && sort === 'trending';
            if (key === 'mvp') active = !category && !budget && stage === 'MVP';
            if (key === 'high-funding') active = !category && budget === '10000000+' && !stage;

            btn.classList.toggle('active', active);
        });
    }

    function syncFeedFormFromUrl() {
        var params = new URLSearchParams(window.location.search);
        setFeedFormValues({
            category: params.get('category') || '',
            budget: params.get('budget') || '',
            stage: params.get('stage') || '',
            sort: params.get('sort') || 'trending'
        });
        updateFeedQuickTabs();
    }

    function scrollToFeedResults() {
        var zone = document.getElementById('dynamic-feed-zone');
        if (!zone) return;

        setTimeout(function() {
            zone.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 80);
    }

    function replaceFeedZone(html) {
        var zone = document.getElementById('dynamic-feed-zone');
        if (!zone) return false;

        var doc = new DOMParser().parseFromString(html, 'text/html');
        var fullPageZone = doc.getElementById('dynamic-feed-zone');

        zone.innerHTML = fullPageZone ? fullPageZone.innerHTML : html;
        zone.querySelectorAll('[data-aos]').forEach(function(el) {
            el.classList.add('aos-animate');
        });
        zone.querySelectorAll('.project-card-premium').forEach(function(card) {
            card.style.opacity = '1';
            card.style.visibility = 'visible';
        });
        return true;
    }

    function setFeedLoading(isLoading) {
        var zone = document.getElementById('dynamic-feed-zone');
        if (!zone) return;

        zone.classList.toggle('is-soft-loading', isLoading);
        zone.setAttribute('aria-busy', isLoading ? 'true' : 'false');

        var existing = zone.querySelector('.feed-filter-loading');
        if (isLoading && !existing) {
            var loader = document.createElement('div');
            loader.className = 'feed-filter-loading';
            loader.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i><span>A filtrar projectos...</span>';
            zone.prepend(loader);
        } else if (!isLoading && existing) {
            existing.remove();
        }
    }

    function renderFeedLoadError() {
        var zone = document.getElementById('dynamic-feed-zone');
        if (!zone) return;

        zone.innerHTML = '<div class="feed-empty-state">' +
            '<i class="fas fa-exclamation-triangle"></i>' +
            '<h3>Nao foi possivel actualizar o feed</h3>' +
            '<p>Tente novamente dentro de instantes.</p>' +
            '<button type="button" onclick="window.applyFeedFilters()">Tentar novamente</button>' +
        '</div>';
    }

    window.applyFeedFilters = function(pageArg, options) {
        if (options && options.event && typeof options.event.preventDefault === 'function') {
            options.event.preventDefault();
        }

        aksantiLog('[AKSANITI-FILTER] Iniciando filtragem...', pageArg);

        var zone = document.getElementById('dynamic-feed-zone');
        var params = getFeedParams(pageArg);
        var query = params.toString();
        var base = (typeof BASE_URL !== 'undefined' ? BASE_URL : '');

        updateFeedQuickTabs();

        if (!zone || typeof fetch !== 'function') {
            window.location.href = base + 'index.php?' + query + '#dynamic-feed-zone';
            return false;
        }

        setFeedLoading(true);

        if (!options || options.pushState !== false) {
            var optimisticUrl = base + 'index.php' + (query ? '?' + query : '');
            window.history.pushState({ feedQuery: query }, '', optimisticUrl);
        }

        fetch(base + 'interface_programacao/projects/get_projects_feed.php?' + query, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(function(html) {
            replaceFeedZone(html);
            setFeedLoading(false);
            updateFeedQuickTabs();

            if (typeof initAdObserver === 'function') initAdObserver();
            if (window.AOS && typeof window.AOS.refresh === 'function') window.AOS.refresh();
            if (!options || options.scrollToResults !== false) scrollToFeedResults();
        })
        .catch(function(err) {
            console.error('[AKSANITI-FILTER] Falha no endpoint parcial, tentando extrair o feed da pagina:', err);

            fetch(base + 'index.php?' + query, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then(function(html) {
                if (!replaceFeedZone(html)) throw new Error('Zona do feed nao encontrada');
                setFeedLoading(false);
                updateFeedQuickTabs();

                if (typeof initAdObserver === 'function') initAdObserver();
                if (window.AOS && typeof window.AOS.refresh === 'function') window.AOS.refresh();
                if (!options || options.scrollToResults !== false) scrollToFeedResults();
            })
            .catch(function(fallbackErr) {
                console.error('[AKSANITI-FILTER] Falha ao actualizar apenas o feed:', fallbackErr);
                setFeedLoading(false);
                renderFeedLoadError();
            });
        });

        return false;
    };

    // Limpar filtros e voltar ao feed completo
    window.clearFeedFilters = function() {
        aksantiLog('[AKSANITI-FILTER] Limpando filtros...');
        setFeedFormValues({ category: '', budget: '', stage: '', sort: 'trending' });
        updateFeedQuickTabs();
        window.applyFeedFilters(1);
    };

    window.setFeedQuickFilter = function(filter, event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        if (filter === 'recent') {
            setFeedFormValues({ category: '', budget: '', stage: '', sort: 'recent' });
        } else if (filter === 'top') {
            setFeedFormValues({ category: '', budget: '', stage: '', sort: 'top' });
        } else if (filter === 'mvp') {
            setFeedFormValues({ category: '', budget: '', stage: 'MVP', sort: 'trending' });
        } else if (filter === 'high-funding') {
            setFeedFormValues({ category: '', budget: '10000000+', stage: '', sort: 'trending' });
        } else {
            setFeedFormValues({ category: '', budget: '', stage: '', sort: 'trending' });
        }

        updateFeedQuickTabs();
        window.applyFeedFilters(1, { scrollToResults: false });
        return false;
    };

    window.addEventListener('popstate', function() {
        syncFeedFormFromUrl();
        var params = new URLSearchParams(window.location.search);
        window.applyFeedFilters(parseInt(params.get('f_page') || '1', 10), { pushState: false });
    });

    document.addEventListener('DOMContentLoaded', syncFeedFormFromUrl);

    document.addEventListener('click', function(event) {
        var link = event.target.closest('#dynamic-feed-zone .feed-pagination a, #dynamic-feed-zone .pagination-btn, #dynamic-feed-zone .pg-item, #dynamic-feed-zone .pg-arrow');
        if (!link || link.classList.contains('pg-disabled')) return;

        var explicitPage = link.getAttribute('data-feed-page');
        var page = explicitPage ? parseInt(explicitPage, 10) : null;
        if (!page) {
            var href = link.getAttribute('href') || '';
            var match = href.match(/[?&]f_page=(\d+)/);
            page = match ? parseInt(match[1], 10) : parseInt(link.textContent, 10);
        }

        if (page && typeof window.applyFeedFilters === 'function') {
            event.preventDefault();
            window.applyFeedFilters(page, { event: event });
        }
    });

    function bindFeedFilterAutoSubmit() {
        var form = getFeedFilterForm();
        if (!form || form.dataset.feedAutoBound === '1') return;

        form.dataset.feedAutoBound = '1';
        form.addEventListener('change', function(event) {
            if (!event.target || !event.target.matches('select')) return;
            event.preventDefault();
            window.applyFeedFilters(1, { scrollToResults: false });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindFeedFilterAutoSubmit);
    } else {
        bindFeedFilterAutoSubmit();
    }

    // 4. Motor de Rastreamento de AnÃºncios (Real-Time Metrics) - GLOBALIZADO

    const trackedAdViews = new Set();
    
    window.adObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const adId = entry.target.getAttribute('data-ad-id');
                if (adId && !trackedAdViews.has(adId)) {
                    window.trackAdView(parseInt(adId));
                    window.adObserver.unobserve(entry.target);
                }
            }
        });
    }, { threshold: 0.5 });

    window.initAdObserver = function() {
        document.querySelectorAll('[data-ad-id]').forEach(el => window.adObserver.observe(el));
    }

    window.trackAdView = function(adId) {
        if (!adId || adId < 0 || trackedAdViews.has(adId.toString())) return;
        trackedAdViews.add(adId.toString());
        fetch(BASE_URL + 'interface_programacao/ads/track_ad_metric.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ad_id=${adId}&metric_type=view`
        }).catch(() => trackedAdViews.delete(adId.toString()));
    }

    window.trackAdClick = function(adId) {
        if (!adId || adId < 0) return;
        aksantiLog("[AKSANITI] Registrando clique no anÃºncio:", adId);
        fetch(BASE_URL + 'interface_programacao/ads/track_ad_metric.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ad_id=${adId}&metric_type=click`
        }).catch(err => console.error('Erro no tracking:', err));
    }

    window.handleAdClick = function(el) {
        if (!el) return;
        const adId = el.getAttribute('data-ad-id');
        const adJson = el.getAttribute('data-ad-json');
        
        aksantiLog("[AKSANITI] Clique detectado no anÃºncio ID:", adId);
        
        try {
            if (!adJson) throw new Error("Dados do anÃºncio nÃ£o encontrados.");
            const ad = JSON.parse(adJson);
            window.openAdModal(ad);
        } catch (err) {
            console.error("[AKSANITI] Erro ao processar clique no anÃºncio:", err);
        }
    };

    window.openAdModal = function(ad) {
        const modal = document.getElementById('adModal');
        if (!modal) {
            console.error("[AKSANITI] Elemento #adModal nÃ£o encontrado.");
            return;
        }

        // Reset do Modal (Limpa vestÃ­gios anteriores)
        document.body.style.overflow = 'hidden';

        let adImg = ad.image_url || '';
        if(adImg && !adImg.startsWith('http')) adImg = BASE_URL + adImg.replace(/^\//, '');

        // Preenchimento de ConteÃºdo
        const titleEl = document.getElementById('adModalTitle');
        const descEl = document.getElementById('adModalDesc');
        const imgEl = document.getElementById('adModalImage');
        const btnEl = document.getElementById('adModalLink');
        const typeEl = document.getElementById('adModalType');

        if (titleEl) titleEl.innerText = ad.title || 'Oportunidade KALIYE';
        if (descEl) descEl.innerText = ad.description || '';
        if (typeEl) typeEl.innerText = (ad.type || 'AnÃºncio').toUpperCase();
        if (imgEl) imgEl.style.backgroundImage = adImg ? `url('${adImg}')` : 'none';
        
        if (btnEl) {
            if (ad.link_url) {
                btnEl.href = ad.link_url;
                btnEl.target = "_blank"; // Abre em novo separador
                btnEl.rel = "noopener noreferrer";
                btnEl.style.display = 'flex';
                btnEl.innerHTML = '<i class="fas fa-external-link-alt"></i> ACEDER AO SITE';
                btnEl.onclick = () => window.trackAdClick(ad.ad_id);
            } else if (ad.contact_info) {
                btnEl.href = "https://wa.me/" + ad.contact_info.replace(/[^0-9]/g, '');
                btnEl.target = "_blank"; // Abre WhatsApp em novo separador
                btnEl.rel = "noopener noreferrer";
                btnEl.style.display = 'flex';
                btnEl.innerHTML = '<i class="fab fa-whatsapp"></i> CONTACTAR VIA WHATSAPP';
                btnEl.onclick = () => window.trackAdClick(ad.ad_id);
            } else {
                btnEl.style.display = 'none'; // Esconde se nÃ£o houver link
            }
        }

        // ExibiÃ§Ã£o Final
        modal.style.setProperty('display', 'flex', 'important');
    };

    // Inicializa observador ao carregar e apÃ³s AJAX
    document.addEventListener('DOMContentLoaded', initAdObserver);
    
    // ExtensÃ£o para index_scripts (injetar no final do applyFeedFilters se necessÃ¡rio)

    // 4. GestÃ£o de Projectos (Posting & Modals)
    window.openPostModal = function() {
        // RestriÃ§Ã£o para Mentores
        if (window.IS_MENTOR === true) {
            Swal.fire({
                title: 'Acesso Restrito',
                text: 'Como Mentor, o teu papel Ã© orientar e validar projectos. Mentores nÃ£o podem publicar projectos prÃ³prios para evitar conflitos de interesse.',
                icon: 'info',
                confirmButtonText: 'Entendido',
                background: '#0d1628',
                color: '#fff'
            });
            return;
        }

        if (!enforceKYC()) return;

        const form = document.getElementById('projectForm');
        const modal = document.getElementById('projectModal');
        if (!form || !modal) return;

        form.reset();
        form.action = `${BASE_URL}interface_programacao/projects/post_project.php`;
        if(document.getElementById('modal_project_id')) document.getElementById('modal_project_id').value = '';
        
        resetProjectModal();
        modal.style.display = 'flex';
    };

    window.previewProjectVideo = function(input) {
        if (input.files && input.files[0]) {
            const container = document.getElementById('existing-video-container');
            const preview = document.getElementById('existing-video-preview');
            if(preview && container) {
                preview.src = URL.createObjectURL(input.files[0]);
                container.style.display = 'block';
                preview.load();
            }
        }
    };

    window.removeProjectVideo = function() {
        if(document.getElementById('existing-video-container')) document.getElementById('existing-video-container').style.display = 'none';
        if(document.getElementById('projectFileVideo')) document.getElementById('projectFileVideo').value = '';
    };

    // NAVEGAÃ‡ÃƒO MULTI-PASSO (Post Ideia)
    window.currentProjectStep = 1;
    window.changeProjectStep = function(n) {
        currentProjectStep += n;
        if (currentProjectStep > 4) currentProjectStep = 4;
        if (currentProjectStep < 1) currentProjectStep = 1;
        showStep(currentProjectStep);
    }

    window.showStep = function(s) {
        document.querySelectorAll('.form-step').forEach(step => step.style.display = 'none');
        if(document.getElementById('step' + s)) document.getElementById('step' + s).style.display = 'block';
        
        const pb = document.getElementById('prevBtn');
        const nb = document.getElementById('nextBtn');
        const sb = document.getElementById('submitBtn');

        if(pb) pb.style.display = (s === 1) ? 'none' : 'block';
        if(nb) nb.style.display = (s === 4) ? 'none' : 'block';
        if(sb) sb.style.display = (s === 4) ? 'block' : 'none';
    }

    window.resetProjectModal = function() {
        for (let i = 1; i <= 4; i++) {
            if(document.getElementById('step' + i)) document.getElementById('step' + i).style.display = (i === 1) ? 'block' : 'none';
        }
        currentProjectStep = 1;
        if(document.getElementById('prevBtn')) document.getElementById('prevBtn').style.display = 'none';
        if(document.getElementById('nextBtn')) document.getElementById('nextBtn').style.display = 'block';
        if(document.getElementById('submitBtn')) document.getElementById('submitBtn').style.display = 'none';
    }

    // --- FUNÃ‡ÃƒO DE EMERGÃŠNCIA: RESTAURAR CLIQUE NOS CARDS ---
    window.showVerificationRequired = function() {
        Swal.fire({
            icon: 'warning',
            title: 'VerificaÃ§Ã£o NecessÃ¡ria',
            html: `<p style="color: rgba(255,255,255,0.6); font-size:0.9rem;">Para aceder aos detalhes completos deste projecto, a sua conta precisa de estar verificada no Hub de ConfianÃ§a.</p>`,
            background: '#0d1628',
            color: '#fff',
            confirmButtonColor: '#f7941d',
            confirmButtonText: 'Verificar Agora',
            showCancelButton: true,
            cancelButtonText: 'Depois',
            cancelButtonColor: 'rgba(255,255,255,0.1)'
        }).then(result => {
            if (result.isConfirmed) {
                if (typeof openKYCModal === 'function') openKYCModal();
            }
        });
    };

    window.handleUserConnection = function(targetId, action, btn) {
        if (!targetId || !action) return;
        
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const fd = new FormData();
        fd.append('target_id', targetId);
        fd.append('action', action);

        fetch(`${BASE_URL}interface_programacao/user/connection_action.php`, {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (action === 'request') {
                    btn.innerHTML = '<i class="fas fa-clock"></i> AGUARDANDO...';
                    btn.style.background = 'rgba(255,255,255,0.05)';
                    btn.style.color = 'rgba(255,255,255,0.3)';
                } else if (action === 'accept') {
                    btn.innerHTML = '<i class="fas fa-check"></i> CONECTADO';
                    btn.style.background = '#10b981';
                } else {
                    location.reload();
                }
            } else {
                Swal.fire({ title: 'Erro', text: data.message, icon: 'error' });
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        })
        .catch(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    };

    // 5. InteraÃ§Ãµes Sociais (Likes & Comments)
    window.toggleLike = function(btn, projectId) {

        const icon = btn.querySelector('i');
        const isActive = icon.classList.contains('fas');
        
        // Efeito Visual Imediato
        icon.classList.toggle('fas');
        icon.classList.toggle('far');
        btn.style.color = !isActive ? '#ef4444' : 'rgba(255,255,255,0.3)';

        const countSpan = document.getElementById(`like-count-${projectId}`);
        if (countSpan) {
            let currentCount = parseInt(countSpan.innerText) || 0;
            countSpan.innerText = !isActive ? currentCount + 1 : Math.max(0, currentCount - 1);
        }

        fetch(`${BASE_URL}interface_programacao/projects/like_project.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: projectId })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                // Reverte se falhar
                icon.classList.toggle('fas');
                icon.classList.toggle('far');
                btn.style.color = isActive ? '#ef4444' : 'rgba(255,255,255,0.3)';
                
                if (countSpan) {
                    let currentCount = parseInt(countSpan.innerText) || 0;
                    countSpan.innerText = isActive ? currentCount + 1 : Math.max(0, currentCount - 1);
                }
            } else if (data.new_count !== undefined) {
                if (countSpan) countSpan.innerText = data.new_count;
            }
        });
    };

    // â”€â”€ F2: Voto de Estudante (Endosso de Peer) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    window.toggleProjectVote = function(btn, projectId) {
        const icon = btn.querySelector('i');
        const isActive = icon.classList.contains('fas');

        // Feedback visual imediato
        icon.classList.toggle('fas');
        icon.classList.toggle('far');
        btn.style.background    = !isActive ? 'rgba(234,179,8,0.15)' : 'rgba(255,255,255,0.05)';
        btn.style.borderColor   = !isActive ? 'rgba(234,179,8,0.4)'  : 'rgba(255,255,255,0.1)';
        btn.style.color         = !isActive ? '#facc15'               : 'rgba(255,255,255,0.3)';

        fetch(`${BASE_URL}interface_programacao/projects/vote_project.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: projectId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Actualizar contador no badge
                const badge = btn.querySelector('span');
                if (data.total_votes > 0) {
                    if (badge) {
                        badge.textContent = data.total_votes;
                    } else {
                        const s = document.createElement('span');
                        s.style.cssText = 'position:absolute;top:-6px;right:-6px;background:#facc15;color:#000;font-size:0.5rem;font-weight:900;border-radius:20px;padding:1px 4px;min-width:14px;text-align:center;';
                        s.textContent = data.total_votes;
                        btn.appendChild(s);
                    }
                } else if (badge) {
                    badge.remove();
                }
                // Sincronizar botÃ£o duplicado (ex: detail page)
                const twin = document.getElementById(`vote-detail-${projectId}`);
                if (twin && twin !== btn) twin.click && null; // evitar loop
            } else {
                // Reverter
                icon.classList.toggle('fas'); icon.classList.toggle('far');
                btn.style.background  = isActive ? 'rgba(234,179,8,0.15)' : 'rgba(255,255,255,0.05)';
                btn.style.borderColor = isActive ? 'rgba(234,179,8,0.4)'  : 'rgba(255,255,255,0.1)';
                btn.style.color       = isActive ? '#facc15'               : 'rgba(255,255,255,0.3)';
                if (data.need_project) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Voto nao registado',
                        text: data.message || 'Nao foi possivel registar o voto agora.',
                        background: '#0d1628', color: '#fff',
                        confirmButtonColor: '#f7941d', confirmButtonText: 'Submeter Ideia',
                        showCancelButton: true, cancelButtonText: 'Fechar'
                    }).then(r => { if (r.isConfirmed) window.openPostModal && openPostModal(); });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: data.limit_reached ? 'Limite diario atingido' : 'Voto nao registado',
                        text: data.message || 'Nao foi possivel registar o voto agora.',
                        background: '#0d1628',
                        color: '#fff',
                        confirmButtonColor: '#f7941d'
                    });
                }
            }
        })
        .catch(() => {
            icon.classList.toggle('fas'); icon.classList.toggle('far');
        });
    };

    // â”€â”€ F1: Candidatura de Estudante a Projecto de Investidor â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    window.openProjectApplication = function(projectId) {
        Swal.fire({
            title: 'Candidatar-me ao Projecto',
            html: `
                <p style="color:rgba(255,255,255,0.6); font-size:0.9rem; margin-bottom:1.5rem; line-height:1.6;">
                    Apresenta brevemente como podes contribuir para este projecto de investimento.
                </p>
                <textarea id="applicationMsg" rows="4" placeholder="Descreve as tuas competÃªncias e motivaÃ§Ã£o..." 
                    style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:12px; padding:1rem; font-size:0.9rem; resize:none; box-sizing:border-box;"></textarea>
            `,
            background: '#0d1628',
            color: '#fff',
            confirmButtonColor: '#10b981',
            confirmButtonText: '<i class="fas fa-paper-plane"></i> Enviar Candidatura',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
            preConfirm: () => {
                const msg = document.getElementById('applicationMsg').value.trim();
                if (!msg) { Swal.showValidationMessage('Escreve uma mensagem de candidatura.'); return false; }
                return msg;
            }
        }).then(result => {
            if (result.isConfirmed) {
                // Usar o sistema de comentÃ¡rios existente como canal de candidatura
                const fd = new FormData();
                fd.append('project_id', projectId);
                fd.append('content', `ðŸ“‹ CANDIDATURA: ${result.value}`);
                fd.append('type', 'application');
                fetch(`${BASE_URL}interface_programacao/projects/post_project_comment.php`, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? 'Candidatura Enviada!' : 'Erro',
                        text: data.success ? 'O investidor serÃ¡ notificado da tua candidatura.' : (data.message || 'Tenta novamente.'),
                        background: '#0d1628', color: '#fff', confirmButtonColor: '#f7941d'
                    });
                });
            }
        });
    };

    // â”€â”€ F3: Aviso de Equity em Tempo Real â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    window._currentProjectEquity = null; // { available, committed, remaining }

    window.checkEquityWarning = function(val) {
        const ctx    = document.getElementById('equityAvailContext');
        const warn   = document.getElementById('equityWarningMsg');
        const input  = document.getElementById('investEquityInput');
        if (!ctx || !warn || !window._currentProjectEquity) return;
        const requested  = parseFloat(val) || 0;
        const remaining  = window._currentProjectEquity.remaining;
        if (remaining !== null && requested > remaining) {
            warn.style.display = 'block';
            if (input) input.style.borderColor = '#ef4444';
        } else {
            warn.style.display = 'none';
            if (input) input.style.borderColor = '';
        }
    };

    // â”€â”€ F3 + F1: Abrir Modal de Investimento (com contexto de equity) â”€â”€â”€â”€â”€â”€â”€â”€â”€
    window.openInvestmentFlow = function(projectId) {
        // Buscar dados do projecto para popular o contexto de equity
        fetch(`${BASE_URL}interface_programacao/projects/get_project_details.php?id=${projectId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const p = data.project;
            // Guardar equity do projecto para o aviso em tempo real
            const avail     = p.equity_available !== null ? parseFloat(p.equity_available) : null;
            const committed = parseFloat(p.equity_committed || 0);
            const remaining = avail !== null ? Math.max(0, avail - committed) : null;
            window._currentProjectEquity = { available: avail, committed, remaining };

            // Popular modal de investimento
            const modal     = document.getElementById('investModal');
            const titleEl   = document.getElementById('investProjectTitle');
            const idEl      = document.getElementById('investProjectId');
            if (!modal) return;

            if (titleEl) titleEl.textContent = p.title || '';
            if (idEl)    idEl.value = projectId;

            // Mostrar contexto de equity se disponÃ­vel
            const ctx      = document.getElementById('equityAvailContext');
            const maxEl    = document.getElementById('equityAvailMax');
            const leftEl   = document.getElementById('equityAvailLeft');
            if (ctx && avail !== null) {
                if (maxEl)  maxEl.textContent  = avail.toFixed(1);
                if (leftEl) leftEl.textContent = remaining !== null ? remaining.toFixed(1) : 'â€”';
                ctx.style.display = 'block';
            } else if (ctx) {
                ctx.style.display = 'none';
            }

            // Reset steps e abrir
            const step1 = document.getElementById('investStep1');
            const step2 = document.getElementById('investStep2');
            if (step1) step1.style.display = 'block';
            if (step2) step2.style.display = 'none';
            modal.style.setProperty('display', 'flex', 'important');
        })
        .catch(() => {
            // Fallback: abrir modal sem contexto de equity
            const modal = document.getElementById('investModal');
            if (modal) {
                const idEl = document.getElementById('investProjectId');
                if (idEl) idEl.value = projectId;
                modal.style.setProperty('display', 'flex', 'important');
            }
        });
    };

    // Alias legacy para compatibilidade com investor_project_card.php
    window.openInvestModal = function(projectId, title) {
        window.openInvestmentFlow(projectId);
    };

    window.closeInvestModal = function() {
        const modal = document.getElementById('investModal');
        if (modal) modal.style.display = 'none';
        window._currentProjectEquity = null;
    };

    window.submitComment = function(projectId) {
        if (!enforceKYC()) return;
        const input = document.getElementById(`comment-input-${projectId}`);
        const content = input.value.trim();
        if (!content) return;
        
        const fd = new FormData(); fd.append('project_id', projectId); fd.append('content', content);
        fetch(`${BASE_URL}interface_programacao/projects/post_project_comment.php`, { method: 'POST', body: fd })
            .then(res => res.json()).then(data => { if (data.success) location.reload(); });
    }

    // 5b. MOTOR DE VISUALIZAÃ‡ÃƒO DE PERFIL MULTI-STEP (USER MODAL ELITE)
    // 5b. MOTOR DE VISUALIZAÃ‡ÃƒO DE PERFIL (Removido duplicado obsoleto)


    // 6. Modal Elite de Detalhes (Viewing System) - VersÃ£o SIMPLIFICADA
    window.openProjectDetails = function(id, startStep = 1) {
        aksantiLog('[AKSANTI-DEBUG] A abrir detalhes do projecto:', id, 'no passo:', startStep);
        const modal = document.getElementById('detailsModal');
        const content = document.getElementById('detailsContent');
        if(!modal || !content) {
            console.error('[AKSANTI-DEBUG] ERRO CRÃTICO: Modal elementos nÃ£o encontrados!', { modal: !!modal, content: !!content });
            alert('Erro: Modal nÃ£o encontrado no DOM');
            return;
        }

        // Mostrar modal com mÃ¡xima simplicidade e prioridade
        modal.style.display = 'flex';
        modal.style.position = 'fixed';
        modal.style.zIndex = '200000';
        modal.style.opacity = '1';
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        aksantiLog('[AKSANTI-DEBUG] Modal mostrado com sucesso');
        
        // Reset de scroll e conteÃºdo
        modal.scrollTop = 0;
        content.innerHTML = '<div style="padding: 4rem; text-align: center; color: rgba(255,255,255,0.2);"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top:1rem;">A carregar inovaÃ§Ã£o...</p></div>';

        fetch(`${BASE_URL}interface_programacao/projects/get_project_details.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const p = data.project;
                    p.owner_pic = p.profile_pic || p.owner_pic || '';
                    p.owner_name = p.full_name || p.owner_name || 'Autor';
                    p.user_type = p.owner_type || p.user_type || '';
                    p.video_url = p.video_url || '';
                    p.pitch_video_url = p.pitch_video_url || p.video_url;
                    p.tags = p.tags || [];
                    p.media = p.media || [];

                    const media = p.media;
                    const tags = p.tags;
                    let mediaHtml = media.length > 0 ? '<div style="display:grid; grid-template-columns:repeat(auto-fit,120px); gap:10px; margin-bottom: 1.5rem;">' + media.map(m => {
                        const src = m.filename || m.media_url || m.url || '';
                        const safeSrc = src && (src.startsWith('http') || src.startsWith('/'))
                            ? src
                            : (src.startsWith('carregamentos/') ? BASE_URL + src : BASE_URL + 'carregamentos/projects/' + src);
                        return `<img src="${safeSrc}" style="width:120px; height:80px; object-fit:cover; border-radius:8px; border: 1px solid rgba(255,255,255,0.1);">`;
                    }).join('') + '</div>' : '';
                    let tagsHtml = tags.length > 0 ? '<div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:1rem;">' + tags.map(t => `<span style="background:rgba(247,148,29,0.1); color:#f7941d; padding:4px 8px; border-radius:6px; font-size:0.6rem; font-weight:800; text-transform:uppercase;">${t}</span>`).join('') + '</div>' : '';
                    window.currentProjectData = { p, mediaHtml, tagsHtml };
                    renderProjectModalStep(startStep);
                } else {
                    content.innerHTML = `<p style="text-align:center; color:#ef4444; padding:2rem;">${data.message || 'Acesso restrito.'}</p>`;
                }
            })
            .catch(() => { content.innerHTML = '<p style="text-align:center; color:#ef4444; padding:2rem;">Erro de ligaÃ§Ã£o ao motor KALIYE.</p>'; });
    };

    window.renderProjectModalStep = function(step) {
        if (!window.currentProjectData) return;
        const { p, mediaHtml, tagsHtml } = window.currentProjectData;
        const content = document.getElementById('detailsContent');
        const videoUrl = p.pitch_video_url || p.video_url;
        let stepTitle = 'Detalhes';
        let stepContent = '';
        let nextAction = '';
        let prevAction = '';
        const labelStyle = 'display:block; font-size:0.6rem; font-weight:950; color:rgba(255,255,255,0.3); text-transform:uppercase; margin-bottom:6px;';
        const dataBox = 'background:rgba(255,255,255,0.02); padding:1rem; border-radius:14px; border:1px solid rgba(255,255,255,0.05);';
        const escProject = (value) => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
        const projectLabel = (key) => ({
            project_id:'ID do projecto', owner_id:'ID do proprietÃ¡rio', title:'TÃ­tulo', description:'DescriÃ§Ã£o',
            category:'Categoria', budget_needed:'Valor total necessÃ¡rio', image_url:'Imagem de capa',
            video_url:'VÃ­deo', pitch_video_url:'Pitch em vÃ­deo', execution_time:'Tempo de execuÃ§Ã£o',
            team_size:'Tamanho da equipa', project_stage:'EstÃ¡gio do projecto', target_audience:'PÃºblico-alvo',
            needs_to_advance:'O que falta para avanÃ§ar', idea_origin:'Origem da ideia', motivation:'MotivaÃ§Ã£o',
            project_url:'Website / URL', funding_goal:'Meta de financiamento', minimum_investment:'Investimento mÃ­nimo',
            maximum_investment:'Investimento mÃ¡ximo', campaign_start_date:'Data inÃ­cio da campanha',
            campaign_end_date:'Data fim da campanha', funding_type:'Tipo de financiamento',
            equity_available:'Equity disponÃ­vel', equity_committed:'Equity comprometido',
            total_invested:'Total investido', total_investors:'Total de investidores',
            approval_status:'Estado de aprovaÃ§Ã£o', approved_by:'Aprovado por', approved_at:'Aprovado em',
            is_public:'Projecto pÃºblico', is_featured:'Em destaque', created_at:'Criado em',
            updated_at:'Actualizado em', market_score:'PontuaÃ§Ã£o de mercado', ai_status:'Estado IA', status:'Estado'
        }[key] || key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()));
        const projectValue = (key, value) => {
            if (value === null || value === undefined || value === '') return '<span style="color:rgba(255,255,255,0.32);">NÃ£o informado</span>';
            if (typeof value === 'boolean') return value ? 'Sim' : 'NÃ£o';
            const money = ['budget_needed','funding_goal','minimum_investment','maximum_investment','total_invested','escrow_balance'];
            if (money.includes(key) && !isNaN(parseFloat(value))) return new Intl.NumberFormat('pt-AO').format(parseFloat(value)) + ' Kz';
            if (['equity_available','equity_committed','expected_return_rate'].includes(key) && !isNaN(parseFloat(value))) return parseFloat(value).toLocaleString('pt-AO') + '%';
            if ((key.includes('_date') || key.includes('_at')) && String(value).length >= 10) {
                const d = new Date(value);
                if (!isNaN(d.getTime())) return d.toLocaleString('pt-PT');
            }
            if (key.includes('url')) {
                const safe = escProject(value);
                return `<a href="${safe}" target="_blank" rel="noopener" style="color:#60a5fa;text-decoration:none;font-weight:800;">${safe}</a>`;
            }
            return escProject(value);
        };
        const renderAllProjectFields = () => {
            const fields = p.project_fields || p;
            const skip = { full_name:true, profile_pic:true, owner_type:true, mentorship_status:true, verification_status:true, is_verified:true, project_fields:true, media:true, tags:true };
            const preferred = ['project_id','owner_id','title','description','category','project_stage','budget_needed','funding_goal','minimum_investment','maximum_investment','funding_type','equity_available','equity_committed','total_invested','total_investors','campaign_start_date','campaign_end_date','execution_time','team_size','target_audience','needs_to_advance','idea_origin','motivation','project_url','image_url','video_url','pitch_video_url','approval_status','approved_by','approved_at','is_public','is_featured','market_score','ai_status','status','created_at','updated_at'];
            const keys = preferred.filter(k => Object.prototype.hasOwnProperty.call(fields, k));
            Object.keys(fields).forEach(k => { if (!keys.includes(k) && !skip[k]) keys.push(k); });
            let out = `<div style="${dataBox} border-left:4px solid #10b981; margin-bottom:1rem;"><div style="${labelStyle}">Regra desta tela</div><div style="color:rgba(255,255,255,0.75);line-height:1.6;">Todos os campos do projecto retornados pela base de dados aparecem abaixo.</div></div>`;
            out += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">';
            keys.forEach(k => {
                if (skip[k]) return;
                const raw = fields[k];
                const long = String(raw || '').length > 120 || ['description','motivation','needs_to_advance','idea_origin'].includes(k);
                out += `<div style="${dataBox}${long ? ' grid-column:1/-1;' : ''}"><div style="${labelStyle}">${escProject(projectLabel(k))}</div><div style="color:rgba(255,255,255,0.82);font-size:0.9rem;line-height:1.55;word-break:break-word;">${projectValue(k, raw)}</div></div>`;
            });
            out += '</div>';
            if (tagsHtml) out += `<div style="margin-top:1.5rem;"><div style="${labelStyle} margin-bottom:0.75rem;">Tags TecnolÃ³gicas</div>${tagsHtml}</div>`;
            if (mediaHtml) out += `<div style="margin-top:1.5rem;"><div style="${labelStyle} margin-bottom:0.75rem;">Galeria do Projecto</div>${mediaHtml}</div>`;
            return out;
        };

        if (step === 0) {
            stepTitle = 'Pitch Cinema';
            nextAction = 'renderProjectModalStep(1)';
            prevAction = 'closeProjectDetailsModal()';
            const fullVideoPath = videoUrl ? (videoUrl.startsWith('http') ? videoUrl : `${BASE_URL}carregamentos/projects/${videoUrl}`) : '';
            stepContent = `<div style="background:#000; border-radius:24px; overflow:hidden; min-height:300px; display:flex; align-items:center; justify-content:center;">${videoUrl ? `<video src="${fullVideoPath}" controls style="width:100%; height:100%; object-fit:contain;"></video>` : `<p style="opacity:0.2;">Sem Pitch de VÃ­deo</p>`}</div>`;
        } else if (step === 1) {
            stepTitle = 'VisÃ£o';
            nextAction = 'renderProjectModalStep(2)';
            prevAction = videoUrl ? 'renderProjectModalStep(0)' : 'closeProjectDetailsModal()';
            stepContent = `<div style="display:flex; align-items:center; gap:15px; margin-bottom:1rem;"><img src="${BASE_URL}${p.owner_pic || 'recursos/images/default_profile.png'}" style="width:40px; height:40px; border-radius:10px; object-fit:cover;"><div><div style="color:#fff; font-weight:800;">${p.owner_name}</div><div style="color:rgba(255,255,255,0.5); font-size:0.8rem;">${(p.owner_type || p.user_type || 'Membro').toString().toUpperCase()}</div></div></div><p style="color:rgba(255,255,255,0.7); line-height:1.6;">${p.description || 'DescriÃ§Ã£o nÃ£o disponÃ­vel.'}</p><div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-top:1.5rem;"><div style="${dataBox}"><div style="${labelStyle}">Categoria</div><div style="color:#fff; font-weight:900;">${p.category || 'NÃ£o definida'}</div></div><div style="${dataBox}"><div style="${labelStyle}">Status</div><div style="color:#fff; font-weight:900;">${p.approval_status || 'Pendente'}</div></div></div>`;
        } else if (step === 2) {
            stepTitle = 'ExecuÃ§Ã£o';
            nextAction = 'renderProjectModalStep(3)';
            prevAction = 'renderProjectModalStep(1)';
            stepContent = `<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:1rem;"><div style="${dataBox}"><div style="${labelStyle}">Equipa</div><div style="color:#fff; font-weight:900;">${p.team_size || '1'}</div></div><div style="${dataBox}"><div style="${labelStyle}">EstÃ¡gio</div><div style="color:#fff; font-weight:900;">${p.project_stage || 'Ideia'}</div></div></div>`;
            if (p.execution_time) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">Tempo de ExecuÃ§Ã£o</div><div style="color:#fff; font-weight:700;">${p.execution_time}</div></div>`;
            }
            if (p.target_audience) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">PÃºblico-Alvo</div><div style="color:rgba(255,255,255,0.7);">${p.target_audience}</div></div>`;
            }
            if (p.idea_origin) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">Origem da Ideia</div><div style="color:rgba(255,255,255,0.7);">${p.idea_origin}</div></div>`;
            }
            if (p.motivation) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">MotivaÃ§Ã£o</div><div style="color:rgba(255,255,255,0.7);">${p.motivation}</div></div>`;
            }
            if (p.needs_to_advance) {
                stepContent += `<div style="${dataBox} border-left:4px solid #3b82f6;"><div style="${labelStyle}">O Que Falta Para AvanÃ§ar?</div><div style="color:rgba(255,255,255,0.7);">${p.needs_to_advance}</div></div>`;
            }
            if (p.project_url) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">Link Externo</div><div><a href="${p.project_url}" target="_blank" style="color:#3b82f6; text-decoration:none;">${p.project_url}</a></div></div>`;
            }
        } else if (step === 3) {
            stepTitle = 'Financeiro';
            nextAction = 'renderProjectModalStep(4)';
            prevAction = 'renderProjectModalStep(2)';
            const goal = p.funding_goal || p.budget_needed || 0;
            stepContent = `<div style="${dataBox} border-left:4px solid #f7941d;"><div style="${labelStyle}">Meta</div><div style="color:#fff; font-size:1.5rem; font-weight:950;">${new Intl.NumberFormat('pt-AO').format(goal)} Kz</div></div>`;
            if (p.minimum_investment) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">Investimento MÃ­nimo</div><div style="color:#fff; font-weight:900;">${new Intl.NumberFormat('pt-AO').format(p.minimum_investment)} Kz</div></div>`;
            }
            if (p.equity_available) {
                stepContent += `<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:1rem;"><div style="${dataBox}"><div style="${labelStyle}">Equity DisponÃ­vel</div><div style="color:#f7941d; font-weight:900;">${p.equity_available}%</div></div><div style="${dataBox}"><div style="${labelStyle}">Equity Comprometido</div><div style="color:#ef4444; font-weight:900;">${p.equity_committed || '0'}%</div></div></div>`;
            }
            if (p.total_invested) {
                stepContent += `<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:1rem;"><div style="${dataBox}"><div style="${labelStyle}">Total Investido</div><div style="color:#10b981; font-weight:900;">${new Intl.NumberFormat('pt-AO').format(p.total_invested)} Kz</div></div><div style="${dataBox}"><div style="${labelStyle}">Investidores</div><div style="color:#3b82f6; font-weight:900;">${p.total_investors || 0}</div></div></div>`;
            }
            if (p.campaign_end_date) {
                stepContent += `<div style="${dataBox} margin-top:1rem;"><div style="${labelStyle}">Fim da Campanha</div><div style="color:#fff; font-weight:700;">${new Date(p.campaign_end_date).toLocaleDateString('pt-PT')}</div></div>`;
            }
            if (mediaHtml) {
                stepContent += `<div style="margin-top:1.5rem;"><div style="${labelStyle} margin-bottom:0.75rem;">Galeria do Projecto</div>${mediaHtml}</div>`;
            }
            if (tagsHtml) {
                stepContent += `<div style="margin-top:1.5rem;"><div style="${labelStyle} margin-bottom:0.75rem;">Tags TecnolÃ³gicas</div>${tagsHtml}</div>`;
            }
        } else if (step === 4) {
            stepTitle = 'Dossier completo';
            nextAction = 'closeProjectDetailsModal()';
            prevAction = 'renderProjectModalStep(3)';
            stepContent = renderAllProjectFields();
        }

        content.innerHTML = `
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size:1.5rem; font-weight:950; color:#fff; margin-bottom:1.5rem;">${stepTitle}</h2>
                ${stepContent}
            </div>
            <div style="display:flex; justify-content:space-between; gap:10px;">
                <button onclick="${prevAction}" style="flex:1; background:rgba(255,255,255,0.05); color:#fff; border:none; padding:12px; border-radius:12px; font-weight:800; cursor:pointer;">VOLTAR</button>
                <button onclick="${nextAction}" style="flex:1.5; background:#f7941d; color:#fff; border:none; padding:12px; border-radius:12px; font-weight:950; cursor:pointer;">${step === 4 ? 'FECHAR' : 'PRÃ“XIMO'}</button>
            </div>
        `;
    };

    window.toggleComments = function(projectId) {
        const section = document.getElementById(`comments-section-${projectId}`);
        if (section) section.style.display = section.style.display === 'none' ? 'block' : 'none';
    };

    window.openUserCard = function(userId) {
        aksantiLog('[AKSANTI] A abrir card de utilizador:', userId);
        const modal = document.getElementById('userCardModal');
        const headerZone = document.getElementById('userCardHeaderZone');
        const contentZone = document.getElementById('userCardContent');
        const profileDd = document.getElementById('profileDropdown');
        
        if(!modal || !headerZone || !contentZone) {
            console.error('[AKSANTI] Erro: Elementos do modal de utilizador nÃ£o encontrados no DOM.');
            return;
        }

        // Limpeza de UI: Fecha dropdowns de navegaÃ§Ã£o
        if(profileDd) profileDd.classList.remove('active');

        // Reset e Loader Visual
        modal.style.setProperty('display', 'flex', 'important');
        modal.style.opacity = '1';
        setTimeout(() => modal.classList.add('active'), 10);
        
        headerZone.innerHTML = '<div style="height:180px; background:rgba(255,255,255,0.02); display:flex; align-items:center; justify-content:center;"><i class="fas fa-spinner fa-spin"></i></div>';
        contentZone.innerHTML = '<div style="padding: 2rem; text-align: center; color: rgba(255,255,255,0.2);"><p>Sincronizando dossiÃª...</p></div>';

        fetch(`${BASE_URL}interface_programacao/user/get_user_card.php?id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    window.currentViewUserData = data;
                    window.currentUserCardData = data;
                    renderUserModalStep(1);
                } else {
                    contentZone.innerHTML = `<p style="text-align:center; color:#ef4444;">${data.message}</p>`;
                }
            })
            .catch(() => { contentZone.innerHTML = '<p style="text-align:center; color:#ef4444;">Falha no motor de dados.</p>'; });
    };

    window.renderUserModalStep = function(step) {
        const u = window.currentUserCardData.user;
        const headerZone = document.getElementById('userCardHeaderZone');
        const contentZone = document.getElementById('userCardContent');
        if(!u || !headerZone || !contentZone) return;

        // FormataÃ§Ã£o de Data de AdesÃ£o
        const memberSince = new Date(u.created_at).toLocaleDateString('pt-PT', { month: 'long', year: 'numeric' });
        
        // Estrelas de AvaliaÃ§Ã£o
        const fullStars = Math.floor(u.rating);
        const hasHalfStar = (u.rating % 1) >= 0.5;
        let starsHtml = '';
        for(let i=0; i<5; i++) {
            if(i < fullStars) starsHtml += '<i class="fas fa-star" style="color:#f7941d; font-size:0.75rem;"></i>';
            else if(i === fullStars && hasHalfStar) starsHtml += '<i class="fas fa-star-half-alt" style="color:#f7941d; font-size:0.75rem;"></i>';
            else starsHtml += '<i class="far fa-star" style="color:rgba(255,255,255,0.2); font-size:0.75rem;"></i>';
        }

        // LÃ³gica de Mensagem (Exclusividade Aksanti)
        // Estudantes nÃ£o podem enviar mensagens a Investidores
        const isStudent = window.sessionUserType && window.sessionUserType.toLowerCase().includes('student');
        const isTargetInvestor = u.role.toLowerCase() === 'investor';
        const canMessage = !(isStudent && isTargetInvestor);

        const messageBtnHtml = canMessage ? `
            <button onclick="openChatWithUser(${u.id})" class="view-profile-full-btn" style="flex:1; background:rgba(255,255,255,0.05); color:#fff; border:none; padding:15px; border-radius:16px; font-weight:800; cursor:pointer;"><i class="fas fa-comment-dots"></i> MENSAGEM</button>
        ` : `
            <div style="flex:1; background:rgba(255,255,255,0.02); color:rgba(255,255,255,0.2); padding:15px; border-radius:16px; font-size:0.65rem; font-weight:800; text-align:center; border:1px dashed rgba(255,255,255,0.05);">CANAL PROTEGIDO</div>
        `;

        // LÃ³gica de BotÃ£o de ConexÃ£o (Branding: REFORÃ‡AR REDE)
        let connectBtnHtml = '';
        const connectBtnStyle = `flex:1; background:linear-gradient(45deg, #f7941d, #ffb347); color:#fff; border:none; padding:15px; border-radius:16px; font-weight:900; cursor:pointer; box-shadow: 0 10px 20px rgba(247,148,29,0.2); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);`;
        
        if (u.connection_status === 'none') {
            connectBtnHtml = `<button onclick="handleUserConnection(${u.id}, 'request', this)" style="${connectBtnStyle}"><i class="fas fa-bolt"></i> REFORÃ‡AR REDE</button>`;
        } else if (u.connection_status === 'pending') {
            connectBtnHtml = `<button disabled style="flex:1; background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.3); border:none; padding:15px; border-radius:16px; font-weight:800; cursor:not-allowed;"><i class="fas fa-clock"></i> AGUARDANDO...</button>`;
        } else if (u.connection_status === 'accepted') {
            connectBtnHtml = `<button onclick="handleUserConnection(${u.id}, 'remove', this)" style="flex:1; background:#10b981; color:#fff; border:none; padding:15px; border-radius:16px; font-weight:800; cursor:pointer;"><i class="fas fa-check"></i> CONECTADO</button>`;
        } else if (u.connection_status === 'received') {
            connectBtnHtml = `<button onclick="handleUserConnection(${u.id}, 'accept', this)" style="${connectBtnStyle}">ACEITAR REDE</button>`;
        }

        // Header do Modal Elite
        headerZone.innerHTML = `
            <div class="user-card-header-elite" style="display:flex; align-items:center; gap:20px; padding: 2.5rem;">
                <div class="user-avatar-premium-wrapper" style="position:relative;">
                    <img src="${String(u.avatar || '').match(/^https?:\/\//) ? u.avatar : BASE_URL + u.avatar}" style="width:100px; height:100px; border-radius:24px; object-fit:cover; border:3px solid #f7941d; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                    ${u.is_verified ? '<div style="position:absolute; bottom:-5px; right:-5px; background:#f7941d; color:#fff; width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #0d1628; font-size:0.6rem;"><i class="fas fa-check"></i></div>' : ''}
                </div>
                <div style="flex-grow:1;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <h2 style="font-size:1.4rem; color:#fff; font-weight:800; margin:0;">${u.name}</h2>
                        <span style="background:rgba(247,148,29,0.1); color:#f7941d; padding:4px 10px; border-radius:8px; font-size:0.65rem; font-weight:800; text-transform:uppercase;">${u.role}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px; margin-top:8px;">
                        <div style="display:flex; align-items:center; gap:4px; background:rgba(255,255,255,0.05); padding:4px 10px; border-radius:30px; border:1px solid rgba(255,255,255,0.1);">
                            <span style="color:#fff; font-size:0.7rem; font-weight:800;">${u.connections_count} CONEXÃ•ES</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:4px;">
                            ${starsHtml}
                            <span style="color:rgba(255,255,255,0.4); font-size:0.65rem; font-weight:700;">(${u.rating > 0 ? u.rating.toFixed(1) : 'Sem avaliaÃ§Ãµes'})</span>
                        </div>
                    </div>
                    <p style="color:rgba(255,255,255,0.4); font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-top:8px;">
                        Membro KALIYE desde ${memberSince}
                    </p>
                </div>
            </div>
        `;

        const skillsArray = u.skills ? u.skills.split(',').map(s => s.trim()) : [];
        const skillsHtml = skillsArray.length > 0 ? skillsArray.map(s => `<span style="background:rgba(255,255,255,0.05); color:#fff; padding:6px 12px; border-radius:10px; font-size:0.7rem; font-weight:800; text-transform:uppercase; border:1px solid rgba(255,255,255,0.08);">${s}</span>`).join('') : '<span style="color:rgba(255,255,255,0.2); font-size:0.75rem;">Nenhuma expertise listada.</span>';
        const focusArray = u.focus_areas ? u.focus_areas.split(',').map(s => s.trim()).filter(Boolean) : [];
        const focusHtml = focusArray.length > 0 ? focusArray.map(s => `<span style="background:rgba(247,148,29,0.08); color:#f7941d; padding:6px 12px; border-radius:10px; font-size:0.7rem; font-weight:800; text-transform:uppercase; border:1px solid rgba(247,148,29,0.16);">${s}</span>`).join('') : '<span style="color:rgba(255,255,255,0.2); font-size:0.75rem;">Nenhuma area de foco listada.</span>';
        
        contentZone.innerHTML = `
            <div style="padding: 0 2.5rem 2.5rem;">
                <div class="user-info-box" style="background:rgba(255,255,255,0.02); padding:1.5rem; border-radius:20px; border:1px solid rgba(255,255,255,0.05); margin-bottom:1.5rem;">
                    <h3 style="color:#f7941d; font-size:0.65rem; font-weight:900; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:12px;">A MINHA HISTÃ“RIA</h3>
                    <p style="color:rgba(255,255,255,0.7); line-height:1.6; font-size:0.9rem; margin:0;">${u.bio}</p>
                </div>

                <div class="user-info-box" style="background:rgba(255,255,255,0.02); padding:1.5rem; border-radius:20px; border:1px solid rgba(255,255,255,0.05); margin-bottom:1.5rem;">
                    <h3 style="color:#f7941d; font-size:0.65rem; font-weight:900; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:15px;">HABILIDADES & ESPECIALIDADES</h3>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        ${skillsHtml}
                    </div>
                </div>

                <div class="user-info-box" style="background:rgba(255,255,255,0.02); padding:1.5rem; border-radius:20px; border:1px solid rgba(255,255,255,0.05); margin-bottom:1.5rem;">
                    <h3 style="color:#f7941d; font-size:0.65rem; font-weight:900; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:15px;">AREAS DE FOCO</h3>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">${focusHtml}</div>
                    ${u.experience_summary ? `<p style="color:rgba(255,255,255,0.62); line-height:1.6; font-size:0.82rem; margin:1rem 0 0;">${u.experience_summary}</p>` : ''}
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:2rem;">
                    <div style="background:rgba(255,255,255,0.02); padding:1.2rem; border-radius:18px; border:1px solid rgba(255,255,255,0.05);">
                        <h3 style="color:#f7941d; font-size:0.6rem; font-weight:900; text-transform:uppercase; margin-bottom:8px;">LOCALIZAÃ‡ÃƒO</h3>
                        <p style="color:#fff; font-size:0.85rem; font-weight:700; margin:0;"><i class="fas fa-map-marker-alt" style="margin-right:6px; opacity:0.5;"></i> ${u.location}</p>
                    </div>
                    <div style="background:rgba(255,255,255,0.02); padding:1.2rem; border-radius:18px; border:1px solid rgba(255,255,255,0.05);">
                        <h3 style="color:#f7941d; font-size:0.6rem; font-weight:900; text-transform:uppercase; margin-bottom:8px;">FORMAÃ‡ÃƒO</h3>
                        <p style="color:#fff; font-size:0.85rem; font-weight:700; margin:0;"><i class="fas fa-graduation-cap" style="margin-right:6px; opacity:0.5;"></i> ${u.level}</p>
                    </div>
                    ${u.institution ? `<div style="background:rgba(255,255,255,0.02); padding:1.2rem; border-radius:18px; border:1px solid rgba(255,255,255,0.05);"><h3 style="color:#f7941d; font-size:0.6rem; font-weight:900; text-transform:uppercase; margin-bottom:8px;">INSTITUICAO</h3><p style="color:#fff; font-size:0.85rem; font-weight:700; margin:0;"><i class="fas fa-university" style="margin-right:6px; opacity:0.5;"></i> ${u.institution}</p></div>` : ''}
                    ${u.organization ? `<div style="background:rgba(255,255,255,0.02); padding:1.2rem; border-radius:18px; border:1px solid rgba(255,255,255,0.05);"><h3 style="color:#f7941d; font-size:0.6rem; font-weight:900; text-transform:uppercase; margin-bottom:8px;">ORGANIZACAO</h3><p style="color:#fff; font-size:0.85rem; font-weight:700; margin:0;"><i class="fas fa-briefcase" style="margin-right:6px; opacity:0.5;"></i> ${u.organization}</p></div>` : ''}
                </div>

                <div style="display:flex; gap:12px;">
                    ${messageBtnHtml}
                    ${connectBtnHtml}
                </div>
            </div>
        `;
    };

    window.openChatWithUser = function(userId) {
        // Redireciona para o portal de mensagens com o ID do utilizador alvo
        window.location.href = `${BASE_URL}paginas/social/messages.php?start=${userId}`;
    };

    window.openEliteChat = function(userId, name, avatar) {
        if (!enforceKYC()) return;
        const modal = document.getElementById('eliteChatModal');
        if(!modal) return;
        
        lastEliteMsgId = 0; // Reset para nova conversa
        
        // Setup inicial
        document.getElementById('eliteReceiverId').value = userId;
        document.getElementById('eliteChatName').innerText = name;
        document.getElementById('eliteChatPfp').querySelector('img').src = BASE_URL + avatar;
        
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
        
        // Limpar mensagens anteriores e carregar histÃ³rico
        const messagesZone = document.getElementById('eliteChatMessages');
        messagesZone.innerHTML = '<div style="margin-top:10rem; text-align:center; color:rgba(255,255,255,0.1);"><i class="fas fa-spinner fa-spin"></i><br>Sincronizando canal...</div>';
        
        fetchEliteMessages(userId);

        // Iniciar Poll SÃ­ncrono
        if(eliteChatPoll) clearInterval(eliteChatPoll);
        eliteChatPoll = setInterval(() => { fetchEliteMessages(userId, true); }, 3000);

        // Tracker de Emojis
        const picker = document.querySelector('emoji-picker');
        const pickerContainer = document.getElementById('emoji-picker-container');
        const emojiBtn = document.getElementById('emojiBtn');
        const inputField = document.getElementById('eliteMsgText');

        emojiBtn.onclick = (e) => {
            e.stopPropagation();
            pickerContainer.style.display = pickerContainer.style.display === 'none' ? 'block' : 'none';
        };

        if(!window.emojiInitialized) {
            picker.addEventListener('emoji-click', event => {
                inputField.value += event.detail.unicode;
                pickerContainer.style.display = 'none';
            });
            window.emojiInitialized = true;
        }

        document.addEventListener('click', (e) => {
            if(!pickerContainer.contains(e.target)) pickerContainer.style.display = 'none';
        });
    };

    window.closeEliteChat = function() {
        const modal = document.getElementById('eliteChatModal');
        if(modal) {
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 400);
        }
        if(eliteChatPoll) clearInterval(eliteChatPoll);
    };

    window.fetchEliteMessages = function(userId, isPoll = false) {
        const endpoint = isPoll ? `${BASE_URL}interface_programacao/social/get_new_messages.php?conversation_id=${userId}&last_id=${lastEliteMsgId}` : `${BASE_URL}interface_programacao/social/get_messages.php?user_id=${userId}`;
        
        fetch(endpoint)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const messagesZone = document.getElementById('eliteChatMessages');
                    if(!isPoll) messagesZone.innerHTML = '';
                    
                    data.messages.forEach(msg => {
                        const div = document.createElement('div');
                        const isMine = msg.sender_id == window.sessionUserId; // window.sessionUserId deve ser definido no index.php
                        div.className = `elite-msg-bubble ${isMine ? 'elite-msg-mine' : 'elite-msg-theirs'}`;
                        
                        let mediaHtml = '';
                        if(msg.media_url) {
                            if(msg.media_type === 'image') mediaHtml = `<img src="${BASE_URL + msg.media_url}" style="width:100%; border-radius:12px; margin-bottom:8px; cursor:zoom-in;">`;
                            else mediaHtml = `<div style="background:rgba(0,0,0,0.2); padding:10px; border-radius:10px; font-size:0.75rem; border:1px solid rgba(255,255,255,0.05);"><i class="fas fa-file-alt"></i> ${msg.media_type.toUpperCase()}</div>`;
                        }

                        // Visto (Status de Leitura)
                        const seenHtml = isMine ? `<span class="visto-icon ${msg.is_read ? 'visto-read' : 'visto-delivered'}"><i class="fas ${msg.is_read ? 'fa-check-double' : 'fa-check'}"></i></span>` : '';

                        div.innerHTML = `
                            ${mediaHtml}
                            <div>${msg.content}</div>
                            ${seenHtml}
                        `;
                        messagesZone.appendChild(div);
                        lastEliteMsgId = Math.max(lastEliteMsgId, msg.message_id);
                    });

                    if(data.messages.length > 0) messagesZone.scrollTop = messagesZone.scrollHeight;
                }
            });
    };

    window.previewEliteMedia = function(input) {
        const info = document.getElementById('eliteMediaInfo');
        const preview = document.getElementById('eliteMediaPreview');
        if(input.files && input.files[0]) {
            info.innerText = input.files[0].name;
            preview.style.display = 'block';
        }
    };

    window.clearEliteMedia = function() {
        document.getElementById('eliteMediaInput').value = '';
        document.getElementById('eliteMediaPreview').style.display = 'none';
    };

    window.submitEliteMessage = function(e) {
        e.preventDefault();
        const form = document.getElementById('eliteChatForm');
        const formData = new FormData(form);
        const textInput = document.getElementById('eliteMsgText');
        
        if(textInput.value.trim() === '' && !document.getElementById('eliteMediaInput').files[0]) return;

        fetch(`${BASE_URL}interface_programacao/social/send_message.php`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                textInput.value = '';
                clearEliteMedia();
                fetchEliteMessages(formData.get('receiver_id'), true);
            }
        });
    };

    window.closeUserCard = function() {
        const modal = document.getElementById('userCardModal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => { modal.style.display = 'none'; }, 400);
        }
    };

    window.handleUserConnection = function(userId, action, btn) {
        if (!enforceKYC()) return;
        const formData = new FormData();
        formData.append('target_id', userId);
        formData.append('action', action);

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>...';

        fetch(`${BASE_URL}interface_programacao/user/connection_action.php`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.currentUserCardData.user.connection_status = data.new_status;
                if(data.new_status === 'accepted') window.currentUserCardData.user.connections_count++;
                renderUserModalStep(1);
            } else {
                Swal.fire('GestÃ£o de Rede', data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        })
        .catch(() => {
            Swal.fire('Erro', 'NÃ£o foi possÃ­vel canalizar o pedido.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    };

    // 5c. MOTOR DE EDIÃ‡ÃƒO DE PERFIL PRÃ“PRIO (EDIT MODAL ELITE)
    // 5c. MOTOR DE EDIÃ‡ÃƒO DE PERFIL PRÃ“PRIO (EDIT MODAL ELITE V3)
    window.openMyProfileEdit = function() {
        const modal = document.getElementById('profileEditModal');
        const content = document.getElementById('profileEditContent');
        const profileDd = document.getElementById('profileDropdown');
        if(!modal || !content) return;

        if(profileDd) profileDd.classList.remove('active');

        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
        content.innerHTML = '<div style="padding: 4rem; text-align: center; color: rgba(255,255,255,0.2);"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top:1rem;">A sincronizar dossier KALIYE...</p></div>';

        fetch(`${BASE_URL}interface_programacao/user/get_my_profile.php`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    window.currentEditProfileData = data.data;
                    renderProfileEditStep(0);
                } else {
                    content.innerHTML = `<p style="text-align:center; color:#ef4444; padding:2rem;">${data.message}</p>`;
                }
            })
            .catch(() => { content.innerHTML = '<p style="text-align:center; color:#ef4444; padding:2rem;">Erro de ligaÃ§Ã£o ao motor de dados.</p>'; });
    };

    window.renderProfileEditStep = function(step) {
        const d = window.currentEditProfileData;
        const contentZone = document.getElementById('profileEditContent');
        const footerZone = document.getElementById('profileEditFooter');
        if(!contentZone || !footerZone) return;
        const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
        const asset = (path) => String(path || '').match(/^https?:\/\//) ? path : BASE_URL + String(path || '');

        if (step === 0) {
            const verificationMap = {
                verified: ['#10b981', 'rgba(16,185,129,0.1)', 'Identidade Verificada', 'fa-check-circle'],
                approved: ['#10b981', 'rgba(16,185,129,0.1)', 'Identidade Verificada', 'fa-check-circle'],
                pending: ['#f59e0b', 'rgba(245,158,11,0.1)', 'Em Analise', 'fa-clock'],
                rejected: ['#ef4444', 'rgba(239,68,68,0.1)', 'Rejeitado', 'fa-times-circle'],
                unsubmitted: ['#94a3b8', 'rgba(148,163,184,0.1)', 'Nao Verificado', 'fa-shield-alt']
            };
            const mentorMap = {
                approved: ['#f5b105', 'rgba(245,177,5,0.1)', 'Mentor Oficial KALIYE', 'fa-award'],
                pending: ['#f59e0b', 'rgba(245,158,11,0.1)', 'Candidatura em Analise', 'fa-hourglass-half'],
                rejected: ['#ef4444', 'rgba(239,68,68,0.1)', 'Requer Ajustes', 'fa-exclamation-triangle'],
                unsubmitted: ['#94a3b8', 'rgba(148,163,184,0.1)', 'Candidatura Disponivel', 'fa-user-graduate']
            };
            const v = verificationMap[d.verification_status] || (d.is_verified ? verificationMap.verified : verificationMap.unsubmitted);
            const m = mentorMap[d.mentorship_status] || mentorMap.unsubmitted;
            const memberYear = d.member_since ? new Date(d.member_since).getFullYear() : new Date().getFullYear();
            const bio = d.bio ? esc(d.bio) : 'Este utilizador ainda nao adicionou uma biografia a sua jornada na KALIYE.';
            const focusFallback = d.focus_areas ? String(d.focus_areas).split(',').map(s => s.trim()).filter(Boolean).map(s => ({ title: s, area_name: s, description: d.experience_summary || 'Area adicionada ao dossier.' })) : [];
            const displayExpertises = (d.expertises || []).length ? (d.expertises || []) : focusFallback;
            const expertisesHtml = displayExpertises.length ? displayExpertises.map(exp => `
                <div style="background:rgba(255,255,255,0.025); border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:0.9rem;">
                    <strong style="display:block; color:#fff; font-size:0.78rem; margin-bottom:0.3rem;">${esc(exp.title || exp.area_name || 'Area de foco')}</strong>
                    <span style="display:block; color:rgba(255,255,255,0.42); font-size:0.68rem; line-height:1.4;">${esc(exp.description || exp.area_name || 'Especialidade adicionada ao dossier.')}</span>
                </div>
            `).join('') : '<div style="color:rgba(255,255,255,0.28); font-size:0.75rem; font-style:italic; padding:0.5rem 0;">Nenhuma area de especialidade adicionada.</div>';
            const skillsHtml = (d.skills_list || []).length ? (d.skills_list || []).map(s => `<span style="background:rgba(247,148,29,0.08); color:#f7941d; padding:5px 12px; border-radius:20px; font-size:0.65rem; font-weight:800; border:1px solid rgba(247,148,29,0.15);">${esc(s)}</span>`).join('') : '<span style="color:rgba(255,255,255,0.2); font-size:0.7rem;">Nenhuma skill listada.</span>';
            contentZone.innerHTML = `
                <div class="user-info-box" style="text-align:center; border:none; background:none; margin-bottom: 1.5rem;">
                    <h2 class="user-card-name" style="font-size: 1.5rem;">Dossier de Elite</h2>
                    <p style="color:rgba(255,255,255,0.4); font-size:0.8rem;">Resumo da sua presenÃ§a no ecossistema KALIYE.</p>
                </div>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 2rem;">
                    <div style="background:rgba(255,255,255,0.03); padding: 0.8rem; border-radius: 16px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="color:#f7941d; font-weight: 950; font-size: 1.2rem;">${d.stats?.connections || 0}</div>
                        <div style="font-size: 0.55rem; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight:700;">ConexÃµes</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); padding: 0.8rem; border-radius: 16px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="color:#f7941d; font-weight: 950; font-size: 1.2rem;">${d.stats?.projects || 0}</div>
                        <div style="font-size: 0.55rem; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight:700;">Projectos</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); padding: 0.8rem; border-radius: 16px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="color:#f7941d; font-weight: 950; font-size: 1.2rem;">${d.stats?.skills || 0}</div>
                        <div style="font-size: 0.55rem; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight:700;">Skills</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); padding: 0.8rem; border-radius: 16px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="color:#f7941d; font-weight: 950; font-size: 1.2rem;">${d.stats?.rating || '0.00'}</div>
                        <div style="font-size: 0.55rem; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight:700;">Rating</div>
                    </div>
                </div>
                <div class="trust-shield-card" style="margin-top: 0; background:rgba(255,255,255,0.02);">
                    <h4 style="margin-bottom: 0.5rem; font-size:0.75rem;"><i class="fas fa-shield-alt"></i> Hub de ConfianÃ§a</h4>
                    <div class="shield-actions-grid" style="grid-template-columns: 1fr 1fr; margin-top: 1rem; gap:10px;">
                        <button type="button" onclick="openKYCModal()" class="btn-shield ${d.is_verified ? 'verified' : ''}" style="width: 100%; font-size:0.65rem; height:40px;">
                            <i class="fas ${d.is_verified ? 'fa-check-double' : 'fa-id-card'}"></i>
                            ${d.is_verified ? 'Verificado' : 'Verificar Agora'}
                        </button>
                        <button type="button" onclick="openMentorAppModal()" class="btn-shield" style="width: 100%; font-size:0.65rem; height:40px;">
                            <i class="fas fa-user-graduate"></i> Ser Mentor
                        </button>
                    </div>
                </div>
                <div style="margin-top: 1.5rem;">
                    <h4 style="font-size: 0.6rem; color: rgba(255,255,255,0.3); text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 1px; font-weight:900;">Minhas Especialidades</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        ${(d.skills_list || []).map(s => `<span style="background:rgba(247,148,29,0.08); color:#f7941d; padding: 5px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; border: 1px solid rgba(247,148,29,0.15);">${s}</span>`).join('') || '<span style="color:rgba(255,255,255,0.2); font-size:0.7rem;">Nenhuma skill listada.</span>'}
                    </div>
                </div>
            `;
            contentZone.innerHTML = `
                <div class="user-info-box" style="text-align:center; border:none; background:none; margin-bottom: 1.5rem;">
                    <h2 class="user-card-name" style="font-size: 1.5rem;">Dossier de Elite</h2>
                    <p style="color:rgba(255,255,255,0.4); font-size:0.8rem;">Resumo da sua presenca no ecossistema KALIYE.</p>
                </div>
                <div style="display:flex; align-items:center; gap:1rem; padding:1rem; border:1px solid rgba(247,148,29,0.16); background:linear-gradient(135deg, rgba(247,148,29,0.1), rgba(255,255,255,0.025)); border-radius:18px; margin-bottom:1rem;">
                    <img src="${asset(d.avatar)}" alt="" style="width:66px; height:66px; border-radius:18px; object-fit:cover; border:2px solid rgba(247,148,29,0.75);">
                    <div style="min-width:0;">
                        <div style="color:#fff; font-weight:950; font-size:1rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${esc(d.name || 'Membro KALIYE')}</div>
                        <div style="color:rgba(255,255,255,0.46); font-size:0.72rem; margin-top:0.25rem;">${esc(d.level || 'Perfil em construcao')} ${d.location ? ' - ' + esc(d.location) : ''}</div>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:10px; margin-bottom:1.5rem;">
                    <div style="background:rgba(255,255,255,0.03); padding:0.8rem; border-radius:16px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
                        <div style="color:#f7941d; font-weight:950; font-size:1.2rem;">${d.stats?.connections || 0}</div>
                        <div style="font-size:0.55rem; color:rgba(255,255,255,0.4); text-transform:uppercase; font-weight:700;">Conexoes</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); padding:0.8rem; border-radius:16px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
                        <div style="color:#f7941d; font-weight:950; font-size:1.2rem;">${d.stats?.projects || 0}</div>
                        <div style="font-size:0.55rem; color:rgba(255,255,255,0.4); text-transform:uppercase; font-weight:700;">Projectos</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); padding:0.8rem; border-radius:16px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
                        <div style="color:#f7941d; font-weight:950; font-size:1.2rem;">${d.stats?.skills || 0}</div>
                        <div style="font-size:0.55rem; color:rgba(255,255,255,0.4); text-transform:uppercase; font-weight:700;">Skills</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); padding:0.8rem; border-radius:16px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
                        <div style="color:#f7941d; font-weight:950; font-size:1.2rem;">${d.stats?.rating || '0.00'}</div>
                        <div style="font-size:0.55rem; color:rgba(255,255,255,0.4); text-transform:uppercase; font-weight:700;">Rating</div>
                    </div>
                </div>
                <div class="profile-dossier-grid" style="display:grid; grid-template-columns:0.9fr 1.6fr; gap:1rem; align-items:start;">
                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        <div class="trust-shield-card" style="margin-top:0; background:rgba(255,255,255,0.02);">
                            <h4 style="margin-bottom:0.8rem; font-size:0.75rem;"><i class="fas fa-address-card"></i> Informacao</h4>
                            <div style="display:flex; align-items:center; gap:0.75rem; background:rgba(255,255,255,0.025); border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:0.8rem;">
                                <span style="width:34px; height:34px; border-radius:10px; background:rgba(247,148,29,0.1); color:#f7941d; display:flex; align-items:center; justify-content:center;"><i class="fas fa-envelope"></i></span>
                                <span style="color:rgba(255,255,255,0.75); font-size:0.75rem; word-break:break-all;">${esc(d.email || 'Email indisponivel')}</span>
                            </div>
                            ${d.phone ? `<div style="display:flex; align-items:center; gap:0.75rem; background:rgba(255,255,255,0.025); border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:0.8rem; margin-top:0.65rem;"><span style="width:34px; height:34px; border-radius:10px; background:rgba(247,148,29,0.1); color:#f7941d; display:flex; align-items:center; justify-content:center;"><i class="fas fa-phone"></i></span><span style="color:rgba(255,255,255,0.75); font-size:0.75rem;">${esc(d.phone)}</span></div>` : ''}
                            <div style="margin-top:0.8rem; color:rgba(255,255,255,0.45); font-size:0.7rem; line-height:1.6;">
                                <div><i class="fas fa-map-marker-alt" style="color:#f7941d; width:18px;"></i>${esc(d.location || 'Angola')}</div>
                                <div><i class="fas fa-graduation-cap" style="color:#f7941d; width:18px;"></i>${esc(d.level || 'Membro KALIYE')}</div>
                                ${d.institution ? `<div><i class="fas fa-university" style="color:#f7941d; width:18px;"></i>${esc(d.institution)}</div>` : ''}
                                ${d.organization ? `<div><i class="fas fa-briefcase" style="color:#f7941d; width:18px;"></i>${esc(d.organization)}</div>` : ''}
                                ${d.birth_date ? `<div><i class="fas fa-birthday-cake" style="color:#f7941d; width:18px;"></i>${esc(d.birth_date)}</div>` : ''}
                                <div><i class="fas fa-calendar-alt" style="color:#f7941d; width:18px;"></i>Membro desde ${memberYear}</div>
                            </div>
                        </div>
                        <div class="trust-shield-card" style="margin-top:0; background:rgba(255,255,255,0.02);">
                            <h4 style="margin-bottom:0.8rem; font-size:0.75rem;"><i class="fas fa-shield-alt"></i> Hub de Confianca</h4>
                            <div style="display:flex; align-items:center; gap:0.65rem; padding:0.8rem; border-radius:14px; color:${v[0]}; background:${v[1]}; font-size:0.75rem; font-weight:900;"><i class="fas ${v[3]}"></i> ${v[2]}</div>
                            <div style="display:flex; align-items:center; gap:0.65rem; padding:0.8rem; border-radius:14px; color:${m[0]}; background:${m[1]}; font-size:0.75rem; font-weight:900; margin-top:0.65rem;"><i class="fas ${m[3]}"></i> ${m[2]}</div>
                            <div class="shield-actions-grid" style="grid-template-columns:1fr 1fr; margin-top:1rem; gap:10px;">
                                <button type="button" onclick="openKYCModal()" class="btn-shield ${d.is_verified ? 'verified' : ''}" style="width:100%; font-size:0.65rem; height:40px;"><i class="fas ${d.is_verified ? 'fa-check-double' : 'fa-id-card'}"></i>${d.is_verified ? 'Verificado' : 'Verificar'}</button>
                                <button type="button" onclick="openMentorAppModal()" class="btn-shield" style="width:100%; font-size:0.65rem; height:40px;"><i class="fas fa-user-graduate"></i> Mentor</button>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        <div class="trust-shield-card" style="margin-top:0; background:rgba(255,255,255,0.02);">
                            <h4 style="margin-bottom:0.8rem; font-size:0.75rem;"><i class="fas fa-quote-left"></i> Sobre</h4>
                            <p style="color:rgba(255,255,255,0.75); line-height:1.65; font-size:0.82rem; margin:0; white-space:pre-wrap;">${bio}</p>
                        </div>
                        <div class="trust-shield-card" style="margin-top:0; background:rgba(255,255,255,0.02);">
                            <h4 style="margin-bottom:0.8rem; font-size:0.75rem;"><i class="fas fa-brain"></i> Areas de Foco</h4>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.7rem;">${expertisesHtml}</div>
                        </div>
                        <div class="trust-shield-card" style="margin-top:0; background:rgba(255,255,255,0.02);">
                            <h4 style="margin-bottom:0.8rem; font-size:0.75rem;"><i class="fas fa-code"></i> Skills & Competencias</h4>
                            <div style="display:flex; flex-wrap:wrap; gap:8px;">${skillsHtml}</div>
                        </div>
                    </div>
                </div>
            `;
            footerZone.innerHTML = `
                <button type="button" onclick="closeMyProfileEdit()" style="background:rgba(255,255,255,0.05); color:#fff; border:none; padding:12px 24px; border-radius:14px; font-weight:800; cursor:pointer;">FECHAR</button>
                <button type="button" onclick="renderProfileEditStep(1)" class="view-profile-full-btn" style="background:#f7941d; color:#000; font-weight:950;">EDITAR MEU DOSSIER</button>
            `;
            return;
        }

        const indicator = `
            <div class="step-indicator-user">
                <div class="step-dot-user ${step >= 1 ? 'active' : ''}"></div>
                <div class="step-dot-user ${step >= 2 ? 'active' : ''}"></div>
                <div class="step-dot-user ${step >= 3 ? 'active' : ''}"></div>
                <div class="step-dot-user ${step >= 4 ? 'active' : ''}"></div>
                <div class="step-dot-user ${step >= 5 ? 'active' : ''}"></div>
            </div>
        `;

        let stepHtml = '';
        let nextBtn = '';

        if (step === 1) {
            stepHtml = `
                ${indicator}
                <div class="user-info-box" style="text-align:center; border:none; background:none;">
                    <h2 class="user-card-name">Identidade & Contacto</h2>
                </div>
                <div class="profile-avatar-edit-wrapper">
                    <img src="${asset(d.avatar)}" id="avatarPreview" class="profile-avatar-preview">
                    <div style="flex:1;">
                        <div style="font-size:0.8rem; font-weight:900; color:#fff;">Avatar do perfil</div>
                        <p style="color:rgba(255,255,255,0.45); font-size:0.68rem; margin:4px 0 10px; line-height:1.45;">Carregue uma foto profissional. Sem upload, a KALIYE mantem o avatar premium da sua funcao.</p>
                        <label for="profileAvatarInput" class="upload-btn-profile"><i class="fas fa-camera"></i> Alterar avatar</label>
                        <input type="file" id="profileAvatarInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewAvatarChange(this)">
                    </div>
                </div>
                <div class="profile-edit-input-group">
                    <label class="profile-edit-label">Nome Completo *</label>
                    <input type="text" name="full_name" class="profile-edit-input" required value="${esc(d.name || '')}">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="profile-edit-input-group">
                        <label class="profile-edit-label">Email</label>
                        <input type="email" class="profile-edit-input" value="${esc(d.email || '')}" disabled>
                    </div>
                    <div class="profile-edit-input-group">
                        <label class="profile-edit-label">Telefone / WhatsApp</label>
                        <input type="text" name="phone" class="profile-edit-input" value="${esc(d.phone || '')}" placeholder="+244 9...">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="profile-edit-input-group">
                        <label class="profile-edit-label">Genero</label>
                        <select name="gender" class="profile-edit-input" style="height:48px;">
                            <option value="none" ${d.gender === 'none' ? 'selected' : ''}>Nao especificado</option>
                            <option value="masculino" ${d.gender === 'masculino' ? 'selected' : ''}>Masculino</option>
                            <option value="feminino" ${d.gender === 'feminino' ? 'selected' : ''}>Feminino</option>
                            <option value="outro" ${d.gender === 'outro' ? 'selected' : ''}>Outro</option>
                        </select>
                    </div>
                    <div class="profile-edit-input-group">
                        <label class="profile-edit-label">Data de nascimento</label>
                        <input type="date" name="birth_date" class="profile-edit-input" value="${esc(d.birth_date || '')}" style="color-scheme:dark;">
                    </div>
                </div>
            `;
            nextBtn = `<button type="button" onclick="if(saveCurrentStepData(1)) renderProfileEditStep(2)" class="view-profile-full-btn">PROFISSIONAL</button>`;
        } else if (step === 2) {
            stepHtml = `
                ${indicator}
                <div class="user-info-box" style="text-align:center; border:none; background:none;">
                    <h2 class="user-card-name">Dossier Profissional</h2>
                </div>
                <div class="profile-edit-input-group">
                    <label class="profile-edit-label">Nivel de Formacao / Cargo *</label>
                    <input type="text" name="level" class="profile-edit-input" required value="${esc(d.level || '')}" placeholder="Ex: Licenciado, Mentor de Negocios, Founder">
                </div>
                <div class="profile-edit-input-group">
                    <label class="profile-edit-label">Localizacao *</label>
                    <input type="text" name="location" class="profile-edit-input" required value="${esc(d.location || '')}" placeholder="Ex: Luanda, Huambo, Benguela">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="profile-edit-input-group">
                        <label class="profile-edit-label">Instituicao</label>
                        <input type="text" name="institution" class="profile-edit-input" value="${esc(d.institution || '')}" placeholder="Universidade, escola ou centro">
                    </div>
                    <div class="profile-edit-input-group">
                        <label class="profile-edit-label">Organizacao / Empresa</label>
                        <input type="text" name="organization" class="profile-edit-input" value="${esc(d.organization || '')}" placeholder="Onde trabalha ou empreende">
                    </div>
                </div>
            `;
            nextBtn = `<button type="button" onclick="if(saveCurrentStepData(2)) renderProfileEditStep(3)" class="view-profile-full-btn">SKILLS</button>`;
        } else if (step === 3) {
            stepHtml = `
                ${indicator}
                <div class="user-info-box" style="text-align:center; border:none; background:none;">
                    <h2 class="user-card-name">Areas, Skills & Experiencia</h2>
                </div>
                <div class="profile-edit-input-group">
                    <label class="profile-edit-label">Areas de foco *</label>
                    <input type="text" name="focus_areas" class="profile-edit-input" required value="${esc(d.focus_areas || '')}" placeholder="Ex: Inteligencia Artificial, Financas, Educacao">
                    <small class="profile-field-hint">Separe por virgula para criar varias areas.</small>
                </div>
                <div class="profile-edit-input-group">
                    <label class="profile-edit-label">Skills e competencias *</label>
                    <input type="text" name="skills" class="profile-edit-input" required value="${esc(d.skills_str || '')}" placeholder="Ex: Programacao, Pitch, Design, Analise financeira">
                    <small class="profile-field-hint">Estas tags aparecem no seu dossier publico.</small>
                </div>
                <div class="profile-edit-input-group">
                    <label class="profile-edit-label">Experiencias / Realizacoes</label>
                    <textarea name="experience_summary" class="profile-edit-input" style="min-height:130px;" placeholder="Projectos, cargos, premios, mentorias, voluntariado ou resultados relevantes.">${esc(d.experience_summary || '')}</textarea>
                </div>
            `;
            nextBtn = `<button type="button" onclick="if(saveCurrentStepData(3)) renderProfileEditStep(4)" class="view-profile-full-btn">BIOGRAFIA</button>`;
        } else if (step === 4) {
            stepHtml = `
                ${indicator}
                <div class="user-info-box" style="text-align:center; border:none; background:none;">
                    <h2 class="user-card-name">Biografia</h2>
                </div>
                <div class="profile-edit-input-group">
                    <label class="profile-edit-label">Biografia / Pitch Pessoal *</label>
                    <textarea name="bio" class="profile-edit-input" required minlength="40" style="min-height:190px;" placeholder="Conte quem e, o que faz, o que procura na KALIYE e que impacto quer criar.">${esc(d.bio || '')}</textarea>
                    <small class="profile-field-hint">Minimo de 40 caracteres para tornar o perfil mais confiavel.</small>
                </div>
            `;
            nextBtn = `<button type="button" onclick="if(saveCurrentStepData(4)) renderProfileEditStep(5)" class="view-profile-full-btn">LINKS</button>`;
        } else if (step === 5) {
            stepHtml = `
                ${indicator}
                <div class="user-info-box" style="text-align:center; border:none; background:none;">
                    <h2 class="user-card-name">Links & Canais</h2>
                </div>
                <div class="profile-edit-input-group">
                    <label class="profile-edit-label">LinkedIn (URL)</label>
                    <input type="url" name="linkedin" class="profile-edit-input" value="${esc(d.linkedin || '')}" placeholder="https://www.linkedin.com/in/seu-perfil">
                </div>
                <div class="profile-edit-input-group">
                    <label class="profile-edit-label">Instagram / Website (URL)</label>
                    <input type="url" name="instagram" class="profile-edit-input" value="${esc(d.instagram || '')}" placeholder="https://...">
                </div>
            `;
            nextBtn = `<button type="button" onclick="if(saveCurrentStepData(5)) submitProfileEdit()" class="view-profile-full-btn" style="background:#10b981;">SALVAR TUDO</button>`;
        }

        contentZone.innerHTML = stepHtml;
        footerZone.innerHTML = `
            <button type="button" onclick="${step === 1 ? 'renderProfileEditStep(0)' : 'renderProfileEditStep('+(step-1)+')'}" 
                    style="background:rgba(255,255,255,0.05); color:#fff; border:none; padding:12px 24px; border-radius:14px; font-weight:800; cursor:pointer;">
                VOLTAR
            </button>
            ${nextBtn}
        `;
    };

    window.saveCurrentStepData = function(step) {
        const form = document.querySelector('#profileEditContent');
        if (!form) return false;

        const d = window.currentEditProfileData;
        const required = Array.from(form.querySelectorAll('[required]'));
        for (const field of required) {
            if (!String(field.value || '').trim()) {
                field.focus();
                Swal.fire({ title: 'Campo obrigatorio', text: 'Preencha os campos marcados com * antes de continuar.', icon: 'warning', background: '#0d1628', color: '#fff' });
                return false;
            }
        }

        if (step === 1) {
            d.name = form.querySelector('[name="full_name"]').value.trim();
            d.gender = form.querySelector('[name="gender"]').value;
            d.phone = form.querySelector('[name="phone"]').value.trim();
            d.birth_date = form.querySelector('[name="birth_date"]').value;
        } else if (step === 2) {
            d.level = form.querySelector('[name="level"]').value.trim();
            d.location = form.querySelector('[name="location"]').value.trim();
            d.institution = form.querySelector('[name="institution"]').value.trim();
            d.organization = form.querySelector('[name="organization"]').value.trim();
        } else if (step === 3) {
            d.focus_areas = form.querySelector('[name="focus_areas"]').value.trim();
            d.skills_str = form.querySelector('[name="skills"]').value;
            d.experience_summary = form.querySelector('[name="experience_summary"]').value.trim();
        } else if (step === 4) {
            d.bio = form.querySelector('[name="bio"]').value.trim();
            if (d.bio.length < 40) {
                form.querySelector('[name="bio"]').focus();
                Swal.fire({ title: 'Biografia curta', text: 'Escreva pelo menos 40 caracteres para completar o dossier.', icon: 'warning', background: '#0d1628', color: '#fff' });
                return false;
            }
        } else if (step === 5) {
            d.linkedin = form.querySelector('[name="linkedin"]').value;
            d.instagram = form.querySelector('[name="instagram"]').value;
        }
        return true;
    };

    window.previewAvatarChange = function(input) {
        if (input.files && input.files[0]) {
            if (input.files[0].size > 4 * 1024 * 1024) {
                Swal.fire({ title: 'Avatar muito pesado', text: 'Escolha uma imagem com no maximo 4MB.', icon: 'warning', background: '#0d1628', color: '#fff' });
                input.value = '';
                return;
            }
            if (window.currentEditProfileData) {
                window.currentEditProfileData.avatarFile = input.files[0];
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('avatarPreview');
                if(preview) preview.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    };

    window.refreshVisibleProfileData = function(d) {
        if (!d) return;
        const avatarUrl = d.avatar ? (/^https?:\/\//.test(String(d.avatar)) ? d.avatar : BASE_URL + d.avatar) : '';
        if (avatarUrl) {
            document.querySelectorAll('.btn-profile img, img[alt="O teu avatar"], .cover-avatar, .sticky-avatar, #avatarPreview').forEach(img => {
                img.src = avatarUrl;
            });
        }
        document.querySelectorAll('.sticky-name').forEach(el => { el.textContent = d.name || el.textContent; });
        document.querySelectorAll('.cover-name').forEach(el => {
            const icon = el.querySelector('i');
            el.childNodes.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) node.textContent = '';
            });
            el.insertBefore(document.createTextNode(d.name || ''), icon || null);
            if (icon) el.appendChild(icon);
        });
    };

    window.submitProfileEdit = function() {
        const d = window.currentEditProfileData;
        const fd = new FormData();
        fd.append('full_name', d.name);
        fd.append('gender', d.gender);
        fd.append('phone', d.phone || '');
        fd.append('birth_date', d.birth_date || '');
        fd.append('level', d.level);
        fd.append('location', d.location);
        fd.append('institution', d.institution || '');
        fd.append('organization', d.organization || '');
        fd.append('focus_areas', d.focus_areas || '');
        fd.append('skills', d.skills_str || '');
        fd.append('experience_summary', d.experience_summary || '');
        fd.append('bio', d.bio);
        fd.append('linkedin', d.linkedin);
        fd.append('instagram', d.instagram);

        if (d.avatarFile) fd.append('profile_pic', d.avatarFile);

        const content = document.getElementById('profileEditContent');
        content.innerHTML = '<div style="padding: 4rem; text-align: center; color: #f7941d;"><i class="fas fa-spinner fa-spin fa-3x"></i><p style="margin-top:1rem; font-weight:800;">SINCRONIZANDO DOSSIER...</p></div>';

        fetch(`${BASE_URL}interface_programacao/user/update_profile.php`, {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fetch(`${BASE_URL}interface_programacao/user/get_my_profile.php`)
                    .then(res => res.json())
                    .then(profile => {
                        if (profile.success) {
                            window.currentEditProfileData = profile.data;
                            refreshVisibleProfileData(profile.data);
                            renderProfileEditStep(0);
                        }
                        Swal.fire({ title: 'Sucesso!', text: data.message, icon: 'success', timer: 1800, showConfirmButton: false, background: '#0d1628', color: '#fff' });
                    })
                    .catch(() => {
                        renderProfileEditStep(0);
                        Swal.fire({ title: 'Sucesso!', text: data.message, icon: 'success', timer: 1800, showConfirmButton: false, background: '#0d1628', color: '#fff' });
                    });
            } else {
                Swal.fire({ title: 'Erro', text: data.message, icon: 'error', background: '#0d1628', color: '#fff' });
                renderProfileEditStep(5);
            }
        })
        .catch(() => {
            Swal.fire({ title: 'Erro de LigaÃ§Ã£o', text: 'NÃ£o foi possÃ­vel comunicar com o motor KALIYE.', icon: 'error', background: '#0d1628', color: '#fff' });
            renderProfileEditStep(5);
        });
    };
</script>

