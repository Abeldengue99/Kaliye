<?php
/**
 * argumentos/insert_test_applications.php
 * Script para inserir candidaturas de teste (sem interface, apenas inserção direta)
 */
require_once '../configuracoes/base_dados.php';
require_once '../inclusoes/ProjectWorkflowSchema.php';

$database = new Database();
$db = $database->getConnection();

// Ensure tables exist
ensureProjectMentorshipApplicationsSchema($db);
ensureSpecialistApplicationsSchema($db);
ensureStudentApplicationsSchema($db);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Inserir Dados de Teste</title><style>
body { background: #0d1628; color: #fff; font-family: sans-serif; padding: 2rem; }
.success { color: #10b981; font-weight: bold; }
.error { color: #ef4444; font-weight: bold; }
</style></head><body><h1>Inserir Dados de Teste</h1>";

try {
    // Get a random project that's not owned by a student
    $project = $db->query("
        SELECT project_id, title, owner_id FROM projects 
        WHERE is_public = true 
        ORDER BY RANDOM() LIMIT 1
    ")->fetch();

    if (!$project) {
        echo "<p class='error'>❌ Sem projetos disponíveis</p>";
        exit;
    }

    echo "<p>✅ Projeto selecionado: {$project['title']} (ID: {$project['project_id']})</p>";

    // Insert specialist application
    $specialist = $db->query("
        SELECT user_id, full_name FROM users 
        WHERE user_type = 'mentor' 
        AND user_id != " . (int)$project['owner_id'] . "
        ORDER BY RANDOM() LIMIT 1
    ")->fetch();

    if ($specialist) {
        try {
            $db->prepare("
                INSERT INTO project_specialist_applications 
                (project_id, specialist_id, status, motivation, relevant_experience, proposed_support, specialization_areas, availability, created_at) 
                VALUES (?, ?, 'submitted', ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $project['project_id'],
                $specialist['user_id'],
                'Tenho grande interesse em contribuir com minha expertise para este projeto.',
                '15 anos de experiência em desenvolvimento full-stack e liderança de equipas',
                'Arquitetura de sistemas, orientação técnica, code review',
                'Arquitetura de Software, Leadership, DevOps',
                '5-8 horas por semana'
            ]);
            echo "<p class='success'>✅ Candidatura de especialista criada: {$specialist['full_name']}</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao criar candidatura de especialista: {$e->getMessage()}</p>";
        }
    }

    // Insert student application
    $student = $db->query("
        SELECT user_id, full_name FROM users 
        WHERE user_type = 'student' 
        AND user_id != " . (int)$project['owner_id'] . "
        ORDER BY RANDOM() LIMIT 1
    ")->fetch();

    if ($student) {
        try {
            $db->prepare("
                INSERT INTO project_student_applications 
                (project_id, student_id, status, motivation, relevant_skills, learning_objectives, time_availability, academic_background, created_at) 
                VALUES (?, ?, 'submitted', ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $project['project_id'],
                $student['user_id'],
                'Estou muito motivado em participar neste projeto para aplicar meus conhecimentos e aprender com profissionais experientes.',
                'Python, JavaScript, React, SQL, Git',
                'Melhorar habilidades em desenvolvimento web full-stack e trabalho em equipe',
                '12-15 horas por semana',
                'Licenciatura em Engenharia Informática (3º ano)'
            ]);
            echo "<p class='success'>✅ Candidatura de estudante criada: {$student['full_name']}</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao criar candidatura de estudante: {$e->getMessage()}</p>";
        }
    }

    // Insert another specialist application
    $specialist2 = $db->query("
        SELECT user_id, full_name FROM users 
        WHERE user_type = 'mentor' 
        AND user_id NOT IN (
            SELECT specialist_id FROM project_specialist_applications 
            WHERE project_id = " . (int)$project['project_id'] . "
        )
        AND user_id != " . (int)$project['owner_id'] . "
        ORDER BY RANDOM() LIMIT 1
    ")->fetch();

    if ($specialist2) {
        try {
            $db->prepare("
                INSERT INTO project_specialist_applications 
                (project_id, specialist_id, status, motivation, relevant_experience, proposed_support, specialization_areas, availability, created_at) 
                VALUES (?, ?, 'submitted', ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $project['project_id'],
                $specialist2['user_id'],
                'Possuo experiência direta nesta área e gostaria de contribuir para o sucesso do projeto.',
                '10 anos em análise de dados e machine learning',
                'Consultoria em IA/ML, otimização de algoritmos, orientação técnica',
                'Data Science, Machine Learning, Python',
                '4-6 horas por semana'
            ]);
            echo "<p class='success'>✅ Segunda candidatura de especialista criada: {$specialist2['full_name']}</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao criar segunda candidatura: {$e->getMessage()}</p>";
        }
    }

    // Insert another student application
    $student2 = $db->query("
        SELECT user_id, full_name FROM users 
        WHERE user_type = 'student' 
        AND user_id NOT IN (
            SELECT student_id FROM project_student_applications 
            WHERE project_id = " . (int)$project['project_id'] . "
        )
        AND user_id != " . (int)$project['owner_id'] . "
        ORDER BY RANDOM() LIMIT 1
    ")->fetch();

    if ($student2) {
        try {
            $db->prepare("
                INSERT INTO project_student_applications 
                (project_id, student_id, status, motivation, relevant_skills, learning_objectives, time_availability, academic_background, created_at) 
                VALUES (?, ?, 'submitted', ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $project['project_id'],
                $student2['user_id'],
                'Quero aprofundar meus conhecimentos práticos e ganhar experiência real de projetos.',
                'JavaScript, HTML/CSS, Basico de Node.js',
                'Dominar desenvolvimento full-stack e compreender arquitetura de sistemas complexos',
                '10-14 horas por semana',
                'Licenciatura em Ciência da Computação (2º ano)'
            ]);
            echo "<p class='success'>✅ Segunda candidatura de estudante criada: {$student2['full_name']}</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao criar segunda candidatura de estudante: {$e->getMessage()}</p>";
        }
    }

    echo "<hr>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ul>";
    echo "<li><a href='debug_applications.php' style='color: #60a5fa;'>📊 Ver Estatísticas Completas</a></li>";
    echo "<li><a href='../administracao/users/project_student_applications.php' style='color: #60a5fa;'>📋 Ver Candidaturas de Estudantes</a></li>";
    echo "<li><a href='../administracao/users/project_specialist_applications.php' style='color: #60a5fa;'>📋 Ver Candidaturas de Especialistas</a></li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Erro geral: {$e->getMessage()}</p>";
}

echo "</body></html>";
?>
