<?php
/**
 * administracao/project_analytics.php - Relatório Profundo de Projectos (KALIYE Admin)
 */
session_start();
$admin_base = './';
$base_url = '../';
require_once '../configuracoes/base_dados.php';
require_once '../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('moderation')) {
    header("Location: ../autenticacao/entrar.php");
    exit();
}

$db = (new Database())->getConnection();

// 1. Projectos Investidos (Performance Actual)
$invested_q = "SELECT p.*, COUNT(i.investment_id) as inv_count, SUM(i.amount) as total_raised, u.full_name as owner_name 
               FROM projects p 
               JOIN project_investments i ON p.project_id = i.project_id 
               JOIN users u ON p.owner_id = u.user_id
               GROUP BY p.project_id, u.full_name 
               ORDER BY total_raised DESC";
$invested_projects = $db->query($invested_q)->fetchAll(PDO::FETCH_ASSOC);

// 2. Projectos Virgens (Nunca Investidos)
$virgin_q = "SELECT p.*, u.full_name as owner_name 
             FROM projects p 
             JOIN users u ON p.owner_id = u.user_id
             LEFT JOIN project_investments i ON p.project_id = i.project_id 
             WHERE i.investment_id IS NULL AND p.approval_status = 'approved'
             ORDER BY p.created_at DESC";
$virgin_projects = $db->query($virgin_q)->fetchAll(PDO::FETCH_ASSOC);

// 3. Projectos Estagnados (Sem investimento há +30 dias ou criados há +30 dias sem nada)
$stale_q = "SELECT p.*, u.full_name as owner_name 
            FROM projects p 
            JOIN users u ON p.owner_id = u.user_id
            WHERE p.approval_status = 'approved' 
            AND (
                -- Nunca teve investimento e tem mais de 30 dias
                (NOT EXISTS (SELECT 1 FROM project_investments i2 WHERE i2.project_id = p.project_id) AND p.created_at < NOW() - INTERVAL '30 days')
                OR 
                -- Teve investimento mas o último foi há mais de 30 dias
                (EXISTS (SELECT 1 FROM project_investments i3 WHERE i3.project_id = p.project_id) 
                 AND (SELECT MAX(created_at) FROM project_investments i4 WHERE i4.project_id = p.project_id) < NOW() - INTERVAL '30 days')
            )
            ORDER BY p.created_at ASC";
$stale_projects = $db->query($stale_q)->fetchAll(PDO::FETCH_ASSOC);

// 4. Ranking de Potencial (Engajamento sem Investimento)
$potential_q = "SELECT p.*, u.full_name as owner_name,
                ((SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id) * 2 + 
                 (SELECT COUNT(*) FROM project_views WHERE project_id = p.project_id)) as engagement_score
                FROM projects p 
                JOIN users u ON p.owner_id = u.user_id
                LEFT JOIN project_investments i ON p.project_id = i.project_id 
                WHERE i.investment_id IS NULL AND p.approval_status = 'approved'
                ORDER BY engagement_score DESC LIMIT 10";
