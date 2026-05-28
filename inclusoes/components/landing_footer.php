<?php
$footer_user_logged_in = isset($_SESSION['user_id']);
$footer_home_url = $footer_user_logged_in ? ($base_url . 'index.php') : ($base_url . 'paginas/guest/landing.php');
// landing_footer.php - Rodapé da landing page
?>
<footer class="rodape-principal">
    <div class="container-secao">
        <div class="rodape-layout">
            <div class="rodape-marca">
                <a href="<?php echo $footer_home_url; ?>" class="marca-box rodape-marca-box">
                    <div class="logo-icon-premium">
                        <img src="<?php echo $base_url; ?>recursos/images/marca/logotipo.png" alt="KALIYE Logo">
                    </div>
                    <span class="rodape-marca-nome">KALIYE</span>
                </a>
                <p class="rodape-descrição">
                    A KALIYE é uma plataforma criada para aproximar pessoas, conhecimento e oportunidades. Aqui, talentos,
                    mentores, empreendedores e investidores encontram um espaço digital para desenvolver projectos, fortalecer
                    competências, construir relações profissionais e transformar projectos em impacto real para Angola.
                </p>
                <form class="kaliye-newsletter" data-kaliye-newsletter>
                    <input class="kaliye-newsletter-input" type="text" name="name" placeholder="O teu nome" autocomplete="name" required>
                    <input class="kaliye-newsletter-input" type="email" name="email" placeholder="O teu e-mail" autocomplete="email" required>
                    <button class="kaliye-newsletter-btn" type="submit">
                        <i class="fas fa-paper-plane"></i> Subscrever
                    </button>
                </form>
                <p class="rodape-newsletter-hint">Recebe novidades e oportunidades sem perder o ritmo.</p>

                <div class="kaliye-preferences-panel" aria-label="Preferencias de apresentacao">
                    <div class="kaliye-pref-group">
                        <label class="kaliye-pref-label" for="kaliyeFooterLang">Idioma</label>
                        <select id="kaliyeFooterLang" class="kaliye-pref-select" data-pref-lang>
                            <option value="pt">Português</option>
                            <option value="en">English</option>
                            <option value="fr">Français</option>
                            <option value="es">Español</option>
                        </select>
                    </div>
                    <div class="kaliye-pref-group">
                        <span class="kaliye-pref-label">Brilho</span>
                        <div class="kaliye-pref-row">
                            <button class="kaliye-pref-btn" type="button" data-pref-brightness="calm">Suave</button>
                            <button class="kaliye-pref-btn" type="button" data-pref-brightness="normal">Normal</button>
                            <button class="kaliye-pref-btn" type="button" data-pref-brightness="bright">Alto</button>
                        </div>
                    </div>
                    <?php if (!$footer_user_logged_in): ?>
                    <div class="kaliye-pref-group">
                        <span class="kaliye-pref-label">Modo</span>
                        <div class="kaliye-pref-row">
                            <button class="kaliye-pref-btn" type="button" data-pref-theme="auto">Automático</button>
                            <button class="kaliye-pref-btn" type="button" data-pref-theme="dark">Escuro</button>
                            <button class="kaliye-pref-btn" type="button" data-pref-theme="light">Claro</button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="kaliye-pref-group">
                        <span class="kaliye-pref-label">Cor</span>
                        <div class="kaliye-pref-row" data-no-translate>
                            <button class="kaliye-pref-btn kaliye-color-btn" type="button" data-pref-accent="orange" style="--swatch:#f7941d" aria-label="Laranja"></button>
                            <button class="kaliye-pref-btn kaliye-color-btn" type="button" data-pref-accent="blue" style="--swatch:#3b82f6" aria-label="Azul"></button>
                            <button class="kaliye-pref-btn kaliye-color-btn" type="button" data-pref-accent="green" style="--swatch:#10b981" aria-label="Verde"></button>
                            <button class="kaliye-pref-btn kaliye-color-btn" type="button" data-pref-accent="rose" style="--swatch:#f43f5e" aria-label="Rosa"></button>
                            <button class="kaliye-pref-btn kaliye-color-btn" type="button" data-pref-accent="violet" style="--swatch:#8b5cf6" aria-label="Violeta"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Direitos: Fundo Branco / Letras Pretas (Estilo Index) -->
    <div class="rodape-direitos">
        <p>© <?php echo date('Y'); ?> <strong>KALIYE</strong>. Todos os direitos reservados.</p>
        <div class="legal-links">
            <a href="javascript:void(0)" onclick="openLegalModal('termos')">Termos</a>
            <span>•</span>
            <a href="javascript:void(0)" onclick="openLegalModal('privacidade')">Privacidade</a>
        </div>
    </div>
</footer>

<style>
.rodape-principal {
    background: #030712;
    padding: 6rem 0 0;
    border-top: 1px solid var(--cor-bordas-vidro);
}
.rodape-principal .container-secao {
    width: min(1120px, calc(100% - 2rem));
    max-width: 1120px;
    margin: 0 auto;
    padding: 0;
}
.rodape-layout {
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
}
.rodape-marca {
    width: 100%;
    max-width: 760px;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.rodape-marca-box {
    margin-bottom: 1.5rem;
    display: inline-flex;
    justify-content: center;
}
.rodape-marca-box .logo-icon-premium {
    width: 56px;
    height: 56px;
    flex: 0 0 56px;
    background: #ffffff;
    border: 1px solid rgba(16, 24, 40, 0.08);
    box-shadow: 0 14px 32px rgba(0,0,0,0.18);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.rodape-marca-box .logo-icon-premium img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: contain;
    object-position: center;
}
.rodape-marca-nome {
    font-family: 'Outfit', sans-serif;
    color: #ffffff;
    font-size: 1.7rem;
    font-weight: 900;
    letter-spacing: 0;
}
.rodape-descrição {
    color: var(--cor-texto-paragrafo);
    font-size: 1rem;
    line-height: 1.8;
    max-width: 680px;
    margin-left: auto;
    margin-right: auto;
}
.rodape-newsletter-hint {
    margin: 0.8rem 0 0;
    color: rgba(255,255,255,0.42);
    font-size: 0.82rem;
    line-height: 1.5;
}
.rodape-direitos {
    background: #ffffff;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2rem;
    font-size: 0.75rem;
    color: #000000;
    font-weight: 600;
    width: 100%;
    margin-top: 4rem;
}
.rodape-direitos p { margin: 0; }
.legal-links { display: flex; gap: 1rem; align-items: center; }
.legal-links a { 
    color: #000000; 
    text-decoration: none; 
    font-weight: 700; 
    transition: 0.25s; 
}
.legal-links a:hover { color: var(--cor-destaque-laranja); }

@media (max-width: 900px) {
    .rodape-principal { padding-top: 4rem; }
    .rodape-descrição { font-size: 0.95rem; }
    .kaliye-newsletter {
        width: min(calc(100% - 1.5rem), 340px);
        margin-left: auto;
        margin-right: auto;
    }
    .kaliye-newsletter-input,
    .kaliye-newsletter-btn {
        width: 100%;
        box-sizing: border-box;
    }
    .kaliye-preferences-panel {
        width: min(calc(100% - 1.5rem), 340px);
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
    }
    .rodape-direitos {
        flex-direction: column;
        gap: 0.75rem;
        text-align: center;
        padding: 1.5rem 1rem;
    }
}
</style>
