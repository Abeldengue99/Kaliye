<?php
/**
 * explore_students.php - Find Students to Guide (Premium Trading Cards Layout with Pagination)
 */
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Mentorship Cascade Logic Map
$can_mentor = [
    'univ_student' => ['univ_student', 'student', 'high_student'],
    'student'      => ['student', 'high_student'],
    'sec_student'  => [],
    'mentor'       => ['univ_student', 'student', 'high_student', 'sec_student'],
    'admin'        => ['mentor', 'univ_student', 'student', 'high_student', 'sec_student']
];

$student_types = ['student', 'univ_student', 'high_student', 'sec_student'];
$target_types = array_values(array_intersect($can_mentor[$user_type] ?? $student_types, $student_types));

// --- Lógica de Paginação ---
$items_per_page = 8; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $items_per_page;

// Contagem total
if (!empty($target_types) || $user_type === 'admin') {
    if ($user_type === 'admin') {
        $placeholders = implode(',', array_fill(0, count($student_types), '?'));
        $count_query = "SELECT COUNT(*) FROM users WHERE user_type IN ($placeholders) AND user_id != ? AND full_name NOT LIKE '%Teste%'";
        $stmt_count = $db->prepare($count_query);
        $stmt_count->execute(array_merge($student_types, [$user_id]));
    } else {
        $placeholders = implode(',', array_fill(0, count($target_types), '?'));
        $count_query = "SELECT COUNT(*) FROM users WHERE user_type IN ($placeholders) AND user_id != ? AND user_type != 'investor' AND full_name NOT LIKE '%Teste%'";
        $stmt_count = $db->prepare($count_query);
        $stmt_count->execute(array_merge($target_types, [$user_id]));
    }
    $total_items = $stmt_count->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
} else {
    $total_items = 0;
    $total_pages = 0;
}
?>

<link rel="stylesheet" href="../../recursos/css/pages/mentorship.css?v=<?php echo time(); ?>">

<style>
/* =========================================================================
   NOVO LAYOUT DE REDE DE TALENTOS: "TRADING CARDS ELITE" (AZUL)
   ========================================================================= */

/* Hero Header Azulado */
.elite-hero-header.talents-hero {
    text-align: center;
    padding: 2.25rem 2rem 2rem;
    position: relative;
    border-radius: 22px;
    background: linear-gradient(135deg, rgba(8, 22, 48, 0.9), rgba(5, 10, 21, 0.98));
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(59, 130, 246, 0.1);
    box-shadow: 0 25px 60px rgba(0,0,0,0.6);
    margin-bottom: 1.75rem;
    overflow: hidden;
}

.elite-hero-header.talents-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: 50%;
    transform: translateX(-50%);
    width: 70%;
    height: 150%;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 60%);
    z-index: 0;
    pointer-events: none;
}

.elite-hero-content { position: relative; z-index: 1; }
.elite-hero-content h1 { font-family: 'Outfit', sans-serif; font-size: clamp(2rem, 4vw, 2.75rem); font-weight: 900; color: #fff; margin-bottom: 0.55rem; letter-spacing: 0; }
.elite-hero-content p { font-size: 0.98rem; color: var(--surface-60); max-width: 620px; margin: 0 auto 1.35rem; line-height: 1.5; }

.hero-search-bar.talents-search { position: relative; max-width: 500px; margin: 0 auto; }
.hero-search-bar.talents-search input {
    width: 100%;
    padding: 1.1rem 1.5rem 1.1rem 3.5rem;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(59, 130, 246, 0.2);
    color: #fff;
    font-size: 1rem;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    outline: none;
}
.hero-search-bar.talents-search i { position: absolute; left: 1.3rem; top: 50%; transform: translateY(-50%); color: rgba(59, 130, 246, 0.5); font-size: 1.2rem; }

/* GRID E CARDS (COMPACTOS) */
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

.elite-mentor-card:hover { transform: translateY(-8px); border-color: rgba(59, 130, 246, 0.4); box-shadow: 0 15px 35px rgba(59, 130, 246, 0.15); }

.card-cover-image {
    width: 100%;
    height: 126px;
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 50% 15%, rgba(59, 130, 246, 0.18), transparent 34%),
        linear-gradient(135deg, rgba(19, 32, 55, 0.95), rgba(5, 10, 21, 0.98));
}
.student-avatar-shell {
    position: absolute;
    left: 50%;
    bottom: -34px;
    transform: translateX(-50%);
    width: 108px;
    height: 108px;
    border-radius: 50%;
    padding: 4px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.9), rgba(247, 148, 29, 0.38));
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

.student-badge {
    position: absolute; top: 12px; right: 12px;
    background: rgba(10, 17, 34, 0.7); backdrop-filter: blur(8px);
    border: 1px solid rgba(59, 130, 246, 0.4);
    color: #60a5fa; font-size: 0.62rem; font-weight: 800;
    padding: 4px 10px; border-radius: 30px;
    display: flex; align-items: center; gap: 4px; z-index: 5;
}

