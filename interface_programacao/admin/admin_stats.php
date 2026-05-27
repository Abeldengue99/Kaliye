<?php
// interface_programacao/admin/admin_stats.php
// Defino o cabeçalho para retornar JSON
header('Content-Type: application/json');
// Inicio a sessão para verificar login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Incluo a conexão com o banco de dados
require_once __DIR__ . '/../../configuracoes/base_dados.php';
// Incluo a verificação de autenticação
require_once __DIR__ . '/../../inclusoes/auth_check.php';

// Verifico se o usuário é administrador
if (!isAdmin() || !hasPermission('dashboard')) {
    // Retorno erro 403 se não for admin
    http_response_code(403);
    // Retorno mensagem de erro em JSON
    echo json_encode(['error' => 'Não autorizado']);
    // Encerro o script
    exit;
}

// Instancio a classe de banco de dados
$database = new Database();
// Obtenho a conexão PDO
/** @var PDO $db */
$db = $database->getConnection();

// Inicializo o array de resposta com valores padrão
$response = [
    'total_users' => 0,
    'total_projects' => 0,
    'total_mentorships' => 0,
    'total_comments' => 0,
    'total_ads' => 0,
    'total_ad_views' => 0,
    'total_ad_clicks' => 0,
    'user_growth' => [],
];

try {
    // 1. Totais Gerais
    // Conto o total de usuários registrados
    $response['total_users'] = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    // Conto o total de projetos pendentes de aprovação
    $response['total_projects'] = (int)$db->query("SELECT COUNT(*) FROM projects WHERE approval_status = 'pending'")->fetchColumn();
    
    // Conta solicitações de verificação pendentes (KYC pendente)
    try {
        $response['total_mentorships'] = (int)$db->query(
            "SELECT COUNT(*) FROM users WHERE verification_status = 'pending'"
        )->fetchColumn();
    } catch (Exception $e) {
        $response['total_mentorships'] = 0;
    }
    
    // Verifico e conto comentários (social_comments ou comments)
    try {
        // Tento contar na tabela social_comments
        $response['total_comments'] = (int)$db->query("SELECT COUNT(*) FROM social_comments")->fetchColumn();
    } catch (Exception $e) {
        // Se falhar, tento na tabela comments antiga
        try {
            $response['total_comments'] = (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
        } catch (Exception $e2) {
            // Se ambas falharem, defino como 0
            $response['total_comments'] = 0;
        }
    }

    // 1.1 Totais de Publicidade
    try {
        $ad_stats = $db->query("SELECT COUNT(DISTINCT a.ad_id) AS total, COUNT(m.*) FILTER (WHERE m.metric_type = 'view') AS views, COUNT(m.*) FILTER (WHERE m.metric_type = 'click') AS clicks FROM ads a LEFT JOIN ad_metrics m ON m.ad_id = a.ad_id")->fetch();
        $response['total_ads'] = (int)$ad_stats['total'];
        $response['total_ad_views'] = (int)$ad_stats['views'];
        $response['total_ad_clicks'] = (int)$ad_stats['clicks'];
    } catch (Exception $e) {
        $response['total_ads'] = 0;
        $response['total_ad_views'] = 0;
        $response['total_ad_clicks'] = 0;
    }

    // 2. Crescimento de Usuários (Últimos 6 meses)
    // Preparo a query para agrupar usuários por mês de criação
    $stmt = $db->query("
        SELECT TO_CHAR(created_at, 'YYYY-MM') as month, COUNT(*) as count 
        FROM users 
        WHERE created_at >= NOW() - INTERVAL '6 months'
        GROUP BY TO_CHAR(created_at, 'YYYY-MM') 
        ORDER BY month ASC
    ");
    // Busco os resultados e salvo no array de resposta
    $response['user_growth'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Categorias Populares (Top 5)
    // Agrupo projetos por categoria e conto
    $stmt = $db->query("
        SELECT category, COUNT(*) as count 
        FROM projects 
        GROUP BY category 
        ORDER BY count DESC 
        LIMIT 5
    ");
    // Salvo as categorias no array de resposta
    $response['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Top Criadores de Projetos
    // Busco usuários com mais projetos publicados
    $stmt = $db->query("
        SELECT u.user_id, u.full_name, u.profile_pic, COUNT(p.project_id) as project_count
        FROM users u
        JOIN projects p ON u.user_id = p.owner_id
        GROUP BY u.user_id
        ORDER BY project_count DESC
        LIMIT 5
    ");
    // Salvo os top criadores
    $response['top_posters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Top Comentaristas
    // Defino o nome da tabela de comentários padrão
    $comment_table = 'social_comments';
    try {
        // Testo se a tabela existe
        $db->query("SELECT 1 FROM social_comments LIMIT 1");
    } catch (Exception $e) {
        // Se não existir, uso a tabela antiga
        $comment_table = 'comments';
    }
    
    try {
        // Busco usuários com mais comentários feitos
        $stmt = $db->query("
            SELECT u.user_id, u.full_name, u.profile_pic, COUNT(c.comment_id) as comment_count
            FROM users u
            JOIN $comment_table c ON u.user_id = c.user_id
            GROUP BY u.user_id
            ORDER BY comment_count DESC
            LIMIT 5
        ");
        // Salvo os top comentaristas
        $response['top_commenters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Se der erro na query, retorno array vazio
        $response['top_commenters'] = [];
    }

    // 6. Aniversariantes do Mês
    // Tento buscar aniversariantes próximos se a coluna birth_date existir
    try {
        // Busco usuários com aniversário nos próximos 30 dias
        $stmt = $db->query("
            SELECT user_id, full_name, profile_pic, 
                   TO_CHAR(birth_date, 'DD/MM') as formatted_date,
                   birth_date
            FROM users
            WHERE birth_date IS NOT NULL
              AND TO_CHAR(birth_date, 'MM-DD') BETWEEN TO_CHAR(NOW(), 'MM-DD') AND TO_CHAR(NOW() + INTERVAL '30 days', 'MM-DD')
            ORDER BY TO_CHAR(birth_date, 'MM-DD') ASC
            LIMIT 5
        ");
        // Salvo os aniversariantes
        $response['upcoming_birthdays'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Se a coluna não existir, retorno lista vazia
        $response['upcoming_birthdays'] = [];
    }

} catch (Exception $e) {
    // Em caso de erro geral, registro no log do servidor
    error_log("Erro no Admin Stats: " . $e->getMessage());
    // O script continuará e retornará o que conseguiu processar (ou valores padrão)
}

// Retorno a resposta completa em formato JSON
echo json_encode($response);
// Encerro a execução do script
exit;

