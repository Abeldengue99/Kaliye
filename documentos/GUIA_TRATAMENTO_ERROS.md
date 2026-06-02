<?php
/**
 * GUIA DE IMPLEMENTAÇÃO - TRATAMENTO DE ERROS
 * Kaliye Platform - 02 de Junho de 2026
 * 
 * Exemplos práticos de como usar o ErrorHandler em diferentes cenários
 */

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../ErrorHandler.php';

?>

<!-- EXEMPLO 1: INCLUIR MODAL EM TODAS AS PÁGINAS -->
<?php
// No início do arquivo principal, incluir:
// require_once __DIR__ . '/../inclusoes/components/error_modal.php';
?>

<!-- EXEMPLO 2: VALIDAÇÃO DE FORMULÁRIO (PHP) -->
<?php
/*
 * Tratamento seguro de formulário com validação e tratamento de erros
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validações básicas
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        
        $errors = [];
        
        // Validar campos
        if (!$email) {
            $errors['email'] = 'Email inválido';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password deve ter pelo menos 8 caracteres';
        }
        if (strlen($name) < 3) {
            $errors['name'] = 'Nome deve ter pelo menos 3 caracteres';
        }
        
        // Se houver erros, retornar
        if (!empty($errors)) {
            ErrorHandler::respondValidationErrors($errors);
        }
        
        // Verificar CSRF
        ErrorHandler::verifyCsrfToken(
            $_POST['csrf_token'] ?? '',
            $_SESSION['csrf_token'] ?? ''
        );
        
        // Conectar BD
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            // Verificar email duplicado
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                ErrorHandler::respondError(
                    ErrorHandler::TYPE_CONFLICT,
                    'Este email já está registado. Usa outro ou faz login.',
                    'Duplicate email registration attempt'
                );
            }
            
            // Inserir utilizador
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("
                INSERT INTO users (email, password_hash, full_name, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$email, $passwordHash, $name]);
            
            // Sucesso
            ErrorHandler::respondSuccess(
                ['user_id' => $db->lastInsertId()],
                'Utilizador registado com sucesso'
            );
            
        } catch (PDOException $e) {
            ErrorHandler::handleDatabaseException($e, 'User registration');
        }
        
    } catch (Exception $e) {
        ErrorHandler::handleException($e, 'Form processing');
    }
}
?>

<!-- EXEMPLO 3: API COM FETCH (JAVASCRIPT) -->
<script>
/*
 * Tratamento de erros em requisições AJAX/Fetch
 */

async function registarUtilizador(dados) {
    try {
        const response = await fetch('/interface_programacao/auth/register_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(dados)
        });
        
        const result = await response.json();
        
        // Verificar se houve erro
        if (!result.success) {
            // Mostrar modal com mensagem de erro
            ErrorUI.show(result.message, {
                title: 'Erro na Operação',
                type: result.type === 'validation' ? 'warning' : 'error',
                details: result.type === 'validation' ? JSON.stringify(result.errors, null, 2) : null
            });
            return;
        }
        
        // Sucesso
        console.log('Utilizador registado:', result.data);
        // Redirecionar ou atualizar página
        window.location.href = '/dashboard';
        
    } catch (error) {
        // Erro de rede
        console.error('Erro:', error);
        ErrorUI.showNetworkError();
    }
}

// Usar em evento de formulário
document.getElementById('formRegistry')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const dados = {
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        name: document.getElementById('name').value,
        csrf_token: document.getElementById('csrf_token').value
    };
    
    await registarUtilizador(dados);
});
</script>

<!-- EXEMPLO 4: TRATAMENTO DE ERRO DE AUTENTICAÇÃO -->
<?php
/*
 * Exemplo de verificação de autenticação segura
 */

function verificarLogin() {
    // Verificar se está autenticado
    if (!isset($_SESSION['user_id'])) {
        ErrorHandler::respondError(
            ErrorHandler::TYPE_AUTHENTICATION,
            'A tua sessão expirou. Por favor, faz login novamente.'
        );
    }
}

// Usar em qualquer página protegida:
// verificarLogin();
?>

<!-- EXEMPLO 5: TRATAMENTO DE ERRO DE AUTORIZAÇÃO -->
<?php
/*
 * Exemplo de verificação de permissão/role
 */

function verificarPermissao($rolesPermitidas) {
    // Primeiro verificar autenticação
    verificarLogin();
    
    // Depois verificar role
    $userRole = $_SESSION['user_role'] ?? null;
    
    ErrorHandler::requireRole(
        $rolesPermitidas,
        $userRole
    );
}

// Usar em páginas administrativas:
// verificarPermissao(['admin', 'moderator']);
?>

