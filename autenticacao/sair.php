<?php
/**
 * autenticacao/sair.php
 * 
 * Este ficheiro gere a terminação da sessão do utilizador.
 * Ele limpa todas as variáveis e garante que o acesso à área privada seja cortado
 * imediatamente, protegendo a conta caso o utilizador esteja em um computador partilhado.
 */

// Iniciamos o acesso à sessão atual para podermos destruí-la.
session_start();

// Limpamos todas as variáveis existentes na memória da $_SESSION (ex: user_id, user_name).
session_unset();

// Destruímos completamente os dados físicos da sessão no servidor.
session_destroy();

/**
 * LIMPEZA DE COOKIES DE SESSÃO
 * Remove o cookie de PHPSESSID do navegador para garantir limpeza completa
 */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Limpar também cookies de autenticação customizados se existirem
$cookies_to_clear = ['user_id', 'user_type', 'user_email', 'remember_me'];
foreach ($cookies_to_clear as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        setcookie($cookie_name, '', time() - 3600, '/');
    }
}

/**
 * HEADERS DE SEGURANÇA E CACHE
 * Impede que o navegador guarde em cache dados da sessão anterior
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

/**
 * REDIRECIONAMENTO DE SEGURANÇA
 * Após o logout bem-sucedido, enviamos o utilizador de volta para a landing page (página de convidados),
 * impedindo que este continue a ver conteúdos restritos na cache do navegador.
 */
header("Location: ../paginas/guest/landing.php");
exit();
?>
