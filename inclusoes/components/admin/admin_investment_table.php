<?php
/**
 * Component: Admin Investment Flow Table (Phase 1 - Candidaturas)
 * Expected Variable: $investments (array)
 */
?>
<div class="admin-card-glass" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0;"><i class="fas fa-hand-holding-usd" style="color: var(--accent-orange);"></i> Propostas de Investimento</h3>
        <div style="background: rgba(247,148,29,0.08); padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; color: #f7941d; border: 1px solid rgba(247,148,29,0.15);">
            FASE 1 — SEM PAGAMENTO DIGITAL
        </div>
    </div>
    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse; color: white;">
            <thead>
                <tr style="border-bottom: 1px solid var(--glass-border); text-align: left;">
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">ID</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">Projeto / Partes</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">Valor Proposto</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">Motivação do Investidor</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">Estado</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($investments)): ?>
                <tr>
                    <td colspan="6" style="padding: 3rem; text-align: center; color: rgba(255,255,255,0.3);">
                        <i class="fas fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                        Nenhuma proposta de investimento registada.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach($investments as $inv): ?>
                <?php 
                    $is_delayed = false;
                    if ($inv['status'] === 'pending') {
                        $hours_passed = (time() - strtotime($inv['created_at'])) / 3600;
                        if ($hours_passed > 72) {
                            $is_delayed = true;
                        }
                    }
                    $row_style = "border-bottom: 1px solid var(--surface-5); transition: background 0.3s;";
                    if ($is_delayed) {
                        $row_style .= " background: rgba(239, 68, 68, 0.05); border-left: 3px solid #ef4444;";
                    }
                ?>
                <tr style="<?= $row_style ?>" id="inv-row-<?= $inv['investment_id'] ?>">
                    <td style="padding: 1rem; font-family: monospace; color: var(--text-secondary);">
                        #<?= $inv['investment_id'] ?>
                        <?php if($is_delayed): ?>
                            <div style="margin-top: 5px; font-size: 0.6rem; color: #ef4444; font-weight: 800; text-transform: uppercase;">
                                <i class="fas fa-exclamation-triangle"></i> Atrasado
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem;">
                        <div style="font-weight: 700; color: white;"><?= htmlspecialchars($inv['project_title']) ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;">
                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($inv['investor_name']) ?> <i class="fas fa-arrow-right" style="font-size: 0.75rem; margin: 0 4px;"></i> <?= htmlspecialchars($inv['owner_name']) ?>
                        </div>
                        <?php if (!empty($inv['investor_linkedin'] ?? '')): ?>
                        <a href="<?= htmlspecialchars($inv['investor_linkedin']) ?>" target="_blank" style="font-size: 0.7rem; color: #3b82f6; text-decoration: none; margin-top: 4px; display: inline-block;">
                            <i class="fab fa-linkedin"></i> LinkedIn
                        </a>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem;">
                        <div style="color: var(--accent-gold); font-weight: 800; font-size: 1.1rem;">
                            <?= number_format($inv['amount'], 2, ',', '.') ?> <span style="font-size: 0.7rem;"><?= $inv['currency'] ?? 'AOA' ?></span>
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-secondary); margin-top: 2px;">
                            <?= htmlspecialchars($inv['investment_type'] ?? 'equity') ?> 
                        </div>
                        <div style="font-size: 0.65rem; color: <?= $is_delayed ? '#ef4444' : 'rgba(255,255,255,0.3)' ?>; margin-top: 2px; font-weight: <?= $is_delayed ? '800' : 'normal' ?>;">
                            <?= date('d/m/Y H:i', strtotime($inv['created_at'])) ?>
                        </div>
                    </td>
                    <td style="padding: 1rem; max-width: 280px;">
                        <?php if (!empty($inv['investor_motivation'] ?? '')): ?>
                            <div style="font-size: 0.8rem; color: #cbd5e1; line-height: 1.5; max-height: 80px; overflow-y: auto; padding-right: 8px;">
                                <?= htmlspecialchars($inv['investor_motivation']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($inv['investor_experience'] ?? '')): ?>
                            <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 6px;">
                                <i class="fas fa-briefcase" style="margin-right: 4px; color: #f7941d;"></i> <?= htmlspecialchars($inv['investor_experience']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (empty($inv['investor_motivation'] ?? '') && empty($inv['investor_experience'] ?? '')): ?>
                            <span style="font-size: 0.75rem; color: rgba(255,255,255,0.2);">— Sem motivação registada</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem;">
                        <?php 
                            $status_map = [
                                'pending' => ['label' => 'Pendente', 'color' => '#f59e0b'],
                                'approved' => ['label' => 'Aprovado', 'color' => '#10b981'],
                                'rejected' => ['label' => 'Rejeitado', 'color' => '#ef4444'],
                                'paid' => ['label' => 'Pago', 'color' => '#3b82f6'],
                                'cancelled' => ['label' => 'Cancelado', 'color' => '#64748b']
                            ];
                            $st = $status_map[$inv['status']] ?? ['label' => $inv['status'], 'color' => '#64748b'];
                        ?>
                        <span style="padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; border: 1px solid <?= $st['color'] ?>; color: <?= $st['color'] ?>; background: <?= $st['color'] ?>11;">
                            <?= $st['label'] ?>
                        </span>
                    </td>
                    <td style="padding: 1rem;">
                        <div style="display: flex; gap: 0.5rem;">
                            <?php if($inv['status'] == 'pending'): ?>
                                <button onclick="approveInvestmentProposal(<?= $inv['investment_id'] ?>, '<?= addslashes($inv['investor_name'] ?? '') ?>')" class="btn-action success" title="Aprovar"><i class="fas fa-check"></i></button>
                                <button onclick="rejectInvestmentProposal(<?= $inv['investment_id'] ?>, '<?= addslashes($inv['investor_name'] ?? '') ?>')" class="btn-action danger" title="Rejeitar"><i class="fas fa-times"></i></button>
                            <?php elseif($inv['status'] == 'approved'): ?>
                                <span style="font-size: 0.75rem; color: #10b981;"><i class="fas fa-check-double"></i> Aprovado</span>
                            <?php elseif($inv['status'] == 'rejected'): ?>
                                <span style="font-size: 0.75rem; color: #ef4444;"><i class="fas fa-ban"></i> Rejeitado</span>
                            <?php else: ?>
                                <span style="font-size: 0.75rem; color: #64748b;"><i class="fas fa-minus-circle"></i> <?= $st['label'] ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
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
.btn-action.success { color: #10b981; border-color: rgba(16, 185, 129, 0.3); }
.btn-action.success:hover { background: #10b981; color: white; }
.btn-action.danger { color: #ef4444; border-color: rgba(239, 68, 68, 0.3); }
.btn-action.danger:hover { background: #ef4444; color: white; }
</style>

<script>
function approveInvestmentProposal(investmentId, investorName) {
    Swal.fire({
        title: 'Aprovar Proposta?',
        html: `<p style="color: #94a3b8;">Confirma a aprovação da proposta de <strong style="color:#fff;">${investorName}</strong>?<br>O investidor será notificado e a equipa KALIYE fará o contacto presencial.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sim, Aprovar',
        cancelButtonText: 'Cancelar',
        background: '#0d1628',
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../interface_programacao/admin/admin_process_investment_proposal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ investment_id: investmentId, action: 'approve' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Aprovada!', text: data.message, background: '#0d1628', color: '#fff' })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.error || 'Erro ao aprovar.', background: '#0d1628', color: '#fff' });
                }
            });
        }
    });
}

function rejectInvestmentProposal(investmentId, investorName) {
    Swal.fire({
        title: 'Rejeitar Proposta?',
        html: `
            <p style="color: #94a3b8; margin-bottom: 1rem;">Rejeitar a proposta de <strong style="color:#fff;">${investorName}</strong>?</p>
            <textarea id="rejectNotes" placeholder="Motivo da rejeição (opcional)..." rows="3" style="width: 100%; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 10px; padding: 0.8rem; resize: none; font-family: inherit;"></textarea>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Rejeitar',
        cancelButtonText: 'Cancelar',
        background: '#0d1628',
        color: '#fff',
        preConfirm: () => {
            return document.getElementById('rejectNotes').value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../interface_programacao/admin/admin_process_investment_proposal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ investment_id: investmentId, action: 'reject', admin_notes: result.value || '' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Rejeitada', text: data.message, background: '#0d1628', color: '#fff' })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.error || 'Erro ao rejeitar.', background: '#0d1628', color: '#fff' });
                }
            });
        }
    });
}
</script>
