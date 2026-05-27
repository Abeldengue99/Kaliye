<?php
/**
 * interface_programacao/system/subscribe_newsletter.php
 * Endpoint para subscrição na Newsletter Aksanti.
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../configuracoes/correio.php';
require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';
require_once __DIR__ . '/../../inclusoes/templates/email_newsletter_welcome.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, insira um e-mail válido.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar se já existe
    $check = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Já está subscrito na nossa newsletter!']);
        exit;
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
            $mailer->send($email, $subject, $body);
        } catch (Exception $e) {
            // Logar erro de e-mail se necessário
            error_log("Newsletter Mail Error: " . $e->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Subscrição efetuada com sucesso! Bem-vindo à nossa comunidade.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ocorreu um erro ao processar a sua subscrição.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
