<?php
// landing_hero.php - Componente do Hero Section da landing page
// Usa $base_url para caminhos corretos independentemente de onde o ficheiro é incluído
?>
<section class="secao-hero">
    <div class="swiper swiper-hero" id="heroSwiper">
        <div class="swiper-wrapper">
            
            <div class="swiper-slide">
                <div class="slide-bg" style="background-image: url('<?php echo $base_url; ?>recursos/images/slide1.png');"></div>
                <div class="slide-overlay"></div>
                <div class="slide-conteudo">
                    <h1 class="txt-hero">A tua <span>jornada</span> profissional em Angola</h1>
                    <p class="desc-hero">A maior rede de mentoria e investimento estratégico. Conecta-te com quem faz acontecer.</p>
                    <a href="<?php echo $base_url; ?>autenticacao/registar.php" class="btn-cta-hero">Começar Agora</a>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="slide-bg" style="background-image: url('<?php echo $base_url; ?>recursos/images/slide2.png');"></div>
                <div class="slide-overlay"></div>
                <div class="slide-conteudo">
                    <h1 class="txt-hero">Aprende com <span>mentores</span> de elite</h1>
                    <p class="desc-hero">Acelera o teu crescimento com a experiência dos melhores profissionais do mercado nacional.</p>
                    <a href="<?php echo $base_url; ?>autenticacao/registar.php" class="btn-cta-hero">Encontrar Mentor</a>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="slide-bg" style="background-image: url('<?php echo $base_url; ?>recursos/images/slide3.png');"></div>
                <div class="slide-overlay"></div>
                <div class="slide-conteudo">
                    <h1 class="txt-hero">Atrai <span>investimento</span> real</h1>
                    <p class="desc-hero">Apresenta os teus projectos a investidores e transforma a tua visão em realidade de sucesso.</p>
                    <a href="<?php echo $base_url; ?>autenticacao/registar.php" class="btn-cta-hero">Submeter Projecto</a>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="slide-bg" style="background-image: url('<?php echo $base_url; ?>recursos/images/slide4.jpg');"></div>
                <div class="slide-overlay"></div>
                <div class="slide-conteudo">
                    <h1 class="txt-hero">Trabalho em <span>Equipa</span></h1>
                    <p class="desc-hero">Junta-te a profissionais dedicados e partilha projectos para alcançar o sucesso em conjunto.</p>
                    <a href="<?php echo $base_url; ?>autenticacao/registar.php" class="btn-cta-hero">Ver Mais</a>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="slide-bg" style="background-image: url('<?php echo $base_url; ?>recursos/images/slide5.jpg');"></div>
                <div class="slide-overlay"></div>
                <div class="slide-conteudo">
                    <h1 class="txt-hero">A Força da <span>Comunidade</span></h1>
                    <p class="desc-hero">Liderança, diversidade e apoio mútuo. Faz parte de uma rede que impulsiona o teu crescimento.</p>
                    <a href="<?php echo $base_url; ?>autenticacao/registar.php" class="btn-cta-hero">Junta-te a Nós</a>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="slide-bg" style="background-image: url('<?php echo $base_url; ?>recursos/images/slide6.jpg');"></div>
                <div class="slide-overlay"></div>
                <div class="slide-conteudo">
                    <h1 class="txt-hero">Mentoria <span>Global</span></h1>
                    <p class="desc-hero">Conecta-te remotamente com especialistas e expande a tua visão de negócio.</p>
                    <a href="<?php echo $base_url; ?>autenticacao/registar.php" class="btn-cta-hero">Explorar</a>
                </div>
            </div>

        </div>
        <div class="swiper-pagination"></div>
    </div>
</section>
