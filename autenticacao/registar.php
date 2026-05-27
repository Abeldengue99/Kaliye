<?php
// registar.php - Página de criação de novas contas na plataforma
// Inicia a sessão para guardar variáveis globais como $_SESSION
session_start();

// Importa as configurações e a classe da base de dados
require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/../inclusoes/SystemSettings.php';

// Cria o objecto principal da base de dados
$database = new Database();

// Tenta estabelecer a ligação à base de dados PostgreSQL
/** @var PDO $db */
$db = $database->getConnection();

// Faz a consulta para buscar o nome dinâmico da plataforma
$site_name_stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");

// Armazena o nome buscado ou usa 'KALIYE' se não encontrar nada
$site_name = $site_name_stmt->fetchColumn() ?: 'KALIYE';

$google_auth_enabled = false;
try {
    $google_auth_enabled_raw = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'google_auth_enabled'")->fetchColumn();
    $google_client_id = trim((string)$db->query("SELECT setting_value FROM settings WHERE setting_key = 'google_client_id'")->fetchColumn());
    $google_auth_enabled = in_array(strtolower((string)$google_auth_enabled_raw), ['1', 'true', 't', 'yes', 'y', 'on'], true) && $google_client_id !== '';
} catch (Throwable $e) {
    $google_auth_enabled = false;
}

if (!systemSettingEnabled($db, 'allow_registrations', true)) {
    http_response_code(403);
    $safe_site_name = htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Registos Fechados | ' . $safe_site_name . '</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#070d1a;color:#fff;font-family:Inter,Arial,sans-serif}.box{max-width:520px;padding:32px;text-align:center}h1{font-size:30px;margin:0 0 10px}p{color:#94a3b8;line-height:1.6}a{color:#f7941d;text-decoration:none;font-weight:800}</style></head><body><main class="box"><h1>Registos temporariamente fechados</h1><p>A criacao de novas contas esta pausada neste momento pela administracao.</p><p><a href="entrar.php">Entrar numa conta existente</a></p></main></body></html>';
    exit();
}

