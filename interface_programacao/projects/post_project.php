<?php
/**
 * post_project.php
 * 
 * Este ficheiro é o coração da submissão de projectos na plataforma Aksanti. 
 * Ele gere desde o upload de media (imagens e vídeos de pitch) até à inserção robusta no PostgreSQL,
 * incluindo o novo fluxo de aprovação administrativa e notificações inteligentes para o ecossistema.
 */

// Iniciamos a sessão para garantir que sabemos exatamente quem está a tentar submeter o projecto.
session_start();

// Carregamos as configurações da base de dados (PDO) para comunicarmos com o PostgreSQL.
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/Security.php';

function project_upload_error_message($error_code) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'O vídeo excede o limite configurado no servidor.',
        UPLOAD_ERR_FORM_SIZE => 'O vídeo excede o limite permitido pelo formulário.',
        UPLOAD_ERR_PARTIAL => 'O upload do vídeo ficou incompleto. Tente novamente.',
        UPLOAD_ERR_NO_FILE => 'Nenhum vídeo foi recebido.',
        UPLOAD_ERR_NO_TMP_DIR => 'A pasta temporária do servidor não está disponível.',
        UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar o vídeo.',
        UPLOAD_ERR_EXTENSION => 'Uma extensão do servidor bloqueou o upload do vídeo.'
    ];

    return $messages[$error_code] ?? 'Erro desconhecido ao receber o vídeo.';
}

function ensure_project_upload_dir(&$error_message) {
    $upload_dir = __DIR__ . '/../../carregamentos/projects/';

    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true) && !is_dir($upload_dir)) {
        $error_message = 'Não foi possível criar a pasta de uploads de projectos.';
        return null;
    }

    @chmod($upload_dir, 0775);
    clearstatcache(true, $upload_dir);

    $upload_dir = rtrim($upload_dir, "/\\") . DIRECTORY_SEPARATOR;
    $test_path = $upload_dir . '.write_test_' . uniqid('', true);
    $handle = @fopen($test_path, 'wb');
    if ($handle === false) {
        $error_message = 'A pasta de uploads de projectos não tem permissão de escrita.';
        return null;
    }

    fclose($handle);
    @unlink($test_path);

    return $upload_dir;
}

function save_project_video_upload($file, &$error_message) {
    if (!isset($file) || !is_array($file)) {
        $error_message = 'Nenhum vídeo foi recebido.';
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error_message = project_upload_error_message($file['error'] ?? UPLOAD_ERR_NO_FILE);
        return null;
    }

    $max_bytes = 300 * 1024 * 1024;
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $max_bytes) {
        $error_message = 'O vídeo deve ter até 300 MB.';
        return null;
    }

    $client_mime = strtolower($file['type'] ?? '');
    $detected_mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected_mime = strtolower(finfo_file($finfo, $file['tmp_name']) ?: '');
            finfo_close($finfo);
        }
    }

    $allowed_mimes = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
        'video/quicktime' => 'mov',
        'video/x-msvideo' => 'avi',
        'video/mpeg' => 'mpeg',
    ];
    $mime = $detected_mime ?: $client_mime;
    if (!isset($allowed_mimes[$mime])) {
        $error_message = 'O ficheiro enviado precisa ser um vídeo.';
        return null;
    }

    $upload_dir = ensure_project_upload_dir($error_message);
    if ($upload_dir === null) {
        return null;
    }

    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        $error_message = 'Não foi possível criar a pasta de uploads de projectos.';
        return null;
    }

    if (false && !is_writable($upload_dir)) {
        $error_message = 'A pasta de uploads de projectos não tem permissão de escrita.';
        return null;
    }

    $vid_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $allowed_mimes[$mime];
    $vid_path = $upload_dir . $vid_name;

    if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $vid_path)) {
        $error_message = 'Falha ao guardar o vídeo no servidor.';
        return null;
    }

    return $vid_name;
}

