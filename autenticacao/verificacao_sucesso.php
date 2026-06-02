<?php
/**
 * verificacao_sucesso.php
 * Página de confirmação após verificação bem-sucedida de email
 * KALIYE - 02 de Junho de 2026
 */
session_start();
require_once __DIR__ . '/../configuracoes/base_dados.php';

$email = $_GET['email'] ?? '';

// Mascarar email para exibição
$masked_email = '';
if ($email) {
    $parts = explode('@', $email);
    if (count($parts) === 2) {
        $name = $parts[0];
        $domain = $parts[1];
        if (strlen($name) > 4) {
            $masked_name = substr($name, 0, 2) . str_repeat('*', strlen($name) - 4) . substr($name, -2);
        } else {
            $masked_name = $name[0] . str_repeat('*', strlen($name) - 1);
        }
        $masked_email = $masked_name . '@' . $domain;
    }
}

// Se não houver email, redirecionar para registro
if (!$email) {
    header("Location: registar.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conta Criada com Sucesso | KALIYE</title>
    <meta name="description" content="Sua conta foi verificada com sucesso. Faça login para começar.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../recursos/images/marca/apple-touch-icon-k.png">

    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #070d1a 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 60px 40px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .icone-sucesso {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 30px;
            animation: bounce 0.6s ease-out;
        }

        @keyframes bounce {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        h1 {
            font-size: 32px;
            color: #ffffff;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .subtitulo {
            color: #94a3b8;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .email-display {
            background: rgba(247, 148, 29, 0.1);
            border: 1px solid rgba(247, 148, 29, 0.3);
            border-radius: 8px;
            padding: 15px 20px;
            margin: 25px 0;
            color: #f7941d;
            font-weight: 500;
            word-break: break-all;
        }

        .mensagem-confirmacao {
            color: #94a3b8;
            font-size: 14px;
            margin: 20px 0;
            line-height: 1.6;
        }

        .botoes {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .botao {
            flex: 1;
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            min-width: 150px;
        }

        .botao-primario {
            background: linear-gradient(135deg, #f7941d 0%, #ff9f3d 100%);
            color: white;
        }

        .botao-primario:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(247, 148, 29, 0.3);
        }

        .botao-secundario {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .botao-secundario:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .checklist {
            text-align: left;
            margin: 30px 0;
            background: rgba(40, 167, 69, 0.1);
            border-left: 3px solid #28a745;
            padding: 20px;
            border-radius: 8px;
        }

        .item-checklist {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            font-size: 14px;
            margin: 10px 0;
        }

        .item-checklist i {
            color: #28a745;
            font-size: 18px;
        }

        @media (max-width: 520px) {
            .container {
                padding: 40px 25px;
            }

            h1 {
                font-size: 24px;
            }

            .botoes {
                flex-direction: column;
            }

            .botao {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Ícone de Sucesso -->
    <div class="icone-sucesso">
        <i class="fas fa-check-circle"></i>
    </div>

    <!-- Título -->
    <h1>Conta Criada com Sucesso!</h1>

    <!-- Subtítulo -->
    <div class="subtitulo">
        Sua conta foi verificada e está pronta para usar.
    </div>

    <!-- Email Confirmado -->
    <div class="email-display">
        <i class="fas fa-envelope" style="margin-right: 8px;"></i>
        <?php echo htmlspecialchars($masked_email ?: 'Email verificado'); ?>
    </div>

    <!-- Próximos Passos -->
    <div class="checklist">
        <div class="item-checklist">
            <i class="fas fa-check"></i>
            <span>Email verificado</span>
        </div>
        <div class="item-checklist">
            <i class="fas fa-check"></i>
            <span>Conta ativada</span>
        </div>
        <div class="item-checklist">
            <i class="fas fa-check"></i>
            <span>Pronto para fazer login</span>
        </div>
    </div>

    <!-- Mensagem -->
    <div class="mensagem-confirmacao">
        <p style="margin-bottom: 10px;">
            <strong>Bem-vindo à KALIYE!</strong>
        </p>
        <p>
            Agora você pode fazer login com suas credenciais e começar a sua jornada.
        </p>
    </div>

    <!-- Botões de Ação -->
    <div class="botoes">
        <a href="entrar.php" class="botao botao-primario">
            <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
            Fazer Login
        </a>
        <a href="../index.php" class="botao botao-secundario">
            <i class="fas fa-home" style="margin-right: 8px;"></i>
            Início
        </a>
    </div>
</div>

</body>
</html>
