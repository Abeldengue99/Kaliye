<?php
/**
 * Component: Investor Dashboard Modals
 * Project Details modal for the investor dashboard.
 * The shared invest modal is loaded once by inclusoes/rodape.php.
 */
?>

<!-- Project Details Modal -->
<div id="detailsModal" class="auth-container" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 10000; justify-content: center; align-items: center; overflow-y: auto;">
    <div class="glass" style="max-width: 800px; width: 95%; max-height: 90vh; overflow-y: auto; position: relative; margin: 2rem 0; border-radius: 24px;">
        <button onclick="document.getElementById('detailsModal').style.display='none'" style="position: absolute; top: 1.5rem; right: 1.5rem; background: var(--surface-10); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; z-index: 20;">&times;</button>
        
        <div id="detailsContent" style="padding: 2.5rem;">
            <!-- Content Injected via JS -->
        </div>
    </div>
</div>

