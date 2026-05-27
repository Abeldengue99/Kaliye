<?php
// admin/commission_dashboard.php
// Dashboard para visualizar e gerir comissões da KALIYE e mentores
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Apenas admins podem aceder
if (!isAdmin() || !hasPermission('finances')) {
    header('Location: ../../index.php');
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Busco estatísticas gerais
$stats_query = "SELECT 
                    SUM(CASE WHEN commission_type = 'aksanti' THEN commission_amount ELSE 0 END) as total_aksanti,
                    SUM(CASE WHEN commission_type = 'mentor' THEN commission_amount ELSE 0 END) as total_mentor,
                    SUM(CASE WHEN commission_type = 'aksanti' AND status = 'paid' THEN commission_amount ELSE 0 END) as paid_aksanti,
                    SUM(CASE WHEN commission_type = 'mentor' AND status = 'paid' THEN commission_amount ELSE 0 END) as paid_mentor,
                    SUM(CASE WHEN commission_type = 'aksanti' AND status = 'pending' THEN commission_amount ELSE 0 END) as pending_aksanti,
                    SUM(CASE WHEN commission_type = 'mentor' AND status = 'pending' THEN commission_amount ELSE 0 END) as pending_mentor
                FROM commission_history";
$stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Busco comissões recentes
$recent_query = "SELECT ch.*, p.title as project_title, u.full_name as mentor_name, i.amount as investment_amount
                 FROM commission_history ch
                 JOIN projects p ON ch.project_id = p.project_id
                 LEFT JOIN users u ON ch.mentor_id = u.user_id
                 JOIN project_investments i ON ch.investment_id = i.investment_id
                 ORDER BY ch.created_at DESC
                 LIMIT 20";
$recent_commissions = $db->query($recent_query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>recursos/images/marca/apple-touch-icon-k.png">
    <title>Dashboard de Comissões - KALIYE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --accent-orange: #f7941d;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --success: #10b981;
            --warning: #fbbf24;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-orange);
        }
        
        .table-container {
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: rgba(0,0,0,0.2);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-pending { background: rgba(251, 191, 36, 0.2); color: var(--warning); }
        .badge-paid { background: rgba(16, 185, 129, 0.2); color: var(--success); }
        .badge-aksanti { background: rgba(247, 148, 29, 0.2); color: var(--accent-orange); }
        .badge-mentor { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        
        .btn-mark-paid {
            padding: 0.5rem 1rem;
            background: var(--success);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-mark-paid:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body>

<?php include '../barra_lateral.php'; ?>

<main class="admin-main-content">
    <header style="margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">
            <i class="fas fa-coins"></i> Dashboard de Comissões
        </h1>
        <p style="color: var(--text-secondary);">Gestão e acompanhamento de comissões da plataforma</p>
    </header>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Comissões KALIYE</div>
            <div class="stat-value"><?php echo number_format($stats['total_aksanti'] ?? 0, 2, ',', '.'); ?> AOA</div>
            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--success);">
                Pago: <?php echo number_format($stats['paid_aksanti'] ?? 0, 2, ',', '.'); ?> AOA
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Total Comissões Mentores</div>
            <div class="stat-value"><?php echo number_format($stats['total_mentor'] ?? 0, 2, ',', '.'); ?> AOA</div>
            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--success);">
                Pago: <?php echo number_format($stats['paid_mentor'] ?? 0, 2, ',', '.'); ?> AOA
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Pendentes KALIYE</div>
            <div class="stat-value" style="color: var(--warning);"><?php echo number_format($stats['pending_aksanti'] ?? 0, 2, ',', '.'); ?> AOA</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Pendentes Mentores</div>
            <div class="stat-value" style="color: var(--warning);"><?php echo number_format($stats['pending_mentor'] ?? 0, 2, ',', '.'); ?> AOA</div>
        </div>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Projeto</th>
                    <th>Tipo</th>
                    <th>Beneficiário</th>
                    <th>Investimento</th>
                    <th>Taxa</th>
                    <th>Comissão</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_commissions as $comm): ?>
                <tr id="commission-<?php echo $comm['commission_id']; ?>">
                    <td>#<?php echo $comm['commission_id']; ?></td>
                    <td><?php echo htmlspecialchars($comm['project_title']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $comm['commission_type']; ?>">
                            <?php echo $comm['commission_type'] == 'aksanti' ? 'KALIYE' : 'Mentor'; ?>
                        </span>
                    </td>
                    <td><?php echo $comm['commission_type'] == 'mentor' ? htmlspecialchars($comm['mentor_name']) : 'KALIYE'; ?></td>
                    <td><?php echo number_format($comm['investment_amount'], 2, ',', '.'); ?> AOA</td>
                    <td><?php echo $comm['commission_rate']; ?>%</td>
                    <td style="font-weight: 600; color: var(--accent-orange);">
                        <?php echo number_format($comm['commission_amount'], 2, ',', '.'); ?> AOA
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $comm['status']; ?>">
                            <?php echo $comm['status'] == 'paid' ? 'Pago' : 'Pendente'; ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($comm['created_at'])); ?></td>
                    <td>
                        <?php if ($comm['status'] == 'pending'): ?>
                        <button class="btn-mark-paid" onclick="markAsPaid(<?php echo $comm['commission_id']; ?>, '<?php echo htmlspecialchars($comm['project_title']); ?>', <?php echo $comm['commission_amount']; ?>)">
                            <i class="fas fa-check"></i> Marcar como Pago
                        </button>
                        <?php else: ?>
                        <span style="color: var(--success); font-size: 0.85rem;">
                            <i class="fas fa-check-circle"></i> Pago em <?php echo date('d/m/Y', strtotime($comm['paid_at'])); ?>
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
async function markAsPaid(commissionId, projectTitle, amount) {
    const result = await Swal.fire({
        title: 'Marcar como Pago?',
        html: `
            <p>Projeto: <strong>${projectTitle}</strong></p>
            <p>Valor: <strong>${amount.toLocaleString('pt-AO', {minimumFractionDigits: 2})} AOA</strong></p>
            <textarea id="payment-notes" class="swal2-input" placeholder="Notas sobre o pagamento (opcional)" style="height: 80px;"></textarea>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, marcar como pago',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        preConfirm: () => {
            return document.getElementById('payment-notes').value;
        }
    });
    
    if (result.isConfirmed) {
        try {
            const response = await fetch('../../interface_programacao/admin/mark_commission_paid.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    commission_id: commissionId,
                    notes: result.value
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                await Swal.fire({
                    title: 'Sucesso!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#f7941d'
                });
                
                // Recarregar a página para atualizar os dados
                location.reload();
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            Swal.fire({
                title: 'Erro!',
                text: error.message || 'Erro ao marcar comissão como paga',
                icon: 'error',
                confirmButtonColor: '#f7941d'
            });
        }
    }
}
</script>

</body>
</html>




