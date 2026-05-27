<?php
/**
 * Component: Investor List Item — Premium Edition
 */
?>
<div class="person-card" data-aos="fade-up">
    <a href="profile.php?user_id=<?php echo $investor['user_id']; ?>" class="person-details">
        <div class="person-pic" style="border-radius: 50%; border-color: rgba(252, 211, 77, 0.2);">
            <?php if(isset($investor['profile_pic']) && $investor['profile_pic'] && $investor['profile_pic'] != 'default_profile.png'): ?>
                <img src="<?php echo $base_url . htmlspecialchars($investor['profile_pic']); ?>" alt="<?php echo htmlspecialchars($investor['full_name']); ?>">
            <?php else: ?>
                <i class="fas fa-hand-holding-usd" style="color: #fcd34d;"></i>
            <?php endif; ?>
        </div>
        <div class="person-info">
            <h4>
                <?php echo htmlspecialchars($investor['full_name']); ?>
                <?php if(isset($investor['badge_verified']) && $investor['badge_verified']): ?>
                    <i class="fas fa-check-circle verified-badge" style="color: #fcd34d; font-size: 0.8rem; margin-left: 0.4rem;"></i>
                <?php endif; ?>
            </h4>
            <p class="person-role">Investidor Estratégico</p>
            <?php if (!empty($investor['location'])): ?>
                <p style="font-size: 0.65rem; color: var(--surface-30); margin-top: 2px;">
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($investor['location']); ?>
                </p>
            <?php endif; ?>
        </div>
    </a>
    
    <a href="messages.php?start=<?php echo $investor['user_id']; ?>" class="contact-link" style="color: #fcd34d; border-color: rgba(252, 211, 77, 0.2); background: rgba(252, 211, 77, 0.05);">
        Contactar
    </a>
</div>
