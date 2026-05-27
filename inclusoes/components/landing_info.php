<?php
$landing_role_asset = static function (string $path) use ($base_url): string {
    $full_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $version = file_exists($full_path) ? filemtime($full_path) : time();
    return $base_url . $path . '?v=' . $version;
};
?>
<section class="secao-dual-roles">
    <div class="container-secao">
        <!-- ROLE 1: O MENTOR -->
        <div class="role-grid mentor-role" id="mentoria" data-aos="fade-up">
            <div class="role-image-box">
                <div class="image-premium-wrap gradient-mentor">
                    <img src="<?php echo $landing_role_asset('recursos/images/landing/mentor_elite_landing.png'); ?>" alt="Mentor" class="role-img">
                    <div class="role-badge">MENTOR</div>
                </div>
            </div>
            <div class="role-content">
                <h2 class="role-title">Transforme Experiência em <span>Legado</span></h2>
                <div class="role-divider"></div>
                <p class="role-desc">
                    Guie a próxima geração e mude o futuro estratégico de Angola. Partilhe o que sabe e deixe a sua marca.
                </p>
                <a href="<?php echo $base_url; ?>autenticacao/registar.php?role=mentor" class="btn-role btn-mentor">QUERO SER MENTOR <i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <!-- ROLE 2: O INVESTIDOR -->
        <div class="role-grid investor-role role-reversed" id="investir" data-aos="fade-up">
            <div class="role-content">
                <h2 class="role-title">Invista no <span>Potencial</span> Nacional</h2>
                <div class="role-divider"></div>
                <p class="role-desc">
                    Conecte-se a projectos inovadores e talentos emergentes. Seja o motor do crescimento económico do país.
                </p>
                <a href="<?php echo $base_url; ?>autenticacao/registar.php?role=investor" class="btn-role btn-investor">QUERO INVESTIR <i class="fas fa-chevron-right"></i></a>
            </div>
            <div class="role-image-box">
                <div class="image-premium-wrap gradient-investor">
                    <img src="<?php echo $landing_role_asset('recursos/images/landing/investidor_prime_landing.png'); ?>" alt="Investidor" class="role-img">
                    <div class="role-badge badge-investor">INVESTIDOR</div>
                    <div class="floating-stat stat-bottom glass">
                        <i class="fas fa-chart-line"></i>
                        <span>Retorno Estratégico</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROLE 3: O MENTOREADO -->
        <div class="role-grid mentee-role" data-aos="fade-up">
            <div class="role-image-box">
                <div class="image-premium-wrap gradient-mentee">
                    <img src="<?php echo $landing_role_asset('recursos/images/landing/mentoreado_impacto_landing.png'); ?>" alt="Mentoreado" class="role-img">
                    <div class="role-badge badge-mentee">MENTOREADO</div>
                    <div class="floating-stat glass">
                        <i class="fas fa-rocket"></i>
                        <span>Crescimento Acelerado</span>
                    </div>
                </div>
            </div>
            <div class="role-content">
                <h2 class="role-title">Acelere Para o <span>Topo</span></h2>
                <div class="role-divider"></div>
                <p class="role-desc">
                    Publique os seus projectos, encontre o mentor ideal e tenha acesso a sessões gratuitas com profissionais de elite para impulsionar o seu projecto ou dúvidas.
                </p>
                <a href="<?php echo $base_url; ?>autenticacao/registar.php?role=univ_student" class="btn-role btn-mentee">QUERO SER MENTOREADO <i class="fas fa-chevron-right"></i></a>
            </div>
        </div>
    </div>
</section>

<style>
.secao-dual-roles {
    padding: 6rem 0;
    overflow: hidden;
    background: radial-gradient(circle at 10% 20%, rgba(247, 148, 29, 0.03) 0%, transparent 40%);
}

.role-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 5rem;
    align-items: center;
    margin-bottom: 8rem;
}

