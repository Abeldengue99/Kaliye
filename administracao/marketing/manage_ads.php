<?php
// admin/marketing/manage_ads.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

if (!hasPermission('ads')) {
    header("Location: ../index.php"); 
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$query = "SELECT * FROM ads ORDER BY created_at DESC";
$ads = $db->query($query)->fetchAll();

// Build JS array of ad IDs for real-time polling
$ad_ids_js = json_encode(array_column($ads, 'ad_id'));
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicidade - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../../recursos/images/marca/apple-touch-icon-k.png">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#f7941d">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script>
        let adIdToDelete = null;

        function confirmDeleteAd(id) {
            adIdToDelete = id;
            const modal = document.getElementById('deleteConfirmModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeDeleteModal() {
            adIdToDelete = null;
            const modal = document.getElementById('deleteConfirmModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        function executeDelete() {
            if (adIdToDelete) {
                window.location.href = `../../interface_programacao/system/delete_ad.php?id=${adIdToDelete}`;
            }
        }

        // Real-time stats polling — uma única chamada para todos os anúncios
        async function refreshAdStats() {
            try {
                const res = await fetch('../../interface_programacao/admin/get_ads_stats.php');
                if (!res.ok) return;
                const data = await res.json();
                if (!data.success) return;

                // Atualizar cada anúncio na tabela
                Object.entries(data.ads).forEach(([id, stats]) => {
                    const vEl = document.getElementById(`views-${id}`);
                    const cEl = document.getElementById(`clicks-${id}`);
                    if (vEl) vEl.textContent = stats.views.toLocaleString('pt-PT');
                    if (cEl) cEl.textContent = stats.clicks.toLocaleString('pt-PT');
                });

                // Piscar o indicador live para mostrar atualização
                const dot = document.getElementById('live-dot');
                if (dot) {
                    dot.style.background = '#60a5fa';
                    setTimeout(() => { dot.style.background = '#10b981'; }, 500);
                }
            } catch (e) {
                console.warn('Polling stats falhou:', e);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('click', function(e) {
                const deleteModal = document.getElementById('deleteConfirmModal');
                if (e.target === deleteModal) closeDeleteModal();
            });

            // Primeira carga imediata
            refreshAdStats();
            // Poll a cada 15 segundos
            setInterval(refreshAdStats, 15000);
        });
    </script>
</head>
<body>

    <!-- Navbar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <?php
        if (isset($_GET['success'])) {
            $messages = [
                'ad_created' => 'Anúncio criado com sucesso!',
                'ad_updated' => 'Anúncio atualizado com sucesso!',
                'ad_deleted' => 'Anúncio eliminado com sucesso!'
            ];
            $msg = $messages[$_GET['success']] ?? 'Operação realizada com sucesso!';
            echo '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; padding: 1rem; border-radius: 14px; margin-bottom: 2rem; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-check-circle"></i> ' . htmlspecialchars($msg) . '
                  </div>';
        }
        if (isset($_GET['error'])) {
            echo '<div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #ef4444; padding: 1rem; border-radius: 14px; margin-bottom: 2rem; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-exclamation-circle"></i> Ocorreu um erro na operação.
                  </div>';
        }
        ?>

        <header class="dashboard-header">
            <div class="header-title">
                <a href="../index.php" style="color: var(--aksanti-orange); text-decoration: none; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-arrow-left"></i> Voltar ao Painel
                </a>
                <h1>Gestão de Publicidade</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Campanhas e banners do ecossistema.</p>
            </div>
            <a href="form_ad.php" class="btn-admin btn-admin-primary">
                <i class="fas fa-plus-circle"></i> NOVO ANÚNCIO
            </a>
        </header>

        <div class="admin-card-premium">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Anúncio</th>
                            <th>Tipo</th>
                            <th>Período</th>
                            <th>
                                Desempenho
                                <span id="live-dot" style="display: inline-block; width: 6px; height: 6px; background: #10b981; border-radius: 50%; margin-left: 6px; animation: pulse-live 2s infinite;" title="Atualizado em tempo real"></span>
                            </th>
                            <th>Estado</th>
                            <th style="text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ads as $ad): 
                            $ctr = $ad['views'] > 0 ? round(($ad['clicks'] / $ad['views']) * 100, 2) : 0;
                            $is_active = $ad['is_active'] ?? 1;
                            $today = date('Y-m-d');
                            $is_expired = $ad['end_date'] && $today > $ad['end_date'];
                        ?>
                        <tr style="<?php echo !$is_active || $is_expired ? 'opacity: 0.5;' : ''; ?>">
                            <!-- Anúncio (imagem + nome) -->
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if($ad['image_url']): ?>
                                        <img src="../../<?php echo htmlspecialchars($ad['image_url']); ?>" 
                                             style="width: 52px; height: 52px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(247,148,29,0.2); box-shadow: 0 4px 10px rgba(0,0,0,0.3);" 
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div style="display:none; width: 52px; height: 52px; background: rgba(247,148,29,0.06); border-radius: 12px; align-items: center; justify-content: center; color: rgba(247,148,29,0.4); border: 1px dashed rgba(247,148,29,0.2); flex-shrink:0;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php else: ?>
                                        <div style="width: 52px; height: 52px; background: rgba(255,255,255,0.04); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.15); border: 1px dashed rgba(255,255,255,0.1); flex-shrink:0;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($ad['title']); ?></div>
                                        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.4);"><?php echo htmlspecialchars($ad['client_name'] ?? 'Cliente Direto'); ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Tipo -->
                            <td>
                                <span class="user-badge-premium" style="color: #60a5fa; border-color: rgba(96, 165, 250, 0.2);">
                                    <?php echo htmlspecialchars($ad['type']); ?>
                                </span>
                            </td>

                            <!-- Período -->
                            <td>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">
                                    <i class="fas fa-calendar-day" style="width: 14px;"></i> <?php echo date('d/m/Y', strtotime($ad['start_date'])); ?><br>
                                    <i class="fas fa-calendar-check" style="width: 14px;"></i> <?php echo $ad['end_date'] ? date('d/m/Y', strtotime($ad['end_date'])) : 'Indeterminado'; ?>
                                </div>
                            </td>

                            <!-- Desempenho — com IDs para atualização em tempo real -->
                            <td>
                                <div style="font-size: 0.82rem;">
                                    <div style="color: #60a5fa; display: flex; align-items: center; gap: 0.4rem;">
                                        <i class="fas fa-eye"></i>
                                        <strong id="views-<?php echo $ad['ad_id']; ?>"><?php echo number_format($ad['views'] ?? 0); ?></strong>
                                        <span style="color: rgba(255,255,255,0.25); font-size: 0.7rem;">views</span>
                                    </div>
                                    <div style="color: #f7941d; display: flex; align-items: center; gap: 0.4rem; margin-top: 0.25rem;">
                                        <i class="fas fa-hand-pointer"></i>
                                        <strong id="clicks-<?php echo $ad['ad_id']; ?>"><?php echo number_format($ad['clicks'] ?? 0); ?></strong>
                                        <span style="color: rgba(255,255,255,0.25); font-size: 0.7rem;">cliques</span>
                                        <?php if ($ctr > 0): ?>
                                            <span style="color: #10b981; font-size: 0.68rem; margin-left: 4px;">(<?php echo $ctr; ?>% CTR)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Estado -->
                            <td>
                                <?php if($is_expired): ?>
                                    <span class="user-badge-premium" style="color: #f43f5e; border-color: rgba(244, 63, 94, 0.2);">EXPIRADO</span>
                                <?php elseif($is_active): ?>
                                    <span class="user-badge-premium" style="color: #34d399; border-color: rgba(52, 211, 153, 0.2);">ATIVO</span>
                                <?php else: ?>
                                    <span class="user-badge-premium" style="color: #94a3b8; border-color: rgba(148, 163, 184, 0.2);">PAUSADO</span>
                                <?php endif; ?>
                            </td>

                            <!-- Ações -->
                            <td>
                                <div style="display: flex; gap: 0.45rem; justify-content: flex-end; align-items: center; flex-wrap: nowrap;">
                                    <a href="ad_analytics.php?ad_id=<?php echo $ad['ad_id']; ?>" 
                                       style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 0.8rem; background:rgba(96,165,250,0.08); border:1px solid rgba(96,165,250,0.2); color:#60a5fa; border-radius:8px; font-size:0.75rem; font-weight:700; text-decoration:none; transition:all 0.2s; white-space:nowrap;"
                                       onmouseover="this.style.background='rgba(96,165,250,0.18)';" onmouseout="this.style.background='rgba(96,165,250,0.08)';">
                                        <i class="fas fa-chart-line"></i> Analytics
                                    </a>
                                    <a href="form_ad.php?id=<?php echo $ad['ad_id']; ?>" 
                                       style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 0.8rem; background:rgba(247,148,29,0.08); border:1px solid rgba(247,148,29,0.2); color:#f7941d; border-radius:8px; font-size:0.75rem; font-weight:700; text-decoration:none; transition:all 0.2s; white-space:nowrap;"
                                       onmouseover="this.style.background='rgba(247,148,29,0.18)';" onmouseout="this.style.background='rgba(247,148,29,0.08)';">
                                        <i class="fas fa-pen-nib"></i> Editar
                                    </a>
                                    <button onclick="confirmDeleteAd(<?php echo $ad['ad_id']; ?>)" 
                                            style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 0.8rem; background:rgba(244,63,94,0.08); border:1px solid rgba(244,63,94,0.2); color:#f43f5e; border-radius:8px; font-size:0.75rem; font-weight:700; cursor:pointer; transition:all 0.2s; white-space:nowrap;"
                                            onmouseover="this.style.background='rgba(244,63,94,0.18)';" onmouseout="this.style.background='rgba(244,63,94,0.08)';">
                                        <i class="fas fa-trash-can"></i> Apagar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ads)): ?>
                        <tr>
                            <td colspan="6" style="padding: 4rem; text-align: center; color: rgba(255,255,255,0.2);">
                                <i class="fas fa-rectangle-ad" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                                <p>Nenhuma campanha publicitária encontrada.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="admin-modal-overlay">
        <div class="admin-modal-content" style="max-width: 450px; text-align: center; border-color: rgba(244, 63, 94, 0.2);">
            <div style="padding: 3rem 2rem;">
                <div style="width: 80px; height: 80px; background: rgba(244, 63, 94, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #f43f5e; font-size: 2rem; border: 1px solid rgba(244, 63, 94, 0.2);">
                    <i class="fas fa-trash-can"></i>
                </div>
                <h3 style="font-size: 1.5rem; color: #fff; margin-bottom: 0.75rem; font-weight: 800; letter-spacing: -0.5px;">Apagar Anúncio?</h3>
                <p style="color: rgba(255,255,255,0.5); font-size: 0.95rem; line-height: 1.6; margin-bottom: 2.5rem;">
                    Esta ação é irreversível. O anúncio será removido permanentemente da plataforma e todas as suas estatísticas serão perdidas.
                </p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button onclick="closeDeleteModal()" class="btn-admin" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #fff;">CANCELAR</button>
                    <button onclick="executeDelete()" class="btn-admin" style="background: #f43f5e; color: #fff; border: none; box-shadow: 0 10px 20px rgba(244, 63, 94, 0.2);">APAGAR AGORA</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes pulse-live {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.8); }
        }
    </style>

</body>
</html>

