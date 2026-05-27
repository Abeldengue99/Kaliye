/**
 * mentorship.js
 * Handles dashboard interactions, tabs, and data loading for the Mentorship page.
 */

// Global State
let mentorshipState = {
    currentView: 'mentee' // 'mentee' or 'mentor'
};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Initialize view based on URL params or PHP initialView
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get('view') || (typeof initialView !== 'undefined' ? initialView : 'mentee');
    const tabParam = urlParams.get('tab') || 'tasks';

    setDashboardView(viewParam, tabParam);

    // Setup generic modal closers
    document.querySelectorAll('.modal-close-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal');
            if (modal) modal.classList.remove('active');
        });
    });

    // Forms Setup
    const forms = {
        'addSlotForm': handleAddSlot,
        'addTaskForm': handleAddTask,
        'addResourceForm': handleAddResource,
        'addNoticeForm': handleAddNotice,
        'offerMentorshipForm': handleOfferMentorship
    };

    Object.entries(forms).forEach(([id, handler]) => {
        const form = document.getElementById(id);
        if (form) form.addEventListener('submit', handler);
    });

    // Star Rating
    document.querySelectorAll('.star-rating').forEach(star => {
        star.addEventListener('click', function () {
            const val = this.dataset.value;
            const field = document.getElementById('rating_input');
            if (field) field.value = val;

            // UI Update
            const parent = this.parentElement;
            parent.querySelectorAll('.star-rating').forEach(s => {
                if (s.dataset.value <= val) {
                    s.classList.replace('far', 'fas');
                    s.style.color = 'var(--accent-gold)';
                } else {
                    s.classList.replace('fas', 'far');
                    s.style.color = '#ccc';
                }
            });
        });
    });

    // File input filename display
    const resFileInput = document.getElementById('resourceFileInput');
    if (resFileInput) {
        resFileInput.addEventListener('change', function() {
            const display = document.getElementById('fileNameDisplay');
            if (display && this.files.length > 0) {
                display.textContent = this.files[0].name;
                display.style.color = '#f7941d';
            }
        });
    }
});

/**
 * Switch between Mentor and Mentee views
 * @param {string} role - 'mentor' or 'mentee'
 * @param {string} preferredTab - optional tab to switch to
 */
function setDashboardView(role, preferredTab = null) {
    mentorshipState.currentView = role;

    const menteeBtn = document.getElementById('viewAsMentee');
    const mentorBtn = document.getElementById('viewAsMentor');
    const cascadeSec = document.getElementById('cascadeSection');

    // Toggle Button Styles
    if (role === 'mentor') {
        if (mentorBtn) mentorBtn.classList.add('active');
        if (menteeBtn) menteeBtn.classList.remove('active');

        if (cascadeSec) cascadeSec.style.display = 'block';

        // Show Mentor-specific tabs
        document.querySelectorAll('.m-tab[data-tab="assignments"], .m-tab[data-tab="legal"], .m-tab[data-tab="projects"]').forEach(tab => {
            tab.style.display = 'flex';
        });

        // Show all mentor buttons
        document.querySelectorAll('.mentor-only-btn').forEach(btn => btn.style.display = 'inline-flex');

    } else {
        if (menteeBtn) menteeBtn.classList.add('active');
        if (mentorBtn) mentorBtn.classList.remove('active');

        if (cascadeSec) cascadeSec.style.display = 'none';

        // Hide Mentor-specific tabs
        document.querySelectorAll('.m-tab[data-tab="assignments"], .m-tab[data-tab="legal"], .m-tab[data-tab="projects"]').forEach(tab => {
            tab.style.display = 'none';
        });

        // If active tab was hidden, switch to tasks
        const activeTab = document.querySelector('.m-tab.active');
        if (activeTab && (activeTab.dataset.tab === 'assignments' || activeTab.dataset.tab === 'legal' || activeTab.dataset.tab === 'projects')) {
            switchMentorTab('tasks');
        }

        // Hide mentor buttons
        document.querySelectorAll('.mentor-only-btn').forEach(btn => btn.style.display = 'none');
    }

    // Refresh current active tab or switch to preferred
    if (preferredTab) {
        switchMentorTab(preferredTab);
    } else {
        const activeTab = document.querySelector('.m-tab.active');
        switchMentorTab(activeTab ? activeTab.dataset.tab : 'tasks');
    }
}

