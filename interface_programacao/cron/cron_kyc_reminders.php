<?php
/**
 * interface_programacao/cron/cron_kyc_reminders.php
 * Script a ser executado via cron job diariamente (ex: às 09:00).
 * Objetivo: Enviar lembretes automáticos para utilizadores que não completaram o KYC há mais de 3 dias.
 */

// Este script pode ser executado via CLI (cron) ou via requisição HTTP com uma chave secreta.
$is_cli = (php_sapi_name() === 'cli');
$secret = $_GET['key'] ?? '';
if (!$is_cli && $secret !== 'kaliye_cron_2026') {
    http_response_code(403);
    die("Acesso negado.");
}

require_once dirname(__DIR__, 2) . '/configuracoes/base_dados.php';
require_once dirname(__DIR__, 2) . '/inclusoes/SimpleMailer.php';

$database = new Database();
$db = $database->getConnection();
$mailer = new SimpleMailer();

// Garantir que a coluna para registar o lembrete existe na tabela users
try {
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_reminder_sent BOOLEAN DEFAULT FALSE");
} catch (PDOException $e) {
    // Ignorar se não for PostgreSQL 9.6+ ou já existir
}

// Procurar utilizadores que:
// 1. verification_status = 'unsubmitted'
// 2. Registaram-se há mais de 72 horas
// 3. Ainda não receberam o lembrete
$query = "
    SELECT user_id, full_name, email, created_at 
    FROM users 
    WHERE verification_status = 'unsubmitted' 
    AND kyc_reminder_sent = FALSE 
    AND created_at < NOW() - INTERVAL '3 days'
";

try {
    $stmt = $db->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sent_count = 0;

    foreach ($users as $user) {
        $subject = "KALIYE: Falta pouco para validar a sua conta!";
        $body = "
            <h3>Olá " . htmlspecialchars($user['full_name']) . "!</h3>
            <p>Notámos que criou a sua conta na KALIYE há alguns dias, mas ainda não completou a verificação de identidade (KYC).</p>
            <p>Para manter um ecossistema seguro e transparente, todos os investidores e mentores devem validar o seu perfil antes de poderem interagir com os projetos.</p>
            <p><strong>Aceda à plataforma e envie a sua documentação hoje mesmo!</strong> Demora menos de 2 minutos.</p>
            <br>
            <p>Com os melhores cumprimentos,</p>
            <p><strong>Equipa KALIYE</strong></p>
        ";

        if ($mailer->sendEmail($user['email'], $user['full_name'], $subject, $body)) {
            // Atualizar o estado para não voltar a enviar
            $update = $db->prepare("UPDATE users SET kyc_reminder_sent = TRUE WHERE user_id = ?");
            $update->execute([$user['user_id']]);
            $sent_count++;
        }
    }

    echo "Cron concluído. $sent_count lembretes enviados.\n";

} catch (PDOException $e) {
    error_log("Erro no Cron KYC: " . $e->getMessage());
    echo "Erro na execução: " . $e->getMessage() . "\n";
}
