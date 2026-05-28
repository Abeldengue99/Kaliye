<?php
// profile_sidebar.php - Sidebar do perfil do usuário
/**
 * @var array $user
 * @var bool $is_own_profile
 * @var int $user_id
 */
?>
<aside class="profile-sidebar">

    <!-- Informação de contacto -->
    <div class="sidebar-card">
        <div class="sidebar-card-title"><i class="fas fa-address-card"></i> Informação</div>
        <?php if ($is_own_profile || (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin')): ?>
            <div class="sidebar-info-row">
                <div class="sidebar-info-icon"><i class="fas fa-envelope"></i></div>
                <span style="font-size:0.85rem; word-break:break-all;"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <?php if(!empty($user['phone'])): ?>
            <div class="sidebar-info-row">
                <div class="sidebar-info-icon"><i class="fas fa-phone"></i></div>
                <span style="font-size:0.85rem;"><?php echo htmlspecialchars($user['phone']); ?></span>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="sidebar-info-row" style="color:var(--surface-40); font-size:0.82rem; font-style:italic;">
                <i class="fas fa-lock" style="color:var(--surface-30);"></i>
                Contacto disponível apenas para conexões aceites.
            </div>
        <?php endif; ?>
    </div>

    <!-- Verificação -->
    <div class="sidebar-card">
        <div class="sidebar-card-title"><i class="fas fa-shield-halved"></i> Verificação</div>
        <?php
            $v_status = $user['verification_status'] ?? 'unsubmitted';
            $v_map = [
                'verified'    => ['color'=>'#10b981','bg'=>'rgba(16,185,129,0.1)','text'=>'Identidade Verificada','icon'=>'fa-check-circle'],
                'pending'     => ['color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.1)','text'=>'Em Análise','icon'=>'fa-clock'],
                'rejected'    => ['color'=>'#ef4444','bg'=>'rgba(239,68,68,0.1)','text'=>'Rejeitado','icon'=>'fa-times-circle'],
                'unsubmitted' => ['color'=>'#64748b','bg'=>'rgba(100,116,139,0.1)','text'=>'Não Verificado','icon'=>'fa-shield-alt']
            ];
            $v = $v_map[$v_status] ?? $v_map['unsubmitted'];
        ?>
        <div class="verification-badge" style="background:<?php echo $v['bg']; ?>; color:<?php echo $v['color']; ?>;">
            <i class="fas <?php echo $v['icon']; ?>"></i>
            <?php echo $v['text']; ?>
        </div>
        <?php if($is_own_profile && ($v_status == 'unsubmitted' || $v_status == 'rejected')): ?>
            <button onclick="openKYCModal()"
                    style="width:100%; margin-top:1rem; padding:0.6rem; background:rgba(59,130,246,0.1); border:1px solid #3b82f6; color:#3b82f6; border-radius:10px; cursor:pointer; font-size:0.8rem; font-weight:700;">
                <i class="fas fa-arrow-right"></i> Verificar Agora
            </button>
        <?php endif; ?>
    </div>

    <!-- Mentor Status (condicional) -->
    <?php if($user['user_type'] == 'mentor' || (($user['verification_status'] ?? '') == 'verified' && $user['user_type'] !== 'investor')): ?>
    <div class="sidebar-card" style="border-color: rgba(245,177,5,0.2);">
        <div class="sidebar-card-title" style="color:#f5b105;"><i class="fas fa-medal"></i> Status de Mentor</div>
        <?php
            $m_status = $user['mentorship_status'] ?? 'unsubmitted';
            $m_map = [
                'approved'    => ['color'=>'#f5b105','bg'=>'rgba(245,177,5,0.1)','text'=>'Mentor Oficial KALIYE','icon'=>'fa-award'],
                'pending'     => ['color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.1)','text'=>'Candidatura em Análise','icon'=>'fa-hourglass-half'],
                'rejected'    => ['color'=>'#ef4444','bg'=>'rgba(239,68,68,0.1)','text'=>'Requer Ajustes','icon'=>'fa-exclamation-triangle'],
                'unsubmitted' => ['color'=>'#94a3b8','bg'=>'rgba(148,163,184,0.1)','text'=>'Candidatura Disponível','icon'=>'fa-user-graduate']
            ];
            $m = $m_map[$m_status] ?? $m_map['unsubmitted'];
        ?>
        <div class="verification-badge" style="background:<?php echo $m['bg']; ?>; color:<?php echo $m['color']; ?>;">
            <i class="fas <?php echo $m['icon']; ?>"></i> <?php echo $m['text']; ?>
        </div>

        <!-- Reconhecimento da Comunidade (Pontos de Avaliação) -->
        <div style="margin-top: 1rem; padding: 1rem; background: rgba(247, 148, 29, 0.05); border: 1px dashed rgba(247, 148, 29, 0.3); border-radius: 12px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.4rem;">Reconhecimento</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent-orange); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-star" style="font-size: 1.1rem;"></i>
                <?php echo $user['avaliacao'] ?? 0; ?>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.2rem;">Pontos de Avaliação</div>
        </div>

        <?php if($is_own_profile): ?>
            <?php if($m_status == 'unsubmitted' || $m_status == 'rejected'): ?>
                <button onclick="openMentorAppModal()"
                        style="width:100%; margin-top:1rem; padding:0.6rem; background:#f5b105; border:none; color:#000; border-radius:10px; cursor:pointer; font-weight:800; font-size:0.8rem; transition: 0.3s;">
                    <i class="fas fa-paper-plane"></i> Submeter Candidatura
                </button>
            <?php elseif($m_status == 'pending'): ?>
                <button disabled
                        style="width:100%; margin-top:1rem; padding:0.6rem; background:rgba(245,158,11,0.1); border:1px solid #f59e0b; color:#f59e0b; border-radius:10px; cursor:default; font-weight:800; font-size:0.8rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-hourglass-half"></i> Candidatura Pendente
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</aside>
