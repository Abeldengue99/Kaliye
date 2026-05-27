<?php
/**
 * doubts.php - Community Doubts Feed — New Premium Design
 */
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

$current_user_id  = $_SESSION['user_id'];
$current_user_type = $_SESSION['user_type'];
?>

<link rel="stylesheet" href="../../recursos/css/pages/doubts.css?v=<?php echo time(); ?>">

<style>
/* ── PAGE LAYOUT ── */
.doubts-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2rem 2rem 6rem;
}

/* ── HERO HEADER ── */
.dq-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 2.5rem;
    padding-bottom: 2.5rem;
    border-bottom: 1px solid var(--surface-5);
    flex-wrap: wrap;
    gap: 1.5rem;
}
.dq-hero-title {
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 950;
    color: #fff;
    letter-spacing: -1.5px;
    line-height: 1;
    margin: 0;
}
.dq-hero-sub {
    margin: 0.75rem 0 0;
    font-size: 0.85rem;
    color: var(--surface-30);
    font-weight: 500;
    max-width: 400px;
    line-height: 1.6;
}
.dq-new-btn {
    background: var(--elite-orange, #f7941d);
    color: #fff;
    border: none;
    padding: 0.9rem 2rem;
    border-radius: 14px;
    font-size: 0.72rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 2px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 24px rgba(247,148,29,0.25);
    transition: transform 0.3s, box-shadow 0.3s;
    white-space: nowrap;
}
.dq-new-btn:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(247,148,29,0.35); }

/* ── STATS BAR ── */
.dq-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    margin-bottom: 2.5rem;
}
.dq-stat-card {
    background: rgba(15,23,42,0.6);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 20px;
    padding: 1.5rem 1.75rem;
    backdrop-filter: blur(12px);
    transition: transform 0.3s;
}
.dq-stat-card:hover { transform: translateY(-3px); }
.dq-stat-label {
    font-size: 0.6rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 2.5px;
    color: var(--surface-30);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dq-stat-value {
    font-size: 2.5rem;
    font-weight: 950;
    letter-spacing: -2px;
    line-height: 1;
    color: #fff;
}
.dq-stat-card.total  .dq-stat-label i { color: var(--surface-30); }
.dq-stat-card.open   .dq-stat-value   { color: #f59e0b; }
.dq-stat-card.open   .dq-stat-label i { color: #f59e0b; }
.dq-stat-card.solved .dq-stat-value   { color: #10b981; }
.dq-stat-card.solved .dq-stat-label i { color: #10b981; }

/* ── FILTER BAR ── */
.dq-filter-bar {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 2.5rem;
    background: rgba(15,23,42,0.5);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 18px;
    padding: 1.25rem 1.5rem;
    backdrop-filter: blur(10px);
}
.dq-search-wrap {
    flex: 1;
    min-width: 240px;
    position: relative;
}
.dq-search-wrap i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--surface-25);
    font-size: 0.85rem;
}
.dq-search-wrap input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--surface-8);
    border-radius: 12px;
    color: #fff;
    font-size: 0.85rem;
    outline: none;
    transition: border-color 0.3s;
    box-sizing: border-box;
}
.dq-search-wrap input:focus { border-color: rgba(247,148,29,0.4); }
.dq-search-wrap input::placeholder { color: var(--surface-20); }
.dq-select {
    padding: 0.75rem 1.25rem;
    background: rgba(15,23,42,0.9);
    border: 1px solid var(--surface-8);
    border-radius: 12px;
    color: var(--surface-70);
    font-size: 0.82rem;
    font-weight: 600;
    outline: none;
    cursor: pointer;
    transition: border-color 0.3s;
    /* Força aparência escura no dropdown nativo */
    -webkit-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23f7941d' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
}
.dq-select:focus { border-color: rgba(247,148,29,0.4); }
.dq-select option {
    background: #0f172a;
    color: #e2e8f0;
    font-weight: 600;
    padding: 8px 12px;
}
.dq-select option:checked,
.dq-select option:hover {
    background: #1e293b;
    color: #f7941d;
}

/* ── DOUBT CARDS ── */
#doubts-container { display: flex; flex-direction: column; gap: 0; }

.dq-card {
    background: rgba(15,23,42,0.5);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.25rem;
    backdrop-filter: blur(10px);
    transition: border-color 0.3s, transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
    animation: dqFadeUp 0.4s ease both;
}
.dq-card:hover {
    border-color: rgba(247,148,29,0.2);
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.3);
}
@keyframes dqFadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }

