<?php
// includes/SimpleSMS.php

class SimpleSMS {
    private $apiKey;

    public function __construct() {
        // Obter a chave da configuração se existir, caso contrário usar placeholder
        $this->apiKey = defined('BREVO_API_KEY') ? BREVO_API_KEY : 'SUA_CHAVE_API_AQUI';
    }

    public function send($to, $message) {
        if ($this->apiKey === 'SUA_CHAVE_API_AQUI' || empty($this->apiKey)) {
            error_log("SMS SIMULATION: To $to -> $message");
            return true;
        }

        // Limpar número de telefone (remover espaços, etc)
        $to = preg_replace('/[^0-9+]/', '', $to);
        
        // Se não tiver o prefixo +, adicionar o de Angola por padrão se tiver 9 dígitos
        if (strlen($to) == 9 && substr($to, 0, 1) == '9') {
            $to = '+244' . $to;
        }

        $data = [
            'sender' => 'KALIYE',
            'recipient' => $to,
            'content' => $message,
            'type' => 'transactional'
        ];

        $ch = curl_init('https://api.brevo.com/v3/transactionalSMS/sms');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $this->apiKey,
            'content-type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log("Brevo SMS Error ($httpCode): " . $response);
            return false;
        }
    }
}
