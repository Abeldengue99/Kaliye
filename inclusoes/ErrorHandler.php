<?php
/**
 * SISTEMA CENTRALIZADO DE TRATAMENTO DE ERROS
 * Kaliye Platform - 02 de Junho de 2026
 * 
 * Responsável por:
 * - Capturar e tratar exceções
 * - Gerar mensagens amigáveis ao utilizador
 * - Fazer log seguro de erros (sem dados sensíveis)
 * - Retornar respostas JSON padronizadas
 * - Diferenciar tipos de erro
 */

class ErrorHandler {
    
    // Tipos de erro
    const TYPE_VALIDATION = 'validation';
    const TYPE_DATABASE = 'database';
    const TYPE_AUTHENTICATION = 'authentication';
    const TYPE_AUTHORIZATION = 'authorization';
    const TYPE_NOT_FOUND = 'not_found';
    const TYPE_CONFLICT = 'conflict';
    const TYPE_SERVER = 'server';
    const TYPE_EXTERNAL_API = 'external_api';
    const TYPE_NETWORK = 'network';
    
    // Código HTTP padrão
    private static $httpCodes = [
        'validation' => 422,
        'database' => 500,
        'authentication' => 401,
        'authorization' => 403,
        'not_found' => 404,
        'conflict' => 409,
        'server' => 500,
        'external_api' => 502,
        'network' => 503
    ];
    
    /**
     * Mensagens amigáveis ao utilizador (sem dados sensíveis)
     */
    private static $userMessages = [
        'validation' => 'Os dados fornecidos não são válidos. Verifica os campos e tenta novamente.',
        'database' => 'Ocorreu um erro ao processar a tua solicitação. A equipa técnica foi notificada.',
        'authentication' => 'A tua sessão expirou. Por favor, faz login novamente.',
        'authorization' => 'Não tens permissão para realizar esta ação.',
        'not_found' => 'O recurso solicitado não foi encontrado.',
        'conflict' => 'Ocorreu um conflito ao processar a tua solicitação. Tenta novamente.',
        'server' => 'Ocorreu um erro no servidor. A equipa técnica foi notificada.',
        'external_api' => 'Erro ao comunicar com um serviço externo. Tenta novamente mais tarde.',
        'network' => 'Problema de conectividade. Verifica a tua ligação à internet.'
    ];
    
    /**
     * Registra erro com segurança (sem dados sensíveis)
     * 
     * @param string $type Tipo de erro
     * @param string $message Mensagem de erro
     * @param string $details Detalhes adicionais
     * @param array $context Contexto da aplicação
     * @return void
     */
    public static function logError($type, $message, $details = '', $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_id = $_SESSION['user_id'] ?? 'Anonymous';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'Unknown';
        $requestPath = $_SERVER['REQUEST_URI'] ?? 'Unknown';
        
        // Sanitizar detalhes para não expor dados sensíveis
        $details = self::sanitizeDetails($details);
        
        $logEntry = [
            'timestamp' => $timestamp,
            'type' => $type,
            'message' => $message,
            'user_id' => $user_id,
            'ip' => $ip,
            'method' => $requestMethod,
            'path' => $requestPath,
            'details' => $details,
            'context' => json_encode($context)
        ];
        
        // Log em arquivo
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/errors_' . date('Y-m-d') . '.log';
        $logLine = json_encode($logEntry) . PHP_EOL;
        file_put_contents($logFile, $logLine, FILE_APPEND);
        
        // Também usar error_log padrão
        error_log(json_encode($logEntry));
    }
    
    /**
     * Remove dados sensíveis dos detalhes de erro
     * 
     * @param string $details Detalhes brutos
     * @return string Detalhes sanitizados
     */
    private static function sanitizeDetails($details) {
        // Remover informações de caminho do servidor
        $details = preg_replace('#[A-Za-z]:[/\\\\].*?[\\/]#i', '[SERVER_PATH]/', $details);
        
        // Remover informações de usuário do banco
        $details = preg_replace('/user\s*=\s*["\']?[^"\';\s]+["\']?/i', 'user=[REDACTED]', $details);
        
        // Remover senhas
        $details = preg_replace('/password\s*=\s*["\']?[^"\';\s]+["\']?/i', 'password=[REDACTED]', $details);
        
        // Remover tokens
        $details = preg_replace('/token\s*=\s*["\']?[^"\';\s]+["\']?/i', 'token=[REDACTED]', $details);
        
        // Remover chaves API
        $details = preg_replace('/api[_-]?key\s*=\s*["\']?[^"\';\s]+["\']?/i', 'api_key=[REDACTED]', $details);
        
        return $details;
    }
    
    /**
     * Retorna resposta de erro padronizada
     * 
     * @param string $type Tipo de erro
     * @param string $userMessage Mensagem customizada (opcional)
     * @param string $internalMessage Mensagem para log
     * @param array $additionalData Dados adicionais para resposta
     * @return array Resposta formatada
     */
    public static function getErrorResponse($type, $userMessage = null, $internalMessage = null, $additionalData = []) {
        
        // Usar mensagem customizada ou padrão
        $message = $userMessage ?? self::$userMessages[$type] ?? 'Ocorreu um erro. Tenta novamente.';
        
        // Log do erro
        if ($internalMessage) {
            self::logError($type, $internalMessage, '', $additionalData);
        }
        
        return [
            'success' => false,
            'error' => true,
            'type' => $type,
            'message' => $message,
            'timestamp' => time(),
            'data' => $additionalData
        ];
    }
    
