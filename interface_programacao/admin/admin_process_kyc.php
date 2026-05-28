<?php
// interface_programacao/admin/admin_process_kyc.php
@session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isAdmin() || !hasPermission('kyc')) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action = $_POST['action'] ?? '';
$notes = $_POST['notes'] ?? '';

if (!$user_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit();
}

require_once '../../inclusoes/SimpleMailer.php';

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $status = ($action === 'approve') ? 'verified' : 'rejected';
    
    // Fetch user details before update to send email
    $userStmt = $db->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch();

    if (!$user) {
        throw new Exception("Utilizador não encontrado.");
    }

    // Lógica Holística: Aprovar Identidade + Perfil (Mentor ou Investidor)
    $stmtRole = $db->prepare("SELECT user_type FROM users WHERE user_id = ?");
    $stmtRole->execute([$user_id]);
    $u_role = $stmtRole->fetchColumn();

    $query = "UPDATE users SET 
                verification_status = :status,
                updated_at = NOW()";

    $params = [
        ':status' => $status,
        ':user_id' => $user_id
    ];

    if ($status === 'verified') {
        // Aprovar Mentoria Profissional ou Peer Mentoria (Estudante)
        $is_peer = $db->query("SELECT is_peer_mentor FROM users WHERE user_id = $user_id")->fetchColumn();
        if ($u_role === 'mentor' || ($u_role === 'univ_student' && $is_peer)) {
            $query .= ", mentorship_status = 'approved'";
        }
        
        // Aprovar Investidor
        if ($u_role === 'investor') {
            $query .= ", investor_status = 'approved'";
        }
    } else {
        // Rejeitar tudo em caso de falha no KYC
        if ($u_role === 'mentor' || $u_role === 'univ_student') {
            $query .= ", mentorship_status = 'rejected'";
        }
        if ($u_role === 'investor') {
            $query .= ", investor_status = 'rejected'";
        }
    }

    $query .= " WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->execute($params);

    // Track for audit
    $audit_action = $status === 'verified' ? 'approve_kyc' : 'reject_kyc';
    $audit_details = "KYC " . ($status === 'verified' ? "aprovado" : "rejeitado") . " para o utilizador ID $user_id. Notas: $notes";
    $auditStmt = $db->prepare("INSERT INTO audit_logs (admin_id, action, details) VALUES (?, ?, ?)");
    $auditStmt->execute([$_SESSION['user_id'], $audit_action, $audit_details]);

    $notif = $db->prepare("
        INSERT INTO notifications (user_id, sender_id, title, content, type, link, is_read, created_at)
        VALUES (?, ?, ?, ?, 'kyc', ?, 0, NOW())
    ");
    if ($status === 'verified') {
        $notif->execute([
            $user_id,
            $_SESSION['user_id'],
            'Perfil verificado',
            'A sua identidade foi aprovada. As funcionalidades restritas ja estao desbloqueadas.',
            'paginas/social/profile.php'
        ]);
    } else {
        $notif->execute([
            $user_id,
            $_SESSION['user_id'],
            'Verificacao recusada',
            $notes ?: 'A sua verificacao precisa de correcao. Revise os dados e envie novamente.',
            'paginas/social/profile.php?kyc_required=1'
        ]);
    }

    // Send notification email
    $emailSent = false;
    try {
        $mailer = new SimpleMailer();
        $subject = $status === 'verified' ? "Perfil Verificado com Sucesso - KALIYE" : "Atualização sobre a sua Verificação - KALIYE";
        
        $emailBody = generateKYCEmailBody($user['full_name'], $status, $notes);
        $emailSent = $mailer->send($user['email'], $user['full_name'], $subject, $emailBody);
    } catch (Exception $mailEx) {
        error_log("KYC Email Error: " . $mailEx->getMessage());
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Utilizador ' . ($action === 'approve' ? 'aprovado' : 'rejeitado') . ' com sucesso. ' . ($emailSent ? '(Email enviado)' : '(Falha no envio do email)'),
        'email_status' => $emailSent
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

function generateKYCEmailBody($name, $status, $notes) {
    if ($status === 'verified') {
        $title = "A tua conta foi Verificada!";
        $subtitle = "Bem-vindo ao ecossistema completo KALIYE.";
        $hero_color = "#10b981";
        $hero_gradient = "linear-gradient(135deg, #10b981, #059669)";
        $message_text = "É com enorme satisfação que informamos que a tua identidade foi verificada pela nossa equipa administrativa. A partir de agora, todas as barreiras foram removidas.";
        $features = [
            "Acesso total ao Marketplace de Projectos",
            "Capacidade de publicar Projectos Ilimitados",
            "Networking directo com Mentores",
            "Área de Mensagens Privadas desbloqueada",
            "Gestão da Carteira Digital KALIYE"
        ];
        $cta_text = "";
    } else {
        $title = "Atualização Necessária";
        $subtitle = "Precisamos de mais alguns detalhes para o seu KYC.";
        $hero_color = "#ef4444";
        $hero_gradient = "linear-gradient(135deg, #ef4444, #dc2626)";
        $message_text = "Durante a nossa análise técnica de verificação, detetámos algumas informações que precisam da sua atenção antes de podermos validar totalmente o seu perfil.";
        $features = [];
        $cta_text = "Corrigir Verificação";
    }
    
    $features_html = "";
    if (!empty($features)) {
        $features_html = "<div style='margin: 25px 0; background: #f0fdf4; padding: 20px; border-radius: 12px; border: 1px solid #dcfce7;'>";
        $features_html .= "<h4 style='margin: 0 0 10px 0; color: #166534; font-size: 14px;'>FUNCIONALIDADES DESBLOQUEADAS:</h4>";
        foreach ($features as $f) {
            $features_html .= "<div style='display: flex; align-items: center; margin-bottom: 8px; color: #15803d; font-size: 14px;'>";
            $features_html .= "<span style='margin-right: 10px;'>✅</span> $f";
            $features_html .= "</div>";
        }
        $features_html .= "</div>";
    }

    $note_section = !empty($notes) ? "
        <div style='margin-top: 25px; padding: 20px; background: #fffcf0; border: 1px solid #fef3c7; border-left: 5px solid $hero_color; border-radius: 8px;'>
            <strong style='color: #92400e; font-size: 14px;'>NOTA DA ADMINISTRAÇÃO:</strong><br>
            <p style='color: #b45309; margin-top: 8px; font-size: 15px; font-style: italic;'>\"$notes\"</p>
        </div>" : "";

    return "
    <!DOCTYPE html>
    <html lang='pt'>
    <head>
        <meta charset='UTF-8'>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
            body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #f7fafc; color: #2d3748; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
            .email-wrapper { background-color: #f7fafc; padding: 40px 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
            .header-logo { background: #0f172a; padding: 30px; text-align: center; }
            .hero-section { background: $hero_gradient; color: white; padding: 45px 30px; text-align: center; }
            .hero-title { font-size: 28px; font-weight: 800; margin: 0; letter-spacing: -0.025em; }
            .hero-subtitle { font-size: 16px; opacity: 0.9; margin-top: 10px; }
            .content-body { padding: 40px 35px; }
            .welcome-text { font-size: 18px; font-weight: 600; color: #1a202c; margin-bottom: 15px; }
            .main-message { font-size: 16px; line-height: 1.7; color: #4a5568; }
            .btn-cta { display: inline-block; background: #f7941d; color: white !important; text-decoration: none; padding: 16px 35px; border-radius: 12px; font-weight: 700; font-size: 16px; margin-top: 30px; transition: all 0.3s ease; box-shadow: 0 10px 15px -3px rgba(247, 148, 29, 0.3); }
            .footer { background: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #edf2f7; }
            .footer-text { font-size: 13px; color: #718096; line-height: 1.5; }
            .social-links { margin-top: 20px; }
            .social-icon { color: #f7941d; text-decoration: none; font-weight: 600; margin: 0 10px; }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <div class='container'>
                <div class='header-logo'>
                    <img src='../../recursos/images/marca/favicon-k-32x32.png' alt='KALIYE Logo' style='width: 45px;'>
                    <div style='color: #f7941d; font-weight: 800; font-size: 18px; margin-top: 12px; letter-spacing: 1px;'>KALIYE</div>
                </div>
                
                <div class='hero-section'>
                    <h1 class='hero-title'>$title</h1>
                    <p class='hero-subtitle'>$subtitle</p>
                </div>

                <div class='content-body'>
                    <div class='welcome-text'>Olá, $name 👋</div>
                    <p class='main-message'>$message_text</p>
                    
                    $features_html
                    $note_section

                    " . ($cta_text ? "<div style='text-align: center;'>
                        <a href='https://aksanti.xyz/paginas/social/profile.php' class='btn-cta'>$cta_text</a>
                    </div>" : "") . "
                    
                    <p style='margin-top: 40px; font-size: 14px; color: #718096;'>Atenciosamente,<br><strong>Equipa de Verificação KALIYE</strong></p>
                </div>

                <div class='footer'>
                    <p class='footer-text'>
                        &copy; 2026 KALIYE. Todos os direitos reservados.<br>
                        Transformando projectos em impacto real.
                    </p>
                    <div class='footer-text' style='margin-top: 15px; font-size: 11px; opacity: 0.7;'>
                        Recebeu este email porque solicitou a verificação da sua conta na nossa plataforma. Por favor, não responda a este email.
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
}

