<?php
// interface_programacao/admin/admin_invite_user.php
// Cria um novo administrador com credenciais e permissões
@session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';
require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';

if (!isAdmin() || !hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$full_name   = trim($input['full_name'] ?? '');
$email       = trim($input['email'] ?? '');
$permissions = $input['permissions'] ?? [];

if (!$full_name || !$email) {
    echo json_encode(['success' => false, 'message' => 'Nome e email são obrigatórios']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Verificar se o email já existe
    $check = $db->prepare("SELECT user_id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Este email já está registado']);
        exit;
    }

    // Gerar senha temporária
    $password = substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#'), 0, 10);
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $db->beginTransaction();

    // Criar o utilizador admin
    $stmt = $db->prepare("
        INSERT INTO users (full_name, email, password_hash, user_type, is_verified, created_at, updated_at)
        VALUES (:name, :email, :password, 'admin', true, NOW(), NOW())
        RETURNING user_id
    ");
    $stmt->execute([
        ':name'     => $full_name,
        ':email'    => $email,
        ':password' => $hashed,
    ]);
    $row     = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_id  = $row['user_id'] ?? null;

    if (!$new_id) {
        throw new Exception('Falha ao criar utilizador');
    }

    // Guardar permissoes no mesmo formato usado por hasPermission().
    if (!empty($permissions)) {
        $allowed = [
            'dashboard', 'users', 'ads', 'moderation', 'support', 'kyc',
            'mentor_approval', 'mentor_assignment', 'finance_docs', 'finances',
            'legal', 'settings', 'chat_monitor', 'mentorship_quality', 'audit'
        ];
        $perm_stmt = $db->prepare("INSERT INTO admin_permissions (user_id, permission_slug, created_at) VALUES (?, ?, NOW())");
        foreach (array_unique(array_map('trim', $permissions)) as $permission) {
            if (in_array($permission, $allowed, true)) {
                $perm_stmt->execute([$new_id, $permission]);
            }
        }
    }
    $db->commit();

    // Enviar email com credenciais
    $mailer = new SimpleMailer();
    $emailBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px;'>
        <div style='background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
            <h2 style='color: #050a15; margin-bottom: 20px; text-align: center;'>Bem-vindo ao Painel KALIYE Admin</h2>
            
            <p style='color: #333; font-size: 16px; line-height: 1.6;'>Olá <strong>$full_name</strong>,</p>
            
            <p style='color: #666; font-size: 14px; line-height: 1.6;'>Sua conta de administrador foi criada com sucesso. Abaixo encontra as suas credenciais de acesso:</p>
            
            <div style='background: #f9f9f9; border-left: 4px solid #f7941d; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                <p style='color: #333; margin: 8px 0;'><strong>Email:</strong> <code style='background: #eee; padding: 2px 6px; border-radius: 3px;'>$email</code></p>
                <p style='color: #333; margin: 8px 0;'><strong>Senha Temporária:</strong> <code style='background: #eee; padding: 2px 6px; border-radius: 3px;'>$password</code></p>
            </div>
            
            <p style='color: #666; font-size: 14px; line-height: 1.6;'><strong>⚠️ Importante:</strong></p>
            <ul style='color: #666; font-size: 14px; line-height: 1.8;'>
                <li>Use estas credenciais para fazer login no painel administrativo</li>
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

    echo json_encode([
        'success' => true,
        'message' => 'Administrador criado com sucesso. Email enviado com as credenciais.',
        'credentials' => [
            'email'    => $email,
            'password' => $password
        ]
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("admin_invite_user error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao criar administrador: ' . $e->getMessage()]);
}
