<?php
/**
 * explore_mentors.php - Find Mentors (Premium Trading Cards Layout with Pagination)
 */
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// --- Lógica de Paginação ---
$items_per_page = 8; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $items_per_page;

// Contagem total para paginação
$count_query = "SELECT COUNT(*) FROM users WHERE user_id != ? AND (user_type = 'mentor' OR ((user_type = 'univ_student' OR user_type = 'student') AND mentorship_status = 'approved')) AND full_name NOT LIKE '%Teste%'";
$stmt_count = $db->prepare($count_query);
$stmt_count->execute([$user_id]);
$total_items = $stmt_count->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

$elite_count_query = "SELECT COUNT(*) FROM users WHERE user_id != ? AND user_type = 'mentor' AND full_name NOT LIKE '%Teste%'";
$stmt_elite_count = $db->prepare($elite_count_query);
$stmt_elite_count->execute([$user_id]);
$elite_count = (int)$stmt_elite_count->fetchColumn();

$peer_count_query = "SELECT COUNT(*) FROM users WHERE user_id != ? AND (user_type = 'univ_student' OR user_type = 'student') AND mentorship_status = 'approved' AND full_name NOT LIKE '%Teste%'";
$stmt_peer_count = $db->prepare($peer_count_query);
$stmt_peer_count->execute([$user_id]);
$peer_count = (int)$stmt_peer_count->fetchColumn();

?>

<link rel="stylesheet" href="../../recursos/css/pages/mentorship.css?v=<?php echo time(); ?>">

<style>
/* =========================================================================
   NOVO LAYOUT DE REDE DE MENTORES: "TRADING CARDS ELITE"
   ========================================================================= */

/* ELITE HERO HEADER */
.elite-hero-header {
    text-align: center;
    padding: 2.25rem 2rem 2rem;
    position: relative;
    border-radius: 22px;
    background: linear-gradient(135deg, rgba(13, 22, 40, 0.9), rgba(5, 10, 21, 0.98));
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.05);
    box-shadow: 0 25px 60px rgba(0,0,0,0.6);
    margin-bottom: 1.75rem;
    overflow: hidden;
}

.elite-hero-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: 50%;
    transform: translateX(-50%);
    width: 70%;
    height: 150%;
    background: radial-gradient(circle, rgba(247,148,29,0.1) 0%, transparent 60%);
    z-index: 0;
    pointer-events: none;
}

.elite-hero-content { position: relative; z-index: 1; }
.elite-hero-content h1 { font-family: 'Outfit', sans-serif; font-size: clamp(2rem, 4vw, 2.75rem); font-weight: 900; color: #fff; margin-bottom: 0.55rem; letter-spacing: 0; }
.elite-hero-content p { font-size: 0.98rem; color: var(--surface-60); max-width: 620px; margin: 0 auto 1.35rem; line-height: 1.5; }

.hero-search-bar { position: relative; max-width: 560px; margin: 0 auto; }
.hero-search-bar input {
    width: 100%;
    padding: 0.95rem 1.25rem 0.95rem 3.25rem;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(247, 148, 29, 0.25);
    color: #fff;
    font-size: 1rem;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    outline: none;
}
.hero-search-bar i { position: absolute; left: 1.3rem; top: 50%; transform: translateY(-50%); color: var(--surface-50); font-size: 1.2rem; }

.mentor-quick-stats {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    margin: 1rem auto 0;
    flex-wrap: wrap;
}

.mentor-stat-chip {
    min-width: 132px;
    padding: 0.65rem 0.9rem;
    border-radius: 14px;
    background: rgba(255,255,255,0.035);
    border: 1px solid rgba(255,255,255,0.07);
    color: rgba(255,255,255,0.72);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    font-size: 0.78rem;
    font-weight: 700;
}

.mentor-stat-chip strong { color: #fff; font-size: 0.95rem; }
.mentor-stat-chip i { color: var(--elite-orange, #f7941d); }

/* GRID E CARDS (REDUZIDOS) */
.elite-mentor-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(245px, 1fr));
    gap: 1.35rem;
    padding-bottom: 3rem;
}

.elite-mentor-card {
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.92), rgba(8, 13, 27, 0.98));
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.065);
    overflow: hidden;
    transition: var(--transition-bounce);
    position: relative;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.elite-mentor-card:hover { transform: translateY(-8px); border-color: rgba(247, 148, 29, 0.4); box-shadow: 0 15px 35px rgba(247, 148, 29, 0.15); }

