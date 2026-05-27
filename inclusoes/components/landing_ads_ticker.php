<?php
// landing_ads_ticker.php - Ticker de anúncios para a landing page
/**
 * @var array $items Os anúncios a exibir
 * @var string $id O ID do swiper (opcional)
 * @var bool $is_reverse_style Se deve aplicar o estilo invertido (opcional)
 */
$items = $items ?? [];
$swiper_id = $swiper_id ?? '';
$is_reverse_style = $is_reverse_style ?? false;
?>
<div class="area-ads-wrap" <?php echo $is_reverse_style ? 'style="transform: skewX(-1deg);"' : ''; ?>>
    <div class="swiper swiper-ads <?php echo $swiper_id; ?>">
        <div class="swiper-wrapper">
            <?php foreach($items as $i => $ads):
                $img_path = ltrim($ads['image_url'] ?? '', '/');
                $ad_img = !empty($ads['image_url']) ? (strpos($ads['image_url'], 'http') === 0 ? $ads['image_url'] : $base_url . $img_path) : '';
                $adJson = htmlspecialchars(json_encode($ads), ENT_QUOTES, 'UTF-8');
                $is_reversed = ($i % 2 !== 0) ? 'ads-reversed' : '';
            ?>
            <div class="swiper-slide area-ads-slide <?php echo $is_reversed; ?>" 
                 data-ad-id="<?php echo $ads['ad_id']; ?>"
                 data-ad-json='<?php echo htmlspecialchars(json_encode($ads, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>'
                 onclick="window.handleAdClick && window.handleAdClick(this)"
                 onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); window.handleAdClick && window.handleAdClick(this); }"
                 role="button"
                 tabindex="0"
                 style="cursor: pointer;">
                <div class="ads-img-bg" style="background-image: url('<?php echo $ad_img; ?>'); <?php echo $is_reverse_style ? 'border-right: 1px solid var(--surface-5);' : ''; ?>"></div>
                <div class="ads-conteudo-box">
                    <span class="badge-ads" <?php echo $is_reverse_style ? 'style="background: #fff; color: #000;"' : ''; ?>>
                        <?php echo $is_reverse_style ? 'Parceiro KALIYE' : 'Oportunidade'; ?>
                    </span>
                    <h4><?php echo htmlspecialchars($ads['title']); ?></h4>
                    <p><?php echo htmlspecialchars($ads['description']); ?></p>
                    <span class="ads-more-link">
                        Ver detalhes <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
