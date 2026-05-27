<?php
/**
 * Component: Mentor List Item — Premium Edition
 */
?>
<div class="person-card" data-aos="fade-up">
    <a href="profile.php?user_id=<?php echo $mentor['user_id']; ?>" class="person-details">
        <div class="person-pic">
            <?php if(isset($mentor['profile_pic']) && $mentor['profile_pic'] && $mentor['profile_pic'] != 'default_profile.png'): ?>
                <img src="<?php echo $base_url . htmlspecialchars($mentor['profile_pic']); ?>" alt="<?php echo htmlspecialchars($mentor['full_name']); ?>">
            <?php else: ?>
                <i class="fas fa-user-tie"></i>
            <?php endif; ?>
        </div>
        <div class="person-info">
            <h4>
                <?php echo htmlspecialchars($mentor['full_name']); ?>
                <?php if(isset($mentor['badge_verified']) && $mentor['badge_verified']): ?>
                    <i class="fas fa-check-circle verified-badge" style="color: #3b82f6; font-size: 0.8rem; margin-left: 0.4rem;"></i>
                <?php endif; ?>
            </h4>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 4px;">
                <span class="person-role">
                    <?php echo $mentor['user_type'] == 'mentor' ? 'Mentor Pro' : 'Mentor Académico'; ?>
                </span>
                <?php if (!empty($mentor['location'])): ?>
                    <span style="font-size: 0.65rem; color: var(--surface-30);">| <?php echo htmlspecialchars($mentor['location']); ?></span>
                <?php endif; ?>
            </div>
            <?php 
                $mentor_spec = $mentor['specialty'] ?? ($mentor['field_of_study'] ?? '');
                if ($mentor_spec): 
            ?>
                <p style="font-size: 0.72rem; color: #f7941d; margin-top: 6px; font-weight: 600; font-family: 'Inter', sans-serif;">
                    <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($mentor_spec); ?>
                </p>
            <?php endif; ?>
        </div>
    </a>
    
    <?php if($_SESSION['user_type'] != 'investor' && $_SESSION['user_type'] != 'mentor'): ?>
        <?php 
            $photo_url = ($mentor['profile_pic'] && $mentor['profile_pic'] != 'default_profile.png') ? $base_url . $mentor['profile_pic'] : '';
        ?>
        <button onclick="openMentorSlotsModal(<?php echo $mentor['user_id']; ?>, '<?php echo addslashes($mentor['full_name']); ?>', '<?php echo $photo_url; ?>', '<?php echo addslashes($mentor_spec); ?>')" class="contact-link">
            Agendar
        </button>
    <?php endif; ?>
</div>
