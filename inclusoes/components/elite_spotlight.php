<?php
/**
 * inclusoes/components/elite_spotlight.php
 * Premium Spotlight Carousel for the Elite Dashboard.
 * Displays ads, top mentors, and featured opportunities.
 */
?>
<section class="elite-spotlight-section">
    <div class="elite-stories-carousel">
        
        <!-- Action: Create New -->
        <div class="elite-story-card elite-story-create" onclick="if(enforceKYC(event)) openPostModal()">
            <div class="create-icon-box">
                <i class="fas fa-plus"></i>
            </div>
            <span class="elite-story-name" style="text-align: center;">Nova<br>Referência</span>
        </div>

        <?php
        // 1. ADVERTISEMENTS (Oportunidades)
        if (!empty($active_ads)):
            foreach ($active_ads as $ad):
                $adJson = htmlspecialchars(json_encode($ad, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                $ad_img = $ad['image_url'] ?? '';
                if(!empty($ad_img) && strpos($ad_img, 'http') === false && strpos($ad_img, 'recursos/') === false && strpos($ad_img, 'carregamentos/') === false) {
                    $ad_img = 'carregamentos/ads/' . $ad_img;
                }
        ?>
            <div class="elite-story-card" data-ad-id="<?php echo (int)$ad['ad_id']; ?>" data-ad-json='<?php echo $adJson; ?>' onclick="window.handleAdClick(this)">
                <div class="elite-story-tag tag-ad"><i class="fas fa-briefcase"></i> <?php echo strtoupper($ad['type'] ?? 'ELITE'); ?></div>
                <div class="elite-story-overlay">
                    <span class="elite-story-name"><?php echo htmlspecialchars(mb_strimwidth($ad['title'], 0, 22, "...")); ?></span>
                </div>
                <?php if($ad_img): ?>
                    <img src="<?php echo $base_url . htmlspecialchars($ad_img); ?>" alt="">
                <?php else: ?>
                    <div style="width:100%; height:100%; background: linear-gradient(135deg, #10b981, #064e3b);"></div>
                <?php endif; ?>
            </div>
        <?php 
            endforeach;
        endif; ?>

        <!-- 2. FEATURED MENTORS -->
        <?php
        $m_stmt = $db->query("SELECT user_id, full_name, profile_pic FROM users WHERE user_type = 'mentor' AND verification_status = 'verified' ORDER BY RANDOM() LIMIT 3");
        while ($mentor = $m_stmt->fetch()):
            $m_pic = $mentor['profile_pic'] ?: 'recursos/images/default_profile.png';
            if (strpos($m_pic, 'http') === false && strpos($m_pic, 'recursos/') === false && strpos($m_pic, 'carregamentos/') === false) {
                $m_pic = 'carregamentos/profiles/' . $m_pic;
            }
        ?>
            <div class="elite-story-card" onclick="window.location.href='<?php echo $base_url; ?>paginas/social/profile.php?user_id=<?php echo $mentor['user_id']; ?>'">
                <div class="elite-story-tag tag-mentor"><i class="fas fa-crown"></i> MENTOR</div>
                <div class="elite-story-overlay">
                    <span class="elite-story-name"><?php echo explode(' ', $mentor['full_name'])[0]; ?></span>
                </div>
                <img src="<?php echo $base_url . htmlspecialchars($m_pic); ?>" alt="">
            </div>
        <?php endwhile; ?>

        <!-- 3. TOP INVESTORS -->
        <?php
        $inv_stmt = $db->query("SELECT user_id, full_name, profile_pic FROM users WHERE user_type = 'investor' AND verification_status = 'verified' ORDER BY RANDOM() LIMIT 2");
        while ($investor = $inv_stmt->fetch()):
            $i_pic = $investor['profile_pic'] ?: 'recursos/images/default_profile.png';
            if (strpos($i_pic, 'http') === false && strpos($i_pic, 'recursos/') === false && strpos($i_pic, 'carregamentos/') === false) {
                $i_pic = 'carregamentos/profiles/' . $i_pic;
            }
        ?>
            <div class="elite-story-card" onclick="window.location.href='<?php echo $base_url; ?>paginas/social/profile.php?user_id=<?php echo $investor['user_id']; ?>'">
                <div class="elite-story-tag tag-investor"><i class="fas fa-gem"></i> INVESTOR</div>
                <div class="elite-story-overlay">
                    <span class="elite-story-name"><?php echo explode(' ', $investor['full_name'])[0]; ?></span>
                </div>
                <img src="<?php echo $base_url . htmlspecialchars($i_pic); ?>" alt="">
            </div>
        <?php endwhile; ?>

    </div>
</section>
