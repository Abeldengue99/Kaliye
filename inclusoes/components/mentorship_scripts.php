<script>
let currentView = '<?php echo $initial_view; ?>';

function setDashboardView(role) {
    currentView = role;
    const menteeBtn = document.getElementById('viewAsMentee');
    const mentorBtn = document.getElementById('viewAsMentor');
    const cascadeSec = document.getElementById('cascadeSection');
    
    if(role === 'mentor') {
        if(mentorBtn) { mentorBtn.style.background = 'var(--accent-orange)'; mentorBtn.style.color = 'white'; }
        if(menteeBtn) { menteeBtn.style.background = 'transparent'; menteeBtn.style.color = 'white'; }
        if(cascadeSec) cascadeSec.style.display = 'block';
        document.getElementById('assignmentsTabBtn')?.style.setProperty('display', 'block');
        document.querySelectorAll('[id$="Btn"]').forEach(btn => btn.style.display = 'block');
    } else {
        if(menteeBtn) { menteeBtn.style.background = 'var(--accent-orange)'; menteeBtn.style.color = 'white'; }
        if(mentorBtn) { mentorBtn.style.background = 'transparent'; mentorBtn.style.color = 'white'; }
        if(cascadeSec) cascadeSec.style.display = 'none';
        document.getElementById('assignmentsTabBtn')?.style.setProperty('display', 'none');
        document.querySelectorAll('[id$="Btn"]').forEach(btn => {
            btn.style.display = (btn.id === 'addSlotBtn') ? 'block' : 'none';
        });
    }
    const activeTab = document.querySelector('.m-tab.active');
    switchMentorTab(activeTab ? activeTab.dataset.tab : 'tasks');
}

function switchMentorTab(tabId, clickedElement) {
    document.querySelectorAll('.mentor-tab-content').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.m-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tabId).style.display = 'block';
    if (clickedElement) clickedElement.classList.add('active');
    else document.querySelector(`.m-tab[data-tab="${tabId}"]`)?.classList.add('active');
    
    if (tabId === 'tasks') loadTasks();
    if (tabId === 'scheduler') loadSlots();
    if (tabId === 'resources') loadResources();
    if (tabId === 'notices') loadNotices();
    if (tabId === 'assignments') loadAssignments();
    if (tabId === 'legal') loadLegalAgreements();
}

