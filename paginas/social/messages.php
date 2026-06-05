<?php
/**
 * messages.php - Integrated Chat System
 * Refactored into a component-based structure.
 */
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';
require_once '../../inclusoes/asset_helper.php';
require_once '../../inclusoes/ChatSecurity.php';

$current_user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
ChatSecurity::touchPresence($db, (int)$current_user_id);

// ✨ AUTO-FIX: Garantir que coluna group_image existe (redundância para segurança)
try {
    $db->exec("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM information_schema.columns 
                WHERE table_name = 'mentor_chat_groups' 
                AND column_name = 'group_image'
            ) THEN
                ALTER TABLE mentor_chat_groups ADD COLUMN group_image VARCHAR(500);
            END IF;
        END $$;
    ");
} catch (Exception $e) {
    // Silenciar - coluna pode já existir
}

if ($user_type === 'school_admin') {
    header("Location: institution_dashboard.php");
    exit();
}

// Fetch Generic Groups (Equipas)
$groups_stmt = $db->prepare("SELECT cg.*, (SELECT COUNT(*) FROM chat_group_members WHERE group_id = cg.group_id) as member_count
                             FROM chat_groups cg JOIN chat_group_members cgm ON cg.group_id = cgm.group_id
                             WHERE cgm.user_id = :uid ORDER BY cg.created_at DESC");
$groups_stmt->execute([':uid' => $current_user_id]);
$user_groups = $groups_stmt->fetchAll();

// Fetch Mentor VIP Groups (A nossa nova funcionalidade inovadora).
// Um aluno vê o grupo se tiver um contrato de mentoria válido. Um mentor vê os grupos criados por si.
$mentor_groups_stmt = $db->prepare("
    SELECT mg.id, mg.name, mg.mentor_id,
        (SELECT full_name FROM users WHERE user_id = mg.mentor_id) as mentor_name,
        (SELECT COUNT(*) FROM mentor_group_members WHERE group_id = mg.id) as member_count
    FROM mentor_chat_groups mg 
    LEFT JOIN mentor_group_members mgm ON mg.id = mgm.group_id AND mgm.user_id = :uid
    WHERE mg.mentor_id = :uid 
       OR mgm.user_id = :uid
    ORDER BY mg.created_at DESC
");
$mentor_groups_stmt->execute([':uid' => $current_user_id]);
$mentor_groups = $mentor_groups_stmt->fetchAll();

// Fetch Networking VIP Rooms
$vip_rooms_stmt = $db->prepare("
    SELECT c.id, c.title, c.description, c.status,
           (SELECT COUNT(*) FROM vip_chat_participants WHERE chat_id = c.id) as member_count
    FROM vip_chats c
    INNER JOIN vip_chat_participants p ON p.chat_id = c.id
    WHERE p.user_id = :uid
    ORDER BY c.created_at DESC
");
$vip_rooms_stmt->execute([':uid' => $current_user_id]);
$vip_rooms = $vip_rooms_stmt->fetchAll();

// Fetch Individual Conversations
$conv_stmt = $db->prepare("SELECT DISTINCT CASE WHEN sender_id = :uid THEN receiver_id ELSE sender_id END as contact_id, MAX(sent_at) as last_msg
                           FROM messages WHERE sender_id = :uid OR receiver_id = :uid
                           GROUP BY contact_id ORDER BY last_msg DESC");
$conv_stmt->execute([':uid' => $current_user_id]);
$conversations = $conv_stmt->fetchAll();

$user_type_labels = [
    'univ_student' => __('univ_student'),
    'high_student' => __('high_student'),
    'mentor' => __('mentor'),
    'investor' => __('investor'),
    'admin' => __('admin')
];

// Start Param Logic
if (isset($_GET['start'])) {
    $start_id = (int)$_GET['start'];
    $policy = ChatSecurity::canDirectMessage($db, (int)$current_user_id, $start_id);
    if (!$policy['allowed']) {
        // Silently redirect if user doesn't have permission
        header('Location: messages.php');
        exit;
    }
}
?>

<link rel="stylesheet" href="../../recursos/css/pages/messages.css?v=<?php echo aksantiAssetVersion('recursos/css/pages/messages.css'); ?>">
<style>
    .main-content-wrapper {
        max-width: none !important;
        padding-top: 0 !important;
    }
</style>

<div class="chat-page-shell">
<div class="chat-layout-elite" style="display: grid; grid-template-columns: 380px 1fr; height: 85vh; max-width: 1400px; margin: 18px auto 20px; padding: 0 2rem; position: relative; gap: 15px;">
    <!-- Back Button - Integrated properly into layout -->
    <div class="chat-back-wrap">
        <button onclick="window.history.back()" class="chat-back-btn" aria-label="Voltar">
            <i class="fas fa-arrow-left"></i>
        </button>
    </div>
    <!-- Contacts Sidebar -->
    <?php include '../../inclusoes/components/chat_sidebar.php'; ?>

    <!-- Chat Main Area -->
    <?php include '../../inclusoes/components/chat_area.php'; ?>
</div>
</div>

<!-- Edit Group Name Modal -->
<div id="editGroupNameModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.85); z-index: 999997; align-items: center; justify-content: center; padding: 1rem; display: none;">
    <div class="glass" style="width: 100%; max-width: 500px; padding: 2rem; position: relative; border-radius: 20px; box-shadow: 0 25px 100px rgba(0,0,0,0.8); border: 1px solid rgba(59,130,246,0.3);">
        <button onclick="document.getElementById('editGroupNameModal').style.display = 'none';" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem; z-index: 10000;"><i class="fas fa-times"></i></button>
        
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(59,130,246,0.2); display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 1.5rem;">
                <i class="fas fa-pen"></i>
            </div>
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem;">Editar Nome da Sala</h3>
                <p style="margin: 0.25rem 0 0; color: #3b82f6; font-size: 0.85rem; font-weight: 600;">Acção do chat</p>
            </div>
        </div>
        
        <p style="color: #cbd5e1; margin: 1rem 0; line-height: 1.5; font-size: 0.9rem;">
            Digite o novo nome para a sala VIP de mentoria.
        </p>
        
        <form onsubmit="submitEditGroupName(event)">
            <label style="display: block; font-size: 0.7rem; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; color: #3b82f6; margin-bottom: 0.5rem;">Novo Nome</label>
            <input type="text" id="editGroupNameInput" placeholder="Ex: Turma Advanced React" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white; font-size: 0.95rem; margin-bottom: 1.5rem;" autocomplete="off" />
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('editGroupNameModal').style.display = 'none';" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s;">
                    Cancelar
                </button>
                <button type="submit" style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s;">
                    <i class="fas fa-check"></i> Atualizar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Group Confirmation Modal -->
<div id="deleteGroupModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.85); z-index: 999998; align-items: center; justify-content: center; padding: 1rem; display: none;">
    <div class="glass" style="width: 100%; max-width: 450px; padding: 2rem; position: relative; border-radius: 20px; box-shadow: 0 25px 100px rgba(0,0,0,0.8); border: 1px solid rgba(239,68,68,0.3);">
        <button onclick="document.getElementById('deleteGroupModal').style.display = 'none';" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem; z-index: 10000;"><i class="fas fa-times"></i></button>
        
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(239,68,68,0.2); display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.5rem;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem;">Eliminar Grupo?</h3>
                <p style="margin: 0.25rem 0 0; color: #ef4444; font-size: 0.85rem; font-weight: 600;">Esta ação não pode ser desfeita</p>
            </div>
        </div>
        
        <p style="color: #cbd5e1; margin: 1rem 0; line-height: 1.5; font-size: 0.9rem;">
            Tem a certeza que deseja <strong>ELIMINAR PERMANENTEMENTE</strong> esta sala?<br><br>
            Todos os membros e mensagens serão removidos.
        </p>
        
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 1.5rem;">
            <button onclick="document.getElementById('deleteGroupModal').style.display = 'none';" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s;">
                Cancelar
            </button>
            <button onclick="confirmDeleteGroup()" style="background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s;">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>

<!-- Member Management Modal -->
<div id="membersModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; padding: 1rem; display: none;">
    <div class="glass" style="width: 100%; max-width: 650px; max-height: 85vh; overflow-y: auto; padding: 2rem; position: relative; border-radius: 20px; box-shadow: 0 25px 100px rgba(0,0,0,0.8);">
        <button onclick="closeMembersModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem; z-index: 10000;"><i class="fas fa-times"></i></button>
        <h3 style="margin-bottom: 0.5rem; color: var(--accent-orange);"><i class="fas fa-users"></i> Gerir Membros</h3>
        
        <!-- Barra de Ações do Grupo -->
        <div style="display: flex; gap: 8px; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <button onclick="editGroupName()" style="background: rgba(59,130,246,0.2); color: #3b82f6; border: 1px solid #3b82f6; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: 0.3s;">
                <i class="fas fa-edit"></i> Editar Nome
            </button>
            <button onclick="deleteGroup()" style="background: rgba(239,68,68,0.2); color: #ef4444; border: 1px solid #ef4444; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: 0.3s;">
                <i class="fas fa-trash"></i> Excluir Grupo
            </button>
        </div>
        
        <div id="currentMembersList" style="margin-bottom: 2rem;"></div>
        <div id="availableStudentsList"></div>
    </div>
</div>

<script>
    const AKSANTI_CONFIG = {
        userId: <?php echo json_encode($current_user_id); ?>,
        baseUrl: <?php echo json_encode($base_url); ?>,
        startReceiver: <?php echo isset($_GET['start']) ? (int)$_GET['start'] : 'null'; ?>
    };
</script>

<!-- Scripts -->
<?php include '../../inclusoes/components/chat_scripts.php'; ?>

<?php require_once '../../inclusoes/rodape.php'; ?>

