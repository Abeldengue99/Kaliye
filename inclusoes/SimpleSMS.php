<?php
// includes/SimpleSMS.php

class SimpleSMS {
    private string $provider;
    private string $apiKey;
    private string $sender;
    private string $customEndpoint;
    private bool $enabled;

    public function __construct(?PDO $db = null) {
        $settings = $this->loadSettings($db);

        $this->provider = $settings['sms_provider'] ?? 'simulation';
        $this->apiKey = $settings['sms_api_key'] ?? (defined('BREVO_API_KEY') ? BREVO_API_KEY : '');
        $this->sender = $settings['sms_sender'] ?? 'KALIYE';
        $this->customEndpoint = $settings['sms_custom_endpoint'] ?? '';
        $this->enabled = $this->enabledValue($settings['sms_enabled'] ?? '0');
    }

    public function send($to, $message): bool {
        $to = $this->normalizePhone($to);
        $message = trim((string)$message);

        if (!$this->enabled || $this->provider === 'simulation' || empty($this->apiKey)) {
            error_log("SMS SIMULATION [$this->provider]: To $to -> $message");
            return true;
        }

        if ($to === '' || $message === '') {
            return false;
        }

        if ($this->provider === 'brevo') {
            return $this->sendBrevo($to, $message);
        }

        if ($this->provider === 'custom_http') {
            return $this->sendCustomHttp($to, $message);
        }

        error_log("SMS provider not supported: $this->provider");
        return false;
    }

    private function sendBrevo(string $to, string $message): bool {
        return $this->postJson('https://api.brevo.com/v3/transactionalSMS/sms', [
            'sender' => $this->sender,
            'recipient' => $to,
            'content' => $message,
            'type' => 'transactional'
        ], [
            'accept: application/json',
            'api-key: ' . $this->apiKey,
            'content-type: application/json'
        ], 'Brevo SMS');
    }

    private function sendCustomHttp(string $to, string $message): bool {
        if ($this->customEndpoint === '') {
            error_log('Custom SMS endpoint is empty.');
            return false;
        }

        return $this->postJson($this->customEndpoint, [
            'sender' => $this->sender,
            'recipient' => $to,
            'content' => $message,
        ], [
            'accept: application/json',
            'authorization: Bearer ' . $this->apiKey,
            'content-type: application/json'
        ], 'Custom SMS');
    }

    private function postJson(string $url, array $data, array $headers, string $label): bool {
        if (!function_exists('curl_init')) {
            error_log("$label Error: cURL extension is not available.");
            return false;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("$label Error ($httpCode): " . ($response ?: $error));
        return false;
    }

    private function normalizePhone($to): string {
        $to = preg_replace('/[^0-9+]/', '', (string)$to);

        if (strlen($to) === 9 && substr($to, 0, 1) === '9') {
            return '+244' . $to;
        }

        return $to;
    }

    private function loadSettings(?PDO $db): array {
        try {
            if (!$db) {
                require_once __DIR__ . '/../configuracoes/base_dados.php';
                $db = (new Database())->getConnection();
            }

            if (!$db) {
                return [];
            }

            $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'sms_%'");
            return $stmt ? ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: []) : [];
        } catch (Throwable $e) {
            error_log('SMS settings lookup failed: ' . $e->getMessage());
            return [];
        }
    }

    private function enabledValue($value): bool {
        return in_array(strtolower((string)$value), ['1', 'true', 't', 'yes', 'y', 'on'], true);
    }
}
