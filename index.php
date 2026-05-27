<?php
/**
 * index.php - Dashboard Central da Plataforma (Elite Designer Version)
 */
$base_url = './';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'inclusoes/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: paginas/guest/landing.php");
    exit();
}

require_once 'configuracoes/base_dados.php';
require_once 'inclusoes/project_votes_schema.php';
$db = (new Database())->getConnection();
ensureProjectVotesTable($db);

require_once 'inclusoes/components/header/logic.php'; // Garante $user_data e $header_user_id

// --- VariÃ¡veis globais do utilizador para o Feed ---
$current_user_id  = $header_user_id ?? 0;
$is_verified      = $_SESSION['is_verified'] ?? false;
$lang             = $_SESSION['lang'] ?? 'pt';
$final_pic        = getUserAvatarUrl(
    $user_data['user_type'] ?? 'student',
    $user_data['mentorship_status'] ?? 'unsubmitted',
    $user_data['profile_pic'] ?? ''
);
$current_user_type = $_SESSION['user_type'] ?? 'student';

// Labels de tipo de utilizador
$user_type_labels = [
    'high_student'  => 'Estudante SecundÃ¡rio',
    'univ_student'  => 'Estudante UniversitÃ¡rio',
    'mentor'        => 'Mentor',
    'investor'      => 'Investidor',
    'entrepreneur'  => 'Empreendedor',
    'admin'         => 'Administrador',
];

// --- LÃ³gica de EstatÃ­sticas DinÃ¢micas ---
$stat_v1 = "00";
$stat_l1 = "PROJECTOS";
$stat_v2 = "00";
$stat_l2 = "MENTORES";
$stat_v3 = "00";
$stat_l3 = "COMUNIDADE";

try {
    // 1. Projectos Verificados no Ecossistema (Global)
    $stmt1 = $db->query("SELECT COUNT(*) FROM projects WHERE is_public = true AND approval_status = 'approved'");
    $stat_v1 = str_pad((string)$stmt1->fetchColumn(), 2, '0', STR_PAD_LEFT);
    $stat_l1 = "PROJECTOS";

    // 2. Mentores Verificados (Global)
    $stmt2 = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'mentor' AND (verification_status = 'verified' OR mentor_status = 'approved')");
    $stat_v2 = str_pad((string)$stmt2->fetchColumn(), 2, '0', STR_PAD_LEFT);
    $stat_l2 = "MENTORES";

    // 3. Estudantes Verificados na Plataforma (Global)
    $stmt3 = $db->query("SELECT COUNT(*) FROM users WHERE (user_type = 'student' OR user_type = 'univ_student' OR user_type = 'high_student') AND verification_status = 'verified'");
    $stat_v3 = number_format((int)$stmt3->fetchColumn());
    $stat_l3 = "ESTUDANTES";


}
catch (Exception $e) {
    $stat_v1 = "00";
    $stat_v2 = "01";
    $stat_v3 = "1";

}

// --- AnÃºncios  ---
$ticker_ads = [];
try {
    $ads_stmt = $db->query("SELECT * FROM ads WHERE is_active = true AND (start_date IS NULL OR start_date <= CURRENT_DATE) AND (end_date IS NULL OR end_date >= CURRENT_DATE) ORDER BY RANDOM() LIMIT 8");
    if ($ads_stmt)
        $ticker_ads = $ads_stmt->fetchAll();
}
catch (Throwable $e) {
}

if (empty($ticker_ads)) {
    $ticker_ads = [
        ['ad_id' => -1, 'title' => 'Parceria KALIYE', 'description' => 'Acelere o seu negÃ³cio com a maior rede de mentoria do paÃ­s.', 'image_url' => 'recursos/images/anuncios/ads1.png', 'type' => 'banner'],
        ['ad_id' => -2, 'title' => 'KALIYE Mentoria Elite', 'description' => 'Acesso direto aos mentores.', 'image_url' => 'recursos/images/anuncios/ads2.png', 'type' => 'premium']
    ];
}

$hour = (int)date('H');
$greeting_word = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
$first_name = explode(' ', $user_data['full_name'] ?? 'Membro')[0];

require_once 'inclusoes/cabecalho.php';
?>
<script>
    window.sessionUserType = '<?php echo $current_user_type; ?>';
    window.sessionUserId = '<?php echo $_SESSION['user_id']; ?>';
