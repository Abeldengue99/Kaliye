<?php
/**
 * interface_programacao/admin/send_broadcast.php
 * Motor de processamento de envio de e-mails em massa.
 */
header('Content-Type: application/json');
session_start();

// Segurança: Apenas admins podem disparar broadcasts
require_once __DIR__ . '/../../inclusoes/auth_check.php';
if (!isAdmin() || !hasPermission('ads')) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../configuracoes/correio.php';
require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$subject = $_POST['subject'] ?? '';
$message_body = $_POST['message'] ?? '';

if (empty($subject) || empty($message_body)) {
    echo json_encode(['success' => false, 'message' => 'Assunto e mensagem são obrigatórios.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $mailer = new SimpleMailer();
    
    // Buscar todos os subscritores
    $stmt = $db->query("SELECT name, email FROM newsletter_subscribers");
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $success_count = 0;
    $error_count = 0;

    // Aumentar o tempo limite de execução para envios longos
    set_time_limit(300); // 5 minutos

    foreach ($subscribers as $sub) {
        $to = $sub['email'];
        $name = $sub['name'] ?: 'Subscritor';
        
        // Personalização básica (opcional)
        $personalized_message = str_replace('{nome}', $name, $message_body);
        
        // Criar um wrapper HTML simples para a mensagem do admin
        $full_html = '
        <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #f7941d;">KALIYE</h2>
            </div>
            <div style="line-height: 1.6; color: #333;">
                ' . nl2br($personalized_message) . '
            </div>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
            <p style="font-size: 11px; color: #999; text-align: center;">
                Recebeste este e-mail porque estás subscrito na Newsletter da KALIYE.<br>
                Para deixar de receber estes e-mails, contacta o suporte.
            </p>
        </div>';

        if ($mailer->send($to, $subject, $full_html)) {
            $success_count++;
        } else {
            $error_count++;
        }
        
        // Pequena pausa para não sobrecarregar o SMTP (Rate Limiting)
        usleep(100000); // 0.1 segundos entre e-mails
    }

    echo json_encode([
        'success' => true, 
        'message' => "Broadcast finalizado. Enviados: $success_count | Falhas: $error_count"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro crítico: ' . $e->getMessage()]);
}