// Se o utilizador já tem sessão iniciada, redireccioná-lo para o feed
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta | KALIYE</title>
    <meta name="description" content="Cria a tua conta KALIYE e junta-te à maior plataforma profissional de Angola.">

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
        /* ===== VARIÁVEIS DO SISTEMA DE DESIGN (consistentes com paginas/guest/landing.php e entrar.php) ===== */
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

        /* ===== RESET E BASE GLOBAL ===== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { min-height: 100%; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--cor-fundo-principal);
            color: var(--cor-texto-titulo);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
        }

        /* ===== PAINEL ESQUERDO — INFORMATIVO (só em desktop) ===== */
        .painel-esquerdo {
            width: 420px; flex-shrink: 0;
            background: linear-gradient(145deg, #0d1628 0%, #0f172a 100%);
            border-right: 1px solid var(--cor-bordas-vidro);
            display: flex; flex-direction: column;
            justify-content: center; padding: 2rem 3rem;
            position: relative; overflow: hidden;
            position: sticky; top: 0; height: 100vh;
        }

        /* Orbe decorativa no canto inferior esquerdo do painel informativo */
        .painel-esquerdo::before {
            content: ''; position: absolute;
            bottom: -80px; left: -80px;
            width: 300px; height: 300px; border-radius: 50%;
            background: radial-gradient(circle, rgba(247,148,29,0.07) 0%, transparent 70%);
        }

        /* Orbe decorativa no canto superior direito do painel informativo */
        .painel-esquerdo::after {
            content: ''; position: absolute;
            top: -60px; right: -60px;
            width: 250px; height: 250px; border-radius: 50%;
            background: radial-gradient(circle, rgba(59,130,246,0.06) 0%, transparent 70%);
        }
        .conteudo-esquerdo { position: relative; z-index: 2; }

        /* Logotipo e nome da marca no painel esquerdo/informativo */
        .logo-info {
            display: flex; align-items: center; gap: 0.8rem;
            text-decoration: none; margin-bottom: 1.5rem;
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .logo-info:hover { transform: scale(1.02); }
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
        .logo-info:hover .logo-icon-premium {
            transform: rotate(5deg) scale(1.05);
            box-shadow: 0 8px 25px rgba(247,148,29,0.3);
        }
        .logo-info-texto { display: flex; flex-direction: column; line-height: 1; }
        .logo-marca-oficial {
            width: 145px;
            height: auto;
            display: block;
            border-radius: 10px;
        }
        .logo-info-nome {
            font-family: 'Outfit', sans-serif;
            font-size: 1.2rem; font-weight: 800; color: #fff; letter-spacing: -0.5px;
            display: flex; flex-direction: column; line-height: 1;
        }
        .logo-info-sub {
            font-size: 0.65rem; color: var(--cor-destaque-laranja);
            font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase;
            margin-top: 2px;
        }

        /* Título do painel informativo lateral */
        .titulo-info {
            font-family: 'Outfit', sans-serif;
            font-size: 1.4rem; font-weight: 900;
            line-height: 1.15; letter-spacing: -0.5px; margin-bottom: 0.4rem;
        }
        .titulo-info span { color: var(--cor-destaque-laranja); }
        .descricao-info { font-size: 0.8rem; color: var(--cor-texto-paragrafo); line-height: 1.5; margin-bottom: 1.2rem; }

        /* Lista de passos do processo de registo na barra lateral */
        .lista-passos { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.2rem; }
        .passo-item { display: flex; align-items: center; gap: 0.9rem; }
        .passo-numero {
            width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
            background: rgba(247,148,29,0.12); border: 1px solid rgba(247,148,29,0.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 800; color: var(--cor-destaque-laranja);
        }
        .passo-texto { font-size: 0.8rem; color: var(--cor-texto-paragrafo); }
        .passo-texto strong { color: #fff; display: block; margin-bottom: 0.1rem; }

        /* Box de garantia de segurança na barra lateral */
        .caixa-seguranca {
            background: rgba(16,185,129,0.06);
            border: 1px solid rgba(16,185,129,0.15);
            border-radius: 12px; padding: 1rem 1.1rem;
            display: flex; align-items: flex-start; gap: 0.75rem;
        }
        .caixa-seguranca i { color: #10b981; font-size: 1rem; margin-top: 2px; flex-shrink: 0; }
        .caixa-seguranca p { font-size: 0.8rem; color: var(--cor-texto-paragrafo); line-height: 1.6; }

        /* ===== PAINEL DIREITO — FORMULÁRIO DE REGISTO ===== */
        .painel-direito {
            flex: 1;
            position: relative;
            display: flex; flex-direction: column;
            align-items: center;
            padding: 4rem 3rem;
            overflow-y: auto;
        }

        /* Fundo com imagem ofuscada no painel do formulário */
        .fundo-direito {
            position: fixed; inset: 0;
            background-image: url('../recursos/images/hero-bg.jpg'); /* Imagem provisória, configurável */
            background-size: cover;
            background-position: center;
            filter: blur(8px) brightness(0.25);
            transform: scale(1.1);
            z-index: -2;
        }

        /* Overlay escuro em cima da imagem ofuscada */
        .overlay-fundo-direito {
            position: fixed; inset: 0;
            background: linear-gradient(135deg, rgba(7, 13, 26, 0.75) 0%, rgba(12, 21, 38, 0.9) 100%);
            z-index: -1;
        }

        /* Área de conteúdo do formulário com largura máxima */
        .conteudo-formulario {
            width: 100%; max-width: 520px;
        }

        /* Link de navegação para voltar ao início */
        .link-voltar {
            display: inline-flex; align-items: center; gap: 0.5rem;
            color: var(--cor-texto-discreto); text-decoration: none;
            font-size: 0.85rem; font-weight: 500;
            margin-bottom: 2.5rem; transition: color 0.3s;
        }
        .link-voltar:hover { color: var(--cor-destaque-laranja); }

        /* Título e subtítulo da área do formulário de registo */
        .titulo-formulario {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem; font-weight: 900;
            letter-spacing: -1px; margin-bottom: 0.5rem; line-height: 1.1;
        }
        .titulo-formulario span {
            background: linear-gradient(135deg, var(--cor-destaque-laranja), var(--cor-destaque-dourado));
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitulo-formulario {
            font-size: 0.95rem; color: var(--cor-texto-paragrafo); margin-bottom: 2rem;
        }

        /* Alerta de erro para problemas ocorridos durante o registo */
        .alerta-erro {
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25);
            border-radius: 10px; padding: 0.9rem 1rem;
            color: #ef4444; font-size: 0.85rem;
            display: flex; align-items: center; gap: 0.6rem;
            margin-bottom: 1.5rem; font-weight: 500;
        }

        /* Separador entre secções do formulário com rótulo */
        .separador-secao {
            font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: 2px; color: var(--cor-texto-discreto);
            margin: 1.75rem 0 1rem;
            display: flex; align-items: center; gap: 0.75rem;
        }
        .separador-secao::before, .separador-secao::after {
            content: ''; flex: 1; height: 1px; background: var(--cor-bordas-vidro);
        }

        /* Grelha para organizar campos de formulário em duas colunas */
        .grelha-2col {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
        }

        /* Grupos individuais de campos do formulário */
        .grupo-campo { margin-bottom: 1.1rem; }
        .grupo-campo.col-completa { grid-column: 1 / -1; }
        .etiqueta-campo {
            display: block; font-size: 0.82rem; font-weight: 600;
            color: var(--cor-texto-paragrafo); margin-bottom: 0.45rem;
            letter-spacing: 0.3px;
        }

        /* Contentor relativo para posicionar ícones dentro dos campos */
        .caixa-input { position: relative; }
        .icone-input {
            position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
            color: var(--cor-texto-discreto); font-size: 0.85rem; pointer-events: none;
        }

        /* Estilo base para todos os campos de entrada de texto */
        .campo-input {
            width: 100%; padding: 0.82rem 1rem 0.82rem 2.6rem;
            background: var(--cor-input-fundo); border: 1px solid var(--cor-input-borda);
            border-radius: 10px; color: var(--cor-texto-titulo);
            font-size: 0.9rem; font-family: 'Inter', sans-serif;
            outline: none; transition: all 0.3s;
        }
        .campo-input::placeholder { color: var(--cor-texto-discreto); }
        .campo-input:focus {
            border-color: var(--cor-destaque-laranja);
            background: rgba(247,148,29,0.03);
            box-shadow: 0 0 0 3px rgba(247,148,29,0.1);
        }
        .campo-input.sem-icone { padding-left: 1rem; }

        /* Estilo especial para o campo select de tipo de utilizador */
        select.campo-input {
            padding-left: 1rem; appearance: none; cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 1rem center;
        }
        select.campo-input option { background: #1e293b; color: #fff; }

        /* Botão de ver/ocultar password dentro do campo de senha */
        .botao-olho {
            position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
            color: var(--cor-texto-discreto); cursor: pointer;
            font-size: 0.85rem; background: none; border: none; transition: color 0.3s;
        }
        .botao-olho:hover { color: var(--cor-destaque-laranja); }
        .input-senha { padding-right: 2.6rem !important; }

        /* Caixa especial de destaque para a opção de Peer Mentor */
        .caixa-peer-mentor {
            display: none; margin-bottom: 1.1rem;
            background: rgba(247,148,29,0.06);
            border: 1px solid rgba(247,148,29,0.2);
            border-radius: 10px; padding: 1rem 1.1rem;
        }
        .label-checkbox {
            display: flex; align-items: flex-start; gap: 0.75rem;
            cursor: pointer; font-size: 0.85rem;
        }
        .label-checkbox input[type="checkbox"] {
            width: 16px; height: 16px; margin-top: 2px;
            accent-color: var(--cor-destaque-laranja); flex-shrink: 0;
        }
        .label-checkbox .texto-checkbox strong {
            color: var(--cor-destaque-laranja); display: block; margin-bottom: 0.2rem;
        }
        .label-checkbox .texto-checkbox p {
            font-size: 0.77rem; color: var(--cor-texto-discreto); line-height: 1.5;
        }

        /* Área de aceitação dos termos e política de privacidade */
        .area-termos { margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.7rem; }
        .linha-termos {
            display: flex; align-items: flex-start; gap: 0.75rem;
            font-size: 0.82rem; color: var(--cor-texto-paragrafo);
        }
        .linha-termos input[type="checkbox"] {
            width: 16px; height: 16px; margin-top: 1px;
            accent-color: var(--cor-destaque-laranja); flex-shrink: 0;
        }
        .linha-termos a { color: var(--cor-destaque-laranja); text-decoration: underline; }

        /* Botão principal de submissão do formulário de registo */
        .botao-registar {
            width: 100%; padding: 1rem 1.5rem; margin-top: 1.75rem;
            background: linear-gradient(135deg, var(--cor-destaque-laranja), #e07b0e);
            border: none; border-radius: 12px;
            color: #fff; font-size: 1rem; font-weight: 700;
            font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.3s;
            box-shadow: 0 6px 25px var(--brilho-laranja);
            display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            position: relative; overflow: hidden;
        }
        .botao-registar::before {
            content: ''; position: absolute; top: -50%; left: -60%;
            width: 40%; height: 200%;
            background: rgba(255,255,255,0.15);
            transform: skewX(-25deg); transition: left 0.5s;
        }
        .botao-registar:hover::before { left: 120%; }
        .botao-registar:hover { transform: translateY(-2px); box-shadow: 0 12px 35px var(--brilho-laranja); }
        .botao-registar:active { transform: translateY(0); }

        /* Link no fundo para utilizadores que já têm conta */
        .link-entrar {
            text-align: center; margin-top: 1.5rem;
            font-size: 0.875rem; color: var(--cor-texto-paragrafo);
        }
        .link-entrar a {
            color: var(--cor-destaque-laranja); font-weight: 700;
            text-decoration: none; transition: opacity 0.3s;
        }
        .link-entrar a:hover { opacity: 0.8; }

        .botao-google {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 12px;
            background: rgba(255,255,255,0.06);
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            font-size: 0.94rem;
            font-weight: 800;
            transition: all 0.25s;
            margin-bottom: 1.25rem;
        }
        .botao-google:hover {
            border-color: rgba(247,148,29,0.45);
            background: rgba(247,148,29,0.08);
            transform: translateY(-1px);
        }

        /* ===== RESPONSIVE PARA ECRÃS PEQUENOS ===== */
        @media (max-width: 960px) {
            .painel-esquerdo { display: none; }
            .painel-direito { padding: 2rem 1.5rem; }
        }
        @media (max-width: 560px) {
            .grelha-2col { grid-template-columns: 1fr; }
            .grupo-campo.col-completa { grid-column: 1; }
            .painel-direito { padding: 2rem 1rem; }
            .titulo-formulario { font-size: 1.7rem; }
        }
    </style>
</head>
<body>

<!-- ===== FUNDO DECORATIVO GLOBAL ===== -->
<div class="fundo-direito"></div>
<div class="overlay-fundo-direito"></div>

<!-- ===== PAINEL ESQUERDO: INFORMATIVO / BARRA LATERAL ===== -->
<div class="painel-esquerdo">
    <div class="conteudo-esquerdo">

        <!-- Título apelativo do painel lateral de registo -->
        <h2 class="titulo-info">Junta-te à <span>nova geração</span> de profissionais africanos</h2>
        <p class="descricao-info">Em menos de 5 minutos, tens acesso à maior rede de mentoria e investimento de Angola.</p>

        <!-- Passos do processo de criação de conta mostrados lateralmente -->
        <div class="lista-passos">
            <div class="passo-item">
                <div class="passo-numero">1</div>
                <div class="passo-texto">
                    <strong>Cria o teu perfil</strong>
                    Preenche os dados básicos da tua conta.
                </div>
            </div>
            <div class="passo-item">
                <div class="passo-numero">2</div>
                <div class="passo-texto">
                    <strong>Verifica a tua identidade</strong>
                    Faz o upload do BI para desbloquear tudo.
                </div>
            </div>
            <div class="passo-item">
                <div class="passo-numero">3</div>
                <div class="passo-texto">
                    <strong>Explora e cresce</strong>
                    Conecta com mentores, investidores e projectos.
                </div>
            </div>
        </div>

        <!-- Caixa de garantia de segurança e privacidade -->
        <div class="caixa-seguranca">
            <i class="fas fa-shield-halved"></i>
            <p>Os teus dados estão protegidos. Nunca partilhamos informações pessoais sem o teu consentimento.</p>
        </div>

    </div>
</div>

<!-- ===== PAINEL DIREITO: FORMULÁRIO DE REGISTO ===== -->
<div class="painel-direito">
    <div class="conteudo-formulario">

        <!-- Link de navegação para voltar à página inicial -->
        <a href="../paginas/guest/landing.php" class="link-voltar">
            <i class="fas fa-arrow-left"></i> Voltar ao Início
        </a>

        <!-- Título e subtítulo da área de registo de nova conta -->
        <h1 class="titulo-formulario">Criar conta <span>grátis</span></h1>
        <p class="subtitulo-formulario">Junta-te a +500 membros que já fazem parte da comunidade KALIYE.</p>

        <!-- Alerta de erro que aparece se o registo falhar por algum motivo -->
        <?php if ($google_auth_enabled): ?>
            <a class="botao-google" href="google_iniciar.php?mode=register">
                <i class="fab fa-google"></i>
                Criar conta com Google
            </a>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alerta-erro">
                <i class="fas fa-exclamation-circle"></i>
                <?php
                    // Mostra a mensagem de erro específica conforme o código recebido
                    switch ($_GET['error']) {
                        case 'email_exists':
                            $msg_erro = 'Este email já está registado. Usa outro ou faz login.';
                            break;
                        case 'db_error':
                            $msg_erro = 'Erro no sistema. Por favor, tenta novamente.';
                            break;
                        case 'invalid_id':
                            $msg_erro = 'Formato de documento inválido. Verifica o BI ou Passaporte.';
                            break;
                        case 'terms_not_accepted':
                            $msg_erro = 'Deves aceitar os Termos e a Política de Privacidade.';
                            break;
                        case 'registrations_closed':
                            $msg_erro = 'Os registos estao temporariamente fechados pela administracao.';
                            break;
                        default:
                            $msg_erro = 'Erro ao criar conta. Verifica os dados inseridos.';
                            break;
                    }
                    echo htmlspecialchars($msg_erro);
                ?>
            </div>
        <?php endif; ?>

        <!-- Formulário principal de registo que envia para o endpoint da API -->
        <form id="formularioRegisto" action="../interface_programacao/auth/register_action.php" method="POST">

            <!-- ===== SECÇÃO 1: DADOS PESSOAIS ===== -->
            <div class="separador-secao">Dados Pessoais</div>
            <div class="grelha-2col">

                <!-- Campo do nome completo do utilizador -->
                <div class="grupo-campo col-completa">
                    <label class="etiqueta-campo" for="nome_completo">Nome Completo</label>
                    <div class="caixa-input">
                        <i class="fas fa-user icone-input"></i>
                        <input type="text" id="nome_completo" name="full_name"
                            class="campo-input"
                            placeholder="Ex: Abel Dengue" required
                            pattern="[a-zA-Z\u00C0-\u00FF\s]+"
                            title="Por favor, insira apenas letras no nome.">
                    </div>
                </div>

                <!-- Campo de data de nascimento do utilizador -->
                <div class="grupo-campo">
                    <label class="etiqueta-campo" for="data_nascimento">Data de Nascimento</label>
                    <div class="caixa-input">
                        <i class="fas fa-calendar icone-input"></i>
                        <input type="date" id="data_nascimento" name="birth_date"
                            class="campo-input"
                            max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <!-- Campo do número do BI ou Passaporte do utilizador -->
                <div class="grupo-campo">
                    <label class="etiqueta-campo" for="numero_documento">Nº BI ou Passaporte</label>
                    <div class="caixa-input">
                        <i class="fas fa-id-card icone-input"></i>
                        <input type="text" id="numero_documento" name="id_number"
                            class="campo-input"
                            placeholder="BI ou Passaporte" required
                            pattern="^(\d{9}[A-Z]{2}\d{3}|[A-Z]{2}\d{7})$"
                            maxlength="14"
                            title="BI: 9 números, 2 letras, 3 números. Passaporte: 2 letras, 7 números."
                            style="text-transform: uppercase;">
                    </div>
                </div>

            </div>

            <!-- ===== SECÇÃO 2: DADOS DE CONTACTO ===== -->
            <div class="separador-secao">Contacto &amp; Acesso</div>
            <div class="grelha-2col">

                <!-- Campo de endereço de email do utilizador -->
                <div class="grupo-campo col-completa">
                    <label class="etiqueta-campo" for="email">Endereço de Email</label>
                    <div class="caixa-input">
                        <i class="fas fa-envelope icone-input"></i>
                        <input type="email" id="email" name="email"
                            class="campo-input"
                            placeholder="nome@exemplo.com" required autocomplete="email">
                    </div>
                </div>

                <!-- Campo de número de telefone do utilizador -->
                <div class="grupo-campo">
                    <label class="etiqueta-campo" for="telefone">Telefone (Obrigatório)</label>
                    <div class="caixa-input">
                        <i class="fas fa-phone icone-input"></i>
                        <input type="tel" id="telefone" name="phone"
                            class="campo-input"
                            placeholder="+244 9..." required>
                    </div>
                </div>

                <!-- Campo de password com botão de mostrar/ocultar -->
                <div class="grupo-campo">
                    <label class="etiqueta-campo" for="senha">Criar Password</label>
                    <div class="caixa-input">
                        <i class="fas fa-lock icone-input"></i>
                        <input type="password" id="senha" name="password"
                            class="campo-input input-senha"
                            placeholder="••••••••" required>
                        <!-- Botão para alternar visibilidade da password -->
                        <button type="button" class="botao-olho" id="alternarSenha" aria-label="Ver password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

            </div>

            <!-- ===== SECÇÃO 3: TIPO DE PERFIL ===== -->
            <div class="separador-secao">Tipo de Perfil</div>

            <!-- Campo de selecção do tipo de utilizador na plataforma -->
            <div class="grupo-campo">
                <label class="etiqueta-campo" for="tipo_utilizador">O meu perfil é...</label>
                <select id="tipo_utilizador" name="user_type"
                    class="campo-input sem-icone" required
                    onchange="alternarCamposExtras()">
                    <option value="" disabled selected>Selecciona uma opção</option>
                    <option value="univ_student">Estudante Universitário</option>
                    <option value="high_student">Estudante do Ensino Médio</option>
                    <option value="mentor">Mentor (Especialista Profissional)</option>
                    <option value="investor">Investidor / Parceiro</option>
                </select>
            </div>

            <!-- Campo opcional de instituição ou organização do utilizador -->
            <div class="grupo-campo" id="campo-instituicao" style="display: none;">
                <label class="etiqueta-campo" for="organizacao">Instituição / Organização (Opcional)</label>
                <div class="caixa-input">
                    <i class="fas fa-building icone-input"></i>
                    <input type="text" id="organizacao" name="organization"
                        class="campo-input"
                        placeholder="Nome da escola ou empresa">
                </div>
            </div>

            <!-- Caixa especial de candidatura a Peer Mentor apenas para estudantes universitários -->
            <div class="caixa-peer-mentor" id="caixa-peer-mentor">
                <label class="label-checkbox">
                    <input type="checkbox" name="is_peer_mentor" value="1">
                    <div class="texto-checkbox">
                        <strong>Quero ser Peer Mentor também</strong>
                        <p>Candidata-te a mentor para alunos mais novos. A aprovação depende da análise do teu histórico académico pelos administradores.</p>
                    </div>
                </label>
            </div>

            <!-- ===== ACEITAÇÃO DE TERMOS E CONDIÇÕES ===== -->
            <div class="area-termos">
                <!-- Checkbox de aceitação dos Termos e Condições de uso -->
                <label class="linha-termos">
                    <input type="checkbox" name="accept_terms" value="1" required>
                    <span>Li e aceito os <a href="../paginas/legal/termos.php" target="_blank">Termos e Condições</a> de uso.</span>
                </label>

                <!-- Checkbox de aceitação da Política de Privacidade -->
                <label class="linha-termos">
                    <input type="checkbox" name="accept_privacy" value="1" required>
                    <span>Li e aceito a <a href="../paginas/legal/privacidade.php" target="_blank">Política de Privacidade</a>.</span>
                </label>
            </div>

            <!-- Botão principal de criação de conta com efeito de brilho -->
            <button type="submit" class="botao-registar" id="botaoCriarConta">
                <i class="fas fa-rocket"></i>
                <span id="textoBotao">Criar Conta Grátis</span>
            </button>

        </form>

        <!-- Link para utilizadores que já têm conta e querem entrar -->
        <div class="link-entrar">
            Já tens conta? <a href="entrar.php">Entra aqui <i class="fas fa-arrow-right"></i></a>
        </div>

    </div>
</div>

<script>
    // Carregamento automático do perfil via URL para facilitar o registo
    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const role = urlParams.get('role');
        const selectPerfil = document.getElementById('tipo_utilizador');

        if (role && selectPerfil) {
            // Tenta encontrar a opção correspondente no select
            for (let i = 0; i < selectPerfil.options.length; i++) {
                if (selectPerfil.options[i].value === role) {
                    selectPerfil.selectedIndex = i;
                    // Dispara a função que mostra campos extras (instituição, peer mentor, etc)
                    if (typeof alternarCamposExtras === 'function') {
                        alternarCamposExtras();
                    }
                    break;
                }
            }
        }
    });

    // Alterna a visibilidade da password entre texto e ponto
    document.getElementById('alternarSenha').addEventListener('click', function() {
        const campo = document.getElementById('senha');
        const icone = this.querySelector('i');
        // Troca o tipo do campo entre 'password' e 'text'
        campo.type = campo.type === 'password' ? 'text' : 'password';
        // Actualiza o ícone conforme o estado actual
        icone.className = campo.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    });

    // Valida que o nome não contém números (apenas letras e espaços)
    document.getElementById('nome_completo').addEventListener('input', function() {
        this.value = this.value.replace(/[0-9]/g, '');
    });

    // Converte o documento para maiúsculas e remove caracteres inválidos
    document.getElementById('numero_documento').addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^0-9A-Z]/g, '');
        // Limita o tamanho máximo a 14 caracteres
        if (this.value.length > 14) this.value = this.value.substring(0, 14);
    });

    // Alterna os campos extras baseado no tipo de utilizador seleccionado
    function alternarCamposExtras() {
        const tipo = document.getElementById('tipo_utilizador').value;
        const secaoPeer = document.getElementById('caixa-peer-mentor');
        const secaoInstituicao = document.getElementById('campo-instituicao');

        // Mostra o campo de Peer Mentor apenas para estudantes universitários
        secaoPeer.style.display = (tipo === 'univ_student') ? 'block' : 'none';
        if (tipo !== 'univ_student') {
            document.querySelector('input[name="is_peer_mentor"]').checked = false;
        }

        // Mostra o campo de instituição para todos os tipos de utilizador
        secaoInstituicao.style.display = tipo ? 'block' : 'none';
    }

    // Mostra feedback de carregamento ao submeter o formulário
    document.getElementById('formularioRegisto').addEventListener('submit', function() {
        const botao = document.getElementById('botaoCriarConta');
        const texto = document.getElementById('textoBotao');
        // Muda o texto do botão e desactiva-o durante o envio
        texto.textContent = 'A criar conta...';
        botao.disabled = true;
        botao.style.opacity = '0.8';
    });
</script>
</body>
</html>
