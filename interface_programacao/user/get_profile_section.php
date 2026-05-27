<?php
/**
 * get_profile_section.php - Roteador de Componentes de Perfil
 * Entrega o HTML parcial para as abas do perfil unificado.
 */
session_start();
require_once '../../configuracoes/base_dados.php';
$db = (new Database())->getConnection();

$section = $_GET['section'] ?? 'overview';
$user_id = (int)($_GET['user_id'] ?? 0);
$current_user_id = $_SESSION['user_id'] ?? 0;
$is_own_profile = ($user_id === $current_user_id);

// Buscar dados do utilizador para os componentes
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('<div class="content-card" style="text-align:center; padding:3rem;">Usuário não encontrado.</div>');
}

// Injeção de dependências globais para os componentes (labels, etc)
$user_type_labels = [
    'univ_student' => 'Estudante Universitário',
    'high_student' => 'Estudante do Ensino Médio',
    'mentor' => 'Mentor Profissional',
    'investor' => 'Investidor Angel/VC',
    'admin' => 'Administrador'
];

// Roteamento de Componentes
switch ($section) {
    case 'kyc':
        if (!$is_own_profile && $_SESSION['user_type'] !== 'admin') {
            echo '<div class="content-card" style="text-align:center; padding:3rem;">Acesso restrito.</div>';
            break;
        }
        include '../../inclusoes/components/profile_kyc_content.php';
        break;

    case 'settings':
        if (!$is_own_profile && $_SESSION['user_type'] !== 'admin') {
            echo '<div class="content-card" style="text-align:center; padding:3rem;">Acesso restrito.</div>';
            break;
        }
        include '../../inclusoes/components/profile_settings_content.php';
        break;

    case 'overview':
    default:
        // Buscar skills para o overview
        $skills = [];
        $expertises = [];
        try {
            $s_stmt = $db->prepare("SELECT s.name as skill_name, us.user_skill_id FROM skills s JOIN user_skills us ON s.skill_id = us.skill_id WHERE us.user_id = ?");
            $s_stmt->execute([$user_id]);
            $skills = $s_stmt->fetchAll();

            $exp_stmt = $db->prepare("SELECT * FROM user_expertises WHERE user_id = ?");
            $exp_stmt->execute([$user_id]);
            $expertises = $exp_stmt->fetchAll();
        } catch (Throwable $e) {}
        
        include '../../inclusoes/components/profile_content.php';
        break;
}
