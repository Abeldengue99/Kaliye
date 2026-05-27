<?php
/**
 * Componente: Filtro Inteligente de Projetos
 * Permite filtrar o marketplace por categorias, valores e termos de pesquisa.
 */
?>
<div class="glass" style="padding: 2.5rem; margin-bottom: 3rem; border-radius: 32px; border: 1px solid var(--surface-5); background: #0d1628; position: relative; overflow: hidden;">
    <!-- Efeito Visual de Fundo -->
    <div style="position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; background: var(--brand-primary); filter: blur(150px); opacity: 0.05; pointer-events: none;"></div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <div>
            <h3 style="margin: 0; font-size: 1.5rem; font-family: 'Outfit', sans-serif; font-weight: 800; display: flex; align-items: center; gap: 0.8rem; color: white;">
                <i class="fas fa-search-dollar" style="color: var(--brand-primary);"></i> Filtros de Elite
            </h3>
            <p style="margin: 5px 0 0; font-size: 0.85rem; color: var(--text-muted);">Encontre as melhores oportunidades de investimento em Angola.</p>
        </div>
        <button onclick="resetFilters()" style="background: var(--surface-3); border: 1px solid var(--surface-10); color: var(--text-muted); padding: 0.8rem 1.5rem; border-radius: 14px; cursor: pointer; font-size: 0.8rem; font-weight: 700; transition: 0.3s; display: flex; align-items: center; gap: 0.6rem;">
            <i class="fas fa-sync-alt"></i> Redefinir
        </button>
    </div>
    
    <form method="GET" id="filterForm">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.8rem;">
            
            <!-- Pesquisa Livre -->
            <div class="input-group">
                <label style="display:block; font-size:0.7rem; font-weight:800; color:var(--text-secondary); text-transform:uppercase; margin-bottom:0.8rem;">O que procura?</label>
                <div style="position: relative;">
                    <input type="text" name="search" placeholder="Título, sector ou tecnologia..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                           style="width: 100%; padding: 1.1rem; padding-left: 3rem; background: rgba(0,0,0,0.2); border: 1px solid var(--surface-10); border-radius: 16px; color: white; outline: none; transition: 0.3s;">
                    <i class="fas fa-search" style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: var(--brand-primary); opacity: 0.7;"></i>
                </div>
            </div>
            
            <!-- Categoria -->
            <div class="input-group">
                <label style="display:block; font-size:0.7rem; font-weight:800; color:var(--text-secondary); text-transform:uppercase; margin-bottom:0.8rem;">Sector Industrial</label>
                <select name="category" style="width: 100%; padding: 1.1rem; background: #1e293b; border: 1px solid var(--surface-10); border-radius: 16px; color: white; appearance: none; cursor: pointer;">
                    <option value="">Todos os Sectores</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Faixa de Investimento -->
            <div class="input-group">
                <label style="display:block; font-size:0.7rem; font-weight:800; color:var(--text-secondary); text-transform:uppercase; margin-bottom:0.8rem;">Capital Necessário (AOA)</label>
                <div style="display: flex; gap: 0.8rem; align-items: center;">
                    <input type="number" name="budget_min" placeholder="Mínimo" value="<?php echo htmlspecialchars($_GET['budget_min'] ?? ''); ?>" style="flex: 1; padding: 1.1rem; background: rgba(0,0,0,0.2); border: 1px solid var(--surface-10); border-radius: 16px; color: white;">
                    <span style="color: var(--text-muted); opacity: 0.5;">—</span>
                    <input type="number" name="budget_max" placeholder="Máximo" value="<?php echo htmlspecialchars($_GET['budget_max'] ?? ''); ?>" style="flex: 1; padding: 1.1rem; background: rgba(0,0,0,0.2); border: 1px solid var(--surface-10); border-radius: 16px; color: white;">
                </div>
            </div>

        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--surface-5); flex-wrap: wrap; gap: 1.5rem;">
            <div style="display: flex; gap: 1.5rem;">
                <label style="display: flex; align-items:center; gap: 0.8rem; cursor: pointer;">
                    <input type="checkbox" name="verified" value="1" <?php echo (isset($_GET['verified']) && $_GET['verified'] == '1') ? 'checked' : ''; ?> 
                           style="width: 22px; height: 22px; cursor: pointer; accent-color: var(--brand-primary);">
                    <span style="font-size: 0.9rem; color: white; font-weight: 600;">Apenas Empreendedores Verificados</span>
                </label>
            </div>
            
            <button type="submit" class="btn-primary" style="width: auto; min-width: 220px; padding: 1.1rem 2.5rem; background: white; color: black; border-radius: 16px; border: none; font-weight: 800; cursor: pointer; transition: 0.3s;">
                APLICAR FILTROS
            </button>
        </div>
    </form>
</div>
<?php 
// Componente de Filtro Finalizado
?>