</script>
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<!-- CANVAS PRINCIPAL DO INDEX (DASHBOARD PÃ“S-LOGIN)                -->
<!-- Container mÃ¡ximo que envolve todos os blocos da pÃ¡gina          -->
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="platform-index-canvas" data-aos="fade">

    <!-- â”€â”€ HERO: SAUDAÃ‡ÃƒO + STATS + BOTÃƒO DE ACÃ‡ÃƒO RÃPIDA â”€â”€ -->
    <!-- Inclui o componente de boas-vindas com frases motivacionais -->
    <div style="padding-top: 1.5rem;">
        <?php include 'inclusoes/components/dashboard/dashboard_hero.php'; ?>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <!-- TICKER DE ANÃšNCIOS (TOPO) â€” Igual Ã  landing page      -->
    <!-- Exibe os anÃºncios reais da base de dados em carrossel  -->
    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="feed-ads-section" data-aos="fade-up" data-aos-delay="200">
        <!-- Label discreta a identificar que Ã© publicidade -->
        <span class="feed-ads-label">
            <i class="fas fa-ad" style="font-size: 0.55rem; opacity: 0.6;"></i>
            Publicidade
        </span>

        <!-- Container do ticker de anÃºncios com o mesmo componente da landing -->
        <!-- Passamos os $ticker_ads como $items e definimos um ID Ãºnico para este swiper -->
        <div class="container-secao ticker-anuncios" style="max-width: 100%; padding: 0;">
            <?php
            // Reutiliza os anÃºncios jÃ¡ carregados no topo do ficheiro ($ticker_ads)
            // O componente landing_ads_ticker.php espera a variÃ¡vel $items com os anÃºncios
            $items = $ticker_ads;
            $swiper_id = 'swiper-feed-top'; // ID Ãºnico para evitar conflito com outros swipers
            $is_reverse_style = false;       // Estilo normal (nÃ£o invertido)
            include 'inclusoes/components/landing_ads_ticker.php';
            ?>
        </div>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <!-- ANÃšNCIOS GLOBAIS (SISTEMA)                             -->
    <!-- NotificaÃ§Ãµes importantes do administrador da plataforma-->
    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <?php
    // Carrega os anÃºncios/comunicados globais activos da plataforma
    try {
        $ann_stmt = $db->query("SELECT * FROM announcements WHERE is_active = true ORDER BY created_at DESC");
        $announcements = $ann_stmt ? $ann_stmt->fetchAll() : [];
    } catch (Throwable $e) {
        $announcements = []; // Tabela pode nÃ£o existir ainda
    }
    foreach ($announcements as $an):
        // Define a cor de fundo com base no tipo de comunicado
        $bg = $an['type'] == 'alert' ? '#ef4444' : ($an['type'] == 'success' ? '#10b981' : '#3b82f6');
    ?>
        <!-- Card de anÃºncio global â€” cor depende do tipo -->
        <div style="background: <?php echo $bg; ?>; color: white; padding: 1rem; border-radius: 12px;
                    margin-bottom: 1rem; display: flex; align-items: center; gap: 1rem;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <i class="fas fa-bullhorn" style="font-size: 1.1rem; flex-shrink:0;"></i>
            <span style="font-weight: 500; font-size: 0.9rem;"><?php echo htmlspecialchars($an['message']); ?></span>
        </div>
    <?php endforeach; ?>



    <?php
    // PrÃ©-carrega as categorias e o papel do utilizador para os componentes das colunas laterais
    try {
        // Busca todas as categorias distintas de projectos para o filtro lateral esquerdo
        $cat_stmt = $db->query("SELECT DISTINCT category FROM projects WHERE is_public = true AND approval_status = 'approved' AND category IS NOT NULL AND category != '' ORDER BY category ASC");
        $categories = $cat_stmt ? $cat_stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        $stage_stmt = $db->query("SELECT DISTINCT project_stage FROM projects WHERE is_public = true AND approval_status = 'approved' AND project_stage IS NOT NULL AND project_stage != '' ORDER BY project_stage ASC");
        $project_stages = $stage_stmt ? $stage_stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    } catch (Exception $e) { $categories = []; $project_stages = []; }
    if (empty($project_stages)) {
        $project_stages = ['Ideia', 'MVP', 'Operacional', 'Escala'];
    }
    $default_project_stages = ['Ideia', 'MVP', 'Operacional', 'Escala'];
    $project_stages = array_values(array_unique(array_merge($default_project_stages, $project_stages)));
                    $user_role = $user_data['user_type'] ?? 'student'; // Perfil do utilizador para widgets

                    function buildFeedPageUrl(int $page): string {
                        $params = ['f_page' => max(1, $page)];
                        foreach (['category', 'budget', 'stage', 'sort'] as $key) {
                            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                                $params[$key] = $_GET[$key];
                            }
                        }
                        return 'index.php?' . http_build_query($params) . '#dynamic-feed-zone';
                    }
                    ?>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <!-- AREA PRINCIPAL: feed simplificado e focado               -->
    <!-- Widgets laterais removidos para reduzir ruido visual      -->
    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="platform-dashboard-grid">

        <!-- â”€â”€ FEED PRINCIPAL â”€â”€ -->
        <div id="feed-content">

            <section class="feed-command-center" data-aos="fade-up" data-aos-delay="260">
                <div class="feed-command-copy">
                    <span class="feed-kicker"><i class="fas fa-compass"></i> Marketplace de Projectos</span>
                    <h2>Descobrir Projectos</h2>
                    <p>Explore projectos verificados, pitches em video e oportunidades prontas para mentoria, financiamento e colaboracao.</p>
                </div>
                <?php
                    $quick_category = $_GET['category'] ?? '';
                    $quick_budget   = $_GET['budget']   ?? '';
                    $quick_stage    = $_GET['stage']    ?? '';
                    $quick_sort     = $_GET['sort']     ?? 'trending';
                    $quick_trending_active = $quick_category === '' && $quick_budget === '' && $quick_stage === '' && $quick_sort === 'trending';
                    $quick_recent_active = $quick_category === '' && $quick_budget === '' && $quick_stage === '' && $quick_sort === 'recent';
                    $quick_top_active = $quick_category === '' && $quick_budget === '' && $quick_stage === '' && $quick_sort === 'top';
                    $quick_mvp_active = $quick_category === '' && $quick_budget === '' && $quick_stage === 'MVP';
                    $quick_high_funding_active = $quick_category === '' && $quick_budget === '10000000+' && $quick_stage === '';
                ?>
                <div class="feed-quick-tabs" aria-label="Atalhos do feed">
                    <a href="index.php?f_page=1&amp;sort=trending#dynamic-feed-zone" data-feed-quick="trending" onclick="return window.setFeedQuickFilter('trending', event)" class="<?php echo $quick_trending_active ? 'active' : ''; ?>"><i class="fas fa-fire"></i> Em alta</a>
                    <a href="index.php?f_page=1&amp;sort=recent#dynamic-feed-zone" data-feed-quick="recent" onclick="return window.setFeedQuickFilter('recent', event)" class="<?php echo $quick_recent_active ? 'active' : ''; ?>"><i class="fas fa-clock"></i> Recentes</a>
                    <a href="index.php?f_page=1&amp;sort=top#dynamic-feed-zone" data-feed-quick="top" onclick="return window.setFeedQuickFilter('top', event)" class="<?php echo $quick_top_active ? 'active' : ''; ?>"><i class="fas fa-star"></i> Mais votadas</a>
                    <a href="index.php?f_page=1&amp;stage=MVP&amp;sort=trending#dynamic-feed-zone" data-feed-quick="mvp" onclick="return window.setFeedQuickFilter('mvp', event)" class="<?php echo $quick_mvp_active ? 'active' : ''; ?>">MVP</a>
                    <a href="index.php?f_page=1&amp;budget=10000000%2B&amp;sort=trending#dynamic-feed-zone" data-feed-quick="high-funding" onclick="return window.setFeedQuickFilter('high-funding', event)" class="<?php echo $quick_high_funding_active ? 'active' : ''; ?>">Alta Capta&ccedil;&atilde;o</a>
                </div>
                <form id="filterFormFeed" style="display:none;" aria-hidden="true">
                    <input type="hidden" name="f_page" value="1">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($quick_category, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="budget" value="<?php echo htmlspecialchars($quick_budget, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="stage" value="<?php echo htmlspecialchars($quick_stage, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($quick_sort, ENT_QUOTES, 'UTF-8'); ?>">
                </form>
                <?php if (false): // Filtros detalhados do feed temporariamente desativados: manter o codigo para reativacao futura. ?>
                <?php
                $sel_category = $_GET['category'] ?? '';
                $sel_budget   = $_GET['budget']   ?? '';
                $sel_stage    = $_GET['stage']    ?? '';
                ?>
                <form id="filterFormFeed" class="feed-inline-filters" onsubmit="event.preventDefault(); window.applyFeedFilters(1);">
                    <input type="hidden" name="f_page" value="1">

                    <div class="filter-group">
                        <label><i class="fas fa-tag"></i> Categoria</label>
                        <select name="category">
                            <option value="">Todas as Categorias</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $sel_category === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-money-bill-wave"></i> Investimento</label>
                        <select name="budget">
                            <option value="">Qualquer Or&ccedil;amento</option>
                            <option value="0-500000"          <?= $sel_budget==='0-500000'          ? 'selected' : '' ?>>At&eacute; 500.000 Kz</option>
                            <option value="500000-2000000"    <?= $sel_budget==='500000-2000000'    ? 'selected' : '' ?>>500k - 2M Kz</option>
                            <option value="2000000-10000000"  <?= $sel_budget==='2000000-10000000'  ? 'selected' : '' ?>>2M - 10M Kz</option>
                            <option value="10000000+"         <?= $sel_budget==='10000000+'         ? 'selected' : '' ?>>Acima de 10M Kz</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-chart-line"></i> Est&aacute;gio</label>
                        <select name="stage">
                            <option value="">Qualquer Est&aacute;gio</option>
                            <?php
                            $stage_labels = [
                                'Ideia' => 'Ideia / Conceito',
                                'MVP' => 'MVP / Prot&oacute;tipo',
                                'Operacional' => 'Operacional / Tracionando',
                                'Escala' => 'Pronto para Escala',
                            ];
                            foreach ($project_stages as $stage):
                                $stage_label = $stage_labels[$stage] ?? htmlspecialchars($stage, ENT_QUOTES, 'UTF-8');
                            ?>
                                <option value="<?php echo htmlspecialchars($stage, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $sel_stage === $stage ? 'selected' : ''; ?>>
                                    <?php echo $stage_label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                <?php endif; ?>
            </section>

            <!-- â”€â”€ CONTAINER DINÃ‚MICO DO FEED (AJAX READY) â”€â”€ -->
            <div id="dynamic-feed-zone" data-aos="fade-up" data-aos-delay="400">
                <!-- â”€â”€ CONTAINER DOS CARDS DO FEED â”€â”€ -->
                <div id="posts-container">
                    <?php
                    // â”€â”€ LÃ“GICA DE PAGINAÃ‡ÃƒO DO FEED â”€â”€
                    $feed_page = isset($_GET['f_page']) ? (int)$_GET['f_page'] : 1;
                    if ($feed_page < 1) $feed_page = 1;
                    $feed_sort = $_GET['sort'] ?? 'trending';
                    if (!in_array($feed_sort, ['trending', 'recent', 'top'], true)) {
                        $feed_sort = 'trending';
                    }

                    $posts_per_page = 6;
                    $offset = ($feed_page - 1) * $posts_per_page;

                    // Filtros dinÃ¢micos (para a contagem total tambÃ©m)
                    $where_clause = "WHERE p.is_public = true AND p.approval_status = 'approved'";
                    $params = [];

                    if (!empty($_GET['category'])) {
                        $where_clause .= " AND LOWER(TRIM(COALESCE(p.category, ''))) = :category";
                        $params[':category'] = strtolower(trim((string)$_GET['category']));
                    }
                    if (!empty($_GET['stage'])) {
                        $stage_filter = strtolower(trim((string)$_GET['stage']));
                        if ($stage_filter === 'mvp') {
                            $where_clause .= " AND (LOWER(p.project_stage) LIKE :stage_mvp OR LOWER(p.project_stage) LIKE :stage_proto)";
                            $params[':stage_mvp'] = '%mvp%';
                            $params[':stage_proto'] = '%prot%';
                        } else {
                            $where_clause .= " AND LOWER(TRIM(COALESCE(p.project_stage, ''))) = :stage";
                            $params[':stage'] = $stage_filter;
                        }
                    }
                    if (!empty($_GET['budget'])) {
                        $budget_filter = str_replace(' ', '+', $_GET['budget']);
                        $parts = explode('-', $budget_filter);
                        if (count($parts) == 2) {
                            $where_clause .= " AND p.budget_needed BETWEEN :b1 AND :b2";
                            $params[':b1'] = $parts[0];
                            $params[':b2'] = $parts[1];
                        } elseif (strpos($budget_filter, '+') !== false) {
                            $where_clause .= " AND p.budget_needed >= :b1";
                            $params[':b1'] = (int)$budget_filter;
                        }
                    }

                    // â”€â”€ CONTAGEM TOTAL â”€â”€
                    $total_query = "SELECT COUNT(*) FROM projects p JOIN users u ON p.owner_id = u.user_id $where_clause";
                    $t_stmt = $db->prepare($total_query);
                    $t_stmt->execute($params);
                    $total_posts = $t_stmt->fetchColumn();
                    $total_pages = ceil($total_posts / $posts_per_page);

                    $order_sql = "p.is_public DESC, vote_count DESC, (u.verification_status = 'verified') DESC, p.created_at DESC";
                    if ($feed_sort === 'recent') {
                        $order_sql = "p.is_public DESC, p.created_at DESC";
                    } elseif ($feed_sort === 'top') {
                        $order_sql = "p.is_public DESC, vote_count DESC, p.created_at DESC";
                    }

                    // â”€â”€ PROJECTOS EM ALTA â”€â”€
                    $hot_stmt = $db->prepare("SELECT p.project_id, p.title, p.category,
                                                     (SELECT COUNT(*) FROM project_votes WHERE project_id = p.project_id) as vote_count
                                              FROM projects p
                                              JOIN users u ON p.owner_id = u.user_id
                                              $where_clause
                                                AND (SELECT COUNT(*) FROM project_votes WHERE project_id = p.project_id) > 0
                                              ORDER BY vote_count DESC, p.created_at DESC
                                              LIMIT 3");
                    foreach($params as $key => $val) {
                        $hot_stmt->bindValue($key, $val);
                    }
                    $hot_stmt->execute();
                    $hot_projects = $hot_stmt->fetchAll(PDO::FETCH_ASSOC);

                    // â”€â”€ QUERY PRINCIPAL â”€â”€
                    $query = "SELECT p.*, u.user_id, u.full_name, u.user_type AS author_role,
                                     u.profile_pic, (u.verification_status = 'verified') AS is_verified, u.verification_status, u.mentorship_status,
                                     (SELECT COUNT(*) FROM project_votes WHERE project_id = p.project_id) as vote_count,
                                     (SELECT COUNT(*) FROM project_votes WHERE project_id = p.project_id AND voter_id = :uid_vote) as user_voted,
                                     (SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id) as like_count,
                                     (SELECT COUNT(*) FROM project_comments WHERE project_id = p.project_id) as comment_count,
                                     (SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id AND user_id = :uid2) as user_liked
                              FROM projects p
                              JOIN users u ON p.owner_id = u.user_id
                              $where_clause
                              ORDER BY $order_sql
                              LIMIT :limit OFFSET :offset";

                    $stmt = $db->prepare($query);
                    foreach($params as $key => $val) {
                        $stmt->bindValue($key, $val);
                    }
                    $stmt->bindValue(':uid2', $current_user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':uid_vote', $current_user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt->execute();
                    $feed_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($feed_posts) == 0) {
                        echo '<div class="feed-empty-state">
                                <i class="fas fa-search"></i>
                                <h3>Nenhum projecto encontrado</h3>
                                <p>Ajuste os filtros ou publique um novo projecto para movimentar o ecossistema.</p>
                                <button type="button" onclick="window.clearFeedFilters()">Limpar filtros</button>
                              </div>';
                    }

                    ?>
                    <?php if (!empty($hot_projects) && count($feed_posts) > 0): ?>
                    <section class="feed-hot-strip" aria-label="Projectos em destaque">
                        <div class="feed-hot-strip__title">
                            <i class="fas fa-bolt"></i>
                            <span>Projectos em destaque</span>
                        </div>
                        <div class="feed-hot-strip__items">
                            <?php foreach ($hot_projects as $idx => $hot): ?>
                                <button type="button" onclick="openProjectDetails(<?php echo (int)$hot['project_id']; ?>, 1)" class="feed-hot-item">
                                    <strong>#<?php echo $idx + 1; ?></strong>
                                    <span><?php echo htmlspecialchars($hot['title'] ?: 'Ideia sem titulo'); ?></span>
                                    <em><i class="fas fa-star"></i> <?php echo (int)$hot['vote_count']; ?></em>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if (count($feed_posts) > 0): ?>
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
                    <?php 
                    foreach ($feed_posts as $post) {
                        include 'inclusoes/components/post_card.php';
                    }
                    ?>
                    </div>
                </div><!-- End #posts-container -->

                <!-- â”€â”€ PAGINAÃ‡ÃƒO AJAX â”€â”€ -->
                <?php if ($total_pages > 1): ?>
                    <div class="feed-pagination">
                        <?php
                        $start_p = max(1, $feed_page - 2);
                        $end_p = min($total_pages, $start_p + 4);
                        if ($end_p - $start_p < 4) $start_p = max(1, $end_p - 4);
                        ?>

                        <?php if ($feed_page > 1): ?>
                            <a href="<?php echo htmlspecialchars(buildFeedPageUrl($feed_page - 1), ENT_QUOTES, 'UTF-8'); ?>" data-feed-page="<?php echo $feed_page - 1; ?>" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>

                        <?php for ($i = $start_p; $i <= $end_p; $i++): ?>
                            <a href="<?php echo htmlspecialchars(buildFeedPageUrl($i), ENT_QUOTES, 'UTF-8'); ?>" data-feed-page="<?php echo $i; ?>"
                               class="pagination-btn <?php echo $i === $feed_page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($feed_page < $total_pages): ?>
                            <a href="<?php echo htmlspecialchars(buildFeedPageUrl($feed_page + 1), ENT_QUOTES, 'UTF-8'); ?>" data-feed-page="<?php echo $feed_page + 1; ?>" class="pagination-btn">
                                Pr&oacute;ximo <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div><!-- End #dynamic-feed-zone -->

        </div><!-- End #feed-content -->

    </div><!-- End .platform-dashboard-grid -->



    <?php include 'inclusoes/components/community_feedback_section.php'; ?>

    </div><!-- End .platform-index-canvas (div principal da pagina index pos-login) -->

    <!-- Scripts de GestÃ£o de Projectos (Modais Elite) 
         carregados via index_scripts.php em rodape.php -->
</main>
</div>


    <!-- Styles moved to top -->

    <!-- Ad Detail Modal carregado via componentes (Removido, jÃ¡ estÃ¡ no rodape.php) -->

<script>
    // Rastreamento de mÃƒÂ©tricas de anÃƒÂºncios
    function trackAdView(adId) {
        fetch('interface_programacao/ads/track_ad_metric.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ad_id=${adId}&metric_type=view`
        }).catch(err => console.error('Erro ao rastrear view:', err));
        return true; // Permite continuar a execuÃƒÂ§ÃƒÂ£o
    }

    function trackAdClick(adId) {
        fetch('interface_programacao/ads/track_ad_metric.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ad_id=${adId}&metric_type=click`
        }).catch(err => console.error('Erro ao rastrear click:', err));
    }

    function openAdModal(ad) {
        document.getElementById('adModalImage').style.backgroundImage = ad.image_url ? `url('${ad.image_url}')` : 'linear-gradient(135deg, #10b981, #064e3b)';
        document.getElementById('adModalTitle').innerText = ad.title;
        document.getElementById('adModalType').innerText = ad.type ? ad.type.toUpperCase() : 'OPORTUNIDADE';
        document.getElementById('adModalDesc').innerText = ad.description;
        
        const btn = document.getElementById('adModalLink');
        if (ad.link_url) {
            btn.href = ad.link_url;
            btn.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-external-link-alt"></i> Ver Mais / Aplicar';
            btn.onclick = () => trackAdClick(ad.ad_id);
        } else if (ad.contact_info) {
             btn.href = "https://wa.me/" + ad.contact_info.replace(/[^0-9]/g, '');
             btn.style.display = 'block';
             btn.innerHTML = '<i class="fab fa-whatsapp"></i> Contactar via WhatsApp';
             btn.onclick = () => trackAdClick(ad.ad_id);
        } else {
            btn.style.display = 'none';
        }
        
        document.getElementById('adModal').style.display = 'flex';
    }

    // Auto-Scroll Stories Logic (Robust Version with Sub-pixel Accumulator)
    (function() {
        function initAutoScroll() {
            const storiesContainer = document.querySelector('.stories-container');
            if (!storiesContainer) return;

            let isHovered = false;
            let scrollSpeed = 0.5; // Visible gentle speed
            let currentScroll = storiesContainer.scrollLeft; // Initialize with current position

            // Interaction handlers to pause scrolling
            storiesContainer.addEventListener('mouseenter', () => isHovered = true);
            storiesContainer.addEventListener('mouseleave', () => isHovered = false);
            storiesContainer.addEventListener('touchstart', () => isHovered = true, {passive: true});
            storiesContainer.addEventListener('touchend', () => isHovered = false);

            function scrollLoop() {
                if (document.visibilityState !== 'visible') {
                    requestAnimationFrame(scrollLoop);
                    return;
                }

                if (!isHovered) {
                    currentScroll += scrollSpeed;

                    // Reset if we reach the end
                    if (currentScroll >= (storiesContainer.scrollWidth - storiesContainer.clientWidth)) {
                        currentScroll = 0;
                    }
                    
                    storiesContainer.scrollLeft = currentScroll;
                } else {
                    // Update tracker if user scrolled manually
                    currentScroll = storiesContainer.scrollLeft;
                }
                requestAnimationFrame(scrollLoop);
            }

            // Start the loop
            requestAnimationFrame(scrollLoop);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAutoScroll);
        } else {
            initAutoScroll();
        }
    })();
    </script> <!-- End main script to allow HTML injection -->

    <!-- Likes Modal -->
    <div id="likesModal" class="login-card glass" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 300px; max-height: 400px; overflow-y: auto; z-index: 3000;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">
            <h4 style="margin: 0;">Curtidas</h4>
            <button onclick="closeLikesModal()" style="background: none; border: none; color: white; cursor: pointer;">&times;</button>
        </div>
        <div id="likesList">
            <!-- Users will be loaded here -->
        </div>
    </div>
    <div id="likesOverlay" onclick="closeLikesModal()" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 2999;"></div>




