<?php
/**
 * Component: Masterclass Modal
 */
?>
<div id="masterclassModal" class="auth-container" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 100000; justify-content: center; align-items: center;">
    <div class="login-card glass" style="max-width: 450px; width: 100%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h3><i class="fas fa-users" style="color: var(--accent-gold);"></i> Criar Masterclass (Foco)</h3>
            <button onclick="document.getElementById('masterclassModal').style.display='none'" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5rem;">&times;</button>
        </div>
        <form action="../servicos/mentorship/create_masterclass.php" method="POST">
            <div class="input-group">
                <label>Data</label>
                <input type="date" name="session_date" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; padding: 0.75rem;">
            </div>
            <div class="input-group">
                <label>Hora</label>
                <input type="time" name="session_time" required style="width: 100%; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; padding: 0.75rem;">
            </div>
            <div class="input-group">
                <label>Capacidade (Máx 5)</label>
                <input type="number" name="max_capacity" value="5" min="2" max="5" style="width: 100%; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; padding: 0.75rem;">
            </div>
            <div class="input-group">
                <label>Preço Sugerido (Kz por pessoa)</label>
                <input type="number" name="price" value="0.00" step="0.01" style="width: 100%; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: white; padding: 0.75rem;">
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; background: var(--accent-gold); border: none; color: black; font-weight: bold;">Lançar Masterclass</button>
        </form>
    </div>
</div>

