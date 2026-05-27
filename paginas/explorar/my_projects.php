<?php
/**
 * paginas/explorar/my_projects.php
 * 
 * Dashboard do Criador: Esta página é o centro de controlo pessoal de cada inovador.
 * Aqui, o utilizador gere o ciclo de vida dos seus projectos, visualiza métricas de impacto
 * (visualizações, likes, comentários) e monitoriza o status de aprovação administrativa.
 */

// Importamos a conexão central para interagir com o PostgreSQL.
require_once '../../configuracoes/base_dados.php';

// Iniciamos a sessão para identificar o 'Criador' que está a aceder à sua área restrita.
session_start();

// Segurança: Se não houver login, barramos o acesso e redirecionamos para o login.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

$role_stmt = $db->prepare("SELECT user_type, mentorship_status FROM users WHERE user_id = ?");
$role_stmt->execute([$user_id]);
$current_user_role = $role_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$can_publish_ideas = (($current_user_role['user_type'] ?? $_SESSION['user_type'] ?? '') !== 'mentor')
    && (($current_user_role['mentorship_status'] ?? $_SESSION['mentorship_status'] ?? '') !== 'approved');

// Definimos o base_url para que o cabeçalho e rodapé encontrem os recursos estáticos (CSS/JS).
$base_url = "../../";

/**
 * BUSCA DE PROJETOS DO UTILIZADOR (SQL ANALÍTICO)
 * Nota Técnica: Usamos subqueries COALESCE para obter contadores de interação de forma performante.
 * Buscamos especificamente investimentos APENAS se estiverem 'approved' para refletir a realidade financeira.
 */
try {
    $query = "SELECT p.*, 
              (SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id) as like_count,
              (SELECT COUNT(*) FROM project_comments WHERE project_id = p.project_id) as comment_count,
              (SELECT COUNT(*) FROM project_views WHERE project_id = p.project_id) as view_count,
              (SELECT COUNT(*) FROM project_investments WHERE project_id = p.project_id AND status = 'approved') as investment_count
              FROM projects p 
              WHERE p.owner_id = ? 
              ORDER BY p.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $projects = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback amigável: se a query falhar, mostramos uma lista vazia e logamos o erro para o TI.
    $projects = [];
    error_log("Erro crítico ao buscar projectos do utilizador $user_id: " . $e->getMessage());
}

// Incluímos o cabeçalho global (Elite UI).
include '../../inclusoes/cabecalho.php';
?>

