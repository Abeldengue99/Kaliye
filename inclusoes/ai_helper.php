<?php
// includes/ai_helper.php - Funções auxiliares para IA

require_once __DIR__ . '/../configuracoes/ia.php';

class AIHelper {
    private $config;
    
    public function __construct($db) {
        $this->config = new AIConfig($db);
    }
    
    /**
     * Faz chamada à API de IA (OpenAI/Azure/Gemini)
     */
    public function callChatAPI($systemPrompt, $userPrompt, $temperature = 0.7, $maxTokens = 1500) {
        if (!$this->config->isEnabled()) {
            throw new Exception("IA não está configurada ou ativada");
        }
        
        $provider = $this->config->getProvider();
        
        if ($provider === 'openai' || $provider === 'azure') {
            return $this->callOpenAI($systemPrompt, $userPrompt, $temperature, $maxTokens);
        }
        
        if ($provider === 'gemini') {
            return $this->callGemini($systemPrompt, $userPrompt, $temperature, $maxTokens);
        }
        
        throw new Exception("Provedor de IA não suportado: " . $provider);
    }
    
    private function callOpenAI($systemPrompt, $userPrompt, $temperature, $maxTokens) {
        $endpoint = $this->config->getEndpoint();
        $apiKey = $this->config->getApiKey();
        $model = $this->config->getModel();
        
        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
        
        // Se for Azure, adicionar api-version
        if ($this->config->getProvider() === 'azure') {
            $endpoint .= '?api-version=2024-02-15-preview';
            $headers = [
                'Content-Type: application/json',
                'api-key: ' . $apiKey
            ];
        }
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->config->logUsage('chat_completion', 0, 0, false, "cURL Error: " . $error);
            throw new Exception("Erro cURL: " . $error);
        }
        
        if ($httpCode !== 200) {
            $this->config->logUsage('chat_completion', 0, 0, false, "HTTP $httpCode: " . $response);
            throw new Exception("Erro HTTP $httpCode: " . $response);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            $this->config->logUsage('chat_completion', 0, 0, false, "Invalid response: " . $response);
            throw new Exception("Resposta inválida da API: " . $response);
        }
        
        // Log de uso
        $tokensUsed = $result['usage']['total_tokens'] ?? 0;
        $costUsd = $this->calculateCost($tokensUsed, $model);
        $this->config->logUsage('chat_completion', $tokensUsed, $costUsd, true);
        
        return [
            'content' => $result['choices'][0]['message']['content'],
            'tokens' => $tokensUsed,
            'cost' => $costUsd
        ];
    }
    
    private function callGemini($systemPrompt, $userPrompt, $temperature, $maxTokens) {
        $endpoint = $this->config->getEndpoint();
        $apiKey = $this->config->getApiKey();
        
        $fullPrompt = $systemPrompt . "\n\n" . $userPrompt;
        
        $data = [
            'contents' => [
                ['parts' => [['text' => $fullPrompt]]]
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens
            ]
        ];
        
        $ch = curl_init($endpoint . '?key=' . $apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->config->logUsage('chat_completion', 0, 0, false, "cURL Error: " . $error);
            throw new Exception("Erro cURL: " . $error);
        }
        
        if ($httpCode !== 200) {
            $this->config->logUsage('chat_completion', 0, 0, false, "HTTP $httpCode: " . $response);
            throw new Exception("Erro HTTP $httpCode: " . $response);
        }
        
        $result = json_decode($response, true);
        $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        if (empty($content)) {
            $this->config->logUsage('chat_completion', 0, 0, false, "Empty response");
            throw new Exception("Resposta vazia da API Gemini");
        }
        
        // Gemini não retorna tokens, estimamos
        $tokensUsed = intval(strlen($fullPrompt . $content) / 4);
        $costUsd = $tokensUsed * 0.00025 / 1000;
        
        $this->config->logUsage('chat_completion', $tokensUsed, $costUsd, true);
        
        return [
            'content' => $content,
            'tokens' => $tokensUsed,
            'cost' => $costUsd
        ];
    }
    
    private function calculateCost($tokens, $model) {
        // Custos aproximados por 1K tokens (atualizar conforme pricing oficial)
        $costs = [
            'gpt-4' => 0.03 / 1000, // input (output é 0.06)
            'gpt-4-turbo' => 0.01 / 1000,
            'gpt-4-turbo-preview' => 0.01 / 1000,
            'gpt-3.5-turbo' => 0.0015 / 1000,
            'gpt-3.5-turbo-16k' => 0.003 / 1000
        ];
        
        return $tokens * ($costs[$model] ?? 0.03 / 1000);
    }
    
    /**
     * Verifica se a IA está configurada e ativa
     */
    public function isEnabled() {
        return $this->config->isEnabled();
    }
    
    /**
     * Retorna informações sobre a configuração atual
     */
    public function getInfo() {
        return [
            'enabled' => $this->config->isEnabled(),
            'provider' => $this->config->getProvider(),
            'model' => $this->config->getModel()
        ];
    }
}
