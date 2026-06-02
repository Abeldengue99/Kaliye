<?php
/**
 * EXEMPLO DE VALIDAÇÃO NO BACKEND
 * Implementado para lançamento crítico - Kaliye Platform
 * Data: 02 de Junho de 2026
 * 
 * Este arquivo mostra como validar dados no backend de forma segura,
 * sem expor informações sensíveis do banco de dados.
 */

header('Content-Type: application/json');
session_start();

// Inclui a biblioteca de validações
require_once __DIR__ . '/../../interface_programacao/validacoes.php';
require_once __DIR__ . '/../../configuracoes/base_dados.php';

/**
 * EXEMPLO 1: Validar Formulário de Login
 */
function exemplo_validacao_login() {
    // Recolher dados do POST
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Criar array com as regras de validação
    $campos = [
        'Email' => [
            'valor' => $email,
            'regras' => [
                'obrigatorio' => true,
                'email' => true
            ]
        ],
        'Password' => [
            'valor' => $password,
            'regras' => [
                'obrigatorio' => true,
                'minimo' => 8
            ]
        ]
    ];
    
    // Validar todos os campos
    $validacao = ValidadorCampos::validarMultiplos($campos);
    
    // Se houver erros, retornar resposta JSON elegante
    if (!$validacao['valido']) {
        retornarErroValidacao(
            'Por favor, corrija os erros abaixo.',
            $validacao['erros']
        );
    }
    
    // Se validação passou, sanitizar os dados
    $email = ValidadorCampos::sanitizar($email);
    $password = $password; // Password NÃO sanitizar, manter como está
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Buscar utilizador por email
        $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar credenciais
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // NÃO revelar se email existe ou password é incorreta
            retornarErroGenerico('Email ou password incorretos.');
        }
        
        // Login bem-sucedido
        $_SESSION['user_id'] = $user['id'];
        
        retornarSucesso('Login realizado com sucesso!', [
            'redirect' => '../index.php'
        ]);
        
    } catch (PDOException $e) {
        // IMPORTANTE: Registar erro no servidor, não expor ao cliente
        error_log("Login error: " . $e->getMessage());
        retornarErroGenerico('Ocorreu um erro ao processar o login. Tente novamente mais tarde.');
    }
}

/**
 * EXEMPLO 2: Validar Formulário de Registro
 */
function exemplo_validacao_registro() {
    // Recolher dados
    $nome = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validar múltiplos campos
    $campos = [
        'Nome Completo' => [
            'valor' => $nome,
            'regras' => [
                'obrigatorio' => true,
                'letras' => true,
                'maximo' => 100
            ]
        ],
        'Email' => [
            'valor' => $email,
            'regras' => [
                'obrigatorio' => true,
                'email' => true
            ]
        ],
        'Telefone' => [
            'valor' => $telefone,
            'regras' => [
                'obrigatorio' => true,
                'telefone' => true
            ]
        ],
        'Password' => [
            'valor' => $password,
            'regras' => [
                'obrigatorio' => true,
                'minimo' => 8
            ]
        ]
    ];
    
    $validacao = ValidadorCampos::validarMultiplos($campos);
    
    if (!$validacao['valido']) {
        retornarErroValidacao(
            'Por favor, corrija os erros abaixo.',
            $validacao['erros']
        );
    }
    
    // Sanitizar dados
    $nome = ValidadorCampos::sanitizar($nome);
    $email = ValidadorCampos::sanitizar($email);
    $telefone = ValidadorCampos::sanitizar($telefone);
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar se email já existe
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            retornarErroValidacao('Este email já está registado.', ['email' => 'Email já em uso']);
        }
        
        // Hash da password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Inserir novo utilizador
        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, phone, password_hash, created_at)
            VALUES (:nome, :email, :telefone, :password_hash, NOW())
        ");
        
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':telefone' => $telefone,
            ':password_hash' => $password_hash
        ]);
        
        retornarSucesso('Conta criada com sucesso! Redirecionando...', [
            'redirect' => 'entrar.php'
        ]);
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        retornarErroGenerico('Erro ao criar a conta. Tente novamente.');
    }
}