    /**
     * Envia resposta de erro como JSON
     * 
     * @param string $type Tipo de erro
     * @param string $userMessage Mensagem customizada (opcional)
     * @param string $internalMessage Mensagem para log
     * @param int $httpCode Código HTTP customizado
     * @return void
     */
    public static function respondError($type, $userMessage = null, $internalMessage = null, $httpCode = null) {
        
        $httpCode = $httpCode ?? self::$httpCodes[$type] ?? 500;
        
        http_response_code($httpCode);
        header('Content-Type: application/json');
        
        $response = self::getErrorResponse($type, $userMessage, $internalMessage);
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Trata exceção PDO/Banco de Dados
     * 
     * @param PDOException $e Exceção PDO
     * @param string $context Contexto da operação
     * @return void
     */
    public static function handleDatabaseException(PDOException $e, $context = '') {
        $message = $e->getMessage();
        
        // Verificar tipo de erro BD
        if (strpos($message, 'UNIQUE constraint failed') !== false) {
            self::respondError(
                self::TYPE_CONFLICT,
                'Este registo já existe. Usa um valor diferente.',
                "Database duplicate key: {$context} | {$message}"
            );
        } elseif (strpos($message, 'foreign key constraint') !== false) {
            self::respondError(
                self::TYPE_CONFLICT,
                'Não é possível realizar esta operação.',
                "Database foreign key violation: {$context} | {$message}"
            );
        } else {
            self::respondError(
                self::TYPE_DATABASE,
                null,
                "Database error: {$context} | {$message}"
            );
        }
    }
    
    /**
     * Trata exceção genérica
     * 
     * @param Exception $e Exceção
     * @param string $context Contexto da operação
     * @return void
     */
    public static function handleException(Exception $e, $context = '') {
        self::respondError(
            self::TYPE_SERVER,
            null,
            "Exception: {$context} | " . $e->getMessage()
        );
    }
    
    /**
     * Valida erros de validação e retorna resposta estruturada
     * 
     * @param array $errors Array de erros ['campo' => 'mensagem']
     * @return void
     */
    public static function respondValidationErrors($errors) {
        http_response_code(422);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'error' => true,
            'type' => self::TYPE_VALIDATION,
            'message' => 'Validação de dados falhou',
            'errors' => $errors,
            'timestamp' => time()
        ]);
        exit;
    }
    
    /**
     * Verifica autenticação e retorna erro se não autenticado
     * 
     * @return bool True se autenticado
     */
    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            self::respondError(
                self::TYPE_AUTHENTICATION,
                'A tua sessão expirou. Por favor, faz login novamente.'
            );
        }
        return true;
    }
    
    /**
     * Verifica autorização (role/permissão) e retorna erro se não autorizado
     * 
     * @param array $requiredRoles Papéis necessários
     * @param string $userRole Papel do utilizador
     * @return bool True se autorizado
     */
    public static function requireRole($requiredRoles, $userRole) {
        if (!in_array($userRole, (array)$requiredRoles)) {
            self::respondError(
                self::TYPE_AUTHORIZATION,
                'Não tens permissão para realizar esta ação.'
            );
        }
        return true;
    }
    
    /**
     * Verifica CSRF token
     * 
     * @param string $token Token fornecido
     * @param string $sessionToken Token da sessão
     * @return bool True se válido
     */
    public static function verifyCsrfToken($token, $sessionToken) {
        if (!hash_equals($token ?? '', $sessionToken ?? '')) {
            self::respondError(
                self::TYPE_VALIDATION,
                'Ocorreu um erro de segurança. Tenta novamente.',
                'CSRF token mismatch'
            );
        }
        return true;
    }
    
    /**
     * Trata erro de arquivo (upload)
     * 
     * @param int $uploadError Código de erro de upload
     * @return string Mensagem de erro amigável
     */
    public static function getUploadErrorMessage($uploadError) {
        $errors = [
            UPLOAD_ERR_OK => 'Ficheiro carregado com sucesso',
            UPLOAD_ERR_INI_SIZE => 'O ficheiro é demasiado grande.',
            UPLOAD_ERR_FORM_SIZE => 'O ficheiro é demasiado grande.',
            UPLOAD_ERR_PARTIAL => 'O carregamento do ficheiro foi interrompido.',
            UPLOAD_ERR_NO_FILE => 'Nenhum ficheiro foi carregado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Erro no servidor. Tenta novamente.',
            UPLOAD_ERR_CANT_WRITE => 'Erro no servidor. Tenta novamente.',
            UPLOAD_ERR_EXTENSION => 'Tipo de ficheiro não permitido.'
        ];
        
        return $errors[$uploadError] ?? 'Erro ao carregar ficheiro.';
    }
    
    /**
     * Cria estrutura de resposta de sucesso padronizada
     * 
     * @param mixed $data Dados a retornar
     * @param string $message Mensagem de sucesso
     * @param array $extra Dados extras
     * @return array Resposta formatada
     */
    public static function getSuccessResponse($data = null, $message = 'Operação realizada com sucesso', $extra = []) {
        return array_merge([
            'success' => true,
            'error' => false,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ], $extra);
    }
    
    /**
     * Envia resposta de sucesso como JSON
     * 
     * @param mixed $data Dados a retornar
     * @param string $message Mensagem de sucesso
     * @param array $extra Dados extras
     * @return void
     */
    public static function respondSuccess($data = null, $message = 'Operação realizada com sucesso', $extra = []) {
        http_response_code(200);
        header('Content-Type: application/json');
        
        echo json_encode(self::getSuccessResponse($data, $message, $extra));
        exit;
    }
}

?>
