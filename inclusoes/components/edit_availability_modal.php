<?php
/**
 * Component: Edit Availability Modal
 */
?>
<div id="editAvailabilityModal" class="auth-container" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 100000; justify-content: center; align-items: center;">
    <div class="login-card glass" style="max-width: 500px; width: 100%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3>Definir Disponibilidade</h3>
            <button onclick="document.getElementById('editAvailabilityModal').style.display='none'" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5rem;">&times;</button>
        </div>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem;">Defina quando os alunos podem solicitar sessões consigo.</p>
        <form action="../servicos/mentorship/update_availability.php" method="POST">
            <div style="max-height: 400px; overflow-y: auto; padding-right: 0.5rem;">
                <?php 
                $days = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
                foreach($days as $index => $day):
                    $day_indexed = ($index + 1) % 7; 
                ?>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; background: var(--surface-3); padding: 0.8rem; border-radius: 8px;">
                        <span style="flex: 1; font-weight: 600; font-size: 0.9rem;"><?php echo $day; ?></span>
                        <input type="time" name="start_<?php echo $day_indexed; ?>" style="background: var(--input-bg); border: 1px solid var(--glass-border); color: white; padding: 0.3rem; border-radius: 4px;">
                        <span>até</span>
                        <input type="time" name="end_<?php echo $day_indexed; ?>" style="background: var(--input-bg); border: 1px solid var(--glass-border); color: white; padding: 0.3rem; border-radius: 4px;">
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 1.5rem;">Guardar Disponibilidade</button>
        </form>
    </div>
</div>

