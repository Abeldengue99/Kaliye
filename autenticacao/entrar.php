<?php
// entrar.php - Página de autenticação do sistema KALIYE
// Inicia a sessão para armazenar e aceder aos dados do utilizador
session_start();

// Inclui o ficheiro de configuração da base de dados
require_once __DIR__ . '/../configuracoes/base_dados.php';

// Cria uma nova instância da classe de ligação à base de dados
$database = new Database();

// Obtém a ligação activa à base de dados PostgreSQL usando PDO
/** @var PDO $db */
$db = $database->getConnection();

// Executa uma consulta para obter o nome do site das configurações
$site_name_stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");

// Usa o nome guardado ou o valor por defeito caso não exista
$site_name = $site_name_stmt->fetchColumn() ?: 'KALIYE';

$google_auth_enabled = false;
try {
    $google_auth_enabled_raw = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'google_auth_enabled'")->fetchColumn();
    $google_client_id = trim((string)$db->query("SELECT setting_value FROM settings WHERE setting_key = 'google_client_id'")->fetchColumn());
    $google_auth_enabled = in_array(strtolower((string)$google_auth_enabled_raw), ['1', 'true', 't', 'yes', 'y', 'on'], true) && $google_client_id !== '';
} catch (Throwable $e) {
    $google_auth_enabled = false;
}

