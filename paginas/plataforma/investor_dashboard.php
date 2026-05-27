<?php
/**
 * investor_dashboard.php - Premium Investment Pipeline
 * Refactored into a component-based structure.
 * 
 * Components:
 *  - includes/components/investor_dashboard_content.php  (Main HTML layout)
 *  - includes/components/investor_dashboard_barra_lateral.php  (Filter panel + transactions)
 *  - includes/components/investor_project_card.php       (Single project card)
 *  - includes/components/investor_dashboard_modals.php   (Details + Invest modals)
 *  - assets/css/paginas/investor_dashboard.css              (Styles)
 *  - assets/js/paginas/investor_dashboard.js                (Scripts)
 */

$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

// â”€â”€â”€ Access Control â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if ($_SESSION['user_type'] != 'investor') {
    header("Location: ../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// â”€â”€â”€ Filter Parameters â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$budget_min = isset($_GET['budget_min']) ? (int)$_GET['budget_min'] : 0;
$budget_max = isset($_GET['budget_max']) ? (int)$_GET['budget_max'] : PHP_INT_MAX;

// â”€â”€â”€ Unread Notifications â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$unread_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND type = 'investment' AND CAST(is_read AS INTEGER) = 0";
$unread_stmt = $db->prepare($unread_query);
$unread_stmt->execute([$user_id]);
$unread_count = $unread_stmt->fetch()['unread_count'];

// â”€â”€â”€ Projects Query â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$query = "SELECT p.*, u.full_name as owner_name, u.user_type as owner_type, u.is_verified as owner_verified, u.verification_status as owner_verification_status, u.profile_pic,
          m.full_name as mentor_name,
          (SELECT COUNT(*) FROM notifications WHERE reference_id = p.project_id AND user_id = ? AND type = 'investment' AND CAST(is_read AS INTEGER) = 0) as is_new,
          (SELECT COUNT(*) FROM notifications WHERE reference_id = p.project_id AND user_id = ? AND type = 'investment' AND CAST(is_read AS INTEGER) = 1) as is_read
          FROM projects p
          JOIN users u ON p.owner_id = u.user_id
          LEFT JOIN users m ON p.assigned_mentor_id = m.user_id
          WHERE p.ai_status = 'analyzed' AND p.is_public = true ";

$params = [$user_id, $user_id];

if (!empty($category_filter)) {
    $query .= " AND p.category = ?";
    $params[] = $category_filter;
}

if (!empty($search_term)) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $search_pattern = "%$search_term%";
    $params[] = $search_pattern;
    $params[] = $search_pattern;
}

if ($budget_min > 0 || $budget_max < PHP_INT_MAX) {
    $query .= " AND p.budget_needed BETWEEN ? AND ?";
    $params[] = $budget_min;
    $params[] = $budget_max;
}

$query .= " ORDER BY p.created_at DESC LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// â”€â”€â”€ Categories â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$cat_query = "SELECT DISTINCT category FROM projects WHERE is_public = true AND category IS NOT NULL AND category != ''";
$cat_stmt = $db->query($cat_query);
$categories = $cat_stmt->fetchAll();

// â”€â”€â”€ Financial Metrics â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$stats_stmt = $db->prepare("SELECT wallet_balance, total_invested FROM users WHERE user_id = ?");
$stats_stmt->execute([$user_id]);
$user_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$balance = $user_stats['wallet_balance'] ?? 0;
$invested = $user_stats['total_invested'] ?? 0;

// Active investments count
$active_investments_stmt = $db->prepare("SELECT COUNT(*) FROM project_investments WHERE investor_id = ? AND status = 'confirmed'");
$active_investments_stmt->execute([$user_id]);
$active_deals = $active_investments_stmt->fetchColumn();

// Recent transactions
$trans_stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$trans_stmt->execute([$user_id]);
$recent_transactions = $trans_stmt->fetchAll();
?>

<!-- Styles -->
<link rel="stylesheet" href="../../recursos/css/pages/investor_dashboard.css?v=<?php echo time(); ?>">

<!-- Content -->
<?php include '../../inclusoes/components/investor_dashboard_content.php'; ?>

<!-- Modals -->
<?php include '../../inclusoes/components/investor_dashboard_modals.php'; ?>

<!-- Scripts -->
<script src="../../recursos/js/pages/investor_dashboard.js?v=<?php echo time(); ?>"></script>

<?php require_once '../../inclusoes/rodape.php'; ?>


