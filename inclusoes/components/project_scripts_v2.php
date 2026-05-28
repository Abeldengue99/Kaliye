<script>

(function() {
    console.log("Elite Engine v9 Active.");
    let IS_PITCH_VALID = false;
    let HAS_PITCH_FILE = false;
    let PITCH_HARD_INVALID = false;
    window.currentProjectStep = 1;
    var BASE_URL = window.BASE_URL || '../../';

    // NAVEGAÇÃO
    window.changeProjectStep = function(n) {
        let nxt = window.currentProjectStep + n;
        if (nxt < 1) nxt = 1; if (nxt > 4) nxt = 4;
        window.showStep(nxt);
    };

    window.showStep = function(s) {
        document.querySelectorAll('.form-step').forEach(e => e.style.display = 'none');
        if (document.getElementById('step'+s)) document.getElementById('step'+s).style.display = 'block';
        
        const pb = document.getElementById('prevBtn');
        const nb = document.getElementById('nextBtn');
        const sb = document.getElementById('submitBtn');

        if(pb) pb.style.display = (s === 1) ? 'none' : 'block';
        if(nb) nb.style.display = (s === 4) ? 'none' : 'block';
        if(sb) sb.style.display = (s === 4) ? 'block' : 'none';

        for (let i = 1; i <= 4; i++) {
            const dot = document.getElementById('dot' + i);
            if(dot) {
                dot.style.background = (i <= s) ? 'var(--elite-orange)' : 'var(--surface-10)';
                dot.style.height = (i === s) ? '8px' : '5px';
            }
        }
        window.currentProjectStep = s;
    };

    // MOTOR DE ABERTURA (Novo Projecto)
    window.openPostModal = function() {
        const uType = (window.AKSANITI_USER && window.AKSANITI_USER.type) ? window.AKSANITI_USER.type.toLowerCase() : '';
        const isMentorOnly = (uType.includes('mentor') || uType.includes('especialista')) 
                             && !uType.includes('estudante') 
                             && !uType.includes('investidor')
                             && !uType.includes('admin');
        if (isMentorOnly) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acesso Restrito',
                    text: 'Mentores especialistas não podem publicar projectos. Esta funcionalidade é exclusiva para Estudantes, Estudantes-Mentores e Investidores.',
                    background: '#0d1628',
                    color: '#fff'
                });
            } else {
                alert('Mentores especialistas não podem publicar projectos.');
            }
            return;
        }

        if (typeof enforceKYC === 'function' && !enforceKYC()) return;
        const modal = document.getElementById('projectModal');
        const form = document.getElementById('projectForm');
        if (!modal || !form) return;

        form.reset();
        document.getElementById('modal_project_id').value = '';
        form.action = BASE_URL + 'interface_programacao/projects/post_project.php';
        document.getElementById('modalTitleText').innerText = 'Submetera sua Inovação';
        
        // Reset Visuals
        if(document.getElementById('existing-video-container')) document.getElementById('existing-video-container').style.display = 'none';
        const preview = document.getElementById('existing-video-preview');
        const source = document.getElementById('existing-video-source');
        const msg = document.getElementById('videoDurationMsg');
        const fileInput = document.getElementById('projectFileVideo');
        const hint = document.getElementById('videoRequirementHint');
        if (hint) {
            hint.textContent = 'Pitch em video obrigatorio: 10 segundos a 5 minutos. Para publicar, o video precisa aparecer e tocar no player desta tela.';
        }
        if (fileInput) fileInput.value = '';
        if (preview) {
            preview.removeAttribute('src');
            preview.load();
            preview.style.display = 'none';
        }
        if (source) {
            source.removeAttribute('src');
        }
        if (msg) msg.innerHTML = '';
        if(document.getElementById('projectSuccessState')) document.getElementById('projectSuccessState').style.display = 'none';
        if(document.getElementById('projectModalContent')) document.getElementById('projectModalContent').style.display = 'block';
        const sb = document.getElementById('submitBtn');
        if (sb) { sb.disabled = true; sb.innerText = 'FINALIZAR E PUBLICAR'; }
        IS_PITCH_VALID = false;
        HAS_PITCH_FILE = false;
        PITCH_HARD_INVALID = false;
        
        window.showStep(1);
        modal.style.display = 'flex';
    };

    // CRUD: EDITAR PROJECTO
    window.editProject = function(projectId) {
        if (typeof enforceKYC === 'function' && !enforceKYC()) return;
        console.log("Motor de Edição: ID " + projectId);
        const modal = document.getElementById('projectModal');
        const form = document.getElementById('projectForm');
        if (!modal || !form) return;

        form.reset();
        document.getElementById('modal_project_id').value = projectId;
        form.action = BASE_URL + 'interface_programacao/projects/update_project.php'; // SET ACTION
        document.getElementById('modalTitleText').innerText = 'Actualizar Inovação #' + projectId;

        if (document.getElementById('projectSuccessState')) document.getElementById('projectSuccessState').style.display = 'none';
        if (document.getElementById('projectModalContent')) document.getElementById('projectModalContent').style.display = 'block';
        const sb = document.getElementById('submitBtn');
        if (sb) { sb.disabled = true; sb.innerText = 'FINALIZAR E PUBLICAR'; }

        const msg = document.getElementById('videoDurationMsg');
        if (msg) msg.innerHTML = '';

        modal.style.display = 'flex';
        window.showStep(1);

        fetch(BASE_URL + 'interface_programacao/projects/get_project_details.php?id=' + projectId)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const p = data.project;
                    form.title.value = p.title;
                    form.category.value = p.category;
                    form.project_stage.value = p.project_stage;
                    form.team_size.value = p.team_size;
                    form.description.value = p.description;
                    form.target_audience.value = p.target_audience;
                    form.needs_to_advance.value = p.needs_to_advance;
                    form.tags.value = p.tags_csv || '';
                    form.idea_origin.value = p.idea_origin;
                    form.motivation.value = p.motivation;
                    form.execution_time.value = p.execution_time;
                    form.project_url.value = p.project_url;
                    form.budget.value = p.budget_needed;
                    form.funding_goal.value = p.funding_goal;
                    form.minimum_investment.value = p.minimum_investment;
                    form.campaign_end_date.value = p.campaign_end_date ? p.campaign_end_date.split(' ')[0] : '';
                    
                    const videoUrl = p.pitch_video_url || p.video_url;
                    if (videoUrl) {
                        const preview = document.getElementById('existing-video-preview');
                        const container = document.getElementById('existing-video-container');
                        const msg = document.getElementById('videoDurationMsg');
                        const fullPath = videoUrl.startsWith('http') ? videoUrl : BASE_URL + 'carregamentos/projects/' + videoUrl;
                        if (preview) {
                            preview.src = fullPath;
                            preview.currentTime = 0;
                        }
                        container.style.display = 'block';
                        IS_PITCH_VALID = true;
                        HAS_PITCH_FILE = true;
                        PITCH_HARD_INVALID = false;
                        if (sb) sb.disabled = false;
                        if (msg) msg.innerHTML = '<span style="color:#10b981;">Vídeo existente carregado.</span>';
                    }
                }
            });
    };

    window.handleProjectVideoUpload = function(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        HAS_PITCH_FILE = true;
        PITCH_HARD_INVALID = false;
        console.log('[Elite Video] handleProjectVideoUpload selected:', file.name, file.type, file.size);
        const preview = document.getElementById('existing-video-preview');
        const container = document.getElementById('existing-video-container');
        const msg = document.getElementById('videoDurationMsg');
        const btn = document.getElementById('submitBtn');

        if (preview && container) {
            if (preview.dataset.objectUrl) {
                try { URL.revokeObjectURL(preview.dataset.objectUrl); } catch (e) {}
                delete preview.dataset.objectUrl;
            }

            const objectUrl = URL.createObjectURL(file);
            preview.dataset.objectUrl = objectUrl;

            try {
                const oldSource = document.getElementById('existing-video-source');
                if (oldSource) oldSource.remove();
            } catch (e) {}

            preview.pause();
            preview.removeAttribute('src');
            preview.removeAttribute('poster');
            preview.controls = true;
            preview.muted = false;
            preview.preload = 'metadata';
            preview.src = objectUrl;
            preview.style.display = 'block';
            container.style.display = 'block';

            IS_PITCH_VALID = false;
            if (btn) btn.disabled = false;
            if (msg) {
                msg.innerHTML = '<span style="color:#60a5fa;">Ficheiro seleccionado: <strong>' + file.name + '</strong> (' + (file.size / 1024 / 1024).toFixed(2) + ' MB). A preparar pre-visualizacao...</span>';
            }

            preview.onerror = function() {
                IS_PITCH_VALID = false;
                PITCH_HARD_INVALID = false;
                if (btn) btn.disabled = false;
                preview.pause();
                preview.removeAttribute('src');
                preview.load();
                preview.style.display = 'none';
                if (msg) {
                    msg.innerHTML =
                        '<span style="color:#f59e0b;">Este video foi seleccionado, mas não consegue ser reproduzido nesta tela. Pode publicar mesmo assim; se quiser pre-visualizar antes, use MP4/H.264 ou WEBM.</span>';
                }
            };

            preview.onloadedmetadata = function() {
                const dur = preview.duration;
                if (!Number.isFinite(dur) || dur <= 0) {
                    IS_PITCH_VALID = false;
                    PITCH_HARD_INVALID = false;
                    if (btn) btn.disabled = false;
                    if (msg) msg.innerHTML = '<span style="color:#f59e0b;">Não foi possível confirmar a duracao do video nesta tela. O ficheiro sera enviado para validação no servidor.</span>';
                } else if (dur < 10) {
                    IS_PITCH_VALID = false;
                    PITCH_HARD_INVALID = true;
                    if (btn) btn.disabled = false;
                    if (msg) msg.innerHTML = '<span style="color:#ef4444;">Video curto: menos de 10 segundos.</span>';
                } else if (dur > 300) {
                    IS_PITCH_VALID = false;
                    PITCH_HARD_INVALID = true;
                    if (btn) btn.disabled = false;
                    if (msg) msg.innerHTML = '<span style="color:#ef4444;">Video acima do maximo de 5 minutos.</span>';
                } else {
                    IS_PITCH_VALID = true;
                    PITCH_HARD_INVALID = false;
                    if (btn) btn.disabled = false;
                    if (msg) msg.innerHTML = '<span style="color:#10b981;">Video valido: ' + Math.floor(dur / 60) + 'm ' + Math.floor(dur % 60) + 's. Use o play para confirmar antes de publicar.</span>';
                }
                try { preview.currentTime = 0; } catch (e) {}
            };

            preview.oncanplay = function() {
                if (msg && (!preview.duration || preview.duration >= 10)) {
                    msg.innerHTML = '<span style="color:#10b981;">Pre-visualizacao pronta. Use o play para confirmar o video antes de publicar.</span>';
                }
            };

            preview.load();
            return;
        }

        if (false && preview && container) {
            const objectUrl = URL.createObjectURL(file);
            if (preview && typeof preview.canPlayType === 'function') {
                try {
                    const support = preview.canPlayType(file.type || '');
                    console.log('[Elite Video] canPlayType for', file.type, '=>', support);
                    if (!support) {
                        if (msg) msg.innerHTML = '<span style="color:#ef4444;">Aviso: este formato pode não ser suportado pelo seu navegador.</span>';
                    }
                } catch (e) { console.warn('[Elite Video] canPlayType check failed', e); }
            }
            console.log('[Elite Video] objectUrl created:', objectUrl);
            preview.src = objectUrl;
            console.log('[Elite Video] preview.src set');
            preview.currentTime = 0;
            preview.style.display = 'block';
            container.style.display = 'block';
            if (btn) btn.disabled = false; // permitir envio imediato mesmo que validação do metadata ainda não tenha acontecido
            if (msg) msg.innerHTML = '<span style="color:#60a5fa;">A carregar vídeo para validação... (pode enviar mesmo assim)</span>';
            preview.onerror = function() {
                console.log('[Elite Video] preview.onerror — attempting FileReader fallback');
                IS_PITCH_VALID = false;
                if (btn) btn.disabled = false;
                if (msg) msg.innerHTML = '<span style="color:#f59e0b;">O ficheiro foi seleccionado. A pre-visualizacao depende do navegador, mas o envio continua permitido.</span>';

                // Remove any <source> child to avoid conflicts when assigning src directly
                try {
                    const srcEl = document.getElementById('existing-video-source');
                    if (srcEl) { srcEl.removeAttribute('src'); }
                } catch (e) { console.warn('[Elite Video] could not clear <source>', e); }

                // Attempt FileReader fallback to produce a data: URL
                try {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        try {
                            preview.src = evt.target.result;
                            preview.load();
                            // allow metadata to drive validation
                            if (msg) msg.innerHTML = '<span style="color:#60a5fa;">Fallback carregado — a validar...</span>';
                        } catch (e) {
                            console.error('[Elite Video] FileReader fallback failed', e);
                            if (msg) msg.innerHTML = '<span style="color:#ef4444;">Fallback falhou — ficheiro não reproduzível.</span>';
                        }
                    };
                    reader.onerror = function(err) {
                        console.error('[Elite Video] FileReader error', err);
                        if (msg) msg.innerHTML = '<span style="color:#ef4444;">Não foi possível ler o ficheiro localmente.</span>';
                    };
                    reader.readAsDataURL(file);
                } catch (e) {
                    console.error('[Elite Video] no FileReader available', e);
                    if (preview) {
                        preview.removeAttribute('src');
                        preview.load();
                    }
                }

                try { URL.revokeObjectURL(objectUrl); } catch (e) {}
            };
            preview.onloadedmetadata = function() {
                console.log('[Elite Video] onloadedmetadata, duration=', preview.duration);
                const dur = preview.duration;
                if (dur < 10) {
                    IS_PITCH_VALID = false;
                    msg.innerHTML = `<span style="color:#ef4444;">🔴 Curto (< 10s)</span>`;
                    if (btn) btn.disabled = false;
                } else if (dur > 300) {
                    IS_PITCH_VALID = false;
                    msg.innerHTML = `<span style="color:#ef4444;">🔴 Máximo 5 mins.</span>`;
                    if (btn) btn.disabled = false;
                } else {
                    IS_PITCH_VALID = true;
                    msg.innerHTML = `<span style="color:#10b981;">✅ Válido: (${Math.floor(dur/60)}m ${Math.floor(dur%60)}s)</span>`;
                    if (btn) btn.disabled = false;
                }
                URL.revokeObjectURL(objectUrl);
            };
            preview.load();
            preview.play().catch(() => {});
        }
    };

    window.initEliteSubmit = function() {
        const form = document.getElementById('projectForm');
        if(!form) return;
        const hint = document.getElementById('videoRequirementHint');
        if (hint) {
            hint.textContent = 'Pitch em video obrigatorio: 10 segundos a 5 minutos. Para publicar, o video precisa aparecer e tocar no player desta tela.';
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Submetendo formulário para: " + form.action);

            function sendFormBypass() {
                const sb = document.getElementById('submitBtn');
                const progBar = document.getElementById('uploadProgressBar');
                const progContainer = document.getElementById('uploadProgressContainer');
                const formData = new FormData(form);
                formData.append('json', '1');

                if (sb) {
                    sb.disabled = true;
                    sb.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SINCRONIZANDO...';
                }
                if (progContainer) progContainer.style.display = 'block';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);
                xhr.upload.onprogress = function(ev) {
                    if (ev.lengthComputable && progBar) {
                        progBar.style.width = ((ev.loaded / ev.total) * 100) + '%';
                    }
                };
                xhr.onload = function() {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            if (document.getElementById('projectModalContent')) document.getElementById('projectModalContent').style.display = 'none';
                            if (document.getElementById('projectSuccessState')) document.getElementById('projectSuccessState').style.display = 'block';
                            setTimeout(() => {
                                const target = res.redirect_url ? (BASE_URL + res.redirect_url.replace(/^(\.\.\/)+/, '')) : (BASE_URL + 'paginas/explorar/my_projects.php?success=project_pending');
                                window.location.href = target;
                            }, 1200);
                        } else {
                            throw new Error(res.error || res.message || 'Verifique o servidor.');
                        }
                    } catch (err) {
                        Swal.fire({ icon: 'error', title: 'Erro de Submissão', text: err.message || 'Verifique o servidor.', background: '#0d1628', color: '#fff' });
                        if (sb) {
                            sb.disabled = false;
                            sb.innerHTML = 'FINALIZAR E PUBLICAR';
                        }
                        if (progContainer) progContainer.style.display = 'none';
                    }
                };
                xhr.onerror = function() {
                    Swal.fire({ icon: 'error', title: 'Falha de rede', text: 'Não foi possível enviar o projecto. Verifique a ligação e tente novamente.', background: '#0d1628', color: '#fff' });
                    if (sb) {
                        sb.disabled = false;
                        sb.innerHTML = 'FINALIZAR E PUBLICAR';
                    }
                    if (progContainer) progContainer.style.display = 'none';
                };
                xhr.send(formData);
            }

            const fileInput = document.getElementById('projectFileVideo');
            const hasSelectedPitch = HAS_PITCH_FILE || (fileInput && fileInput.files && fileInput.files[0]);
            if (PITCH_HARD_INVALID) {
                Swal.fire({
                    icon: 'error',
                    title: 'Pitch fora dos limites',
                    text: 'O pitch em video precisa ter entre 10 segundos e 5 minutos.',
                    background: '#0d1628',
                    color: '#fff',
                    confirmButtonColor: '#f7941d'
                });
                window.showStep(4);
                if (fileInput) fileInput.focus();
                return;
            }

            if (!IS_PITCH_VALID && !hasSelectedPitch) {
                Swal.fire({
                    icon: 'error',
                    title: 'Pitch em vídeo obrigatório',
                    text: 'Por favor, carregue um pitch em vídeo antes de publicar o projecto.',
                    background: '#0d1628',
                    color: '#fff',
                    confirmButtonColor: '#f7941d'
                });
                window.showStep(4);
                if (fileInput) fileInput.focus();
                const btn = document.getElementById('submitBtn'); if (btn) btn.disabled = true;
                return;
            }

            if (!IS_PITCH_VALID && hasSelectedPitch) {
                const msg = document.getElementById('videoDurationMsg');
                if (msg) {
                    msg.innerHTML = '<span style="color:#f59e0b;">Video seleccionado. A publicar sem pre-visualizacao local.</span>';
                }
            }

            const sb = document.getElementById('submitBtn');
            const progBar = document.getElementById('uploadProgressBar');
            const progContainer = document.getElementById('uploadProgressContainer');
            
            sb.disabled = true; sb.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SINCRONIZANDO...';
            if (progContainer) progContainer.style.display = 'block';

            const formData = new FormData(form);
            formData.append('json', '1');

            // Use helper to allow bypassed submission from confirmation flow
            function sendForm() {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);

                xhr.upload.onprogress = function(ev) {
                    if (ev.lengthComputable && progBar) {
                        const pct = (ev.loaded / ev.total) * 100;
                        progBar.style.width = pct + '%';
                    }
                };

                xhr.onload = function() {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            if (document.getElementById('projectModalContent')) document.getElementById('projectModalContent').style.display = 'none';
                            if (document.getElementById('projectSuccessState')) document.getElementById('projectSuccessState').style.display = 'block';
                            setTimeout(() => {
                                const target = res.redirect_url ? (BASE_URL + res.redirect_url.replace(/^(\.\.\/)+/, '')) : (BASE_URL + 'paginas/explorar/my_projects.php?success=project_pending');
                                window.location.href = target;
                            }, 1200);
                        } else { throw new Error(res.error); }
                    } catch (err) {
                        Swal.fire({ icon: 'error', title: 'Erro de Submissão', text: err.message || 'Verifique o servidor.', background: '#0d1628', color: '#fff' });
                        sb.disabled = false; sb.innerHTML = 'FINALIZAR E PUBLICAR';
                        if (progContainer) progContainer.style.display = 'none';
                    }
                };

                xhr.onerror = function() {
                    Swal.fire({ icon: 'error', title: 'Falha de rede', text: 'Não foi possível enviar o projecto. Verifique a ligacao e tente novamente.', background: '#0d1628', color: '#fff' });
                    sb.disabled = false;
                    sb.innerHTML = 'FINALIZAR E PUBLICAR';
                    if (progContainer) progContainer.style.display = 'none';
                };

                xhr.send(formData);
            }

            // If we arrive here normally and validation passed, send immediately
            sendForm();
        });
    };

    window.initEliteSubmit();
    
    // Debug helper: envia somente o ficheiro de vídeo para o endpoint de debug
    window.debugUpload = async function() {
        try {
            const input = document.getElementById('projectFileVideo');
            if (!input || !input.files || !input.files[0]) { console.log('debugUpload: nenhum ficheiro seleccionado'); return; }
            const fd = new FormData();
            fd.append('project_video', input.files[0]);
            const resp = await fetch(BASE_URL + 'interface_programacao/projects/debug_upload.php', { method: 'POST', body: fd });
            const data = await resp.json();
            console.log('debugUpload response:', data);
            alert('Debug upload: ' + (data.success ? ('OK — ' + (data.moved_to || 'moved')) : ('FAIL — ' + (data.error || 'unknown'))));
        } catch (e) { console.error('debugUpload error', e); alert('Erro debugUpload: ' + e.message); }
    };
})();
</script>
