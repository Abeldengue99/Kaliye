<?php
/**
 * inclusoes/components/dashboard/dashboard_hero.php
 * Hero Greeting + Stats + Motivational Ticker por Perfil & Dia da Semana
 * 
 * CORREÇÃO (1 Junho 2026): 
 * - Preload das imagens para evitar flash inicial
 * - Animation-fill-mode para suavidade
 * - Estado inicial correto da animação
 */
global $base_url, $greeting_word, $first_name, $user_role, $stat_v1, $stat_l1, $stat_v2, $stat_l2, $stat_v3, $stat_l3;

// --- Frases Motivacionais por Perfil e Dia da Semana ---
// 0=Dom, 1=Seg, 2=Ter, 3=Qua, 4=Qui, 5=Sex, 6=Sáb
$day = (int)date('w');

$motivational = [
    'investor' => [
        0 => ["Cada projecto nacional que apoias", "é um tijolo na construção do país que queres ver."],
        1 => ["Nova semana, novas oportunidades.", "Que projecto angolano merece o teu capital hoje?"],
        2 => ["O dinheiro parado perde valor.", "Investe no ecossistema. Investe no futuro."],
        3 => ["A meio da semana é hora de agir.", "Descobre um projecto e transforma a tua visão em impacto."],
        4 => ["Quinta-feira: revê o teu portfólio.", "Há algum empreendedor nacional à espera do teu apoio."],
        5 => ["Porque não investir em projectos nacionais?", "O retorno vai muito além do financeiro."],
        6 => ["Fim de semana, mente aberta.", "Planeia o próximo grande investimento do ecossistema."],
    ],
    'mentor' => [
        0 => ["O teu conhecimento tem valor.", "Partilhá-lo é o maior legado que podes deixar."],
        1 => ["Começa a semana inspirando alguém.", "Um mentee está à espera da tua orientação."],
        2 => ["Ensinar é aprender duas vezes.", "Partilha o que sabes e cresce com quem orientas."],
        3 => ["Quem precisa da tua experiência hoje?", "Partilha, orienta e deixa a tua marca."],
        4 => ["Quinta-feira: revê as tarefas dos teus mentees.", "O progresso deles é o teu maior sucesso."],
        5 => ["Porque não ensinar aquilo que sabes fazer?", "Cria o teu legado. A comunidade precisa de ti."],
        6 => ["Grandes mentores constroem-se com o tempo.", "Mas começa sempre com uma primeira conversa."],
    ],
    'student' => [
        0 => ["Domingo é o dia de pensar grande.", "Qual é o projecto que vai mudar o teu futuro? Publica agora."],
        1 => ["Nova semana, novas oportunidades.", "Acelera a tua visão. Partilha o teu projecto com a comunidade."],
        2 => ["Um projecto não ganha vida no papel.", "Dá o primeiro passo e publica o teu projecto hoje."],
        3 => ["Grandes projectos precisam de palco.", "Qual é o problema real que vais resolver hoje?"],
        4 => ["Quinta-feira: o ecossistema está à espera.", "O que consegues mudar com um bom projecto?"],
        5 => ["Antes de terminar a semana, age.", "Submete aquele projecto que tens na cabeça há meses."],
        6 => ["O teu maior concorrente és tu do passado.", "Começa a construir o teu futuro publicando um projecto."],
    ],
    'default' => [
        0 => ["Cada domingo é uma página em branco.", "Que história vais escrever esta semana?"],
        1 => ["Segunda-feira: o ecossistema está vivo.", "A tua contribuição faz parte desta história."],
        2 => ["O sucesso constrói-se dia a dia.", "Faz algo hoje que o teu futuro vai agradecer."],
        3 => ["A meio da semana avalia o progresso.", "Ajusta a rota e mantém o foco no destino."],
        4 => ["Quinta-feira: mais perto dos teus objectivos.", "O que ainda falta fazer esta semana?"],
        5 => ["Termina a semana com uma acção concreta.", "A KALIYE está aqui para te apoiar."],
        6 => ["Recarrega energias este fim de semana.", "A semana que vem pede a tua melhor versão."],
    ],
];

