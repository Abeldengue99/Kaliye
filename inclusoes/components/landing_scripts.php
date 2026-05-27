<script>
    document.body.classList.remove('no-js');

    const trackedViews = new Set();
    const adObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const adId = entry.target.getAttribute('data-ad-id');
                if (adId && !trackedViews.has(adId)) {
                    trackAdView(parseInt(adId));
                    // Uma vez visto, não precisamos observar mais este elemento nesta sessão
                    adObserver.unobserve(entry.target);
                }
            }
        });
    }, { threshold: 0.5 }); // 50% do anúncio deve estar visível

    function initAdObserver() {
        document.querySelectorAll('[data-ad-id]').forEach(el => adObserver.observe(el));
    }

    function trackLandingVisibleAd(swiper) {
        const activeSlide = swiper && swiper.slides ? swiper.slides[swiper.activeIndex] : null;
        const adId = activeSlide ? parseInt(activeSlide.getAttribute('data-ad-id'), 10) : 0;
        if (adId) trackAdView(adId);
    }

    function trackAdView(adId) {
        if (!adId || adId < 0 || trackedViews.has(adId.toString())) return;
        trackedViews.add(adId.toString());
        
        fetch(BASE_URL + 'interface_programacao/ads/track_ad_metric.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ad_id=${adId}&metric_type=view`
        }).catch(() => trackedViews.delete(adId.toString()));
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Hero Swiper
        if (document.querySelector('#heroSwiper')) {
            new Swiper('#heroSwiper', {
                loop: true,
                effect: 'fade',
                fadeEffect: { crossFade: true },
                speed: 1200,
                autoplay: { delay: 5000, disableOnInteraction: false },
                pagination: { el: '.swiper-pagination', clickable: true },
            });
        }

        // Ads Swiper - Dinâmico para Mobile
        const isMobile = window.innerWidth <= 992;
        if (document.querySelectorAll('.swiper-ads').length > 0) {
            new Swiper('.swiper-ads', {
                direction: isMobile ? 'horizontal' : 'vertical',
                loop: true,
                autoplay: { delay: 4000, disableOnInteraction: false },
                speed: 1000,
                on: {
                    init: function() { trackLandingVisibleAd(this); },
                    slideChange: function() { trackLandingVisibleAd(this); }
                }
            });
        }
        initAdObserver();

        window.addEventListener('scroll', () => {
            document.body.classList.toggle('header-scrolled', window.scrollY > 50);
        });
    });

    window.trackAdClick = function(adId) {
        if (!adId || adId < 0) return;
        fetch(BASE_URL + 'interface_programacao/ads/track_ad_metric.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ad_id=${adId}&metric_type=click`
        }).catch(err => console.error('Erro:', err));
    }

    window.handleAdClick = function(el) {
        if (!el) return;
        const adId = el.getAttribute('data-ad-id');
        const adJson = el.getAttribute('data-ad-json');
        try {
            const ad = JSON.parse(adJson);
            window.openAdModal(ad);
        } catch (err) {
            console.error("Erro ao abrir anúncio:", err);
        }
    }

    window.openAdModal = function(ad) {
        const modal = document.getElementById('adModal');
        if (!modal) return;

        let adImg = ad.image_url;
        if(adImg && !adImg.startsWith('http')) adImg = BASE_URL + adImg.replace(/^\//, '');

        document.getElementById('adModalImage').style.backgroundImage = adImg ? `url('${adImg}')` : 'none';
        document.getElementById('adModalTitle').innerText = ad.title;
        document.getElementById('adModalType').innerText = (ad.type || 'OPORTUNIDADE').toUpperCase();
        document.getElementById('adModalDesc').innerText = ad.description;
        
        const btn = document.getElementById('adModalLink');
        const linkText = document.getElementById('adModalLinkText');
        if (btn) {
            if (ad.link_url) {
                const targetUrl = ad.link_url.startsWith('http') ? ad.link_url : new URL(ad.link_url, window.location.href).href;
                btn.href = targetUrl;
                btn.target = "_blank";
                btn.style.display = 'flex';
                btn.innerHTML = '<i class="fas fa-external-link-alt"></i> ACEDER AGORA';
                btn.onclick = () => window.trackAdClick(ad.ad_id);
                if (linkText) {
                    linkText.href = targetUrl;
                    linkText.innerHTML = '<i class="fas fa-user-plus"></i> Atalho rápido: Criar conta';
                    linkText.style.display = 'block';
                }
            } else if (ad.contact_info) {
                const whatsappUrl = "https://wa.me/" + ad.contact_info.replace(/[^0-9]/g, '');
                btn.href = whatsappUrl;
                btn.target = "_blank";
                btn.style.display = 'flex';
                btn.innerHTML = '<i class="fab fa-whatsapp"></i> CONTACTAR AGORA';
                btn.onclick = () => window.trackAdClick(ad.ad_id);
                if (linkText) {
                    linkText.href = whatsappUrl;
                    linkText.innerHTML = '<i class="fab fa-whatsapp"></i> Atalho rápido: Contactar';
                    linkText.style.display = 'block';
                }
            } else { 
                btn.style.display = 'none';
                if (linkText) {
                    linkText.style.display = 'none';
                    linkText.removeAttribute('href');
                    linkText.innerHTML = '';
                }
            }
        }
        
        modal.style.setProperty('display', 'flex', 'important');
        document.body.style.overflow = 'hidden';
    }
</script>
