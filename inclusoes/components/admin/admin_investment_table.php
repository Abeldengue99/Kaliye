<?php
/**
 * Component: Admin Investment Flow Table
 * Expected Variable: $investments (array)
 */
?>
<div class="admin-card-glass" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0;"><i class="fas fa-exchange-alt" style="color: var(--accent-orange);"></i> Fluxo de Capital</h3>
    </div>
    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse; color: white;">
            <thead>
                <tr style="border-bottom: 1px solid var(--glass-border); text-align: left;">
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">ID</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">Projeto / Partes</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">Valor</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">Estado</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">Ações Administrativas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($investments as $inv): ?>
                <tr style="border-bottom: 1px solid var(--surface-5); transition: background 0.3s;">
                    <td style="padding: 1rem; font-family: monospace; color: var(--text-secondary);">#<?= $inv['investment_id'] ?></td>
                    <td style="padding: 1rem;">
                        <div style="font-weight: 700; color: white;"><?= htmlspecialchars($inv['project_title']) ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;">
                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($inv['investor_name']) ?> <i class="fas fa-arrow-right" style="font-size: 0.75rem; margin: 0 4px;"></i> <?= htmlspecialchars($inv['owner_name']) ?>
                        </div>
                    </td>
                    <td style="padding: 1rem;">
                        <div style="color: var(--accent-gold); font-weight: 800; font-size: 1.1rem;">
                            <?= number_format($inv['amount'], 2, ',', '.') ?> <span style="font-size: 0.7rem;"><?= $inv['currency'] ?></span>
                        </div>
                    </td>
                    <td style="padding: 1rem;">
                        <?php 
                            $status_map = [
                                'awaiting_payment' => ['label' => 'Aguardando', 'color' => '#64748b'],
                                'pending' => ['label' => 'Em Análise', 'color' => '#f59e0b'],
                                'approved' => ['label' => 'Aprovado', 'color' => '#3b82f6'],
                                'paid' => ['label' => 'Pago', 'color' => '#10b981'],
                                'cancelled' => ['label' => 'Cancelado', 'color' => '#ef4444']
                            ];
                            $st = $status_map[$inv['status']] ?? ['label' => $inv['status'], 'color' => '#64748b'];
                        ?>
                        <span style="padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; border: 1px solid <?= $st['color'] ?>; color: <?= $st['color'] ?>; background: <?= $st['color'] ?>11;">
                            <?= $st['label'] ?>
                        </span>
                    </td>
                    <td style="padding: 1rem;">
                        <div style="display: flex; gap: 0.5rem;">
                            <?php if ($inv['proof_document_path']): ?>
                                <button onclick="window.open('../<?= htmlspecialchars($inv['proof_document_path']) ?>', '_blank')" class="btn-action" title="Ver Comprovativo">
                                    <i class="fas fa-file-invoice"></i>
                                </button>
                                <?php if($inv['status'] == 'pending'): ?>
                                    <button onclick="analyzeProof(<?= $inv['investment_id'] ?>, '<?= $inv['amount'] ?>', '<?= $inv['currency'] ?>', '<?= $inv['proof_document_path'] ?>', '<?= addslashes($inv['project_title']) ?>')" class="btn-action ai" title="Análise IA">
                                        <i class="fas fa-robot"></i>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if($inv['status'] == 'pending'): ?>
                                <button onclick="processInvestment(<?= $inv['investment_id'] ?>, 'approved')" class="btn-action success" title="Aprovar"><i class="fas fa-check"></i></button>
                                <button onclick="rejectInvestment(<?= $inv['investment_id'] ?>)" class="btn-action danger" title="Rejeitar"><i class="fas fa-times"></i></button>
                            <?php elseif($inv['status'] == 'approved'): ?>
                                <button onclick="processInvestment(<?= $inv['investment_id'] ?>, 'paid')" class="btn-confirm-pay">Confirmar Pagamento</button>
                            <?php else: ?>
                                <span style="font-size: 0.75rem; color: #10b981;"><i class="fas fa-check-double"></i> Validado</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <!-- Analysis Row Holder -->
                <tr id="analysis-row-<?= $inv['investment_id'] ?>" style="display: none;">
                    <td colspan="5" style="padding: 0 1rem 1rem 1rem;">
                        <div id="analysis-box-<?= $inv['investment_id'] ?>" class="ai-analysis-output"></div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.btn-action {
    width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--glass-border);
    background: var(--surface-5); color: white; cursor: pointer; transition: 0.3s;
    display: flex; align-items: center; justify-content: center;
}
.btn-action:hover { background: var(--accent-orange); border-color: var(--accent-orange); }
.btn-action.ai { color: #8b5cf6; border-color: rgba(139, 92, 246, 0.3); }
.btn-action.ai:hover { background: #8b5cf6; color: white; }
.btn-action.success { color: #10b981; border-color: rgba(16, 185, 129, 0.3); }
.btn-action.success:hover { background: #10b981; color: white; }
.btn-action.danger { color: #ef4444; border-color: rgba(239, 68, 68, 0.3); }
.btn-action.danger:hover { background: #ef4444; color: white; }

.btn-confirm-pay {
    padding: 0.4rem 1rem; border-radius: 8px; background: #3b82f6; color: white;
    border: none; font-size: 0.75rem; font-weight: 700; cursor: pointer;
}

.ai-analysis-output {
    background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 12px; padding: 1.5rem; margin-top: 10px;
}
</style>
