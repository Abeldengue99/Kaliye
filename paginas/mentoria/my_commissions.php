<?php
// mentoria/my_commissions.php
// Painel para mentores visualizarem suas comiss�es
session_start();
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Verificar autentica��o
if (!isset($_SESSION['user_id'])) {
    header('Location: ../entrar.php');
    exit;
}

$mentor_id = $_SESSION['user_id'];
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Buscar estat�sticas do mentor
$stats_query = "SELECT 
                    COUNT(*) as total_commissions,
                    SUM(commission_amount) as total_earned,
                    SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as total_paid,
                    SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as total_pending
                FROM commission_history
                WHERE mentor_id = :mentor_id AND commission_type = 'mentor'";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([':mentor_id' => $mentor_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Buscar hist�rico de comiss�es
$history_query = "SELECT ch.*, p.title as project_title, pi.amount as investment_amount
                  FROM commission_history ch
                  JOIN projects p ON ch.project_id = p.project_id
                  JOIN project_investments pi ON ch.investment_id = pi.investment_id
                  WHERE ch.mentor_id = :mentor_id AND ch.commission_type = 'mentor'
                  ORDER BY ch.created_at DESC";

$history_stmt = $db->prepare($history_query);
$history_stmt->execute([':mentor_id' => $mentor_id]);
$commissions = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>recursos/images/marca/apple-touch-icon-k.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Comiss�es - KALIYE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            padding: 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            margin-bottom: 2rem;
        }
        
        h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(247, 148, 29, 0.2);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        .badge-pending { 
            background: rgba(251, 191, 36, 0.2); 
            color: var(--warning); 
        }
        
        .badge-paid { 
            background: rgba(16, 185, 129, 0.2); 
            color: var(--success); 
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--accent-orange);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .back-btn:hover {
            background: #e67e0d;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
        </a>
        
        <header>
            <h1>
                <i class="fas fa-coins"></i>
                Minhas Comiss�es
            </h1>
            <p style="color: var(--text-secondary);">Acompanhe suas comiss�es como mentor</p>
        </header>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">
                    <i class="fas fa-chart-line"></i>
                    Total de Comiss�es
                </div>
                <div class="stat-value"><?php echo $stats['total_commissions'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">
                    <i class="fas fa-wallet"></i>
                    Total Ganho
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_earned'] ?? 0, 2, ',', '.'); ?> AOA</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">
                    <i class="fas fa-check-circle"></i>
                    Total Pago
                </div>
                <div class="stat-value" style="color: var(--success);">
                    <?php echo number_format($stats['total_paid'] ?? 0, 2, ',', '.'); ?> AOA
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">
                    <i class="fas fa-clock"></i>
                    Pendente
                </div>
                <div class="stat-value" style="color: var(--warning);">
                    <?php echo number_format($stats['total_pending'] ?? 0, 2, ',', '.'); ?> AOA
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <?php if (count($commissions) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Projecto</th>
                        <th>Investimento</th>
                        <th>Taxa</th>
                        <th>Comiss�o</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Pago em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commissions as $comm): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($comm['project_title']); ?></td>
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
                            <?php echo $comm['paid_at'] ? date('d/m/Y', strtotime($comm['paid_at'])) : '-'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Nenhuma comiss�o ainda</h3>
                <p>Voc� ainda n�o possui comiss�es registradas. Continue mentorando projectos!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>