.card-cover-image {
    width: 100%;
    height: 126px;
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 50% 15%, rgba(247, 148, 29, 0.18), transparent 34%),
        linear-gradient(135deg, rgba(19, 32, 55, 0.95), rgba(5, 10, 21, 0.98));
}

.mentor-avatar-shell {
    position: absolute;
    left: 50%;
    bottom: -34px;
    transform: translateX(-50%);
    width: 108px;
    height: 108px;
    border-radius: 50%;
    padding: 4px;
    background: linear-gradient(135deg, rgba(247, 148, 29, 0.9), rgba(96, 165, 250, 0.42));
    box-shadow: 0 18px 35px rgba(0,0,0,0.38);
    z-index: 2;
}

.card-cover-image img.mentor-avatar {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid rgba(8, 13, 27, 0.98);
    background: #111827;
    transition: transform 0.45s ease;
}
.elite-mentor-card:hover .mentor-avatar { transform: scale(1.06); }
.cover-gradient { position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(5,10,21,0) 20%, rgba(8,13,27,0.92) 100%); z-index: 1; }

.mentor-badge {
    position: absolute; top: 12px; right: 12px;
    background: rgba(10, 17, 34, 0.7); backdrop-filter: blur(8px);
    border: 1px solid rgba(247, 148, 29, 0.4);
    color: #f7941d; font-size: 0.62rem; font-weight: 800;
    padding: 4px 10px; border-radius: 30px;
    display: flex; align-items: center; gap: 4px; z-index: 5;
}
.mentor-badge.peer {
    color: #60a5fa; border-color: rgba(59, 130, 246, 0.4);
}

