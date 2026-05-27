/**
 * Investor Dashboard Scripts
 * Handles project details modal, investment flow, legal agreements, and notifications.
 */

function openInvestorProjectDetails(id) {
    const modal = document.getElementById('detailsModal');
    const content = document.getElementById('detailsContent');
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align: center; color: var(--text-secondary); padding: 5rem;"><i class="fas fa-spinner fa-spin fa-3x" style="color: var(--accent-gold);"></i><p style="margin-top: 1rem;">Preparando dossiÃª estratÃ©gico...</p></div>';

    fetch(`../../interface_programacao/projects/get_project_details.php?id=${id}&project_id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const p = data.project || {};
                const media = data.media || [];
                const tags = data.tags || [];
                const budgetAmount = p.budget_needed || p.funding_goal || p.budget || p.requested_budget || 0;
                const minimumInvest = p.minimum_investment || p.minimum_investment_amount || p.minimum_amount || 0;
                const equityAvailable = p.equity_available || p.equity || null;
                const equityCommitted = p.equity_committed || p.committed_equity || 0;
                const totalInvested = p.total_invested || p.total_raised || 0;
                const totalInvestors = p.total_investors || p.investors_count || 0;
                const ownerName = p.full_name || p.owner_name || 'Autor';
                const ownerType = p.user_type || p.owner_type || '';
                const ownerPic = p.profile_pic && p.profile_pic !== 'default_profile.png' ? '../../' + p.profile_pic : '../../recursos/images/default_profile.png';
                const campaignEnds = p.campaign_end_date || p.end_date || null;
                const projectUrl = p.project_url || p.website_url || p.external_link || null;

                let mediaHtml = '';
                if (media.length > 0) {
                    mediaHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2.5rem;">';
                    media.forEach(m => {
                        if (m.media_type === 'image') {
                            mediaHtml += `<img src="../../${m.media_url}" style="width: 100%; height: 180px; object-fit: cover; border-radius: 16px; border: 1px solid var(--glass-border); cursor: pointer;" onclick="window.open(this.src)">`;
                        } else {
                            mediaHtml += `<video src="../../${m.media_url}" style="width: 100%; height: 180px; object-fit: cover; border-radius: 16px; border: 1px solid var(--glass-border);" controls></video>`;
                        }
                    });
                    mediaHtml += '</div>';
                }

                let tagsHtml = '';
                if (tags && tags.length > 0) {
                    tagsHtml = '<div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--glass-border);">';
                    tagsHtml += '<h4 style="margin: 0 0 1rem 0; font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;"><i class="fas fa-microchip"></i> Ecossistema TecnolÃ³gico</h4>';
                    tagsHtml += '<div style="display: flex; gap: 0.8rem; flex-wrap: wrap;">';
                    tags.forEach(t => {
                        tagsHtml += `<span style="background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.2); padding: 6px 16px; border-radius: 10px; font-size: 0.85rem; color: var(--accent-gold); font-weight: 600;">${t}</span>`;
                    });
                    tagsHtml += '</div></div>';
                }

                let invHtml = '';
                if (data.investors && data.investors.length > 0) {
                    invHtml = '<div style="margin-top: 2.5rem; padding-top: 2rem; border-top: 1px solid var(--glass-border);">';
                    invHtml += '<h4 style="margin: 0 0 1.5rem 0; font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;"><i class="fas fa-hand-holding-usd"></i> Pool de Investidores</h4>';
                    invHtml += '<div style="display: flex; gap: 1rem; flex-wrap: wrap;">';
                    data.investors.forEach(inv => {
                        const pfp = inv.profile_pic && inv.profile_pic !== 'default_profile.png' ? '../../' + inv.profile_pic : '../../recursos/images/default_profile.png';
                        invHtml += `
                            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.05); padding: 8px 15px; border-radius: 12px; border: 1px solid var(--glass-border);">
                                <img src="${pfp}" style="width: 25px; height: 25px; border-radius: 50%; object-fit: cover;">
                                <span style="font-size: 0.85rem; font-weight: 600;">${inv.full_name}</span>
                            </div>
                        `;
                    });
                    invHtml += '</div></div>';
                }

                let msHtml = '';
                if (data.milestones && data.milestones.length > 0) {
                    msHtml = '<div style="margin-top: 2.5rem;"><h4 style="margin: 0 0 1.5rem 0; font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;"><i class="fas fa-map-marked-alt"></i> Roadmap de ExecuÃ§Ã£o</h4>';
                    data.milestones.forEach(ms => {
                        let statusColor = '#94a3b8';
                        let statusIcon = 'fa-circle';
                        if (ms.status === 'completed') { statusColor = '#10b981'; statusIcon = 'fa-check-circle'; }
                        else if (ms.status === 'in_progress') { statusColor = '#f59e0b'; statusIcon = 'fa-spinner fa-spin'; }

                        msHtml += `
                            <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; padding-left: 1.5rem; border-left: 2px solid ${statusColor}44; position: relative;">
                                <i class="fas ${statusIcon}" style="position: absolute; left: -11px; top: 0; background: #0f172a; color: ${statusColor}; font-size: 1.3rem;"></i>
                                <div style="flex-grow: 1; background: rgba(255,255,255,0.02); padding: 1.2rem; border-radius: 16px; border: 1px solid var(--glass-border);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <h5 style="margin: 0; font-size: 1.1rem; color: white; font-weight: 700;">${ms.title}</h5>
                                        <span style="font-size: 0.7rem; color: ${statusColor}; text-transform: uppercase; font-weight: 800; border: 1px solid ${statusColor}44; padding: 4px 10px; border-radius: 8px;">${ms.status}</span>
                                    </div>
                                    <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0.8rem 0;">${ms.description || ''}</p>
                                    ${ms.target_date ? `<div style="font-size: 0.75rem; color: var(--text-secondary);"><i class="fas fa-calendar-day"></i> Estimativa: ${new Date(ms.target_date).toLocaleDateString('pt-AO')}</div>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    msHtml += '</div>';
                }

                content.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2.5rem;">
                        <img src="${ownerPic}" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--accent-gold); box-shadow: 0 0 20px rgba(251, 191, 36, 0.2);">
                        <div style="flex-grow: 1;">
                            <h2 style="font-size: 2.2rem; color: white; margin: 0; font-weight: 900; letter-spacing: -1px;">${p.title || 'Projecto'}</h2>
                            <p style="color: var(--text-secondary); margin: 0.5rem 0 0 0; font-size: 1.1rem;">Visão estratégica de <strong>${ownerName}</strong></p>
                        </div>
                        <div style="text-align: right;">
                             <div style="color: var(--accent-gold); font-size: 1.8rem; font-weight: 900;">${new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA' }).format(budgetAmount)}</div>
                             <small style="color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">
                                 ${ownerType === 'investor' ? 'Capital Disponível' : 'Captação Necessária'}
                             </small>
                        </div>
                    </div>

                    ${mediaHtml}

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;">
                        <div style="background:rgba(15,23,42,0.4);border:1px solid rgba(255,255,255,0.08);padding:1.2rem;border-radius:18px;">
                            <div style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem;">Meta de Captação</div>
                            <div style="font-size:1.2rem;color:#fff;font-weight:900;">${new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA' }).format(budgetAmount)}</div>
                        </div>
                        <div style="background:rgba(15,23,42,0.4);border:1px solid rgba(255,255,255,0.08);padding:1.2rem;border-radius:18px;">
                            <div style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem;">Investimento mínimo</div>
                            <div style="font-size:1.2rem;color:#fff;font-weight:900;">${new Intl.NumberFormat('pt-AO').format(minimumInvest)} AKZ</div>
                        </div>
                        <div style="background:rgba(15,23,42,0.4);border:1px solid rgba(255,255,255,0.08);padding:1.2rem;border-radius:18px;">
                            <div style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem;">Total angariado</div>
                            <div style="font-size:1.2rem;color:#10b981;font-weight:900;">${new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA' }).format(totalInvested)}</div>
                        </div>
                        <div style="background:rgba(15,23,42,0.4);border:1px solid rgba(255,255,255,0.08);padding:1.2rem;border-radius:18px;">
                            <div style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem;">Investidores</div>
                            <div style="font-size:1.2rem;color:#3b82f6;font-weight:900;">${totalInvestors}</div>
                        </div>
                    </div>

                    ${campaignEnds ? `<div style="margin-bottom:1.5rem; padding:1.4rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:18px;"><strong style="color:#fff; display:block; margin-bottom:0.5rem;">Fim da campanha</strong><span style="color:var(--text-secondary);">${new Date(campaignEnds).toLocaleDateString('pt-PT')}</span></div>` : ''}
                    ${projectUrl ? `<div style="margin-bottom:1.5rem; padding:1.4rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:18px;"><strong style="color:#fff; display:block; margin-bottom:0.5rem;">Link Externo</strong><a href="${projectUrl}" target="_blank" style="color:#3b82f6; text-decoration:none;">${projectUrl}</a></div>` : ''}
                    ${(equityAvailable || equityCommitted) ? `<div style="margin-bottom:1.5rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem;">
                        ${equityAvailable ? `<div style="padding:1.4rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:18px;"><strong style="color:#fff; display:block; margin-bottom:0.5rem;">Equity Disponível</strong><span style="color:var(--text-secondary);">${equityAvailable}%</span></div>` : ''}
                        ${equityCommitted ? `<div style="padding:1.4rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:18px;"><strong style="color:#fff; display:block; margin-bottom:0.5rem;">Equity Comprometido</strong><span style="color:var(--text-secondary);">${equityCommitted}%</span></div>` : ''}
                    </div>` : ''}

                    <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--glass-border); padding: 2.5rem; border-radius: 24px; margin-bottom: 2.5rem; line-height: 1.9; color: var(--text-secondary); white-space: pre-line;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; padding-bottom: 2rem; border-bottom: 1px solid var(--glass-border);">
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <small style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; font-weight: 700; letter-spacing: 1px;">Cronograma</small>
                                <span style="font-weight: 800; color: white; font-size: 1.1rem;"><i class="fas fa-hourglass-half" style="color: var(--accent-gold);"></i> ${p.execution_time || 'Sob consulta'}</span>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <small style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; font-weight: 700; letter-spacing: 1px;">Capital Humano</small>
                                <span style="font-weight: 800; color: white; font-size: 1.1rem;"><i class="fas fa-users-cog" style="color: var(--accent-gold);"></i> ${p.team_size || 1} Especialistas</span>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <small style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; font-weight: 700; letter-spacing: 1px;">NÃ­vel de Maturidade</small>
                                <span style="font-weight: 800; color: white; font-size: 1.1rem;"><i class="fas fa-rocket" style="color: var(--accent-gold);"></i> ${p.project_stage || 'Ideia'}</span>
                            </div>
                        </div>

                        <div style="margin-bottom: 2rem;">
                            <h4 style="margin: 0 0 1rem 0; font-size: 1.1rem; color: white; font-weight: 800;"><i class="fas fa-align-left" style="color: var(--accent-gold);"></i> Resumo Executivo</h4>
                            <p style="font-size: 1.05rem;">${p.description}</p>
                        </div>

                        ${p.target_audience ? `
                        <div style="margin-bottom: 2rem; padding: 1.5rem; background: rgba(59, 130, 246, 0.08); border-radius: 16px; border: 1px solid rgba(59, 130, 246, 0.2);">
                            <h4 style="margin: 0 0 0.8rem 0; font-size: 0.95rem; color: #60a5fa; font-weight: 800;"><i class="fas fa-bullseye"></i> Market Fit / PÃºblico-Alvo</h4>
                            <p style="margin: 0; font-size: 1rem; color: #94a3b8;">${p.target_audience}</p>
                        </div>` : ''}
                    </div>

                    ${msHtml}
                    ${tagsHtml}
                    ${invHtml}

                    <button onclick="document.getElementById('detailsModal').style.display='none'; openInvestModal(${p.project_id}, '${p.title.replace(/'/g, "\\'")}')" class="btn-primary" style="display: block; width: 100%; text-align: center; margin-top: 2rem; background: var(--accent-gold); color: #000; padding: 1.2rem; border-radius: 16px; cursor: pointer; font-size: 1.1rem; font-weight: 900; border: none; box-shadow: 0 10px 20px rgba(251, 191, 36, 0.3);">
                        <i class="fas fa-hand-holding-usd"></i> INVESTIR NESTA IDEIA / PROJECTO
                    </button>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--glass-border);">
                        <a href="../social/messages.php?start=${p.owner_id}" class="btn-primary" style="text-decoration: none; text-align: center; padding: 1rem; background: rgba(255,255,255,0.05); color: white; font-weight: 700; font-size: 0.9rem; border-radius: 12px; border: 1px solid var(--glass-border);">
                            <i class="fas fa-comments"></i> Iniciar ConexÃ£o
                        </a>
                        <a href="../social/profile.php?user_id=${p.owner_id}" class="btn-primary" style="text-decoration: none; text-align: center; padding: 1rem; background: rgba(255,255,255,0.05); color: white; font-weight: 700; font-size: 0.9rem; border-radius: 12px; border: 1px solid var(--glass-border);">
                            <i class="fas fa-user-circle"></i> Ver Perfil
                        </a>
                    </div>
                `;
            } else {
                content.innerHTML = '<p style="color: var(--danger); text-align: center;">Erro ao carregar projeto.</p>';
            }
        })
        .catch(err => {
            console.error(err);
            content.innerHTML = '<p style="color: var(--danger); text-align: center;">Erro de conexÃ£o estratÃ©gica.</p>';
        });
}

