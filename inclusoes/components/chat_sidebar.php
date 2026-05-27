<?php
/**
 * Component: Elite Chat Sidebar (Aksanti Social Hub)
 */
?>
<aside class="chat-sidebar-elite">
    <div class="sidebar-header">
        <div class="chat-sidebar-top">
            <!-- Título mestre de Identidade de Chat -->
            <div>
                <span class="chat-sidebar-kicker">Centro de Conversas</span>
                <h3 class="chat-sidebar-title">Mensagens</h3>
            </div>
            
            <!-- Botão restrito para mentores. Aciona o Ajax no background sem alterar o reload. -->
            <?php if ($_SESSION['user_type'] === 'mentor'): ?>
                <button onclick="createMentorGroup()" class="mentor-room-btn">
                    <i class="fas fa-plus"></i> Sala VIP
                </button>
            <?php endif; ?>
        </div>
        
        <div class="chat-search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" id="chatSearchInput" onkeyup="filterChats()" placeholder="Pesquisar rede...">
        </div>
        <div class="chat-sidebar-meta">
            <span><i class="fas fa-user-friends"></i> <?php echo count($conversations); ?> diretas</span>
            <span><i class="fas fa-layer-group"></i> <?php echo count($user_groups) + count($mentor_groups); ?> salas</span>
        </div>
    </div>
    
    <div class="contacts-list-elite">
        
        <!-- ============================================== -->
        <!-- AS NOVAS SALAS VIP DE MENTORIA (Inovação Premium) -->
        <!-- Esta aba carrega as micro-bolhas dos grupos do mentor interconectados. -->
        <?php if (!empty($mentor_groups)): ?>
            <div class="chat-list-section chat-list-section--vip">
                <h4><i class="fas fa-crown"></i> Salas de Mentoria VIP</h4>
            </div>
            <?php foreach ($mentor_groups as $mgroup): ?>
                <div class="contact-item-elite group-item mentor-group" onclick="loadMentorGroupChat(<?php echo $mgroup['id']; ?>, '<?php echo addslashes($mgroup['name']); ?>', <?php echo $mgroup['mentor_id']; ?>)">
                    <div class="contact-avatar-elite" style="background: linear-gradient(135deg, #059669, #10b981); display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(16,185,129,0.4);">
                        <i class="fas fa-gem" style="color: #fff; font-size: 1.2rem;"></i>
                    </div>
                    <div class="contact-info-elite" style="flex:1;">
                        <!-- Hierarquia Visual: Título do Grupo e Mentor em Responsabilidade -->
                        <h4><?php echo htmlspecialchars($mgroup['name']); ?></h4>
                        <p style="color: #10b981; font-size: 0.70rem;"><?php echo htmlspecialchars($mgroup['mentor_name']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <!-- ============================================== -->

        <!-- Grupos Tradicionais de Projectos -->
        <?php if (!empty($user_groups)): ?>
            <div class="chat-list-section chat-list-section--group">
                <h4>Sinergias de Grupo</h4>
            </div>
            <?php foreach ($user_groups as $group): ?>
                <div class="contact-item-elite group-item" onclick="loadGroupChat(<?php echo $group['group_id']; ?>, '<?php echo addslashes($group['group_name']); ?>')">
                    <div class="contact-avatar-elite" style="background: linear-gradient(135deg, #f7941d, #ffb347); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-users" style="color: #fff; font-size: 1.2rem;"></i>
                    </div>
                    <div class="contact-info-elite">
                        <h4><?php echo htmlspecialchars($group['group_name']); ?></h4>
                        <p><?php echo $group['member_count']; ?> membros ativos</p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Conversas Diretas Elite -->
        <div class="chat-list-section">
            <h4>Rede Direta</h4>
        </div>
        
        <?php if (empty($conversations) && empty($user_groups)): ?>
            <div class="chat-sidebar-empty">
                <i class="fas fa-comment-slash" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <p style="font-size: 0.85rem;">Nenhuma conexão <br> iniciada ainda.</p>
            </div>
        <?php else: ?>
            <?php foreach ($conversations as $conv): 
                $contact_id = $conv['contact_id'];
                $c_stmt = $db->prepare("SELECT full_name, user_type, profile_pic, mentorship_status, last_activity FROM users WHERE user_id = :cid");
                $c_stmt->execute([':cid' => $contact_id]);
                $contact = $c_stmt->fetch();
                
                $u_stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = :cid AND receiver_id = :uid AND CAST(is_read AS INTEGER) = 0");
                $u_stmt->execute([':cid' => $contact_id, ':uid' => $current_user_id]);
                $unread = $u_stmt->fetchColumn();
                
                $avatar_path = getUserAvatarUrl($contact['user_type'] ?? 'student', $contact['mentorship_status'] ?? 'unsubmitted', $contact['profile_pic'] ?? '');
                $pfp = (strpos($avatar_path, 'http') === 0) ? $avatar_path : $base_url . $avatar_path;
                $presence = class_exists('ChatSecurity') ? ChatSecurity::onlineMeta($contact['last_activity'] ?? null) : ['is_online' => false, 'label' => 'Offline'];
            ?>
                <div class="contact-item-elite direct-item" id="contact-item-<?php echo $contact_id; ?>" onclick="loadChat(<?php echo $contact_id; ?>, '<?php echo addslashes($contact['full_name']); ?>', '<?php echo addslashes($avatar_path); ?>')">
                    <div class="contact-avatar-elite" style="position:relative;">
                         <img src="<?php echo $pfp; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                         <span title="<?php echo htmlspecialchars($presence['label']); ?>" style="position:absolute; right:-2px; bottom:-2px; width:10px; height:10px; border-radius:50%; background:<?php echo $presence['is_online'] ? '#10b981' : 'rgba(148,163,184,0.75)'; ?>; border:2px solid #101827;"></span>
                    </div>
                    <div class="contact-info-elite" style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h4><?php echo htmlspecialchars($contact['full_name']); ?></h4>
                            <?php if($unread > 0): ?>
                                <span style="background: #f7941d; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 800; color: #fff;"><?php echo $unread; ?></span>
                            <?php endif; ?>
                        </div>
                        <p><?php echo htmlspecialchars($presence['label']); ?> • <?php echo $user_type_labels[$contact['user_type']] ?? $contact['user_type']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</aside>
