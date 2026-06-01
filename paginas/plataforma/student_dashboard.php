<?php
/**
 * paginas/plataforma/student_dashboard.php
 * Dashboard de Candidaturas para Estudantes
 */

$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';
require_once '../../inclusoes/asset_helper.php';

// Access Control
if ($_SESSION['user_type'] != 'student' && $_SESSION['user_type'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$view_mode = 'my_applications'; // Dashboard mode

// Ensure table exists
require_once '../../inclusoes/ProjectWorkflowSchema.php';
ensureStudentApplicationsSchema($db);

// Query for student applications
$select_fields = "p.*, u.full_name as owner_name, u.user_type as owner_type, u.is_verified as owner_verified, u.verification_status as owner_verification_status, u.profile_pic,
          sa.status as my_application_status, sa.created_at as my_application_date, sa.application_id as my_application_id";

$query = "SELECT $select_fields
          FROM projects p
          JOIN users u ON p.owner_id = u.user_id
          JOIN project_student_applications sa ON p.project_id = sa.project_id
          WHERE sa.student_id = ? 
          ORDER BY sa.created_at DESC";
$params = [$user_id];

$stmt = $db->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Get application stats
$stats_query = "
    SELECT 
        COUNT(*) as total_applications,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_count,
        SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM project_student_applications
    WHERE student_id = ?
";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

?>

<!-- Styles (using investor styles since layout is identical) -->
<link rel="stylesheet" href="../../recursos/css/pages/investor_dashboard.css?v=<?php echo aksantiAssetVersion('recursos/css/pages/investor_dashboard.css'); ?>">

<!-- Content -->
<div class="investor-dashboard">
    <!-- Header Compacto -->
    <div class="investor-hero">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.6rem; font-weight: 900; letter-spacing: -0.5px; margin: 0;">
                    Minhas Candidaturas
                </h1>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.3rem; max-width: 500px;">
                    Acompanhe o estado das candidaturas que enviou para participar em projetos.
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <div style="background: rgba(0,0,0,0.2); padding: 3px; border-radius: 10px; border: 1px solid var(--glass-border); display: flex;">
                    <a href="../../index.php#projectFeedContainer" style="padding: 0.45rem 0.85rem; border-radius: 7px; text-decoration: none; font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.5); background: transparent; transition: 0.2s;">Explorar</a>
                    <a href="?view=my_applications" style="padding: 0.45rem 0.85rem; border-radius: 7px; text-decoration: none; font-size: 0.78rem; font-weight: 700; color: #fff; background: var(--accent-orange); transition: 0.2s;">Candidaturas</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; padding: 0 1rem;">
        <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 15px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 1.8rem; font-weight: 900; color: #f7941d; margin-bottom: 0.5rem;">
                <?php echo $stats['total_applications'] ?? 0; ?>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">
                Total Candidaturas
            </div>
        </div>
        <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 15px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 1.8rem; font-weight: 900; color: #60a5fa; margin-bottom: 0.5rem;">
                <?php echo $stats['submitted_count'] ?? 0; ?>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">
                Enviadas
            </div>
        </div>
        <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 15px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 1.8rem; font-weight: 900; color: #a78bfa; margin-bottom: 0.5rem;">
                <?php echo $stats['under_review_count'] ?? 0; ?>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">
                Em Análise
            </div>
        </div>
        <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 15px; padding: 1.5rem; text-align: center;">
            <div style="font-size: 1.8rem; font-weight: 900; color: #10b981; margin-bottom: 0.5rem;">
                <?php echo $stats['approved_count'] ?? 0; ?>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">
                Aprovadas
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
                        <!-- Student Application Card -->
                        <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 1.5rem; overflow: hidden; transition: all 0.3s;">
                            <!-- Header com Status -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <div style="flex: 1;">
                                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 900;">
                                        <?php echo htmlspecialchars($project['title']); ?>
                                    </h3>
                                    <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 0.85rem;">
                                        Por <?php echo htmlspecialchars($project['owner_name']); ?>
                                    </p>
                                </div>
                                <!-- Status Badge -->
                                <?php
                                $status_color = [
                                    'submitted' => 'rgba(247,148,29,0.1);color: #f7941d',
                                    'under_review' => 'rgba(96,165,250,0.1);color: #60a5fa',
                                    'approved' => 'rgba(16,185,129,0.1);color: #10b981',
                                    'rejected' => 'rgba(239,68,68,0.1);color: #ef4444'
                                ];
                                $color = $status_color[$project['my_application_status']] ?? 'rgba(255,255,255,0.1);color: #ccc';
                                $status_label = [
                                    'submitted' => 'Enviada',
                                    'under_review' => 'Em Análise',
                                    'approved' => 'Aprovada',
                                    'rejected' => 'Rejeitada'
                                ];
                                $label = $status_label[$project['my_application_status']] ?? $project['my_application_status'];
                                ?>
                                <span style="background: <?php echo $color; ?>; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; white-space: nowrap; margin-left: 1rem;">
                                    <?php echo $label; ?>
                                </span>
                            </div>

                            <!-- Informações -->
                            <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem; margin-bottom: 1rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.85rem;">
                                    <div>
                                        <p style="color: var(--text-secondary); margin: 0 0 0.3rem 0; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Data Candidatura</p>
                                        <p style="margin: 0; font-weight: 600;">
                                            <?php echo date('d M Y', strtotime($project['my_application_date'])); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p style="color: var(--text-secondary); margin: 0 0 0.3rem 0; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Proprietário</p>
                                        <p style="margin: 0; font-weight: 600;">
                                            <?php echo htmlspecialchars($project['owner_type']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Ações -->
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="viewApplicationDetails(<?php echo $project['project_id']; ?>, <?php echo $project['my_application_id']; ?>)" style="flex: 1; padding: 0.7rem; background: var(--accent-orange); color: #000; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 0.85rem; transition: 0.2s;">
                                    Ver Detalhes
                                </button>
                                <button onclick="viewProject(<?php echo $project['project_id']; ?>)" style="flex: 1; padding: 0.7rem; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 0.85rem; transition: 0.2s;">
                                    Ver Projeto
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="glass" style="padding: 3rem 1.5rem; text-align: center; border-radius: 20px;">
                    <i class="fas fa-file-signature" style="font-size: 2.5rem; color: var(--text-secondary); opacity: 0.25; margin-bottom: 1rem;"></i>
                    <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Nenhuma candidatura encontrada</h3>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Ainda não enviou candidaturas para nenhum projeto. Explore os projetos disponíveis e candidata-te aos que mais te interessam.</p>
                    <a href="../../index.php#projectFeedContainer" class="btn-primary" style="width: auto; padding: 0.7rem 1.5rem; display: inline-block; text-decoration: none; font-size: 0.85rem;">Explorar Projetos</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function viewApplicationDetails(projectId, applicationId) {
    // This could open a modal or navigate to a details page
    alert('Detalhes da candidatura #' + applicationId + ' para projeto #' + projectId);
    // TODO: Implement details modal or page
}

function viewProject(projectId) {
    window.location.href = '../../index.php?view=project&id=' + projectId;
}
</script>

<?php require_once '../../inclusoes/rodape.php'; ?>
