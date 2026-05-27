<?php
/**
 * paginas/social/profile.php — O Hub de Identidade Aksanti
 * 
 * Este ficheiro é a página central de perfil do ecossistema. 
 * Ele implementa uma arquitetura baseada em componentes (cabecalho, hero, sidebar, content, rodape)
 * e possui regras de segurança críticas (Security Gates) para proteger a privacidade dos
 * investidores e garantir que apenas utilizadores verificados interajam socialmente.
 */

session_start();
$base_url = '../../'; // Caminho relativo para a raiz, facilitando a inclusão de assets.

// Carregamento do cabeçalho global que contém o menu e metadados.
require_once '../../inclusoes/cabecalho.php';

/**
 * LÓGICA DE VISUALIZAÇÃO
 * Por padrão, o utilizador vê o seu próprio perfil. Se houver um 'user_id' na URL,
 * tentamos carregar um perfil de terceiros, após validação de segurança.
 */
$current_user_id = $_SESSION['user_id'] ?? 0;
$is_verified = (isset($_SESSION['is_verified']) && $_SESSION['is_verified']);

// Perfil Alvo
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : $current_user_id;

$is_own_profile = ($profile_id == $current_user_id);
$user_id = $profile_id; // Define $user_id para ser usado no restante do script

if (!$is_own_profile) {
    
    /**
     * BARREIRA DE SEGURANÇA (KYC GATE)
     * Regra de Negócio: Utilizadores não verificados não podem "cuscar" perfis alheios.
     * Exceção: Administradores e Adms Escolares têm passe livre para auditoria.
     */
    if (!$is_verified && $_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'school_admin') {
        header("Location: " . $base_url . "paginas/social/profile.php?verify_required=1");
        exit();
    }

    $is_own_profile = false;
    $user_id = (int)$profile_id; // Mantemos o ID já validado em $profile_id da linha 26
    
    /**
     * VERIFICAÇÃO DE CONEXÃO
     * Buscamos o status da amizade/conexão para decidir quais botões mostrar (Conectar, Aceitar, etc).
     * Usamos min/max para garantir que a ordem dos IDs na query seja sempre a mesma do DB.
     */
    $u1 = min($_SESSION['user_id'], $user_id);
    $u2 = max($_SESSION['user_id'], $user_id);
    $conn_stmt = $db->prepare("SELECT status, user_id_1, user_id_2, requester_id FROM user_connections WHERE user_id_1 = ? AND user_id_2 = ?");
    $conn_stmt->execute([$u1, $u2]);
    $connection = $conn_stmt->fetch();
}

/**
 * BUSCA DE DADOS DO UTILIZADOR
 * Carregamos o objeto principal do utilizador a partir do PostgreSQL.
 */
$query = "SELECT u.* FROM users u WHERE u.user_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch();

// Se o utilizador tentou aceder a um ID inexistente, mostramos erro elegante.
if (!$user) {
    echo "<div style='padding: 2rem; text-align: center; color: white;'>Utilizador não encontrado.</div>";
    require_once '../../inclusoes/rodape.php';
    exit();
}

/**
 * PROTEÇÃO DE PRIVACIDADE DO INVESTIDOR
 * Regra de Negócio: O perfil de um Investidor é "Top Secret". 
 * Apenas o próprio investidor ou o Administrador podem visualizar.
 * Isso evita assédio direto e protege os dados financeiros dos parceiros.
 */
