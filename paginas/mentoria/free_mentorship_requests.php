<?php
// paginas/mentoria/free_mentorship_requests.php - Pedidos de Mentoria Gratuita
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

$current_user_id = $_SESSION['user_id'];
$current_user_type = $_SESSION['user_type'];
// Mentors MUST be approved to see the mentor view/functions
$is_mentor = (isMentor() || isAdmin());

// Check if coming from a doubt conversion
$from_doubt_id = isset($_GET['from_doubt']) ? intval($_GET['from_doubt']) : null;
$doubt_data = null;

if ($from_doubt_id) {
    $doubt_stmt = $db->prepare("SELECT * FROM doubts WHERE doubt_id = ? AND user_id = ?");
    $doubt_stmt->execute([$from_doubt_id, $current_user_id]);
    $doubt_data = $doubt_stmt->fetch();
}

$request_id_to_open = isset($_GET['request_id']) ? intval($_GET['request_id']) : null;
?>

<style>
    .request-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .request-card:hover {
        border-color: var(--accent-orange);
        box-shadow: 0 8px 24px rgba(247, 148, 29, 0.2);
        transform: translateY(-2px);
    }
    
    .request-status {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    
    .status-open { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59,  130, 246, 0.3); }
    .status-in_progress { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
    .status-completed { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-cancelled { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    
    .difficulty-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-right: 0.5rem;
    }
    
    .diff-beginner { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .diff-intermediate { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }
    .diff-advanced { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    
    .mentor-application-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .mentor-application-card:hover {
        background: rgba(255,255,255,0.05);
        border-color: var(--accent-orange);
    }
    
    .tab-nav {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid var(--glass-border);
    }
    
    .tab-btn {
        padding: 0.75rem 1.5rem;
        background: none;
        border: none;
        color: var(--text-secondary);
        font-weight: 600;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }
    
    .tab-btn.active {
        color: var(--accent-orange);
        border-bottom-color: var(--accent-orange);
    }
    
    .tab-btn:hover {
        color: var(--text-primary);
    }

    .action-btn {
        padding: 0.8rem 1.8rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        border: 1px solid rgba(255,255,255,0.1);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary-doubt {
        background: linear-gradient(135deg, #f7941d 0%, #d47a00 100%);
        color: #000;
        border: none;
        box-shadow: 0 4px 15px rgba(247, 148, 29, 0.2);
    }

    .btn-primary-doubt:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(247, 148, 29, 0.4);
        filter: brightness(1.1);
    }

    .btn-primary-doubt:active {
        transform: translateY(-1px);
    }

    .btn-cancel {
        background: rgba(255, 255, 255, 0.03);
        color: var(--text-secondary);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-cancel:hover {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.3);
    }

    .close-modal-btn {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        z-index: 10;
    }

    .close-modal-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.3);
        transform: rotate(90deg);
    }
</style>

<div style="max-width: 1000px; margin: 0 auto; padding: 2rem 1rem;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin: 0; font-size: 2rem; color: var(--accent-orange); display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-handshake"></i> Mentoria Gratuita
            </h1>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">
                <?php if ($is_mentor): ?>
                    Ajude estudantes a resolver as suas dúvidas e desenvolver projectos
                <?php else: ?>
                    Solicite ajuda de mentores experientes de forma gratuita
                <?php endif; ?>
            </p>
        </div>
        <?php if (!$is_mentor): ?>
            <button onclick="openRequestModal()" class="action-btn btn-primary-doubt">
                <i class="fas fa-plus"></i> Novo Pedido
            </button>
        <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div class="tab-nav">
        <?php if (!$is_mentor): ?>
            <button class="tab-btn active" onclick="switchTab('my-requests')">
                <i class="fas fa-list"></i> Meus Pedidos
            </button>
        <?php endif; ?>
        <?php if ($is_mentor): ?>
            <button class="tab-btn active" onclick="switchTab('available')">
                <i class="fas fa-inbox"></i> Disponíveis
            </button>
            <button class="tab-btn" onclick="switchTab('my-mentorships')">
                <i class="fas fa-chalkboard-teacher"></i> Minhas Mentorias
            </button>
        <?php else: ?>
            <button class="tab-btn" onclick="switchTab('browse')">
                <i class="fas fa-search"></i> Explorar
            </button>
        <?php endif; ?>
    </div>

    <!-- Content Container -->
    <div id="content-container">
        <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
            <p>A carregar...</p>
        </div>
    </div>
</div>

<!-- Modal: Novo Pedido -->
<div id="requestModal" style="display: <?php echo $from_doubt_id ? 'flex' : 'none'; ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10001; align-items: center; justify-content: center; backdrop-filter: blur(5px); overflow-y: auto; padding: 2rem 0;">
    <div class="glass" style="width: 95%; max-width: 700px; margin: auto; padding: 2rem; border-radius: 20px; position: relative;">
        <button onclick="closeRequestModal()" class="close-modal-btn" title="Fechar">
            <i class="fas fa-times"></i>
        </button>
        
        <h2 style="margin: 0 0 1.5rem 0; color: var(--accent-orange);">
            <i class="fas fa-handshake"></i> Solicitar Mentoria Gratuita
        </h2>
        
        <form id="requestForm" onsubmit="submitRequest(event)">
            <input type="hidden" name="doubt_id" value="<?php echo $from_doubt_id ?: ''; ?>">
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Título*</label>
                <input type="text" name="title" required maxlength="255"
                    value="<?php echo $doubt_data ? htmlspecialchars($doubt_data['title']) : ''; ?>"
                    placeholder="Ex: Ajuda com desenvolvimento de aplicação web"
                    style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Descrição Detalhada*</label>
                <textarea name="description" required rows="6"
                    placeholder="Descreva o que precisa de ajuda, os seus objetivos e o que já tentou..."
                    style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white; resize: vertical;"><?php echo $doubt_data ? htmlspecialchars($doubt_data['description']) : ''; ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Categoria*</label>
                    <select name="category" required
                        style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;">
                        <option value="">Selecione...</option>
                        <option value="programming" <?php echo ($doubt_data && $doubt_data['category'] === 'programming') ? 'selected' : ''; ?>>Programação</option>
                        <option value="math" <?php echo ($doubt_data && $doubt_data['category'] === 'math') ? 'selected' : ''; ?>>Matemática</option>
                        <option value="physics" <?php echo ($doubt_data && $doubt_data['category'] === 'physics') ? 'selected' : ''; ?>>Física</option>
                        <option value="chemistry" <?php echo ($doubt_data && $doubt_data['category'] === 'chemistry') ? 'selected' : ''; ?>>Química</option>
                        <option value="languages" <?php echo ($doubt_data && $doubt_data['category'] === 'languages') ? 'selected' : ''; ?>>Línguas</option>
                        <option value="business" <?php echo ($doubt_data && $doubt_data['category'] === 'business') ? 'selected' : ''; ?>>Negócios</option>
                        <option value="design" <?php echo ($doubt_data && $doubt_data['category'] === 'design') ? 'selected' : ''; ?>>Design</option>
                        <option value="other" <?php echo ($doubt_data && $doubt_data['category'] === 'other') ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Nível de Dificuldade*</label>
                    <select name="difficulty_level" required
                        style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;">
                        <option value="beginner">Iniciante</option>
                        <option value="intermediate">Intermédio</option>
                        <option value="advanced">Avançado</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Duração Estimada</label>
                <input type="text" name="estimated_duration"
                    placeholder="Ex: 1 sessão de 1 hora"
                    style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;">
                <small style="color: var(--text-secondary); font-size: 0.75rem;">Opcional: Quanto tempo acha que vai precisar</small>
            </div>
            
            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
                <span style="color: var(--text-secondary); font-size: 0.85rem; margin-left: 0.5rem;">
                    Mentores poderão candidatar-se para ajudá-lo. Você escolherá quem prefere trabalhar consigo.
                </span>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" onclick="closeRequestModal()" class="action-btn btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="action-btn btn-primary-doubt">
                    <i class="fas fa-paper-plane"></i> Publicar Pedido
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Detalhes do Pedido -->
<div id="requestDetailModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10002; align-items: center; justify-content: center; backdrop-filter: blur(5px); overflow-y: auto; padding: 2rem 0;">
    <div class="glass" style="width: 95%; max-width: 900px; margin: auto; padding: 0; border-radius: 20px; position: relative;">
        <button onclick="closeRequestDetailModal()" class="close-modal-btn" title="Fechar">
            <i class="fas fa-times"></i>
        </button>
        
        <div id="requestDetailContent" style="padding: 2rem; max-height: 85vh; overflow-y: auto;">
            <!-- Conteúdo carregado dinamicamente -->
        </div>
    </div>
</div>

<script>
const IS_MENTOR = <?php echo $is_mentor ? 'true' : 'false'; ?>;
const AKSANTI_CONFIG = {
    userId: <?php echo json_encode($current_user_id); ?>,
    baseUrl: <?php echo json_encode($base_url); ?>
};
let currentTab = IS_MENTOR ? 'available' : 'my-requests';

// Carregar conteúdo inicial
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($from_doubt_id): ?>
        // Modal de criação já está aberto via inline style no HTML
    <?php endif; ?>
    
    <?php if ($request_id_to_open): ?>
        openRequestDetail(<?php echo $request_id_to_open; ?>);
    <?php endif; ?>
    
    loadContent();
});

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    loadContent();
}

