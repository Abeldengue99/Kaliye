<?php
/**
 * messages.php - Integrated Chat System
 * Refactored into a component-based structure.
 */
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';
require_once '../../inclusoes/ChatSecurity.php';

$current_user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
ChatSecurity::touchPresence($db, (int)$current_user_id);

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
    SELECT mg.*, 
        (SELECT full_name FROM users WHERE user_id = mg.mentor_id) as mentor_name
    FROM mentor_chat_groups mg 
    WHERE mg.mentor_id = :uid 
       OR mg.mentor_id IN (
           SELECT mentor_id FROM mentorship_contracts WHERE student_id = :uid AND status = 'active'
           UNION 
           SELECT mentor_id FROM mentorships WHERE mentee_id = :uid AND status = 'active'
       )
    ORDER BY mg.created_at DESC
");
$mentor_groups_stmt->execute([':uid' => $current_user_id]);
$mentor_groups = $mentor_groups_stmt->fetchAll();

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
        echo "<script>alert(" . json_encode($policy['reason']) . "); window.location.href='messages.php';</script>";
        exit;
    }
}
?>

<link rel="stylesheet" href="../../recursos/css/pages/messages.css?v=<?php echo time(); ?>">
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

<!-- Member Management Modal -->
<div id="membersModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div class="glass" style="width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; padding: 2rem; position: relative; border-radius: 20px;">
        <button onclick="closeMembersModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;"><i class="fas fa-times"></i></button>
        <h3 style="margin-bottom: 1.5rem; color: var(--accent-orange);"><i class="fas fa-users"></i> Gerir Membros</h3>
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