<!-- Content Canvas: Design Elite Premium -->
<div class="elite-dashboard-container" style="padding-top: 2rem; min-height: 100vh; background: #030712;">
    <div class="elite-max-width" style="max-width: 1400px; margin: 0 auto; padding: 0 2rem;">
        
        <!-- Header de Seção: Título e Botão de Ação Rápida -->
        <div data-aos="fade-down" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4rem; flex-wrap: wrap; gap: 20px;">
            <div>
                <div class="elite-label-micro" style="letter-spacing: 3px; color: var(--elite-orange); font-weight: 800; margin-bottom: 15px;">DASHBOARD DO CRIADOR</div>
                <h1 style="font-size: 3rem; font-weight: 950; letter-spacing: -2px; color: #fff; margin: 0; line-height: 1;">Meus Projectos</h1>
            </div>
            
            <!-- Botão de Gatilho para o Modal de Submissão AJAX (index_scripts.php) -->
            <?php if ($can_publish_ideas): ?>
                <button onclick="window.openPostModal()" style="background: var(--elite-orange); color: white; border: none; padding: 0.8rem 1.8rem; border-radius: 14px; font-weight: 800; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: 0.3s; box-shadow: 0 10px 20px rgba(247, 148, 29, 0.2); width: fit-content;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-plus"></i> NOVO PROJECTO
                </button>
            <?php endif; ?>
        </div>

        <!-- Barra de Filtros de Estado (Visual Only por agora, expansível para filtros dinâmicos) -->
        <div style="display: flex; gap: 1rem; margin-bottom: 3rem; overflow-x: auto; padding-bottom: 10px;">
            <a href="#" class="tab-btn active">Todos (<?php echo count($projects); ?>)</a>
            <a href="#" class="tab-btn">Aprovados</a>
            <a href="#" class="tab-btn">Em Análise</a>
            <a href="#" class="tab-btn">Drafts</a>
        </div>

        <!-- Grid de Cards de Projecto: Listagem Automática -->
        <div class="elite-projects-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.2rem;">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $proj): 
                    /**
                     * LÓGICA DE ETIQUETAS DE STATUS (Badges)
                     * Business Case: O utilizador precisa de transparência máxima sobre o estado do seu projecto.
                     * 'Aguardando Validação' é o estado padrão para qualquer novo projecto ou edição recente.
                     */
                    $status_class = '';
                    $status_label = '';
                    switch ($proj['approval_status']) {
                        case 'approved': $status_class = 'tag-green'; $status_label = 'APROVADO'; break;
                        case 'pending': $status_class = 'tag-orange'; $status_label = 'Aguardando Validação'; break;
                        case 'rejected': $status_class = 'tag-red'; $status_label = 'REJEITADO'; break;
                    }
                ?>
                    <div class="elite-post-card" style="margin-bottom: 0; padding: 1.2rem; border-radius: 20px;">
                        <div class="post-header-elite" style="gap: 0.8rem; margin-bottom: 0.8rem;">
                            <div style="display: flex; gap: 0.7rem; align-items: center; overflow: hidden;">
                                <div class="project-icon-box" style="flex-shrink: 0; width: 40px; height: 40px; background: var(--surface-3); border: 1.5px solid var(--elite-card-border); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: var(--elite-orange);">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <div style="overflow: hidden;">
                                    <h3 style="margin: 0; font-size: 0.95rem; font-weight: 850; color: #fff; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($proj['title']); ?></h3>
                                    <p style="margin: 1px 0 0; font-size: 0.6rem; color: var(--elite-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <?php echo date('d/m/Y', strtotime($proj['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <!-- Etiqueta de Status: Corresponde às regras de aprovação administrativa. -->
                            <span class="elite-tag <?php echo $status_class; ?>" style="padding: 3px 6px; font-size: 0.55rem; flex-shrink: 0;"><?php echo $status_label; ?></span>
                        </div>

                        <!-- Resumo do impacto: Usamos htmlspecialchars para segurança e clamp visual no CSS. -->
                        <p style="font-size: 0.8rem; color: var(--surface-70); line-height: 1.4; margin: 0.8rem 0;" class="description-clamp">
                            <?php echo htmlspecialchars($proj['description']); ?>
                        </p>

                        <!-- Rodapé do Card: KPIs de Interação e Botões de Manutenção. -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid var(--elite-card-border);">
                            <div style="display: flex; gap: 1rem;">
                                <div title="Visualizações" class="stat-item">
                                    <i class="fas fa-eye" style="font-size: 0.7rem;"></i> <?php echo $proj['view_count']; ?>
                                </div>
                                <div title="Interessados" class="stat-item">
                                    <i class="fas fa-heart" style="font-size: 0.7rem;"></i> <?php echo $proj['like_count']; ?>
                                </div>
                                <div title="Feedback" class="stat-item">
                                    <i class="fas fa-comment" style="font-size: 0.7rem;"></i> <?php echo $proj['comment_count']; ?>
                                </div>
                            </div>

                            <!-- Ações de Gestão: Editar ou eliminar o projecto. -->
                            <div style="display: flex; gap: 8px;">
                                <button onclick="window.editProject(<?php echo $proj['project_id']; ?>)" class="action-btn-mini" title="Editar este projecto">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="window.deleteProject(<?php echo $proj['project_id']; ?>)" class="action-btn-mini delete-btn" title="Remover projecto">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Estado Vazio Amigável: Incentiva o utilizador à proatividade. -->
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>Ainda não tens projectos publicados</h3>
                    <p>O ecossistema KALIYE está à espera da tua próxima grande inovação.</p>
                    <?php if ($can_publish_ideas): ?>
                        <button onclick="window.openPostModal()" class="btn-invest-elite">SUBMETER AGORA</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Estilos Locais de Suporte (Design Elite) */
.tab-btn { padding: 0.7rem 1.8rem; border-radius: 14px; color: var(--elite-text-muted); font-size: 0.85rem; font-weight: 800; text-decoration: none; transition: 0.3s; }
.tab-btn:hover { color: var(--text-primary); background: var(--surface-5); }
.tab-btn.active { background: var(--elite-orange); color: #ffffff; box-shadow: 0 10px 20px rgba(247, 148, 29, 0.2); }
.tag-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
.action-btn-mini { width: 32px; height: 32px; border-radius: 12px; background: var(--surface-3); border: 1px solid var(--surface-5); color: var(--elite-text-muted); cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; }
.action-btn-mini:hover { background: var(--surface-8); color: var(--text-primary); border-color: var(--surface-20); transform: translateY(-2px); }
.action-btn-mini.delete-btn:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2); }
.description-clamp { display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 2.3rem; }
.stat-item { display: flex; align-items: center; gap: 4px; font-size: 0.65rem; color: var(--elite-text-muted); font-weight: 750; }
.empty-state { grid-column: span 3; padding: 10rem 2rem; text-align: center; background: rgba(255,255,255,0.01); border: 1px dashed var(--surface-5); border-radius: 32px; }
.empty-state i { font-size: 4rem; color: var(--surface-3); margin-bottom: 2rem; display: block; }
</style>

<?php 
// Rodapé global: Carrega os scripts de edição/eliminação que são partilhados entre páginas.
include '../../inclusoes/rodape.php'; 
?>
