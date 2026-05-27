<?php

?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ——— Helper: rastrear anúncio visível ———
    function trackVisibleAd(swiper) {
        const activeSlide = swiper.slides[swiper.activeIndex];
        if (!activeSlide) return;
        const adId = activeSlide.getAttribute('data-ad-id');
        if (adId && adId > 0 && typeof window.trackAdView === 'function') {
            window.trackAdView(parseInt(adId));
        }
    }

    // ——— Hero Ads (fade) — apenas para o swiper do hero, se existir ———
    if (document.querySelector('.swiper-dashboard-ads')) {
        new Swiper('.swiper-dashboard-ads', {
            loop:       true,
            effect:     'fade',
            speed:      800,
            autoplay:   { delay: 4000, disableOnInteraction: false },
            fadeEffect: { crossFade: true },
            pagination: { el: '.swiper-pagination', clickable: true },
        });
    }

    // NOTA: .swiper-ads e .swiper-ads-bottom são inicializados em index_scripts.php
    // com delegação de eventos Swiper-safe. Não re-inicializar aqui.

});
</script>
