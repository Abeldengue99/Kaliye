<?php
// includes/SimpleMailer.php

class SimpleMailer {
    private $sock;

    public function send($to, $full_name, $subject, $body) {
        require_once dirname(__DIR__) . '/configuracoes/correio.php';

        if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
            error_log("MAIL SIMULATION: Email to $to ($full_name) with subject '$subject' simulated.");
            return true;
        }

        try {
            $this->connect();
            $this->auth();
            $this->sendData($to, $subject, $body);
            $this->disconnect();
            return true;
        } catch (Exception $e) {
            $error_msg = "SMTP Error [" . date('Y-m-d H:i:s') . "]: " . $e->getMessage();
            error_log($error_msg);
            // Also log to a dedicated file for easier debugging
            file_put_contents(dirname(__DIR__) . '/registos/smtp_debug.log', $error_msg . PHP_EOL, FILE_APPEND);
            return false;
        }
    }

    private function connect() {
        $this->sock = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
        if (!$this->sock) throw new Exception("Connection failed: $errstr ($errno)");
        $this->getResponse(); // Banner
        $this->sendCommand('EHLO ' . gethostname());
        
        if (SMTP_PORT == 587) {
            $this->sendCommand('STARTTLS');
            stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCommand('EHLO ' . gethostname());
        }
    }

    private function auth() {
        $this->sendCommand('AUTH LOGIN');
        $this->sendCommand(base64_encode(SMTP_USER));
        $this->sendCommand(base64_encode(SMTP_PASS));
    }

    private function sendData($to, $subject, $body) {
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : SMTP_USER;
        $this->sendCommand('MAIL FROM: <' . $fromEmail . '>');
        $this->sendCommand('RCPT TO: <' . $to . '>');
        $this->sendCommand('DATA');

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . $fromEmail . ">\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";

        $message = "$headers\r\n$body\r\n.";
        $this->sendCommand($message);
    }

    private function sendCommand($cmd) {
        fputs($this->sock, $cmd . "\r\n");
        return $this->getResponse();
    }

    private function getResponse() {
        $response = "";
        while ($str = fgets($this->sock, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $response;
    }

    private function disconnect() {
        if ($this->sock) {
            fputs($this->sock, "QUIT\r\n");
            fclose($this->sock);
        }
    }
}