async function loadContent() {
    const container = document.getElementById('content-container');
    container.innerHTML = `
        <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
            <p>A carregar...</p>
        </div>
    `;
    
    try {
        let endpoint = '';
        if (currentTab === 'my-requests') endpoint = '../../interface_programacao/mentorship/get_my_mentorship_requests.php';
        else if (currentTab === 'available') endpoint = '../../interface_programacao/mentorship/get_available_mentorship_requests.php';
        else if (currentTab === 'my-mentorships') endpoint = '../../interface_programacao/mentorship/get_my_mentorships.php';
        else if (currentTab === 'browse') endpoint = '../../interface_programacao/mentorship/get_available_mentorship_requests.php';
        
        const response = await fetch(endpoint);
        const data = await response.json();
        
        if (data.success) {
            renderRequests(data.requests);
        } else {
            container.innerHTML = `
                <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                    <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                    <p>${data.message || 'Nenhum pedido encontrado'}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Erro:', error);
        container.innerHTML = `
            <div style="text-align: center; padding: 3rem; color: var(--danger);">
                <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Erro ao carregar. Tente novamente.</p>
            </div>
        `;
    }
}

function renderRequests(requests) {
    const container = document.getElementById('content-container');
    
    if (requests.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 4rem 2rem; color: var(--text-secondary); background: rgba(0,0,0,0.1); border-radius: 20px;">
                <i class="fas fa-inbox" style="font-size: 4rem; opacity: 0.15; margin-bottom: 1.5rem; display: block;"></i>
                <h3 style="margin-bottom: 0.5rem; color: rgba(255,255,255,0.4);">Sem pedidos ativos</h3>
                <p style="font-size: 0.9rem; opacity: 0.6;">Dúvidas convertidas ou novos pedidos aparecerão aqui.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = requests.map(req => {
        // Lógica de foto de perfil
        let picRaw = req.profile_pic;
        let finalPic = AKSANTI_CONFIG.baseUrl + 'recursos/images/default_profile.png';
        if (picRaw && picRaw !== 'default_profile.png') {
            finalPic = picRaw.startsWith('http') ? picRaw : 
                       (picRaw.startsWith('carregamentos/') ? AKSANTI_CONFIG.baseUrl + picRaw : 
                        AKSANTI_CONFIG.baseUrl + 'carregamentos/profiles/' + picRaw);
        }

        return `
            <div class="request-card" onclick="openRequestDetail(${req.request_id})">
                <span class="request-status status-${req.status}">
                    ${getStatusLabel(req.status)}
                </span>
                
                <div style="display: flex; gap: 1.25rem; margin-bottom: 1.25rem; padding-right: 120px;">
                    <div style="position: relative;">
                        <img src="${finalPic}" 
                            style="width: 55px; height: 55px; border-radius: 16px; border: 2px solid var(--accent-orange); object-fit: cover; background: #fff;">
                    </div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 0.5rem 0; color: #fff; font-size: 1.1rem; line-height: 1.4;">${req.title}</h3>
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: var(--text-secondary);">
                            <span style="font-weight: 700; color: var(--accent-orange);">${req.full_name}</span>
                            <span style="opacity: 0.4;">•</span>
                            <span>${req.user_type_label}</span>
                            <span style="opacity: 0.4;">•</span>
                            <span style="font-family: monospace;">${new Date(req.created_at).toLocaleDateString('pt-PT')}</span>
                        </div>
                    </div>
                </div>
                
                <p style="color: rgba(255,255,255,0.6); line-height: 1.7; font-size: 0.9rem; margin-bottom: 1.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; padding: 0.75rem; background: rgba(0,0,0,0.15); border-radius: 10px;">
                    ${req.description}
                </p>
                
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                    <span class="difficulty-badge diff-${req.difficulty_level}" style="padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.75rem;">
                        ${getDifficultyLabel(req.difficulty_level)}
                    </span>
                    ${req.category ? `<span class="category-tag" style="background: rgba(255,255,255,0.05); padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.75rem; color: #fff;"><i class="fas fa-tag"></i> ${req.category}</span>` : ''}
                    ${req.estimated_duration ? `<span style="font-size: 0.75rem; color: rgba(255,255,255,0.4); margin-left: auto;"><i class="fas fa-clock"></i> ${req.estimated_duration}</span>` : ''}
                </div>
                
                <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05); font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-users" style="color: var(--accent-orange);"></i> 
                    <b>${req.application_count || 0}</b> mentores interessados
                </div>
            </div>
        `;
    }).join('');
}

function getStatusLabel(status) {
    const labels = {
        'open': '🔓 Aberto',
        'in_progress': '⏳ Em Progresso',
        'completed': '✅ Concluído',
        'cancelled': '❌ Cancelado'
    };
    return labels[status] || status;
}

function getDifficultyLabel(level) {
    const labels = {
        'beginner': '🟢 Iniciante',
        'intermediate': '🟡 Intermédio',
        'advanced': '🔴 Avançado'
    };
    return labels[level] || level;
}

// Modals
function openRequestModal() {
    document.getElementById('requestModal').style.display = 'flex';
}

function closeRequestModal() {
    document.getElementById('requestModal').style.display = 'none';
    if (!<?php echo $from_doubt_id ? 'true' : 'false'; ?>) {
        document.getElementById('requestForm').reset();
    }
}

async function submitRequest(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-magic fa-spin"></i> Processando...';
    
    try {
        const response = await fetch('../../interface_programacao/mentorship/create_free_mentorship_request.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Pedido publicado! Os mentores poderão candidatar-se.',
                background: '#1e293b',
                color: '#fff',
                timer: 2000
            });
            closeRequestModal();
            loadContent();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: data.message,
                background: '#1e293b',
                color: '#fff'
            });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Erro:', error);
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function openRequestDetail(requestId) {
    document.getElementById('requestDetailModal').style.display = 'flex';
    document.getElementById('requestDetailContent').innerHTML = `
        <div style="text-align: center; padding: 3rem;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--accent-orange);"></i>
        </div>
    `;
    
    try {
        const response = await fetch(`../../interface_programacao/mentorship/get_mentorship_request_detail.php?request_id=${requestId}`);
        const data = await response.json();
        
        if (data.success) {
            renderRequestDetail(data.request, data.applications);
        }
    } catch (error) {
        console.error('Erro:', error);
    }
}

function closeRequestDetailModal() {
    document.getElementById('requestDetailModal').style.display = 'none';
}

function renderRequestDetail(req, applications) {
    const isOwner = <?php echo $current_user_id; ?> == req.student_id;
    const canApply = IS_MENTOR && req.status === 'open';
    
    document.getElementById('requestDetailContent').innerHTML = `
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem;">
                <h2 style="margin: 0; color: var(--accent-orange); flex: 1;">${req.title}</h2>
                <span class="request-status status-${req.status}">
                    ${getStatusLabel(req.status)}
                </span>
            </div>
            
            <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem;">
                <img src="${req.profile_pic ? (req.profile_pic.startsWith('http') ? req.profile_pic : (req.profile_pic.startsWith('carregamentos/') ? AKSANTI_CONFIG.baseUrl + req.profile_pic : AKSANTI_CONFIG.baseUrl + 'carregamentos/profiles/' + req.profile_pic)) : AKSANTI_CONFIG.baseUrl + 'recursos/images/default_profile.png'}" 
                    style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid var(--accent-orange); object-fit: cover;">
                <div>
                    <div style="font-weight: 600; color: var(--accent-orange);">${req.full_name}</div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                        ${req.user_type_label} • ${new Date(req.created_at).toLocaleString('pt-PT')}
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <span class="difficulty-badge diff-${req.difficulty_level}">
                    ${getDifficultyLabel(req.difficulty_level)}
                </span>
                ${req.category ? `<span class="category-tag"><i class="fas fa-tag"></i> ${req.category}</span>` : ''}
                ${req.estimated_duration ? `<span class="category-tag"><i class="fas fa-clock"></i> ${req.estimated_duration}</span>` : ''}
            </div>
            
            <div style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 3px solid var(--accent-orange);">
                <p style="color: var(--text-primary); line-height: 1.8; margin: 0; white-space: pre-wrap;">${req.description}</p>
            </div>
            
            ${canApply && !req.user_has_applied ? `
                <button onclick="showApplicationForm(${req.request_id})" class="action-btn btn-primary-doubt" style="margin-bottom: 1.5rem;">
                    <i class="fas fa-hand-paper"></i> Candidatar-me para Ajudar
                </button>
            ` : ''}
            ${canApply && req.user_has_applied ? `
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    <span style="color: var(--text-secondary); margin-left: 0.5rem;">Você já se candidatou a este pedido</span>
                </div>
            ` : ''}
            
            ${req.session_date ? `
                <div style="background: rgba(247, 148, 29, 0.1); border: 1px solid var(--accent-orange); padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 0.75rem 0; color: var(--accent-orange); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-calendar-alt"></i> Sessão Agendada
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem; color: var(--text-primary);">
                        <div><i class="fas fa-clock" style="color: var(--accent-orange);"></i> <b>Data/Hora:</b> ${new Date(req.session_date).toLocaleString('pt-PT')}</div>
                        <div><i class="fas fa-hourglass-half" style="color: var(--accent-orange);"></i> <b>Duração:</b> ${req.duration_minutes} min</div>
                    </div>
                    ${req.meeting_link ? `
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(247, 148, 29, 0.2);">
                            <a href="${req.meeting_link}" target="_blank" class="action-btn btn-primary-doubt" style="display: inline-flex; width: auto; padding: 0.6rem 1.5rem;">
                                <i class="fas fa-video"></i> Entrar na Reunião
                            </a>
                        </div>
                    ` : ''}
                </div>
            ` : (req.status === 'in_progress' ? `
                <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: var(--text-secondary); font-size: 0.9rem;">
                    <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
                    Aguardando que o mentor agende a sessão.
                </div>
            ` : '')}

            ${IS_MENTOR && req.selected_mentor_id == <?php echo $current_user_id; ?> && req.status === 'in_progress' ? `
                <button onclick="showScheduleForm(${req.request_id})" class="action-btn" style="background: var(--accent-orange); color: white; margin-bottom: 1.5rem; margin-right: 0.5rem;">
                    <i class="fas fa-calendar-plus"></i> ${req.session_date ? 'Re-agendar Sessão' : 'Agendar Sessão'}
                </button>
            ` : ''}

            ${isOwner && req.status === 'in_progress' ? `
                <button onclick="showCompleteForm(${req.request_id})" class="action-btn" style="background: var(--brand-green); color: white; margin-bottom: 1.5rem;">
                    <i class="fas fa-check-double"></i> Concluir Mentoria e Avaliar
                </button>
            ` : ''}
        </div>
        
        <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 2rem 0;">
        
        <h3 style="margin-bottom: 1.5rem;">
            <i class="fas fa-users"></i> Candidaturas (${applications.length})
        </h3>
        
        <div id="applications-list">
            ${applications.length === 0 ? `
                <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <i class="fas fa-inbox" style="font-size: 2rem; opacity: 0.3; margin-bottom: 0.5rem;"></i>
                    <p>Nenhuma candidatura ainda</p>
                </div>
            ` : applications.map(app => `
                <div class="mentor-application-card">
                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                        <img src="${app.profile_pic ? (app.profile_pic.startsWith('http') ? app.profile_pic : (app.profile_pic.startsWith('carregamentos/') ? AKSANTI_CONFIG.baseUrl + app.profile_pic : AKSANTI_CONFIG.baseUrl + 'carregamentos/profiles/' + app.profile_pic)) : AKSANTI_CONFIG.baseUrl + 'recursos/images/default_profile.png'}" 
                            style="width: 45px; height: 45px; border-radius: 50%; border: 2px solid var(--accent-orange); object-fit: cover;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: var(--accent-orange); margin-bottom: 0.25rem;">
                                ${app.full_name}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                ${app.user_type_label} • <span style="color: var(--accent-orange); font-weight: 600;"><i class="fas fa-star" style="font-size: 0.7rem;"></i> Avaliação: ${app.avaliacao || 0}</span> • ${new Date(app.created_at).toLocaleString('pt-PT')}
                            </div>
                        </div>
                        ${app.status === 'accepted' ? `
                            <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; height: fit-content;">
                                ✅ Aceite
                            </span>
                        ` : ''}
                    </div>
                    ${app.message ? `
                        <p style="color: var(--text-primary); line-height: 1.6; margin-bottom: 1rem; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 8px; white-space: pre-wrap;">
                            ${app.message}
                        </p>
                    ` : ''}
                    ${isOwner && app.status === 'pending' && req.status === 'open' ? `
                        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                            <button onclick="respondToApplication(${req.request_id}, ${app.application_id}, 'accept')" 
                                class="action-btn" style="background: #10b981; color: white; padding: 0.5rem 1rem;">
                                <i class="fas fa-check"></i> Aceitar
                            </button>
                            <button onclick="respondToApplication(${req.request_id}, ${app.application_id}, 'reject')" 
                                class="action-btn" style="background: #ef4444; color: white; padding: 0.5rem 1rem;">
                                <i class="fas fa-times"></i> Recusar
                            </button>
                        </div>
                    ` : ''}
                </div>
            `).join('')}
        </div>
    `;
}

function showApplicationForm(requestId) {
    Swal.fire({
        target: document.getElementById('requestDetailModal'),
        title: 'Candidatar-se',
        html: `
            <textarea id="applicationMessage" class="swal2-textarea" placeholder="Por que você é a pessoa certa para ajudar? (Opcional)" style="width: 90%; height: 120px; background: var(--input-bg); color: white; border: 1px solid var(--input-border);"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Enviar Candidatura',
        cancelButtonText: 'Cancelar',
        background: '#1e293b',
        color: '#fff',
        confirmButtonColor: '#f7941d',
        preConfirm: () => {
            return document.getElementById('applicationMessage').value;
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('request_id', requestId);
            formData.append('message', result.value);
            
            try {
                const response = await fetch('../../interface_programacao/mentorship/apply_for_free_mentorship.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        target: document.getElementById('requestDetailModal'),
                        icon: 'success',
                        title: 'Candidatura enviada!',
                        text: 'O estudante receberá a sua candidatura.',
                        background: '#1e293b',
                        color: '#fff',
                        timer: 2000
                    });
                    openRequestDetail(requestId); // Recarregar
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }
    });
}

