<?php
/**
 * interface_programacao/system/subscribe_newsletter.php
 * Endpoint para subscrição na Newsletter Aksanti.
 */

// Iniciar output buffering para evitar problemas de output antes de JSON
ob_start();

// Limpar a pasta de output buffer
ob_clean();

header('Content-Type: application/json; charset=utf-8');
session_start();

// Função para responder e sair
function respondJSON($success, $message) {
    ob_end_clean();
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../configuracoes/correio.php';
require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';
require_once __DIR__ . '/../../inclusoes/templates/email_newsletter_welcome.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJSON(false, 'Método não permitido.');
}

$name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respondJSON(false, 'Por favor, insira um e-mail válido.');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar se já existe
    $check = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->rowCount() > 0) {
        respondJSON(true, 'Já está subscrito na nossa newsletter!');
    }

    // Inserir novo subscritor
    $stmt = $db->prepare("INSERT INTO newsletter_subscribers (name, email) VALUES (?, ?)");
    if ($stmt->execute([$name, $email])) {
        
        // --- ENVIO AUTOMÁTICO DE E-MAIL ---
        try {
            $mailer = new SimpleMailer();
            $subject = "Bem-vindo à Newsletter Aksanti!";
            $body = getNewsletterWelcomeTemplate($name ?: 'Amigo');
            
            // Tentativa de envio silencioso (não bloqueia a resposta se falhar)
            @$mailer->send($email, $name ?: 'Subscritor', $subject, $body);
        } catch (Throwable $e) {
            // Logar erro de e-mail se necessário
            error_log("Newsletter Mail Error: " . $e->getMessage());
        }

        respondJSON(true, 'Subscrição efetuada com sucesso! Bem-vindo à nossa comunidade.');
    } else {
        respondJSON(false, 'Ocorreu um erro ao processar a sua subscrição.');
    }

} catch (Throwable $e) {
    respondJSON(false, 'Erro: ' . $e->getMessage());
}