function save_project_image_upload($file, &$error_message, $prefix = 'project_image') {
    $stored = Security::storeUploadedFile(
        $file,
        __DIR__ . '/../../carregamentos/projects',
        'carregamentos/projects',
        [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ],
        10 * 1024 * 1024,
        $prefix
    );

    if (!$stored['ok']) {
        $error_message = $stored['error'];
        return null;
    }

    return $stored['path'];
}

function project_table_columns(PDO $db, $table) {
    $stmt = $db->prepare("
        SELECT column_name
        FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = ?
    ");
    $stmt->execute([$table]);

    $columns = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $column) {
        $columns[$column] = true;
    }

    return $columns;
}

function project_table_exists(PDO $db, $table) {
    $stmt = $db->prepare("
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = ?
        LIMIT 1
    ");
    $stmt->execute([$table]);

    return (bool) $stmt->fetchColumn();
}

function ensure_project_submission_schema(PDO $db) {
    $statements = [
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS image_url VARCHAR(255)",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS video_url VARCHAR(255)",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS pitch_video_url VARCHAR(255)",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS execution_time VARCHAR(100)",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS team_size INTEGER DEFAULT 1",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS project_stage VARCHAR(50) DEFAULT 'Projecto'",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS target_audience TEXT",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS needs_to_advance TEXT",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS idea_origin TEXT",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS motivation TEXT",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS project_url VARCHAR(255)",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS funding_goal NUMERIC(15,2) DEFAULT 0",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS minimum_investment NUMERIC(15,2) DEFAULT 1000",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS maximum_investment NUMERIC(15,2)",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS campaign_start_date TIMESTAMP NULL",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS campaign_end_date TIMESTAMP NULL",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS funding_type VARCHAR(30) DEFAULT 'flexible'",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS equity_available NUMERIC(5,2)",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS approval_status VARCHAR(30) DEFAULT 'pending'",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS status VARCHAR(30) DEFAULT 'pending'",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS is_public BOOLEAN DEFAULT FALSE",
        "ALTER TABLE projects ADD COLUMN IF NOT EXISTS market_score INTEGER DEFAULT 0",
        "CREATE TABLE IF NOT EXISTS project_media (
            media_id SERIAL PRIMARY KEY,
            project_id INTEGER NOT NULL REFERENCES projects(project_id) ON DELETE CASCADE,
            media_url VARCHAR(255) NOT NULL,
            media_type VARCHAR(30) NOT NULL DEFAULT 'image',
            created_at TIMESTAMP DEFAULT NOW()
        )",
        "CREATE TABLE IF NOT EXISTS skills (
            skill_id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            category VARCHAR(50) DEFAULT 'General'
        )",
        "CREATE TABLE IF NOT EXISTS project_tags (
            project_id INTEGER NOT NULL REFERENCES projects(project_id) ON DELETE CASCADE,
            skill_id INTEGER NOT NULL REFERENCES skills(skill_id) ON DELETE CASCADE,
            PRIMARY KEY (project_id, skill_id)
        )"
    ];

    foreach ($statements as $sql) {
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            error_log("Project schema sync skipped: " . $e->getMessage());
        }
    }
}

function add_project_insert_field($columns, &$insert_columns, &$value_expressions, &$params, $column, $value, $placeholder = null) {
    if (!isset($columns[$column])) {
        return;
    }

    $placeholder = $placeholder ?: ':' . $column;
    $insert_columns[] = $column;
    $value_expressions[] = $placeholder;
    $params[$placeholder] = $value;
}

