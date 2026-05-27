<?php
// processos/migrations/restore_organization_field.php
// Objetivo: Restaurar a capacidade de registar onde o utilizador estuda ou trabalha.
// Abordagem: Campo de texto livre (organization) para simplificar o MVP, cobrindo escolas e empresas.

require_once __DIR__ . '/../../configuracoes/base_dados.php';

$db = new Database();
$conn = $db->getConnection();

function logMsg($msg) {
    echo "[INFO] $msg\n";
}

try {
    logMsg("Iniciando restauração do campo de Instituição/Organização...");

    // Verifica se a coluna já existe
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'organization'");
    if ($check->rowCount() == 0) {
        // Adiciona a coluna genérica 'organization'
        // Ela servirá para: Nome da Faculdade (Estudantes), Nome da Empresa (Investidores), Local de Formação/Trabalho (Mentores)
        $sql = "ALTER TABLE `users` ADD COLUMN `organization` VARCHAR(255) DEFAULT NULL COMMENT 'Nome da Escola, Faculdade ou Empresa'";
        $conn->exec($sql);
        logMsg("Coluna 'organization' adicionada com sucesso à tabela 'users'.");
    } else {
        logMsg("A coluna 'organization' já existe.");
    }
    
    // Opcional: Se quisermos ser muito específicos, podemos ter 'education_place' e 'work_place',
    // mas 'organization' costuma ser suficiente para o "Header" do perfil.
    
    logMsg("Migração concluída.");

} catch(PDOException $e) {
    echo "[ERRO] Falha na migração: " . $e->getMessage() . "\n";
}