$profile_key = in_array($user_role, ['investor', 'mentor', 'student']) ? $user_role : 'default';
[$msg_line1, $msg_line2] = $motivational[$profile_key][$day];

$hero_carousel_images = [
    'recursos/images/dashboard/hero_team_discussion.jpg',
    'recursos/images/dashboard/hero_business_presentation.jpg',
    'recursos/images/dashboard/hero_workplace_collaboration.jpg',
    'recursos/images/dashboard/hero_team_handshake.jpg',
    'recursos/images/dashboard/hero_women_working.jpg',
    'recursos/images/dashboard/hero_team_meeting.jpg',
    'recursos/images/dashboard/hero_startup_office.jpg',
];
$hero_base_url = $base_url ?? './';

// ✅ CORREÇÃO: Injetar preload de imagens diretamente
if (!isset($GLOBALS['hero_preloads_injected'])) {
    $GLOBALS['hero_preloads_injected'] = true;
    echo '<script>';
    echo "(function() {";
    echo "    const preloadImages = () => {";
    foreach ($hero_carousel_images as $img) {
        $img_url = htmlspecialchars($hero_base_url . $img, ENT_QUOTES, 'UTF-8');
        echo "        const link = document.createElement('link');";
        echo "        link.rel = 'preload';";
        echo "        link.as = 'image';";
        echo "        link.href = '" . $img_url . "';";
        echo "        document.head.appendChild(link);";
    }
    echo "    };";
    echo "    preloadImages();"; // Otimização: pré-carrega de imediato
    echo "})();";
    echo '</script>';
}
?>

<style>
.dashboard-hero-section {
    position: relative;
    isolation: isolate;
    overflow: visible;
    min-height: 280px; /* Previne layout shifts */
}

.dashboard-hero-section::before {
    content: "";
    position: absolute;
    top: -190px;
    bottom: -90px;
    left: calc(50% - 50vw);
    right: calc(50% - 50vw);
    z-index: -4;
    background:
        radial-gradient(circle at 72% 24%, rgba(59, 130, 246, 0.16), transparent 28%),
        radial-gradient(circle at 86% 74%, rgba(16, 185, 129, 0.09), transparent 24%),
        linear-gradient(115deg, rgba(247, 148, 29, 0.10), transparent 36%),
        #030814;
}

