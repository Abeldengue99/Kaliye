<?php
// processos/migrations/setup_admin_rbac.php
require_once dirname(__DIR__, 2) . '/configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create admin_permissions table
    $sql = "CREATE TABLE IF NOT EXISTS admin_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        permission_slug VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_permission (user_id, permission_slug),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Tabela 'admin_permissions' criada com sucesso.\n";

    // Opcional: Atribuir permissões totais ao usuário 1 (assumindo que seja o Super Admin)
    // Se o user_id 1 não for o super admin, o Abel terá que se atribuir manualmente
    // ou usaremos uma lógica de 'is_super_admin' se existisse.
    
    // Por segurança, vamos apenas criar a tabela.

} catch (PDOException $e) {
    die("Erro na migração: " . $e->getMessage());
}
?>

