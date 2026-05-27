<?php
/**
 * Component: Booking Modal
 * Expected Variables: $user_id (int), $db (PDO)
 */
?>
<div id="bookingModal" class="auth-container" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 100000; justify-content: center; align-items: center;">
    <div class="login-card glass" style="max-width: 450px; width: 100%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3>Reservar Mentoria</h3>
            <button onclick="document.getElementById('bookingModal').style.display='none'" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5rem;">&times;</button>
        </div>
        
        <form action="../servicos/mentorship/book_session.php" method="POST">
            <input type="hidden" name="mentor_id" value="<?php echo $user_id; ?>">
            
            <div class="input-group">
                <label>Data da Sessão</label>
                <input type="date" name="session_date" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; padding: 0.75rem;">
            </div>

            <div class="input-group">
                <label>Horário</label>
                <input type="time" name="session_time" required style="width: 100%; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; padding: 0.75rem;">
            </div>

            <div style="background: rgba(247, 148, 29, 0.1); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid var(--accent-orange);">
                <h5 style="color: var(--accent-orange); margin-bottom: 0.5rem;"><i class="fas fa-clock"></i> Disponibilidade do Mentor:</h5>
                <ul style="font-size: 0.8rem; color: var(--text-secondary); padding-left: 1.2rem;">
                    <?php
                    $avail_stmt = $db->prepare("SELECT * FROM mentor_availability WHERE mentor_id = ? ORDER BY day_of_week ASC");
                    $avail_stmt->execute([$user_id]);
                    $avails = $avail_stmt->fetchAll();
                    $day_names = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                    if(count($avails) > 0) {
                        foreach($avails as $av) {
                            echo "<li>{$day_names[$av['day_of_week']]}: ".substr($av['start_time'], 0, 5)." - ".substr($av['end_time'], 0, 5)."</li>";
                        }
                    } else {
                        echo "<li>Nenhum horário definido pelo mentor.</li>";
                    }
                    ?>
                </ul>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%;">Solicitar Reserva</button>
        </form>
    </div>
</div>

