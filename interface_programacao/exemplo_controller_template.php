<?php
/**
 * EXEMPLO PRÁTICO: TEMPLATE PARA CONTROLLERS
 * Kaliye Platform - 02 de Junho de 2026
 * 
 * Copie este template e adapte para cada controller
 * Remova os comentários após usar
 */

// ═══════════════════════════════════════════════════════════
// 1. INCLUIR CONFIGURAÇÕES GLOBAIS NO TOPO
// ═══════════════════════════════════════════════════════════
require_once __DIR__ . '/error_config.php';
require_once __DIR__ . '/../ErrorHandler.php';
require_once __DIR__ . '/../../configuracoes/base_dados.php';

// ═══════════════════════════════════════════════════════════
// 2. INICIAR SESSÃO E VERIFICAR AUTENTICAÇÃO (se necessário)
// ═══════════════════════════════════════════════════════════
session_start();

// Se esta página requer autenticação, descommente:
// ErrorHandler::requireAuth();

// Se esta página requer autorização específica, descommente:
// ErrorHandler::requireRole(['admin', 'moderator'], $_SESSION['user_role'] ?? null);

// ═══════════════════════════════════════════════════════════
// 3. VALIDAR MÉTODO DE REQUISIÇÃO
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ErrorHandler::respondError(
        ErrorHandler::TYPE_VALIDATION,
        'Método de requisição inválido. Use POST.'
    );
}

// ═══════════════════════════════════════════════════════════
// 4. WRAP PRINCIPAL EM TRY-CATCH
// ═══════════════════════════════════════════════════════════
try {
    
    // ───────────────────────────────────────────────────────
    // 4.1: OBTER E SANITIZAR INPUTS
    // ───────────────────────────────────────────────────────
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING);
    $name = trim($_POST['name'] ?? '');
    
    // Para inputs JSON (AJAX)
    $jsonInput = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
        strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $jsonInput = getJsonInput();
        if (!$jsonInput) {
            ErrorHandler::respondValidationErrors(['json' => 'JSON inválido']);
        }
    }
    
    // ───────────────────────────────────────────────────────
    // 4.2: VALIDAR CAMPOS
    // ───────────────────────────────────────────────────────
    $errors = [];
    
    if (!$email) {
        $errors['email'] = 'Email inválido ou não fornecido';
    }
    
    if (strlen($phone) < 9) {
        $errors['phone'] = 'Telefone deve ter pelo menos 9 dígitos';
    }
    
    if (strlen($name) < 3) {
        $errors['name'] = 'Nome deve ter pelo menos 3 caracteres';
    }
    
    // Se houver erros, responder imediatamente
    if (!empty($errors)) {
        ErrorHandler::respondValidationErrors($errors);
    }
    
    // ───────────────────────────────────────────────────────
    // 4.3: VERIFICAR CSRF (se necessário)
    // ───────────────────────────────────────────────────────
    ErrorHandler::verifyCsrfToken(
        $_POST['csrf_token'] ?? '',
        $_SESSION['csrf_token'] ?? ''
    );
    
    // ───────────────────────────────────────────────────────
    // 4.4: CONECTAR À BD
    // ───────────────────────────────────────────────────────
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Erro ao conectar à base de dados');
    }
    
    // ───────────────────────────────────────────────────────
    // 4.5: EXECUTAR OPERAÇÃO (protegida por try-catch)
    // ───────────────────────────────────────────────────────
    try {
        
        // Verificar se email já existe
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            ErrorHandler::respondError(
                ErrorHandler::TYPE_CONFLICT,
                'Este email já está registado. Usa outro ou faz login.',
                'Duplicate email attempt: ' . substr($email, 0, 3) . '***'
            );
        }
        
        // Inserir novo registo
        $stmt = $db->prepare("
            INSERT INTO users (email, phone, full_name, created_at)
            VALUES (:email, :phone, :name, NOW())
        ");
        
        $stmt->execute([
            ':email' => $email,
            ':phone' => $phone,
            ':name' => $name
        ]);
        
        $userId = $db->lastInsertId();
        
        // ───────────────────────────────────────────────────
        // 4.6: LOG DE OPERAÇÃO (sucesso)
        // ───────────────────────────────────────────────────
        logOperation('user_created', true, [
            'user_id' => $userId,
            'email' => substr($email, 0, 3) . '***'
        ]);
        
        // ───────────────────────────────────────────────────
        // 4.7: RESPONDER COM SUCESSO
        // ───────────────────────────────────────────────────
        ErrorHandler::respondSuccess(
            [
                'user_id' => $userId,
                'email' => $email,
                'name' => $name
            ],
            'Utilizador criado com sucesso!'
        );
        
    } catch (PDOException $e) {
        // Erro de base de dados
        ErrorHandler::handleDatabaseException($e, 'User creation in template');
    }
    
} catch (Exception $e) {
    // Exceção genérica
    ErrorHandler::handleException($e, 'Controller template');
}

// ═══════════════════════════════════════════════════════════
// FIM DO CONTROLADOR
// ═══════════════════════════════════════════════════════════
?>