function markAsRead(projectId) {
    const card = document.querySelector(`[onclick="openInvestorProjectDetails(${projectId})"]`).closest('.glass');
    const archiveBtn = card.querySelector('button[onclick^="markAsRead"]');

    if (archiveBtn) {
        archiveBtn.disabled = true;
        archiveBtn.style.opacity = '0.5';
        archiveBtn.innerHTML = '<i class="fas fa-check"></i> Lido';
    }

    fetch('../../interface_programacao/social/mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ project_id: projectId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector('.investor-dashboard h1 + p + div .glass div:last-child') ||
                    document.querySelector('div[style*="font-weight: 900; color: white;"]');

                if (badge) {
                    let currentCount = parseInt(badge.innerText);
                    if (currentCount > 0) {
                        badge.innerText = currentCount - 1;
                    }
                }

                const projectBadge = card.querySelector('.new-project-badge');
                if (projectBadge) {
                    projectBadge.style.transition = 'all 0.4s ease';
                    projectBadge.style.opacity = '0';
                    projectBadge.style.transform = 'scale(0.8)';
                    setTimeout(() => projectBadge.remove(), 400);
                }

                if (archiveBtn) {
                    archiveBtn.style.background = 'rgba(16, 185, 129, 0.1)';
                    archiveBtn.style.color = '#10b981';
                    archiveBtn.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                }
            } else {
                if (archiveBtn) {
                    archiveBtn.disabled = false;
                    archiveBtn.style.opacity = '1';
                    archiveBtn.innerHTML = '<i class="fas fa-archive"></i> Arquivar';
                }
                Swal.fire('Erro', 'NÃ£o foi possÃ­vel marcar como lido.', 'error');
            }
        });
}

