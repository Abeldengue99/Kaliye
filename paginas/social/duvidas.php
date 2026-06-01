<?php
/**
 * ⚠️ REDIRECIONAMENTO: Arquivo Legado
 * 
 * Este arquivo foi movido para: paginas/explorar/doubts.php
 * Qualquer requisição para este arquivo é redirecionada automaticamente
 */

// Obter o ID da dúvida da URL
$doubt_id = $_GET['doubt_id'] ?? null;

if ($doubt_id) {
    // Redirecionar para o novo local com redirect 301 (permanente)
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ../explorar/doubts.php?doubt_id=' . urlencode($doubt_id));
} else {
    // Se não houver doubt_id, redirecionar para a página principal de dúvidas
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ../explorar/doubts.php');
}

exit();
?>
