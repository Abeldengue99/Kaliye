<?php
/**
 * Componente: Sistema de Gestão de Especialidades (Skills)
 * Autor: Aksanti Agent
 * Versão: 2.0 (UTF-8 Fixed & Premium Layout)
 */

if (!isset($base_url)) $base_url = '../';
?>

<style>
    /* Estilos Premium para o Sistema de Especialidades */
    .expertise-system-container {
        font-family: 'Outfit', sans-serif;
    }

    .expertise-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.6rem 1.2rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        margin: 0.35rem;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        cursor: default;
        position: relative;
        overflow: hidden;
    }

    .expertise-badge::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(45deg, var(--surface-10), rgba(255,255,255,0));
        z-index: 0;
    }

    .expertise-badge:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 10px 20px -5px rgba(0,0,0,0.4);
        z-index: 10;
    }

    .level-beginner { background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1)); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4); }
    .level-intermediate { background: linear-gradient(135deg, rgba(234, 179, 8, 0.2), rgba(234, 179, 8, 0.1)); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.4); }
    .level-advanced { background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(249, 115, 22, 0.1)); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.4); }
    .level-expert { background: linear-gradient(135deg, rgba(168, 85, 247, 0.2), rgba(168, 85, 247, 0.1)); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4); box-shadow: 0 0 15px rgba(168, 85, 247, 0.2); }

    .area-card {
        background: linear-gradient(145deg, var(--surface-5), rgba(255,255,255,0.02));
        border: 1px solid var(--surface-10);
        border-radius: 16px;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .area-card:hover {
        background: linear-gradient(145deg, var(--surface-10), var(--surface-5));
        border-color: #f7941d;
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }

    .area-card.selected {
        border-color: #f7941d;
        background: rgba(247, 148, 29, 0.15);
        box-shadow: 0 0 0 2px rgba(247, 148, 29, 0.3);
    }

    .glass {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--surface-8);
        border-radius: 20px;
    }

    .action-btn {
        padding: 0.8rem 1.5rem;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        border: none;
    }

    .btn-primary-doubt {
        background: linear-gradient(135deg, #f7941d, #e07b0e);
        color: white;
        box-shadow: 0 4px 15px rgba(247, 148, 29, 0.3);
    }

    .btn-primary-doubt:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(247, 148, 29, 0.5);
        filter: brightness(1.1);
    }
</style>

<div class="expertise-system-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; font-size: 1.8rem; color: #f7941d; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-brain"></i> Minhas Especialidades
            </h2>
            <p style="color: var(--surface-60); margin: 0.5rem 0 0 0; font-size: 0.95rem;">
                Defina suas áreas de conhecimento para melhor matching com estudantes
            </p>
        </div>
        <button onclick="openAddExpertiseModal()" class="action-btn btn-primary-doubt">
            <i class="fas fa-plus"></i> Adicionar Área
        </button>
    </div>

    <!-- Seção: Minhas Áreas -->
    <div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1.2rem; color: white;">
            <i class="fas fa-star" style="color: #f7941d; margin-right: 0.5rem;"></i> Minhas Áreas Atuais
        </h3>
        <div id="expertisesList" style="display: flex; flex-wrap: wrap; gap: 0.5rem; min-height: 80px;">
            <div style="width: 100%; text-align: center; padding: 2rem; color: var(--surface-40);">
                <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <p>Carregando especialidades...</p>
            </div>
        </div>
    </div>

    <!-- Seção: Áreas Populares -->
    <div class="glass" style="padding: 2rem;">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1.2rem; color: white;">
            <i class="fas fa-fire" style="color: #f97316; margin-right: 0.5rem;"></i> Áreas Recomendadas
        </h3>
        <div id="popularAreasContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <!-- Carregado via JS -->
        </div>
    </div>
</div>

<!-- MODAL: ADICIONAR ESPECIALIDADE (INTERNO) -->
<div id="addExpertiseSystemModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(5, 10, 20, 0.9); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); z-index: 100000; justify-content: center; align-items: center; padding: 1rem; overflow-y: auto;">
    <div style="background: rgba(13, 22, 40, 0.98); border: 1px solid rgba(247,148,29,0.3); border-radius: 28px; width: 100%; max-width: 850px; box-shadow: 0 40px 100px rgba(0,0,0,0.8); overflow: hidden; animation: modalAppear 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 2rem; border-bottom: 1px solid var(--surface-8);">
            <h3 style="margin: 0; color: white; display: flex; align-items: center; gap: 0.8rem;">
                <i class="fas fa-plus-circle" style="color: #f7941d;"></i> Nova Especialidade
            </h3>
            <button onclick="closeAddExpertiseModal()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; opacity: 0.5;">&times;</button>
        </div>

        <form id="addExpertiseForm" onsubmit="handleExpertiseSubmit(event)" style="padding: 2rem;">
            <p style="color: var(--surface-50); font-size: 0.9rem; margin-bottom: 1.5rem;">Selecione uma área abaixo ou sugira uma nova:</p>
            
            <div id="modalAreasSelector" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; max-height: 250px; overflow-y: auto; padding-right: 0.5rem;">
                <!-- Áreas para seleção -->
            </div>

            <div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; color: var(--surface-40); text-transform: uppercase; margin-bottom: 0.5rem;">Nível de Proficiência</label>
                    <select name="proficiency_level" required style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid var(--surface-10); border-radius: 10px; color: white;">
                        <option value="beginner">Iniciante</option>
                        <option value="intermediate" selected>Intermediário</option>
                        <option value="advanced">Avançado</option>
                        <option value="expert">Especialista</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: var(--surface-40); text-transform: uppercase; margin-bottom: 0.5rem;">Anos de Experiência</label>
                    <input type="number" name="years_experience" value="1" min="0" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid var(--surface-10); border-radius: 10px; color: white;">
                </div>
            </div>

            <div style="margin-top: 1.5rem; display: flex; gap: 2rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: white;">
                    <input type="checkbox" name="can_mentor" checked style="width: 18px; height: 18px;">
                    Disponível para Mentoria
                </label>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="button" onclick="closeAddExpertiseModal()" style="flex: 1; padding: 1rem; border-radius: 12px; border: 1px solid var(--surface-10); background: none; color: white; cursor: pointer;">Cancelar</button>
                <button type="submit" class="action-btn btn-primary-doubt" style="flex: 2; justify-content: center;">Confirmar Especialidade</button>
            </div>
        </form>
    </div>