// Legal Infrastructure
function checkInvestorLegal() {
    fetch('../../interface_programacao/system/get_legal_agreements.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.agreements.some(a => a.status === 'pending')) {
                const pending = data.agreements.filter(a => a.status === 'pending');
                const alertDiv = document.getElementById('investorLegalAlert');
                if (!alertDiv) return;
                alertDiv.innerHTML = `
                    <div class="glass" data-aos="flip-up" style="margin-bottom: 2.5rem; padding: 1.5rem 2rem; border: 2px solid var(--accent-orange); background: rgba(247, 148, 29, 0.05); border-radius: 20px; display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 1.5rem;">
                            <div style="width: 50px; height: 50px; background: var(--accent-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 20px rgba(247, 148, 29, 0.3);">
                                <i class="fas fa-file-signature" style="font-size: 1.2rem; color: #000;"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0; color: #fff; font-size: 1.1rem; font-weight: 800;">PendÃªncias JurÃ­dicas (${pending.length})</h4>
                                <p style="margin: 0.2rem 0 0 0; font-size: 0.85rem; color: var(--text-secondary);">Existem acordos de investimento ou termos de plataforma aguardando sua assinatura para validaÃ§Ã£o legal.</p>
                            </div>
                        </div>
                        <button onclick="viewAndSignLegal(${JSON.stringify(pending[0])})" class="btn-primary" style="background: var(--accent-orange); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 800; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; flex-shrink: 0;">
                            Resolver PendÃªncia <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                `;
            }
        });
}

