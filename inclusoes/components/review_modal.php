<?php
/**
 * Component: Review Modal
 * Expected Variables: $user_id (int)
 */
?>
<div id="reviewModal" class="auth-container" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 100000; justify-content: center; align-items: center;">
    <div class="login-card glass" style="max-width: 400px; width: 100%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3>Avaliar Mentor</h3>
            <button onclick="document.getElementById('reviewModal').style.display='none'" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5rem;">&times;</button>
        </div>
        <form action="../servicos/social/post_review.php" method="POST">
            <input type="hidden" name="mentor_id" value="<?php echo $user_id; ?>">
            <div class="input-group" style="margin-bottom: 1rem;">
                <label>Nota (1-5)</label>
                <select name="rating" style="width: 100%; padding: 0.8rem; border-radius: 8px; background: var(--input-bg); border: 1px solid var(--glass-border); color: white;">
                    <option value="5">â˜…â˜…â˜…â˜…â˜… (Excelente)</option>
                    <option value="4">â˜…â˜…â˜…â˜…â˜† (Muito Bom)</option>
                    <option value="3">â˜…â˜…â˜…â˜†â˜† (Bom)</option>
                    <option value="2">â˜…â˜…â˜†â˜†â˜† (Regular)</option>
                    <option value="1">â˜…â˜†â˜†â˜†â˜† (Fraco)</option>
                </select>
            </div>
            <div class="input-group">
                <label>Testemunho</label>
                <textarea name="comment" rows="4" placeholder="Como foi a sua experiência?" required style="width: 100%; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; padding: 0.75rem;"></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%;">Enviar Avaliação</button>
        </form>
    </div>
</div>

