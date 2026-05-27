<?php
// landing_header.php - Componente de cabeçalho da landing page
?>
<header class="cabecalho-flutuante">
    <div class="container-header">
        <a href="<?php echo $base_url; ?>paginas/guest/landing.php" class="marca-box">
            <div class="logo-icon-premium">
                <img src="<?php echo $base_url; ?>recursos/images/marca/logotipo.png" alt="KALIYE Logo">
            </div>
            <div class="marca-titulo">
                <span>KALIYE</span>
            </div>
        </a>
        <nav class="nav-links">
            <a href="#como-aceder">Como ter acesso</a>
            <a href="#investir">Investir</a>
            <a href="#mentoria">Mentoria</a>
        </nav>

        <div class="botoes-nav">
            <a href="<?php echo $base_url; ?>autenticacao/entrar.php" class="btn-entrar">Entrar</a>
            <a href="<?php echo $base_url; ?>autenticacao/registar.php" class="btn-comecar">Criar Conta</a>
            
            <button class="btn-mobile-trigger" id="abrirMenuMobile" aria-label="Abrir Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<div class="mobile-menu-overlay" id="menuMobile">
    <div class="mobile-menu-header">
        <a href="<?php echo $base_url; ?>paginas/guest/landing.php" class="marca-box mobile-brand">
            <div class="logo-icon-premium">
                <img src="<?php echo $base_url; ?>recursos/images/marca/logotipo.png" alt="KALIYE Logo">
            </div>
            <div class="marca-titulo">
                <span>KALIYE</span>
                <span class="marca-subtitulo">Conectar • Crescer • Investir</span>
            </div>
        </a>
        <button class="btn-close-mobile" id="fecharMenuMobile" aria-label="Fechar Menu"><i class="fas fa-times"></i></button>
    </div>
    <div class="mobile-menu-links">
        <a href="#como-aceder" class="mobile-link">Como ter acesso</a>
        <a href="#investir" class="mobile-link">Investir</a>
        <a href="#mentoria" class="mobile-link">Mentoria</a>
        <div class="mobile-divider"></div>
        <a href="<?php echo $base_url; ?>autenticacao/entrar.php" class="mobile-link">Entrar</a>
        <a href="<?php echo $base_url; ?>autenticacao/registar.php" class="btn-comecar-mobile">Criar Conta</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnAbrir = document.getElementById('abrirMenuMobile');
    const btnFechar = document.getElementById('fecharMenuMobile');
    const menu = document.getElementById('menuMobile');
    const links = document.querySelectorAll('.mobile-link');

    if(btnAbrir && menu) {
        btnAbrir.addEventListener('click', (e) => {
            e.preventDefault();
            menu.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    if(btnFechar && menu) {
        btnFechar.addEventListener('click', (e) => {
            e.preventDefault();
            menu.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    links.forEach(link => {
        link.addEventListener('click', () => {
            menu.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
});
</script>
