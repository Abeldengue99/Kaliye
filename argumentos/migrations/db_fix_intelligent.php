<?php
// processos/db_fix_intelligent.php
// SCRIPT DE CORREÃƒâ€¡ÃƒO INTELIGENTE DE SCHEMA
// Executa alterações apenas se necessário, evitando erros #1060 (Coluna duplicada)
// --------------------------------------------------------------------------

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'alugue_facil_nacional'); // Confirmar se este é o nome correto da DB

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h1><i style='color:green'>âÅ“â€</i> Conectado à base de dados: " . DB_NAME . "</h1><hr>";
} catch (PDOException $e) {
    die("<h1 style='color:red'>Erro de Conexão: " . $e->getMessage() . "</h1>Verifique o nome da DB no arquivo.");
}

function safeExecute($pdo, $sql, $desc) {
    try {
        $pdo->exec($sql);
        echo "<p style='color:green'><b>[SUCESSO]</b> $desc</p>";
    } catch (PDOException $e) {
        // Se tabela já existe
        if (strpos($e->getMessage(), '1050') !== false) {
             echo "<p style='color:orange'>[INFO] $desc (Tabela já existia)</p>";
        } else {
             echo "<p style='color:red'>[ERRO] $desc: " . $e->getMessage() . "</p>";
        }
    }
}

function addColumnIfNotExists($pdo, $table, $column, $definition) {
    try {
        // Check if column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        
        if ($stmt->rowCount() == 0) {
            // Column does not exist, optimize add
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "<p style='color:green'><b>[CORRIGIDO]</b> Coluna '$column' adicionada à tabela '$table'.</p>";
        } else {
            echo "<p style='color:gray'>[OK] Coluna '$column' já existe em '$table'.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>[ERRO] Falha ao adicionar '$column': " . $e->getMessage() . "</p>";
    }
}

echo "<h2>1. Criação de Tabelas em Falta</h2>";

// 1. SKILLS
safeExecute($pdo, "
CREATE TABLE IF NOT EXISTS skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Tabela 'skills'");

// 2. USER_SKILLS
safeExecute($pdo, "
CREATE TABLE IF NOT EXISTS user_skills (
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    type ENUM('learner', 'expert') NOT NULL DEFAULT 'learner',
    level INT DEFAULT 1,
    PRIMARY KEY (user_id, skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Tabela 'user_skills'");

// 3. PROJECT_TAGS
safeExecute($pdo, "
CREATE TABLE IF NOT EXISTS project_tags (
    project_id INT NOT NULL,
    skill_id INT NOT NULL,
    PRIMARY KEY (project_id, skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Tabela 'project_tags'");

// 4. INVESTIMENTOS
safeExecute($pdo, "
CREATE TABLE IF NOT EXISTS project_investments (
    investment_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    investor_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
    terms_agreement TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Tabela 'project_investments'");

// 5. BADGES
safeExecute($pdo, "
CREATE TABLE IF NOT EXISTS badges (
    badge_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon_url VARCHAR(255),
    criteria TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Tabela 'badges'");

safeExecute($pdo, "
CREATE TABLE IF NOT EXISTS user_badges (
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Tabela 'user_badges'");

// 6. LOGS
safeExecute($pdo, "
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    details TEXT, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Tabela 'activity_logs'");


echo "<h2>2. Verificação de Colunas (Correção do Erro #1060)</h2>";

// MENTORSHIP_BOOKINGS - Adicionar colunas em falta de forma segura
addColumnIfNotExists($pdo, 'mentorship_bookings', 'meeting_link', "VARCHAR(255) DEFAULT NULL");
addColumnIfNotExists($pdo, 'mentorship_bookings', 'meeting_platform', "VARCHAR(50) DEFAULT 'Internal'");
addColumnIfNotExists($pdo, 'mentorship_bookings', 'mentee_rating', "TINYINT DEFAULT NULL");
addColumnIfNotExists($pdo, 'mentorship_bookings', 'mentor_notes', "TEXT DEFAULT NULL");

// NOTIFICATIONS - Garantir estrutura compatível com o novo sistema
safeExecute($pdo, "
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        type VARCHAR(50),
        reference_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Tabela 'notifications' (Check/Create)");

addColumnIfNotExists($pdo, 'notifications', 'message', "TEXT DEFAULT NULL");
addColumnIfNotExists($pdo, 'notifications', 'type', "VARCHAR(50) DEFAULT 'system'");
addColumnIfNotExists($pdo, 'notifications', 'reference_id', "INT DEFAULT NULL");

addColumnIfNotExists($pdo, 'notifications', 'reference_id', "INT DEFAULT NULL");

// COMENTÃƒÂRIOS NOS PROJETOS
safeExecute($pdo, "
    CREATE TABLE IF NOT EXISTS project_comments (
        comment_id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Tabela 'project_comments'");

echo "<hr><h3>Processo Concluído! A base de dados está atualizada e sem erros.</h3>";
echo "<a href='../administracao/finance_dashboard.php' style='display:inline-block; padding:10px 20px; background:green; color:white; text-decoration:none;'>Ir para Painel Financeiro</a>";
?>