</div>

<script>
let allKnowledgeAreas = [];
let currentSelectedAreaId = null;

async function syncExpertiseSystem() {
    await loadUserExpertises();
    await loadAllKnowledgeAreas();
}

async function loadUserExpertises() {
    const list = document.getElementById('expertisesList');
    try {
        const res = await fetch('<?php echo $base_url; ?>servicos/user/get_user_expertise.php');
        const data = await res.json();
        
        if (data.success && data.expertises.length > 0) {
            list.innerHTML = data.expertises.map(e => `
                <div class="expertise-badge level-${e.proficiency_level}">
                    <i class="${e.icon}" style="color: ${e.color};"></i>
                    <span>${e.area_name}</span>
                    <span style="opacity: 0.5; font-size: 0.7rem;">(${e.years_experience} anos)</span>
                </div>
            `).join('');
        } else {
            list.innerHTML = `
                <div style="width:100%; text-align:center; opacity:0.5; padding: 1rem;">
                    <i class="fas fa-info-circle"></i> Nenhuma área adicionada ainda.
                </div>
            `;
        }
    } catch (e) { list.innerHTML = 'Erro ao carregar.'; }
}

async function loadAllKnowledgeAreas() {
    try {
        const res = await fetch('<?php echo $base_url; ?>servicos/user/get_knowledge_areas.php');
        const data = await res.json();
        if (data.success) {
            allKnowledgeAreas = data.areas;
            renderAreas();
        }
    } catch (e) { }
}

function renderAreas() {
    const popular = document.getElementById('popularAreasContainer');
    const modalSelector = document.getElementById('modalAreasSelector');
    
    // Render popular
    popular.innerHTML = allKnowledgeAreas.slice(0, 8).map(a => `
        <div class="area-card" onclick="openAndSelectArea(${a.area_id})">
            <i class="${a.icon}" style="font-size: 1.5rem; color: ${a.color}; margin-bottom: 0.8rem; display: block;"></i>
            <span style="color: white; font-weight: 600;">${a.name}</span>
        </div>
    `).join('');

    // Render modal selector
    modalSelector.innerHTML = allKnowledgeAreas.map(a => `
        <div class="area-card" id="modal-area-${a.area_id}" onclick="selectAreaInModal(${a.area_id})" style="padding: 0.8rem;">
            <i class="${a.icon}" style="font-size: 1.2rem; color: ${a.color}; margin-bottom: 0.4rem; display: block;"></i>
            <span style="color: white; font-size: 0.8rem;">${a.name}</span>
        </div>
    `).join('');
}

function openAddExpertiseModal() {
    document.getElementById('addExpertiseSystemModal').style.display = 'flex';
}

function closeAddExpertiseModal() {
    document.getElementById('addExpertiseSystemModal').style.display = 'none';
    currentSelectedAreaId = null;
    document.querySelectorAll('.area-card').forEach(c => c.classList.remove('selected'));
}

function selectAreaInModal(id) {
    document.querySelectorAll('#modalAreasSelector .area-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('modal-area-' + id).classList.add('selected');
    currentSelectedAreaId = id;
}

function openAndSelectArea(id) {
    openAddExpertiseModal();
    setTimeout(() => selectAreaInModal(id), 100);
}

async function handleExpertiseSubmit(e) {
    e.preventDefault();
    if (!currentSelectedAreaId) {
        Swal.fire('Aviso', 'Por favor, selecione uma área.', 'warning');
        return;
    }

    const formData = new FormData(e.target);
    formData.append('area_id', currentSelectedAreaId);

    try {
        const res = await fetch('<?php echo $base_url; ?>servicos/mentorship/add_expertise.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('Sucesso', 'Especialidade adicionada!', 'success');
            closeAddExpertiseModal();
            loadUserExpertises();
        } else {
            Swal.fire('Erro', data.message, 'error');
        }
    } catch (err) { Swal.fire('Erro', 'Falha na conexão.', 'error'); }
}

// Inicializar
document.addEventListener('DOMContentLoaded', syncExpertiseSystem);
</script>

<style>
@keyframes modalAppear {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