if (!$is_own_profile && $user['user_type'] === 'investor' && (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin')) {
    echo "<div class='container' style='margin-top: 5rem; margin-bottom: 5rem;'>
            <div class='glass' style='padding: 4rem 2rem; text-align: center; border-radius: 20px;'>
                <i class='fas fa-lock' style='font-size: 4rem; color: var(--accent-orange); margin-bottom: 2rem; display: block;'></i>
                <h2 style='color: white; margin-bottom: 1rem;'>Acesso Restrito</h2>
                <p style='color: var(--text-secondary); max-width: 500px; margin: 0 auto 2rem;'>
                    Os perfis de Investidores são privados e acessíveis apenas pela administração para preservar a integridade da plataforma.
                </p>
                <a href='{$base_url}pages/index.php' class='btn-primary' style='display: inline-block; width: auto; padding: 0.8rem 2.5rem; text-decoration: none;'>
                    <i class='fas fa-arrow-left'></i> Voltar ao Início
                </a>
            </div>
          </div>";
    require_once '../../inclusoes/rodape.php';
    exit();
}

// Mapeamento amigável de tipos de utilizador para a UI.
$user_type_labels = [
    'univ_student' => __('univ_student'),
    'high_student' => __('high_student'),
    'mentor' => __('mentor'),
    'investor' => __('investor'),
    'admin' => __('admin')
];

/**
 * GESTÃO DE ESPECIALIDADES (Expertises)
 * Carregamos as áreas de conhecimento onde o utilizador se destaca.
 */
$exp_query = "SELECT ue.*, ka.name as area_name, ka.icon, ka.color, ka.category
              FROM user_expertises ue
              JOIN knowledge_areas ka ON ue.area_id = ka.area_id
              WHERE ue.user_id = ?
              ORDER BY ue.is_primary DESC, ue.proficiency_level DESC";
$exp_stmt = $db->prepare($exp_query);
$exp_stmt->execute([$user_id]);
$expertises = $exp_stmt->fetchAll(PDO::FETCH_ASSOC);

// Função auxiliar para definir cores e labels de proficiência (Elite Badges).
function getProficiencyBadge($level) {
    switch($level) {
        case 'beginner': return ['color' => '#22c55e', 'bg' => 'rgba(34, 197, 94, 0.15)', 'label' => 'Iniciante'];
        case 'intermediate': return ['color' => '#eab308', 'bg' => 'rgba(234, 179, 8, 0.15)', 'label' => 'Intermédio'];
        case 'advanced': return ['color' => '#f97316', 'bg' => 'rgba(249, 115, 22, 0.15)', 'label' => 'Avançado'];
        case 'expert': return ['color' => '#a855f7', 'bg' => 'rgba(168, 85, 247, 0.15)', 'label' => 'Especialista'];
        default: return ['color' => '#94a3b8', 'bg' => 'rgba(148, 163, 184, 0.15)', 'label' => 'N/A'];
    }
}

/**
 * CONTAGEM DE ESTATÍSTICAS (Métricas de Engajamento)
 * Buscamos conexões aceitas e total de projectos publicados para o "Social Proof".
 */
$stats_conn = $db->prepare("SELECT COUNT(*) FROM user_connections WHERE (user_id_1 = ? OR user_id_2 = ?) AND status = 'accepted'");
$stats_conn->execute([$user_id, $user_id]);
$total_connections = $stats_conn->fetchColumn();

$stats_proj = $db->prepare("SELECT COUNT(*) FROM projects WHERE owner_id = ?");
$stats_proj->execute([$user_id]);
$total_projects = $stats_proj->fetchColumn();

$total_expertises = count($expertises);
$sticky_avatar = getUserAvatarUrl($user['user_type'], $user['mentorship_status'] ?? 'unsubmitted', $user['profile_pic'] ?? '');
$sticky_avatar_src = (strpos($sticky_avatar, 'http') === 0) ? $sticky_avatar : $base_url . $sticky_avatar;
?>

<!-- Importação de estilos específicos da página de perfil (Elite Layout) -->
<link rel="stylesheet" href="../../recursos/css/pages/profile.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../../recursos/css/components/profile_tabs.css?v=<?php echo time(); ?>">

<div class="profile-page-wrapper">

    <!-- Botão Flutuante de Retrocesso (UX Premium) -->
    <button onclick="window.history.back()" style="position: absolute; top: 1.5rem; left: 1.5rem; z-index: 100; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary); width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
        <i class="fas fa-arrow-left"></i>
    </button>

    <!-- ── STICKY BAR ── 
         Excelente para UX: Surge quando o utilizador faz scroll para baixo, 
         mantendo a identidade do perfil e as estatísticas sempre visíveis. 
    -->
    <div class="profile-sticky-bar" id="profileStickyBar">
        <div class="sticky-user-info">
            <img src="<?php echo htmlspecialchars($sticky_avatar_src); ?>" class="sticky-avatar" alt="foto">
            <div>
                <div class="sticky-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="sticky-role"><?php echo $user_type_labels[$user['user_type']] ?? $user['user_type']; ?></div>
            </div>
        </div>
        <div class="sticky-stats">
            <div class="sticky-stat"><div class="sticky-stat-val"><?php echo $total_connections; ?></div><div class="sticky-stat-label">Conexões</div></div>
            <div class="sticky-stat"><div class="sticky-stat-val"><?php echo $total_projects; ?></div><div class="sticky-stat-label">Projectos</div></div>
            <div class="sticky-stat"><div class="sticky-stat-val" style="color: var(--accent-orange);"><?php echo $user['avaliacao'] ?? 0; ?></div><div class="sticky-stat-label">Avaliação</div></div>
            <div class="sticky-stat"><div class="sticky-stat-val"><?php echo $total_expertises; ?></div><div class="sticky-stat-label">Skills</div></div>
        </div>
        <?php if ($is_own_profile): ?>
        <button onclick="openMyProfileEdit()" class="btn-cover-primary" style="flex-shrink:0; font-size:0.8rem; padding:0.5rem 1.1rem;">
            <i class="fas fa-pen"></i> Editar Perfil
        </button>
        <?php endif; ?>
    </div>

    <!-- COMPONENTE: HERO / COVER CARD
         Gere a foto de capa, avatar principal e as principais CTAs de conexão/mensagem.
    -->
    <?php include '../../inclusoes/components/profile_hero.php'; ?>

    <!-- ════ BODY GRID: Arquitetura Moderna (Sidebar à Esquerda + Feed/Conteúdo à Direita) ════ -->
    <div class="profile-body-grid">

        <!-- LADO ESQUERDO: SIDEBAR 
             Informações rápidas, biografia, localização e redes sociais.
        -->
        <?php include '../../inclusoes/components/profile_sidebar.php'; ?>

        <!-- LADO DIREITO: COLUNA DE CONTEÚDO PRINCIPAL -->
        <div id="profileDynamicContent" style="width: 100%;">
            <?php 
                // Injeção de dependências para o Overview
                $skills = [];
                try {
                    $s_stmt = $db->prepare("SELECT s.name as skill_name, us.user_skill_id FROM skills s JOIN user_skills us ON s.skill_id = us.skill_id WHERE us.user_id = ?");
                    $s_stmt->execute([$user_id]);
                    $skills = $s_stmt->fetchAll();
                } catch (Throwable $e) {}
                
                include '../../inclusoes/components/profile_content.php'; 
            ?>
        </div>
    </div>

</div>

<?php if($is_own_profile): ?>
    <?php include '../../inclusoes/components/add_skill_modal.php'; ?>
    <?php include_once '../../inclusoes/components/mentor_app_modal.php'; ?>
<?php endif; ?>

<?php 
include '../../inclusoes/components/review_modal.php';
include '../../inclusoes/components/booking_modal.php';
include '../../inclusoes/components/masterclass_modal.php';
include '../../inclusoes/components/edit_availability_modal.php';
?>

<!-- Inclusão de Scripts Dinâmicos (AJAX de conexões, editar foto, etc) -->
<?php include '../../inclusoes/components/profile_scripts.php'; ?>

<script>
/**
 * INTERSECTION OBSERVER PARA STICKY BAR
 * Técnica avançada para detetar quando o utilizador passou do "Hero" e mostrar a barra secundária.
 */
(function() {
    const bar  = document.getElementById('profileStickyBar');
    const hero = document.getElementById('profileHero');
    if (!bar || !hero) return;
    const observer = new IntersectionObserver(
        ([e]) => bar.classList.toggle('is-visible', !e.isIntersecting),
        { threshold: 0.15 }
    );
    observer.observe(hero);
})();
</script>

<!-- MODAL: GESTÃO DE ESPECIALIDADES (Expertise System Premium) 
     Este modal usa um backgroun escuro com glassmorphism extremo para foco total.
-->
<div id="profileExpertiseModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(5, 10, 20, 0.92); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); z-index: 100001; justify-content: center; align-items: flex-start; padding: 2rem; overflow-y: auto;">
    <div style="background: rgba(13, 22, 40, 0.98); border: 1px solid rgba(247,148,29,0.25); border-radius: 24px; width: 100%; max-width: 1000px; margin: auto; box-shadow: 0 50px 100px rgba(0,0,0,0.8); position: relative; animation: modalAppear 0.4s ease-out;">
        <button onclick="document.getElementById('profileExpertiseModal').style.display='none'" style="position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; z-index: 10; font-size: 1.2rem;">&times;</button>
        <div style="padding: 3rem;">
            <?php include '../../inclusoes/components/expertise_system.php'; ?>
        </div>
    </div>
</div>

<style>
/* Micro-animações Premium para o status Online e entrada de Modals */
@keyframes pulse-online {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
@keyframes modalAppear {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Finalização do documento com o rodapé global. -->
<?php require_once '../../inclusoes/rodape.php'; ?>
