<?php
/**
 * Component: Admin Settings Cards
 * Expected Variable: $settings (array)
 */
?>
<div class="responsive-grid-3">
    <!-- General Identity -->
    <article class="admin-card-premium">
        <header style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
            <div style="width: 42px; height: 42px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #60a5fa;">
                <i class="fas fa-globe"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1rem; color: #fff;">Identidade Global</h3>
                <p style="margin: 0; font-size: 0.7rem; color: var(--surface-40);">Nome e contato oficial.</p>
            </div>
        </header>
        
        <div class="input-group-premium" style="margin-bottom: 1.5rem;">
            <label style="display: block; font-size: 0.7rem; font-weight: 800; color: var(--surface-30); text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 1px;">Nome da Plataforma</label>
            <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'KALIYE') ?>" style="width: 100%; background: var(--surface-3); border: 1px solid var(--surface-5); border-radius: 12px; padding: 0.8rem 1rem; color: #fff; outline: none; transition: 0.3s; font-size: 0.9rem;">
        </div>

        <div class="input-group-premium">
            <label style="display: block; font-size: 0.7rem; font-weight: 800; color: var(--surface-30); text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 1px;">Email Suporte</label>
            <input type="email" name="admin_email" value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>" style="width: 100%; background: var(--surface-3); border: 1px solid var(--surface-5); border-radius: 12px; padding: 0.8rem 1rem; color: #fff; outline: none; transition: 0.3s; font-size: 0.9rem;">
        </div>
    </article>

    <!-- System Control -->
    <article class="admin-card-premium">
        <header style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
            <div style="width: 42px; height: 42px; background: rgba(247, 148, 29, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #f7941d;">
                <i class="fas fa-toggle-on"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1rem; color: #fff;">Gestão de Fluxos</h3>
                <p style="margin: 0; font-size: 0.7rem; color: var(--surface-40);">Status e disponibilidade.</p>
            </div>
        </header>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 16px; margin-bottom: 1rem; border: 1px solid var(--surface-3);">
            <div>
                <div style="font-weight: 700; font-size: 0.85rem; color: #fff;">Registos Públicos</div>
                <div style="font-size: 0.7rem; color: var(--surface-40);">Abertura para novos membros.</div>
            </div>
            <label class="toggle-switch-premium">
                <input type="checkbox" name="allow_registrations" value="1" <?= ($settings['allow_registrations'] ?? '1') == '1' ? 'checked' : '' ?>>
                <span class="slider-premium"></span>
            </label>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(244, 63, 94, 0.03); border-radius: 16px; border: 1px solid rgba(244, 63, 94, 0.1);">
            <div>
                <div style="font-weight: 700; font-size: 0.85rem; color: #f43f5e;">Modo Manutenção</div>
                <div style="font-size: 0.7rem; color: rgba(244, 63, 94, 0.4);">Bloqueio geral do sistema.</div>
            </div>
            <label class="toggle-switch-premium">
                <input type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                <span class="slider-premium danger"></span>
            </label>
        </div>
    </article>

    <!-- AI Brain -->
    <article class="admin-card-premium" style="border: 1px solid rgba(139, 92, 246, 0.2); background: radial-gradient(circle at top right, rgba(139, 92, 246, 0.05), transparent);">
        <header style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
            <div style="width: 42px; height: 42px; background: rgba(139, 92, 246, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #a78bfa;">
                <i class="fas fa-brain"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1rem; color: #fff;">Brain IA Engine</h3>
                <p style="margin: 0; font-size: 0.7rem; color: rgba(167, 139, 250, 0.4);">Processamento cognitivo.</p>
            </div>
        </header>

        <div class="input-group-premium" style="margin-bottom: 1.5rem;">
            <label style="display: block; font-size: 0.7rem; font-weight: 800; color: rgba(167, 139, 250, 0.3); text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 1px;">Modelo Ativo</label>
            <select name="ai_model" style="width: 100%; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px; padding: 0.8rem 1rem; color: #fff; outline: none; cursor: pointer; font-size: 0.9rem;">
                <option value="gemini-pro" <?= ($settings['ai_model'] ?? '') == 'gemini-pro' ? 'selected' : '' ?>>KALIYE Brain (Gemini 1.5)</option>
                <option value="gpt-4o" <?= ($settings['ai_model'] ?? '') == 'gpt-4o' ? 'selected' : '' ?>>Deep Link (GPT-4o)</option>
            </select>
        </div>

        <div class="input-group-premium">
            <label style="display: block; font-size: 0.7rem; font-weight: 800; color: rgba(167, 139, 250, 0.3); text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 1px;">Auth Key</label>
            <input type="password" name="gemini_api_key" value="<?= htmlspecialchars($settings['gemini_api_key'] ?? '') ?>" placeholder="••••••••••••" style="width: 100%; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px; padding: 0.8rem 1rem; color: #fff; outline: none; font-size: 0.9rem;">
        </div>
    </article>

    <!-- Finance -->
    <article class="admin-card-premium">
        <header style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
            <div style="width: 42px; height: 42px; background: rgba(52, 211, 153, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #34d399;">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1rem; color: #fff;">Configurações de Taxas</h3>
                <p style="margin: 0; font-size: 0.7rem; color: rgba(52, 211, 153, 0.4);">Monetização e transações.</p>
            </div>
        </header>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div class="input-group-premium">
                <label style="display: block; font-size: 0.7rem; font-weight: 800; color: var(--surface-30); text-transform: uppercase; margin-bottom: 0.75rem;">Comissão (%)</label>
                <input type="number" step="0.1" name="platform_fee_percent" value="<?= htmlspecialchars($settings['platform_fee_percent'] ?? '5.0') ?>" style="width: 100%; background: var(--surface-3); border: 1px solid var(--surface-5); border-radius: 12px; padding: 0.8rem 1rem; color: #fff; outline: none;">
            </div>
            <div class="input-group-premium">
                <label style="display: block; font-size: 0.7rem; font-weight: 800; color: var(--surface-30); text-transform: uppercase; margin-bottom: 0.75rem;">Moeda Base</label>
                <input type="text" name="currency_code" value="<?= htmlspecialchars($settings['currency_code'] ?? 'AOA') ?>" style="width: 100%; background: var(--surface-3); border: 1px solid var(--surface-5); border-radius: 12px; padding: 0.8rem 1rem; color: #fff; outline: none;">
            </div>
        </div>

        <div class="input-group-premium">
            <label style="display: block; font-size: 0.7rem; font-weight: 800; color: var(--surface-30); text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 1px;">IBAN da Plataforma</label>
            <input type="text" name="platform_iban" value="<?= htmlspecialchars($settings['platform_iban'] ?? '') ?>" placeholder="AO06..." style="width: 100%; background: var(--surface-3); border: 1px solid var(--surface-5); border-radius: 12px; padding: 0.8rem 1rem; color: #fff; outline: none; font-size: 0.85rem; font-family: monospace;">
        </div>
    </article>

    <!-- Automation Center -->
    <article class="admin-card-premium" style="grid-column: 1 / -1; border: 1px solid rgba(20, 184, 166, 0.18); background: linear-gradient(135deg, rgba(20, 184, 166, 0.05), rgba(15, 23, 42, 0.35));">
        <header style="display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 42px; height: 42px; background: rgba(20, 184, 166, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #2dd4bf;">
                    <i class="fas fa-robot"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1rem; color: #fff;">Centro de Automação Administrativa</h3>
                    <p style="margin: 0; font-size: 0.7rem; color: rgba(45, 212, 191, 0.45);">Alertas, escalonamentos, limpeza segura e tarefas recorrentes configuráveis.</p>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; justify-content: flex-end;">
                <button type="button" onclick="runAdminAutomation(true)" class="btn-admin" style="background: rgba(255,255,255,0.05); color: #d1d5db; border: 1px solid rgba(255,255,255,0.1);">
                    <i class="fas fa-vial"></i> Simular
                </button>
                <button type="button" onclick="runAdminAutomation(false)" class="btn-admin" style="background: rgba(20, 184, 166, 0.18); color: #5eead4; border: 1px solid rgba(20, 184, 166, 0.25);">
                    <i class="fas fa-play"></i> Executar Agora
                </button>
            </div>
        </header>

        <div style="display: grid; grid-template-columns: minmax(240px, 1.1fr) repeat(2, minmax(220px, 1fr)); gap: 1rem;">
            <div style="padding: 1rem; background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                    <div>
                        <div style="font-weight: 800; color: #fff; font-size: 0.9rem;">Motor Global</div>
                        <div style="color: rgba(255,255,255,0.4); font-size: 0.72rem; margin-top: 0.2rem;">Liga ou pausa todas as automações.</div>
                    </div>
                    <label class="toggle-switch-premium">
                        <input type="checkbox" name="automation_enabled" value="1" <?= ($settings['automation_enabled'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <span class="slider-premium"></span>
                    </label>
                </div>
                <div style="margin-top: 1rem; color: rgba(255,255,255,0.35); font-size: 0.7rem; line-height: 1.5;">
                    Última execução: <?= htmlspecialchars($settings['automation_last_run_at'] ?? 'ainda não executada') ?>
                </div>
            </div>

            <?php
            $automation_rules = [
                ['automation_kyc_reminders', 'automation_kyc_hours', 'KYC pendente', 'Alertar admins sobre verificações antigas.', '24'],
                ['automation_mentor_reminders', 'automation_mentor_hours', 'Mentores pendentes', 'Escalonar candidaturas sem resposta.', '48'],
                ['automation_project_reminders', 'automation_project_hours', 'Projetos por moderar', 'Alertar quando a fila de publicação envelhece.', '24'],
                ['automation_progress_reminders', 'automation_progress_hours', 'Relatórios de progresso', 'Acompanhar validações de milestones.', '24'],
                ['automation_investment_reminders', 'automation_investment_hours', 'Investimentos pendentes', 'Priorizar comprovativos por validar.', '12'],
                ['automation_support_escalation', 'automation_support_hours', 'Suporte prioritário', 'Alertar mensagens não lidas fora do SLA.', '6'],
                ['automation_dormant_user_alerts', 'automation_dormant_user_days', 'Utilizadores inativos', 'Alertar contas paradas para reativacao ou revisao.', '30'],
            ];
            foreach ($automation_rules as $rule):
            ?>
            <div style="padding: 1rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; margin-bottom: 1rem;">
                    <div>
                        <div style="font-weight: 800; color: #fff; font-size: 0.85rem;"><?= htmlspecialchars($rule[2]) ?></div>
                        <div style="color: rgba(255,255,255,0.38); font-size: 0.68rem; margin-top: 0.25rem; line-height: 1.4;"><?= htmlspecialchars($rule[3]) ?></div>
                    </div>
                    <label class="toggle-switch-premium">
                        <input type="checkbox" name="<?= $rule[0] ?>" value="1" <?= ($settings[$rule[0]] ?? '1') == '1' ? 'checked' : '' ?>>
                        <span class="slider-premium"></span>
                    </label>
                </div>
                <label style="display: block; font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.32); text-transform: uppercase; margin-bottom: 0.5rem;">SLA em horas</label>
                <input type="number" min="1" max="720" name="<?= $rule[1] ?>" value="<?= htmlspecialchars($settings[$rule[1]] ?? $rule[4]) ?>" style="width: 100%; background: rgba(0,0,0,0.18); border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; padding: 0.75rem 0.9rem; color: #fff; outline: none;">
            </div>
            <?php endforeach; ?>

            <div style="padding: 1rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; margin-bottom: 1rem;">
                    <div>
                        <div style="font-weight: 800; color: #fff; font-size: 0.85rem;">Limpeza de notificações</div>
                        <div style="color: rgba(255,255,255,0.38); font-size: 0.68rem; margin-top: 0.25rem; line-height: 1.4;">Marca notificações antigas como lidas sem apagar histórico.</div>
                    </div>
                    <label class="toggle-switch-premium">
                        <input type="checkbox" name="automation_archive_notifications" value="1" <?= ($settings['automation_archive_notifications'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <span class="slider-premium"></span>
                    </label>
                </div>
                <label style="display: block; font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.32); text-transform: uppercase; margin-bottom: 0.5rem;">Arquivar após dias</label>
                <input type="number" min="7" max="3650" name="automation_archive_notifications_days" value="<?= htmlspecialchars($settings['automation_archive_notifications_days'] ?? '90') ?>" style="width: 100%; background: rgba(0,0,0,0.18); border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; padding: 0.75rem 0.9rem; color: #fff; outline: none;">
            </div>

            <?php
            $maintenance_rules = [
                ['automation_expire_otp_codes', 'Expirar OTP antigos', 'Fecha codigos vencidos para impedir reutilizacao.'],
                ['automation_expire_mentorship_slots', 'Fechar horarios vencidos', 'Move horarios antigos de mentoria para expirado.'],
                ['automation_cleanup_history', 'Limpar historico tecnico', 'Remove eventos antigos da automacao apos o prazo.'],
            ];
            foreach ($maintenance_rules as $rule):
            ?>
            <div style="padding: 1rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start;">
                    <div>
                        <div style="font-weight: 800; color: #fff; font-size: 0.85rem;"><?= htmlspecialchars($rule[1]) ?></div>
                        <div style="color: rgba(255,255,255,0.38); font-size: 0.68rem; margin-top: 0.25rem; line-height: 1.4;"><?= htmlspecialchars($rule[2]) ?></div>
                    </div>
                    <label class="toggle-switch-premium">
                        <input type="checkbox" name="<?= $rule[0] ?>" value="1" <?= ($settings[$rule[0]] ?? '1') == '1' ? 'checked' : '' ?>>
                        <span class="slider-premium"></span>
                    </label>
                </div>
                <?php if ($rule[0] === 'automation_cleanup_history'): ?>
                    <label style="display: block; font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.32); text-transform: uppercase; margin: 1rem 0 0.5rem;">Guardar por dias</label>
                    <input type="number" min="30" max="3650" name="automation_history_days" value="<?= htmlspecialchars($settings['automation_history_days'] ?? '180') ?>" style="width: 100%; background: rgba(0,0,0,0.18); border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; padding: 0.75rem 0.9rem; color: #fff; outline: none;">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </article>
</div>

<style>
.toggle-switch-premium {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}
.toggle-switch-premium input { opacity: 0; width: 0; height: 0; }
.slider-premium {
    position: absolute; cursor: pointer; inset: 0;
    background-color: var(--surface-10);
    transition: .4s; border-radius: 24px;
}
.slider-premium:before {
    position: absolute; content: ""; height: 18px; width: 18px;
    left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%;
}
input:checked + .slider-premium { background-color: #34d399; }
input:checked + .slider-premium.danger { background-color: #f43f5e; }
input:focus + .slider-premium { box-shadow: 0 0 1px #34d399; }
input:checked + .slider-premium:before { transform: translateX(20px); }
</style>


