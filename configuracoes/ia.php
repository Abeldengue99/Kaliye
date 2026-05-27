<?php
// config/ai.php - Configuração centralizada de IA

class AIConfig {
    private $db;
    private $settings = null;
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadSettings();
    }
    
    private function loadSettings() {
        try {
            $stmt = $this->db->query("SELECT * FROM ai_settings WHERE is_active = true LIMIT 1");
            $this->settings = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AI Config Error: " . $e->getMessage());
        }
    }
    
    public function isEnabled() {
        return $this->settings !== null && $this->settings !== false;
    }
    
    public function getProvider() {
        return $this->settings['provider'] ?? 'none';
    }
    
    public function getApiKey() {
        return $this->settings['api_key'] ?? '';
    }
    
    public function getModel() {
        return $this->settings['model'] ?? 'gpt-4';
    }
    
    public function getEndpoint() {
        $provider = $this->getProvider();
        
        if ($provider === 'azure') {
            return $this->settings['endpoint'] ?? '';
        }
        
        if ($provider === 'openai') {
            return 'https://api.openai.com/v1/chat/completions';
        }
        
        if ($provider === 'gemini') {
            return 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent';
        }
        
        return '';
    }
    
    public function logUsage($actionType, $tokensUsed, $costUsd, $success = true, $errorMessage = null) {
        try {
            $stmt = $this->db->prepare("INSERT INTO ai_usage_logs (action_type, tokens_used, cost_usd, success, error_message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$actionType, $tokensUsed, $costUsd, $success ? 1 : 0, $errorMessage]);
        } catch (Exception $e) {
            error_log("AI Usage Log Error: " . $e->getMessage());
        }
    }
}
