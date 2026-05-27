<?php

?>
<div class="cascade-card" style="background: rgba(30, 41, 59, 0.6); border: 1px solid var(--surface-10); border-radius: 20px; padding: 1.5rem; text-align: center; transition: all 0.3s; position: relative; overflow: hidden; opacity: 1 !important; visibility: visible !important;">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, #f7941d, #e07b0e);"></div>
    
    <a href="../social/profile.php?user_id=<?php echo $student['user_id']; ?>" style="text-decoration: none;">
        <div class="profile-pic-container" style="width: 70px; height: 70px; border-radius: 20px; border: 2px solid rgba(247, 148, 29, 0.3); margin: 0 auto 1rem; overflow: hidden; background: rgba(0,0,0,0.2);">
            <?php if($student['profile_pic'] && $student['profile_pic'] != 'default_profile.png'): ?>
                <img src="<?php echo $base_url . htmlspecialchars($student['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--surface-20); font-size: 1.5rem;">
                    <i class="fas fa-user-graduate"></i>
                </div>
            <?php endif; ?>
        </div>
        <h4 style="margin: 0; font-size: 0.95rem; color: white; font-weight: 700;"><?php echo htmlspecialchars($student['full_name']); ?></h4>
    </a>
    
    <div style="margin: 0.5rem 0 1.25rem 0;">
        <span style="font-size: 0.7rem; color: var(--surface-40); text-transform: uppercase; letter-spacing: 0.5px;">
            <?php 
                if($student['user_type'] == 'high_student') echo 'Ensino Médio';
                elseif($student['user_type'] == 'sec_student') echo 'Secundário';
                elseif($student['user_type'] == 'univ_student') echo 'Universitário';
                else echo 'Estudante';
            ?>
        </span>
        <?php $stud_spec = $student['specialty'] ?? ($student['field_of_study'] ?? ''); ?>
        <?php if($stud_spec): ?>
            <p style="color: #f7941d; font-size: 0.75rem; margin: 4px 0 0 0; font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                <i class="fas fa-book"></i> <?php echo htmlspecialchars($stud_spec); ?>
            </p>
        <?php endif; ?>
    </div>
    
    <?php 
        $stud_photo = ($student['profile_pic'] && $student['profile_pic'] != 'default_profile.png') ? $base_url . $student['profile_pic'] : '';
    ?>
    <button onclick="openOfferMentorship(<?php echo $student['user_id']; ?>, '<?php echo addslashes($student['full_name']); ?>', '<?php echo $stud_photo; ?>', '<?php echo addslashes($stud_spec); ?>')" 
            class="contact-link" style="width: 100%; justify-content: center; font-size: 0.75rem;">
        Mentorizar
    </button>
</div>