// Se o utilizador já tem sessão iniciada, redireccioná-lo para o feed
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Contagem real de utilizadores para a prova social
$user_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | KALIYE</title>
    <meta name="description" content="Entra na tua conta KALIYE e continua a tua jornada profissional.">

    <!-- Favicon Oficial KALIYE — completo e local -->
    <?php
    require_once __DIR__ . '/../inclusoes/components/favicon.php';
    renderKaliyeFavicons('../');
    ?>

    <!-- Fontes modernas do Google para tipografia premium -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Ícones Font Awesome para os elementos visuais do formulário -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ===== VARIÁVEIS DO SISTEMA DE DESIGN (consistentes com o paginas/guest/landing.php) ===== */
        :root {
            --cor-fundo-principal: #070d1a;
            --cor-fundo-cartao: #0f172a;
            --cor-destaque-laranja: #f7941d;
            --cor-destaque-dourado: #fbbf24;
            --cor-destaque-azul: #3b82f6;
            --cor-texto-titulo: #ffffff;
            --cor-texto-paragrafo: #94a3b8;
            --cor-texto-discreto: #64748b;
            --cor-vidro: rgba(255,255,255,0.04);
            --cor-bordas-vidro: rgba(255,255,255,0.08);
            --cor-input-fundo: rgba(255,255,255,0.05);
            --cor-input-borda: rgba(255,255,255,0.1);
            --brilho-laranja: rgba(247,148,29,0.25);
        }

        /* ===== RESET E BASE ===== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--cor-fundo-principal);
            color: var(--cor-texto-titulo);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
        }

        /* ===== PAINEL ESQUERDO — FORMULÁRIO ===== */
        .painel-esquerdo {
            flex: 1;
            position: relative;
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            padding: 3rem 4rem;
            overflow: hidden;
            min-height: 100vh;
        }

        /* Fundo com imagem ofuscada no painel esquerdo */
        .fundo-esquerdo {
            position: absolute; inset: 0;
            background-image: url('../recursos/images/hero-bg.jpg'); /* Imagem provisória, ajustável depois */
            background-size: cover;
            background-position: center;
            filter: blur(8px) brightness(0.3);
            transform: scale(1.1); /* Evitar bordas brancas pelo blur */
            z-index: 0;
        }

        /* Overlay escuro em cima da imagem ofuscada */
        .overlay-fundo-esquerdo {
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(7, 13, 26, 0.7) 0%, rgba(12, 21, 38, 0.85) 100%);
            z-index: 1;
        }

        /* Grelha de pontos decorativos no fundo do painel esquerdo */
        .grelha-esquerda {
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 35px 35px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
            z-index: 1;
        }

        /* Conteúdo principal do formulário com largura máxima definida */
        .conteudo-esquerdo {
            position: relative; z-index: 2;
            width: 100%; max-width: 440px;
        }

        /* Link "Voltar ao Início" no topo do formulário */
        .link-voltar {
            display: inline-flex; align-items: center; gap: 0.5rem;
            color: var(--cor-texto-discreto); text-decoration: none;
            font-size: 0.85rem; font-weight: 500;
            margin-bottom: 2.5rem;
            transition: color 0.3s;
        }
        .link-voltar:hover { color: var(--cor-destaque-laranja); }

        /* Logo + nome da marca no topo do formulário */
        .logo-auth {
            display: flex; align-items: center; gap: 0.8rem;
            margin-bottom: 2.5rem; text-decoration: none;
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .logo-auth:hover { transform: scale(1.02); }
        .logo-icon-premium { 
            width: 44px; height: 44px; border-radius: 10px; 
            background: #ffffff;
            padding: 0px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            flex-shrink: 0;
            overflow: hidden;
        }
        .logo-icon-premium svg { width: 100%; height: 100%; }
        .logo-auth:hover .logo-icon-premium {
            transform: rotate(5deg) scale(1.05);
            box-shadow: 0 8px 25px rgba(247,148,29,0.3);
        }
        .logo-auth-texto { display: flex; flex-direction: column; line-height: 1; }
        .logo-marca-oficial {
            width: 145px;
            height: auto;
            display: block;
            border-radius: 10px;
        }
        .logo-auth-nome {
            font-family: 'Outfit', sans-serif;
            font-size: 1.2rem; font-weight: 800; color: #fff; letter-spacing: -0.5px;
        }
        .logo-auth-sub {
            font-size: 0.65rem; color: var(--cor-destaque-laranja);
            font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase;
            margin-top: 2px;
        }

        /* Título principal do formulário de login */
        .titulo-auth {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem; font-weight: 900;
            letter-spacing: -1px; margin-bottom: 0.5rem; line-height: 1.1;
        }
        .titulo-auth span {
            background: linear-gradient(135deg, var(--cor-destaque-laranja), var(--cor-destaque-dourado));
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitulo-auth {
            font-size: 0.95rem; color: var(--cor-texto-paragrafo); margin-bottom: 2rem;
        }

        /* Caixas de alerta para erros e sucessos do formulário */
        .alerta {
            display: flex; align-items: center; gap: 0.6rem;
            padding: 0.8rem 1rem; border-radius: 10px;
            font-size: 0.85rem; margin-bottom: 1.5rem; font-weight: 500;
        }
        .alerta-erro { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #ef4444; }
        .alerta-sucesso { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.25); color: #10b981; }
        .alerta-aviso { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.25); color: #f59e0b; }
        .alerta-bloqueio { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3); color: #f87171; flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        .countdown-timer { font-size: 1.4rem; font-weight: 800; font-family: 'Outfit', monospace; color: #ef4444; margin-top: 0.25rem; }

        /* Formulário de autenticação e grupos de campos */
        .formulario-auth { width: 100%; }
        .grupo-campo { margin-bottom: 1.25rem; }
        .etiqueta-campo {
            display: block; font-size: 0.85rem; font-weight: 600;
            color: var(--cor-texto-paragrafo); margin-bottom: 0.5rem;
            letter-spacing: 0.3px;
        }

        /* Contentor relativo para posicionar ícone dentro do campo */
        .caixa-input { position: relative; }
        .icone-input {
            position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
            color: var(--cor-texto-discreto); font-size: 0.9rem;
            pointer-events: none; transition: color 0.3s;
        }

        /* Estilo base dos campos de entrada do formulário */
        .campo-input {
            width: 100%; padding: 0.875rem 1rem 0.875rem 2.75rem;
            background: var(--cor-input-fundo); border: 1px solid var(--cor-input-borda);
            border-radius: 10px; color: var(--cor-texto-titulo);
            font-size: 0.95rem; font-family: 'Inter', sans-serif;
            outline: none; transition: all 0.3s;
        }
        .campo-input::placeholder { color: var(--cor-texto-discreto); }
        .campo-input:focus {
            border-color: var(--cor-destaque-laranja);
            background: rgba(247,148,29,0.04);
            box-shadow: 0 0 0 3px rgba(247,148,29,0.1);
        }

        /* Botão de ver/esconder password dentro do campo */
        .botao-olho {
            position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
            color: var(--cor-texto-discreto); cursor: pointer; font-size: 0.9rem;
            transition: color 0.3s; background: none; border: none;
        }
        .botao-olho:hover { color: var(--cor-destaque-laranja); }
        .input-senha { padding-right: 2.75rem !important; }

        /* Link de recuperação de password alinhado à direita */
        .linha-rodape-campo {
            display: flex; justify-content: flex-end; margin-top: 0.35rem;
        }
        .link-esqueci {
            font-size: 0.8rem; color: var(--cor-texto-discreto);
            text-decoration: none; transition: color 0.3s;
        }
        .link-esqueci:hover { color: var(--cor-destaque-laranja); }

        /* Botão principal de submissão do formulário */
        .botao-submeter {
            width: 100%; padding: 0.9rem 1.5rem; margin-top: 1.5rem;
            background: linear-gradient(135deg, var(--cor-destaque-laranja), #e07b0e);
            border: none; border-radius: 10px;
            color: #fff; font-size: 1rem; font-weight: 700;
            font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.3s;
            box-shadow: 0 6px 25px var(--brilho-laranja);
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            position: relative; overflow: hidden;
        }
        .botao-submeter::before {
            content: ''; position: absolute; top: -50%; left: -60%;
            width: 40%; height: 200%;
            background: rgba(255,255,255,0.15);
            transform: skewX(-25deg); transition: left 0.5s;
        }
        .botao-submeter:hover::before { left: 120%; }
        .botao-submeter:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px var(--brilho-laranja);
        }
        .botao-submeter:active { transform: translateY(0px); }

        /* Divisor "ou" entre o formulário e o link de registo */
        .divisor {
            display: flex; align-items: center; gap: 1rem;
            margin: 1.5rem 0; color: var(--cor-texto-discreto); font-size: 0.8rem;
        }
        .divisor::before, .divisor::after {
            content: ''; flex: 1; height: 1px; background: var(--cor-bordas-vidro);
        }

        /* Texto com link para a página de registo no fundo */
        .alternancia-auth {
            text-align: center; font-size: 0.875rem; color: var(--cor-texto-paragrafo);
        }
        .alternancia-auth a {
            color: var(--cor-destaque-laranja); font-weight: 700; text-decoration: none; transition: opacity 0.3s;
        }
        .alternancia-auth a:hover { opacity: 0.8; }

        .botao-google {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 10px;
            background: rgba(255,255,255,0.06);
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            font-size: 0.92rem;
            font-weight: 700;
            transition: all 0.25s;
        }
        .botao-google:hover {
            border-color: rgba(247,148,29,0.45);
            background: rgba(247,148,29,0.08);
            transform: translateY(-1px);
        }

        /* ===== PAINEL DIREITO — INFORMATIVO ===== */
        .painel-direito {
            width: 460px; flex-shrink: 0;
            background: linear-gradient(145deg, #0f172a 0%, #111827 100%);
            border-left: 1px solid var(--cor-bordas-vidro);
            display: flex; flex-direction: column;
            justify-content: center; padding: 4rem 3rem;
            position: relative; overflow: hidden;
        }

        /* Orbe decorativa inferior direita do painel informativo */
        .painel-direito::before {
            content: ''; position: absolute;
            bottom: -100px; right: -100px;
            width: 350px; height: 350px; border-radius: 50%;
            background: radial-gradient(circle, rgba(247,148,29,0.07) 0%, transparent 70%);
        }

        /* Orbe decorativa superior esquerda do painel informativo */
        .painel-direito::after {
            content: ''; position: absolute;
            top: -80px; left: -80px;
            width: 280px; height: 280px; border-radius: 50%;
            background: radial-gradient(circle, rgba(59,130,246,0.05) 0%, transparent 70%);
        }
        .conteudo-direito { position: relative; z-index: 2; }

        /* Etiqueta de verificação no topo do painel direito */
        .etiqueta-direita {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(247,148,29,0.1); border: 1px solid rgba(247,148,29,0.2);
            border-radius: 50px; padding: 0.35rem 0.85rem;
            font-size: 0.7rem; font-weight: 700; color: var(--cor-destaque-laranja);
            letter-spacing: 1px; text-transform: uppercase; margin-bottom: 1.5rem;
        }

        /* Título principal do painel informativo direito */
        .titulo-direito {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem; font-weight: 800;
            letter-spacing: -0.5px; margin-bottom: 1rem; line-height: 1.2;
        }
        .descrição-direita {
            font-size: 0.875rem; color: var(--cor-texto-paragrafo);
            line-height: 1.7; margin-bottom: 2rem;
        }

        /* Lista de benefícios da plataforma no painel direito */
        .lista-beneficios { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
        .item-beneficio { display: flex; align-items: flex-start; gap: 0.75rem; }
        .icone-beneficio {
            width: 34px; height: 34px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; flex-shrink: 0; margin-top: 1px;
        }
        .item-beneficio h4 { font-size: 0.875rem; font-weight: 700; margin-bottom: 0.2rem; }
        .item-beneficio p { font-size: 0.78rem; color: var(--cor-texto-discreto); line-height: 1.5; }

        /* Box de prova social com avatares e contagem de membros */
        .prova-social {
            background: rgba(255,255,255,0.03); border: 1px solid var(--cor-bordas-vidro);
            border-radius: 14px; padding: 1.25rem;
        }
        .avatares-prova { display: flex; margin-bottom: 0.6rem; }
        .avatar-prova {
            width: 32px; height: 32px; border-radius: 50%;
            border: 2px solid #0f172a;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: 700;
            margin-right: -8px; flex-shrink: 0;
        }
        .texto-prova { font-size: 0.8rem; color: var(--cor-texto-paragrafo); }
        .texto-prova strong { color: #fff; }

        /* ===== SPINNER DE CARREGAMENTO AO SUBMETER ===== */
        .spinner {
            display: none; width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.3); border-radius: 50%;
            border-top-color: #fff; animation: girar 0.8s linear infinite;
        }
        @keyframes girar { to { transform: rotate(360deg); } }

        /* ===== RESPONSIVE PARA ECRÃS PEQUENOS ===== */
        @media (max-width: 900px) {
            .painel-direito { display: none; }
            .painel-esquerdo { padding: 2rem 1.5rem; }
        }
        @media (max-width: 480px) {
            .titulo-auth { font-size: 1.8rem; }
            .painel-esquerdo { padding: 2rem 1rem; }
        }
    </style>
</head>
<body>

<!-- ===== PAINEL ESQUERDO: FORMULÁRIO DE LOGIN ===== -->
<div class="painel-esquerdo">
    <!-- Fundo com imagem ofuscada -->
    <div class="fundo-esquerdo"></div>
    <div class="overlay-fundo-esquerdo"></div>
    <!-- Grelha de pontos decorativos no fundo -->
    <div class="grelha-esquerda"></div>

    <!-- Área de conteúdo do formulário centrada na coluna -->
    <div class="conteudo-esquerdo">

        <!-- Link de navegação para voltar à página inicial -->
        <a href="../paginas/guest/landing.php" class="link-voltar">
            <i class="fas fa-arrow-left"></i> Voltar ao Início
        </a>

        <!-- Título e subtítulo de boas-vindas ao utilizador -->
        <h1 class="titulo-auth">Bem-vindo <span>de volta!</span></h1>
        <p class="subtitulo-auth">Entra na tua conta para continuar a jornada.</p>

        <!-- Alerta de credenciais inválidas após tentativa falhada -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alerta alerta-erro">
                <i class="fas fa-exclamation-circle"></i>
                Credenciais inválidas. Por favor, verifique o e-mail e a password.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'rate_limited'): ?>
            <?php $retry = max(0, intval($_GET['retry'] ?? 900)); ?>
            <div class="alerta alerta-bloqueio">
                <div style="display:flex;align-items:center;gap:0.6rem;">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Demasiadas tentativas de acesso</strong>
                </div>
                <p style="font-size:0.82rem;margin:0;">Por segurança, o acesso foi temporariamente suspenso. Podes tentar novamente em:</p>
                <div class="countdown-timer" id="countdownTimer">...</div>
                <p style="font-size:0.75rem;color:#94a3b8;margin:0;">Se não foste tu, considera alterar a tua password.</p>
            </div>
            <script>
                (function() {
                    let seconds = <?= $retry ?>;
                    const el = document.getElementById('countdownTimer');
                    function pad(n) { return String(n).padStart(2, '0'); }
                    function tick() {
                        if (seconds <= 0) { el.textContent = 'Podes tentar agora!'; el.style.color = '#10b981'; return; }
                        const m = Math.floor(seconds / 60);
                        const s = seconds % 60;
                        el.textContent = pad(m) + ':' + pad(s);
                        seconds--;
                        setTimeout(tick, 1000);
                    }
                    tick();
                })();
            </script>
        <?php endif; ?>

        <?php if (isset($_SESSION['rate_limit_warning'])): ?>
            <div class="alerta alerta-aviso">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($_SESSION['rate_limit_warning']) ?>
            </div>
            <?php unset($_SESSION['rate_limit_warning']); ?>
        <?php endif; ?>

        <!-- Alerta de sucesso após redefinição de password -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'password_reset_sent'): ?>
            <div class="alerta alerta-sucesso">
                <i class="fas fa-check-circle"></i>
                Email de recuperação enviado! Verifica a tua caixa de entrada.
            </div>
        <?php endif; ?>

        <!-- Alerta de confirmação após alteração bem-sucedida de password -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'password_updated'): ?>
            <div class="alerta alerta-sucesso">
                <i class="fas fa-check-circle"></i>
                Password alterada com sucesso! Podes iniciar sessão.
            </div>
        <?php endif; ?>

        <!-- Formulário principal de autenticação que envia para a API -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'session_expired'): ?>
            <div class="alerta alerta-aviso">
                <i class="fas fa-clock"></i>
                A tua sessão expirou por inatividade. Entra novamente para continuar.
            </div>
        <?php endif; ?>

        <form class="formulario-auth" id="formularioLogin" action="../interface_programacao/auth/login_action.php" method="POST">

            <!-- Campo de email com ícone de envelope integrado -->
            <div class="grupo-campo">
                <label class="etiqueta-campo" for="email">Endereço de Email</label>
                <div class="caixa-input">
                    <i class="fas fa-envelope icone-input"></i>
                    <input
                        type="email" id="email" name="email"
                        class="campo-input"
                        placeholder="nome@exemplo.com"
                        required autocomplete="email"
                    >
                </div>
            </div>

            <!-- Campo de password com ícone de cadeado e botão de visibilidade -->
            <div class="grupo-campo">
                <label class="etiqueta-campo" for="password">Password</label>
                <div class="caixa-input">
                    <i class="fas fa-lock icone-input"></i>
                    <input
                        type="password" id="password" name="password"
                        class="campo-input input-senha"
                        placeholder="••••••••"
                        required autocomplete="current-password"
                    >
                    <!-- Botão para alternar entre ver e ocultar a password -->
                    <button type="button" class="botao-olho" id="alternarSenha" aria-label="Mostrar password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <!-- Link de recuperação de password esquecida -->
                <div class="linha-rodape-campo">
                    <a href="recuperar_senha.php" class="link-esqueci">Esqueceste a password?</a>
                </div>
            </div>

            <!-- Botão de submissão com spinner de carregamento integrado -->
            <button type="submit" class="botao-submeter" id="botaoSubmeter">
                <span class="spinner" id="spinnerLoader"></span>
                <i class="fas fa-sign-in-alt" id="iconeBtn"></i>
                <span id="textoBtn">Entrar na Plataforma</span>
            </button>
        </form>

        <!-- Divisor visual entre o formulário e a opção de registo -->
        <div class="divisor">ou</div>

        <?php if ($google_auth_enabled): ?>
            <a class="botao-google" href="google_iniciar.php?mode=login">
                <i class="fab fa-google"></i>
                Entrar com Google
            </a>
            <div style="height: 1rem;"></div>
        <?php endif; ?>

        <!-- Link para criar uma nova conta de utilizador -->
        <div class="alternancia-auth">
            Não tens conta? <a href="registar.php">Regista-te Grátis <i class="fas fa-arrow-right"></i></a>
        </div>

    </div>
</div>

<!-- ===== PAINEL DIREITO: INFORMAÇÃO E BENEFÍCIOS ===== -->
<div class="painel-direito">
    <div class="conteudo-direito">
        <!-- Etiqueta de segurança e verificação da plataforma -->
        <div class="etiqueta-direita"><i class="fas fa-shield-alt"></i> Plataforma Segura &amp; Verificada</div>

        <!-- Título do painel informativo sobre a plataforma -->
        <h2 class="titulo-direito">A tua plataforma de crescimento profissional</h2>
        <p class="descrição-direita">A plataforma angolana que conecta estudantes, mentores e investidores num único ecossistema digital de oportunidades.</p>

        <!-- Lista de vantagens de ser membro da plataforma -->
        <div class="lista-beneficios">

            <!-- Benefício 1: Projectos e Investimento -->
            <div class="item-beneficio">
                <div class="icone-beneficio" style="background: rgba(247,148,29,0.12); color: var(--cor-destaque-laranja);">
                    <i class="fas fa-rocket"></i>
                </div>
                <div>
                    <h4>Projectos &amp; Investimento</h4>
                    <p>Publica os teus projectos e conecta-te com investidores reais dispostos a financiar.</p>
                </div>
            </div>

            <!-- Benefício 2: Mentores Verificados -->
            <div class="item-beneficio">
                <div class="icone-beneficio" style="background: rgba(59,130,246,0.12); color: var(--cor-destaque-azul);">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <h4>Mentores Verificados</h4>
                    <p>Acede a sessões de mentoria com profissionais experientes nas mais diversas áreas.</p>
                </div>
            </div>

            <!-- Benefício 3: Carteira Digital Segura -->
            <div class="item-beneficio">
                <div class="icone-beneficio" style="background: rgba(139,92,246,0.12); color: #8b5cf6;">
                    <i class="fas fa-wallet"></i>
                </div>
                <div>
                    <h4>Carteira Digital Segura</h4>
                    <p>Gestão de pagamentos, comissões e transferências num só lugar.</p>
                </div>
            </div>

        </div>

        <!-- Prova social: avatares e contagem de membros activos -->
        <div class="prova-social">
            <div class="avatares-prova">
                <div class="avatar-prova" style="background: linear-gradient(135deg,#f7941d,#e07b0e);">KN</div>
                <div class="avatar-prova" style="background: linear-gradient(135deg,#3b82f6,#1e40af);">AL</div>
                <div class="avatar-prova" style="background: linear-gradient(135deg,#10b981,#059669);">PM</div>
                <div class="avatar-prova" style="background: linear-gradient(135deg,#8b5cf6,#6d28d9);">RD</div>
                <div class="avatar-prova" style="background: linear-gradient(135deg,#f43f5e,#be123c);">+</div>
            </div>
            <p class="texto-prova"><strong>+<?php echo number_format($user_count); ?> utilizadores</strong> já fazem parte da comunidade KALIYE!</p>
        </div>
    </div>
</div>

<script>
    // Alterna a visibilidade da password entre texto e ponto
    document.getElementById('alternarSenha').addEventListener('click', function() {
        const campoPw = document.getElementById('password');
        const icone = this.querySelector('i');
        // Troca o tipo do campo entre 'password' e 'text'
        campoPw.type = campoPw.type === 'password' ? 'text' : 'password';
        // Actualiza o ícone conforme o estado actual
        icone.className = campoPw.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    });

    // Mostra o spinner e desactiva o botão ao submeter o formulário
    document.getElementById('formularioLogin').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value;
        // Verifica se o email tem formato minimamente válido antes de enviar
        if (!email.includes('@')) {
            e.preventDefault();
            return;
        }
        // Activa o spinner de carregamento e desactiva o botão
        document.getElementById('spinnerLoader').style.display = 'block';
        document.getElementById('iconeBtn').style.display = 'none';
        document.getElementById('textoBtn').textContent = 'A entrar...';
        document.getElementById('botaoSubmeter').disabled = true;
    });

    // Animação suave de entrada do conteúdo ao carregar a página
    document.addEventListener('DOMContentLoaded', () => {
        const conteudo = document.querySelector('.conteudo-esquerdo');
        conteudo.style.opacity = '0';
        conteudo.style.transform = 'translateY(20px)';
        conteudo.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        // Activa a transição no próximo ciclo de animação do browser
        requestAnimationFrame(() => {
            conteudo.style.opacity = '1';
            conteudo.style.transform = 'translateY(0)';
        });
    });
</script>
</body>
</html>