.dq-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1rem;
}
.dq-card-author {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.dq-card-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--surface-8);
}
.dq-card-author-info { line-height: 1.3; }
.dq-card-name {
    font-size: 0.85rem;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 6px;
}
.dq-badge {
    font-size: 0.52rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 2px 7px;
    border-radius: 6px;
    background: rgba(247,148,29,0.15);
    color: #f7941d;
    border: 1px solid rgba(247,148,29,0.2);
}
.dq-badge.mentor  { background: rgba(167,139,250,0.15); color: #a78bfa; border-color: rgba(167,139,250,0.2); }
.dq-badge.student { background: rgba(99,179,237,0.15);  color: #63b3ed; border-color: rgba(99,179,237,0.2); }
.dq-card-meta {
    font-size: 0.7rem;
    color: var(--surface-25);
    font-weight: 500;
}
.dq-tags { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.dq-tag {
    font-size: 0.58rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 4px 10px;
    border-radius: 8px;
    background: var(--surface-5);
    color: var(--surface-40);
    border: 1px solid var(--surface-8);
}
.dq-tag.open     { background: rgba(247,148,29,0.1); color: #f7941d; border-color: rgba(247,148,29,0.2); }
.dq-tag.resolved { background: rgba(16,185,129,0.1); color: #10b981; border-color: rgba(16,185,129,0.2); }
.dq-tag.closed   { background: rgba(100,116,139,0.15); color: var(--surface-30); border-color: var(--surface-8); }

.dq-card-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 0.6rem;
    letter-spacing: -0.3px;
    line-height: 1.4;
}
.dq-card-excerpt {
    font-size: 0.82rem;
    color: var(--surface-40);
    line-height: 1.65;
    margin: 0 0 1.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.dq-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid rgba(255,255,255,0.04);
}
.dq-card-counters {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}
.dq-counter {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--surface-25);
}
.dq-counter i { font-size: 0.7rem; }
.dq-see-link {
    font-size: 0.68rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--elite-orange, #f7941d);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    opacity: 0.7;
    transition: opacity 0.25s, gap 0.25s;
}
.dq-see-link:hover { opacity: 1; gap: 10px; }

/* ── LOADING ── */
.dq-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 5rem 0;
    gap: 1rem;
    color: var(--surface-20);
}
.dq-spinner {
    width: 44px; height: 44px;
    border-radius: 50%;
    border: 3px solid rgba(247,148,29,0.15);
    border-top-color: #f7941d;
    animation: spin 0.75s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── EMPTY STATE ── */
.dq-empty {
    text-align: center;
    padding: 5rem 2rem;
    color: var(--surface-15);
}
.dq-empty i { font-size: 3rem; margin-bottom: 1rem; display: block; }
.dq-empty p { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

/* ── PAGINATION ── */
.dq-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 3rem;
}
.dq-pg-btn {
    width: 36px; height: 36px;
    border-radius: 10px;
    border: 1px solid var(--surface-8);
    background: var(--surface-3);
    color: var(--surface-40);
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: 0.25s;
}
.dq-pg-btn:hover, .dq-pg-btn.active {
    background: rgba(247,148,29,0.12);
    border-color: rgba(247,148,29,0.3);
    color: #f7941d;
}

@media (max-width: 640px) {
    .dq-stats { grid-template-columns: 1fr; }
    .dq-hero { flex-direction: column; align-items: flex-start; }
    .dq-card { padding: 1.25rem; }
}
</style>

<div class="doubts-page" data-aos="fade">

    <!-- ── BOTÃO VOLTAR ── -->
    <button onclick="window.history.back()" style="
        position: fixed; top: 90px; left: 2rem; z-index: 200;
        background: rgba(15,23,42,0.8); backdrop-filter: blur(12px);
        border: 1px solid var(--surface-8); color: var(--surface-50);
        width: 40px; height: 40px; border-radius: 50%; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.9rem; transition: all 0.3s;"
        onmouseover="this.style.color='#fff'; this.style.borderColor='var(--surface-25)'; this.style.transform='scale(1.08)'"
        onmouseout="this.style.color='var(--surface-50)'; this.style.borderColor='var(--surface-8)'; this.style.transform='scale(1)'">
        <i class="fas fa-arrow-left"></i>
    </button>

    <!-- ── HERO ── -->
    <div class="dq-hero" data-aos="fade-down">
        <div>
            <h1 class="dq-hero-title">Dúvidas da Comunidade</h1>
            <p class="dq-hero-sub">Partilhe as suas dúvidas e ajude outros membros da comunidade a crescer no ecossistema de referências.</p>
        </div>
        <button onclick="openDoubtModal()" class="dq-new-btn">
            <i class="fas fa-plus"></i> Nova Dúvida
        </button>
    </div>

    <!-- ── STATS BAR ── -->
    <div class="dq-stats" id="doubts-stats-bar" style="opacity:0; transition: opacity 0.5s;" data-aos="fade-up">
        <div class="dq-stat-card total">
            <div class="dq-stat-label"><i class="fas fa-layer-group"></i> Total</div>
            <div class="dq-stat-value" id="stat-total">—</div>
        </div>
        <div class="dq-stat-card open">
            <div class="dq-stat-label"><i class="fas fa-circle-notch"></i> Abertas</div>
            <div class="dq-stat-value" id="stat-open">—</div>
        </div>
        <div class="dq-stat-card solved">
            <div class="dq-stat-label"><i class="fas fa-check-circle"></i> Resolvidas</div>
            <div class="dq-stat-value" id="stat-resolved">—</div>
        </div>
    </div>

    <!-- ── FILTER BAR ── -->
    <div class="dq-filter-bar" data-aos="fade-up">
        <div class="dq-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="O que você está procurando?" oninput="filterDoubts()">
        </div>
        <select id="categoryFilter" onchange="filterDoubts()" class="dq-select">
            <option value="">Todas Categorias</option>
            <option value="programming">Programação</option>
            <option value="math">Matemática</option>
            <option value="physics">Física</option>
            <option value="chemistry">Química</option>
            <option value="languages">Línguas</option>
            <option value="business">Negócios</option>
            <option value="technology">Tecnologia</option>
            <option value="finance">Finanças</option>
            <option value="design">Design</option>
            <option value="other">Outro</option>
        </select>
        <select id="statusFilter" onchange="filterDoubts()" class="dq-select">
            <option value="">Status: Todos</option>
            <option value="open">Abertas</option>
            <option value="resolved">Resolvidas</option>
            <option value="closed">Fechadas</option>
        </select>
    </div>

    <!-- ── DOUBTS LIST (populated by JS) ── -->
    <div id="doubts-container">
        <div class="dq-loading">
            <div class="dq-spinner"></div>
            <p style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin: 0;">A carregar dúvidas...</p>
        </div>
    </div>

    <!-- ── PAGINATION ── -->
    <div class="dq-pagination" id="doubts-pagination" style="display:none;"></div>

</div>

<!-- Modals -->
<?php include '../../inclusoes/components/doubt_modal.php'; ?>
<?php include '../../inclusoes/components/doubt_detail_modal.php'; ?>

<!-- Scripts Config -->
<script>
    const AKSANTI_CONFIG = {
        userId:   <?php echo json_encode($current_user_id); ?>,
        userType: <?php echo json_encode($current_user_type); ?>,
        baseUrl:  <?php echo json_encode($base_url); ?>
    };
</script>

<!-- Scripts -->
<?php include '../../inclusoes/components/doubts_scripts.php'; ?>

<?php require_once '../../inclusoes/rodape.php'; ?>