<script>
    // Auto-Translation Logic for User Content
    document.addEventListener('DOMContentLoaded', function() {
        const currentLang = '<?php echo $lang ?? "pt"; ?>';
        
        if (currentLang === 'en') {
            translateDynamicContent();
        }
    });

    function translateDynamicContent() {
        // Translate Titles
        const titles = document.querySelectorAll('.translate-title');
        titles.forEach(el => {
            if (el.dataset.translated) return;
            translateText(el.innerText).then(text => {
                if(text) {
                    el.innerText = text;
                    el.dataset.translated = "true";
                }
            });
        });

        // Translate Descriptions
        const descs = document.querySelectorAll('.translate-desc');
        descs.forEach(el => {
            if (el.dataset.translated) return;
            translateText(el.innerText).then(text => {
                if(text) {
                    el.innerText = text;
                    el.dataset.translated = "true";
                }
            });
        });
    }

    async function translateText(text) {
        if (!text || text.trim() === '') return null;
        
        // Chunking logic to handle large texts (API limit usually ~500 chars)
        const CHUNK_SIZE = 450;
        const chunks = [];
        let currentChunk = '';
        
        // Split by sentences to match context better
        const sentences = text.match(/[^.!?]+[.!?]+|[^.!?]+$/g) || [text];
        
        sentences.forEach(sentence => {
            if ((currentChunk + sentence).length > CHUNK_SIZE) {
                if (currentChunk) chunks.push(currentChunk);
                currentChunk = sentence;
                
                // If a single sentence is huge, force split it
                while (currentChunk.length > CHUNK_SIZE) {
                    chunks.push(currentChunk.slice(0, CHUNK_SIZE));
                    currentChunk = currentChunk.slice(CHUNK_SIZE);
                }
            } else {
                currentChunk += sentence;
            }
        });
        if (currentChunk) chunks.push(currentChunk);

        // Translate chunks sequentially
        try {
            const translatedChunks = await Promise.all(chunks.map(async (chunk) => {
                // Using MyMemory API
                const url = `https://api.mymemory.translated.net/get?q=${encodeURIComponent(chunk)}&langpair=pt|en`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (data && data.responseData && data.responseData.translatedText) {
                    const result = data.responseData.translatedText;
                    if (result.includes("QUERY LENGTH LIMIT") || result.includes("MYMEMORY")) {
                        return chunk; // Return original if error
                    }
                    return result;
                }
                return chunk;
            }));
            
            return translatedChunks.join(' ');
        } catch (e) {
            console.error('Translation failed:', e);
            return null; // Fallback to original text
        }
    }
</script>

<!-- Modais removidos daqui porque sÃ£o carregados universalmente via rodape.php -->

<?php require_once 'inclusoes/rodape.php'; ?>
