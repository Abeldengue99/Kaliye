<!-- Project Details Modal: Elite Experience -->
<div id="detailsModal" class="elite-modal-overlay" style="display: none; z-index: 20000;">
    <div class="elite-modal-card" style="max-width: 650px; max-height: 90vh; overflow-y: auto;">
        <button onclick="closeProjectDetailsModal()" class="elite-modal-close">
            <i class="fas fa-times"></i>
        </button>
        
        <div id="detailsContent">
            <!-- Content Injected via JS (scripts.php / index_scripts.php) -->
        </div>
    </div>
</div>

<script>
function closeProjectDetailsModal() {
    const modal = document.getElementById('detailsModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        // Reset styles on close so next open works cleanly
        setTimeout(() => {
            modal.style.cssText = 'display: none;';
        }, 300);
    }
}
</script>