<!-- EXEMPLO 6: TRATAMENTO DE UPLOAD DE ARQUIVO -->
<?php
/*
 * Exemplo de tratamento seguro de upload com erro
 */

function procesarUpload($fileInput) {
    // Verificar erros de upload
    if (!isset($_FILES[$fileInput])) {
        return ErrorHandler::getErrorResponse(
            ErrorHandler::TYPE_VALIDATION,
            'Nenhum ficheiro foi fornecido'
        );
    }
    
    $file = $_FILES[$fileInput];
    
    // Verificar erros de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = ErrorHandler::getUploadErrorMessage($file['error']);
        return ErrorHandler::getErrorResponse(
            ErrorHandler::TYPE_VALIDATION,
            $errorMsg
        );
    }
    
    // Validar tipo
    $tiposPermitidos = ['image/jpeg', 'image/png', 'application/pdf'];
    $mimeType = mime_content_type($file['tmp_name']);
    
    if (!in_array($mimeType, $tiposPermitidos)) {
        return ErrorHandler::getErrorResponse(
            ErrorHandler::TYPE_VALIDATION,
            'Tipo de ficheiro não permitido. Use JPG, PNG ou PDF.'
        );
    }
    
    // Validar tamanho (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ErrorHandler::getErrorResponse(
            ErrorHandler::TYPE_VALIDATION,
            'O ficheiro é demasiado grande. Máximo 5MB.'
        );
    }
    
    // Sucesso
    return ErrorHandler::getSuccessResponse(
        ['filename' => $file['name']],
        'Ficheiro validado com sucesso'
    );
}

// Usar em processamento de upload:
// $result = procesarUpload('document');
// if (!$result['success']) {
//     ErrorHandler::respondError(ErrorHandler::TYPE_VALIDATION, $result['message']);
// }
?>

<!-- EXEMPLO 7: TRATAMENTO DE ERRO DE INTEGRAÇÃO EXTERNA -->
<?php
/*
 * Exemplo de erro ao integrar com API externa (ex: SMS, Email)
 */

try {
    // Tentar enviar SMS
    $response = @file_get_contents('https://api.sms-provider.com/send', false, 
        stream_context_create([
            'http' => ['timeout' => 10]
        ])
    );
    
    if ($response === false) {
        throw new Exception('Failed to connect to SMS service');
    }
    
} catch (Exception $e) {
    ErrorHandler::respondError(
        ErrorHandler::TYPE_EXTERNAL_API,
        'Erro ao enviar mensagem. Tenta novamente em poucos minutos.',
        'SMS API error: ' . $e->getMessage()
    );
}
?>

<!-- EXEMPLO 8: TRATAMENTO CENTRALIZADO EM CONFIG -->
<?php
/*
 * Adicionar ao arquivo de configuração base_dados.php
 * para capturar erros não tratados
 */

// Adicionar no topo:
set_exception_handler(function ($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage());
    
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $exception->getMessage() . "\n";
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => true,
            'message' => 'Ocorreu um erro inesperado. A equipa técnica foi notificada.',
            'type' => 'server'
        ]);
    }
    exit;
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile:$errline");
    return false;
});
?>

<!-- RESUMO DE BOAS PRÁTICAS -->
<?php
/*
 * RESUMO DE IMPLEMENTAÇÃO
 * 
 * 1. Incluir em base_dados.php:
 *    require_once __DIR__ . '/inclusoes/ErrorHandler.php';
 * 
 * 2. Incluir em layout principal (header.php / rodape.php):
 *    require_once __DIR__ . '/inclusoes/components/error_modal.php';
 * 
 * 3. Em formulários e APIs:
 *    - Validar inputs
 *    - Usar ErrorHandler::respondValidationErrors() para validação
 *    - Usar try-catch com ErrorHandler::handleDatabaseException() para BD
 *    - Usar ErrorHandler::respondError() para erros específicos
 *    - Usar ErrorHandler::respondSuccess() para sucesso
 * 
 * 4. Em JavaScript:
 *    - Usar try-catch com ErrorUI.show() para erros
 *    - Usar ErrorUI.showNetworkError() para erros de rede
 *    - Usar ErrorUI.showValidationErrors() para validação
 * 
 * 5. Segurança:
 *    - NUNCA expor detalhes do servidor em mensagens de erro
 *    - Registar erros com ErrorHandler::logError()
 *    - Usar mensagens genéricas para o utilizador
 *    - Guardar detalhes sensíveis no log do servidor
 * 
 * RESULTADOS:
 * ✅ Modal visual e profissional para erros
 * ✅ Mensagens amigáveis ao utilizador
 * ✅ Logs seguros e estruturados
 * ✅ Consistência em toda a plataforma
 * ✅ Proteção de dados sensíveis
 */
?>