/**
 * Switch tabs within the dashboard
 * @param {string} tabId 
 * @param {HTMLElement} clickedElement 
 */
function switchMentorTab(tabId, clickedElement) {
    // Hide all contents
    document.querySelectorAll('.mentor-tab-content').forEach(c => c.style.display = 'none');

    // Deactivate all tabs
    document.querySelectorAll('.m-tab').forEach(t => t.classList.remove('active'));

    // Activate target
    const targetContent = document.getElementById('tab-' + tabId);
    if (targetContent) targetContent.style.display = 'block';

    if (clickedElement) {
        clickedElement.classList.add('active');
    } else {
        const tabBtn = document.querySelector(`.m-tab[data-tab="${tabId}"]`);
        if (tabBtn) tabBtn.classList.add('active');
    }

    // Load Data
    const loaders = {
        'tasks': loadTasks,
        'scheduler': loadSlots,
        'resources': loadResources,
        'notices': loadNotices,
        'assignments': loadAssignments,
        'legal': loadLegalAgreements,
        'projects': loadProjectReviews
    };

    if (loaders[tabId]) loaders[tabId]();
}

/* --- Data Loading Functions --- */

async function loadTasks() {
    const list = document.getElementById('tasksList');
    if (!list) return;
    list.innerHTML = '<div class="loading-spinner"></div>';

    const data = await fetchApi(`../../interface_programacao/mentorship/get_tasks.php?view=${mentorshipState.currentView}`);
    if (data.success && data.tasks) {
        if (data.tasks.length === 0) {
            list.innerHTML = '<div class="empty-state">Nenhuma tarefa encontrada.</div>';
            return;
        }
        list.innerHTML = data.tasks.map(t => createTaskCard(t)).join('');
    } else {
        list.innerHTML = `<div class="error-state">${data.message || 'Erro ao carregar tarefas.'}</div>`;
    }
}

function createTaskCard(t) {
    const isMentee = mentorshipState.currentView === 'mentee';
    const isCompleted = t.status === 'completed';
    const statusIcon = isCompleted ? 'fa-check-circle' : 'fa-clock';
    
    return `
        <div class="task-card fade-in ${t.status}">
            <div class="task-header">
                <span class="status-badge ${t.status}">
                    <i class="fas ${statusIcon}"></i> ${t.status.toUpperCase()}
                </span>
                <span class="task-date">${new Date(t.created_at).toLocaleDateString()}</span>
            </div>
            <h4>${t.task_name}</h4>
            <p>${t.description}</p>
            ${isMentee && !isCompleted ?
            `<button onclick="completeTask(${t.task_id})" class="btn-primary-small" style="width:100%; margin-top:1rem; padding: 0.8rem; border-radius: 14px;">
                <i class="fas fa-check"></i> Marcar como Concluída
            </button>`
            : ''}
            ${isCompleted ? 
            `<div style="margin-top: 1rem; text-align: center; color: #10b981; font-size: 0.8rem; font-weight: 700;">
                <i class="fas fa-award"></i> Tarefa Validada
            </div>` 
            : ''}
        </div>
    `;
}

async function loadSlots() {
    const list = document.getElementById('slotsList');
    if (!list) return;
    list.innerHTML = '<div class="loading-spinner"></div>';

    const data = await fetchApi(`../../interface_programacao/mentorship/get_mentorship_slots.php?view=${mentorshipState.currentView}`);
    if (data.success && data.slots) {
        if (data.slots.length === 0) {
            list.innerHTML = '<div class="empty-state">Nenhuma sessão agendada.</div>';
            return;
        }
        list.innerHTML = data.slots.map(s => createSlotCard(s)).join('');
    } else {
        list.innerHTML = `<div class="error-state">${data.message || 'Erro ao carregar agenda.'}</div>`;
    }
}

