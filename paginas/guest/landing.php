<?php
// paginas/guest/landing.php - Página principal pública da plataforma KALIYE
session_start();
$base_url = '../../';

if (isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Adicionar conexao a base de dados para anuncios reais
require_once '../../configuracoes/base_dados.php';
$db = (new Database())->getConnection();

$anuncios = [];
try {
    // Busca os mesmos anuncios que aparecem para usuarios logados
    $ads_stmt = $db->query("SELECT ad_id, title, description, image_url, link_url, type FROM ads WHERE is_active = true AND (start_date IS NULL OR start_date <= CURRENT_DATE) AND (end_date IS NULL OR end_date >= CURRENT_DATE) ORDER BY RANDOM() LIMIT 5");
    if ($ads_stmt) {
        $anuncios = $ads_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) { /* Silenciar erro */ }

// Fallback: se nao houver anuncios na DB
if (empty($anuncios)) {
    $anuncios = [
        [
            'ad_id' => -1,
            'title' => 'KALIYE Mentoria Premium',
            'description' => 'Acelere o seu sucesso com mentores estrategicos e oportunidades reais de investimento.',
            'image_url' => 'recursos/images/anuncios/ads1.png',
            'link_url' => '../../autenticacao/registar.php',
            'type' => 'banner'
        ],
        [
            'ad_id' => -2,
            'title' => 'Rede de Investimento Estrategico',
            'description' => 'Expanda o seu negocio com capital estrategico dentro do ecossistema KALIYE.',
            'image_url' => 'recursos/images/anuncios/ads2.png',
            'link_url' => '../../autenticacao/registar.php',
            'type' => 'premium'
        ],
        [
            'ad_id' => -3,
            'title' => 'Networking Profissional Luanda',
            'description' => 'Participe no ecossistema de networking profissional da KALIYE.',
            'image_url' => 'recursos/images/anuncios/ads3.png',
            'link_url' => '../../autenticacao/registar.php',
            'type' => 'banner'
        ]
    ];
}

// Versao estavel para cache refresh quando landing, CSS ou componentes mudam.
$v = (string)max(
    filemtime(__FILE__),
    filemtime(__DIR__ . '/../../recursos/css/pages/landing.css'),
    filemtime(__DIR__ . '/../../inclusoes/components/landing_info.php'),
    filemtime(__DIR__ . '/../../inclusoes/components/landing_ads_ticker.php'),
    filemtime(__DIR__ . '/../../inclusoes/components/landing_scripts.php'),
    filemtime(__DIR__ . '/../../recursos/css/user-preferences.css'),
    filemtime(__DIR__ . '/../../recursos/js/user-preferences.js'),
    filemtime(__DIR__ . '/../../recursos/images/landing/mentor_elite_landing.png'),
    filemtime(__DIR__ . '/../../recursos/images/landing/investidor_prime_landing.png'),
    filemtime(__DIR__ . '/../../recursos/images/landing/mentoreado_impacto_landing.png')
);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>KALIYE | A Plataforma de Crescimento Profissional em Angola</title>
    <?php
    require_once '../../inclusoes/components/favicon.php';
    renderKaliyeFavicons($base_url);
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11.1.0/swiper-bundle.min.css">
    <link rel="stylesheet" href="../../recursos/css/pages/landing.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="../../recursos/css/user-preferences.css?v=<?php echo $v; ?>">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11.1.0/swiper-bundle.min.js"></script>
    <script src="../../recursos/js/user-preferences.js?v=<?php echo $v; ?>" defer></script>
    <script>
        // Ponto Central de VariÃ¡veis JS para a Landing Page
        window.BASE_URL = (function() {
            const link = document.createElement('a');
            link.href = '<?php echo $base_url; ?>';
            return link.href.endsWith('/') ? link.href : link.href + '/';
        })();
    </script>
    <style>
        /* CRITICAL PRELOADER CSS */
        #kaliye-preloader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #06090f; z-index: 999999;
            display: flex; align-items: center; justify-content: center;
            transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.8s;
        }
        .preloader-content { text-align: center; width: 100%; max-width: 300px; padding: 20px; }
        .preloader-counter { 
            font-family: 'Outfit', sans-serif; font-size: 2.5rem; font-weight: 900; 
            color: #fff; margin-bottom: 15px; letter-spacing: -1px;
        }
        .preloader-counter span { color: #f7941d; }
        .preloader-bar { width: 100%; height: 2px; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden; }
        .bar-fill { width: 0%; height: 100%; background: linear-gradient(90deg, #f7941d, #ffb347); transition: width 0.3s ease; }
        body.loaded #kaliye-preloader { opacity: 0; visibility: hidden; pointer-events: none; }
        .landing-content-fade { opacity: 0; transform: translateY(20px); transition: all 1s ease-out; }
        body.loaded .landing-content-fade { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body class="no-js">
    <!-- PRELOADER KALIYE -->
    <div id="kaliye-preloader">
        <div class="preloader-content">
            <div class="preloader-counter">
                <span id="load-count">0</span>%
            </div>
            <div class="preloader-bar">
                <div id="load-bar-fill" class="bar-fill"></div>
            </div>
            <p style="color: rgba(255,255,255,0.3); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 2px; margin-top: 20px; font-weight: 800;">
                Sincronizando Ecossistema...
            </p>
        </div>
    </div>

    <div class="landing-content-fade">
    <?php include '../../inclusoes/components/landing_header.php'; ?>
    <?php include '../../inclusoes/components/landing_hero.php'; ?>
    <div class="container-secao ticker-anuncios" id="anuncios">
        <?php $items = $anuncios; include '../../inclusoes/components/landing_ads_ticker.php'; ?>
    </div>
    <?php include '../../inclusoes/components/landing_features.php'; ?>
    <div class="container-secao ticker-anuncios">
        <?php $items = array_reverse($anuncios); $is_reverse_style = true; include '../../inclusoes/components/landing_ads_ticker.php'; ?>
    </div>
    <?php include '../../inclusoes/components/landing_info.php'; ?>
    <div class="container-secao ticker-anuncios" style="margin-bottom: 6rem;">
        <?php $items = $anuncios; $is_reverse_style = false; include '../../inclusoes/components/landing_ads_ticker.php'; ?>
    </div>
    </div> <!-- End landing-content-fade -->

    <?php include '../../inclusoes/components/landing_footer.php'; ?>
    <?php include '../../inclusoes/components/ad_modal.php'; ?>
    <?php include '../../inclusoes/components/legal_modal.php'; ?>
    <?php include '../../inclusoes/components/landing_scripts.php'; ?>

    <script>
        /**
         * AKSANTI ELITE PRELOADER ENGINE
         * Gerencia a contagem e a revelaÃ§Ã£o do ecossistema.
         */
        document.addEventListener('DOMContentLoaded', () => {
            const countEl = document.getElementById('load-count');
            const barEl = document.getElementById('load-bar-fill');
            let count = 0;
            
            // Velocidade da contagem (mais lenta no inÃ­cio, rÃ¡pida no fim)
            const interval = setInterval(() => {
                count += Math.floor(Math.random() * 5) + 2;
                
                if (count >= 100) {
                    count = 100;
                    clearInterval(interval);
                    
                    // Pequeno atraso para o utilizador ver o 100%
                    setTimeout(() => {
                        document.body.classList.add('loaded');
                        // Remove a classe no-js para ativar outras interaÃ§Ãµes
                        document.body.classList.remove('no-js');
                    }, 500);
                }
                
                countEl.innerText = count;
                barEl.style.width = count + '%';
            }, 80);
        });

        // Fail-safe: Se a DOM demorar muito, revela o site aos 5 segundos
        setTimeout(() => {
            if (!document.body.classList.contains('loaded')) {
                document.body.classList.add('loaded');
            }
        }, 5000);
    </script>
</body>
</html>
