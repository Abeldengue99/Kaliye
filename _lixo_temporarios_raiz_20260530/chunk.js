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
            project_id:'ID do projecto', owner_id:'ID do proprietário', title:'Título', description:'Descrição',
            category:'Categoria', budget_needed:'Valor total necessário', image_url:'Imagem de capa',
            video_url:'Vídeo', pitch_video_url:'Pitch em vídeo', execution_time:'Tempo de execução',
            team_size:'Tamanho da equipa', project_stage:'Estágio do projecto', target_audience:'Público-alvo',
            needs_to_advance:'O que falta para avançar', idea_origin:'Origem do projecto', motivation:'Motivação',
            project_url:'Website / URL', funding_goal:'Meta de financiamento', minimum_investment:'Investimento mínimo',
            maximum_investment:'Investimento máximo', campaign_start_date:'Data início da campanha',
            campaign_end_date:'Data fim da campanha', funding_type:'Tipo de financiamento',
            equity_available:'Equity disponível', equity_committed:'Equity comprometido',
            total_invested:'Total investido', total_investors:'Total de investidores',
            approval_status:'Estado de aprovação', approved_by:'Aprovado por', approved_at:'Aprovado em',
            is_public:'Projecto público', is_featured:'Em destaque', created_at:'Criado em',
            updated_at:'Actualizado em', market_score:'Pontuação de mercado', ai_status:'Estado IA', status:'Estado'
        }[key] || key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()));
        const projectValue = (key, value) => {
            if (value === null || value === undefined || value === '') return '<span style="color:rgba(255,255,255,0.32);">Não informado</span>';
            if (typeof value === 'boolean') return value ? 'Sim' : 'Não';
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
            if (tagsHtml) out += `<div style="margin-top:1.5rem;"><div style="${labelStyle} margin-bottom:0.75rem;">Tags Tecnológicas</div>${tagsHtml}</div>`;
            if (mediaHtml) out += `<div style="margin-top:1.5rem;"><div style="${labelStyle} margin-bottom:0.75rem;">Galeria do Projecto</div>${mediaHtml}</div>`;
            return out;
        };

        if (step === 0) {
            stepTitle = 'Pitch Cinema';
            nextAction = 'renderProjectModalStep(1)';
            prevAction = 'closeProjectDetailsModal()';
            const fullVideoPath = videoUrl ? (videoUrl.startsWith('http') ? videoUrl : `${BASE_URL}carregamentos/projects/${videoUrl}`) : '';
            stepContent = `<div style="background:#000; border-radius:24px; overflow:hidden; min-height:300px; display:flex; align-items:center; justify-content:center;">${videoUrl ? `<video src="${fullVideoPath}" controls style="width:100%; height:100%; object-fit:contain;"></video>` : `<p style="opacity:0.2;">Sem Pitch de Vídeo</p>`}</div>`;
        } else if (step === 1) {
            stepTitle = 'Visão';
            nextAction = 'renderProjectModalStep(2)';
            prevAction = videoUrl ? 'renderProjectModalStep(0)' : 'closeProjectDetailsModal()';
            stepContent = `<div style="display:flex; align-items:center; gap:15px; margin-bottom:1rem;"><img src="${BASE_URL}${p.owner_pic || 'recursos/images/default_profile.png'}" style="width:40px; height:40px; border-radius:10px; object-fit:cover;"><div><div style="color:#fff; font-weight:800;">${p.owner_name}</div><div style="color:rgba(255,255,255,0.5); font-size:0.8rem;">${(p.owner_type || p.user_type || 'Membro').toString().toUpperCase()}</div></div></div><p style="color:rgba(255,255,255,0.7); line-height:1.6;">${p.description || 'Descrição não disponível.'}</p><div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-top:1.5rem;"><div style="${dataBox}"><div style="${labelStyle}">Categoria</div><div style="color:#fff; font-weight:900;">${p.category || 'Não definida'}</div></div><div style="${dataBox}"><div style="${labelStyle}">Status</div><div style="color:#fff; font-weight:900;">${p.approval_status || 'Pendente'}</div></div></div>`;
        } else if (step === 2) {
            stepTitle = 'Execução';
            nextAction = 'renderProjectModalStep(3)';
            prevAction = 'renderProjectModalStep(1)';
            stepContent = `<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:1rem;"><div style="${dataBox}"><div style="${labelStyle}">Equipa</div><div style="color:#fff; font-weight:900;">${p.team_size || '1'}</div></div><div style="${dataBox}"><div style="${labelStyle}">Estágio</div><div style="color:#fff; font-weight:900;">${p.project_stage || 'Projecto'}</div></div></div>`;
            if (p.execution_time) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">Tempo de Execução</div><div style="color:#fff; font-weight:700;">${p.execution_time}</div></div>`;
            }
            if (p.target_audience) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">Público-Alvo</div><div style="color:rgba(255,255,255,0.7);">${p.target_audience}</div></div>`;
            }
            if (p.idea_origin) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">Origem do projecto</div><div style="color:rgba(255,255,255,0.7);">${p.idea_origin}</div></div>`;
            }
            if (p.motivation) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">Motivação</div><div style="color:rgba(255,255,255,0.7);">${p.motivation}</div></div>`;
            }
            if (p.needs_to_advance) {
                stepContent += `<div style="${dataBox} border-left:4px solid #3b82f6;"><div style="${labelStyle}">O Que Falta Para Avançar?</div><div style="color:rgba(255,255,255,0.7);">${p.needs_to_advance}</div></div>`;
            }
            if (p.project_url) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">Link Externo</div><div><a href="${p.project_url}" target="_blank" style="color:#3b82f6; text-decoration:none;">${p.project_url}</a></div></div>`;
            }
        } else if (step === 3) {
            stepTitle = 'Financeiro';
            nextAction = 'closeProjectDetailsModal()';
            prevAction = 'renderProjectModalStep(2)';
            const goal = p.funding_goal || p.budget_needed || 0;
            stepContent = `<div style="${dataBox} border-left:4px solid #f7941d;"><div style="${labelStyle}">Meta</div><div style="color:#fff; font-size:1.5rem; font-weight:950;">${new Intl.NumberFormat('pt-AO').format(goal)} Kz</div></div>`;
            if (p.minimum_investment) {
                stepContent += `<div style="${dataBox}"><div style="${labelStyle}">Investimento Mínimo</div><div style="color:#fff; font-weight:900;">${new Intl.NumberFormat('pt-AO').format(p.minimum_investment)} Kz</div></div>`;
            }
            if (p.equity_available) {
                stepContent += `<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:1rem;"><div style="${dataBox}"><div style="${labelStyle}">Equity Disponível</div><div style="color:#f7941d; font-weight:900;">${p.equity_available}%</div></div><div style="${dataBox}"><div style="${labelStyle}">Equity Comprometido</div><div style="color:#ef4444; font-weight:900;">${p.equity_committed || '0'}%</div></div></div>`;
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
                stepContent += `<div style="margin-top:1.5rem;"><div style="${labelStyle} margin-bottom:0.75rem;">Tags Tecnológicas</div>${tagsHtml}</div>`;
        }

        content.innerHTML = `
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size:1.5rem; font-weight:950; color:#fff; margin-bottom:1.5rem;">${stepTitle}</h2>
                ${stepContent}
            </div>
            <div style="display:flex; justify-content:space-between; gap:10px;">
                <button onclick="${prevAction}" style="flex:1; background:rgba(255,255,255,0.05); color:#fff; border:none; padding:12px; border-radius:12px; font-weight:800; cursor:pointer;">VOLTAR</button>
                <button onclick="${nextAction}" style="flex:1.5; background:#f7941d; color:#fff; border:none; padding:12px; border-radius:12px; font-weight:950; cursor:pointer;">${step === 3 ? 'FECHAR' : 'PRÓXIMO'}</button>
            </div>
        `;
    };