function createSlotCard(s) {
    const roomParam = s.meeting_room ? `?room=${s.meeting_room}` : '';
    const meetingUrl = roomParam ? `meeting.php${roomParam}` : (s.meeting_link || '#');
    const isAvailable = s.status === 'available';
    const isMentee = mentorshipState.currentView === 'mentee';
    const isMentor = mentorshipState.currentView === 'mentor';

    // Formatting date
    const dateObj = new Date(s.start_time);
    const dateStr = dateObj.toLocaleDateString('pt-PT', { day: '2-digit', month: 'short' });
    const timeStr = dateObj.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });

    // Countdown Logic
    const startTime = new Date(s.start_time);
    const now = new Date();
    const diffMs = startTime - now;
    const diffMins = Math.floor(diffMs / 60000);
    const isToday = startTime.toDateString() === now.toDateString();
    const canJoin = diffMins <= 10 && diffMs > -(s.duration || 60) * 60000;

    let actionHtml = '';
    if (s.status === 'confirmed') {
        if (canJoin) {
            actionHtml = `
                <a href="${meetingUrl}" target="_blank" class="btn-join fade-in">
                    <i class="fas fa-video"></i> Entrar Agora
                </a>
            `;
        } else if (diffMs > 0) {
            if (isToday) {
                actionHtml = `<div class="countdown-badge" data-start="${startTime.getTime()}"><i class="fas fa-clock"></i> Começa em ${diffMins} min</div>`;
            } else {
                const daysLeft = Math.ceil(diffMs / (1000 * 60 * 60 * 24));
                actionHtml = `<div class="countdown-badge gray"><i class="fas fa-calendar"></i> Em ${daysLeft} d</div>`;
            }
        }
    }

    return `
    <div class="slot-card fade-in ${s.status}">
        <div class="slot-time">
            <div class="date">${dateStr}</div>
            <div class="time">${timeStr}</div>
        </div>
        
        <div class="slot-details">
            <div class="slot-header-row">
                <h4 class="slot-title">${s.title || 'Sessão de Mentoria'}</h4>
                <span class="badge ${s.status}">${s.status === 'booked' ? 'PENDENTE' : s.status.toUpperCase()}</span>
            </div>
            
            <div style="display: flex; gap: 0.8rem; margin: 2px 0;">
                <span class="badge-mini"><i class="fas fa-tag"></i> ${s.category || 'Geral'}</span>
                <span class="badge-mini"><i class="fas fa-clock"></i> ${s.duration || '60'} min</span>
            </div>

            <div class="slot-participants">
                <i class="fas fa-user-tie"></i> 
                <span>${s.mentor_name} ${s.participant_name ? `<i class="fas fa-chevron-right" style="font-size: 0.7rem; margin: 0 4px;"></i> ${s.participant_name}` : ''}</span>
            </div>
            
            ${s.description ? `<p style="margin: 8px 0 0; font-size: 0.8rem; color: var(--surface-40); line-height: 1.4; font-style: italic;">"${s.description}"</p>` : ''}
        </div>
        
        <div class="slot-actions">
            ${actionHtml}

            ${s.status === 'booked' && isMentor ? `
                <button onclick="confirmBooking(${s.slot_id})" class="btn-join" style="background: linear-gradient(135deg, #10b981, #059669); border:none;">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            ` : ''}
            
            ${isAvailable && isMentee ? `
                <button onclick="bookSlot(${s.slot_id})" class="btn-book">
                    <i class="fas fa-bookmark"></i> Reservar
                </button>
            ` : ''}
            
            ${isMentor ? `
                <button onclick="deleteSlot(${s.slot_id})" class="btn-delete" title="Eliminar Horário">
                    <i class="fas fa-trash"></i>
                </button>
            ` : ''}
        </div>
    </div>
    `;
}

// Data loading helpers
async function fetchApi(url, options = {}) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    } catch (e) {
        console.error("API Error:", e);
        return { success: false, error: e.message };
    }
}

async function loadResources() {
    const list = document.getElementById('resourcesList');
    if (!list) return;
    list.innerHTML = '<div class="loading-spinner"></div>';

    const data = await fetchApi(`../../interface_programacao/mentorship/get_mentor_resources.php?view=${mentorshipState.currentView}`);
    if (data.success && data.resources) {
        if (data.resources.length === 0) {
            const msg = mentorshipState.currentView === 'mentor' ? 'Ainda não partilhou nenhum material.' : 'Nenhum material partilhado consigo.';
            list.innerHTML = `<div class="empty-state">${msg}</div>`;
            return;
        }
        list.innerHTML = data.resources.map(r => `
            <div class="resource-card fade-in">
                <i class="fas ${getResourceIcon(r.file_type)}"></i>
                <div class="resource-info">
                    <h4>${r.title}</h4>
                    <p>${r.description || 'Sem descrição.'}</p>
                    <div style="margin-top: 5px; font-size: 0.75rem; color: var(--surface-40);">
                        <i class="fas fa-calendar-alt" style="font-size: 0.7rem;"></i> ${new Date(r.created_at).toLocaleDateString()}
                    </div>
                </div>
                <a href="${r.file_url.startsWith('http') ? r.file_url : '../' + r.file_url}" download class="btn-primary-small">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        `).join('');
    } else {
        list.innerHTML = '<div class="error-state">Erro ao carregar materiais.</div>';
    }
}

