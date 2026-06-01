<?php
// admin/admins.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('users')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$admins = $db->query("SELECT * FROM users WHERE user_type = 'admin'")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Administradores - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.openAdminModal = function() { 
                const modal = document.getElementById('adminModal');
                if (modal) {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            };
            
            window.closeAdminModal = function() { 
                const modal = document.getElementById('adminModal');
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            };

            window.restrictAdmin = function() {
                Swal.fire({
                    title: 'Funcionalidade Restrita',
                    text: 'As alterações de privilégios requerem autenticação de segundo fator (2FA) do Super Administrador.',
                    icon: 'info',
                    background: '#050a15',
                    color: '#fff',
                    confirmButtonColor: '#f7941d'
                });
            };

            const adminForm = document.getElementById('createAdminForm');
            if (adminForm) {
                adminForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const submitBtn = adminForm.querySelector('[type="submit"]');
                    const originalText = submitBtn ? submitBtn.innerHTML : '';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> A CRIAR...';
                    }

                    try {
                        const response = await fetch(adminForm.action, {
                            method: 'POST',
                            body: new FormData(adminForm)
                        });
                        const data = await response.json();

                        if (data.success) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Administrador criado',
                                text: data.message || 'Credenciais criadas com sucesso.',
                                background: '#0f172a',
                                color: '#fff',
                                confirmButtonColor: '#f7941d'
                            });
                            location.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: data.message || 'Não foi possível criar o administrador.',
                                background: '#0f172a',
                                color: '#fff',
                                confirmButtonColor: '#f7941d'
                            });
                        }
                    } catch (err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Falha de comunicacao',
                            text: 'O servidor não devolveu uma resposta valida.',
                            background: '#0f172a',
                            color: '#fff',
                            confirmButtonColor: '#f7941d'
                        });
                    } finally {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    }
                });
            }

            // Close modal if clicking outside content
            window.addEventListener('click', function(e) {
                const modal = document.getElementById('adminModal');
                if (e.target === modal) {
                    window.closeAdminModal();
                }
            });
        });
    </script>
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
                <h1>Corpo Administrativo</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Gestão de identidades e privilégios de alto nível.</p>
            </div>
            <button onclick="openAdminModal()" class="btn-admin btn-admin-primary">
                <i class="fas fa-plus"></i> NOVO ADMINISTRADOR
            </button>
        </header>

        <div class="admin-card-premium" style="padding: 0;">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Identidade</th>
                            <th>Contacto Digital</th>
                            <th>Data de Ingresso</th>
                            <th>Privilégios / Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($admins as $a): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="position: relative;">
                                        <?php 
                                            $final_pic = $base_url . getUserAvatarUrl($a['user_type'], $a['mentorship_status'] ?? 'unsubmitted');
                                        ?>
                                        <img src="<?= $final_pic ?>" 
                                             onerror="this.src='../../recursos/images/marca/favicon-k-32x32.png'; this.style.padding='4px'; this.style.background='#fff';"
                                             style="width: 40px; height: 40px; border-radius: 12px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                        <?php if($_SESSION['user_id'] == $a['user_id']): ?>
                                            <span style="position: absolute; -1px; -1px; width: 10px; height: 10px; background: #34d399; border-radius: 50%; border: 2px solid #050a15;"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 800; color: #fff; font-size: 0.9rem;"><?= htmlspecialchars($a['full_name']) ?></div>
                                        <?php if($_SESSION['user_id'] == $a['user_id']): ?>
                                            <span style="font-size: 0.6rem; color: #34d399; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">SESSÃO ATIVA</span>
                                        <?php else: ?>
                                            <span style="font-size: 0.6rem; color: rgba(255,255,255,0.3); font-weight: 700; text-transform: uppercase;">ID: #<?= $a['user_id'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.85rem; color: rgba(255,255,255,0.6); font-weight: 500;"><?= htmlspecialchars($a['email']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 700; font-size: 0.8rem; color: #fff;"><?= date('d M, Y', strtotime($a['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if($_SESSION['user_id'] != $a['user_id']): ?>
                                        <button onclick="restrictAdmin()" class="btn-action" title="Restringir Acesso" style="color: rgba(255,255,255,0.2);"><i class="fas fa- lock"></i></button>
                                        <button onclick="Swal.fire({title: 'Segurança', text: 'Apenas SuperAdmins podem remover administradores.', background: '#0f172a', color: '#fff'})" class="btn-action" title="Remover" style="color: rgba(239, 68, 68, 0.3);"><i class="fas fa-trash-alt"></i></button>
                                    <?php else: ?>
                                        <span style="font-size: 0.7rem; background: rgba(247, 148, 29, 0.1); color: #f7941d; padding: 4px 10px; border-radius: 6px; font-weight: 800; border: 1px solid rgba(247, 148, 29, 0.2);">SUPER ADMIN</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Novo Admin -->
    <div id="adminModal" class="admin-modal-overlay">
        <div class="admin-modal-content" style="max-width: 450px; background: #0d1628; border: 1px solid rgba(255,255,255,0.08);">
            <div class="admin-modal-header">
                <h3 style="color: #fff;">Novo Administrador</h3>
                <button onclick="closeAdminModal()" class="close-btn">&times;</button>
            </div>
            <form id="createAdminForm" action="../../interface_programacao/admin/create_admin.php" method="POST" style="padding: 2rem; max-height: 70vh; overflow-y: auto;">
                <div class="input-group-premium" style="margin-bottom: 1.25rem;">
                    <label style="color: rgba(255,255,255,0.5);">NOME COMPLETO</label>
                    <input type="text" name="full_name" required placeholder="Nome completo do administrador" style="background: rgba(255,255,255,0.03); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <div class="input-group-premium" style="margin-bottom: 1.25rem;">
                    <label style="color: rgba(255,255,255,0.5);">EMAIL INSTITUCIONAL</label>
                    <input type="email" name="email" required placeholder="admin@kaliye.com" style="background: rgba(255,255,255,0.03); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <div class="input-group-premium" style="margin-bottom: 2rem;">
                    <label style="color: rgba(255,255,255,0.5);">CHAVE DE ACESSO (PASSWORD)</label>
                    <input type="password" name="password" required placeholder="••••••••••••••••" style="background: rgba(255,255,255,0.03); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="color: #f7941d; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 1rem;">PERMISSÕES DE ACESSO:</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="dashboard" id="perm_dashboard" style="cursor: pointer;">
                            <label for="perm_dashboard" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Dashboard</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="users" id="perm_users" style="cursor: pointer;">
                            <label for="perm_users" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Gestão de Utilizadores</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="ads" id="perm_ads" style="cursor: pointer;">
                            <label for="perm_ads" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Publicidade</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="moderation" id="perm_moderation" style="cursor: pointer;">
                            <label for="perm_moderation" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Moderação</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="support" id="perm_support" style="cursor: pointer;">
                            <label for="perm_support" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Suporte</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="kyc" id="perm_kyc" style="cursor: pointer;">
                            <label for="perm_kyc" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">KYC</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="mentor_approval" id="perm_mentor_approval" style="cursor: pointer;">
                            <label for="perm_mentor_approval" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Acolhimento Mentores</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="finance_docs" id="perm_finance_docs" style="cursor: pointer;">
                            <label for="perm_finance_docs" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Documentos Financeiros</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="finances" id="perm_finances" style="cursor: pointer;">
                            <label for="perm_finances" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Finanças</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="mentor_assignment" id="perm_mentor_assignment" style="cursor: pointer;">
                            <label for="perm_mentor_assignment" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Atribuição Mentores</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="legal" id="perm_legal" style="cursor: pointer;">
                            <label for="perm_legal" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Gestão Legal</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="settings" id="perm_settings" style="cursor: pointer;">
                            <label for="perm_settings" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Definições</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="chat_monitor" id="perm_chat_monitor" style="cursor: pointer;">
                            <label for="perm_chat_monitor" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Monitoramento Chat</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="mentorship_quality" id="perm_mentorship_quality" style="cursor: pointer;">
                            <label for="perm_mentorship_quality" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Qualidade Mentoria</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="permissions[]" value="audit" id="perm_audit" style="cursor: pointer;">
                            <label for="perm_audit" style="color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer; margin: 0;">Auditoria</label>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="button" onclick="closeAdminModal()" class="btn-admin" style="flex: 1; border: 1px solid rgba(255,255,255,0.1); color: #fff; background: transparent;">CANCELAR</button>
                    <button type="submit" class="btn-admin btn-admin-primary" style="flex: 2;">CRIAR CREDENCIAIS</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>

