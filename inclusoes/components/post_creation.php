<?php
// Mentores nao podem publicar projectos; devem atuar apenas na orientação e validação.
$can_post = !in_array($user_data['user_type'], ['mentor', 'investor'])
    && (($user_data['mentorship_status'] ?? 'unsubmitted') !== 'approved');
if ($can_post): 
?>
<div class="glass" style="padding: 1.5rem; border-radius: 20px; margin-bottom: 2rem;">
    <div style="display: flex; gap: 1rem; align-items: flex-start;">
        <div style="width: 45px; height: 45px; border-radius: 50%; background: var(--secondary-bg); flex-shrink: 0; display: flex; align-items: center; justify-content: center; border: 1px solid var(--accent-orange); overflow: hidden;">
            <?php
                $post_avatar = getUserAvatarUrl($user_data['user_type'] ?? 'student', $user_data['mentorship_status'] ?? 'unsubmitted', $user_data['profile_pic'] ?? '');
                $post_avatar_src = (strpos($post_avatar, 'http') === 0) ? $post_avatar : $base_url . $post_avatar;
            ?>
            <img src="<?php echo htmlspecialchars($post_avatar_src); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <i class="fas fa-user" style="font-size: 1.2rem; color: var(--text-secondary); display:none;"></i>
        </div>
        <div style="flex-grow: 1;">
            <?php if($is_verified || isAdmin()): ?>
                <button onclick="if(enforceKYC()) openPostModal()" style="width: 100%; padding: 1.2rem; background: rgba(15,23,42,0.8); border: 1px solid var(--glass-border); border-radius: 40px; color: var(--text-secondary); font-size: 0.95rem; text-align: left; cursor: pointer; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; align-items: center; justify-content: space-between;" onmouseover="this.style.background='rgba(30, 41, 59, 0.9)'; this.style.borderColor='rgba(247,148,29,0.3)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.2)';" onmouseout="this.style.background='rgba(15,23,42,0.8)'; this.style.borderColor='var(--glass-border)'; this.style.boxShadow='none';">
                    <span><?php echo __('share_idea'); ?>, <?php echo explode(' ', $user_data['full_name'])[0]; ?>?</span>
                    <i class="fas fa-edit" style="color: var(--accent-orange); opacity: 0.7;"></i>
                </button>
            <?php else: ?>
                <button onclick="enforceKYC()" style="width: 100%; padding: 1.2rem; background: rgba(15,23,42,0.8); border: 1px dashed var(--danger); border-radius: 40px; color: var(--text-secondary); text-align: left; cursor: pointer; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                    <i class="fas fa-lock" style="color: var(--danger);"></i> Verifique a sua conta para publicar e desfrutar do ecossistema.
                </button>
            <?php endif; ?>
            
            <div style="display: flex; gap: 0.8rem; margin-top: 1.4rem; font-size: 0.82rem; font-weight: 700; flex-wrap: wrap; text-transform: uppercase; letter-spacing: 0.5px;">
                <span onclick="if(enforceKYC()) openPostModal()" style="cursor: pointer; color: #4ade80; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.2rem; background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.2); border-radius: 100px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.background='rgba(74, 222, 128, 0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.background='rgba(74, 222, 128, 0.1)';">
                    <i class="fas fa-image"></i> <?php echo __('photo'); ?>
                </span>
                
                <span onclick="if(enforceKYC()) openPostModal()" style="cursor: pointer; color: #60a5fa; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.2rem; background: rgba(96, 165, 250, 0.1); border: 1px solid rgba(96, 165, 250, 0.2); border-radius: 100px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.background='rgba(96, 165, 250, 0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.background='rgba(96, 165, 250, 0.1)';">
                    <i class="fas fa-video"></i> <?php echo __('video'); ?>
                </span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