function getResourceIcon(type) {
    const t = type.toLowerCase();
    if (t.includes('pdf')) return 'fa-file-pdf';
    if (t.includes('image') || t.includes('jpg') || t.includes('png')) return 'fa-file-image';
    if (t.includes('video') || t.includes('mp4')) return 'fa-file-video';
    if (t.includes('word') || t.includes('doc')) return 'fa-file-word';
    if (t.includes('zip') || t.includes('rar')) return 'fa-file-archive';
    return 'fa-file-alt';
}

async function loadNotices() {
    const list = document.getElementById('noticesList');
    if (!list) return;
    list.innerHTML = '<div class="loading-spinner"></div>';

    const data = await fetchApi(`../../interface_programacao/mentorship/get_mentor_notices.php?view=${mentorshipState.currentView}`);
    if (data.success && data.notices) {
        if (data.notices.length === 0) {
            const msg = mentorshipState.currentView === 'mentor' ? 'Ainda não postou nenhum aviso.' : 'O mural está vazio.';
            list.innerHTML = `<div class="empty-state">${msg}</div>`;
            return;
        }
        list.innerHTML = data.notices.map(n => `
            <div class="notice-card fade-in">
                <div class="notice-header">
                    <span class="author"><i class="fas fa-user-circle"></i> ${n.author_name}</span>
                    <span class="date">${new Date(n.created_at).toLocaleDateString('pt-PT', { day: '2-digit', month: 'long', year: 'numeric' })}</span>
                </div>
                <p>${n.content}</p>
            </div>
        `).join('');
    } else {
        list.innerHTML = '<div class="error-state">Erro ao carregar avisos.</div>';
    }
}

async function loadAssignments() {
    const list = document.getElementById('assignmentsList');
    if (!list) return;
    list.innerHTML = '<div class="loading-spinner"></div>';

    const data = await fetchApi(`../../interface_programacao/mentorship/get_mentor_assignments.php`);
    if (data.success && data.assignments) {
        if (data.assignments.length === 0) {
            list.innerHTML = '<div class="empty-state">Sem atribuições pendentes.</div>';
            return;
        }
        list.innerHTML = data.assignments.map(a => `
            <div class="assignment-card glass fade-in">
                <h4>${a.title}</h4>
                <p>${a.description}</p>
                <div class="status-badge ${a.status}">${a.status}</div>
            </div>
        `).join('');
    } else {
        list.innerHTML = '<div class="error-state">Erro ao carregar atribuições.</div>';
    }
}

function loadLegalAgreements() {
    document.getElementById('legalList').innerHTML = '<div class="empty-state">Documentação jurídica em processamento.</div>';
}

/* --- Updates & Actions --- */

function handleAddSlot(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('../../interface_programacao/mentorship/add_mentorship_slot.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Sucesso!', 'Horário adicionado.', 'success');
                closeModal('addSlotModal');
                loadSlots();
            } else {
                Swal.fire('Erro', data.error || 'Falha ao adicionar.', 'error');
            }
        });
}

function handleAddTask(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('../../interface_programacao/mentorship/add_mentee_task.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Sucesso!', 'Tarefa atribuída.', 'success');
                closeModal('addTaskModal');
                loadTasks();
            } else {
                Swal.fire('Erro', data.error || 'Falha ao atribuir.', 'error');
            }
        });
}

function handleAddResource(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('../../interface_programacao/mentorship/add_mentor_resource.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Sucesso!', 'Material enviado.', 'success');
                closeModal('addResourceModal');
                loadResources();
            } else {
                Swal.fire('Erro', data.error || 'Falha ao enviar.', 'error');
            }
        });
}