$potential_projects = $db->query($potential_q)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inteligência de Projectos - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">

    <link rel="stylesheet" href="../recursos/css/style.css">
    <link rel="stylesheet" href="../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body class="admin-dashboard-layout">
    
    <?php include 'barra_lateral.php'; ?>

    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Relatório Profundo de Ecossistema</h1>
                <p style="color: rgba(255,255,255,0.5);">Visão analítica de performance, lacunas e oportunidades.</p>
            </div>
        </header>

        <!-- Tabs de Navegação Analítica -->
        <div style="display: flex; gap: 1rem; margin-top: 2rem; margin-bottom: 2rem;">
            <button onclick="switchTab('invested')" id="tab-invested" class="tab-btn-aksanti active">Investidos (<?php echo count($invested_projects); ?>)</button>
            <button onclick="switchTab('virgin')" id="tab-virgin" class="tab-btn-aksanti">Sem Investimento (<?php echo count($virgin_projects); ?>)</button>
            <button onclick="switchTab('stale')" id="tab-stale" class="tab-btn-aksanti" style="border-color: rgba(239, 68, 68, 0.2); color: #ef4444;">Estagnados (<?php echo count($stale_projects); ?>)</button>
            <button onclick="switchTab('potential')" id="tab-potential" class="tab-btn-aksanti">Potenciais Destaques</button>
        </div>

        <!-- Seção: Investidos -->
        <div id="sec-invested" class="analytics-section">
            <div class="table-container-aksanti">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Projecto</th>
                            <th>Mentor / Mentor</th>
                            <th>Total Arrecadado</th>
                            <th>Investidores</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($invested_projects as $p): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 800; color: #fff;"><?php echo htmlspecialchars($p['title']); ?></div>
                                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.3);">ID: #<?php echo $p['project_id']; ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($p['owner_name']); ?></td>
                            <td style="color: #10b981; font-weight: 900;"><?php echo number_format($p['total_raised'], 2); ?> AKZ</td>

                            <td><span class="badge-inv"><?php echo $p['inv_count']; ?></span></td>
                            <td><span class="tag-status tag-published">ACTIVO</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Seção: Sem Investimento -->
        <div id="sec-virgin" class="analytics-section" style="display:none;">
            <div class="table-container-aksanti">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Projecto Disponível</th>
                            <th>Fundador</th>
                            <th>Data de Publicação</th>
                            <th>Expectativa Orçamental</th>
                            <th>Acção Recomendada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($virgin_projects as $p): ?>
                        <tr>
                            <td><strong style="color: #fff;"><?php echo htmlspecialchars($p['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['owner_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($p['created_at'])); ?></td>
                            <td><?php echo number_format($p['funding_goal'] ?? 0, 2); ?> AKZ</td>

                            <td><button onclick="toggleBoost(<?= $p['project_id'] ?>, this)" 
                                        class="btn-micro-aksanti <?= $p['is_featured'] ? 'active-boost' : '' ?>">
                                    <i class="fas fa-rocket"></i> <?= $p['is_featured'] ? 'Impulsionado' : 'Impulsionar' ?>
                                </button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Seção: Estagnados -->
        <div id="sec-stale" class="analytics-section" style="display:none;">
             <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.1); padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                <i class="fas fa-exclamation-triangle" style="color: #ef4444; font-size: 1.5rem;"></i>
                <div>
                    <h4 style="color: #ef4444; margin: 0; font-weight: 800;">Alerta de Inactividade</h4>
                    <p style="color: rgba(255,255,255,0.5); font-size: 0.85rem; margin: 0;">Projectos sem investimento há mais de 30 dias precisam de revisão na estratégia de Mentoria ou Marketing.</p>
                </div>
            </div>
            <div class="table-container-aksanti">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Projecto Crítico</th>
                            <th>Fundador</th>
                            <th>Tempo Total S/ Inv.</th>
                            <th>Engajamento</th>
                            <th>Prioridade</th>
                             <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stale_projects as $p): 
                            $date1 = new DateTime($p['created_at']);
                            $date2 = new DateTime();
                            $diff = $date1->diff($date2)->days;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['title']); ?></td>
                            <td><?php echo htmlspecialchars($p['owner_name']); ?></td>
                            <td style="color: #ef4444; font-weight: 800;"><?php echo $diff; ?> Dias</td>
                            <td>Média</td>
                            <td><span class="tag-status tag-red">ALTA</span></td>
                             <td>
                                <button onclick="toggleBoost(<?= $p['project_id'] ?>, this)" 
                                        class="btn-micro-aksanti <?= $p['is_featured'] ? 'active-boost' : '' ?>">
                                    <i class="fas fa-rocket"></i> <?= $p['is_featured'] ? 'Impulsionado' : 'Impulsionar' ?>
                                </button>
                             </td>

                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Seção: Potenciais -->
        <div id="sec-potential" class="analytics-section" style="display:none;">
             <div class="table-container-aksanti">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Ranking de Interesse</th>
                            <th>Engagement Score</th>
                            <th>Acção Sugerida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($potential_projects as $idx => $p): ?>
                        <tr>
                            <td><strong><?php echo ($idx+1); ?>.</strong> <?php echo htmlspecialchars($p['title']); ?></td>
                            <td style="color: var(--accent-orange); font-weight: 900;"><?php echo $p['engagement_score']; ?> pts</td>
                            <td><button class="btn-micro-aksanti" style="background: var(--accent-orange); color: white;">Converter p/ Investimento</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <style>
        .tab-btn-aksanti { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); color: rgba(255,255,255,0.4); padding: 12px 24px; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 0.8rem; text-transform: uppercase; }
        .tab-btn-aksanti:hover { background: rgba(255,255,255,0.04); color: #fff; }
        .tab-btn-aksanti.active { background: var(--accent-orange); color: white; border-color: var(--accent-orange); box-shadow: 0 5px 15px rgba(247, 148, 29, 0.2); }
        
        .table-container-aksanti { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; overflow: hidden; }
        .aksanti-table { width: 100%; border-collapse: collapse; text-align: left; }
        .aksanti-table th { padding: 1.2rem; background: rgba(0,0,0,0.2); font-size: 0.7rem; font-weight: 900; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1px; }
        .aksanti-table td { padding: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 0.85rem; color: rgba(255,255,255,0.6); }
        .badge-inv { background: #10b981; color: white; padding: 3px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 900; }
        .tag-status { padding: 4px 10px; border-radius: 6px; font-size: 0.65rem; font-weight: 900; }
        .tag-published { background: rgba(16, 185, 129, 0.1); color: #34d399; }
        .tag-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .btn-micro-aksanti { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 6px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 5px; }
        .btn-micro-aksanti.active-boost { background: #10b981; border-color: #10b981; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
    </style>


    <script>
    function switchTab(tabId) {
        document.querySelectorAll('.analytics-section').forEach(s => s.style.display = 'none');
        document.querySelectorAll('.tab-btn-aksanti').forEach(t => t.classList.remove('active'));
        
        document.getElementById('sec-' + tabId).style.display = 'block';
        document.getElementById('tab-' + tabId).classList.add('active');
    }

    function toggleBoost(projectId, btn) {
        const formData = new FormData();
        formData.append('project_id', projectId);

        fetch('../interface_programacao/admin/boost_project.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.is_featured) {
                    btn.classList.add('active-boost');
                    btn.innerHTML = '<i class="fas fa-rocket"></i> Impulsionado';
                    Swal.fire({
                        title: 'Projeto Impulsionado!',
                        text: 'Este projeto agora tem visibilidade prioritária no marketplace.',
                        icon: 'success',
                        background: '#050a15',
                        color: '#fff',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    btn.classList.remove('active-boost');
                    btn.innerHTML = '<i class="fas fa-rocket"></i> Impulsionar';
                }
            } else {
                Swal.fire('Erro', data.message || 'Falha ao atualizar status', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Erro', 'Ocorreu um problema na ligação.', 'error');
        });
    }
    </script>

</body>
</html>

