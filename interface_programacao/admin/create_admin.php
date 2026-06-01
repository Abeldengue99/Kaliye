<?php
/**
 * interface_programacao/admin/create_admin.php
 * Creates an administrator from the dedicated admin screen.
 */
@session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';
require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';

if (!isAdmin() || !hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$permissions = isset($_POST['permissions']) ? (array)$_POST['permissions'] : [];

if ($full_name === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Preencha nome, email e senha.']);
    exit;
}

if (empty($permissions)) {
    echo json_encode(['success' => false, 'message' => 'Selecione pelo menos uma permissão para o administrador.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalido.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 8 caracteres.']);
    exit;
}

try {
    $db = (new Database())->getConnection();

    $check = $db->prepare('SELECT user_id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Este email ja esta registado.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO users (full_name, email, password_hash, user_type, is_verified, created_at, updated_at)
        VALUES (?, ?, ?, 'admin', true, NOW(), NOW())
        RETURNING user_id
    ");
    $stmt->execute([$full_name, $email, $hash]);
    $new_id = (int)$stmt->fetchColumn();

    // Validar e atribuir permissões fornecidas
    $allowedPermissions = ['dashboard', 'users', 'ads', 'moderation', 'support', 'kyc', 'mentor_approval', 'mentor_assignment', 'finance_docs', 'finances', 'legal', 'settings', 'chat_monitor', 'mentorship_quality', 'audit'];
    $customPermissions = array_filter(array_map('trim', $permissions), function($p) use ($allowedPermissions) {
        return in_array($p, $allowedPermissions, true);
    });
    
    $permStmt = $db->prepare('INSERT INTO admin_permissions (user_id, permission_slug, created_at) VALUES (?, ?, NOW())');
    foreach ($customPermissions as $permission) {
        $permStmt->execute([$new_id, $permission]);
    }

    $db->commit();

    // Preparar lista de permissões para email
    $permissionLabels = [
        'dashboard' => 'Dashboard',
        'users' => 'Gestão de Utilizadores',
        'ads' => 'Publicidade',
        'moderation' => 'Moderação',
        'support' => 'Suporte',
        'kyc' => 'KYC',
        'mentor_approval' => 'Acolhimento Mentores',
        'mentor_assignment' => 'Atribuição Mentores',
        'finance_docs' => 'Documentos Financeiros',
        'finances' => 'Finanças',
        'legal' => 'Gestão Legal',
        'settings' => 'Definições',
        'chat_monitor' => 'Monitoramento Chat',
        'mentorship_quality' => 'Qualidade Mentoria',
        'audit' => 'Auditoria'
    ];
    
    $permissionsList = '';
    foreach ($customPermissions as $perm) {
        $label = $permissionLabels[$perm] ?? ucfirst(str_replace('_', ' ', $perm));
        $permissionsList .= "<li style='margin-bottom: 8px;'>✓ <strong>$label</strong></li>";
    }
    
    // Enviar email com credenciais
    $mailer = new SimpleMailer();
    $emailBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px;'>
        <div style='background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
            <h2 style='color: #050a15; margin-bottom: 20px; text-align: center;'>Bem-vindo ao Painel KALIYE Admin</h2>
            
            <p style='color: #333; font-size: 16px; line-height: 1.6;'>Olá <strong>$full_name</strong>,</p>
            
            <p style='color: #666; font-size: 14px; line-height: 1.6;'>Sua conta de administrador foi criada com sucesso. Abaixo encontra as suas credenciais de acesso e as permissões atribuídas:</p>
            
            <div style='background: #f9f9f9; border-left: 4px solid #f7941d; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                <p style='color: #333; margin: 8px 0;'><strong>Email:</strong> <code style='background: #eee; padding: 2px 6px; border-radius: 3px;'>$email</code></p>
                <p style='color: #333; margin: 8px 0;'><strong>Senha Temporária:</strong> <code style='background: #eee; padding: 2px 6px; border-radius: 3px;'>$password</code></p>
            </div>
            
            <p style='color: #333; font-size: 14px; font-weight: bold; margin-top: 20px; margin-bottom: 10px;'>Permissões de Acesso Atribuídas:</p>
            <ul style='color: #666; font-size: 13px; line-height: 1.8; background: #f0f4f8; padding: 15px; border-radius: 6px; border-left: 3px solid #f7941d;'>
                $permissionsList
            </ul>
            
            <p style='color: #666; font-size: 14px; line-height: 1.6; margin-top: 20px;'><strong>⚠️ Importante:</strong></p>
            <ul style='color: #666; font-size: 14px; line-height: 1.8;'>
                <li>Use estas credenciais para fazer login no painel administrativo</li>
                <li>Terá acesso APENAS às funcionalidades listadas acima</li>
                <li>Recomendamos mudar a senha assim que fizer o primeiro acesso</li>
                <li>Guarde a senha num local seguro</li>
            </ul>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                <a href='https://192.168.0.195/aksanti/administracao/index.php' style='display: inline-block; background: #f7941d; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold;'>Aceder ao Painel Admin</a>
            </div>
            
            <p style='color: #999; font-size: 12px; text-align: center; margin-top: 20px;'>Se tem dúvidas, contacte o suporte técnico.</p>
        </div>
    </div>
    ";
    
    $mailer->send($email, $full_name, 'Credenciais de Acesso - KALIYE Admin', $emailBody);

    echo json_encode(['success' => true, 'message' => 'Administrador criado com sucesso. Email enviado com as credenciais.']);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('create_admin error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao criar administrador: ' . $e->getMessage()]);
}