function loadTasks() {
    const list = document.getElementById('tasksList');
    fetch(`../servicos/mentorship/get_tasks.php?view=${currentView}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                list.innerHTML = data.tasks.map(t => `
                    <div class="task-card">
                        <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                            <span style="font-size:0.7rem; color:var(--accent-orange); font-weight:700;">${t.status.toUpperCase()}</span>
                            <span style="font-size:0.7rem; color:var(--text-secondary);">${new Date(t.created_at).toLocaleDateString()}</span>
                        </div>
                        <h4 style="margin:0 0 0.5rem;">${t.task_name}</h4>
                        <p style="font-size:0.8rem; color:var(--text-secondary);">${t.description}</p>
                        ${currentView === 'mentee' && t.status === 'pending' ? `<button onclick="completeTask(${t.task_id})" class="btn-primary" style="width:100%; margin-top:1rem; font-size:0.8rem;">Marcar como Concluída</button>` : ''}
                    </div>
                `).join('');
            }
        });
}

function loadSlots() {
    const list = document.getElementById('slotsList');
    fetch(`../servicos/mentorship/get_mentorship_slots.php?view=${currentView}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                list.innerHTML = data.slots.length ? data.slots.map(s => {
                    const roomParam = s.meeting_room ? `?room=${s.meeting_room}` : '';
                    const meetingUrl = roomParam ? `meeting.php${roomParam}` : (s.meeting_link || '#');
                    const isAvailable = s.status === 'available';
                    const isBooked = s.status === 'booked' || s.status === 'confirmed';
                    
                    return `
                    <div class="slot-card" style="border-left: 4px solid ${isAvailable ? '#10b981' : '#f7941d'};">
                        <div style="font-size:0.8rem; margin-bottom:0.5rem; font-weight:700;">
                            <i class="fas fa-calendar-alt"></i> ${new Date(s.start_time).toLocaleString('pt-PT', {day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'})}
                        </div>
                        <div style="font-size:0.75rem; color:var(--text-secondary); margin-bottom: 0.5rem;">
                            <span class="badge" style="background: ${isAvailable ? 'rgba(16,185,129,0.1)' : 'rgba(247,148,29,0.1)'}; color: ${isAvailable ? '#10b981' : '#f7941d'}; padding: 2px 8px; border-radius: 4px;">
                                ${s.status.toUpperCase()}
                            </span>
                        </div>
                        <div style="font-size:0.8rem; margin-bottom: 1rem;">
                            <i class="fas fa-user"></i> ${s.mentor_name} ${s.participant_name ? ` âž” ${s.participant_name}` : ''}
                        </div>
                        ${isBooked ? `
                            <a href="${meetingUrl}" target="_blank" class="btn-primary" style="display:block; text-align:center; font-size:0.75rem; background: #3b82f6; border: none;">
                                <i class="fas fa-video"></i> Entrar na Chamada
                            </a>
                        ` : ''}
                        ${isAvailable && currentView === 'mentee' ? `
                            <button onclick="bookSlot(${s.slot_id})" class="btn-primary" style="display:block; width:100%; font-size:0.75rem;">
                                <i class="fas fa-bookmark"></i> Reservar Vaga
                            </button>
                        ` : ''}
                         ${currentView === 'mentor' ? `
                            <button onclick="deleteSlot(${s.slot_id})" style="background:none; border:none; color:#ef4444; font-size:0.7rem; cursor:pointer; margin-top:0.5rem; width:100%;">
                                <i class="fas fa-trash"></i> Remover Slot
                            </button>
                        ` : ''}
                    </div>
                `;}).join('') : '<p style="color:var(--text-secondary); grid-column: 1/-1; text-align:center;">Nenhuma sessão agendada.</p>';
            }
        });
}

// Slot Management
document.getElementById('addSlotForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('../servicos/mentorship/add_mentorship_slot.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.fire('Sucesso!', 'Horário adicionado à agenda.', 'success');
                closeModal('addSlotModal');
                loadSlots();
            } else {
                Swal.fire('Erro', data.error || 'Falha ao adicionar slot.', 'error');
            }
        });
});

function bookSlot(slotId) {
    Swal.fire({
        title: 'Confirmar Reserva?',
        text: 'Desejas marcar esta mentoria para o horário selecionado?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, Reservar',
        background: '#1e293b',
        color: '#fff'
    }).then(result => {
        if(result.isConfirmed) {
            const formData = new FormData();
            formData.append('slot_id', slotId);
            fetch('../servicos/mentorship/book_mentorship_slot.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('Reservado!', 'A sessão foi marcada com sucesso.', 'success');
                        loadSlots();
                    } else {
                        Swal.fire('Erro', data.message || 'Falha ao reservar.', 'error');
                    }
                });
        }
    });
}

function deleteSlot(slotId) {
    Swal.fire({
        title: 'Remover Horário?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Sim, Remover',
        background: '#1e293b',
        color: '#fff'
    }).then(result => {
        if(result.isConfirmed) {
            fetch(`../servicos/mentorship/delete_mentorship_slot.php?id=${slotId}`)
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('Removido!', 'O horário foi retirado da agenda.', 'success');
                        loadSlots();
                    }
                });
        }
    });
}

function openModal(id) { 
    if(id === 'addSlotModal') {
        // Load mentees into select
        fetch('../servicos/mentorship/get_my_mentees.php')
            .then(res => res.json())
            .then(data => {
                const sel = document.getElementById('participantSelect');
                if(data.success) {
                    sel.innerHTML = '<option value="">-- Abrir Horário Geral (Sem participante fixo) --</option>' + 
                        data.mentees.map(m => `<option value="${m.user_id}">${m.full_name}</option>`).join('');
                }
            });
    }
    document.getElementById(id).style.display = 'flex'; 
}
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

document.addEventListener('DOMContentLoaded', () => setDashboardView(currentView));
</script>

