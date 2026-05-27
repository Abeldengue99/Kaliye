<?php
// admin/finances.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('finances')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch all approved investments that have been PAID
$query = "
    SELECT pi.*, p.title as project_title, p.owner_id,
    u.full_name as investor_name,
    owner.full_name as owner_name,
    m.mentor_id as auto_mentor_id,
    mentor.full_name as mentor_name,
    (SELECT SUM(amount) FROM payouts WHERE investment_id = pi.investment_id) as split_amount
    FROM project_investments pi
    JOIN projects p ON pi.project_id = p.project_id
    JOIN users u ON pi.investor_id = u.user_id
    JOIN users owner ON p.owner_id = owner.user_id
    LEFT JOIN mentorships m ON p.owner_id = m.mentee_id AND m.status = 'active'
    LEFT JOIN users mentor ON m.mentor_id = mentor.user_id
    WHERE pi.status = 'paid' OR pi.status = 'approved'
    ORDER BY pi.created_at DESC
";
$stmt = $db->query($query);
$investments = $stmt->fetchAll();

// Need list of mentors and students for the split form
$mentors = $db->query("SELECT user_id, full_name FROM users WHERE user_type = 'mentor' OR (user_type = 'univ_student' AND mentorship_status = 'approved')")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão Financeira | KALIYE Admin</title>
    <link rel='icon' type='image/png' href='../../recursos/images/marca/favicon-k-32x32.png'>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?php echo filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .finance-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        .finance-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.05);
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-gold), #10b981);
            transition: width 0.5s ease-in-out;
        }
        .split-modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background: #0f172a;
            border: 1px solid var(--glass-border);
            padding: 2.5rem;
            border-radius: 20px;
            max-width: 700px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body style="display: flex;">
    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1><i class="fas fa-coins" style="color: var(--accent-gold);"></i> Gestão Financeira</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Divisão de capital e controle de tranches para projectos financiados.</p>
            </div>
            <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); padding: 1rem 1.5rem; border-radius: 12px; text-align: left; min-width: 250px;">
                <div style="font-size: 0.75rem; color: #10b981; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">Liquidez de Projectos</div>
                <div style="font-size: 1.8rem; font-weight: 900; color: #fff;">
                    <?php 
                        $total = array_sum(array_column($investments, 'amount'));
                        echo number_format($total, 2, ',', '.');
                    ?> <span style="font-size: 1rem; color: rgba(255,255,255,0.5); font-weight: 600;">AOA</span>
                </div>
            </div>
        </header>

        <?php if (count($investments) == 0): ?>
            <div class="glass" style="padding: 3rem 1.5rem; text-align: center; border-radius: 20px;">
                <i class="fas fa-receipt" style="font-size: 4rem; color: var(--text-secondary); opacity: 0.2; margin-bottom: 1.5rem;"></i>
                <h3 style="color: var(--text-secondary); font-size: 1.2rem;">Nenhum investimento aprovado para distribuição.</h3>
                <p style="color: var(--text-secondary); max-width: 400px; margin: 1rem auto; font-size: 0.9rem;">Os investimentos aparecerão aqui assim que o comprovativo de pagamento do investidor for validado.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                <?php foreach($investments as $inv): 
                    $split = $inv['split_amount'] ?? 0;
                    $percent = ($split / $inv['amount']) * 100;
                    $statusColor = $percent >= 100 ? '#10b981' : ($percent > 0 ? 'var(--accent-gold)' : '#64748b');
                ?>
                    <div class="finance-card">
                        <div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                            <div style="flex: 1 1 250px;">
                                <h3 style="margin: 0; color: white; font-size: 1.2rem; line-height: 1.3;"><?php echo htmlspecialchars($inv['project_title']); ?></h3>
                                <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.75rem; font-size: 0.85rem; color: var(--text-secondary);">
                                    <span><i class="fas fa-user-tie"></i> Investidor: <strong><?php echo htmlspecialchars($inv['investor_name']); ?></strong></span>
                                    <span><i class="fas fa-calendar-alt"></i> Data: <?php echo date('d/m/Y', strtotime($inv['created_at'])); ?></span>
                                </div>
                            </div>
                            <div style="text-align: left; background: rgba(255,255,255,0.02); padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); min-width: 150px;">
                                <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Montante Total</div>
                                <div style="font-size: 1.2rem; font-weight: 800; color: var(--accent-gold);"><?php echo number_format($inv['amount'], 2, ',', '.'); ?> <span style="font-size: 0.8rem;">AOA</span></div>
                            </div>
                        </div>

                        <div style="background: rgba(0,0,0,0.2); border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span style="font-size: 0.85rem; color: var(--text-secondary);">Estado da Distribuição</span>
                                <span style="font-size: 0.85rem; font-weight: bold; color: <?php echo $statusColor; ?>;"><?php echo round($percent); ?>% Distribuído</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.8rem; color: var(--text-secondary);">
                                <span>Distribuído: <?php echo number_format($split, 2, ',', '.'); ?> AOA</span>
                                <span>Restante: <?php echo number_format($inv['amount'] - $split, 2, ',', '.'); ?> AOA</span>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                            <button onclick='openSplitModal(<?php echo htmlspecialchars(json_encode($inv), ENT_QUOTES, "UTF-8"); ?>)' class="btn-primary" style="background: var(--accent-blue); border: none; padding: 0.6rem 1.5rem; font-size: 0.85rem;">
                                <i class="fas fa-scissors"></i> Repartir Capital
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Split Modal -->
    <div id="splitModal" class="split-modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="margin: 0;"><i class="fas fa-layer-group" style="color: var(--accent-gold);"></i> Repartição de Capital</h2>
                <button onclick="document.getElementById('splitModal').style.display='none'" style="background: none; border: none; color: #64748b; cursor: pointer; font-size: 2rem;">&times;</button>
            </div>
            
            <div id="projectInfo" style="margin-bottom: 2rem; background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border); padding: 1.5rem; border-radius: 12px;">
                <!-- Injected by JS -->
            </div>

            <form id="distributeForm" action="../../interface_programacao/admin/admin_distribute_capital.php" method="POST">
                <input type="hidden" name="investment_id" id="form_investment_id">
                <input type="hidden" name="project_id" id="form_project_id">
                <input type="hidden" name="owner_id" id="form_owner_id">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="input-group" style="margin: 0;">
                        <label style="display: block; margin-bottom: 8px; font-size: 0.85rem; color: var(--text-secondary);">Mentor Responsável</label>
                        <select name="mentor_id" required style="width: 100%; height: 50px; background: rgba(255,255,255,0.05); color: white; border: 1px solid var(--glass-border); padding: 0 1rem; border-radius: 8px;">
                            <option value="">Selecionar Mentor...</option>
                            <?php foreach($mentors as $m): ?>
                                <option value="<?php echo $m['user_id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group" style="margin: 0;">
                        <label style="display: block; margin-bottom: 8px; font-size: 0.85rem; color: var(--text-secondary);">Honorários Mentor (AOA)</label>
                        <input type="number" name="mentor_amount" step="0.01" required value="0" style="height: 50px; background: rgba(255,255,255,0.05); color: white;">
                    </div>
                </div>

                <div class="input-group" style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 8px; font-size: 0.85rem; color: var(--text-secondary);">Comissão da Plataforma KALIYE (AOA)</label>
                    <input type="number" name="company_amount" step="0.01" required value="0" style="height: 50px; background: rgba(255,255,255,0.05); color: white;">
                </div>

                <div style="margin-top: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h4 style="margin: 0; color: var(--accent-orange);"><i class="fas fa-hand-holding-usd"></i> Tranches do Estudante (Autor)</h4>
                        <button type="button" onclick="addTranche()" style="background: rgba(245, 158, 11, 0.1); border: 1px solid var(--accent-orange); color: var(--accent-orange); padding: 0.4rem 1rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+ Nova Tranche</button>
                    </div>
                    
                    <div id="tranchesContainer">
                        <div class="tranche-row" style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                            <input type="text" name="tranche_desc[]" value="1ª Prestação (Início do Projecto)" placeholder="Descrição" required style="flex: 2; height: 45px;">
                            <input type="number" name="tranche_amount[]" step="0.01" value="0" placeholder="Valor" required style="flex: 1; height: 45px;">
                            <select name="tranche_status[]" style="flex: 1; height: 45px; background: #1e293b; color: white; border: 1px solid #334155; border-radius: 6px;">
                                <option value="paid">Pagar Já</option>
                                <option value="pending">Agendar</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; height: 55px; margin-top: 2rem; font-weight: bold; background: linear-gradient(90deg, #10b981, #059669);">
                    <i class="fas fa-check-double"></i> Confirmar e Distribuir Valores
                </button>
            </form>
        </div>
    </div>

    <script>
    function openSplitModal(inv) {
        document.getElementById('form_investment_id').value = inv.investment_id;
        document.getElementById('form_project_id').value = inv.project_id;
        document.getElementById('form_owner_id').value = inv.owner_id;
        
        // Auto-select mentor if found
        const mentorSelect = document.querySelector('select[name="mentor_id"]');
        if (inv.auto_mentor_id) {
            mentorSelect.value = inv.auto_mentor_id;
        } else {
            mentorSelect.value = "";
        }

        document.getElementById('projectInfo').innerHTML = `
            <div style="font-weight: 800; font-size: 1.2rem; color: white; margin-bottom: 0.5rem;">${inv.project_title}</div>
            <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1rem;">
                <i class="fas fa-id-badge"></i> Proprietário: <strong>${inv.owner_name}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem;">
                <span style="color: var(--text-secondary); font-size: 0.9rem;"><i class="fas fa-wallet"></i> Capital sob Custódia:</span>
                <span style="font-size: 1.5rem; font-weight: 800; color: var(--accent-gold);">${new Intl.NumberFormat('pt-AO').format(inv.amount)} AOA</span>
            </div>
        `;
        document.getElementById('splitModal').style.display = 'flex';
    }

    function addTranche() {
        const container = document.getElementById('tranchesContainer');
        const div = document.createElement('div');
        div.className = 'tranche-row';
        div.style.display = 'flex';
        div.style.gap = '0.5rem';
        div.style.marginBottom = '1rem';
        div.innerHTML = `
            <input type="text" name="tranche_desc[]" placeholder="Descrição da Tranche" required style="flex: 2; height: 45px;">
            <input type="number" name="tranche_amount[]" step="0.01" placeholder="Valor AOA" required style="flex: 1; height: 45px;">
            <select name="tranche_status[]" style="flex: 1; height: 45px; background: #1e293b; color: white; border: 1px solid #334155; border-radius: 6px;">
                <option value="pending">Agendar</option>
                <option value="paid">Pagar Já</option>
            </select>
            <button type="button" onclick="this.parentElement.remove()" style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; border-radius: 6px; width: 45px; cursor: pointer;">&times;</button>
        `;
        container.appendChild(div);
    }

    // Modal close on background click
    window.onclick = function(event) {
        const modal = document.getElementById('splitModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
</body>
</html>
