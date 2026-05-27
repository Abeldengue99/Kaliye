<?php
/**
 * inclusoes/components/dashboard/feedback_wall.php
 * Componente "Mural de Testemunhos" que exibe feedbacks destacados pela administração.
 */
require_once __DIR__ . '/../../../configuracoes/base_dados.php';
$db_wall = (new Database())->getConnection();

try {
    $stmt_wall = $db_wall->query("SELECT e.*, u.full_name, u.profile_pic 
                                 FROM platform_evaluations e 
                                 JOIN users u ON e.user_id = u.user_id 
                                 WHERE e.is_featured = true 
                                 ORDER BY e.created_at DESC 
                                 LIMIT 10");
    $featured_evals = $stmt_wall->fetchAll();
} catch (Exception $e) {
    $featured_evals = [];
}

if (!empty($featured_evals)):
?>
<div class="feedback-wall-section" style="margin: 2rem 0; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 2rem;" data-aos="fade-up">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <span class="feed-ads-label" style="margin: 0;">
            <i class="fas fa-quote-left" style="font-size: 0.55rem; opacity: 0.6;"></i>
            Comunidade KALIYE
        </span>
        <div class="swiper-pagination-compact"></div>
    </div>

    <div class="feedback-swiper swiper">
        <div class="swiper-wrapper">
            <?php foreach ($featured_evals as $fe): ?>
                <div class="swiper-slide feedback-compact-card">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <?php
                            if (!empty($fe['profile_pic']) && $fe['profile_pic'] !== 'default_profile.png') {
                                if (strpos($fe['profile_pic'], 'http') === 0 || strpos($fe['profile_pic'], 'carregamentos/') === 0) {
                                    $pic = $base_url . $fe['profile_pic'];
                                } else {
                                    $pic = $base_url . 'carregamentos/profiles/' . $fe['profile_pic'];
                                }
                            } else {
                                $pic = $base_url . 'recursos/images/default_profile.png';
                            }
                        ?>
                        <img src="<?php echo $pic; ?>" alt="User" class="compact-author-img">
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <span class="compact-author-name"><?php echo htmlspecialchars($fe['full_name']); ?></span>
                                <div class="compact-stars">
                                    <?php for($i=1; $i<=5; $i++) echo '<i class="' . ($i <= $fe['rating'] ? 'fas' : 'far') . ' fa-star"></i>'; ?>
                                </div>
                            </div>
                            <p class="compact-feedback-text">"<?php echo htmlspecialchars($fe['comment']); ?>"</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.feedback-compact-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.04);
    border-radius: 16px;
    padding: 1.2rem;
    transition: 0.3s ease;
}
.feedback-compact-card:hover {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(247, 148, 29, 0.2);
}
.compact-author-img { width: 40px; height: 40px; border-radius: 12px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1); }
.compact-author-name { font-weight: 700; color: #fff; font-size: 0.8rem; }
.compact-stars { color: #f7941d; font-size: 0.65rem; }
.compact-feedback-text { color: rgba(255,255,255,0.6); font-size: 0.85rem; font-style: italic; line-height: 1.4; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

/* Swiper Pagination Compact */
.swiper-pagination-compact { display: flex; gap: 6px; }
.swiper-pagination-bullet { width: 6px; height: 6px; background: rgba(255,255,255,0.1) !important; opacity: 1 !important; margin: 0 !important; }
.swiper-pagination-bullet-active { background: #f7941d !important; width: 12px !important; border-radius: 3px !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Swiper !== 'undefined') {
        new Swiper('.feedback-swiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            autoplay: { delay: 5000, disableOnInteraction: false },
            pagination: { el: '.swiper-pagination-compact', clickable: true },
            breakpoints: {
                768: { slidesPerView: 2 },
                1024: { slidesPerView: 3 }
            }
        });
    }
});
</script>
<?php endif; ?>
