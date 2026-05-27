<?php
/**
 * mentorship.php - Mentorship Ecosystem
 * Refactored: Separated CSS/JS/PHP
 */
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// Get current user details for specialty matching and dynamic background
$stmt = $db->prepare("SELECT specialty, institution, field_of_study, profile_pic FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$user_pic = $current_user_data['profile_pic'] ? $base_url . 'recursos/images/perfil/' . $current_user_data['profile_pic'] : '';
$user_specialty = $current_user_data['specialty'] ?? ($current_user_data['field_of_study'] ?? '');

// Mentorship Cascade Logic Map
$can_mentor = [
    'univ_student' => ['univ_student', 'high_student'],
    'high_student' => [],
    'mentor'       => ['univ_student', 'high_student'],
    'admin'        => ['mentor', 'univ_student', 'high_student']
];

$target_types = $can_mentor[$user_type] ?? [];
$user_mentorship_status = $_SESSION['mentorship_status'] ?? 'unsubmitted';
$is_peer_mentor = (in_array($user_type, ['univ_student', 'high_student', 'sec_student']) && $user_mentorship_status === 'approved');

// Simplified Logic: 
// 1. If they are a verified Peer Mentor (Student + Approved as Mentor), show toggle.
// 2. Otherwise, no toggle needed as they have a single primary role.
$show_view_toggle = $is_peer_mentor;

// Initial View logic:
$authorized_to_mentor = (in_array($user_type, ['mentor', 'admin']) || $is_peer_mentor);
$initial_view = ($authorized_to_mentor) ? 'mentor' : 'mentee';

// Override if specifically requested or if they ARE only mentes
if (!$authorized_to_mentor) {
    $initial_view = 'mentee';
}
?>

<link rel="stylesheet" href="../../recursos/css/pages/mentorship.css?v=<?php echo time(); ?>">

<div class="mentorship-container" style="position: relative;">

    <!-- Back Button -->
    <button onclick="window.history.back()" style="position: absolute; top: 1.5rem; left: 1rem; z-index: 100; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; font-size: 1rem;" onmouseover="this.style.transform='scale(1.1)'; this.style.background='rgba(0,0,0,0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(0,0,0,0.4)';">
        <i class="fas fa-arrow-left"></i>
    </button>
    <!-- Dashboard Section -->
    <?php if ($user_type != 'investor'): ?>
    <div class="dashboard-card" data-aos="fade-up" <?php if($user_pic): ?> style="--user-bg: url('<?php echo $user_pic; ?>');" <?php endif; ?>>
        
        <!-- View Toggle & Tabs Header -->
        <div class="dashboard-header">
            
            <?php if ($show_view_toggle): ?>
            <div class="view-toggle">
                <button id="viewAsMentee" class="btn-toggle" onclick="setDashboardView('mentee')">
                    <i class="fas fa-user-graduate"></i> Sou Mentorado
                </button>
                <button id="viewAsMentor" class="btn-toggle" onclick="setDashboardView('mentor')">
                    <i class="fas fa-user-tie"></i> Sou Mentor
                </button>
            </div>
            <?php endif; ?>

            <div class="mentor-tabs">
                <button class="m-tab active" data-tab="tasks" onclick="switchMentorTab('tasks', this)">
                    <i class="fas fa-check-circle"></i> Progresso
                </button>
                <button id="assignmentsTabBtn" class="m-tab" data-tab="assignments" onclick="switchMentorTab('assignments', this)" style="display: none;">
                    <i class="fas fa-handshake"></i> Atribuições
                </button>
                <button class="m-tab" data-tab="scheduler" onclick="switchMentorTab('scheduler', this)">
                    <i class="fas fa-calendar-alt"></i> Agenda
                </button>
                <button class="m-tab" data-tab="resources" onclick="switchMentorTab('resources', this)">
                    <i class="fas fa-folder-open"></i> Materiais
                </button>
                <button class="m-tab" data-tab="notices" onclick="switchMentorTab('notices', this)">
                    <i class="fas fa-bullhorn"></i> Mural
                </button>
                <button class="m-tab" data-tab="legal" onclick="switchMentorTab('legal', this)">
                    <i class="fas fa-file-signature"></i> Jurídico
                </button>
                <button class="m-tab mentor-only-tab" data-tab="projects" onclick="switchMentorTab('projects', this)" style="display: none; border-color: var(--accent-orange); color: var(--accent-orange);">
                    <i class="fas fa-rocket"></i> Revisão de Projectos
                </button>
            </div>
        </div>

        <!-- Tab Contents -->
        
        <!-- TAB: TASKS -->
        <div id="tab-tasks" class="mentor-tab-content">
            <div class="tab-header">
                <h4 style="margin: 0; color: white;">Trilha de Tarefas</h4>
                <button id="addTaskBtn" class="btn-primary-small mentor-only-btn" onclick="openModal('addTaskModal')" style="display: none;">
                    <i class="fas fa-plus"></i> Atribuir Tarefa
                </button>
            </div>
            <div id="tasksList" class="tab-grid"></div>
        </div>

        <!-- TAB: SCHEDULER -->
        <div id="tab-scheduler" class="mentor-tab-content" style="display: none;">
            <div class="tab-header">
                <h4 style="margin: 0; color: white;">Agenda de Reuniões</h4>
                <button id="addSlotBtn" class="btn-primary-small mentor-only-btn" onclick="openModal('addSlotModal')" style="display: none;">
                    <i class="fas fa-plus"></i> Agendar Reunião
                </button>
            </div>
            <div id="slotsList" class="vertical-list"></div>
        </div>

        <!-- TAB: RESOURCES -->
        <div id="tab-resources" class="mentor-tab-content" style="display: none;">
            <div class="tab-header">
                <h4 style="margin: 0; color: white;">Repositório de Recursos</h4>
                <button id="addResourceBtn" class="btn-primary-small mentor-only-btn" onclick="openModal('addResourceModal')" style="display: none;">
                    <i class="fas fa-upload"></i> Subir Material
                </button>
            </div>
            <div id="resourcesList" class="tab-grid"></div>
        </div>

        <!-- TAB: NOTICES -->
        <div id="tab-notices" class="mentor-tab-content" style="display: none;">
            <div class="tab-header">
                <h4 style="margin: 0; color: white;">Mural de Avisos</h4>
                <button id="addNoticeBtn" class="btn-primary-small mentor-only-btn" onclick="openModal('addNoticeModal')" style="display: none;">
                    <i class="fas fa-bullhorn"></i> Postar Aviso
                </button>
            </div>
            <div id="noticesList" class="tab-list"></div>
        </div>

        <!-- TAB: ASSIGNMENTS -->
        <div id="tab-assignments" class="mentor-tab-content" style="display: none;">
            <div class="tab-header"><h4 style="margin: 0; color: white;">Atribuições Administrativas</h4></div>
            <div id="assignmentsList" class="tab-grid"></div>
        </div>

        <!-- TAB: LEGAL -->
        <div id="tab-legal" class="mentor-tab-content" style="display: none;">
            <div class="tab-header"><h4 style="margin: 0; color: white;">Documentos Legais</h4></div>
            <div id="legalList" class="tab-grid"></div>
        </div>

        <!-- TAB: PROJECT REVIEWS -->
        <div id="tab-projects" class="mentor-tab-content" style="display: none;">
            <div class="tab-header">
                <h4 style="margin: 0; color: white;">Supervisão de Projectos Investidos</h4>
            </div>
            <div style="background: rgba(247, 148, 29, 0.05); padding: 1rem; border-radius: 12px; border: 1px solid rgba(247, 148, 29, 0.1); margin-bottom: 1.5rem; display: flex; gap: 0.8rem; align-items: center;">
                <i class="fas fa-shield-alt" style="color: var(--accent-orange);"></i>
                <p style="margin: 0; font-size: 0.8rem; color: rgba(255,255,255,0.6); line-height: 1.4;">Como mentor, deves validar os progressos dos teus mentoreados antes de seguirem para a KALIYE Admin.</p>
            </div>
            <div id="projectReviewsList" class="tab-grid"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bloco condicional envolvente: Se o utilizador não for um orientador aprovado, toda a secção inferior do Grid não será enviada ao HTML, já que a outra caixa 'Encontrar Mentores' foi apagada à pedido do superior -->
    <?php if ($authorized_to_mentor): ?>
    <!-- Navegação Rápida (Exclusiva para Mentores Orientadores agora) -->
    <!-- Definimos as colunas estritamente como 1 (1fr) no Grid CSS, pois agora ele abriga solitariamente o botão 'Orientar Alunos' e focá-lo no centro beneficia a visibilidade em vez de esticar a página -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem; margin-top: 2rem;">
        
        <!-- Correção Crítica de Caminhos: A ruta prévia 'explore_students.php' falhava gravemente (dava branca/404) pois tencionava carregar dentro desta mesma pasta (/mentoria/) um arquivo que só existe em "/explorar/" -->
        <!-- Ruta nova testada confiavelmente saindo da /mentoria e descendo para /explorar/ no diretório pai '../' -->
        <a href="../explorar/explore_students.php" style="text-decoration: none;">
            <!-- Cartão único retificado que suporta visualmente a hiperligação acima, sem margens indesejadas e com os efeitos originais de transição -->
            <div class="dashboard-card" style="margin-bottom: 0; padding: 2rem; text-align: center; border-color: rgba(247, 148, 29, 0.2); transition: 0.3s;" onmouseover="this.style.transform='translateY(-10px)'; this.style.borderColor='rgba(247, 148, 29, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(247, 148, 29, 0.2)';">
                <i class="fas fa-graduation-cap" style="font-size: 2.5rem; color: #f7941d; margin-bottom: 1rem;"></i>
                <!-- Foi mantida a correcção ortográfica do termo errôneo de 'Oriental' para 'Orientar' sob design unificado em branco -->
                <h4 style="color: white; margin: 0;">Orientar Alunos</h4>
                <p style="color: rgba(255,255,255,0.4); font-size: 0.85rem; margin-top: 5px;">Assume o teu papel na mentoria em cascata.</p>
            </div>
        </a>
        <!-- Fechando de forma higiênica e estruturante a hiperligação para impedir que mais ficheiros ganhem atributos de ligação vazios se adicionados subsequentemente -->
    </div>
    <!-- Fim do escopo estrito de visualização que resguarda os perfis de estudante ou não autorizados -->
    <?php endif; ?>
</div>

<!-- Modals & Scripts -->
<?php include '../../inclusoes/components/mentorship_modals.php'; ?>

<!-- Pass Initial View to JS -->
<script>
    const initialView = '<?php echo $initial_view; ?>';
    const currentUserId = <?php echo (int)$user_id; ?>;
</script>
<script src="../../recursos/js/pages/mentorship.js?v=<?php echo time(); ?>"></script>

<?php require_once '../../inclusoes/rodape.php'; ?>

