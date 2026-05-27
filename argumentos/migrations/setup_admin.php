<?php
// setup_admin.php
require_once '../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

$email = 'admin@aksanti.xyz';
$password = 'admin123';
$fullName = 'Administrador Principal';

// Check if admin exists
$stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo "<h1>Admin Account Already Exists</h1>";
    echo "<p>Email: <strong>$email</strong></p>";
    echo "<p>If you forgot the password, please delete this user from database or update the password hash manually.</p>";
} else {
    // Create Admin
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $sql = "INSERT INTO users (full_name, email, password_hash, user_type) VALUES (?, ?, ?, 'admin')";
    $insert = $db->prepare($sql);
    
    if ($insert->execute([$fullName, $email, $hash])) {
        echo "<div style='font-family: sans-serif; padding: 2rem; border: 1px solid #ccc; max-width: 500px; margin: 0 auto; border-radius: 8px;'>";
        echo "<h1 style='color: #10b981;'>Admin Account Created! &check;</h1>";
        echo "<p>You can now login at <a href='entrar.php'>entrar.php</a> with:</p>";
        echo "<p>Email: <strong>$email</strong></p>";
        echo "<p>Password: <strong>$password</strong></p>";
        echo "<hr>";
        echo "<p style='color: #ef4444; font-size: 0.9rem;'>Please delete this file (setup_admin.php) after using it for security.</p>";
        echo "</div>";
    } else {
        echo "Error creating admin account.";
    }
}
?>