async function viewAndSignLegal(la) {
    const { value: formValues } = await Swal.fire({
        title: 'Assinatura Profissional de Acordo',
        html: `
            <div style="text-align: left; max-height: 350px; overflow-y: auto; background: rgba(0,0,0,0.3); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid var(--glass-border); line-height: 1.6; font-size: 0.9rem; color: #cbd5e1; white-space: pre-line;">
                <h4 style="color: var(--accent-gold); margin-top: 0; font-size: 1rem;"><i class="fas fa-gavel"></i> Termos e ClÃ¡usulas</h4>
                ${la.contract_terms}
            </div>

            ${la.admin_signed_file ? `
                <div style="background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="font-size: 0.8rem; font-weight: 800; color: #10b981; text-transform: uppercase;">Contrato Validado</div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">A Aksanti jÃ¡ assinou este documento.</div>
                    </div>
                    <a href="../../${la.admin_signed_file}" target="_blank" style="background: #10b981; color: white; padding: 6px 15px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; font-weight: 700;">Original <i class="fas fa-external-link-alt"></i></a>
                </div>
            ` : ''}

            <div style="text-align: left;">
                <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.8rem; font-weight: 600;">MÃ©todo de ValidaÃ§Ã£o Legal:</label>
                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <button type="button" id="btn-dig" onclick="setSignType('digital')" style="flex: 1; padding: 0.8rem; border-radius: 10px; border: 2px solid var(--accent-orange); background: rgba(247,148,29,0.1); color: #fff; font-weight: 700; cursor: pointer;">Assinatura Digital</button>
                    <button type="button" id="btn-phy" onclick="setSignType('physical')" style="flex: 1; padding: 0.8rem; border-radius: 10px; border: 2px solid #334155; background: transparent; color: #94a3b8; font-weight: 700; cursor: pointer;">Upload FÃ­sico</button>
                </div>

                <div id="cont-dig">
                    <label style="font-size: 0.75rem; color: #94a3b8; display: block; margin-bottom: 0.5rem;">Declare seu Nome Completo como Aceite Digital:</label>
                    <input type="text" id="swal-sig-text" class="swal2-input" placeholder="Seu Nome Completo" style="width: 100%; margin: 0; background: #0f172a; border: 1px solid #334155; color: white; font-family: 'Outfit', sans-serif;">
                </div>

                <div id="cont-phy" style="display: none;">
                    <label style="font-size: 0.75rem; color: #94a3b8; display: block; margin-bottom: 0.5rem;">Anexe o Contrato Assinado e Digitalizado:</label>
                    <input type="file" id="swal-sig-file" class="swal2-input" style="width: 100%; margin: 0; background: #0f172a; border: 1px solid #334155; color: white;">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Validar e Assinar',
        cancelButtonText: 'Analisar mais tarde',
        confirmButtonColor: '#10b981',
        background: '#1e293b',
        color: '#fff',
        width: '650px',
        preConfirm: () => {
            const type = window.currentSignType || 'digital';
            const text = document.getElementById('swal-sig-text').value;
            const file = document.getElementById('swal-sig-file').files[0];

            if (type === 'digital' && (!text || text.length < 5)) {
                Swal.showValidationMessage('Escreva o seu nome completo para validar.');
                return false;
            }
            if (type === 'physical' && !file) {
                Swal.showValidationMessage('Anexe o ficheiro assinado.');
                return false;
            }

            return { type, text, file };
        }
    });

    if (formValues) {
        Swal.fire({ title: 'A processar contrato...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        const formData = new FormData();
        formData.append('agreement_id', la.agreement_id);
        formData.append('signature_type', formValues.type);
        if (formValues.type === 'digital') {
            formData.append('signature_text', formValues.text);
        } else {
            formData.append('user_signed_file', formValues.file);
        }

        fetch('../../interface_programacao/system/sign_legal_agreement.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Efetivado!', 'O acordo foi assinado e arquivado legalmente.', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erro', data.message, 'error');
                }
            });
    }
}

window.setSignType = function (type) {
    window.currentSignType = type;
    document.getElementById('cont-dig').style.display = type === 'digital' ? 'block' : 'none';
    document.getElementById('cont-phy').style.display = type === 'physical' ? 'block' : 'none';

    document.getElementById('btn-dig').style.borderColor = type === 'digital' ? 'var(--accent-orange)' : '#334155';
    document.getElementById('btn-dig').style.background = type === 'digital' ? 'rgba(247,148,29,0.1)' : 'transparent';
    document.getElementById('btn-dig').style.color = type === 'digital' ? '#fff' : '#94a3b8';

    document.getElementById('btn-phy').style.borderColor = type === 'physical' ? 'var(--accent-orange)' : '#334155';
    document.getElementById('btn-phy').style.background = type === 'physical' ? 'rgba(247,148,29,0.1)' : 'transparent';
    document.getElementById('btn-phy').style.color = type === 'physical' ? '#fff' : '#94a3b8';
}

document.addEventListener('DOMContentLoaded', checkInvestorLegal);

// Investment Modal Functions
function openInvestModal(id, title) {
    const modal = document.getElementById('investModal');
    if (!modal) {
        Swal.fire('Indisponivel', 'A funcionalidade de investimentos esta desativada nesta versao.', 'info');
        return;
    }
    // Bloqueio de Segurança: Requer verificação documental (KYC) para investir
    if (typeof enforceKYC === 'function') {
        if (!enforceKYC()) return;
    }

    document.getElementById('investProjectId').value = id;
    document.getElementById('investProjectTitle').innerText = title;
    document.getElementById('investStep1').style.display = 'block';
    document.getElementById('investStep2').style.display = 'none';
    document.getElementById('step1Circle').style.background = 'var(--accent-gold)';
    document.getElementById('step2Circle').style.background = 'var(--glass-border)';
    modal.style.display = 'flex';
}

function closeInvestModal() {
    document.getElementById('investModal').style.display = 'none';
}

function toggleInvestFields(val) {
    document.getElementById('equityFields').style.display = (val === 'equity') ? 'block' : 'none';
    document.getElementById('loanFields').style.display = (val === 'loan') ? 'grid' : 'none';
}

const generateRefForm = document.getElementById('generateRefForm');
if (generateRefForm) generateRefForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando ReferÃªncia...';
    btn.disabled = true;

    const fd = new FormData(this);
    fetch('../../interface_programacao/projects/invest_project.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const formattedRef = String(data.reference || data.investment_id || '').match(/.{1,3}/g).join(' ');
                document.getElementById('paymentRef').innerText = formattedRef;
                document.getElementById('paymentAmountText').innerText = data.formatted_amount;
                document.getElementById('proofInvestmentId').value = data.investment_id;
                document.getElementById('investStep1').style.display = 'none';
                document.getElementById('investStep2').style.display = 'block';
                document.getElementById('step2Circle').style.background = 'var(--accent-gold)';
                document.getElementById('step2Circle').style.color = 'black';
            } else {
                Swal.fire({
                    title: 'AtenÃ§Ã£o',
                    text: data.message,
                    icon: 'warning',
                    background: '#1e293b',
                    color: '#fff'
                });
            }
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
});

const submitProofForm = document.getElementById('submitProofForm');
if (submitProofForm) submitProofForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('../../interface_programacao/projects/upload_investment_proof.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Sucesso!', 'A sua proposta foi enviada. O administrador irÃ¡ validar o pagamento.', 'success')
                    .then(() => location.reload());
            } else {
                Swal.fire('Erro', data.message, 'error');
            }
        });
});

