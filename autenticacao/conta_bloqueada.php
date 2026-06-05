<?php
/**
 * autenticacao/conta_bloqueada.php
 * Página exibida quando um utilizador é bloqueado permanentemente (Hard Lock).
 * Instrui o utilizador a contactar a equipa Aksanti para desbloquear.
 */
session_start();
require_once __DIR__ . '/../configuracoes/base_dados.php';

$action = htmlspecialchars($_GET['action'] ?? 'login');
$reason = htmlspecialchars($_GET['reason'] ?? 'suspicious_activity');

// Mapear ação para texto legível
$action_labels = [
    'login'    => 'início de sessão',
    'register' => 'criação de conta',
    'payment'  => 'operação financeira',
    'withdrawal' => 'levantamento',
];
$action_label = $action_labels[$action] ?? 'acesso';

// Obter nome do site
try {
    $database = new Database();
    $db = $database->getConnection();
    $site_name = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'")->fetchColumn() ?: 'KALIYE';
    $support_email = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'support_email'")->fetchColumn() ?: 'seguranca@aksanti.ao';
} catch (Exception $e) {
    $site_name = 'KALIYE';
    $support_email = 'seguranca@aksanti.ao';
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conta Bloqueada | <?= $site_name ?></title>
    <meta name="robots" content="noindex, nofollow">

        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --cor-fundo: #070d1a;
            --cor-cartao: #0f172a;
            --cor-bordas: rgba(255,255,255,0.08);
            --cor-laranja: #f7941d;
            --cor-vermelho: #ef4444;
            --cor-texto: #ffffff;
            --cor-sub: #94a3b8;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            height: 100%; font-family: 'Inter', sans-serif;
            background: var(--cor-fundo); color: var(--cor-texto);
        }

        body {
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; padding: 2rem;
            background: radial-gradient(ellipse at 50% 0%, rgba(239,68,68,0.07) 0%, transparent 60%),
                        var(--cor-fundo);
        }

        .container {
            max-width: 520px; width: 100%;
            text-align: center; animation: entrar 0.6s ease forwards;
        }

        @keyframes entrar {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Ícone central animado */
        .icone-bloqueio {
            width: 96px; height: 96px; border-radius: 50%;
            background: rgba(239,68,68,0.1);
            border: 2px solid rgba(239,68,68,0.2);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2.5rem; color: var(--cor-vermelho);
            animation: pulso 2s ease-in-out infinite;
        }
        @keyframes pulso {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.3); }
            50%       { box-shadow: 0 0 0 20px rgba(239,68,68,0); }
        }

        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem; font-weight: 900; letter-spacing: -0.5px;
            color: var(--cor-texto); margin-bottom: 0.75rem;
        }
        h1 span { color: var(--cor-vermelho); }

        .subtitulo {
            font-size: 0.95rem; color: var(--cor-sub); line-height: 1.7;
            margin-bottom: 2rem;
        }

        /* Card de informação */
        .card-info {
            background: var(--cor-cartao);
            border: 1px solid var(--cor-bordas);
            border-radius: 16px; padding: 1.75rem;
            margin-bottom: 1.5rem; text-align: left;
        }

        .card-info h3 {
            font-size: 0.8rem; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: var(--cor-laranja);
            margin-bottom: 1rem;
        }

        .passo {
            display: flex; align-items: flex-start; gap: 1rem;
            margin-bottom: 1rem; padding-bottom: 1rem;
            border-bottom: 1px solid var(--cor-bordas);
        }
        .passo:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }

        .passo-num {
            width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
            background: rgba(247,148,29,0.12); border: 1px solid rgba(247,148,29,0.25);
            color: var(--cor-laranja); font-size: 0.75rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }
        .passo h4 { font-size: 0.85rem; font-weight: 700; margin-bottom: 0.2rem; }
        .passo p  { font-size: 0.78rem; color: var(--cor-sub); line-height: 1.5; }

        /* Botão de contacto */
        .botao-contacto {
            display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            width: 100%; padding: 0.9rem;
            background: linear-gradient(135deg, var(--cor-laranja), #e07b0e);
            border: none; border-radius: 12px;
            color: #fff; font-size: 0.95rem; font-weight: 700;
            font-family: 'Inter', sans-serif; cursor: pointer;
            text-decoration: none; transition: all 0.3s;
            box-shadow: 0 6px 25px rgba(247,148,29,0.25);
            margin-bottom: 1rem;
        }
        .botao-contacto:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(247,148,29,0.35);
        }

        .link-voltar {
            display: inline-flex; align-items: center; gap: 0.5rem;
            color: var(--cor-sub); text-decoration: none;
            font-size: 0.85rem; font-weight: 500; transition: color 0.3s;
        }
        .link-voltar:hover { color: var(--cor-laranja); }

        /* Badge de motivo */
        .badge-motivo {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.15);
            border-radius: 50px; padding: 0.3rem 0.85rem;
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;
            text-transform: uppercase; color: #f87171; margin-bottom: 1.5rem;
        }

        /* Código de referência para o suporte */
        .codigo-ref {
            background: rgba(255,255,255,0.03); border: 1px solid var(--cor-bordas);
            border-radius: 8px; padding: 0.6rem 1rem; margin-bottom: 1.5rem;
            font-size: 0.78rem; color: var(--cor-sub); font-family: monospace;
        }
        .codigo-ref strong { color: #fff; }
    </style>
    <?php 
    if (!function_exists('renderKaliyeFavicons')) {
        $root_dir_favicon = __DIR__;
        while (!is_dir($root_dir_favicon . '/inclusoes') && dirname($root_dir_favicon) !== $root_dir_favicon) {
            $root_dir_favicon = dirname($root_dir_favicon);
        }
        require_once $root_dir_favicon . '/inclusoes/components/favicon.php';
    }
    renderKaliyeFavicons($base_url ?? './'); 
    ?>
</head>
<body>
<div class="container">

    <!-- Ícone de bloqueio com animação pulsante -->
    <div class="icone-bloqueio">
        <i class="fas fa-shield-exclamation"></i>
    </div>

    <!-- Badge de estado -->
    <div class="badge-motivo">
        <i class="fas fa-lock"></i> Acesso Bloqueado por Segurança
    </div>

    <!-- Título e descrição -->
    <h1>A tua conta foi <span>bloqueada</span></h1>
    <p class="subtitulo">
        Detetámos demasiadas tentativas suspeitas de <?= $action_label ?> a partir deste dispositivo.
        Por segurança, o acesso foi suspenso temporariamente.
    </p>

    <!-- Código de referência para o suporte -->
    <?php
        $ref_code = strtoupper(substr(md5($_SERVER['REMOTE_ADDR'] . date('Ymd') . $action), 0, 8));
    ?>
    <div class="codigo-ref">
        Código de referência para suporte: <strong>#<?= $ref_code ?></strong>
    </div>

    <!-- Passos para desbloquear -->
    <div class="card-info">
        <h3><i class="fas fa-list-check" style="margin-right:0.5rem;"></i>Como desbloquear a tua conta</h3>

        <div class="passo">
            <div class="passo-num">1</div>
            <div>
                <h4>Envia um email à equipa de segurança</h4>
                <p>Inclui o teu email de registo e o código de referência acima no pedido.</p>
            </div>
        </div>

        <div class="passo">
            <div class="passo-num">2</div>
            <div>
                <h4>Verificação de identidade</h4>
                <p>A equipa irá confirmar a tua identidade antes de proceder ao desbloqueio.</p>
            </div>
        </div>

        <div class="passo">
            <div class="passo-num">3</div>
            <div>
                <h4>Desbloqueio em até 24 horas</h4>
                <p>Após confirmação, a tua conta será desbloqueada e receberás confirmação por email.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Botão de contacto -->
    <button onclick="openUnlockModal('<?= $ref_code ?>')" class="botao-contacto">
        <i class="fas fa-lock-open"></i>
        Pedir Desbloqueio ao Administrador
    </button>

    <script>
    function openUnlockModal(refCode) {
        Swal.fire({
            title: 'Pedir Desbloqueio',
            html: `
                <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 1.5rem;">Por favor, indique os dados da sua conta para que a nossa equipa possa analisar e remover a restrição.</p>
                <div style="display: flex; flex-direction: column; gap: 1rem; text-align: left;">
                    <div>
                        <label style="font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 700; margin-bottom: 0.3rem; display: block;">Nome Completo <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="swal-input-name" class="swal2-input swal-input-custom" placeholder="O seu nome..." style="margin:0; width:100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 700; margin-bottom: 0.3rem; display: block;">Email de Registo <span style="color:#ef4444;">*</span></label>
                        <input type="email" id="swal-input-email" class="swal2-input swal-input-custom" placeholder="email@exemplo.com" style="margin:0; width:100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 700; margin-bottom: 0.3rem; display: block;">O que estava a fazer antes do bloqueio? (Opcional)</label>
                        <textarea id="swal-input-reason" class="swal2-textarea swal-input-custom" placeholder="Descreva brevemente a situação..." maxlength="250" style="margin:0; width:100%; box-sizing: border-box; min-height: 80px;"></textarea>
                        <div style="text-align: right; font-size: 0.65rem; color: rgba(255,255,255,0.4); margin-top: 4px;">Máx. 250 caracteres</div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-paper-plane"></i> ENVIAR PEDIDO',
            cancelButtonText: 'Cancelar',
            background: '#0d1628',
            color: '#fff',
            confirmButtonColor: '#f7941d',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
            preConfirm: () => {
                const name = document.getElementById('swal-input-name').value;
                const email = document.getElementById('swal-input-email').value;
                const reason = document.getElementById('swal-input-reason').value;

                if (!name || !email) {
                    Swal.showValidationMessage('O nome e o email são obrigatórios!');
                    return false;
                }

                const fd = new FormData();
                fd.append('name', name);
                fd.append('email', email);
                fd.append('reason', reason);
                fd.append('ref_code', refCode);

                return fetch('../interface_programacao/admin/request_unlock.php', { method: 'POST', body: fd })
                    .then(response => {
                        return response.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error("Server output:", text);
                                throw new Error("Erro do servidor (Caminho/Rede): " + text.substring(0, 100));
                            }
                        });
                    })
                    .then(data => {
                        if (!data.success) throw new Error(data.message);
                        return data;
                    })
                    .catch(error => { Swal.showValidationMessage(error.message); });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Pedido Enviado', 
                    text: 'A equipa administrativa recebeu o seu pedido e irá verificar a sua conta em breve.', 
                    background: '#0d1628', 
                    color: '#fff',
                    confirmButtonColor: '#10b981'
                }).then(() => {
                    window.location.href = 'entrar.php';
                });
            }
        });
    }
    </script>
    <style>
        .swal-input-custom { background: rgba(0,0,0,0.3) !important; color: #fff !important; border: 1px solid rgba(255,255,255,0.1) !important; outline: none; font-size: 0.9rem !important; }
        .swal-input-custom:focus { border-color: rgba(247,148,29,0.5) !important; box-shadow: 0 0 10px rgba(247,148,29,0.1) !important; }
    </style>

    <!-- Link de regresso -->
    <a href="../autenticacao/entrar.php" class="link-voltar">
        <i class="fas fa-arrow-left"></i> Voltar ao início de sessão
    </a>

</div>
</body>
</html>
