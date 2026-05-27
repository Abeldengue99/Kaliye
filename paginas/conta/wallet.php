<?php
/**
 * wallet.php - Premium Financial Ecosystem Dashboard (2026 Edition)
 */
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Obtém o identificador único do utilizador da sessão
$user_type = $_SESSION['user_type']; // Obtém o tipo de utilizador (perfil) da sessão
$m_status = $_SESSION['mentorship_status'] ?? 'unsubmitted'; // Obtém o estado de mentoria se disponível

// BLOQUEIO DE ACESSO - VERSÃO 1.0 (Estudantes e Mentores) // Início do bloco de segurança de acesso
$is_restricted = ($user_type === 'student' || $user_type === 'mentor' || strpos($user_type, 'student') !== false); // Identifica se o perfil pertence ao grupo restrito
if ($is_restricted && $user_type !== 'admin' && $user_type !== 'superadmin') { // Verifica se é restrito e NÃO é administrador
    echo "<script>window.location.href = '".$base_url."index.php?wallet_blocked=1';</script>"; // Redireciona para a home com um flag de bloqueio
    exit(); // Encerra a execução do script para impedir o carregamento dos dados financeiros
} // Fim do bloco de bloqueio de segurança

// Fetch Wallet Data (Optimized Query)
$user_stmt = $db->prepare("SELECT wallet_balance, total_invested, bank_iban FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$user_data_wallet = $user_stmt->fetch(PDO::FETCH_ASSOC);

$current_balance = $user_data_wallet['wallet_balance'] ?? 0;
$total_inv_stats = $user_data_wallet['total_invested'] ?? 0;
$user_iban = $user_data_wallet['bank_iban'] ?? '';

// Fetch Transactions (Last 50 for performance)
$trans_stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$trans_stmt->execute([$user_id]);
$all_transactions = $trans_stmt->fetchAll();

// Context-Specific Data
$investments = [];
if ($user_type == 'investor' || $user_type == 'admin') {
    $inv_stmt = $db->prepare("SELECT pi.*, p.title as project_title, u.full_name as owner_name FROM project_investments pi
                              JOIN projects p ON pi.project_id = p.project_id
                              JOIN users u ON p.owner_id = u.user_id WHERE pi.investor_id = ? ORDER BY pi.created_at DESC");
    $inv_stmt->execute([$user_id]);
    $investments = $inv_stmt->fetchAll();
}

$payouts = [];
if ($user_type != 'investor') {
    $p_stmt = $db->prepare("SELECT po.*, p.title as project_title, pi.amount as total_investment FROM payouts po
                            JOIN projects p ON po.project_id = p.project_id
                            JOIN project_investments pi ON po.investment_id = pi.investment_id WHERE po.recipient_id = ? ORDER BY po.created_at DESC");
    $p_stmt->execute([$user_id]);
    $payouts = $p_stmt->fetchAll();
}
?>

<!-- Premium Styles -->
<link rel="stylesheet" href="../../recursos/css/pages/wallet.css?v=<?php echo time(); ?>">

<script>
    document.body.classList.add('wallet-page');
    document.body.style.setProperty('--wallet-bg', "url('<?php echo $base_url; ?>recursos/images/wallet_premium_bg.png')");
</script>

<div class="wallet-grid">
    <!-- Sidebar Navigation & Status -->
    <?php include '../../inclusoes/components/wallet_sidebar.php'; ?>

    <!-- Main Viewport -->
    <main class="wallet-main">
        
        <!-- Section: Activity -->
        <section class="glass-container">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-history"></i> Atividade Recente
                </h3>
                <div class="filter-group" id="transactionsFilter">
                    <button class="filter-btn active" onclick="filterTransactions('all', this)">Geral</button>
                    <button class="filter-btn" onclick="filterTransactions('in', this)">Crédito</button>
                    <button class="filter-btn" onclick="filterTransactions('out', this)">Débito</button>
                </div>
            </div>

            <div class="transactions-list" id="transactionsList">
                <?php if (count($all_transactions) > 0): ?>
                    <?php foreach ($all_transactions as $t): 
                        $is_positive = $t['amount'] >= 0;
                        $bg_color = $is_positive ? 'rgba(16, 185, 129, 0.1)' : 'rgba(244, 63, 94, 0.1)';
                        $txt_color = $is_positive ? '#10b981' : '#f43f5e';
                        $icon = 'fa-exchange-alt';
                        if($t['type'] == 'deposit') $icon = 'fa-arrow-down';
                        elseif($t['type'] == 'withdraw') $icon = 'fa-arrow-up';
                        elseif($t['type'] == 'investment') $icon = 'fa-rocket';
                        elseif($t['type'] == 'payout') $icon = 'fa-crown';
                    ?>
                        <?php 
                            $ref_match = [];
                            $is_pending_deposit = ($t['type'] == 'deposit' && $t['status'] == 'pending');
                            $reference_id = '';
                            if ($is_pending_deposit && preg_match('/Ref: (\d+)/', $t['description'], $ref_match)) {
                                $reference_id = $ref_match[1];
                            }
                        ?>
                        <div class="transaction-item <?php echo $reference_id ? 'clickable-deposit' : ''; ?>" 
                             data-type="<?php echo $is_positive ? 'in' : 'out'; ?>"
                             <?php if($reference_id): ?> 
                                onclick="openPaymentSuccessModal('<?php echo $reference_id; ?>', '<?php echo number_format($t['amount'], 0, ',', '.'); ?>')" 
                                style="cursor: pointer;"
                                title="Clique para ver dados de pagamento"
                             <?php endif; ?>>
                            <div class="trans-icon" style="background: <?php echo $bg_color; ?>; color: <?php echo $txt_color; ?>;">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="trans-info">
                                <span class="trans-title"><?php 
                                    if(!empty($t['description'])) {
                                        echo htmlspecialchars($t['description']);
                                    } else {
                                        $labels = ['deposit' => 'Depósito em Carteira', 'withdraw' => 'Levantamento solicitado', 'investment' => 'Investimento em Projecto', 'payout' => 'Ganho Recebido'];
                                        echo $labels[$t['type']] ?? ucfirst($t['type']); 
                                    }
                                ?></span>
                                <span class="trans-meta"><?php echo date('d M Y • H:i', strtotime($t['created_at'])); ?></span>
                            </div>
                            <div class="trans-amount-status">
                                <span class="trans-amount" style="color: <?php echo $is_positive ? 'var(--brand-green)' : 'var(--text-primary)'; ?>;">
                                    <?php echo ($is_positive ? '+' : '') . number_format($t['amount'], 0, ',', '.'); ?> <small>AKZ</small>
                                </span>
                                <!-- Estado da Transação: Traduzido para clareza do utilizador -->
                                <?php 
                                    $status_map = [
                                        'pending' => 'PENDENTE',
                                        'completed' => 'CONCLUÍDO',
                                        'confirmed' => 'CONFIRMADO',
                                        'paid' => 'PAGO',
                                        'failed' => 'FALHOU',
                                        'rejected' => 'REJEITADO',
                                        'pending_approval' => 'EM APROVAÇÃO'
                                    ];
                                    $status_text = $status_map[$t['status']] ?? strtoupper($t['status']);
                                    
                                    $status_class = 'badge-orange';
                                    if(in_array($t['status'], ['completed', 'confirmed', 'paid'])) $status_class = 'badge-green';
                                    if(in_array($t['status'], ['failed', 'rejected'])) $status_class = 'badge-danger';
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>Nenhuma transação registada até ao momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Section: Projects Flow -->
        <section class="glass-container">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-chart-pie"></i> 
                    <?php echo ($user_type == 'investor' || $user_type == 'admin') ? 'Fluxo de Projectos' : 'Agendamento de Ganhos'; ?>
                </h3>
                <a href="#" class="btn-secondary" style="padding: 0.6rem 1rem; text-decoration: none; font-size: 0.75rem;">Ver Relatório <i class="fas fa-chevron-right" style="margin-left:5px"></i></a>
            </div>
            
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="wallet-table">
                    <thead>
                        <tr>
                            <?php if($user_type == 'investor' || $user_type == 'admin'): ?>
                                <th class="col-date">Data</th>
                                <th class="col-title">Escopo/Projecto</th>
                                <th class="col-desc">Responsável</th>
                                <th class="col-amount">Montante</th>
                                <th class="col-status">Status</th>
                                <th class="col-status">Ação</th>
                            <?php else: ?>
                                <th class="col-date">Data Prevista</th>
                                <th class="col-title">Origem</th>
                                <th class="col-desc">Descrição do Ganho</th>
                                <th class="col-amount">Quota</th>
                                <th class="col-status">Estado</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($user_type == 'investor' || $user_type == 'admin'): ?>
                            <?php if(count($investments) > 0): ?>
                                <?php foreach ($investments as $inv): ?>
                                    <tr>
                                        <td><span style="color: var(--text-muted); font-size: 0.85rem;"><?php echo date('d/m/Y', strtotime($inv['created_at'])); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($inv['project_title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($inv['owner_name']); ?></td>
                                        <td class="col-amount"><span style="color: white; font-weight: 800;"><?php echo number_format($inv['amount'], 0, ',', '.'); ?></span> <small style="color: var(--brand-primary);">AKZ</small></td>
                                        <td class="col-status">
                                            <?php 
                                                $inv_status_map = [
                                                    'awaiting_payment' => 'AGUARDA-PAGAMENTO',
                                                    'pending_approval' => 'EM-APROVAÇÃO',
                                                    'paid' => 'PAGO',
                                                    'active' => 'ATIVO',
                                                    'cancelled' => 'CANCELADO'
                                                ];
                                                $inv_status_text = $inv_status_map[$inv['status']] ?? strtoupper($inv['status']);
                                                
                                                $inv_status_class = 'badge-orange';
                                                if($inv['status'] == 'paid' || $inv['status'] == 'active') $inv_status_class = 'badge-green';
                                                if($inv['status'] == 'cancelled') $inv_status_class = 'badge-danger';
                                            ?>
                                            <span class="badge <?php echo $inv_status_class; ?>"><?php echo $inv_status_text; ?></span>
                                        </td>
                                        <td class="col-status">
                                            <?php if($inv['status'] == 'awaiting_payment'): ?>
                                                <button onclick="openUploadProofModal(<?php echo $inv['investment_id']; ?>, '<?php echo $inv['payment_reference']; ?>', '<?php echo number_format($inv['amount'], 0, ',', '.'); ?>', '<?php echo $inv['currency']; ?>')" class="btn-primary" style="padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.7rem; border:none; cursor:pointer;">Pagar</button>
                                            <?php else: ?>
                                                <i class="fas fa-file-invoice-dollar" style="color: var(--brand-primary); opacity: 0.8; font-size: 1.2rem; cursor: pointer;"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6"><div class="empty-state" style="padding: 2rem;"><p>Sem investimentos ativos.</p></div></td></tr>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if(count($payouts) > 0): ?>
                                <?php foreach ($payouts as $po): ?>
                                    <tr>
                                        <td><span style="color: var(--text-muted); font-size: 0.85rem;"><?php echo date('d/m/Y', strtotime($po['scheduled_date'])); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($po['project_title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($po['description']); ?></td>
                                        <td class="col-amount"><span style="color: var(--brand-green); font-weight: 900;"><?php echo number_format($po['amount'], 0, ',', '.'); ?></span> <small>AKZ</small></td>
                                        <?php 
                                            $po_status_map = [
                                                'scheduled' => 'AGENDADO',
                                                'paid' => 'PAGO',
                                                'cancelled' => 'CANCELADO'
                                            ];
                                            $po_status_text = $po_status_map[$po['status']] ?? strtoupper($po['status']);
                                        ?>
                                        <td class="col-status"><span class="badge badge-blue"><?php echo $po_status_text; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6"><div class="empty-state" style="padding: 2rem;"><p>Nenhum ganho agendado.</p></div></td></tr>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<!-- Essential Components -->
<?php include '../../inclusoes/components/wallet_modals.php'; ?>
<?php include '../../inclusoes/components/wallet_scripts.php'; ?>

<?php require_once '../../inclusoes/rodape.php'; ?>

