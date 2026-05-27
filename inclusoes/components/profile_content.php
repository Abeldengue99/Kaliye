<?php
// profile_content.php - Coluna de conteúdo principal do perfil
/**
 * @var array $user
 * @var bool $is_own_profile
 * @var array $expertises
 * @var array $skills
 * @var string $profile_bio
 */
?>
<main class="profile-content-col">

    <!-- Biografia e Bio Especializada -->
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-quote-left"></i> Sobre</h3>
            <?php if ($is_own_profile): ?>
                <button onclick="openMyProfileEdit()" class="btn-edit-inline"><i class="fas fa-edit"></i></button>
            <?php endif; ?>
        </div>
        <div class="bio-text" style="color:rgba(255,255,255,0.85); line-height:1.75; font-size:1rem; white-space: pre-wrap;">
            <?php echo !empty($profile_bio) ? htmlspecialchars($profile_bio) : 'Este utilizador ainda não adicionou uma biografia à sua jornada na KALIYE.'; ?>
        </div>
    </div>

    <!-- Áreas de Especialidade (Main Expertises) -->
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-brain"></i> Áreas de Foco</h3>
            <?php if ($is_own_profile): ?>
                <button onclick="document.getElementById('profileExpertiseModal').style.display='flex'" class="btn-edit-inline"><i class="fas fa-plus"></i></button>
            <?php endif; ?>
        </div>
        <div class="expertise-grid">
            <?php if(!empty($expertises)): ?>
                <?php foreach($expertises as $exp): ?>
                <div class="expertise-item">
                    <div class="exp-icon"><i class="fas fa-star"></i></div>
                    <div class="exp-info">
                        <strong><?php echo htmlspecialchars($exp['title']); ?></strong>
                        <p><?php echo htmlspecialchars($exp['description'] ?? ''); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align:center; padding: 2rem; color:var(--surface-30); font-style:italic;">Nenhuma área de especialidade adicionada.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Skills e Competências -->
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-code"></i> Skills & Competências</h3>
        </div>
        <div class="skills-tag-cloud">
            <?php if(!empty($skills)): ?>
                <?php foreach($skills as $skill): ?>
                    <span class="skill-tag">
                        #<?php echo htmlspecialchars($skill['skill_name']); ?>
                        <?php if($is_own_profile): ?>
                            <i class="fas fa-times remove-skill" onclick="removeSkill(<?php echo $skill['user_skill_id']; ?>)"></i>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="width:100%; text-align:center; padding: 1.5rem; color:var(--surface-20); font-size:0.85rem;">Nenhuma competência tagueada.</div>
            <?php endif; ?>
        </div>
    </div>

</main>