/**
 * EXEMPLO 3: Validar Formulário de Comentário com Limite de Caracteres
 */
function exemplo_validacao_comentario() {
    $idDuvida = $_POST['doubt_id'] ?? '';
    $comentario = $_POST['comentario'] ?? '';
    
    $campos = [
        'ID da Dúvida' => [
            'valor' => $idDuvida,
            'regras' => [
                'obrigatorio' => true,
                'numeros' => true
            ]
        ],
        'Comentário' => [
            'valor' => $comentario,
            'regras' => [
                'obrigatorio' => true,
                'maximo' => 250
            ]
        ]
    ];
    
    $validacao = ValidadorCampos::validarMultiplos($campos);
    
    if (!$validacao['valido']) {
        retornarErroValidacao(
            'Verifique o seu comentário.',
            $validacao['erros']
        );
    }
    
    // Sanitizar
    $comentario = ValidadorCampos::sanitizar($comentario);
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Inserir comentário
        $stmt = $db->prepare("
            INSERT INTO doubt_comments (doubt_id, user_id, comment_text, created_at)
            VALUES (:doubt_id, :user_id, :comment, NOW())
        ");
        
        $stmt->execute([
            ':doubt_id' => $idDuvida,
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':comment' => $comentario
        ]);
        
        retornarSucesso('Comentário adicionado com sucesso!');
        
    } catch (Exception $e) {
        error_log("Comment error: " . $e->getMessage());
        retornarErroGenerico('Erro ao adicionar comentário.');
    }
}

/**
 * EXEMPLO 4: Validação de Campo Numérico
 */
function exemplo_validacao_numero() {
    $valor = $_POST['quantidade'] ?? '';
    
    $resultado = ValidadorCampos::validarApenasNumeros($valor, 'Quantidade');
    
    if (!$resultado['valido']) {
        retornarErroValidacao($resultado['erro']);
    }
    
    // Processar...
    retornarSucesso('Quantidade válida!');
}

/**
 * EXEMPLO 5: Validação de Email Único
 */
function exemplo_validacao_email_unico() {
    $email = $_POST['email'] ?? '';
    
    $resultado = ValidadorCampos::validarEmail($email, 'Email');
    
    if (!$resultado['valido']) {
        retornarErroValidacao($resultado['erro']);
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            retornarErroValidacao('Este email já está registado.');
        }
        
        retornarSucesso('Email disponível!');
        
    } catch (Exception $e) {
        error_log("Email check error: " . $e->getMessage());
        retornarErroGenerico('Erro ao verificar email.');
    }
}

/**
 * FUNÇÕES AUXILIARES JÁ DEFINIDAS EM validacoes.php:
 * - retornarSucesso($mensagem, $dados)
 * - retornarErroValidacao($mensagem, $erros)
 * - retornarErroGenerico($mensagem)
 */

?>

<!-- 
INSTRUÇÕES DE USO:

1. No seu formulário frontend, chamar via AJAX:
   
   fetch('/interface_programacao/exemplo_validacao.php', {
       method: 'POST',
       headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
       body: new FormData(document.getElementById('meuFormulario'))
   })
   .then(r => r.json())
   .then(data => {
       if (data.sucesso) {
           ValidadorFormulario.mostrarNotificacao(data.mensagem, 'sucesso');
           if (data.dados?.redirect) {
               window.location.href = data.dados.redirect;
           }
       } else {
           ValidadorFormulario.mostrarNotificacao(data.mensagem, 'erro');
       }
   })
   .catch(e => {
       ValidadorFormulario.mostrarNotificacao('Erro ao processar', 'erro');
   });

2. As validações feitas NO FRONTEND não são suficientes - 
   SEMPRE validar novamente no BACKEND

3. NUNCA retornar detalhes de erro da BD - usar retornarErroGenerico()

4. SEMPRE sanitizar com ValidadorCampos::sanitizar()

5. Usar try-catch para erros de BD e registar em error_log()
-->