async function respondToApplication(requestId, applicationId, action) {
    const formData = new FormData();
    formData.append('application_id', applicationId);
    formData.append('action', action);
    
    try {
        const response = await fetch('../../interface_programacao/mentorship/respond_mentorship_application.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                target: document.getElementById('requestDetailModal'),
                icon: action === 'accept' ? 'success' : 'success',
                title: action === 'accept' ? 'Mentor aceite!' : 'Candidatura recusada',
                text: data.message,
                background: '#1e293b',
                color: '#fff',
                timer: 2000
            });
            openRequestDetail(requestId); // Recarregar
            loadContent(); // Atualizar lista principal
        }
    } catch (error) {
        console.error('Erro:', error);
    }
}

function showCompleteForm(requestId) {
    Swal.fire({
        target: document.getElementById('requestDetailModal'),
        title: 'Concluir Mentoria',
        html: `
            <div style="text-align: left; margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">Avaliação do Mentor (1 a 5 estrelas)</label>
                <select id="mentorRating" class="swal2-select" style="width: 100%; height: 45px; margin: 0; background: #0f172a; color: #fff;">
                    <option value="5">⭐️⭐️⭐️⭐️⭐️ (Excelente)</option>
                    <option value="4">⭐️⭐️⭐️⭐️ (Muito Bom)</option>
                    <option value="3">⭐️⭐️⭐️ (Bom / Razoável)</option>
                    <option value="2">⭐️⭐️ (Poderia ser melhor)</option>
                    <option value="1">⭐️ (Não ajudou)</option>
                </select>
            </div>
            <div style="text-align: left;">
                <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">Feedback / Comentário</label>
                <textarea id="mentorFeedback" class="swal2-textarea" placeholder="Como foi a experiência?" style="width: 100%; height: 100px; margin: 0; background: #0f172a; color: #fff; border: 1px solid rgba(255,255,255,0.1);"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirmar Conclusão',
        cancelButtonText: 'Cancelar',
        background: '#1e293b',
        color: '#fff',
        confirmButtonColor: '#10b981',
        preConfirm: () => {
            return {
                rating: document.getElementById('mentorRating').value,
                feedback: document.getElementById('mentorFeedback').value
            };
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('request_id', requestId);
            formData.append('rating', result.value.rating);
            formData.append('feedback', result.value.feedback);
            
            try {
                const response = await fetch('../../interface_programacao/mentorship/complete_free_mentorship.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        target: document.getElementById('requestDetailModal'),
                        icon: 'success',
                        title: 'Concluído!',
                        text: data.message,
                        background: '#1e293b',
                        color: '#fff'
                    });
                    openRequestDetail(requestId);
                    loadContent();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.message,
                        background: '#1e293b',
                        color: '#fff'
                    });
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }
    });
}

function showScheduleForm(requestId) {
    Swal.fire({
        target: document.getElementById('requestDetailModal'),
        title: 'Agendar Sessão',
        html: `
            <div style="text-align: left; margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">Data e Hora*</label>
                <input type="datetime-local" id="sessionDate" class="swal2-input" style="width: 100%; margin: 0; background: #0f172a; color: #fff;">
            </div>
            <div style="text-align: left; margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">Duração (minutos)</label>
                <input type="number" id="sessionDuration" class="swal2-input" value="60" style="width: 100%; margin: 0; background: #0f172a; color: #fff;">
            </div>
            <div style="text-align: left;">
                <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8;">Link da Reunião (Interno/Externo)</label>
                <input type="url" id="meetingLink" class="swal2-input" placeholder="https://meet.google.com/..." style="width: 100%; margin: 0; background: #0f172a; color: #fff;">
                <small style="color: #64748b;">Dica: Você pode usar o link de vídeo da plataforma KALIYE.</small>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agendar e Notificar',
        cancelButtonText: 'Cancelar',
        background: '#1e293b',
        color: '#fff',
        confirmButtonColor: '#f7941d',
        preConfirm: () => {
            return {
                session_date: document.getElementById('sessionDate').value,
                duration: document.getElementById('sessionDuration').value,
                meeting_link: document.getElementById('meetingLink').value
            };
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            if (!result.value.session_date) {
                Swal.showValidationMessage('Data e hora são obrigatórias');
                return;
            }

            const formData = new FormData();
            formData.append('request_id', requestId);
            formData.append('session_date', result.value.session_date);
            formData.append('duration', result.value.duration);
            formData.append('meeting_link', result.value.meeting_link);
            
            try {
                const response = await fetch('../../interface_programacao/mentorship/schedule_free_mentorship.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        target: document.getElementById('requestDetailModal'),
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        background: '#1e293b',
                        color: '#fff',
                        timer: 2500
                    });
                    openRequestDetail(requestId);
                    loadContent();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.message,
                        background: '#1e293b',
                        color: '#fff'
                    });
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }
    });
}
</script>

<?php require_once '../../inclusoes/rodape.php'; ?>


