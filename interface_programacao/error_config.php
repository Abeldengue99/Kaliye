<?php
/**
 * CONFIGURAÇÃO GLOBAL DE TRATAMENTO DE ERROS
 * Kaliye Platform - 02 de Junho de 2026
 * 
 * Deve ser incluído em TODOS os arquivos PHP de controllers/APIs
 * Uso: require_once __DIR__ . '/error_config.php';
 */

// Configurar relatório de erros
error_reporting(E_ALL);
ini_set('display_errors', 0); // NÃO exibir erros no browser
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors_' . date('Y-m-d') . '.log');

// Garantir header JSON para APIs
if (php_sapi_name() !== 'cli' && strpos($_SERVER['REQUEST_URI'] ?? '', '/interface_programacao/') !== false) {
    if (headers_sent() === false) {
        header('Content-Type: application/json; charset=utf-8');
    }
}

// Capturador de exceções não tratadas
set_exception_handler(function (Throwable $exception) {
    require_once __DIR__ . '/ErrorHandler.php';
    
    if (php_sapi_name() !== 'cli') {
        if (headers_sent() === false) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        
        // Verificar se é uma operação da API
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/interface_programacao/') !== false) {
            ErrorHandler::respondError(
                ErrorHandler::TYPE_SERVER,
                null,
                'Uncaught exception: ' . $exception->getMessage()
            );
        }
    }
    
    // Log
    error_log('Uncaught Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $exception->getMessage() . "\n";
    }
    
    exit(1);
});

// Capturador de erros PHP não tratados
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Não capturar warnings que são controlados (ex: file_exists)
    if (error_reporting() === 0) {
        return false;
    }
    
    // Log apenas de erros críticos
    if ($errno === E_ERROR || $errno === E_USER_ERROR) {
        error_log("PHP Error [$errno]: $errstr in $errfile:$errline");
    }
    
    return false; // Use PHP's internal error handler também
});

// Capturador de erros fatais (PHP 7+)
register_shutdown_function(function () {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        require_once __DIR__ . '/ErrorHandler.php';
        
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
        
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            
            echo json_encode([
                'success' => false,
                'error' => true,
                'message' => 'Ocorreu um erro fatal. A equipa técnica foi notificada.',
                'type' => 'server'
            ]);
        }
    }
});

// Definir timeout para execução longa
set_time_limit(120);

// Definir locale padrão
setlocale(LC_ALL, 'pt_PT.UTF-8', 'pt_PT', 'pt-PT', 'Portuguese');

// Funções auxiliares úteis

/**
 * Valida se a requisição é POST e tem JSON válido
 * 
 * @return array|null Array decodificado ou null
 */
function getJsonInput() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return null;
    }
    
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

/**
 * Verifica se é uma requisição AJAX
 * 
 * @return bool
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Obtém endereço IP real do cliente (considerando proxies)
 * 
 * @return string
 */
function getRealClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    
    return $ip;
}

/**
 * Log estruturado de operações sensíveis
 * 
 * @param string $operation Operação realizada
 * @param bool $success Se foi bem-sucedida
 * @param array $context Contexto adicional
 * @return void
 */
function logOperation($operation, $success = true, $context = []) {
    $logFile = __DIR__ . '/../../logs/operations_' . date('Y-m-d') . '.log';
    $dir = dirname($logFile);
    
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'operation' => $operation,
        'success' => $success,
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip' => getRealClientIP(),
        'context' => $context
    ];
    
    file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND);
}

/**
 * Sanitiza entrada de segurança básica
 * 
 * @param mixed $data Dado a sanitizar
 * @return mixed Dado sanitizado
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    if (is_string($data)) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

?>
