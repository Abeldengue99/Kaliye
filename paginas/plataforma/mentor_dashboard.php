<?php
/**
 * mentor_dashboard.php - Mentor Proposal Tracking
 */

$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';
require_once '../../inclusoes/asset_helper.php';

// Access Control
if ($_SESSION['user_type'] != 'mentor') {
    header("Location: ../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$view_mode = 'my_proposals'; // Enforce proposals view for Phase 1

// Projects Query (Mentorship Applications)
$select_fields = "p.*, u.full_name as owner_name, u.user_type as owner_type, u.is_verified as owner_verified, u.verification_status as owner_verification_status, u.profile_pic,
          ma.status as my_investment_status, ma.created_at as my_investment_date";

$query = "SELECT $select_fields
          FROM projects p
          JOIN users u ON p.owner_id = u.user_id
          JOIN project_mentorship_applications ma ON p.project_id = ma.project_id
          WHERE ma.mentor_id = ? 
          ORDER BY ma.created_at DESC";
$params = [$user_id];

$stmt = $db->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

?>

<!-- Styles (using investor styles for now since layout is identical) -->
<link rel="stylesheet" href="../../recursos/css/pages/investor_dashboard.css?v=<?php echo aksantiAssetVersion('recursos/css/pages/investor_dashboard.css'); ?>">

<!-- Content -->
<div class="investor-dashboard">
    <!-- Header Compacto -->
    <div class="investor-hero">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.6rem; font-weight: 900; letter-spacing: -0.5px; margin: 0;">
                    Minhas Propostas
                </h1>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.3rem; max-width: 500px;">
                    Acompanhe o estado das propostas de mentoria que enviou aos projetos.
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <div style="background: rgba(0,0,0,0.2); padding: 3px; border-radius: 10px; border: 1px solid var(--glass-border); display: flex;">
                    <a href="../../index.php#projectFeedContainer" style="padding: 0.45rem 0.85rem; border-radius: 7px; text-decoration: none; font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.5); background: transparent; transition: 0.2s;">Explorar</a>
                    <a href="?view=my_proposals" style="padding: 0.45rem 0.85rem; border-radius: 7px; text-decoration: none; font-size: 0.78rem; font-weight: 700; color: #fff; background: var(--accent-orange); transition: 0.2s;">Propostas</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="dashboard-layout">
        <!-- Projects Feed -->
        <div class="projects-feed" style="width: 100%; max-width: 1200px; margin: 0 auto;">
            <?php if (count($projects) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($projects as $project): ?>
                        <?php include '../../inclusoes/components/mentor_project_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="glass" style="padding: 3rem 1.5rem; text-align: center; border-radius: 20px;">
                    <i class="fas fa-file-signature" style="font-size: 2.5rem; color: var(--text-secondary); opacity: 0.25; margin-bottom: 1rem;"></i>
                    <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Nenhuma proposta encontrada</h3>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Ainda não enviou propostas de mentoria para nenhum projeto.</p>
                    <a href="../../index.php#projectFeedContainer" class="btn-primary" style="width: auto; padding: 0.7rem 1.5rem; display: inline-block; text-decoration: none; font-size: 0.85rem;">Explorar Projetos</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../inclusoes/rodape.php'; ?>