.card-body { padding: 2.8rem 1rem 1rem; text-align: center; z-index: 2; flex-grow: 1; display: flex; flex-direction: column;}
.mentor-name { font-size: 1.05rem; font-weight: 800; color: #fff; margin: 0 0 4px; font-family: 'Outfit', sans-serif; line-height: 1.2; }
.mentor-role { font-size: 0.72rem; color: #f7941d; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem; display: block; line-height: 1.35; }

.mentor-skills { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; margin-bottom: 0.85rem; min-height: 26px; }
.skill-pill { background: rgba(255, 255, 255, 0.035); border: 1px solid rgba(255, 255, 255, 0.08); color: var(--surface-50); font-size: 0.66rem; padding: 4px 8px; border-radius: 6px; font-weight: 700; }

.card-footer { padding: 0 1rem 1rem; }
.elite-btn {
    width: 100%;
    padding: 10px 15px;
    border-radius: 10px;
    font-weight: 800;
    font-size: 0.85rem;
    font-family: 'Outfit', sans-serif;
    transition: all 0.3s ease;
    cursor: pointer;
    background: rgba(247, 148, 29, 0.1);
    color: #f7941d;
    border: 1px solid rgba(247, 148, 29, 0.2);
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.elite-mentor-card:hover .elite-btn { background: #f7941d; color: #111; box-shadow: 0 4px 15px rgba(247, 148, 29, 0.3); }

/* PAGINAÇÃO ELITE */
.pagination-container {
    display: flex;
    justify-content: center;
    gap: 0.6rem;
    margin-top: 2rem;
    padding-bottom: 4rem;
}
.page-link {
    width: 44px; height: 44px;
    display: flex; align-items: center; justify-content: center;
    background: var(--surface-5);
    border: 1px solid var(--surface-10);
    border-radius: 12px;
    color: var(--surface-60);
    font-weight: 700;
    transition: all 0.3s;
    text-decoration: none;
}
.page-link:hover { background: var(--surface-10); color: #fff; transform: translateY(-3px); }
.page-link.active { background: #f7941d; color: #111; border-color: #f7941d; box-shadow: 0 8px 20px rgba(247,148,29,0.3); }
.page-link.disabled { opacity: 0.3; pointer-events: none; }

.back-button-glass {
    background: var(--surface-5); backdrop-filter: blur(12px); border: 1px solid var(--surface-10);
    color: #cbd5e1; min-width: 44px; height: 44px; border-radius: 12px;
    cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 0.55rem; margin-bottom: 1rem; padding: 0 0.9rem;
    font-weight: 700;
}
.back-button-glass:hover { background: rgba(247,148,29,0.2); color: #fff; transform: translateX(-4px); }

.filter-chip {
    padding: 8px 20px; border-radius: 30px; background: rgba(255,255,255,0.03); 
    border: 1px solid rgba(255,255,255,0.08); color: rgba(255,255,255,0.6);
    font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.3s;
}
.filter-chip span { color: inherit; opacity: 0.78; margin-left: 0.35rem; }
.filter-chip:hover { background: rgba(255,255,255,0.06); color: #fff; border-color: rgba(255,255,255,0.15); }
.filter-chip.active { background: var(--elite-orange, #f7941d); color: #111; border-color: var(--elite-orange, #f7941d); box-shadow: 0 5px 15px rgba(247,148,29,0.2); }

@media (max-width: 768px) {
    .mentorship-container { padding: 1rem 4% !important; }
    .elite-hero-header { padding: 1.5rem 1rem; border-radius: 18px; }
    .hero-search-bar input { font-size: 0.92rem; }
    .mentor-stat-chip { min-width: calc(50% - 0.5rem); }
    .elite-mentor-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
    .back-button-glass span { display: none; }
}

@media (max-width: 480px) {
    .mentor-stat-chip { min-width: 100%; }
    .filter-chip { width: 100%; }
}
</style>

<div class="mentorship-container" style="max-width: 1400px; margin: 0 auto; padding: 2rem 5%;">
    <button onclick="window.history.back()" class="back-button-glass"><i class="fas fa-arrow-left"></i><span>Voltar</span></button>

    <div class="elite-hero-header">
        <div class="elite-hero-content">
            <h1>Ecossistema de Mentores</h1>
            <p>Conecte-se com especialistas aprovados. Mentoria de alto impacto para acelerar o seu percurso profissional.</p>
            <div class="hero-search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="mentorSearchInput" placeholder="Pesquisar mentores ou areas..." onkeyup="filterMentors()">
            </div>

            <div class="mentor-quick-stats">
                <div class="mentor-stat-chip"><i class="fas fa-users"></i><strong><?php echo (int)$total_items; ?></strong> disponiveis</div>
                <div class="mentor-stat-chip"><i class="fas fa-certificate"></i><strong><?php echo (int)$elite_count; ?></strong> elite</div>
                <div class="mentor-stat-chip"><i class="fas fa-user-graduate"></i><strong><?php echo (int)$peer_count; ?></strong> peers</div>
            </div>

            <!-- Filtros Rápidos Elite -->
            <div style="display: flex; justify-content: center; gap: 10px; margin-top: 1rem; flex-wrap: wrap;">
                <button class="filter-chip active" onclick="setMentorFilter('all', this)">Todos <span><?php echo (int)$total_items; ?></span></button>
                <button class="filter-chip" onclick="setMentorFilter('mentor', this)">Mentores Elite <span><?php echo (int)$elite_count; ?></span></button>
                <button class="filter-chip" onclick="setMentorFilter('peer', this)">Peer Mentors <span><?php echo (int)$peer_count; ?></span></button>
            </div>
        </div>
    </div>

    <div class="elite-mentor-grid" id="mentorsGrid">
        <?php
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id != ? AND (user_type = 'mentor' OR ((user_type = 'univ_student' OR user_type = 'student') AND mentorship_status = 'approved')) AND full_name NOT LIKE '%Teste%' ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$user_id, $items_per_page, $offset]);
        
            while ($mentor = $stmt->fetch()):
                $pfp = '../../recursos/images/default_profile.png';
                if (!empty($mentor['profile_pic'])) {
                    if (strpos($mentor['profile_pic'], 'http') !== false) {
                        $pfp = $mentor['profile_pic'];
                    } elseif (strpos($mentor['profile_pic'], 'carregamentos/') !== false) {
                        $pfp = '../../' . $mentor['profile_pic'];
                    } else {
                        $pfp = '../../carregamentos/profiles/' . $mentor['profile_pic'];
                    }
                }
                $profession = trim($mentor['profession'] ?? '');
                $specialty = trim($mentor['specialty'] ?? '');
                $role_label = ($mentor['user_type'] === 'mentor') ? $profession : $specialty;
        ?>
            <div class="elite-mentor-card mentor-search-item" 
                data-type="<?php echo ($mentor['user_type'] === 'mentor') ? 'mentor' : 'peer'; ?>"
                 data-search="<?php echo strtolower($mentor['full_name'] . ' ' . $profession . ' ' . $specialty); ?>">
                <div class="card-cover-image">
                    <div class="cover-gradient"></div>
                    <div class="mentor-avatar-shell">
                        <img src="<?php echo htmlspecialchars($pfp); ?>" alt="<?php echo htmlspecialchars($mentor['full_name']); ?>" class="mentor-avatar" onerror="this.src='../../recursos/images/default_profile.png'">
                    </div>
                    
                    <?php if ($mentor['user_type'] === 'mentor'): ?>
                        <div class="mentor-badge"><i class="fas fa-certificate"></i> MENTOR ELITE</div>
                    <?php else: ?>
                        <div class="mentor-badge peer"><i class="fas fa-user-graduate"></i> PEER MENTOR</div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h3 class="mentor-name"><?php echo htmlspecialchars($mentor['full_name']); ?></h3>
                    <?php if (!empty($role_label)): ?>
                    <span class="mentor-role">
                        <?php 
                            if ($mentor['user_type'] === 'univ_student') {
                                echo !empty($mentor['specialty']) ? htmlspecialchars($mentor['specialty']) : 'Estudante Universitário';
                            } else {
                                echo htmlspecialchars($profession); 
                            }
                        ?>
                    </span>
                    <?php endif; ?>
                    <div class="mentor-skills">
                        <?php if (!empty($specialty)): ?>
                            <span class="skill-pill"><?php echo htmlspecialchars($specialty); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($profession) && strcasecmp($profession, $specialty) !== 0): ?>
                            <span class="skill-pill"><?php echo htmlspecialchars($profession); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="elite-btn" onclick="openUserCard(<?php echo $mentor['user_id']; ?>)">
                        <i class="fas fa-user-circle"></i> Ver perfil
                    </button>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Paginação -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <a href="?page=<?php echo $current_page - 1; ?>" class="page-link <?php if($current_page <= 1) echo 'disabled'; ?>"><i class="fas fa-chevron-left"></i></a>
        <?php 
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        for ($i = $start_page; $i <= $end_page; $i++): 
        ?>
            <a href="?page=<?php echo $i; ?>" class="page-link <?php if($i == $current_page) echo 'active'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <a href="?page=<?php echo $current_page + 1; ?>" class="page-link <?php if($current_page >= $total_pages) echo 'disabled'; ?>"><i class="fas fa-chevron-right"></i></a>
    </div>
    <?php endif; ?>
</div>

<script>
let currentTypeFilter = 'all';

function setMentorFilter(type, el) {
    currentTypeFilter = type;
    document.querySelectorAll('.filter-chip').forEach(btn => btn.classList.remove('active'));
    el.classList.add('active');
    filterMentors();
}

function filterMentors() {
    const query = document.getElementById('mentorSearchInput').value.toLowerCase().trim();
    const items = document.querySelectorAll('.mentor-search-item');
    
    items.forEach(item => {
        const dataSearch = item.getAttribute('data-search');
        const dataType = item.getAttribute('data-type');
        
        const matchSearch = dataSearch.includes(query);
        const matchType = (currentTypeFilter === 'all') || (dataType === currentTypeFilter);
        
        item.style.display = (matchSearch && matchType) ? 'flex' : 'none';
    });
}
</script>

<?php require_once '../../inclusoes/rodape.php'; ?>
