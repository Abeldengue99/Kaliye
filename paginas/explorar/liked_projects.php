<?php
/**
 * liked_projects.php - Projectos que o Utilizador Adorou (Aksanti Elite)
 */
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

// Proteção: Apenas utilizadores autenticados
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../paginas/guest/landing.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$header_user_id = $user_id; // For post_card compatibility
$elite_orange = '#f7941d';

// --- PAGINAÇÃO ---
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Contagem total para paginação
$count_query = "SELECT COUNT(*) FROM project_likes WHERE user_id = :uid";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute([':uid' => $user_id]);
$total_liked = $count_stmt->fetchColumn();
$total_pages = ceil($total_liked / $limit);

// Obter projectos que o utilizador deu LIKE (Corrigido para author_role)
$query = "SELECT p.*, u.full_name, u.profile_pic, u.user_type as author_role, u.is_verified,
          (SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id) as like_count,
          (SELECT COUNT(*) FROM project_comments WHERE project_id = p.project_id) as comment_count,
          EXISTS(SELECT 1 FROM project_likes WHERE project_id = p.project_id AND user_id = :curr_uid) as user_liked
          FROM projects p 
          JOIN users u ON p.owner_id = u.user_id
          JOIN project_likes pl ON p.project_id = pl.project_id
          WHERE pl.user_id = :curr_uid
          ORDER BY pl.created_at DESC
          LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($query);
$stmt->execute([':curr_uid' => $user_id]);
$liked_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para compatibilidade com post_card.php
$is_privileged_viewer = in_array($_SESSION['user_type'], ['investor', 'mentor', 'admin']);
?>

<link rel="stylesheet" href="../../recursos/css/dashboard-aksanti-elite.css?v=<?php echo time(); ?>">

<div style="max-width: 1400px; margin: 0 auto; padding: 2rem 5%; min-height: 80vh;">
    
    <!-- Cabeçalho -->
    <div style="margin-bottom: 3rem;" data-aos="fade-down">
        <h6 style="color: var(--elite-orange); font-size: 0.7rem; font-weight: 850; text-transform: uppercase; letter-spacing: 3px; margin: 0 0 0.5rem;">Cura Pessoal</h6>
        <h1 style="font-size: 2.2rem; font-weight: 900; color: #fff; margin: 0; letter-spacing: -1px;">Projectos que Adorei</h1>
        <p style="color: var(--elite-text-muted); margin-top: 0.5rem; font-size: 0.9rem;">Gere e acompanha os projectos que despertaram o teu interesse.</p>
    </div>

    <!-- Lista de Projectos (Cards Menores e Grade Compacta) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;" data-aos="fade-up">
        <?php if (!empty($liked_projects)): ?>
            <?php foreach ($liked_projects as $post): 
                $is_owner = ($post['owner_id'] == $user_id);
                // O componente post_card.php já renderiza o card
                include '../../inclusoes/components/post_card.php'; 
            endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; padding: 8rem 2rem; text-align: center; background: rgba(255,255,255,0.01); border: 1px dashed var(--surface-5); border-radius: 24px;">
                <i class="fas fa-heart-broken" style="font-size: 3rem; color: var(--surface-3); margin-bottom: 1.5rem;"></i>
                <h3 style="color: var(--surface-40); font-weight: 800;">Ainda não adoraste nada</h3>
                <p style="color: var(--surface-20); font-size: 0.85rem;">Explora o ecossistema para encontrar projectos inspiradores.</p>
                <a href="../../index.php" class="btn-invest-elite" style="display: inline-flex; width: auto; margin-top: 2rem; padding: 0.8rem 2.5rem; text-decoration: none; border-radius: 12px; font-size: 0.85rem;">
                    EXPLORAR ECOSSISTEMA
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Paginação -->
    <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin-top: 4rem;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" 
                   style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; text-decoration: none; font-weight: 800; font-size: 0.9rem; transition: 0.3s; 
                          background: <?php echo $page == $i ? 'var(--elite-orange)' : 'var(--surface-5)'; ?>; 
                          color: <?php echo $page == $i ? '#fff' : 'var(--surface-40)'; ?>;
                          border: 1px solid <?php echo $page == $i ? 'var(--elite-orange)' : 'var(--surface-10)'; ?>;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
// Modais Necessários
require_once '../../inclusoes/rodape.php'; 
?>
