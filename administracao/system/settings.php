<?php
/**
 * admin/settings.php - System Configuration
 * Refactored into a component-based structure.
 */
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('settings')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch all settings
$settings = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch users for legal modal
$modal_users = $db->query("SELECT user_id, full_name, user_type FROM users WHERE user_type != 'admin' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definições - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <a href="../index.php" style="color: var(--aksanti-orange); text-decoration: none; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-arrow-left"></i> Voltar ao Painel
                </a>
                <h1>Configurações do Sistema</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Parâmetros globais, segurança e infraestrutura.</p>
            </div>
            <div style="background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); padding: 0.6rem 1.25rem; border-radius: 12px; font-size: 0.75rem; color: #34d399; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981;"></span>
                NÚCLEO ATIVO
            </div>
        </header>

        <form action="../../interface_programacao/admin/admin_save_settings.php" method="POST" id="settingsForm">
            <?= getCSRFHiddenInput() ?>
            <!-- Settings Cards Component -->
            <?php include '../../inclusoes/components/admin/settings_cards.php'; ?>

            <!-- Sticky Save Bar -->
            <div id="saveBar" class="admin-save-bar" style="display: none; position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%); background: #1e293b; border: 1px solid rgba(255,255,255,0.1); padding: 1rem 2rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); z-index: 1000; align-items: center; gap: 2rem; backdrop-filter: blur(10px);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-exclamation-triangle" style="color: #f7941d;"></i>
                    <span style="color: #fff; font-weight: 600; font-size: 0.9rem;">Existem alterações não guardadas.</span>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="button" onclick="location.reload()" class="btn-admin" style="background: rgba(255,255,255,0.05); color: #fff;">Descartar</button>
                    <button type="submit" class="btn-admin btn-admin-primary">Guardar Alterações</button>
                </div>
            </div>
        </form>

        <!-- Legal Tools Shortcut -->
        <section style="margin-top: 3rem; padding: 2.5rem; border-radius: 24px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 10, 21, 0.1)); border: 1px dashed rgba(16, 185, 129, 0.2); position: relative; overflow: hidden;">
            <div style="position: absolute; right: -20px; bottom: -20px; font-size: 10rem; opacity: 0.03; color: #10b981; pointer-events: none;">
                <i class="fas fa-file-contract"></i>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
                <div>
                    <h3 style="margin: 0; color: #34d399; font-weight: 800; font-size: 1.25rem;"><i class="fas fa-file-signature"></i> Formalização Legal</h3>
                    <p style="color: rgba(255,255,255,0.4); margin: 0.5rem 0 0 0; font-size: 0.9rem;">Automatize a geração e envio de contratos inteligentes para assinatura digital.</p>
                </div>
                <button onclick="openLegalModal()" class="btn-admin" style="background: rgba(16, 185, 129, 0.2); color: #34d399; border-color: rgba(16, 185, 129, 0.3);"><i class="fas fa-plus"></i> NOVO CONTRATO</button>
            </div>
        </section>
    </main>

    <script>
    const form = document.getElementById('settingsForm');
    const bar = document.getElementById('saveBar');

    form.addEventListener('change', () => bar.style.display = 'flex');
    form.addEventListener('input', () => bar.style.display = 'flex');

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        Swal.fire({
            title: 'Configurações Guardadas!',
            text: 'As alterações foram aplicadas com sucesso.',
            icon: 'success',
            background: '#0f172a',
            color: '#ffffff',
            confirmButtonColor: '#f7941d',
            borderRadius: '20px'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    function openLegalModal() {
        window.location.href = 'legal_management.php';
    }

    function runAdminAutomation(dryRun) {
        const formData = new FormData();
        formData.append('dry_run', dryRun ? '1' : '0');
        formData.append('csrf_token', '<?= generateCSRFToken() ?>');

        Swal.fire({
            title: 'A executar automações...',
            text: 'A aplicar filas administrativas e regras configuradas.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
            background: '#0f172a',
            color: '#ffffff'
        });

        fetch('../../interface_programacao/admin/run_admin_automation.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Falha desconhecida.');
            const list = (data.actions || []).slice(0, 12).map(item => `<li>${item.label}</li>`).join('');
            Swal.fire({
                title: 'Automação executada',
                html: `<div style="text-align:left; color:rgba(255,255,255,0.75); font-size:0.9rem;">
                    <p>${data.message}</p>
                    ${list ? `<ul style="padding-left:1.2rem;">${list}</ul>` : '<p>Nenhuma ação pendente neste momento.</p>'}
                </div>`,
                icon: 'success',
                background: '#0f172a',
                color: '#ffffff',
                confirmButtonColor: '#14b8a6'
            }).then(() => {
                if (!dryRun) location.reload();
            });
        })
        .catch(err => {
            Swal.fire({
                title: 'Erro na automação',
                text: err.message,
                icon: 'error',
                background: '#0f172a',
                color: '#ffffff',
                confirmButtonColor: '#f7941d'
            });
        });
    }
    </script>
</body>
</html>