function handleAddNotice(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('../../interface_programacao/mentorship/add_mentor_notice.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Sucesso!', 'Aviso postado.', 'success');
                closeModal('addNoticeModal');
                loadNotices();
            } else {
                Swal.fire('Erro', data.error || 'Falha ao postar.', 'error');
            }
        });
}

function bookSlot(slotId) {
    Swal.fire({
        title: 'Confirmar Reserva?',
        text: 'Marcar mentoria para este horário?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, Reservar',
        background: '#1e293b',
        color: '#fff'
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('slot_id', slotId);
            fetch('../../interface_programacao/mentorship/book_mentorship_slot.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Reservado!', 'Sessão marcada.', 'success');
                        loadSlots();
                    } else {
                        Swal.fire('Erro', data.message || 'Falha.', 'error');
                    }
                });
        }
    });
}

function completeTask(taskId) {
    Swal.fire({
        title: 'Tarefa Concluída?',
        text: 'Confirmar finalização desta tarefa.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, Concluir',
        background: '#1e293b',
        color: '#fff'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(`../../interface_programacao/mentorship/complete_task.php?id=${taskId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Parabéns!', 'Tarefa concluída com sucesso.', 'success');
                        loadTasks();
                    } else {
                        Swal.fire('Erro', data.message || 'Falha ao atualizar.', 'error');
                    }
                });
        }
    });
}

function deleteSlot(slotId) {
    Swal.fire({
        title: 'Remover?',
        text: 'Irreversível.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Sim, Remover',
        background: '#1e293b',
        color: '#fff'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(`../../interface_programacao/mentorship/delete_mentorship_slot.php?id=${slotId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Swal.fire('Removido', '', 'success');
                        loadSlots();
                    }
                });
        }
    });
}

// Modal Helpers
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        // Populate mentee selectors for any of these modals
        const menteeModals = ['addSlotModal', 'addTaskModal', 'addResourceModal', 'addNoticeModal'];
        if (menteeModals.includes(id)) populateMenteeSelect();
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

function populateMenteeSelect() {
    const sel = document.getElementById('participantSelect');
    const taskSel = document.getElementById('task_student_id');
    const resArea = document.getElementById('menteeSelectResource');
    const noticeArea = document.getElementById('menteeSelectNotice');

    fetch('../../interface_programacao/mentorship/get_my_mentees.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.mentees) {
                // For Slot Select
                if (sel) {
                    let options = '<option value="">-- Horário Livre --</option>';
                    options += data.mentees.map(m => `<option value="${m.user_id}">${m.full_name}</option>`).join('');
                    sel.innerHTML = options;
                }

                // For Task Select
                if (taskSel) {
                    let options = '<option value="">Selecionar Estudante...</option>';
                    options += data.mentees.map(m => `<option value="${m.user_id}">${m.full_name}</option>`).join('');
                    taskSel.innerHTML = options;
                }

                // For Resource/Notice Checkboxes
                let checkboxHtml = '';
                if (data.mentees.length === 0) {
                    checkboxHtml = '<p style="color: rgba(255,255,255,0.4); font-size: 0.85rem; margin: 0;">Ainda não tem mentorados ativos (sem reuniões ou tarefas).<br><small>Quando tiver estudantes, poderá partilhar materiais diretamente aqui.</small></p>';
                } else {
                    checkboxHtml += `
                    <label class="checkbox-item" style="grid-column: 1 / -1; margin-bottom: 0.5rem; background: rgba(247, 148, 29, 0.1); padding: 0.5rem; border-radius: 8px;">
                        <input type="checkbox" onchange="document.querySelectorAll('.mentee-checkbox').forEach(cb => cb.checked = this.checked)">
                        <span style="font-size: 0.9rem; font-weight: bold; color: #f7941d;">Partilhar com o Grupo Inteiro (Todos Mentorados)</span>
                    </label>`;
                    
                    checkboxHtml += data.mentees.map(m => `
                    <label class="checkbox-item">
                        <input type="checkbox" name="mentee_ids[]" value="${m.user_id}" class="mentee-checkbox">
                        <span style="font-size: 0.85rem;">${m.full_name}</span>
                    </label>
                    `).join('');
                }

                if (resArea) {
                    resArea.className = data.mentees.length > 0 ? 'checkbox-list' : 'empty-state-small';
                    resArea.innerHTML = checkboxHtml;
                }
                if (noticeArea) {
                    noticeArea.className = data.mentees.length > 0 ? 'checkbox-list' : 'empty-state-small';
                    noticeArea.innerHTML = checkboxHtml;
                }
            }
        });
}

