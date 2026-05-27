<!-- Likes Modal: Elite Style -->
<div id="likesModal" class="elite-modal-overlay" style="display: none; align-items: center; justify-content: center;">
    <div class="elite-modal-card" style="max-width: 360px; padding: 2.5rem;">
        <div class="modal-header-elite" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--surface-5); padding-bottom: 1rem;">
            <h4 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #fff;">INTERAÇÕES</h4>
            <button onclick="closeLikesModal()" class="elite-modal-close" style="position: static; width: 32px; height: 32px;">&times;</button>
        </div>
        <div id="likesList" class="elite-scroll-mini" style="max-height: 350px; overflow-y: auto;">
            <!-- Users dynamically loaded -->
        </div>
    </div>
</div>
<div id="likesOverlay" onclick="closeLikesModal()" class="elite-modal-overlay" style="display: none; background: rgba(5,10,21,0.8); backdrop-filter: none; z-index: 2999;"></div>

<style>
.elite-scroll-mini::-webkit-scrollbar { width: 4px; }
.elite-scroll-mini::-webkit-scrollbar-track { background: transparent; }
.elite-scroll-mini::-webkit-scrollbar-thumb { background: var(--surface-10); border-radius: 10px; }
</style>
