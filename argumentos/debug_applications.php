<?php
/**
 * argumentos/debug_applications.php
 * Debug script para verificar estado das tabelas de candidaturas
 */
require_once '../configuracoes/base_dados.php';
require_once '../inclusoes/ProjectWorkflowSchema.php';

$database = new Database();
$db = $database->getConnection();

// Ensure all tables exist
try {
    ensureProjectMentorshipApplicationsSchema($db);
    ensureSpecialistApplicationsSchema($db);
    ensureStudentApplicationsSchema($db);
    ensureInvestmentApplicationsSchema($db);
    $creation_status = "✅ Tabelas criadas/verificadas com sucesso";
} catch (Exception $e) {
    $creation_status = "❌ Erro ao criar tabelas: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Candidaturas</title>
    <style>
        body { 
            background: #0d1628; 
            color: #fff; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 2rem;
            margin: 0;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #f7941d; border-bottom: 2px solid #f7941d; padding-bottom: 1rem; }
        h2 { color: #60a5fa; margin-top: 2rem; }
        .card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-box {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 900;
            color: #f7941d;
            margin: 0.5rem 0;
        }
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-weight: 700;
        }
        .table-info {
            font-family: monospace;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 1rem;
            overflow-x: auto;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f7941d; }
        a { color: #60a5fa; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Debug Candidaturas</h1>
    
    <div class="card">
        <h2>Status das Tabelas</h2>
        <p class="<?php echo strpos($creation_status, '✅') === 0 ? 'success' : 'error'; ?>">
            <?php echo $creation_status; ?>
        </p>
    </div>

    <div class="card">
        <h2>Estatísticas da Base de Dados</h2>
        <div class="stat-grid">
            <div class="stat-box">
                <div class="stat-label">Projetos</div>
                <div class="stat-number">
                    <?php 
                    try {
                        echo $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
                    } catch (Exception $e) {
                        echo "Erro";
                    }
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Utilizadores</div>
                <div class="stat-number">
                    <?php 
                    try {
                        echo $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
                    } catch (Exception $e) {
                        echo "Erro";
                    }
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Candidaturas Mentores</div>
                <div class="stat-number">
                    <?php 
                    try {
                        echo $db->query("SELECT COUNT(*) FROM project_mentorship_applications")->fetchColumn();
                    } catch (Exception $e) {
                        echo "0";
                    }
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Candidaturas Especialistas</div>
                <div class="stat-number">
                    <?php 
                    try {
                        echo $db->query("SELECT COUNT(*) FROM project_specialist_applications")->fetchColumn();
                    } catch (Exception $e) {
                        echo "0";
                    }
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Candidaturas Estudantes</div>
                <div class="stat-number">
                    <?php 
                    try {
                        echo $db->query("SELECT COUNT(*) FROM project_student_applications")->fetchColumn();
                    } catch (Exception $e) {
                        echo "0";
                    }
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Investimentos</div>
                <div class="stat-number">
                    <?php 
                    try {
                        echo $db->query("SELECT COUNT(*) FROM project_investments")->fetchColumn();
                    } catch (Exception $e) {
                        echo "0";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Utilizadores por Tipo</h2>
        <div class="stat-grid">
            <?php
            try {
                $types = $db->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type")->fetchAll();
                foreach ($types as $type) {
                    echo '<div class="stat-box">';
                    echo '<div class="stat-label">' . ucfirst($type['user_type']) . '</div>';
                    echo '<div class="stat-number">' . $type['count'] . '</div>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<p class="error">Erro ao contar utilizadores</p>';
            }
            ?>
        </div>
    </div>

    <div class="card">
        <h2>Candidaturas de Especialistas (Últimas 5)</h2>
        <?php
        try {
            $apps = $db->query("
                SELECT psa.application_id, psa.status, u.full_name as specialist, p.title as project
                FROM project_specialist_applications psa
                JOIN users u ON u.user_id = psa.specialist_id
                JOIN projects p ON p.project_id = psa.project_id
                ORDER BY psa.created_at DESC
                LIMIT 5
            ")->fetchAll();
            
            if (count($apps) > 0) {
                echo '<table style="width: 100%; border-collapse: collapse;">';
                echo '<tr style="border-bottom: 2px solid rgba(255,255,255,0.1);"><th style="text-align: left; padding: 0.5rem;">ID</th><th style="text-align: left; padding: 0.5rem;">Especialista</th><th style="text-align: left; padding: 0.5rem;">Projeto</th><th style="text-align: left; padding: 0.5rem;">Estado</th></tr>';
                foreach ($apps as $app) {
                    echo '<tr style="border-bottom: 1px solid rgba(255,255,255,0.05);"><td style="padding: 0.5rem;">#' . $app['application_id'] . '</td><td style="padding: 0.5rem;">' . htmlspecialchars($app['specialist']) . '</td><td style="padding: 0.5rem;">' . htmlspecialchars($app['project']) . '</td><td style="padding: 0.5rem;"><span style="background: rgba(96,165,250,0.1); color: #60a5fa; padding: 2px 6px; border-radius: 5px; font-size: 0.75rem;">' . $app['status'] . '</span></td></tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="warning">Nenhuma candidatura de especialista encontrada.</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">Erro: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <div class="card">
        <h2>Candidaturas de Estudantes (Últimas 5)</h2>
        <?php
        try {
            $apps = $db->query("
                SELECT psa.application_id, psa.status, u.full_name as student, p.title as project
                FROM project_student_applications psa
                JOIN users u ON u.user_id = psa.student_id
                JOIN projects p ON p.project_id = psa.project_id
                ORDER BY psa.created_at DESC
                LIMIT 5
            ")->fetchAll();
            
            if (count($apps) > 0) {
                echo '<table style="width: 100%; border-collapse: collapse;">';
                echo '<tr style="border-bottom: 2px solid rgba(255,255,255,0.1);"><th style="text-align: left; padding: 0.5rem;">ID</th><th style="text-align: left; padding: 0.5rem;">Estudante</th><th style="text-align: left; padding: 0.5rem;">Projeto</th><th style="text-align: left; padding: 0.5rem;">Estado</th></tr>';
                foreach ($apps as $app) {
                    echo '<tr style="border-bottom: 1px solid rgba(255,255,255,0.05);"><td style="padding: 0.5rem;">#' . $app['application_id'] . '</td><td style="padding: 0.5rem;">' . htmlspecialchars($app['student']) . '</td><td style="padding: 0.5rem;">' . htmlspecialchars($app['project']) . '</td><td style="padding: 0.5rem;"><span style="background: rgba(96,165,250,0.1); color: #60a5fa; padding: 2px 6px; border-radius: 5px; font-size: 0.75rem;">' . $app['status'] . '</span></td></tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="warning">Nenhuma candidatura de estudante encontrada.</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">Erro: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <div class="card">
        <h2>Links Úteis</h2>
        <ul>
            <li><a href="../administracao/users/project_student_applications.php">📋 Ver Candidaturas de Estudantes</a></li>
            <li><a href="../administracao/users/project_specialist_applications.php">📋 Ver Candidaturas de Especialistas</a></li>
            <li><a href="../administracao/users/project_mentorship_applications.php">📋 Ver Candidaturas de Mentores</a></li>
            <li><a href="../argumentos/test_populate_applications.php">🧪 Criar Dados de Teste</a></li>
        </ul>
    </div>
</div>
</body>
</html>
