<?php
/**
 * configuracoes/legacy_users.php
 * 
 * Lista de usuários legados com permissão para usar senhas curtas.
 * Estes usuários foram cadastrados antes da implementação da regra
 * de senha mínima de 8 caracteres.
 * 
 * Data: 02 de junho de 2026
 */

// Lista de emails de usuários legados que podem usar senhas curtas
$LEGACY_USERS_SHORT_PASSWORD = [
    'alexandrinadeoliveiraale@gmail.com',
    'admin@aksanti.com',
    'anielaniel417@gmail.com'
];

/**
 * Função auxiliar para verificar se um email é de um usuário legado
 */
function isLegacyUser($email) {
    global $LEGACY_USERS_SHORT_PASSWORD;
    return in_array(strtolower(trim($email)), array_map('strtolower', $LEGACY_USERS_SHORT_PASSWORD));
}
?>
