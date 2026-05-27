<?php
/**
 * Community feedback section for the post-login dashboard.
 */
try {
    $stmt_wall = $db->prepare("SELECT e.*, COALESCE(u.full_name, 'Utilizador KALIYE') as full_name, u.profile_pic
                                FROM platform_evaluations e
                                LEFT JOIN users u ON e.user_id = u.user_id
                                ORDER BY e.is_featured DESC, e.created_at DESC
                                LIMIT 8");
    $stmt_wall->execute();
    $community_evals = $stmt_wall->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "<!-- COMMUNITY SECTION ERROR: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . " -->";
    $community_evals = [];
}
?>

<section class="community-feedback-section" data-aos="fade-up">
    <div class="community-feedback-heading">
        <div>
            <span>Comunidade</span>
            <h2>KALIYE <strong>★</strong></h2>
        </div>
        <div class="community-feedback-icon">
            <i class="fas fa-quote-right"></i>
        </div>
    </div>

    <p class="community-feedback-copy">
        Veja as avaliações dos nossos usuários da KALIYE:
    </p>

    <div class="community-feedback-swiper swiper">
        <div class="swiper-wrapper">
            <?php if (empty($community_evals)): ?>
                <div class="swiper-slide">
                    <div class="community-feedback-card community-feedback-empty">
                        Experiência KALIYE ★
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($community_evals as $fe): ?>
                    <?php
                        $pic = !empty($fe['profile_pic'])
                            ? $base_url . 'carregamentos/profiles/' . $fe['profile_pic']
                            : $base_url . 'recursos/images/default_profile.png';
                        $rating = (int)($fe['rating'] ?? 5);
                    ?>
                    <div class="swiper-slide">
                        <article class="community-feedback-card">
                            <div class="community-feedback-avatar">
                                <img src="<?php echo htmlspecialchars($pic, ENT_QUOTES, 'UTF-8'); ?>"
                                     onerror="this.src='<?php echo $base_url; ?>recursos/images/default_profile.png'"
                                     alt="<?php echo htmlspecialchars($fe['full_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <span></span>
                            </div>
                            <div class="community-feedback-body">
                                <h3><?php echo htmlspecialchars($fe['full_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="community-feedback-stars" aria-label="<?php echo $rating; ?> de 5 estrelas">
                                    <?php for ($k = 1; $k <= 5; $k++): ?>
                                        <i class="<?php echo $k <= $rating ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php if (!empty($fe['comment'])): ?>
                                    <p><?php echo htmlspecialchars($fe['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const initCommunityFeedback = () => {
        if (typeof Swiper === 'undefined') {
            setTimeout(initCommunityFeedback, 500);
            return;
        }

        new Swiper('.community-feedback-swiper', {
            slidesPerView: 1,
            spaceBetween: 16,
            loop: true,
            speed: 900,
            autoplay: { delay: 5000, disableOnInteraction: false },
            grabCursor: true,
            breakpoints: {
                720: { slidesPerView: 2 },
                1100: { slidesPerView: 3 }
            }
        });
    };

    initCommunityFeedback();
});
</script>