/**
 * Open Modal to View Specific Mentor's Slots
 */
async function openMentorSlotsModal(mentorId, mentorName, mentorPhoto, mentorSpec) {
    const modalId = 'requestMentorshipModal';
    const list = document.getElementById('mentorAvailableSlotsList');
    const nameEl = document.getElementById('mentorSlotsName');
    const photoEl = document.getElementById('mentorPic');
    const specEl = document.getElementById('mentorSlotsSpecialty');
    const linkEl = document.getElementById('mentorProfileLink');

    if (nameEl) nameEl.textContent = mentorName;
    if (specEl) specEl.textContent = mentorSpec || 'Mentor';
    if (linkEl) linkEl.href = `profile.php?user_id=${mentorId}`;

    if (photoEl) {
        if (mentorPhoto) {
            photoEl.innerHTML = `<img src="${mentorPhoto}" style="width:100%; height:100%; object-fit:cover;">`;
        } else {
            photoEl.innerHTML = `<i class="fas fa-user-tie" style="font-size: 2.5rem; line-height: 80px; color: var(--text-secondary);"></i>`;
        }
    }

    if (list) list.innerHTML = '<div class="loading-spinner"></div>';

    openModal(modalId);

    const data = await fetchApi(`../../interface_programacao/mentorship/get_mentorship_slots.php?mentor_id=${mentorId}`);

    if (data.success && data.slots) {
        // Show Available OR Booked (Pending) by CURRENT USER. Hide Confirmed/Other's bookings.
        const visibleSlots = data.slots.filter(s =>
            s.status === 'available' ||
            (s.status === 'booked' && s.participant_id == currentUserId)
        );

        if (visibleSlots.length === 0) {
            list.innerHTML = '<div class="empty-state">Este mentor não possui novos horários disponíveis no momento.</div>';
            return;
        }

        list.innerHTML = visibleSlots.map(s => {
            const dateObj = new Date(s.start_time);
            const dateStr = dateObj.toLocaleDateString('pt-PT', { day: '2-digit', month: 'short', year: 'numeric' });
            const timeStr = dateObj.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
            const isPending = s.status === 'booked';

            return `
                <div class="slot-card glass fade-in" style="margin-bottom: 0.75rem; padding: 1rem;">
                    <div class="slot-time" style="padding-right: 1rem; min-width: 70px;">
                        <div class="date" style="font-size: 0.75rem;">${dateStr}</div>
                        <div class="time" style="font-size: 1rem;">${timeStr}</div>
                    </div>
                    <div class="slot-participants" style="flex: 1;">
                        <strong style="font-size: 0.95rem;">${s.title || 'Sessão de Mentoria'}</strong>
                        <div style="display: flex; gap: 0.5rem; margin-top: 4px;">
                            <span class="badge-mini"><i class="fas fa-tag"></i> ${s.category || 'Geral'}</span>
                            <span class="badge-mini"><i class="fas fa-clock"></i> ${s.duration || '60'} min</span>
                        </div>
                    </div>
                    ${isPending ? `
                        <button class="btn-primary-small" style="background: rgba(247, 148, 29, 0.1); color: #f7941d; border: 1px solid #f7941d; cursor: default; pointer-events: none;">
                            <i class="fas fa-clock"></i> Pendente
                        </button>
                    ` : `
                        <button onclick="bookSlot(${s.slot_id})" class="btn-book" style="padding: 0.5rem 1rem; border-radius: 8px;">
                            Reservar
                        </button>
                    `}
                </div>
            `;
        }).join('');
    } else {
        list.innerHTML = '<div class="error-state">Erro ao carregar agenda do mentor.</div>';
    }
}

