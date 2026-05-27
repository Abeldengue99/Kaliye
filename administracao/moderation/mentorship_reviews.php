<?php
// admin/mentorship_reviews.php
session_start();
$admin_base = '../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
requireAdmin();

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// 1. Get Top Rated Mentors (Unified from user_reviews)
$top_mentors_query = "
    SELECT 
        u.user_id, 
        u.full_name, 
        u.profile_pic,
        COUNT(r.review_id) as total_reviews, 
        AVG(r.rating) as avg_rating
    FROM users u
    JOIN user_reviews r ON u.user_id = r.mentor_id
    GROUP BY u.user_id
    ORDER BY avg_rating DESC
    LIMIT 5
";
$top_mentors = $db->query($top_mentors_query)->fetchAll();

// 2. Get All Reviews (From user_reviews table)
$reviews_query = "
    SELECT 
        r.review_id,
        r.rating as mentee_rating,
        r.comment as student_feedback,
        r.created_at as start_time,
        m.full_name as mentor_name,
        s.full_name as student_name
    FROM user_reviews r
    JOIN users m ON r.mentor_id = m.user_id
    JOIN users s ON r.student_id = s.user_id
    ORDER BY r.created_at DESC
";
$reviews = $db->query($reviews_query)->fetchAll();

// 3. Get Top Mentors by Period
function getTopMentorByPeriod($db, $interval) {
    $q = "
        SELECT u.full_name, AVG(r.rating) as avg_rating
        FROM users u
        JOIN user_reviews r ON u.user_id = r.mentor_id
        WHERE r.created_at >= NOW() - INTERVAL '$interval'
        GROUP BY u.user_id, u.full_name
        ORDER BY avg_rating DESC
        LIMIT 1
    ";
    return $db->query($q)->fetch();
}

$top_week = getTopMentorByPeriod($db, '1 WEEK');
$top_month = getTopMentorByPeriod($db, '1 MONTH');
$top_year = getTopMentorByPeriod($db, '1 YEAR');
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>KALIYE Admin - Avaliação Mentoria</title>
    <link rel='icon' type='image/png' href='../../recursos/images/marca/favicon-k-32x32.png'>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { display: flex; background: #020617; color: white; }
        main { margin-left: 260px; flex-grow: 1; padding: 3rem; min-height: 100vh; }
    </style>
</head>
<body>
    <?php include '../barra_lateral.php'; ?>

    <main>
        <h1 style="margin-bottom: 2rem;">Qualidade e Avaliação de Mentoria</h1>

        <!-- Period Rankings -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="glass" style="padding: 1.5rem; text-align: center; border: 1px solid var(--accent-blue);">
                <small style="color: var(--accent-blue);">TOP MENTOR DA SEMANA</small>
                <h3 style="margin: 0.5rem 0;"><?php echo $top_week ? htmlspecialchars($top_week['full_name']) : '---'; ?></h3>
                <div style="color: var(--accent-gold);"><?php echo $top_week ? number_format($top_week['avg_rating'], 1) . ' <i class="fas fa-star"></i>' : ''; ?></div>
            </div>
            <div class="glass" style="padding: 1.5rem; text-align: center; border: 1px solid var(--accent-orange);">
                <small style="color: var(--accent-orange);">TOP MENTOR DO MÊS</small>
                <h3 style="margin: 0.5rem 0;"><?php echo $top_month ? htmlspecialchars($top_month['full_name']) : '---'; ?></h3>
                <div style="color: var(--accent-gold);"><?php echo $top_month ? number_format($top_month['avg_rating'], 1) . ' <i class="fas fa-star"></i>' : ''; ?></div>
            </div>
            <div class="glass" style="padding: 1.5rem; text-align: center; border: 1px solid var(--accent-gold);">
                <small style="color: var(--accent-gold);">TOP MENTOR DO ANO</small>
                <h3 style="margin: 0.5rem 0;"><?php echo $top_year ? htmlspecialchars($top_year['full_name']) : '---'; ?></h3>
                <div style="color: var(--accent-gold);"><?php echo $top_year ? number_format($top_year['avg_rating'], 1) . ' <i class="fas fa-star"></i>' : ''; ?></div>
            </div>
        </div>

        <h3 style="margin-bottom: 1rem;">Melhores Mentores (Geral)</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <?php foreach($top_mentors as $mentor): ?>
            <div class="glass" style="padding: 1.5rem; display: flex; align-items: center; gap: 1rem; border: 1px solid rgba(255,255,255,0.1);">
                <img src="../<?php echo ($mentor['profile_pic'] && $mentor['profile_pic'] != 'default_profile.png') ? $mentor['profile_pic'] : 'recursos/images/default_profile.png'; ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 1px solid var(--accent-gold);">
                <div>
                    <h4 style="margin: 0; font-size: 0.9rem;"><?php echo htmlspecialchars($mentor['full_name']); ?></h4>
                    <div style="color: var(--accent-gold); font-size: 1rem; font-weight: bold;">
                        <?php echo number_format($mentor['avg_rating'], 1); ?> <i class="fas fa-star"></i>
                    </div>
                    <small style="color: var(--text-secondary); font-size: 0.7rem;"><?php echo $mentor['total_reviews']; ?> avaliações</small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Reviews Table -->
        <div class="glass" style="padding: 2rem;">
            <h3>Histórico de Avaliações</h3>
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem; color: white;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--glass-border); text-align: left;">
                        <th style="padding: 1rem;">Data</th>
                        <th style="padding: 1rem;">Mentor</th>
                        <th style="padding: 1rem;">Estudante</th>
                        <th style="padding: 1rem;">Nota</th>
                        <th style="padding: 1rem;">Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reviews as $rev): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 1rem; color: var(--text-secondary);"><?php echo date('d/m/Y', strtotime($rev['start_time'])); ?></td>
                        <td style="padding: 1rem; font-weight: 600;"><?php echo htmlspecialchars($rev['mentor_name']); ?></td>
                        <td style="padding: 1rem;"><?php echo htmlspecialchars($rev['student_name']); ?></td>
                        <td style="padding: 1rem;">
                            <?php 
                            for($i=1; $i<=5; $i++) {
                                echo '<i class="'.($i <= $rev['mentee_rating'] ? 'fas' : 'far').' fa-star" style="color: var(--accent-gold);"></i>';
                            }
                            ?>
                        </td>
                        <td style="padding: 1rem; font-style: italic; color: var(--text-secondary);">"<?php echo htmlspecialchars($rev['student_feedback'] ?? 'Sem comentário'); ?>"</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($reviews)): ?>
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-secondary);">Ainda não existem avaliações registadas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>