.role-grid.role-reversed {
    grid-template-columns: 1fr 1fr;
}

.role-grid:last-child {
    margin-bottom: 0;
}

.role-image-box {
    position: relative;
    z-index: 5;
}

.image-premium-wrap {
    position: relative;
    border-radius: 20px;
    padding: 0;
    background: none;
    border: none;
    max-width: 380px;
    margin: 0 auto;
}

.image-premium-wrap::before {
    content: '';
    position: absolute;
    inset: -10px;
    border-radius: 30px;
    z-index: -1;
    opacity: 0.15;
    filter: blur(25px);
}

.gradient-mentor::before { background: var(--cor-destaque-laranja); }
.gradient-investor::before { background: #10b981; }
.gradient-mentee::before { background: var(--cor-destaque-dourado); }

.role-img {
    width: 100%;
    height: 340px;
    object-fit: cover;
    border-radius: 15px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    display: block;
}

.role-badge {
    position: absolute;
    top: 20px;
    left: -10px;
    background: var(--cor-destaque-laranja);
    color: white;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 0.55rem;
    font-weight: 900;
    letter-spacing: 1px;
    box-shadow: 0 5px 15px rgba(247,148,29,0.3);
    z-index: 10;
}

.badge-investor { background: #10b981; box-shadow: 0 5px 15px rgba(16,185,129,0.3); left: auto; right: -10px; }
.badge-mentee { background: var(--cor-destaque-dourado); box-shadow: 0 5px 15px rgba(251,191,36,0.3); left: auto; right: -10px; }
.mentee-role .badge-mentee { left: -10px; right: auto; }

.floating-stat {
    position: absolute;
    top: -12px;
    right: 12px;
    padding: 8px 15px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 7px;
    border: 1px solid var(--surface-10);
    background: rgba(13, 22, 40, 0.9);
    backdrop-filter: blur(8px);
    z-index: 11;
}

.stat-bottom { top: auto; bottom: -12px; left: 12px; right: auto; }

.floating-stat i { color: var(--cor-destaque-laranja); font-size: 0.8rem; }
.investor-role .floating-stat i { color: #10b981; }
.mentee-role .floating-stat i { color: var(--cor-destaque-dourado); }
.floating-stat span { color: white; font-weight: 700; font-size: 0.65rem; white-space: nowrap; }

.role-title {
    font-family: 'Outfit', sans-serif;
    font-size: 2.2rem;
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 0.8rem;
    color: white;
}

.role-title span { color: var(--cor-destaque-laranja); }
.investor-role .role-title span { color: #10b981; }
.mentee-role .role-title span { color: var(--cor-destaque-dourado); }

.role-divider {
    width: 35px;
    height: 4px;
    background: var(--cor-destaque-laranja);
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.investor-role .role-divider { background: #10b981; }
.mentee-role .role-divider { background: var(--cor-destaque-dourado); }

.role-desc {
    font-size: 0.95rem;
    color: var(--cor-texto-paragrafo);
    line-height: 1.5;
    margin-bottom: 2rem;
}

.btn-role {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1.6rem;
    border-radius: 8px;
    font-weight: 800;
    text-decoration: none;
    transition: 0.3s;
    font-size: 0.8rem;
}

.btn-mentor { background: var(--cor-destaque-laranja); color: white; }
.btn-investor { background: #10b981; color: white; }
.btn-mentee { background: var(--cor-destaque-dourado); color: white; }

.btn-role:hover { transform: translateY(-2px); filter: brightness(1.1); }

@media (max-width: 992px) {
    .role-grid, .role-grid.role-reversed { grid-template-columns: 1fr; gap: 3rem; text-align: center; }
    .role-content { order: 2; }
    .role-image-box { order: 1; max-width: 360px; margin: 0 auto; }
    .role-divider { margin: 1.5rem auto; }
    .role-title { font-size: 1.8rem; }
    .role-img { height: 300px; }
}
</style>
