<?php
// Diagnostic configuration
$debug_mode = false; 
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Define basic path if not already set
if (!isset($base_url)) {
    $base_url = './';
}

// Database Connection
if (!isset($db)) {
    require_once __DIR__ . '/../configuracoes/base_dados.php';
    try {
        $db = (new Database())->getConnection();
        $run_header_schema_repair = false;
        if ($run_header_schema_repair) {
        
        // AUTO-REPARAÇÃO: Cria as tabelas vitais se elas não existirem
        $db->exec("CREATE TABLE IF NOT EXISTS knowledge_areas (
            area_id SERIAL PRIMARY KEY, 
            name VARCHAR(100) NOT NULL, 
            icon VARCHAR(50), 
            color VARCHAR(20), 
            category VARCHAR(50), 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE IF NOT EXISTS user_expertises (
            expertise_id SERIAL PRIMARY KEY, 
            user_id INTEGER NOT NULL, 
            area_id INTEGER NOT NULL, 
            proficiency_level VARCHAR(20) DEFAULT 'beginner', 
            is_primary BOOLEAN DEFAULT FALSE, 
            years_experience INTEGER DEFAULT 0, 
            description TEXT, 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
            UNIQUE(user_id, area_id)
        )");

        // AUTO-REPARAÇÃO: Adicionar colunas de Mentoria à tabela users se não existirem
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS mentorship_status VARCHAR(20) DEFAULT 'unsubmitted'");
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS specialization_tags TEXT");
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS years_of_experience INTEGER DEFAULT 0");
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS linkedin_url VARCHAR(255)");
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS cv_path VARCHAR(255)");
        
        // AUTO-REPARAÇÃO: Tabela de Notificações
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            notification_id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            type VARCHAR(50),
            title VARCHAR(255),
            content TEXT,
            link VARCHAR(255),
            is_read BOOLEAN DEFAULT FALSE,
            sender_id INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Garantir que a coluna is_read existe na tabela notifications (caso a tabela tenha sido criada antes por outro script)
        $db->exec("DO $$ 
            BEGIN 
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='notifications' AND column_name='is_read') THEN
                    ALTER TABLE notifications ADD COLUMN is_read BOOLEAN DEFAULT FALSE;
                END IF;
            END $$;");

        // AUTO-REPARAÇÃO: Tabela de Avaliações (Carrossel da Comunidade)
        $db->exec("CREATE TABLE IF NOT EXISTS platform_evaluations (
            evaluation_id SERIAL PRIMARY KEY,
            user_id INTEGER,
            rating INTEGER DEFAULT 5,
            comment TEXT,
            is_featured BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // SEEDING: Popular se estiver vazia para garantir o "Elite Wow"
        $check_evals = $db->query("SELECT COUNT(*) FROM platform_evaluations")->fetchColumn();
        if ($check_evals == 0) {
            $db->exec("INSERT INTO platform_evaluations (user_id, rating, comment, is_featured) VALUES 
                (null, 5, 'A KALIYE mudou a forma como encaro os meus projetos académicos!', true),
                (null, 5, 'Encontrei o mentor perfeito para a minha startup aqui.', true),
                (null, 5, 'Sistema de mentoria super intuitivo e profissional.', true)");
        }

        // AUTO-REPARAÇÃO: Mentoria (Sessões e Tarefas)
        $db->exec("CREATE TABLE IF NOT EXISTS mentorship_slots (
            slot_id SERIAL PRIMARY KEY,
            mentor_id INTEGER NOT NULL,
            participant_id INTEGER,
            start_time TIMESTAMP NOT NULL,
            end_time TIMESTAMP NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            meeting_link VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS mentorship_tasks (
            task_id SERIAL PRIMARY KEY,
            mentor_id INTEGER NOT NULL,
            mentee_id INTEGER NOT NULL,
            task_name VARCHAR(255) NOT NULL,
            description TEXT,
            deadline TIMESTAMP,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        }

    } catch (Exception $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Header Inclusions
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/SystemSettings.php';
enforceMaintenanceMode($db, $base_url);
require_once __DIR__ . '/components/header/logic.php';

// HTML Head
require_once __DIR__ . '/components/header/head.php';
?>
<body>
    
    <?php if (isset($trigger_kyc_modal) && $trigger_kyc_modal): ?>
    <!-- KYC Trigger Active: Modal will be handled by JS to avoid stuck blur -->
    <?php endif; ?>

    <?php 
    // Navbar
    require_once __DIR__ . '/components/header/navbar.php'; 
    ?>

    <?php 
    // Scripts
    require_once __DIR__ . '/components/header/scripts.php'; 
    ?>

    <!-- ── BOTTOM NAVIGATION (MOBILE ONLY) ── -->
    <nav class="bottom-nav">
        <a href="<?php echo $base_url; ?>index.php" class="bottom-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Início</span>
        </a>
        <a href="javascript:void(0)" onclick="openMobileExploreMenu()" class="bottom-nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'explore') !== false || strpos($_SERVER['PHP_SELF'], 'doubts') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-search"></i>
            <span>Explorar</span>
        </a>
        <a href="javascript:void(0)" onclick="openMobileMentorshipMenu()" class="bottom-nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'mentoria') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-graduation-cap"></i>
            <span>Mentoria</span>
        </a>
        <a href="javascript:void(0)" onclick="openMobileProfileMenu()" class="bottom-nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'profile.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i>
            <span>Perfil</span>
        </a>
    </nav>

    <?php 
    // Page Scripts - Removido daqui para evitar execução dupla e precoce (movido para o rodapé)
    ?>

    <div class="main-content-wrapper">
