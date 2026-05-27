<?php
/**
 * Component: Doubt Filter Section
 */
?>
<div class="glass" style="padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem;">
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" id="searchInput" placeholder="🔍 Pesquisar dúvidas..." 
                style="width: 100%; padding: 0.75rem 1rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;">
        </div>
        <select id="categoryFilter" onchange="filterDoubts()" 
            style="padding: 0.75rem 1rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;">
            <option value="">Todas Categorias</option>
            <option value="programming">Programação</option>
            <option value="math">Matemática</option>
            <option value="physics">Física</option>
            <option value="chemistry">Química</option>
            <option value="languages">Línguas</option>
            <option value="business">Negócios</option>
            <option value="design">Design</option>
            <option value="other">Outro</option>
        </select>
        <select id="statusFilter" onchange="filterDoubts()" 
            style="padding: 0.75rem 1rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; color: white;">
            <option value="">Todos Status</option>
            <option value="open">Abertas</option>
            <option value="resolved">Resolvidas</option>
            <option value="closed">Fechadas</option>
        </select>
    </div>
</div>