async function confirmBooking(slotId) {
    const result = await Swal.fire({
        title: 'Confirmar Mentoria?',
        text: 'Ao confirmar, o estudante será notificado e receberá o link da sala.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, Confirmar',
        background: '#1e293b',
        color: '#fff'
    });

    if (result.isConfirmed) {
        const formData = new FormData();
        formData.append('slot_id', slotId);

        const data = await fetchApi('../../interface_programacao/mentorship/confirm_mentorship_booking.php', {
            method: 'POST',
            body: formData
        });

        if (data.success) {
            Swal.fire('Sucesso!', 'Mentoria confirmada! O link foi enviado.', 'success');
            loadSlots(); // Refresh current view
        } else {
            console.error("Confirmation failed:", data);
            Swal.fire('Erro', data.error || 'Falha ao confirmar.', 'error');
        }
    }
}

/**
 * Handle Offer Mentorship (Mentor Offering to Student)
 */
async function openOfferMentorship(studentId, studentName, studentPhoto, studentSpec) {
    const modalId = 'offerMentorshipModal';
    const nameEl = document.getElementById('offerStudentName');
    const selectEl = document.getElementById('offerSlotSelect');
    const idInput = document.getElementById('offer_student_id');
    const photoEl = document.getElementById('offerStudentPic');
    const specEl = document.getElementById('offerStudentSpecialty');

    if (nameEl) nameEl.textContent = studentName;
    if (specEl) specEl.textContent = studentSpec || 'Estudante';
    if (idInput) idInput.value = studentId;

    if (photoEl) {
        if (studentPhoto) {
            photoEl.innerHTML = `<img src="${studentPhoto}" style="width:100%; height:100%; object-fit:cover;">`;
        } else {
            photoEl.innerHTML = `<i class="fas fa-user-graduate" style="font-size: 2rem; line-height: 70px; color: var(--text-secondary);"></i>`;
        }
    }

    if (selectEl) selectEl.innerHTML = '<option value="">Carregando...</option>';

    openModal(modalId);

    // Load current mentor's available slots
    const data = await fetchApi(`../../interface_programacao/mentorship/get_mentorship_slots.php?view=mentor`);

    if (data.success && data.slots) {
        const available = data.slots.filter(s => s.status === 'available');
        if (available.length === 0) {
            selectEl.innerHTML = '<option value="">Crie um horário na sua agenda primeiro.</option>';
        } else {
            selectEl.innerHTML = '<option value="">Selecionar Horário...</option>' + available.map(s => {
                const d = new Date(s.start_time);
                return `<option value="${s.slot_id}">${d.toLocaleDateString()} ${d.getHours()}:${d.getMinutes().toString().padStart(2, '0')} - ${s.title || 'Sessão'}</option>`;
            }).join('');
        }
    } else {
        selectEl.innerHTML = '<option value="">Erro ao carregar horários.</option>';
    }
}

async function handleOfferMentorship(e) {
    if (e) e.preventDefault();
    const form = document.getElementById('offerMentorshipForm');
    const formData = new FormData(form);

    const data = await fetchApi('../../interface_programacao/mentorship/offer_mentorship.php', {
        method: 'POST',
        body: formData
    });

    if (data.success) {
        Swal.fire('Enviado!', 'Sua oferta de mentoria foi enviada ao estudante.', 'success');
        closeModal('offerMentorshipModal');
        loadSlots();
    } else {
        Swal.fire('Erro', data.error || 'Falha ao enviar oferta.', 'error');
    }
}

