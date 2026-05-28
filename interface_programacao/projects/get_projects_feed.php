<?php
/**
 * get_projects_feed.php - Endpoint AJAX dedicado para o Feed de Projectos.
 * Retorna apenas o HTML parcial da zona dinamica do feed.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo '<p style="color:red;">Não autorizado.</p>';
    exit();
}

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/project_votes_schema.php';

$db = (new Database())->getConnection();
ensureProjectVotesTable($db);

$base_url = '../../';
$current_user_id   = (int)$_SESSION['user_id'];
$current_user_type = $_SESSION['user_type'] ?? 'student';

$feed_page      = isset($_GET['f_page']) ? max(1, (int)$_GET['f_page']) : 1;
$feed_sort      = $_GET['sort'] ?? 'trending';
$project_id     = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_GET['project_modal']) ? (int)$_GET['project_modal'] : (isset($_GET['comment_project_id']) ? (int)$_GET['comment_project_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0)));
if (!in_array($feed_sort, ['trending', 'recent', 'top'], true)) {
    $feed_sort = 'trending';
}
$posts_per_page = 6;
$offset         = ($feed_page - 1) * $posts_per_page;

$where_parts = [];
$params      = [];

$where_parts[] = "p.is_public = true AND p.approval_status = 'approved'";

if ($project_id > 0) {
    $where_parts[] = "p.project_id = :project_id";
    $params[':project_id'] = $project_id;
}

if (!empty($_GET['category'])) {
    $where_parts[] = "LOWER(TRIM(COALESCE(p.category, ''))) = :category";
    $params[':category'] = strtolower(trim((string)$_GET['category']));
}

if (!empty($_GET['stage'])) {
    $stage_filter = strtolower(trim((string)$_GET['stage']));
    if ($stage_filter === 'mvp') {
        $where_parts[] = "(LOWER(p.project_stage) LIKE :stage_mvp OR LOWER(p.project_stage) LIKE :stage_proto)";
        $params[':stage_mvp'] = '%mvp%';
        $params[':stage_proto'] = '%prot%';
    } else {
        $where_parts[] = "LOWER(TRIM(COALESCE(p.project_stage, ''))) = :stage";
        $params[':stage'] = $stage_filter;
    }
}

if (!empty($_GET['budget'])) {
    $budget_value = str_replace(' ', '+', $_GET['budget']);

    if (strpos($budget_value, '+') !== false) {
        $where_parts[] = "p.budget_needed >= :b1";
        $params[':b1'] = (int)rtrim($budget_value, '+');
    } elseif (strpos($budget_value, '-') !== false) {
        $parts = explode('-', $budget_value, 2);
        $where_parts[] = "p.budget_needed BETWEEN :b1 AND :b2";
        $params[':b1'] = (int)$parts[0];
        $params[':b2'] = (int)$parts[1];
    }
}

$where_sql = $where_parts ? ('WHERE ' . implode(' AND ', $where_parts)) : '';
$order_sql = "p.is_public DESC, vote_count DESC, (u.verification_status = 'verified') DESC, p.created_at DESC";
if ($feed_sort === 'recent') {
    $order_sql = "p.is_public DESC, p.created_at DESC";
} elseif ($feed_sort === 'top') {
    $order_sql = "p.is_public DESC, vote_count DESC, p.created_at DESC";
}

$count_sql  = "SELECT COUNT(*) FROM projects p JOIN users u ON p.owner_id = u.user_id $where_sql";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_posts = (int)$count_stmt->fetchColumn();
$total_pages = $total_posts > 0 ? (int)ceil($total_posts / $posts_per_page) : 1;

$main_sql = "SELECT p.*, u.user_id as author_id, u.full_name, u.user_type AS author_role,
                    u.profile_pic, (u.verification_status = 'verified') AS is_verified, u.verification_status, u.mentorship_status,
                    (SELECT COUNT(*) FROM project_votes WHERE project_id = p.project_id) AS vote_count,
                    (SELECT COUNT(*) FROM project_votes WHERE project_id = p.project_id AND voter_id = :uid_vote) AS user_voted,
                    (SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id) AS like_count,
                    (SELECT COUNT(*) FROM project_comments WHERE project_id = p.project_id) AS comment_count,
                    (SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id AND user_id = :uid2) AS user_liked
             FROM projects p
             JOIN users u ON p.owner_id = u.user_id
             $where_sql
             ORDER BY $order_sql
             LIMIT :lim OFFSET :off";

$stmt = $db->prepare($main_sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':uid2', $current_user_id, PDO::PARAM_INT);
$stmt->bindValue(':uid_vote', $current_user_id, PDO::PARAM_INT);
$stmt->bindValue(':lim',  $posts_per_page,  PDO::PARAM_INT);
$stmt->bindValue(':off',  $offset,          PDO::PARAM_INT);
$stmt->execute();

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hot_sql = "SELECT p.project_id, p.title, p.category,
                   (SELECT COUNT(*) FROM project_votes WHERE project_id = p.project_id) AS vote_count
            FROM projects p
            JOIN users u ON p.owner_id = u.user_id
            $where_sql
              AND (SELECT COUNT(*) FROM project_votes WHERE project_id = p.project_id) > 0
            ORDER BY vote_count DESC, p.created_at DESC
            LIMIT 3";
$hot_stmt = $db->prepare($hot_sql);
foreach ($params as $key => $val) {
    $hot_stmt->bindValue($key, $val);
}
$hot_stmt->execute();
$hot_projects = $hot_stmt->fetchAll(PDO::FETCH_ASSOC);

function buildFeedAjaxPageUrl(int $page): string {
    $params = ['f_page' => max(1, $page)];
    foreach (['category', 'budget', 'stage', 'sort'] as $key) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            $params[$key] = $_GET[$key];
        }
    }
    return 'index.php?' . http_build_query($params) . '#dynamic-feed-zone';
}
?>
<?php if (!empty($hot_projects) && !empty($posts)): ?>
<section class="feed-hot-strip" aria-label="Projectos em destaque">
    <div class="feed-hot-strip__title">
        <i class="fas fa-bolt"></i>
        <span>Projectos em destaque</span>
    </div>
    <div class="feed-hot-strip__items">
        <?php foreach ($hot_projects as $idx => $hot): ?>
            <button type="button" onclick="openProjectDetails(<?php echo (int)$hot['project_id']; ?>, 1)" class="feed-hot-item">
                <strong>#<?php echo $idx + 1; ?></strong>
                <span><?php echo htmlspecialchars($hot['title'] ?: 'Projecto sem titulo'); ?></span>
                <em><i class="fas fa-star"></i> <?php echo (int)$hot['vote_count']; ?></em>
            </button>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($posts)): ?>
<div class="feed-results-header">
    <div>
        <span class="feed-ads-label" style="margin: 0;">
            <i class="fas fa-lightbulb" style="font-size: 0.55rem; opacity: 0.6;"></i>
            Oportunidades
        </span>
        <strong><?php echo (int)$total_posts; ?> projecto<?php echo (int)$total_posts === 1 ? '' : 's'; ?> encontrado<?php echo (int)$total_posts === 1 ? '' : 's'; ?></strong>
    </div>
    <span>P&aacute;gina <?php echo (int)$feed_page; ?> de <?php echo max(1, (int)$total_pages); ?></span>
</div>
<?php endif; ?>

<div class="projects-grid">
<?php if (empty($posts)): ?>
    <div class="feed-empty-state" style="grid-column: 1 / -1;">
        <i class="fas fa-search"></i>
        <h3>Nenhum projecto encontrado</h3>
        <p>Ajuste os filtros ou publique um novo projecto para movimentar o ecossistema.</p>
        <button type="button" onclick="window.clearFeedFilters()">Limpar filtros</button>
    </div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?php include '../../inclusoes/components/post_card.php'; ?>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<?php if ($total_pages > 1): ?>
<div class="feed-pagination" style="margin-top: 1.5rem;">
    <?php if ($feed_page > 1): ?>
        <a href="<?php echo htmlspecialchars(buildFeedAjaxPageUrl($feed_page - 1), ENT_QUOTES, 'UTF-8'); ?>" data-feed-page="<?php echo $feed_page - 1; ?>" class="pagination-btn">
            <i class="fas fa-chevron-left"></i> Anterior
        </a>
    <?php endif; ?>

    <?php
    $start_p = max(1, $feed_page - 2);
    $end_p   = min($total_pages, $start_p + 4);
    if ($end_p - $start_p < 4) $start_p = max(1, $end_p - 4);
    for ($i = $start_p; $i <= $end_p; $i++):
    ?>
        <a href="<?php echo htmlspecialchars(buildFeedAjaxPageUrl($i), ENT_QUOTES, 'UTF-8'); ?>" data-feed-page="<?php echo $i; ?>"
           class="pagination-btn <?php echo $i === $feed_page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($feed_page < $total_pages): ?>
        <a href="<?php echo htmlspecialchars(buildFeedAjaxPageUrl($feed_page + 1), ENT_QUOTES, 'UTF-8'); ?>" data-feed-page="<?php echo $feed_page + 1; ?>" class="pagination-btn">
            Pr&oacute;ximo <i class="fas fa-chevron-right"></i>
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>
