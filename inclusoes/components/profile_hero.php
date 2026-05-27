<?php
// profile_hero.php - Hero cover card for user profile
/**
 * @var array $user Dados do utilizador sendo visualizado
 * @var bool $is_own_profile
 * @var array $user_type_labels
 * @var int $total_connections
 * @var int $total_projects
 * @var int $total_expertises
 * @var int $user_id
 */
?>
<?php
    $cover_avatar = getUserAvatarUrl($user['user_type'], $user['mentorship_status'] ?? 'unsubmitted', $user['profile_pic'] ?? '');
    $cover_avatar_src = (strpos($cover_avatar, 'http') === 0) ? $cover_avatar : $base_url . $cover_avatar;
?>
<div class="profile-cover-card" id="profileHero">
    <!-- Background decorativos -->
    <div class="cover-grid"></div>
    <div class="cover-bg-orb cover-orb-1"></div>
    <div class="cover-bg-orb cover-orb-2"></div>

    <!-- Conteúdo principal -->
    <div class="cover-inner">
        <!-- Avatar -->
        <div class="cover-avatar-wrap">
            <img src="<?php echo htmlspecialchars($cover_avatar_src); ?>" class="cover-avatar" alt="foto">
            <div class="avatar-online-dot" title="Online" style="animation: pulse-online 2s infinite;"></div>
        </div>

        <!-- Info do utilizador -->
        <div class="cover-user-info">
            <?php
                $display_role = $user_type_labels[$user['user_type']] ?? $user['user_type'];
                if ($user['user_type'] == 'univ_student' && ($user['mentorship_status'] ?? '') == 'approved') {
                    $display_role = 'Estudante & Mentor KALIYE';
                }
            ?>
            <div class="cover-role-badge">
                <span class="dot"></span>
                <?php echo $display_role; ?>
            </div>

            <h1 class="cover-name">
                <?php echo htmlspecialchars($user['full_name']); ?>
                <?php if(($user['badge_verified'] ?? false) === true): ?>
                    <i class="fas fa-check-circle cover-name-verified" title="Verificado Oficialmente"></i>
                <?php endif; ?>
            </h1>

            <?php 
                $info_line = "";
                if (!empty($user['organization'])) $info_line .= '<i class="fas fa-building"></i> ' . htmlspecialchars($user['organization']);
                if (!empty($user['institution'])) {
                    if ($info_line) $info_line .= " · ";
                    $info_line .= '<i class="fas fa-graduation-cap"></i> ' . htmlspecialchars($user['institution']);
                }
                
                if (empty($info_line)) {
                    $loc = !empty($user['location']) ? htmlspecialchars($user['location']) : 'Luanda';
                    $info_line = '<i class="fas fa-map-marker-alt"></i> ' . $loc . ', Angola · KALIYE';
                } else if (!empty($user['location'])) {
                    $info_line .= ' <span style="opacity: 0.8; margin-left: 5px; color: #f7941d; font-weight: 700;">| <i class="fas fa-map-marker-alt" style="margin-left:5px;"></i> ' . htmlspecialchars($user['location']) . '</span>';
                }
            ?>
            <div class="cover-org">
                <?php echo $info_line; ?>
            </div>

            <!-- Actions -->
            <div class="cover-actions">
                <?php if ($is_own_profile): ?>
                    <button onclick="openMyProfileEdit()" class="btn-cover-primary">
                        <i class="fas fa-pen"></i> Editar Perfil
                    </button>
                    <button onclick="document.getElementById('profileExpertiseModal').style.display='flex'" class="btn-cover-ghost">
                        <i class="fas fa-brain"></i> Gerir Skills
                    </button>
                <?php elseif (isAdmin()): ?>
                    <button onclick="openMyProfileEdit()" class="btn-cover-primary">
                        <i class="fas fa-pen"></i> Editar
                    </button>
                    <button onclick="toggleVerifiedBadge(<?php echo $user_id; ?>, <?php echo ($user['badge_verified'] ? 'false' : 'true'); ?>)"
                            class="btn-cover-ghost" style="border-color:<?php echo $user['badge_verified'] ? '#ef4444' : '#3b82f6'; ?>; color:<?php echo $user['badge_verified'] ? '#ef4444' : '#3b82f6'; ?>">
                        <i class="fas <?php echo $user['badge_verified'] ? 'fa-user-minus' : 'fa-user-check'; ?>"></i>
                        <?php echo $user['badge_verified'] ? 'Remover Selo' : 'Atribuir Selo'; ?>
                    </button>
                <?php else: ?>
                    <?php if (!isset($connection) || !$connection): ?>
                        <button onclick="handleConnection(<?php echo $user_id; ?>, 'request', this)" class="btn-cover-primary">
                            <i class="fas fa-user-plus"></i> Conectar
                        </button>
                    <?php elseif ($connection['status'] == 'pending'): ?>
                        <?php if ($connection['requester_id'] == $_SESSION['user_id']): ?>
                            <button class="btn-cover-ghost" disabled><i class="fas fa-clock"></i> Pedido Enviado</button>
                        <?php else: ?>
                            <button onclick="handleConnection(<?php echo $user_id; ?>, 'accept', this)" class="btn-cover-primary" style="background: linear-gradient(135deg,#10b981,#059669);">
                                <i class="fas fa-check"></i> Aceitar
                            </button>
                            <button onclick="handleConnection(<?php echo $user_id; ?>, 'reject', this)" class="btn-cover-ghost" style="border-color:#ef4444;color:#ef4444;">
                                <i class="fas fa-times"></i>
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="messages.php?start=<?php echo $user['user_id']; ?>" class="btn-cover-primary">
                            <i class="fas fa-envelope"></i> Mensagem
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats strip no inferior do cover -->
    <div class="cover-stats-strip">
        <div class="cover-stat-item">
            <div class="cover-stat-num"><?php echo $total_connections; ?></div>
            <div class="cover-stat-lbl">Conexões</div>
        </div>
        <div class="cover-stat-item">
            <div class="cover-stat-num"><?php echo $total_projects; ?></div>
            <div class="cover-stat-lbl">Projectos</div>
        </div>
        <div class="cover-stat-item">
            <div class="cover-stat-num"><?php echo $total_expertises; ?></div>
            <div class="cover-stat-lbl">Especialidades</div>
        </div>
        <div class="cover-stat-item">
            <div class="cover-stat-num" style="color: var(--accent-orange);"><?php echo $user['avaliacao'] ?? 0; ?></div>
            <div class="cover-stat-lbl">Avaliação</div>
        </div>
        <div class="cover-stat-item">
            <div class="cover-stat-num"><?php echo date('Y', strtotime($user['created_at'])); ?></div>
            <div class="cover-stat-lbl">Membro desde</div>
        </div>
    </div>
</div>