window.openModal = openModal;
window.closeModal = closeModal;
window.setDashboardView = setDashboardView;
window.switchMentorTab = switchMentorTab;
window.bookSlot = bookSlot;
window.deleteSlot = deleteSlot;
window.confirmBooking = confirmBooking;
window.openMentorSlotsModal = openMentorSlotsModal;
window.openOfferMentorship = openOfferMentorship;
window.handleOfferMentorship = handleOfferMentorship;
async function loadProjectReviews() {
    const list = document.getElementById('projectReviewsList');
    if (!list) return;
    list.innerHTML = '<div class="loading-spinner"></div>';

    const data = await fetchApi(`../../interface_programacao/mentorship/get_mentor_project_reviews.php`);
    if (data.success && data.reviews) {
        if (data.reviews.length === 0) {
            list.innerHTML = '<div class="empty-state">Nenhum progresso aguardando revisão.</div>';
            return;
        }
        list.innerHTML = data.reviews.map(r => `
            <div class="task-card glass fade-in" style="border-left: 4px solid var(--accent-orange);">
                <div class="task-header">
                    <span class="status-badge ${r.report_status}">${r.report_status.replace('_', ' ').toUpperCase()}</span>
                    <span class="task-date">${new Date(r.created_at).toLocaleDateString()}</span>
                </div>
                <h4 style="color: var(--accent-orange); text-transform: uppercase; font-size: 0.7rem; margin-bottom: 5px;">${r.project_name}</h4>
                <h3 style="color: white; font-size: 1.1rem; font-weight: 800; margin: 0 0 10px;">${r.title}</h3>
                <p style="font-size: 0.85rem; max-height: 100px; overflow-y: auto;">${r.content}</p>
                
                <div style="margin: 15px 0; display: flex; align-items: center; gap: 10px; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 8px;">
                    <span style="font-size: 0.7rem; color: rgba(255,255,255,0.4);">Avanço:</span>
                    <div style="flex: 1; height: 4px; background: rgba(255,255,255,0.1); border-radius: 10px;">
                        <div style="width: ${r.progress_percentage}%; height: 100%; background: var(--accent-orange); border-radius: 10px;"></div>
                    </div>
                    <span style="font-size: 0.8rem; font-weight: 900; color: white;">${r.progress_percentage}%</span>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 1rem;">
                    <button onclick="handleMentorAction(${r.report_id}, 'feedback')" class="btn-micro-aksanti" style="flex: 1; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2);">
                        <i class="fas fa-times-circle"></i> Pedir Ajustes
                    </button>
                    <button onclick="handleMentorAction(${r.report_id}, 'validate')" class="btn-micro-aksanti" style="flex: 1; background: #10b981; color: white; border: none;">
                        <i class="fas fa-check-circle"></i> Validar agora
                    </button>
                </div>
            </div>
        `).join('');
    } else {
        list.innerHTML = `<div class="error-state">Erro ao carregar revisões.</div>`;
    }
}

async function handleMentorAction(reportId, action) {
    if (action === 'validate') {
        const result = await Swal.fire({
            title: 'Validar este Progresso?',
            text: 'Ao validar, o relatório seguirá para a Administração e Investidores.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, Validar',
            background: '#1e293b',
            color: '#fff'
        });

        if (result.isConfirmed) processMentorAction(reportId, 'validate');
    } else {
        const { value: feedback } = await Swal.fire({
            title: 'Solicitar Ajustes',
            input: 'textarea',
            inputPlaceholder: 'Explica ao teu mentoreado o que ele captalizar de diferente...',
            showCancelButton: true,
            background: '#1e293b',
            color: '#fff',
            inputAttributes: { required: 'true' }
        });

        if (feedback) processMentorAction(reportId, 'feedback', feedback);
    }
}

async function processMentorAction(reportId, action, feedback = '') {
    const formData = new FormData();
    formData.append('report_id', reportId);
    formData.append('action', action);
    formData.append('mentor_feedback', feedback);

    const data = await fetchApi('../../interface_programacao/mentorship/mentor_action_progress.php', {
        method: 'POST',
        body: formData
    });

    if (data.success) {
        Swal.fire({ title: 'Sucesso!', text: data.message, icon: 'success', background: '#1e293b', color: '#fff' })
        .then(() => loadProjectReviews());
    } else {
        Swal.fire({ title: 'Atenção', text: data.message || 'Erro ao processar.', icon: 'warning', background: '#1e293b', color: '#fff' });
    }
}

window.handleMentorAction = handleMentorAction;

// Live Countdown Updater
setInterval(() => {
    document.querySelectorAll('.countdown-badge[data-start]').forEach(badge => {
        const startTime = parseInt(badge.dataset.start);
        const now = new Date().getTime();
        const diffMins = Math.floor((startTime - now) / 60000);

        if (diffMins <= 10) {
            // If it's time to join, we should ideally refresh the list to show the button
            // but for now, just show a "Quase hora" or let the user refresh
            if (diffMins <= 0) {
                badge.innerHTML = '<i class="fas fa-clock"></i> Começando...';
                // Trigger refresh if desired: loadSlots(); but be careful with loops
            } else {
                badge.innerHTML = `<i class="fas fa-clock"></i> Começa em ${diffMins} min`;
            }
        } else {
            badge.innerHTML = `<i class="fas fa-clock"></i> Começa em ${diffMins} min`;
        }
    });
}, 60000);