// Apenas aceitamos pedidos via POST, protegendo o endpoint de acessos indevidos por URL.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Verificamos se o utilizador está logado. Se não estiver, mandamos ele para a porta de entrada.
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../../autenticacao/entrar.php");
        exit();
    }

    // Restringir Mentores Puros (que não sejam estudantes nem investidores)
    $user_types = strtolower($_SESSION['user_type'] ?? '');
    $is_mentor_only = (strpos($user_types, 'mentor') !== false || strpos($user_types, 'especialista') !== false) 
                      && strpos($user_types, 'estudante') === false 
                      && strpos($user_types, 'investidor') === false
                      && strpos($user_types, 'admin') === false;

    if ($is_mentor_only) {
        if (isset($_POST['json'])) {
            echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas Estudantes, Estudantes-Mentores e Investidores podem publicar projectos.']);
            exit();
        }
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../../index.php';
        header("Location: " . $redirect . "?error=access_denied&details=" . urlencode('Apenas Estudantes e Investidores podem publicar projectos.'));
        exit();
    }
    /**
     * Validação de Transbordamento (Post Max Size)
     * Se o utilizador tentar enviar um vídeo de 500MB e o servidor só aceitar 100MB, 
     * o PHP limpa as globais $_POST e $_FILES. Capturamos isso aqui para não dar erro de 'missing fields'.
     */
    if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $displayError = "O arquivo enviado excede o limite do servidor (" . ini_get('post_max_size') . ").";
        
        // Se for um pedido AJAX (JSON), respondemos no formato esperado pelo frontend Elite.
        if (isset($_POST['json'])) {
            echo json_encode(['success' => false, 'error' => $displayError]);
            exit();
        }
        
        // Fallback para submissão tradicional com redirect.
        header("Location: ../../index.php?error=file_too_large&details=" . urlencode($displayError));
        exit();
    }

    // Identificamos o autor do projecto através da sessão blindada.
    $owner_id = $_SESSION['user_id'];
    
    // Sanitização rigorosa dos inputs para evitar XSS e garantir a integridade dos dados no banco.
    // Usamos filter_var com filtros de string e URL para 'limpar' o que vem do formulário.
    $title           = filter_var($_POST['title'] ?? '', FILTER_SANITIZE_STRING);
    $description     = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_STRING);
    $category        = filter_var($_POST['category'] ?? '', FILTER_SANITIZE_STRING);
    $budget          = filter_var($_POST['budget'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // Campos da Linha 'Elite' (Informação técnica e estratégica para investidores).
    $execution_time  = filter_var($_POST['execution_time'] ?? '', FILTER_SANITIZE_STRING);
    $team_size       = filter_var($_POST['team_size'] ?? 1, FILTER_SANITIZE_NUMBER_INT);
    $project_stage   = filter_var($_POST['project_stage'] ?? 'Projecto', FILTER_SANITIZE_STRING);

    // Campos de Visão & Estratégia (Essenciais para o algoritmo de análise e filtragem).
    $target_audience = filter_var($_POST['target_audience'] ?? '', FILTER_SANITIZE_STRING);
    $needs_to_advance= filter_var($_POST['needs_to_advance'] ?? '', FILTER_SANITIZE_STRING);
    $idea_origin     = filter_var($_POST['idea_origin'] ?? '', FILTER_SANITIZE_STRING);
    $motivation      = filter_var($_POST['motivation'] ?? '', FILTER_SANITIZE_STRING);
    $project_url     = filter_var($_POST['project_url'] ?? '', FILTER_SANITIZE_URL);

    // Detalhes Financeiros
    $funding_goal    = !empty($_POST['funding_goal']) ? filter_var($_POST['funding_goal'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
    $min_investment  = !empty($_POST['minimum_investment']) ? filter_var($_POST['minimum_investment'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 1000;
    $max_investment  = !empty($_POST['maximum_investment']) ? filter_var($_POST['maximum_investment'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
    $equity_available = !empty($_POST['equity_available']) ? filter_var($_POST['equity_available'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
    
    // Datas de Campanha (Regra: Se não houver data de início, o sistema pode assumir NOW() na query).
    $campaign_start  = !empty($_POST['campaign_start_date']) ? $_POST['campaign_start_date'] : null;
    $campaign_end    = !empty($_POST['campaign_end_date']) ? $_POST['campaign_end_date'] : null;
    $funding_type    = filter_var($_POST['funding_type'] ?? 'flexible', FILTER_SANITIZE_STRING);

    // Validação de Campos Críticos: Não permitimos projectos 'fantasmas' sem título ou descrição.
    if (empty($title) || empty($description)) {
        if (isset($_POST['json'])) {
            echo json_encode(['success' => false, 'error' => 'Título e Descrição são obrigatórios.']);
            exit();
        }
        header("Location: ../../index.php?error=empty_fields&details=" . urlencode("Título e Descrição são obrigatórios."));
        exit();
    }

    /**
     * VERIFICAÇÃO DE VÍDEO OBRIGATÓRIO
     * Implementamos esta regra para garantir que cada novo projecto tenha um pitch visual,
     * aumentando a taxa de conversão para investimento.
     */
    if (!isset($_POST['project_id']) || empty($_POST['project_id'])) {
        if (!isset($_FILES['project_video']) || $_FILES['project_video']['error'] === UPLOAD_ERR_NO_FILE) {
            $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../../index.php';
            $msg = "O vídeo de pitch é obrigatório para validar a originalidade do projecto.";
            
            if (isset($_POST['json'])) {
                echo json_encode(['success' => false, 'error' => $msg]);
                exit();
            }
            header("Location: " . $redirect . "?error=missing_video&details=" . urlencode($msg));
            exit();
        }
    }

    // Instanciamos a conexão segura via PDO.
    $database = new Database();
    $db = $database->getConnection();
    ensure_project_submission_schema($db);

    // Consultamos a base de dados para garantir que os dados estão corretos (mentor restriction removed)
    $role_stmt = $db->prepare("SELECT user_type, mentorship_status FROM users WHERE user_id = ?");
    $role_stmt->execute([$owner_id]);
    $owner_role = $role_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $image_url = null;
    $video_url = null;

    /**
     * GESTÃO DE ASSETS (UPLOADS)
     * Aqui lidamos com o sistema de ficheiros. Usamos time() para evitar conflitos de nomes.
     */
    
    // Upload da Imagem de Capa do Projecto.
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] == 0) {
        $image_upload_error = null;
        $project_upload_dir = ensure_project_upload_dir($image_upload_error);
        if ($project_upload_dir === null) {
            if (isset($_POST['json'])) {
                echo json_encode(['success' => false, 'error' => $image_upload_error]);
                exit();
            }
            $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../../index.php';
            header("Location: " . $redirect . "?error=image_upload_failed&details=" . urlencode($image_upload_error));
            exit();
        }
        $image_url = save_project_image_upload($_FILES['project_image'], $image_upload_error, 'project_cover');
        if ($image_url === null) {
            if (isset($_POST['json'])) {
                echo json_encode(['success' => false, 'error' => $image_upload_error]);
                exit();
            }
            $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../../index.php';
            header("Location: " . $redirect . "?error=image_upload_failed&details=" . urlencode($image_upload_error));
            exit();
        }
    }

    // Upload do Vídeo de Pitch (O componente mais valioso para os investidores).
    if (isset($_FILES['project_video']) && $_FILES['project_video']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_error = null;
        $video_url = save_project_video_upload($_FILES['project_video'], $upload_error);
        if ($video_url === null) {
            if (isset($_POST['json'])) {
                echo json_encode(['success' => false, 'error' => $upload_error]);
                exit();
            }
            $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../../index.php';
            header("Location: " . $redirect . "?error=video_upload_failed&details=" . urlencode($upload_error));
            exit();
        }
    }

    try {
        // Iniciamos uma transação SQL para garantir que ou gravamos TUDO ou nada. (Atomicidade)
        $db->beginTransaction();

        /**
         * INSERÇÃO DO PROJETO NO POSTGRESQL
         * Nota Técnica: Definimos explicitamente 'approval_status' como 'pending' e 'is_public' como FALSE.
         * Isto obriga o projecto a passar pela curadoria do Administrador antes de aparecer no feed global.
         */
        $project_columns = project_table_columns($db, 'projects');
        $insert_columns = [];
        $value_expressions = [];
        $params = [];

        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'owner_id', $owner_id);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'title', $title);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'description', $description);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'category', $category);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'budget_needed', $budget, ':budget');
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'image_url', $image_url, ':image');
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'video_url', $video_url, ':video');
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'pitch_video_url', $video_url, ':pitch_video');
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'execution_time', $execution_time);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'team_size', $team_size);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'project_stage', $project_stage);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'target_audience', $target_audience);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'needs_to_advance', $needs_to_advance);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'idea_origin', $idea_origin);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'motivation', $motivation);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'project_url', $project_url);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'funding_goal', $funding_goal);
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'minimum_investment', $min_investment, ':min_inv');
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'maximum_investment', $max_investment, ':max_inv');
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'campaign_start_date', $campaign_start, ':camp_start');
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'campaign_end_date', $campaign_end, ':camp_end');
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'funding_type', $funding_type, ':fund_type');
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'equity_available', $equity_available);
        
        $content_hash = hash('sha256', $title . $description . $owner_id . time());
        add_project_insert_field($project_columns, $insert_columns, $value_expressions, $params, 'content_hash', $content_hash, ':content_hash');

        if (isset($project_columns['created_at'])) {
            $insert_columns[] = 'created_at';
            $value_expressions[] = 'NOW()';
        }
        if (isset($project_columns['approval_status'])) {
            $insert_columns[] = 'approval_status';
            $value_expressions[] = "'pending'";
        }
        if (isset($project_columns['status'])) {
            $insert_columns[] = 'status';
            $value_expressions[] = "'pending'";
        }
        if (isset($project_columns['is_public'])) {
            $insert_columns[] = 'is_public';
            $value_expressions[] = 'FALSE';
        }

        $query = "INSERT INTO projects (" . implode(', ', $insert_columns) . ")
                  VALUES (" . implode(', ', $value_expressions) . ")
                  RETURNING project_id";
        
        $stmt = $db->prepare($query);
        
        // Fazemos o binding seguro de todos os parâmetros para prevenir SQL Injection de forma absoluta.
        // Os valores são passados em lote para acompanhar o INSERT dinamico.

        // Executamos a query principal.
        if ($stmt->execute($params)) {
            // Capturamos o ID do projecto que acabamos de criar para associar media e tags.
            $project_id = $stmt->fetchColumn();

            /**
             * UPLOAD DE IMAGENS ADICIONAIS (Galeria)
             * Permite que o fundador mostre mais detalhes visuais além da capa e do vídeo.
             */
            if (project_table_exists($db, 'project_media') && isset($_FILES['project_images'])) {
                $files = $_FILES['project_images'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] == 0) {
                        $gallery_upload_error = null;
                        $project_upload_dir = ensure_project_upload_dir($gallery_upload_error);
                        if ($project_upload_dir === null) {
                            throw new Exception($gallery_upload_error);
                        }
                        $gallery_file = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i],
                        ];
                        $m_url = save_project_image_upload($gallery_file, $gallery_upload_error, 'project_gallery');
                        if ($m_url === null) {
                            throw new Exception($gallery_upload_error);
                        }
                        // Registamos cada imagem na tabela de media do projecto.
                        $m_query = "INSERT INTO project_media (project_id, media_url, media_type) VALUES (?, ?, 'image')";
                        $m_stmt = $db->prepare($m_query);
                        $m_stmt->execute([$project_id, $m_url]);
                    }
                }
            }

            /**
             * GESTÃO DE SKILLS / TAGS / TECH STACK
             * Lógica inteligente: Se a tag não existir no mestre de 'skills', nós criamos. 
             * Depois ligamos o projecto a essa skill via 'project_tags'.
             */
            if (project_table_exists($db, 'skills') && project_table_exists($db, 'project_tags') && isset($_POST['tags']) && !empty($_POST['tags'])) {
                $tags = explode(',', $_POST['tags']);
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (empty($tag)) continue;
                    
                    // Verificamos se esta skill já é conhecida pelo sistema para manter a consistência.
                    $s_stmt = $db->prepare("SELECT skill_id FROM skills WHERE name = ?");
                    $s_stmt->execute([$tag]);
                    $skill_id = $s_stmt->fetchColumn();
                    
                    if (!$skill_id) {
                        // Nova Skill detetada! Guardamos no dicionário global.
                        $s_ins = $db->prepare("INSERT INTO skills (name) VALUES (?) RETURNING skill_id");
                        $s_ins->execute([$tag]);
                        $skill_id = $s_ins->fetchColumn();
                    }
                    
                    // Associamos a skill a este projecto específico.
                    $pt_ins = $db->prepare("INSERT INTO project_tags (project_id, skill_id) VALUES (?, ?)");
                    $pt_ins->execute([$project_id, $skill_id]);
                }
            }

            /**
             * GATILHO DE NOTIFICAÇÃO ESTRATÉGICA
             * Se um Investidor publicar um projecto (oportunidade), notificamos o ecossistema automaticamente.
             */
            $u_type_stmt = $db->prepare("SELECT user_type, full_name FROM users WHERE user_id = ?");
            $u_type_stmt->execute([$owner_id]);
            $user_info = $u_type_stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_info && $user_info['user_type'] == 'investor') {
                $notif_title = "🚨 OPORTUNIDADE: Novo Projecto Publicado!";
                $notif_content = "O investidor " . $user_info['full_name'] . " acabou de publicar um projecto estratégica: '$title'. Seja o primeiro a conectar-se!";
                $notif_link = "index.php?id=" . $project_id;

                // Broadcast para todos os perfis relevantes (Estudantes e Mentores).
                $broadcast_query = "
                    INSERT INTO notifications (user_id, sender_id, title, content, type, link, created_at)
                    SELECT user_id, ?, ?, ?, 'opportunity', ?, NOW()
                    FROM users 
                    WHERE user_type IN ('univ_student', 'high_student', 'mentor')
                      AND user_id != ?
                ";
                $broadcast_stmt = $db->prepare($broadcast_query);
                $broadcast_stmt->execute([$owner_id, $notif_title, $notif_content, $notif_link, $owner_id]);
            }

            // Tudo correu bem! Efetuamos o Commit para tornar as alterações permanentes no PostgreSQL.
            $db->commit();
            
            /**
             * CÁLCULO DE MARKET READINESS
             * Atualiza a pontuação de mercado do projecto recém-criado.
             */
            require_once 'calculate_readiness.php';
            try {
                updateProjectScore($db, $project_id);
            } catch (Exception $e) {
                error_log("Project score update skipped: " . $e->getMessage());
            }

            // Verificamos se o pedido espera uma resposta JSON (Nova UX Elite via AJAX).
            if (isset($_POST['json'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Projecto submetido com sucesso! Ele ficara pendente de aprovação administrativa antes de aparecer no feed.',
                    'redirect_url' => 'paginas/explorar/my_projects.php?success=project_pending'
                ]);
                exit();
            }
            
            // Redirect tradicional para garantir compatibilidade com versões anteriores.
            header("Location: ../../paginas/explorar/my_projects.php?success=project_pending");
            exit();
            
        } else {
            // Em caso de erro na execução da query, fazemos Rollback total por segurança dos dados.
            $db->rollBack();
            $err = $stmt->errorInfo();
            $msg = "Falha ao gravar o projecto: " . $err[2];
            
            if (isset($_POST['json'])) { echo json_encode(['success' => false, 'error' => $msg]); exit(); }
            
            $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../../index.php';
            header("Location: " . $redirect . "?error=failed&details=" . urlencode($msg));
            exit();
        }
        
    } catch (PDOException $e) {
        // Bloco de Catch para capturar erros críticos de DB (ex: constraints violadas).
        if ($db->inTransaction()) $db->rollBack();
        error_log("Post Project DB Error: " . $e->getMessage());
        
        if (isset($_POST['json'])) {
            echo json_encode(['success' => false, 'error' => "Erro crítico na base de dados. Por favor, contacte o TI."]);
            exit();
        }
        
        header("Location: ../../index.php?error=db_error&details=" . urlencode("Erro de Banco de Dados: " . $e->getMessage()));
        exit();
    }
}
?>