.card-body { padding: 2.8rem 1rem 1rem; text-align: center; z-index: 2; flex-grow: 1; display: flex; flex-direction: column;}
.mentor-name { font-size: 1.05rem; font-weight: 800; color: #fff; margin: 0 0 4px; font-family: 'Outfit', sans-serif; line-height: 1.2; }
.mentor-role.student-role { font-size: 0.72rem; color: #60a5fa; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem; display: block; line-height: 1.35; }

.mentor-skills { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; margin-bottom: 0.85rem; min-height: 26px; }
.skill-pill { background: rgba(255, 255, 255, 0.035); border: 1px solid rgba(255, 255, 255, 0.08); color: var(--surface-50); font-size: 0.66rem; padding: 4px 8px; border-radius: 6px; font-weight: 700; }

.card-footer { padding: 0 1rem 1rem; }
.elite-btn.student-btn {
    width: 100%;
    padding: 10px 15px;
    border-radius: 10px;
    font-weight: 800;
    font-size: 0.85rem;
    font-family: 'Outfit', sans-serif;
    transition: all 0.3s ease;
    cursor: pointer;
    background: rgba(59, 130, 246, 0.1);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.2);
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.elite-mentor-card:hover .elite-btn.student-btn { background: #2563eb; color: #fff; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); }

/* PAGINAÇÃO */
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
.page-link.active { background: #3b82f6; color: #fff; border-color: #3b82f6; box-shadow: 0 8px 20px rgba(59,130,246,0.3); }
.page-link.disabled { opacity: 0.3; pointer-events: none; }

.back-button-glass {
    background: var(--surface-5); backdrop-filter: blur(12px); border: 1px solid var(--surface-10);
    color: #cbd5e1; width: 44px; height: 44px; border-radius: 12px;
    cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;
}
.back-button-glass:hover { background: rgba(59, 130, 246, 0.2); color: #fff; transform: translateX(-4px); }
</style>

<div class="mentorship-container" style="max-width: 1400px; margin: 0 auto; padding: 2rem 5%;">
    <button onclick="window.history.back()" class="back-button-glass"><i class="fas fa-arrow-left"></i></button>

    <div class="elite-hero-header talents-hero">
        <div class="elite-hero-content">
            <h1>Rede de Talentos</h1>
            <p>Descobre projectos, perfis e mentes brilhantes. Torna-te no guia destes estudantes promissores e cria impacto.</p>
            <div class="hero-search-bar talents-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Pesquisar por nome, curso ou instituição..." onkeyup="filterMentors(this.value)">
            </div>
        </div>
    </div>

    <div class="elite-mentor-grid" id="mentorsGrid">
        <?php
        if (!empty($target_types) || $user_type === 'admin') {
            if ($user_type === 'admin') {
                $placeholders = implode(',', array_fill(0, count($student_types), '?'));
                $stmt = $db->prepare("SELECT * FROM users WHERE user_type IN ($placeholders) AND user_id != ? AND full_name NOT LIKE '%Teste%' ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->execute(array_merge($student_types, [$user_id, $items_per_page, $offset]));
            } else {
                $placeholders = implode(',', array_fill(0, count($target_types), '?'));
                $stmt = $db->prepare("SELECT * FROM users WHERE user_type IN ($placeholders) AND user_id != ? AND user_type != 'investor' AND full_name NOT LIKE '%Teste%' ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->execute(array_merge($target_types, [$user_id], [$items_per_page, $offset]));
            }
            
            while ($student = $stmt->fetch()):
                // Logic aprimorada para fotos de perfil
                $pfp = '../../recursos/images/default_profile.png';
                if (!empty($student['profile_pic'])) {
                    if (strpos($student['profile_pic'], 'http') !== false) {
                        $pfp = $student['profile_pic'];
                    } elseif (strpos($student['profile_pic'], 'carregamentos/') !== false) {
                        $pfp = '../../' . $student['profile_pic'];
                    } else {
                        $pfp = '../../carregamentos/profiles/' . $student['profile_pic'];
                    }
                }
                
                // Tipo de Estudante p/ badge
                $_t = $student['user_type'] ?? '';
                $badge_txt = 'Talento';
                if($_t == 'univ_student') $badge_txt = 'Universitário';
                elseif($_t == 'high_student') $badge_txt = 'Ensino Médio';
                elseif($_t == 'sec_student') $badge_txt = 'Secundário';

                $skills = [];
                if(!empty($student['field_of_study'])) $skills[] = $student['field_of_study'];
                if(!empty($student['institution'])) $skills[] = $student['institution'];
                if(empty($skills)) $skills = ['Em Evolução'];
        ?>
            <div class="elite-mentor-card mentor-search-item" data-search="<?php echo strtolower($student['full_name'] . ' ' . implode(' ', $skills)); ?>">
                <div class="card-cover-image">
                    <div class="cover-gradient"></div>
                    <div class="student-avatar-shell">
                        <img src="<?php echo htmlspecialchars($pfp); ?>" alt="<?php echo htmlspecialchars($student['full_name']); ?>" class="mentor-avatar" onerror="this.src='../../recursos/images/default_profile.png'">
                    </div>
                    <div class="student-badge"><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($badge_txt); ?></div>
                </div>
                <div class="card-body">
                    <h3 class="mentor-name"><?php echo htmlspecialchars($student['full_name']); ?></h3>
                    <span class="mentor-role student-role">Aspirante Elite</span>
                    <div class="mentor-skills">
                        <?php foreach(array_slice($skills, 0, 2) as $s): ?>
                            <span class="skill-pill"><?php echo htmlspecialchars($s); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="elite-btn student-btn" onclick="openUserCard(<?php echo $student['user_id']; ?>)">
                        <i class="fas fa-user-circle"></i> Ver Perfil Completo
                    </button>
                </div>
            </div>
        <?php endwhile; } ?>
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
function filterMentors(query) {
    query = query.toLowerCase().trim();
    const items = document.querySelectorAll('.mentor-search-item');
    items.forEach(item => {
        const data = item.getAttribute('data-search');
        item.style.display = data.includes(query) ? 'flex' : 'none';
    });
}
</script>

<?php require_once '../../inclusoes/rodape.php'; ?>
