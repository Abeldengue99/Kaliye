<?php
// paginas/conta/my_expertise.php - Gerir Minhas Especialidades
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = '../../';

// Check permission
$is_student = in_array($_SESSION['user_type'] ?? '', ['high_student', 'univ_student']);
if ($is_student) {
    require_once '../../configuracoes/base_dados.php';
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT mentorship_status FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $m_status = $stmt->fetchColumn();
    
    if ($m_status != 'approved') {
        header("Location: profile.php?error=access_denied&details=Apenas mentores aprovados podem gerir especialidades.");
        exit;
    }
}

require_once '../../inclusoes/cabecalho.php';
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; position: relative; min-height: 80vh;">
    <!-- Botão Voltar -->
    <button onclick="window.history.back()" style="position: absolute; top: 1.5rem; left: 1rem; z-index: 100; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
        <i class="fas fa-arrow-left"></i>
    </button>
    
    <div style="margin-top: 3.5rem;">
        <?php include '../../inclusoes/components/expertise_system.php'; ?>
    </div>
</div>

<?php require_once '../../inclusoes/rodape.php'; ?>

