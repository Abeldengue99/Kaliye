/**
 * aksanti_modals_v2.js — Sistema de Modais Aksanti (Standalone)
 * 
 * Substitui COMPLETAMENTE as funções:
 *   - openProjectDetails(id, startStep)
 *   - toggleLike(btn, projectId)
 *   - openUserCard(userId)
 * 
 * Cria os seus próprios elementos DOM com inline styles.
 * Zero dependências de CSS externo.
 */
(function () {
    'use strict';

    console.log('%c[AKSANTI-V2] Modal System v2 LOADED', 'background:#f7941d;color:#fff;font-size:14px;font-weight:bold;padding:4px 12px;');

    var BASE = window.BASE_URL || './';

    // ═══════════════════════════════════════════════════════
    // UTILITÁRIO: Criar overlay de modal genérico
    // ═══════════════════════════════════════════════════════
    function createOverlay(id) {
        // Remove qualquer overlay anterior com este ID
        var old = document.getElementById(id);
        if (old) old.remove();

        var overlay = document.createElement('div');
        overlay.id = id;
        overlay.style.cssText = [
            'position:fixed',
            'top:0', 'left:0', 'width:100%', 'height:100%',
            'background:rgba(2,6,23,0.92)',
            'backdrop-filter:blur(18px)',
            '-webkit-backdrop-filter:blur(18px)',
            'z-index:999999',
            'display:flex',
            'align-items:center',
            'justify-content:center',
            'padding:1rem',
            'opacity:0',
            'transition:opacity 0.3s ease'
        ].join(';');

        var card = document.createElement('div');
        card.className = '_v2-card';
        card.style.cssText = [
            'background:#0d1628',
            'border:1px solid rgba(255,255,255,0.08)',
            'border-radius:28px',
            'width:100%',
            'max-width:860px',
            'max-height:90vh',
            'overflow-y:auto',
            'position:relative',
            'box-shadow:0 40px 100px rgba(0,0,0,0.7)',
            'transform:translateY(30px) scale(0.95)',
            'transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease',
            'opacity:0'
        ].join(';');

        // Botão de fechar
        var closeBtn = document.createElement('button');
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.style.cssText = [
            'position:absolute', 'top:16px', 'right:16px',
            'width:40px', 'height:40px', 'border-radius:50%',
            'background:rgba(255,255,255,0.08)',
            'border:1px solid rgba(255,255,255,0.1)',
            'color:#fff', 'cursor:pointer', 'z-index:10',
            'display:flex', 'align-items:center', 'justify-content:center',
            'font-size:1rem', 'transition:0.3s'
        ].join(';');
        closeBtn.onmouseenter = function () { this.style.background = '#ef4444'; this.style.transform = 'rotate(90deg)'; };
        closeBtn.onmouseleave = function () { this.style.background = 'rgba(255,255,255,0.08)'; this.style.transform = 'rotate(0)'; };
        closeBtn.onclick = function () { closeOverlay(id); };

        // Conteúdo
        var content = document.createElement('div');
        content.className = '_v2-content';
        content.style.cssText = 'padding:2.5rem;';

        card.appendChild(closeBtn);
        card.appendChild(content);
        overlay.appendChild(card);
        document.body.appendChild(overlay);

        // --- DRM / Proteção Anti-Cópia ---
        card.style.userSelect = 'none';
        card.style.webkitUserSelect = 'none';
        card.addEventListener('contextmenu', function(e) { e.preventDefault(); });
        card.addEventListener('copy', function(e) { e.preventDefault(); });
        card.addEventListener('cut', function(e) { e.preventDefault(); });

        // Fechar ao clicar no fundo
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeOverlay(id);
        });

        // Animar entrada
        requestAnimationFrame(function () {
            overlay.style.opacity = '1';
            card.style.transform = 'translateY(0) scale(1)';
            card.style.opacity = '1';
        });

        document.body.style.overflow = 'hidden';

        return content;
    }

    function closeOverlay(id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        var card = overlay.querySelector('._v2-card');
        overlay.style.opacity = '0';
        if (card) {
            card.style.transform = 'translateY(30px) scale(0.95)';
            card.style.opacity = '0';
        }
        setTimeout(function () {
            if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            document.body.style.overflow = '';
        }, 350);
    }

    function spinner() {
        return '<div style="padding:4rem;text-align:center;color:rgba(255,255,255,0.2);">' +
            '<i class="fas fa-spinner fa-spin" style="font-size:2rem;"></i>' +
            '<p style="margin-top:1rem;">A carregar...</p></div>';
    }

    // ═══════════════════════════════════════════════════════
    // 1. openProjectDetails — Modal de Dossier do Projecto
    // ═══════════════════════════════════════════════════════
    window.openProjectDetails = function (id, startStep) {
        if (typeof startStep === 'undefined') startStep = 1;
        console.log('[AKSANTI-V2] openProjectDetails', id, startStep);

        var content = createOverlay('_v2_detailsModal');
        content.innerHTML = spinner();

        fetch(BASE + 'interface_programacao/projects/get_project_details.php?id=' + id)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    content.innerHTML = '<p style="text-align:center;color:#ef4444;padding:2rem;">' + (data.message || 'Erro ao carregar projecto.') + '</p>';
                    return;
                }

                if (data.access_level === 'nda_required') {
                    content.innerHTML = '';
                    closeOverlay('_v2_detailsModal');
                    
                    Swal.fire({
                        title: 'Acordo de Confidencialidade (NDA)',
                        html: `
                            <div style="text-align:left; font-size:0.9rem; line-height:1.6; color:rgba(255,255,255,0.8);">
                                <p>Para aceder ao dossier completo deste projecto, tem de concordar em manter o total sigilo sobre as informações, tecnologias e modelos de negócio aqui descritos.</p>
                                <p>O seu nome, e-mail e endereço IP serão registados para proteção legal da Propriedade Intelectual do autor.</p>
                                <p style="color:#facc15; font-weight:bold; margin-top:10px;"><i class="fas fa-shield-alt"></i> Concorda em manter esta ideia confidencial?</p>
                            </div>
                        `,
                        icon: 'info',
                        background: '#0d1628',
                        color: '#fff',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-file-signature"></i> Aceito o Termo de Sigilo',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#10b981',
                        cancelButtonColor: 'rgba(255,255,255,0.1)'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const fd = new FormData();
                            fd.append('project_id', id);
                            fetch(BASE + 'interface_programacao/projects/accept_project_nda.php', { method: 'POST', body: fd })
                                .then(r => r.json())
                                .then(res => {
                                    if (res.success) {
                                        Swal.fire({ icon: 'success', title: 'Acesso Concedido', text: 'O dossier foi desbloqueado.', background: '#0d1628', color: '#fff', timer: 1500, showConfirmButton: false })
                                            .then(() => openProjectDetails(id, startStep));
                                    } else {
                                        Swal.fire({ icon: 'error', title: 'Erro', text: res.message, background: '#0d1628', color: '#fff' });
                                    }
                                });
                        }
                    });
                    return;
                }

                var p = data.project;
                window._v2ProjectData = p;
                renderProjectStep(content, p, startStep);

                // --- Marca de Água (Watermark) ---
                if (data.viewer_name) {
                    var wm = document.createElement('div');
                    wm.style.cssText = [
                        'position:absolute', 'top:0', 'left:0', 'width:100%', 'height:100%',
                        'pointer-events:none', 'z-index:9999', 'overflow:hidden',
                        'display:flex', 'flex-wrap:wrap', 'align-content:space-around', 'justify-content:space-around',
                        'opacity:0.04', 'transform:rotate(-30deg) scale(1.5)'
                    ].join(';');
                    for(var i=0; i<30; i++) {
                        var span = document.createElement('span');
                        span.textContent = data.viewer_name + ' - Protegido por KALIYE';
                        span.style.cssText = 'font-size:1.5rem; font-weight:bold; color:#fff; white-space:nowrap; padding:2rem;';
                        wm.appendChild(span);
                    }
                    content.parentNode.appendChild(wm);
                }
            })
            .catch(function (err) {
                console.error('[AKSANTI-V2] Fetch error:', err);
                content.innerHTML = '<p style="text-align:center;color:#ef4444;padding:2rem;">Erro de ligação ao servidor.</p>';
            });
    };

    function escapeHtml(value) {
        return String(value === null || typeof value === 'undefined' ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function hasProjectValue(value) {
        return !(value === null || typeof value === 'undefined' || value === '');
    }

    function prettyProjectLabel(key) {
        var labels = {
            project_id: 'ID do projecto',
            owner_id: 'ID do proprietário',
            title: 'Título',
            description: 'Descrição',
            category: 'Categoria',
            budget_needed: 'Valor total necessário',
            image_url: 'Imagem de capa',
            video_url: 'Vídeo',
            pitch_video_url: 'Pitch em vídeo',
            execution_time: 'Tempo de execução',
            team_size: 'Tamanho da equipa',
            project_stage: 'Estágio do projecto',
            target_audience: 'Público-alvo',
            needs_to_advance: 'O que falta para avançar',
            idea_origin: 'Origem do projecto',
            motivation: 'Motivação',
            project_url: 'Website / URL',
            funding_goal: 'Meta de financiamento',
            minimum_investment: 'Investimento mínimo',
            maximum_investment: 'Investimento máximo',
            campaign_start_date: 'Data início da campanha',
            campaign_end_date: 'Data fim da campanha',
            funding_type: 'Tipo de financiamento',
            equity_available: 'Equity disponível',
            equity_committed: 'Equity comprometido',
            total_invested: 'Total investido',
            total_investors: 'Total de investidores',
            approval_status: 'Estado de aprovação',
            approved_by: 'Aprovado por',
            approved_at: 'Aprovado em',
            is_public: 'Projecto público',
            is_featured: 'Em destaque',
            created_at: 'Criado em',
            updated_at: 'Actualizado em',
            market_score: 'Pontuação de mercado',
            ai_status: 'Estado IA',
            status: 'Estado'
        };
        return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    function formatProjectValue(key, value) {
        if (!hasProjectValue(value)) return '<span style="color:rgba(255,255,255,0.32);">Não informado</span>';
        if (typeof value === 'boolean') return value ? 'Sim' : 'Não';
        if (value === 0 || value === '0') return '0';

        var numericMoney = ['budget_needed', 'funding_goal', 'minimum_investment', 'maximum_investment', 'total_invested', 'escrow_balance'];
        if (numericMoney.indexOf(key) !== -1 && !isNaN(parseFloat(value))) {
            return new Intl.NumberFormat('pt-AO').format(parseFloat(value)) + ' Kz';
        }

        var percentages = ['equity_available', 'equity_committed', 'expected_return_rate'];
        if (percentages.indexOf(key) !== -1 && !isNaN(parseFloat(value))) {
            return parseFloat(value).toLocaleString('pt-AO') + '%';
        }

        if ((key.indexOf('_date') !== -1 || key.indexOf('_at') !== -1) && String(value).length >= 10) {
            var parsed = new Date(value);
            if (!isNaN(parsed.getTime())) return parsed.toLocaleString('pt-PT');
        }

        if (key.indexOf('url') !== -1 && typeof value === 'string') {
            var safeUrl = escapeHtml(value);
            return '<a href="' + safeUrl + '" target="_blank" rel="noopener" style="color:#60a5fa;text-decoration:none;font-weight:800;">' + safeUrl + '</a>';
        }

        if ((key === 'video_url' || key === 'pitch_video_url') && typeof value === 'string') {
            var safeVideo = value.indexOf('http') === 0 ? value : BASE + 'carregamentos/projects/' + value;
            return '<a href="' + escapeHtml(safeVideo) + '" target="_blank" rel="noopener" style="color:#60a5fa;text-decoration:none;font-weight:800;">' + escapeHtml(value) + '</a>';
        }

        if (key === 'image_url' && typeof value === 'string') {
            var safeImage = value.indexOf('http') === 0 || value.indexOf('/') === 0 ? value : BASE + value;
            return '<a href="' + escapeHtml(safeImage) + '" target="_blank" rel="noopener" style="color:#60a5fa;text-decoration:none;font-weight:800;">' + escapeHtml(value) + '</a>';
        }

        return escapeHtml(value);
    }

    function renderCompleteProjectFields(p, box, lbl) {
        var fields = p.project_fields || p;
        var skip = {
            full_name: true,
            profile_pic: true,
            owner_type: true,
            mentorship_status: true,
            verification_status: true,
            is_verified: true,
            project_fields: true,
            media: true,
            tags: true
        };
        var preferred = [
            'project_id', 'owner_id', 'title', 'description', 'category', 'project_stage',
            'budget_needed', 'funding_goal', 'minimum_investment', 'maximum_investment',
            'funding_type', 'equity_available', 'equity_committed', 'total_invested', 'total_investors',
            'campaign_start_date', 'campaign_end_date', 'execution_time', 'team_size',
            'target_audience', 'needs_to_advance', 'idea_origin', 'motivation', 'project_url',
            'image_url', 'video_url', 'pitch_video_url', 'approval_status', 'approved_by', 'approved_at',
            'is_public', 'is_featured', 'market_score', 'ai_status', 'status', 'created_at', 'updated_at'
        ];
        var keys = preferred.filter(function (key) { return Object.prototype.hasOwnProperty.call(fields, key); });
        Object.keys(fields).forEach(function (key) {
            if (keys.indexOf(key) === -1 && !skip[key]) keys.push(key);
        });

        var html = '<div style="' + box + 'border-left:4px solid #10b981;margin-bottom:1.2rem;">' +
            '<div style="' + lbl + '">Regra desta tela</div>' +
            '<div style="color:rgba(255,255,255,0.75);line-height:1.6;font-size:0.9rem;">Todos os campos do projecto retornados pela base de dados aparecem abaixo. Campos vazios ficam marcados como não informado.</div>' +
            '</div>';

        html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">';
        keys.forEach(function (key) {
            if (skip[key]) return;
            var raw = fields[key];
            var isLong = String(raw || '').length > 120 || key === 'description' || key === 'motivation' || key === 'needs_to_advance' || key === 'idea_origin';
            html += '<div style="' + box + (isLong ? 'grid-column:1/-1;' : '') + '">' +
                '<div style="' + lbl + '">' + escapeHtml(prettyProjectLabel(key)) + '</div>' +
                '<div style="color:rgba(255,255,255,0.82);font-size:0.9rem;line-height:1.55;word-break:break-word;">' + formatProjectValue(key, raw) + '</div>' +
                '</div>';
        });
        html += '</div>';

        if ((p.tags || []).length > 0) {
            html += '<div style="' + box + 'margin-top:12px;"><div style="' + lbl + '">Tags / Stack tecnológica</div><div style="display:flex;flex-wrap:wrap;gap:6px;">';
            (p.tags || []).forEach(function (tag) {
                html += '<span style="background:rgba(247,148,29,0.1);color:#f7941d;padding:5px 10px;border-radius:8px;font-size:0.65rem;font-weight:800;text-transform:uppercase;">' + escapeHtml(tag) + '</span>';
            });
            html += '</div></div>';
        }

        return html;
    }

    function renderProjectStep(content, p, step) {
        var videoUrl = p.pitch_video_url || p.video_url || '';
        var media = p.media || [];
        var tags = p.tags || [];

        var lbl = 'display:block;font-size:0.6rem;font-weight:950;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;';
        var box = 'background:rgba(255,255,255,0.03);padding:1.2rem;border-radius:16px;border:1px solid rgba(255,255,255,0.06);margin-bottom:1rem;';

        var ownerPic = (p.profile_pic && p.profile_pic !== 'default_profile.png')
            ? (p.profile_pic.startsWith('http') ? p.profile_pic : (p.profile_pic.startsWith('carregamentos/') ? BASE + p.profile_pic : BASE + 'carregamentos/profiles/' + p.profile_pic))
            : BASE + 'recursos/images/default_profile.png';

        var html = '';
        var title = '';
        var prevStep = null;
        var nextStep = null;

        if (step === 0) {
            title = '<i class="fas fa-film" style="color:#f7941d;margin-right:8px;"></i>Pitch Cinema';
            var fullVideo = videoUrl ? (videoUrl.indexOf('http') === 0 ? videoUrl : BASE + 'carregamentos/projects/' + videoUrl) : '';
            if (fullVideo) {
                html = '<div style="background:#000;border-radius:20px;overflow:hidden;margin-bottom:1.5rem;">' +
                    '<video src="' + fullVideo + '" controls style="width:100%;max-height:400px;object-fit:contain;"></video></div>';
            } else {
                html = '<div style="' + box + 'text-align:center;padding:4rem;"><i class="fas fa-video-slash" style="font-size:2rem;color:rgba(255,255,255,0.1);"></i><p style="color:rgba(255,255,255,0.2);margin-top:1rem;">Sem vídeo de pitch</p></div>';
            }
            nextStep = 1;
            prevStep = -1; // close
        } else if (step === 1) {
            title = '<i class="fas fa-lightbulb" style="color:#f7941d;margin-right:8px;"></i>Visão do Projecto';
            html += '<div style="display:flex;align-items:center;gap:14px;margin-bottom:1.5rem;">' +
                '<img src="' + ownerPic + '" style="width:48px;height:48px;border-radius:14px;object-fit:cover;border:2px solid #f7941d;">' +
                '<div><div style="color:#fff;font-weight:800;font-size:1rem;">' + (p.full_name || 'Autor') + '</div>' +
                '<div style="font-size:0.7rem;color:rgba(255,255,255,0.4);font-weight:700;">' + (p.owner_type || '').toUpperCase() + '</div></div></div>';
            html += '<h2 style="color:#fff;font-size:1.4rem;font-weight:900;margin-bottom:1rem;line-height:1.3;">' + (p.title || '') + '</h2>';
            if (p.description) {
                html += '<p style="color:rgba(255,255,255,0.7);line-height:1.7;font-size:0.95rem;margin-bottom:1.5rem;">' + p.description + '</p>';
            }
            if (tags.length > 0) {
                html += '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:1rem;">';
                tags.forEach(function (t) {
                    html += '<span style="background:rgba(247,148,29,0.1);color:#f7941d;padding:5px 10px;border-radius:8px;font-size:0.65rem;font-weight:800;text-transform:uppercase;">' + t + '</span>';
                });
                html += '</div>';
            }
            prevStep = videoUrl ? 0 : -1;
            nextStep = 2;
        } else if (step === 2) {
            title = '<i class="fas fa-cogs" style="color:#f7941d;margin-right:8px;"></i>Execução & Estratégia';
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:1.5rem;">';
            html += '<div style="' + box + '"><div style="' + lbl + '">Equipa</div><div style="color:#fff;font-weight:900;font-size:1.1rem;">' + (p.team_size || '1') + ' pessoa(s)</div></div>';
            html += '<div style="' + box + '"><div style="' + lbl + '">Estágio</div><div style="color:#fff;font-weight:900;font-size:1.1rem;">' + (p.project_stage || 'Projecto') + '</div></div>';
            html += '</div>';
            if (p.execution_time) {
                html += '<div style="' + box + '"><div style="' + lbl + '">Tempo de Execução Previsto</div><div style="color:#fff;font-weight:700;">' + p.execution_time + '</div></div>';
            }
            if (p.target_audience) {
                html += '<div style="' + box + '"><div style="' + lbl + '">Público Alvo</div><div style="color:rgba(255,255,255,0.7);font-size:0.9rem;line-height:1.6;">' + p.target_audience + '</div></div>';
            }
            if (p.idea_origin) {
                html += '<div style="' + box + '"><div style="' + lbl + '">Origem do projecto</div><div style="color:rgba(255,255,255,0.7);font-size:0.9rem;line-height:1.6;">' + p.idea_origin + '</div></div>';
            }
            if (p.motivation) {
                html += '<div style="' + box + '"><div style="' + lbl + '">Motivação / Propósito</div><div style="color:rgba(255,255,255,0.7);font-size:0.9rem;line-height:1.6;">' + p.motivation + '</div></div>';
            }
            if (p.needs_to_advance) {
                html += '<div style="' + box + 'border-left:4px solid #3b82f6;"><div style="' + lbl + '">O Que Falta Para Avançar?</div><div style="color:rgba(255,255,255,0.7);font-size:0.9rem;line-height:1.6;">' + p.needs_to_advance + '</div></div>';
            }
            prevStep = 1;
            nextStep = 3;
        } else if (step === 3) {
            title = '<i class="fas fa-chart-line" style="color:#f7941d;margin-right:8px;"></i>Financeiro & Media';
            if (p.budget_needed || p.funding_goal) {
                var goal = p.funding_goal || p.budget_needed || 0;
                html += '<div style="' + box + 'border-left:4px solid #f7941d;">' +
                    '<div style="' + lbl + '">Meta de Investimento</div>' +
                    '<div style="color:#fff;font-size:1.8rem;font-weight:950;">' + new Intl.NumberFormat('pt-AO').format(goal) + ' <small style="font-size:0.7rem;opacity:0.5;">AKZ</small></div></div>';
            }
            if (p.minimum_investment > 0) {
                html += '<div style="' + box + '"><div style="' + lbl + '">Investimento Mínimo Aceite</div><div style="color:#fff;font-weight:900;font-size:1.1rem;">' + new Intl.NumberFormat('pt-AO').format(p.minimum_investment) + ' Kz</div></div>';
            }
            if (p.equity_available) {
                html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:1rem;">';
                html += '<div style="' + box + '"><div style="' + lbl + '">Capital Oferecido (Equity)</div><div style="color:#f7941d;font-weight:900;font-size:1.1rem;">' + p.equity_available + '%</div></div>';
                html += '<div style="' + box + '"><div style="' + lbl + '">Capital Comprometido</div><div style="color:#ef4444;font-weight:900;font-size:1.1rem;">' + (p.equity_committed || '0') + '%</div></div>';
                html += '</div>';
            }
            if (p.total_invested > 0) {
                html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:1rem;">';
                html += '<div style="' + box + '"><div style="' + lbl + '">Total Investido (Angariado)</div><div style="color:#10b981;font-weight:900;font-size:1.1rem;">' + new Intl.NumberFormat('pt-AO').format(p.total_invested) + ' Kz</div></div>';
                html += '<div style="' + box + '"><div style="' + lbl + '">Nº Investidores</div><div style="color:#3b82f6;font-weight:900;font-size:1.1rem;">' + (p.total_investors || 0) + '</div></div>';
                html += '</div>';
            }
            if (p.campaign_end_date) {
                var endDate = new Date(p.campaign_end_date).toLocaleDateString('pt-PT');
                html += '<div style="' + box + '"><div style="' + lbl + '">Fim da Campanha</div><div style="color:#fff;font-weight:700;">' + endDate + '</div></div>';
            }
            if (p.project_url) {
                html += '<div style="' + box + '"><div style="' + lbl + '">Website / Link Externo</div><a href="' + p.project_url + '" target="_blank" style="color:#3b82f6;font-weight:700;text-decoration:none;">' + p.project_url + '</a></div>';
            }
            // Media gallery
            if (media.length > 0) {
                html += '<div style="' + lbl + 'margin-top:1.5rem;">Galeria do Projecto</div>';
                html += '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;">';
                media.forEach(function (m) {
                    var mediaUrl = m.media_url || m.filename || m.url || '';
                    var safeUrl = mediaUrl && (mediaUrl.indexOf('http') === 0 || mediaUrl.indexOf('/') === 0)
                        ? mediaUrl
                        : (mediaUrl.indexOf('carregamentos/') === 0 ? BASE + mediaUrl : BASE + 'carregamentos/projects/' + mediaUrl);
                    if (m.media_type === 'video' || m.type === 'video') {
                        html += '<video src="' + safeUrl + '" controls style="width:100%;height:80px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,0.08);"></video>';
                    } else {
                        html += '<img src="' + safeUrl + '" style="width:100%;height:80px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,0.08);cursor:pointer;" onclick="window.open(this.src)">';
                    }
                });
                html += '</div>';
            }
            prevStep = 2;
            nextStep = 4;
        } else if (step === 4) {
            title = '<i class="fas fa-comments" style="color:#f7941d;margin-right:8px;"></i>Comentários da Comunidade';
            html += '<div id="_v2_comments_container" style="min-height:200px;position:relative;">' + spinner() + '</div>';
            
            setTimeout(function() {
                var commentsUrl = BASE + 'interface_programacao/projects/get_project_comments.php?project_id=' + p.project_id;
                fetch(commentsUrl)
                    .then(function(res) {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(function(data) {
                        var cContainer = document.getElementById('_v2_comments_container');
                        if(!cContainer) return;
                        var cHtml = '<div style="max-height:400px;overflow-y:auto;padding-right:10px;margin-bottom:20px;">';
                        function renderCommentBlock(c, isReply) {
                            var cPic = c.profile_pic ? (c.profile_pic.startsWith('http') ? c.profile_pic : (c.profile_pic.startsWith('carregamentos/') ? BASE + c.profile_pic : BASE + 'carregamentos/profiles/' + c.profile_pic)) : BASE + 'recursos/images/default_profile.png';
                            var marginL = isReply ? 'margin-left:30px;border-left:2px solid rgba(255,255,255,0.05);' : '';
                            var bg = isReply ? 'background:rgba(0,0,0,0.15);' : 'background:rgba(255,255,255,0.02);';
                            var badge = (p.owner_id == c.user_id) ? '<span style="background:var(--elite-orange);color:#fff;font-size:0.6rem;padding:2px 6px;border-radius:4px;font-weight:bold;margin-left:6px;">AUTOR</span>' : '';
                            var blockHtml = '<div style="border:1px solid rgba(255,255,255,0.05);border-radius:12px;padding:1rem;margin-bottom:12px;' + marginL + bg + '">' +
                                '<div style="display:flex;gap:12px;">' +
                                '<img src="' + cPic + '" style="width:40px;height:40px;border-radius:12px;object-fit:cover;border:1px solid rgba(255,255,255,0.1);">' +
                                '<div style="flex:1;"><div style="display:flex;align-items:center;gap:8px;"><strong style="color:#fff;font-size:0.9rem;">' + escapeHtml(c.full_name) + '</strong>' + badge + '<span style="font-size:0.7rem;color:rgba(255,255,255,0.3);">' + escapeHtml(c.created_at_formatted || c.created_at) + '</span></div>' +
                                '<div style="color:rgba(255,255,255,0.8);font-size:0.9rem;margin-top:4px;line-height:1.5;word-break:break-word;">' + escapeHtml(c.content || '') + '</div></div></div>';
                            if (!isReply) {
                                blockHtml += '<div style="margin-top:10px;text-align:right;"><button onclick="window.toggleReplyForm(' + c.comment_id + ')" style="background:none;border:none;color:rgba(255,255,255,0.5);font-size:0.8rem;cursor:pointer;font-weight:bold;"><i class="fas fa-reply"></i> Responder</button></div>';
                                blockHtml += '<div id="reply_form_' + c.comment_id + '" style="display:none;margin-top:10px;gap:8px;align-items:flex-start;">' +
                                    '<textarea id="_v2_reply_' + c.comment_id + '" placeholder="Escreva a resposta..." style="flex:1;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);color:#fff;border-radius:8px;padding:10px;font-size:0.85rem;min-height:40px;resize:none;font-family:inherit;"></textarea>' +
                                    '<button onclick="window.submitV2Comment(' + p.project_id + ', ' + c.comment_id + ')" style="background:#f7941d;color:#fff;border:none;padding:10px 14px;border-radius:8px;cursor:pointer;"><i class="fas fa-paper-plane"></i></button>' +
                                    '</div>';
                            }
                            blockHtml += '</div>';
                            if (c.replies && c.replies.length > 0) {
                                c.replies.forEach(function(r) { blockHtml += renderCommentBlock(r, true); });
                            }
                            return blockHtml;
                        }
                        if(!data || data.length === 0) {
                            cHtml += '<div style="text-align:center;padding:2rem;color:rgba(255,255,255,0.4);"><i class="fas fa-comment-slash" style="font-size:2rem;margin-bottom:10px;"></i><p>Nenhum comentário ainda. Seja o primeiro a comentar!</p></div>';
                        } else {
                            data.forEach(function(c) { cHtml += renderCommentBlock(c, false); });
                        }
                        cHtml += '</div>';
                        cHtml += '<div style="display:flex;gap:10px;align-items:flex-start;background:rgba(0,0,0,0.2);padding:15px;border-radius:16px;border:1px solid rgba(255,255,255,0.05);">' +
                            '<textarea id="_v2_new_comment" placeholder="Escreva o seu comentário..." style="flex:1;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.1);color:#fff;border-radius:12px;padding:12px;font-size:0.9rem;min-height:60px;resize:none;font-family:inherit;"></textarea>' +
                            '<button onclick="window.submitV2Comment(' + p.project_id + ', 0)" style="background:#f7941d;color:#fff;border:none;padding:12px 20px;border-radius:12px;font-weight:bold;cursor:pointer;"><i class="fas fa-paper-plane"></i></button>' +
                            '</div>';
                        cContainer.innerHTML = cHtml;
                    }).catch(function(err) {
                        console.error('[KALIYE] Erro comentários:', err);
                        var cContainer = document.getElementById('_v2_comments_container');
                        if(cContainer) {
                            cContainer.innerHTML = '<p style="color:#ef4444;">Erro ao carregar comentários.</p>';
                        }
                    });
            }, 100);
            
            prevStep = 3;
            nextStep = -1; // End of flow
        }

        // Step indicators
        var totalSteps = videoUrl ? 5 : 4;
        var actualStep = videoUrl ? step : step - 1;
        var dots = '<div style="display:flex;gap:6px;justify-content:center;margin-bottom:2rem;">';
        for (var i = 0; i < totalSteps; i++) {
            dots += '<div style="width:30px;height:4px;border-radius:2px;background:' + (i <= actualStep ? '#f7941d' : 'rgba(255,255,255,0.1)') + ';transition:0.3s;' + (i <= actualStep ? 'box-shadow:0 0 8px rgba(247,148,29,0.3);' : '') + '"></div>';
        }
        dots += '</div>';

        var btnStyle = 'border:none;padding:14px 24px;border-radius:14px;font-weight:800;font-size:0.85rem;cursor:pointer;transition:0.3s;';

        var certHtml = '';
        if (p.content_hash && nextStep === -1) {
            certHtml = '<div style="margin-top:1.5rem; padding:1rem; border:1px dashed rgba(247,148,29,0.4); border-radius:12px; background:rgba(247,148,29,0.05); text-align:center;">' +
                       '<p style="color:#f7941d; font-size:0.75rem; font-weight:800; margin-bottom:5px; text-transform:uppercase;"><i class="fas fa-certificate"></i> Certificado KALIYE de Propriedade Intelectual</p>' +
                       '<p style="color:rgba(255,255,255,0.6); font-size:0.7rem; word-break:break-all; font-family:monospace;">SHA256: ' + p.content_hash + '</p>' +
                       '</div>';
        }

        content.innerHTML = dots +
            '<h2 style="font-size:1.3rem;font-weight:950;color:#fff;margin-bottom:1.8rem;">' + title + '</h2>' +
            html +
            '<div style="display:flex;justify-content:space-between;gap:12px;margin-top:2rem;">' +
            '<button class="_v2-step-prev" style="flex:1;background:rgba(255,255,255,0.06);color:#fff;' + btnStyle + '">' + (prevStep === -1 ? 'FECHAR' : '<i class="fas fa-arrow-left"></i> VOLTAR') + '</button>' +
            '<button class="_v2-step-next" style="flex:1.5;background:#f7941d;color:#fff;' + btnStyle + 'box-shadow:0 8px 20px rgba(247,148,29,0.25);">' + (nextStep === -1 ? '<i class="fas fa-check"></i> CONCLUIR' : 'PRÓXIMO <i class="fas fa-arrow-right"></i>') + '</button>' +
            '</div>' + certHtml;

        var prevBtn = content.querySelector('._v2-step-prev');
        var nextBtn = content.querySelector('._v2-step-next');
        var overlay = document.getElementById('_v2_detailsModal');
        var nextContent = overlay ? overlay.querySelector('._v2-content') : content;

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                if (prevStep === -1) {
                    closeOverlay('_v2_detailsModal');
                } else {
                    renderProjectStep(nextContent, window._v2ProjectData, prevStep);
                }
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                if (nextStep === -1) {
                    closeOverlay('_v2_detailsModal');
                } else {
                    renderProjectStep(nextContent, window._v2ProjectData, nextStep);
                }
            });
        }

        // Scroll to top of content
        content.scrollTop = 0;
        var card = content.parentElement;
        if (card) card.scrollTop = 0;
    }

    window.toggleReplyForm = function(commentId) {
        var form = document.getElementById('reply_form_' + commentId);
        if (form) {
            form.style.display = form.style.display === 'none' ? 'flex' : 'none';
        }
    };

    window.submitV2Comment = function(projectId, parentId) {
        parentId = parentId || 0;
        var inputId = parentId > 0 ? '_v2_reply_' + parentId : '_v2_new_comment';
        var input = document.getElementById(inputId);
        if(!input || !input.value.trim()) return;
        var btn = input.nextElementSibling;
        
        var fd = new FormData();
        fd.append('project_id', projectId);
        fd.append('content', input.value.trim());
        if (parentId > 0) fd.append('parent_id', parentId);
        if (window.CSRF_TOKEN) fd.append('csrf_token', window.CSRF_TOKEN); // Crucial!
        
        var oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch(BASE + 'interface_programacao/projects/post_project_comment.php', {
            method: 'POST',
            body: fd
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if(data.success) {
                // Refresh comments
                renderProjectStep(document.getElementById('_v2_detailsModal').querySelector('._v2-content'), window._v2ProjectData, 4);
            } else {
                var toast = document.createElement('div');
                toast.innerHTML = '<div style="position:fixed;bottom:20px;right:20px;background:#ef4444;color:white;padding:12px 24px;border-radius:8px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.2);">' + (data.message || 'Erro ao publicar comentário') + '</div>';
                document.body.appendChild(toast);
                setTimeout(function(){ toast.remove(); }, 4000);
                
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
        })
        .catch(function(err) {
            alert('Erro de rede ou permissão. A sua sessão pode ter expirado.');
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        });
    };

    // Make renderProjectStep globally accessible for onclick
    window.renderProjectStep = renderProjectStep;
    window.closeOverlay = closeOverlay;

    // Legacy alias
    window.closeProjectDetailsModal = function () { closeOverlay('_v2_detailsModal'); };


    // ═══════════════════════════════════════════════════════
    // 2. toggleLike — Botão Adoro
    // ═══════════════════════════════════════════════════════
    window.toggleLike = function (btn, projectId) {
        console.log('[AKSANTI-V2] toggleLike', projectId);

        if (!btn || !projectId) {
            console.error('[AKSANTI-V2] toggleLike: argumentos inválidos', btn, projectId);
            return;
        }

        var icon = btn.querySelector('i');
        if (!icon) {
            console.error('[AKSANTI-V2] toggleLike: ícone não encontrado');
            return;
        }

        var wasLiked = icon.classList.contains('fas');

        // Feedback visual imediato
        if (wasLiked) {
            icon.classList.remove('fas');
            icon.classList.add('far');
            btn.style.color = 'rgba(255,255,255,0.3)';
        } else {
            icon.classList.remove('far');
            icon.classList.add('fas');
            btn.style.color = '#ef4444';
            // Micro-animação
            btn.style.transform = 'scale(1.3)';
            setTimeout(function () { btn.style.transform = 'scale(1)'; }, 200);
        }

        // Update counter
        var countEl = document.getElementById('like-count-' + projectId);
        if (countEl) {
            var c = parseInt(countEl.innerText) || 0;
            countEl.innerText = wasLiked ? Math.max(0, c - 1) : c + 1;
        }

        // API call
        fetch(BASE + 'interface_programacao/projects/like_project.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: projectId })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    // Reverter
                    if (wasLiked) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        btn.style.color = '#ef4444';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        btn.style.color = 'rgba(255,255,255,0.3)';
                    }
                    if (countEl) {
                        var c2 = parseInt(countEl.innerText) || 0;
                        countEl.innerText = wasLiked ? c2 + 1 : Math.max(0, c2 - 1);
                    }
                } else if (data.new_count !== undefined && countEl) {
                    countEl.innerText = data.new_count;
                }
            })
            .catch(function (err) {
                console.error('[AKSANTI-V2] Like error:', err);
            });
    };


    // ═══════════════════════════════════════════════════════
    // 3. openUserCard — Modal de Perfil do Utilizador
    // ═══════════════════════════════════════════════════════
    window.openUserCard = function (userId) {
        console.log('[AKSANTI-V2] openUserCard', userId);

        var content = createOverlay('_v2_userCardModal');
        content.innerHTML = spinner();

        fetch(BASE + 'interface_programacao/user/get_user_card.php?id=' + userId)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    content.innerHTML = '<p style="text-align:center;color:#ef4444;padding:2rem;">' + (data.message || 'Utilizador não encontrado.') + '</p>';
                    return;
                }
                renderUserCard(content, data.user);
            })
            .catch(function (err) {
                console.error('[AKSANTI-V2] User card fetch error:', err);
                content.innerHTML = '<p style="text-align:center;color:#ef4444;padding:2rem;">Erro de ligação ao servidor.</p>';
            });
    };

    function renderUserCard(content, u) {
        var avatarUrl = (u.avatar && u.avatar.indexOf('http') === 0)
            ? u.avatar
            : BASE + (u.avatar || 'recursos/images/default_profile.png');

        // Stars
        var fullStars = Math.floor(u.rating || 0);
        var hasHalf = ((u.rating || 0) % 1) >= 0.5;
        var starsHtml = '';
        for (var i = 0; i < 5; i++) {
            if (i < fullStars) starsHtml += '<i class="fas fa-star" style="color:#f7941d;font-size:0.8rem;"></i>';
            else if (i === fullStars && hasHalf) starsHtml += '<i class="fas fa-star-half-alt" style="color:#f7941d;font-size:0.8rem;"></i>';
            else starsHtml += '<i class="far fa-star" style="color:rgba(255,255,255,0.15);font-size:0.8rem;"></i>';
        }

        // Member since
        var since = '';
        try {
            since = new Date(u.created_at).toLocaleDateString('pt-PT', { month: 'long', year: 'numeric' });
        } catch (e) { since = u.created_at || ''; }

        // Skills
        var skills = u.skills ? u.skills.split(',').map(function (s) { return s.trim(); }).filter(Boolean) : [];
        var skillsHtml = skills.length > 0
            ? skills.map(function (s) {
                return '<span style="background:rgba(247,148,29,0.08);color:#f7941d;padding:6px 14px;border-radius:20px;font-size:0.7rem;font-weight:800;border:1px solid rgba(247,148,29,0.15);">' + s + '</span>';
            }).join('')
            : '<span style="color:rgba(255,255,255,0.2);font-size:0.75rem;">Nenhuma especialidade listada.</span>';

        // Connection button logic
        var sessionUserId = window.sessionUserId || '';
        var isOwnProfile = (String(u.id) === String(sessionUserId));

        var connectBtn = '';
        var msgBtn = '';
        if (!isOwnProfile) {
            var isSessionStudent = (window.sessionUserType === 'student' || window.sessionUserType === 'univ_student' || window.sessionUserType === 'high_student');
            var isTargetAuthority = (u.user_type_raw === 'mentor' || u.user_type_raw === 'investor');
            
            // Only show message button if connection is accepted AND it's not a student trying to message an authority
            if (u.connection_status === 'accepted' && !(isSessionStudent && isTargetAuthority)) {
                msgBtn = '<button onclick="window.location.href=\'' + BASE + 'paginas/mensagens/index.php?user=' + u.id + '\'" style="flex:1;background:rgba(255,255,255,0.05);color:#fff;border:1px solid rgba(255,255,255,0.1);padding:14px;border-radius:14px;font-weight:900;cursor:pointer;font-size:0.85rem;"><i class="fas fa-comment-dots" style="margin-right:8px;"></i> MENSAGEM</button>';
            }
            
            var cs = u.connection_status || 'none';
            if (cs === 'none') {
                connectBtn = '<button onclick="handleUserConnectionV2(' + u.id + ', \'request\', this)" style="flex:1;background:linear-gradient(135deg,#f7941d,#ffb347);color:#fff;border:none;padding:14px;border-radius:14px;font-weight:900;cursor:pointer;font-size:0.8rem;box-shadow:0 8px 20px rgba(247,148,29,0.2);transition:0.3s;"><i class="fas fa-bolt"></i> REFORÇAR REDE</button>';
            } else if (cs === 'pending') {
                connectBtn = '<button onclick="handleUserConnectionV2(' + u.id + ', \'cancel\', this)" style="flex:1;background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.58);border:1px solid rgba(255,255,255,0.08);padding:14px;border-radius:14px;font-weight:800;cursor:pointer;font-size:0.8rem;"><i class="fas fa-clock"></i> CANCELAR PEDIDO</button>';
            } else if (cs === 'accepted') {
                connectBtn = '<button onclick="handleUserConnectionV2(' + u.id + ', \'remove\', this)" style="flex:1;background:#10b981;color:#fff;border:none;padding:14px;border-radius:14px;font-weight:800;cursor:pointer;font-size:0.8rem;"><i class="fas fa-check"></i> CONECTADO</button>';
            } else if (cs === 'received') {
                connectBtn = '<button onclick="handleUserConnectionV2(' + u.id + ', \'accept\', this)" style="flex:1;background:linear-gradient(135deg,#f7941d,#ffb347);color:#fff;border:none;padding:14px;border-radius:14px;font-weight:900;cursor:pointer;font-size:0.8rem;"><i class="fas fa-check"></i> ACEITAR</button><button onclick="handleUserConnectionV2(' + u.id + ', \'reject\', this)" style="flex:1;background:rgba(239,68,68,0.12);color:#f87171;border:1px solid rgba(239,68,68,0.22);padding:14px;border-radius:14px;font-weight:900;cursor:pointer;font-size:0.8rem;"><i class="fas fa-times"></i> RECUSAR</button>';
            }
        }

        // Message button (students can't message investors)
        var canMessage = true;
        var sessionType = (window.sessionUserType || '').toLowerCase();
        if (sessionType.indexOf('student') !== -1 && (u.role || '').toLowerCase() === 'investor') {
            canMessage = false;
        }
        var msgBtn = '';
        if (!isOwnProfile) {
            if (canMessage) {
                msgBtn = '<button onclick="window.location.href=\'' + BASE + 'paginas/social/messages.php?start=' + u.id + '\'" style="flex:1;background:rgba(255,255,255,0.06);color:#fff;border:none;padding:14px;border-radius:14px;font-weight:800;cursor:pointer;font-size:0.8rem;transition:0.3s;"><i class="fas fa-comment-dots"></i> MENSAGEM</button>';
            } else {
                msgBtn = '<div style="flex:1;background:rgba(255,255,255,0.02);color:rgba(255,255,255,0.2);padding:14px;border-radius:14px;font-size:0.7rem;font-weight:800;text-align:center;border:1px dashed rgba(255,255,255,0.05);">CANAL PROTEGIDO</div>';
            }
        }

        var box = 'background:rgba(255,255,255,0.03);padding:1.2rem;border-radius:20px;border:1px solid rgba(255,255,255,0.06);margin-bottom:1rem;';
        var lbl = 'color:#f7941d;font-size:0.6rem;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:10px;';

        content.style.padding = '0';
        content.style.overflowX = 'hidden';
        content.innerHTML =
            // Header
            '<div style="display:flex;align-items:center;gap:16px;padding:1.5rem;background:linear-gradient(135deg,rgba(247,148,29,0.05),transparent);flex-wrap:wrap;">' +
            '<div style="position:relative;flex-shrink:0;">' +
            '<img src="' + avatarUrl + '" style="width:100px;height:100px;border-radius:24px;object-fit:cover;border:3px solid #f7941d;box-shadow:0 10px 30px rgba(0,0,0,0.5);">' +
            (u.is_verified ? '<div style="position:absolute;bottom:-4px;right:-4px;background:#f7941d;color:#fff;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #0d1628;font-size:0.55rem;"><i class="fas fa-check"></i></div>' : '') +
            '</div>' +
            '<div style="flex:1;min-width:0;">' +
            '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">' +
            '<h2 style="font-size:1.3rem;color:#fff;font-weight:800;margin:0;">' + (u.name || 'Utilizador') + '</h2>' +
            '<span style="background:rgba(247,148,29,0.12);color:#f7941d;padding:4px 10px;border-radius:8px;font-size:0.6rem;font-weight:900;text-transform:uppercase;">' + (u.role || 'MEMBRO') + '</span>' +
            '</div>' +
            '<div style="display:flex;align-items:center;gap:12px;margin-top:10px;flex-wrap:wrap;">' +
            '<span style="background:rgba(255,255,255,0.05);padding:4px 12px;border-radius:20px;border:1px solid rgba(255,255,255,0.08);color:#fff;font-size:0.7rem;font-weight:800;">' + (u.connections_count || 0) + ' CONEXÕES</span>' +
            '<div style="display:flex;align-items:center;gap:3px;">' + starsHtml +
            '<span style="color:rgba(255,255,255,0.35);font-size:0.65rem;font-weight:700;margin-left:4px;">(' + ((u.rating && u.rating > 0) ? u.rating.toFixed(1) : 'N/A') + ')</span>' +
            '</div>' +
            '</div>' +
            '<p style="color:rgba(255,255,255,0.3);font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-top:8px;">Membro desde ' + since + '</p>' +
            '</div>' +
            '</div>' +

            // Body
            '<div style="padding:0 1.2rem 1.5rem;">' +
            // Bio
            '<div style="' + box + '">' +
            '<h3 style="' + lbl + '">A MINHA HISTÓRIA</h3>' +
            '<p style="color:rgba(255,255,255,0.7);line-height:1.7;font-size:0.9rem;margin:0;">' + (u.bio || 'Sem biografia disponível.') + '</p>' +
            '</div>' +

            // Skills
            '<div style="' + box + '">' +
            '<h3 style="' + lbl + '">HABILIDADES & ESPECIALIDADES</h3>' +
            '<div style="display:flex;flex-wrap:wrap;gap:8px;">' + skillsHtml + '</div>' +
            '</div>' +

            // Experience / Portfolio
            (u.experience_summary ? '<div style="' + box + '"><h3 style="' + lbl + '">TRABALHOS & PORTFÓLIO</h3><p style="color:rgba(255,255,255,0.7);line-height:1.7;font-size:0.9rem;margin:0;">' + escapeHtml(u.experience_summary).replace(/\\n/g, '<br>') + '</p></div>' : '') +

            // Location & Institution
            (function() {
                var orgInfo = u.organization ? '<p style="color:#fff;font-size:0.85rem;font-weight:700;margin:0;margin-top:8px;"><i class="fas fa-building" style="margin-right:6px;opacity:0.4;"></i>' + escapeHtml(u.organization) + '</p>' : '';
                var instInfo = u.institution ? '<p style="color:#fff;font-size:0.85rem;font-weight:700;margin:0;margin-top:8px;"><i class="fas fa-university" style="margin-right:6px;opacity:0.4;"></i>' + escapeHtml(u.institution) + '</p>' : '';
                return '<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;margin-bottom:1.5rem;">' +
                '<div style="' + box + 'margin-bottom:0;">' +
                '<h3 style="' + lbl + '">LOCALIZAÇÃO</h3>' +
                '<p style="color:#fff;font-size:0.85rem;font-weight:700;margin:0;"><i class="fas fa-map-marker-alt" style="margin-right:6px;opacity:0.4;"></i>' + (u.location || 'Angola') + '</p>' +
                '</div>' +
                '<div style="' + box + 'margin-bottom:0;">' +
                '<h3 style="' + lbl + '">FORMAÇÃO & INSTITUIÇÃO</h3>' +
                '<p style="color:#fff;font-size:0.85rem;font-weight:700;margin:0;"><i class="fas fa-graduation-cap" style="margin-right:6px;opacity:0.4;"></i>' + (u.level || 'Membro Aksanti') + '</p>' +
                instInfo + orgInfo +
                '</div>' +
                '</div>';
            })() +
            
            // Social Links
            ((u.linkedin || u.website) ? '<div style="display:flex;gap:12px;margin-bottom:1.5rem;flex-wrap:wrap;">' +
            (u.linkedin ? '<a href="' + escapeHtml(u.linkedin) + '" target="_blank" style="flex:1;min-width:120px;background:rgba(10,102,194,0.1);border:1px solid rgba(10,102,194,0.3);color:#60a5fa;text-decoration:none;padding:12px;border-radius:12px;text-align:center;font-size:0.8rem;font-weight:800;transition:0.3s;"><i class="fab fa-linkedin" style="margin-right:6px;font-size:1rem;"></i> LINKEDIN</a>' : '') +
            (u.website ? '<a href="' + escapeHtml(u.website) + '" target="_blank" style="flex:1;min-width:120px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.1);color:#fff;text-decoration:none;padding:12px;border-radius:12px;text-align:center;font-size:0.8rem;font-weight:800;transition:0.3s;"><i class="fas fa-globe" style="margin-right:6px;font-size:1rem;"></i> WEBSITE</a>' : '') +
            '</div>' : '') +

            // Action buttons
            ((!isOwnProfile) ? '<div style="display:flex;gap:10px;flex-wrap:wrap;">' + msgBtn + connectBtn + '</div>' : '') +
            '</div>';
    }

    // Connection action handler
    window.handleUserConnectionV2 = function (userId, action, btn) {
        if (typeof enforceKYC === 'function' && !enforceKYC()) return;

        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';

        var fd = new FormData();
        fd.append('target_id', userId);
        fd.append('action', action);

        fetch(BASE + 'interface_programacao/user/connection_action.php', {
            method: 'POST',
            body: fd
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    // Re-open the card to refresh
                    closeOverlay('_v2_userCardModal');
                    setTimeout(function () { window.openUserCard(userId); }, 400);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Erro', data.message || 'Operação falhou.', 'error');
                    } else {
                        alert(data.message || 'Operação falhou.');
                    }
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
    };

    // Legacy alias
    window.closeUserCard = function () { closeOverlay('_v2_userCardModal'); };

    // ═══════════════════════════════════════════════════════
    // ESC key handler
    // ═══════════════════════════════════════════════════════
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeOverlay('_v2_detailsModal');
            closeOverlay('_v2_userCardModal');
        }
    });

    console.log('%c[AKSANTI-V2] ✅ 3 funções registadas: openProjectDetails, toggleLike, openUserCard', 'background:#10b981;color:#fff;font-size:12px;font-weight:bold;padding:4px 12px;');

})();
