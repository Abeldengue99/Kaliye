<?php
/**
 * argumentos/test_populate_applications.php
 * Script para criar dados de teste nas tabelas de candidaturas
 */
require_once '../configuracoes/base_dados.php';
require_once '../inclusoes/ProjectWorkflowSchema.php';

$database = new Database();
$db = $database->getConnection();

// Ensure all tables exist
ensureProjectMentorshipApplicationsSchema($db);
ensureSpecialistApplicationsSchema($db);
ensureStudentApplicationsSchema($db);
ensureInvestmentApplicationsSchema($db);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Teste Candidaturas</title></head><body>";
echo "<h1>Script de Teste - População de Candidaturas</h1>";

try {
    // 1. Verificar se existem projetos
    $proj_count = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    echo "<p>✅ Projetos na BD: <strong>$proj_count</strong></p>";

    if ($proj_count === 0) {
        echo "<p style='color: red;'>❌ Sem projetos. Criar projetos primeiro.</p>";
        exit;
    }

    // 2. Verificar se existem usuários (mentores, especialistas, estudantes)
    $mentor_count = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'mentor'")->fetchColumn();
    $specialist_count = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'mentor'")->fetchColumn();
    $student_count = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'student'")->fetchColumn();
    $investor_count = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'investor'")->fetchColumn();

    echo "<p>✅ Mentores: <strong>$mentor_count</strong></p>";
    echo "<p>✅ Especialistas: <strong>$specialist_count</strong></p>";
    echo "<p>✅ Estudantes: <strong>$student_count</strong></p>";
    echo "<p>✅ Investidores: <strong>$investor_count</strong></p>";

    if ($student_count === 0 || $mentor_count === 0) {
        echo "<p style='color: red;'>❌ Sem utilizadores suficientes. Criando dados de teste...</p>";
    }

    // 3. Contar candidaturas existentes
    $student_apps = $db->query("SELECT COUNT(*) FROM project_student_applications")->fetchColumn();
    $specialist_apps = $db->query("SELECT COUNT(*) FROM project_specialist_applications")->fetchColumn();
    $mentor_apps = $db->query("SELECT COUNT(*) FROM project_mentorship_applications")->fetchColumn();

    echo "<h2>Candidaturas Atuais</h2>";
    echo "<p>✅ Candidaturas de Estudantes: <strong>$student_apps</strong></p>";
    echo "<p>✅ Candidaturas de Especialistas: <strong>$specialist_apps</strong></p>";
    echo "<p>✅ Candidaturas de Mentores: <strong>$mentor_apps</strong></p>";

    // 4. Se não houver candidaturas, criar algumas de teste
    if ($student_apps == 0 && $student_count > 0 && $proj_count > 0) {
        echo "<h2>Criando Candidaturas de Teste...</h2>";
        
        // Get random project, student
        $project = $db->query("SELECT project_id, title, owner_id FROM projects ORDER BY RANDOM() LIMIT 1")->fetch();
        $student = $db->query("SELECT user_id, full_name FROM users WHERE user_type = 'student' AND user_id != " . (int)$project['owner_id'] . " ORDER BY RANDOM() LIMIT 1")->fetch();

        if ($student && $project) {
            $insert = $db->prepare("
                INSERT INTO project_student_applications 
                (project_id, student_id, status, motivation, relevant_skills, learning_objectives, time_availability, academic_background) 
                VALUES (?, ?, 'submitted', ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $project['project_id'],
                $student['user_id'],
                'Estou muito interessado em participar neste projeto porque quero aprender mais sobre a área.',
                'Python, JavaScript, Project Management',
                'Desenvolver habilidades em desenvolvimento web e gestão de projetos',
                '10-15 horas por semana',
                'Licenciatura em Ciência da Computação'
            ]);
            echo "<p style='color: green;'>✅ Candidatura de estudante criada: {$student['full_name']} para \"{$project['title']}\"</p>";
        }
    }

    if ($specialist_apps == 0 && $mentor_count > 0 && $proj_count > 0) {
        echo "<h2>Criando Candidaturas de Especialistas de Teste...</h2>";
        
        $project = $db->query("SELECT project_id, title, owner_id FROM projects ORDER BY RANDOM() LIMIT 1")->fetch();
        $specialist = $db->query("SELECT user_id, full_name FROM users WHERE user_type = 'mentor' AND user_id != " . (int)$project['owner_id'] . " ORDER BY RANDOM() LIMIT 1")->fetch();

        if ($specialist && $project) {
            $insert = $db->prepare("
                INSERT INTO project_specialist_applications 
                (project_id, specialist_id, status, motivation, relevant_experience, proposed_support, specialization_areas, availability) 
                VALUES (?, ?, 'submitted', ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $project['project_id'],
                $specialist['user_id'],
                'Tenho experiência relevante nesta área e gostaria de contribuir.',
                '10 anos de experiência em desenvolvimento de software e liderança técnica',
                'Orientação arquitetônica, revisão de código, mentoria técnica',
                'Arquitetura de Software, DevOps, Leadership',
                '5-8 horas por semana'
            ]);
            echo "<p style='color: green;'>✅ Candidatura de especialista criada: {$specialist['full_name']} para \"{$project['title']}\"</p>";
        }
    }

    // 5. Listar algumas candidaturas
    echo "<h2>Candidaturas de Estudantes</h2>";
    $student_list = $db->query("
        SELECT psa.application_id, psa.status, u.full_name, p.title 
        FROM project_student_applications psa
        JOIN users u ON u.user_id = psa.student_id
        JOIN projects p ON p.project_id = psa.project_id
        LIMIT 5
    ")->fetchAll();

    if (count($student_list) > 0) {
        echo "<ul>";
        foreach ($student_list as $app) {
            echo "<li>#{$app['application_id']} - {$app['full_name']} ({$app['status']}) - {$app['title']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Nenhuma candidatura de estudante encontrada.</p>";
    }

    echo "<h2>Candidaturas de Especialistas</h2>";
    $specialist_list = $db->query("
        SELECT psa.application_id, psa.status, u.full_name, p.title 
        FROM project_specialist_applications psa
        JOIN users u ON u.user_id = psa.specialist_id
        JOIN projects p ON p.project_id = psa.project_id
        LIMIT 5
    ")->fetchAll();

    if (count($specialist_list) > 0) {
        echo "<ul>";
        foreach ($specialist_list as $app) {
            echo "<li>#{$app['application_id']} - {$app['full_name']} ({$app['status']}) - {$app['title']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Nenhuma candidatura de especialista encontrada.</p>";
    }

    echo "<p style='margin-top: 2rem;'><a href='../administracao/users/project_student_applications.php'>Ver Candidaturas de Estudantes</a></p>";
    echo "<p><a href='../administracao/users/project_specialist_applications.php'>Ver Candidaturas de Especialistas</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