.dashboard-hero-section::after {
    content: "";
    position: absolute;
    top: -170px;
    bottom: -90px;
    left: calc(50% - 50vw);
    right: calc(50% - 50vw);
    z-index: -2;
    background-image:
        linear-gradient(rgba(255,255,255,0.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.035) 1px, transparent 1px);
    background-size: 84px 84px;
    mask-image: linear-gradient(90deg, rgba(0,0,0,0.05), #000 18%, #000 82%, rgba(0,0,0,0.05));
    opacity: 0.32;
    animation: heroGridDrift 28s linear infinite;
}

.dashboard-hero-bg-carousel {
    position: absolute;
    top: -190px;
    bottom: -90px;
    left: calc(50% - 50vw);
    right: calc(50% - 50vw);
    z-index: -3;
    pointer-events: none;
    overflow: hidden;
    background: #030814;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.5s ease-in-out, visibility 0.5s ease-in-out;
}

.dashboard-hero-bg-carousel.carousel-ready {
    opacity: 1;
    visibility: visible;
}

.dashboard-hero-bg-track {
    position: absolute;
    inset: 0;
    display: flex;
    width: max-content;
    min-width: 200%;
    gap: clamp(1rem, 2vw, 2rem);
    padding: 0 clamp(1rem, 2vw, 2rem);
    opacity: 0.22;
    filter: saturate(0.78) contrast(1.04) brightness(0.56);
    transform: translate3d(0, 0, 0);
    animation: heroBgTrack 54s linear infinite;
    animation-play-state: paused; /* Inicia pausada e aguarda inicialização segura */
    animation-fill-mode: forwards;
    will-change: transform;
    -webkit-font-smoothing: antialiased;
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
}

.dashboard-hero-bg-carousel.carousel-ready .dashboard-hero-bg-track {
    animation-play-state: running; /* Ativa autoplay apenas após prontidão */
}

.dashboard-hero-bg-carousel::before,
.dashboard-hero-bg-carousel::after {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -3;
    pointer-events: none;
}

.dashboard-hero-bg-carousel::before {
    background:
        linear-gradient(90deg, rgba(3,8,20,0.98) 0%, rgba(3,8,20,0.82) 28%, rgba(3,8,20,0.68) 62%, rgba(3,8,20,0.9) 100%),
        linear-gradient(180deg, rgba(3,8,20,0.98) 0%, rgba(3,8,20,0.58) 42%, rgba(3,8,20,0.96) 100%);
}

.dashboard-hero-bg-carousel::after {
    background:
        radial-gradient(circle at 78% 46%, rgba(59,130,246,0.13), transparent 28%),
        radial-gradient(circle at 86% 78%, rgba(16,185,129,0.10), transparent 24%),
        linear-gradient(115deg, rgba(247,148,29,0.12), transparent 34%);
    mix-blend-mode: screen;
    opacity: 0.82;
}

.dashboard-hero-bg-slide {
    flex: 0 0 clamp(360px, 34vw, 560px);
    height: 100%;
    margin: 0;
    transform: skewX(-6deg);
    overflow: hidden;
    background: #1a1a2e;
}

.dashboard-hero-bg-slide img {
    width: 116%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transform: translateX(-7%) skewX(6deg) scale(1.04);
    opacity: 0.95;
    display: block;
    will-change: transform;
    -webkit-font-smoothing: antialiased;
    image-rendering: auto;
    image-rendering: -webkit-optimize-contrast;
    backface-visibility: hidden;
}

.dashboard-hero-container {
    position: relative;
    z-index: 2;
    width: 100%;
}

.dashboard-hero-ambient {
    position: absolute;
    top: -170px;
    bottom: -90px;
    left: calc(50% - 50vw);
    right: calc(50% - 50vw);
    z-index: -1;
    pointer-events: none;
    overflow: hidden;
}

.dashboard-hero-ambient::before {
    content: "";
    position: absolute;
    width: min(68rem, 78vw);
    height: min(68rem, 78vw);
    right: 6%;
    top: 2%;
    border: 1px solid rgba(247, 148, 29, 0.12);
    border-radius: 50%;
    transform: rotateX(62deg) rotateZ(-18deg);
    animation: heroRingBreath 9s ease-in-out infinite;
}

.ambient-line {
    position: absolute;
    height: 1px;
    width: min(44vw, 660px);
    background: linear-gradient(90deg, transparent, rgba(247,148,29,0.42), rgba(59,130,246,0.22), transparent);
    transform-origin: left center;
    opacity: 0.45;
}

.line-a { right: 7%; top: 23%; transform: rotate(-16deg); animation: heroLineTravel 8s ease-in-out infinite; }
.line-b { right: 0; top: 48%; transform: rotate(12deg); animation: heroLineTravel 10s ease-in-out infinite 1.4s; }
.line-c { right: 13%; top: 74%; transform: rotate(-6deg); animation: heroLineTravel 9s ease-in-out infinite 2.2s; }

.ambient-node {
    position: absolute;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #f7941d;
    box-shadow: 0 0 0 8px rgba(247,148,29,0.08), 0 0 26px rgba(247,148,29,0.55);
    opacity: 0.85;
    animation: heroNodePulse 4.8s ease-in-out infinite;
}

.node-a { right: 18%; top: 20%; }
.node-b { right: 39%; top: 42%; background: #3b82f6; box-shadow: 0 0 0 8px rgba(59,130,246,0.07), 0 0 24px rgba(59,130,246,0.5); animation-delay: 0.8s; }
.node-c { right: 12%; top: 62%; background: #10b981; box-shadow: 0 0 0 8px rgba(16,185,129,0.07), 0 0 24px rgba(16,185,129,0.48); animation-delay: 1.6s; }
.node-d { right: 30%; top: 78%; animation-delay: 2.4s; }

.btn-publish-idea {
    position: relative;
    overflow: hidden;
}

.btn-publish-idea::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(110deg, transparent 0%, rgba(255,255,255,0.28) 45%, transparent 70%);
    transform: translateX(-120%);
    animation: heroButtonSheen 4.5s ease-in-out infinite;
}

.btn-publish-idea > * {
    position: relative;
    z-index: 1;
}

@keyframes msgFadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes heroGridDrift {
    from { background-position: 0 0, 0 0; }
    to { background-position: 84px 84px, 84px 84px; }
}

@keyframes heroBgTrack {
    0% { 
        transform: translate3d(0, 0, 0);
        opacity: 0.22;
    }
    50% { 
        transform: translate3d(-25%, 0, 0);
        opacity: 0.22;
    }
    100% { 
        transform: translate3d(-50%, 0, 0);
        opacity: 0.22;
    }
}

@keyframes heroRingBreath {
    0%, 100% { opacity: 0.18; transform: rotateX(62deg) rotateZ(-18deg) scale(0.96); }
    50% { opacity: 0.34; transform: rotateX(62deg) rotateZ(-18deg) scale(1.04); }
}

@keyframes heroLineTravel {
    0%, 100% { opacity: 0.15; filter: blur(0); clip-path: inset(0 92% 0 0); }
    45%, 55% { opacity: 0.62; filter: blur(0.2px); clip-path: inset(0 0 0 0); }
}

@keyframes heroNodePulse {
    0%, 100% { transform: scale(0.78); opacity: 0.48; }
    50% { transform: scale(1.2); opacity: 1; }
}

@keyframes heroButtonSheen {
    0%, 48% { transform: translateX(-130%); }
    72%, 100% { transform: translateX(130%); }
}

@media (max-width: 900px) {
    .dashboard-hero-section {
        margin-top: 2rem;
        min-height: 480px; /* Altura maior para acomodar wrap de flex no mobile */
    }

    .dashboard-hero-container {
        padding-top: 0.5rem;
    }

    .dashboard-hero-bg-track {
        gap: 0.85rem;
        opacity: 0.18;
        animation-duration: 42s;
    }

    .dashboard-hero-bg-slide {
        flex-basis: clamp(260px, 66vw, 380px);
    }

    .dashboard-hero-ambient::before,
    .ambient-line,
    .ambient-node {
        opacity: 0.45;
    }
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-hero-section::after,
    .dashboard-hero-bg-track,
    .dashboard-hero-ambient::before,
    .ambient-line,
    .ambient-node,
    .btn-publish-idea::after {
        animation: none !important;
    }

    .dashboard-hero-bg-track {
        transform: translate3d(0, 0, 0);
    }
}
</style>

<div style="margin-bottom: 4rem;" class="dashboard-hero-section" data-aos="fade-down">
    <!-- Proteção contra FOUC e empilhamento vertical com styles inline críticos -->
    <div class="dashboard-hero-bg-carousel" id="hero-carousel" style="position: absolute; top: -190px; bottom: -90px; left: calc(50% - 50vw); right: calc(50% - 50vw); z-index: -3; pointer-events: none; overflow: hidden; background: #030814; opacity: 0; visibility: hidden; transition: opacity 0.5s ease-in-out, visibility 0.5s ease-in-out;" aria-hidden="true">
        <div class="dashboard-hero-bg-track">
            <?php for ($loop = 0; $loop < 2; $loop++): ?>
                <?php foreach ($hero_carousel_images as $index => $image_path): ?>
                    <figure class="dashboard-hero-bg-slide" data-index="<?php echo $index; ?>">
                        <img src="<?php echo htmlspecialchars($hero_base_url . $image_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="eager" decoding="async" class="hero-carousel-img">
                    </figure>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </div>
    <div class="dashboard-hero-ambient" aria-hidden="true">
        <span class="ambient-line line-a"></span>
        <span class="ambient-line line-b"></span>
        <span class="ambient-line line-c"></span>
        <span class="ambient-node node-a"></span>
        <span class="ambient-node node-b"></span>
        <span class="ambient-node node-c"></span>
        <span class="ambient-node node-d"></span>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 1px solid var(--surface-5); padding-top: 3.5rem; padding-bottom: 3.5rem; flex-wrap: wrap; gap: 3rem;" class="dashboard-hero-container">
        
        <!-- SAUDAÇÃO -->
        <div style="flex: 1; min-width: 320px;" class="dashboard-hero-welcome">
            <h1 style="font-size: clamp(2.2rem, 5vw, 4rem); font-weight: 950; color: #fff; line-height: 1; margin: 0; letter-spacing: -2.5px;" class="dashboard-hero-title">
                <?php echo $greeting_word; ?>, <span style="color: var(--elite-orange);" data-aos="fade-left" data-aos-delay="200"><?php echo htmlspecialchars($first_name); ?>.</span>
            </h1>
            
            <!-- MENSAGEM MOTIVACIONAL DO DIA -->
            <div style="margin-top: 1.4rem; animation: msgFadeUp 0.8s ease both; animation-delay: 0.3s; opacity: 0; animation-fill-mode: forwards;" class="dashboard-hero-msg">
                <p style="margin: 0; font-size: 0.82rem; font-weight: 600; color: var(--surface-40); letter-spacing: 0.3px; line-height: 1.6;">
                    <?php echo htmlspecialchars($msg_line1); ?>
                </p>
                <p style="margin: 3px 0 0; font-size: 0.82rem; font-weight: 700; color: var(--elite-orange); letter-spacing: 0.3px; line-height: 1.6;">
                    <?php echo htmlspecialchars($msg_line2); ?>
                </p>
            </div>
            
            <!-- BOTÃO DE ACÇÃO RÁPIDA -->
            <?php
            $user_types_hero = strtolower($_SESSION['user_type'] ?? '');
            $is_mentor_only_hero = (strpos($user_types_hero, 'mentor') !== false || strpos($user_types_hero, 'especialista') !== false) 
                              && strpos($user_types_hero, 'estudante') === false 
                              && strpos($user_types_hero, 'investidor') === false
                              && strpos($user_types_hero, 'admin') === false;
            if (!$is_mentor_only_hero): 
            ?>
            <button onclick="window.openPostModal()" class="btn-publish-idea" data-aos="zoom-in" data-aos-delay="600" style="margin-top: 2.5rem; background: var(--elite-orange); color: #fff; border: none; padding: 1.1rem 3rem; border-radius: 20px; font-size: 0.75rem; font-weight: 950; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; box-shadow: 0 10px 40px rgba(247, 148, 29, 0.3); transition: 0.4s; display: flex; align-items: center; gap: 15px;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fas fa-plus" style="background: rgba(255,255,255,0.2); padding: 5px; border-radius: 6px;"></i> Publicar Projecto
            </button>
            <?php endif; ?>
        </div>
        
        <!-- ESTATÍSTICAS DINÂMICAS POR PERFIL -->
        <div style="display: flex; gap: 4rem; align-items: center; padding: 0 1.5rem 0.5rem;" class="stats-grid">
            <div style="text-align: center;" data-aos="fade-up" data-aos-delay="300">
                <div style="font-size: 3rem; font-weight: 950; color: #fff; line-height: 0.8; letter-spacing: -2px;"><?php echo $stat_v1; ?></div>
                <div style="font-size: 0.55rem; font-weight: 900; color: var(--surface-20); text-transform: uppercase; margin-top: 12px; letter-spacing: 2.5px;"><?php echo $stat_l1; ?></div>
            </div>
            <div style="width: 1px; height: 50px; background: rgba(255,255,255,0.06);" data-aos="fade" data-aos-delay="400"></div>
            <div style="text-align: center;" data-aos="fade-up" data-aos-delay="450">
                <div style="font-size: 3rem; font-weight: 950; color: #fff; line-height: 0.8; letter-spacing: -2px;"><?php echo $stat_v2; ?></div>
                <div style="font-size: 0.55rem; font-weight: 900; color: var(--surface-20); text-transform: uppercase; margin-top: 12px; letter-spacing: 2.5px;"><?php echo $stat_l2; ?></div>
            </div>
            <div style="width: 1px; height: 50px; background: rgba(255,255,255,0.06);" data-aos="fade" data-aos-delay="550"></div>
            <div style="text-align: center;" data-aos="fade-up" data-aos-delay="600">
                <div style="font-size: 3rem; font-weight: 950; color: #fff; line-height: 0.8; letter-spacing: -2px;"><?php echo $stat_v3; ?></div>
                <div style="font-size: 0.55rem; font-weight: 900; color: var(--surface-20); text-transform: uppercase; margin-top: 12px; letter-spacing: 2.5px;"><?php echo $stat_l3; ?></div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * ============================================================================
 * SISTEMA DE INICIALIZAÇÃO DE SEGURANÇA E AUTO-RECUPERAÇÃO DO CAROUSEL (DASHBOARD HERO)
 * ============================================================================
 * 
 * ⚠️ ATENÇÃO: NÃO REMOVER OU ALTERAR ESTE MECANISMO SEM NOVA VALIDAÇÃO COMPLETA.
 * 
 * 🔍 DOCUMENTAÇÃO DO COMPORTAMENTO E EVOLUÇÃO (PRODUÇÃO):
 * 
 * 1. O BUG ORIGINAL:
 *    Durante o carregamento (principalmente em dispositivos móveis, refreshs rápidos
 *    ou rede instável), o CSS era processado após as imagens HTML do carrossel. Como
 *    as imagens tinham 'loading="eager"', elas renderizavam empilhadas verticalmente
 *    no fluxo nativo antes do slider inicializar, causando FOUC (Flash of Unstyled Content).
 * 
 * 2. PORQUE O CAROUSEL INICIA OCULTO:
 *    Aplicamos estilos inline síncronos críticos diretamente no markup HTML do container
 *    ('.dashboard-hero-bg-carousel') definindo 'opacity: 0; visibility: hidden;'. Isso
 *    garante que o carrossel permaneça 100% invisível no exato milissegundo de parsing
 *    do DOM, evitando qualquer cintilação visual ou layout shift indesejado.
 * 
 * 3. PORQUE OS ESTILOS INLINE SÃO SOBRESCRITOS VIA JAVASCRIPT:
 *    Estilos inline possuem precedência/especificidade superior a qualquer regra de classe
 *    CSS (como '.carousel-ready'). Portanto, a classe CSS sozinha não conseguia tornar o
 *    carrossel visível. É obrigatório sobrescrever diretamente no DOM via JS as propriedades
 *    'opacity' e 'visibility' (Abordagem B) no momento da inicialização segura.
 * 
 * 4. SEGURANÇA E PREVENÇÃO DE REGRESSÕES:
 *    O script monitora dinamicamente a prontidão do CSS, carregamento das imagens em cache/rede,
 *    trata erros individuais de 404 (removendo slides redundantes sem quebrar o infinite loop)
 *    e provê um mecanismo de auto-recuperação (timeouts de 2.5s e 4.5s) que força o fallback.
 *    Alterações neste fluxo podem quebrar a estabilidade ou falhar nos testes automatizados.
 * ============================================================================
 */
(function() {
    const FIRST_TIMEOUT = 2500; // Primeira tentativa após 2.5s
    const RECOVERY_TIMEOUT = 4500; // Segunda tentativa (auto-recuperação) após 4.5s
    const startTime = performance.now();
    
    // Evitar múltiplas inicializações
    if (window.__heroCarouselInitialized) {
        console.log("[DashboardHero Carousel] Já inicializado anteriormente.");
        return;
    }
    window.__heroCarouselInitialized = true;

    console.log("[DashboardHero Carousel] Iniciando auditoria e preparação...");

    const carousel = document.querySelector('.dashboard-hero-bg-carousel');
    const track = document.querySelector('.dashboard-hero-bg-track');
    if (!carousel || !track) {
        console.error("[DashboardHero Carousel] Falha crítica: Elementos do carousel não encontrados no DOM.");
        return;
    }

    const images = Array.from(track.querySelectorAll('.hero-carousel-img'));
    if (images.length === 0) {
        console.warn("[DashboardHero Carousel] Nenhuma imagem encontrada no track.");
        return;
    }

    let isInitialized = false;
    let failedIndices = new Set();

    // Função de inicialização final do slider
    function initSlider() {
        if (isInitialized) return;
        isInitialized = true;

        const duration = (performance.now() - startTime).toFixed(1);
        console.log(`[DashboardHero Carousel] Inicialização completa e segura concluída em ${duration}ms.`);
        
        // Sobrescrever os estilos inline de ocultação (Abordagem B)
        carousel.style.opacity = '1';
        carousel.style.visibility = 'visible';
        
        // Ativação do autoplay e estado final do componente
        carousel.classList.add('carousel-ready');
        console.log("[DashboardHero Carousel] Autoplay ativado. Slider visível.");
    }

    // Função para verificar se o CSS está carregado e aplicado
    function checkCssApplied() {
        const computed = window.getComputedStyle(carousel);
        const cssLoaded = computed.position === 'absolute' && computed.overflow === 'hidden';
        if (cssLoaded) {
            console.log("[DashboardHero Carousel] Validação do CSS: Carregado e ativo.");
            return true;
        }
        return false;
    }

    // Função para verificar se todas as imagens terminaram de carregar
    function checkCompletion() {
        if (isInitialized) return;

        const activeImages = Array.from(track.querySelectorAll('.hero-carousel-img'));
        const pendingImages = activeImages.filter(img => !img.complete);
        
        if (pendingImages.length === 0) {
            console.log(`[DashboardHero Carousel] Todas as imagens ativas foram processadas com sucesso.`);
            if (checkCssApplied()) {
                initSlider();
            } else {
                let attempts = 0;
                const cssPoll = setInterval(() => {
                    attempts++;
                    if (checkCssApplied() || attempts > 10) {
                        clearInterval(cssPoll);
                        initSlider();
                    }
                }, 50);
            }
        }
    }

    // Tratar erro em imagem
    function handleImageError(img, reason = "Erro de rede / 404") {
        const slide = img.closest('.dashboard-hero-bg-slide');
        if (!slide) return;
        
        const index = slide.getAttribute('data-index');
        if (index !== null && !failedIndices.has(index)) {
            failedIndices.add(index);
            console.warn(`[DashboardHero Carousel] Falha detectada na imagem [índice: ${index}]: ${img.src}. Motivo: ${reason}. Removendo slides redundantes.`);
            
            // Remove todos os slides correspondentes a este índice nos dois loops
            const slidesToRemove = track.querySelectorAll(`.dashboard-hero-bg-slide[data-index="${index}"]`);
            slidesToRemove.forEach(s => s.remove());
        }
        checkCompletion();
    }

    // Configurar listeners iniciais
    images.forEach(img => {
        if (img.complete) {
            if (img.naturalWidth === 0) {
                handleImageError(img, "Carregamento incompleto (naturalWidth = 0)");
            } else {
                checkCompletion();
            }
        } else {
            img.addEventListener('load', () => {
                console.log(`[DashboardHero Carousel] Imagem carregada: ${img.getAttribute('src')}`);
                checkCompletion();
            });
            img.addEventListener('error', () => {
                handleImageError(img);
            });
        }
    });

    // Primeira Tentativa: Timeout de Segurança
    setTimeout(() => {
        if (isInitialized) return;
        
        const activeImages = Array.from(track.querySelectorAll('.hero-carousel-img'));
        const pendingUrls = activeImages.filter(img => !img.complete).map(img => img.src);
        
        console.warn(`[DashboardHero Carousel] Primeira tentativa falhou. Motivo: ${pendingUrls.length} imagem(ns) ainda pendente(s). Agendando recuperação automática...`, pendingUrls);
    }, FIRST_TIMEOUT);

    // Segunda Tentativa: Auto-recuperação (Recovery Mechanism)
    setTimeout(() => {
        if (isInitialized) return;
        
        console.error("[DashboardHero Carousel] Segunda tentativa (Auto-recuperação) iniciada devido a atraso excessivo.");
        
        const activeImages = Array.from(track.querySelectorAll('.hero-carousel-img'));
        activeImages.forEach(img => {
            if (!img.complete || img.naturalWidth === 0) {
                handleImageError(img, "Timeout de carregamento (Auto-recuperação)");
            }
        });
        
        initSlider();
    }, RECOVERY_TIMEOUT);

})();
</script>
