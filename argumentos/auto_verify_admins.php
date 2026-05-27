<?php
require_once '../configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("UPDATE users SET is_verified = true WHERE user_type = 'admin'");
    $stmt->execute();
    echo "All admins have been verified.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
