<?php
/**
 * Component: New Doubt Modal
 */
?>
<div id="doubtModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 100000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div class="glass" style="width: 95%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 2rem; border-radius: 20px; position: relative;">
        <button onclick="closeDoubtModal()" style="position: absolute; top: 1rem; right: 1rem; background: var(--surface-10); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">
            <i class="fas fa-times"></i>
        </button>
        
        <h2 style="margin: 0 0 1.5rem 0; color: var(--accent-orange);">
            <i class="fas fa-question-circle"></i> Publicar Dúvida
        </h2>
        
        <form id="doubtForm" onsubmit="submitDoubt(event)">
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Título da Dúvida*</label>
                <input type="text" name="title" required maxlength="255"
                    placeholder="Ex: Como resolver equações do 2º grau?"
                    style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Descrição Detalhada*</label>
                <textarea name="description" required rows="6"
                    placeholder="Descreva a sua dúvida com o máximo de detalhes possível..."
                    style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white; resize: vertical;"></textarea>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Categoria*</label>
                <select name="category" required
                    style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;">
                    <option value="">Selecione...</option>
                    <option value="programming">Programação</option>
                    <option value="math">Matemática</option>
                    <option value="physics">Física</option>
                    <option value="chemistry">Química</option>
                    <option value="languages">Línguas</option>
                    <option value="business">Negócios</option>
                    <option value="design">Design</option>
                    <option value="other">Outro</option>
                </select>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Imagem (Opcional)</label>
                <div style="position: relative;">
                    <input type="file" name="image" id="doubtImage" accept="image/*" 
                        style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;"
                        onchange="previewImage(this)">
                    <div id="imagePreview" style="display: none; margin-top: 1rem; border-radius: 8px; overflow: hidden; max-height: 200px;">
                        <img src="" style="width: 100%; object-fit: cover;">
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Tags (separadas por vírgula)</label>
                <input type="text" name="tags"
                    placeholder="Ex: javascript, array, loop"
                    style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;">
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeDoubtModal()" class="action-btn" style="background: var(--surface-5); color: white;">
                    Cancelar
                </button>
                <button type="submit" class="action-btn btn-primary-doubt">
                    <i class="fas fa-paper-plane"></i> Publicar
                </button>
            </div>
        </form>
    </div>
</div>
