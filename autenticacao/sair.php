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
 * REDIRECIONAMENTO DE SEGURANÇA
 * Após o logout bem-sucedido, enviamos o utilizador de volta para a landing page (página de convidados),
 * impedindo que este continue a ver conteúdos restritos na cache do navegador.
 */
header("Location: ../paginas/guest/landing.php");
exit();
?>
