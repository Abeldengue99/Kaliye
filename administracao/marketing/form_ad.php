<?php
// administracao/form_ad.php - Página dedicada para Criar/Editar Anúncios
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('ads')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$ad = null;
$ad_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($ad_id) {
    $stmt = $db->prepare("SELECT * FROM ads WHERE ad_id = ?");
    $stmt->execute([$ad_id]);
    $ad = $stmt->fetch();
    
    if (!$ad) {
        header("Location: manage_ads.php");
        exit();
    }
}

$page_title = $ad ? "Editar Publicidade" : "Nova Publicidade";
$action_url = $ad ? "../../interface_programacao/system/update_ad.php" : "../../interface_programacao/system/save_ad.php";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - KALIYE Admin</title>
    
    <!-- Favicon Oficial KALIYE — completo e local -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../../recursos/images/marca/apple-touch-icon-k.png">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#f7941d">

    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <a href="manage_ads.php" style="color: var(--aksanti-orange); text-decoration: none; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-arrow-left"></i> Voltar à Gestão
                </a>
                <h1><?php echo $page_title; ?></h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;"><?php echo $ad ? "Atualize os detalhes da campanha '".htmlspecialchars($ad['title'])."'" : "Configure uma nova campanha publicitária para a rede."; ?></p>
            </div>
        </header>

        <div class="admin-card-premium" style="max-width: 900px; margin: 0 auto;">
            <form action="<?php echo $action_url; ?>" method="POST" enctype="multipart/form-data" style="padding: 1rem;">
                <input type="hidden" name="ad_id" value="<?php echo $ad['ad_id'] ?? ''; ?>">
                
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2.5rem;">
                    
                    <!-- Coluna Principal -->
                    <div>
                        <!-- Conteúdo do Anúncio -->
                        <div class="input-group-premium" style="margin-bottom: 1.5rem;">
                            <label>Título da Campanha</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($ad['title'] ?? ''); ?>" required placeholder="Ex: Promoção de Março - 30% OFF">
                        </div>

                        <div class="input-group-premium" style="margin-bottom: 1.5rem;">
                            <label>Descrição do Anúncio</label>
                            <textarea name="description" rows="4" required placeholder="Escreva o texto que será exibido no banner..."><?php echo htmlspecialchars($ad['description'] ?? ''); ?></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                            <div class="input-group-premium">
                                <label>Tipo / Categoria</label>
                                <select name="type">
                                    <option value="banner" <?php echo ($ad['type'] ?? '') == 'banner' ? 'selected' : ''; ?>>Banner Geral</option>
                                    <option value="premium" <?php echo ($ad['type'] ?? '') == 'premium' ? 'selected' : ''; ?>>Destaque Premium</option>
                                    <option value="event" <?php echo ($ad['type'] ?? '') == 'event' ? 'selected' : ''; ?>>Evento Profissional</option>
                                    <option value="mentorship" <?php echo ($ad['type'] ?? '') == 'mentorship' ? 'selected' : ''; ?>>Mentoria Especial</option>
                                    <option value="investment" <?php echo ($ad['type'] ?? '') == 'investment' ? 'selected' : ''; ?>>Investimento</option>
                                </select>
                            </div>
                            <div class="input-group-premium">
                                <label>Link de Destino</label>
                                <input type="text" name="link_url" value="<?php echo htmlspecialchars($ad['link_url'] ?? ''); ?>" placeholder="https://exemplo.ao/pagina">
                            </div>
                        </div>

                        <div class="input-group-premium" style="margin-bottom: 1.5rem;">
                            <label>Banner ou Criativo (Imagem)</label>
                            <div style="display: flex; gap: 1.5rem; align-items: center;">
                                <?php if($ad && $ad['image_url']): ?>
                                    <img src="../<?php echo htmlspecialchars($ad['image_url']); ?>" style="width: 100px; height: 100px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                                <?php endif; ?>
                                <input type="file" name="image" accept="image/*" style="padding: 0.6rem; border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px; background: rgba(255,255,255,0.02); flex: 1;">
                            </div>
                            <small style="color: rgba(255,255,255,0.3); display: block; margin-top: 0.5rem;">Recomendado: 1200x400px para banners hero, 800x800px para destaque.</small>
                        </div>

                        <!-- Notas Internas -->
                        <div class="input-group-premium">
                            <label>Observações Administrativas (Privado)</label>
                            <textarea name="notes" rows="3" placeholder="Notas sobre faturação, contatos extras, etc..."><?php echo htmlspecialchars($ad['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Coluna Lateral (Configurações) -->
                    <div>
                        <!-- Informações do Cliente -->
                        <div style="background: rgba(59, 130, 246, 0.05); padding: 1.5rem; border-radius: 20px; margin-bottom: 1.5rem; border: 1px solid rgba(59, 130, 246, 0.1);">
                            <h4 style="margin: 0 0 1rem 0; color: #60a5fa; font-size: 0.85rem; font-weight: 800; text-transform: uppercase;"><i class="fas fa-user-tie"></i> Cliente</h4>
                            <div class="input-group-premium" style="margin-bottom: 1rem;">
                                <label>Nome da Empresa/Pessoa</label>
                                <input type="text" name="client_name" value="<?php echo htmlspecialchars($ad['client_name'] ?? ''); ?>" placeholder="Ex: Unitel Money">
                            </div>
                            <div class="input-group-premium" style="margin-bottom: 1rem;">
                                <label>Email para Contato</label>
                                <input type="email" name="client_email" value="<?php echo htmlspecialchars($ad['client_email'] ?? ''); ?>" placeholder="ads@empresa.ao">
                            </div>
                            <div class="input-group-premium">
                                <label>Telefone</label>
                                <input type="text" name="client_phone" value="<?php echo htmlspecialchars($ad['client_phone'] ?? ''); ?>" placeholder="+244 9xx xxx xxx">
                            </div>
                        </div>

                        <!-- Vigência e Orçamento -->
                        <div style="background: rgba(247, 148, 29, 0.05); padding: 1.5rem; border-radius: 20px; border: 1px solid rgba(247, 148, 29, 0.1);">
                            <h4 style="margin: 0 0 1rem 0; color: var(--aksanti-orange); font-size: 0.85rem; font-weight: 800; text-transform: uppercase;"><i class="fas fa-calendar-check"></i> Vigência</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="input-group-premium">
                                    <label>Data Início</label>
                                    <input type="date" name="start_date" value="<?php echo $ad['start_date'] ?? date('Y-m-d'); ?>">
                                </div>
                                <div class="input-group-premium">
                                    <label>Data Fim</label>
                                    <input type="date" name="end_date" value="<?php echo $ad['end_date'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="input-group-premium" style="margin-bottom: 1.5rem;">
                                <label>Status Financeiro</label>
                                <select name="payment_status">
                                    <option value="pending" <?php echo ($ad['payment_status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pendente / Aguardando</option>
                                    <option value="paid" <?php echo ($ad['payment_status'] ?? '') == 'paid' ? 'selected' : ''; ?>>Pago / Confirmado</option>
                                    <option value="cancelled" <?php echo ($ad['payment_status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelado / Rejeitado</option>
                                </select>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; background: rgba(52, 211, 153, 0.05); padding: 1rem; border-radius: 12px; border: 1px solid rgba(52, 211, 153, 0.1);">
                                <input type="checkbox" name="is_active" id="ad_is_active" value="1" <?php echo ($ad['is_active'] ?? 1) == 1 ? 'checked' : ''; ?> style="width: 20px; height: 20px; accent-color: #10b981; cursor: pointer;">
                                <label for="ad_is_active" style="margin: 0; cursor: pointer; color: #fff; font-size: 0.85rem; font-weight: 700;">Campanha Ativa</label>
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div style="margin-top: 2rem; display: flex; flex-direction: column; gap: 1rem;">
                            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 1.25rem; font-size: 1rem; justify-content: center;">
                                <i class="fas fa-save"></i> <?php echo $ad ? "ATUALIZAR CAMPANHA" : "PUBLICAR ANÚNCIO"; ?>
                            </button>
                            <a href="manage_ads.php" class="btn-admin" style="justify-content: center; background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); color: rgba(255,255,255,0.6);">
                                Descartar Alterações
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

</body>
</html>



