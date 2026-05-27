<?php
require_once 'configuracoes/base_dados.php';

// LISTA DE TABELAS PARA REMOÇÃO (Análise de desnecessárias/redundantes para o MVP)
$tables_to_drop = [
    'badges',                           // Gamificação (Não usada)
    'user_badges',                      // Gamificação (Não usada)
    'certificates',                     // Gamificação (Não usada)
    'mentorship_certificates',          // Gamificação (Não usada)
    'mentorship_paths',                 // Complexidade de Trilhas (Excesso)
    'mentorship_path_steps',            // Complexidade de Trilhas (Excesso)
    'mentorship_path_enrollments',      // Complexidade de Trilhas (Excesso)
    'mentorship_path_progress',         // Complexidade de Trilhas (Excesso)
    'institutions',                     // Complexidade B2B/Institucional (Não usada)
    'institution_challenges',           // Complexidade B2B/Institucional (Não usada)
    'institution_opportunities',        // Complexidade B2B/Institucional (Não usada)
    'institution_invitations',          // Complexidade B2B/Institucional (Não usada)
    'mentorship_acknowledgments',       // Burocracia redundante
    'mentorship_item_targets',          // Burocracia redundante
    'mentor_notices',                   // Avisos redundantes (já temos notificações)
    'announcements',                    // Avisos redundantes (já temos notificações)
    'followers',                        // Social redundante (foco em networking)
    'project_endorsements',             // Social redundante (já temos likes e investimentos)
    'activity_logs'                     // Redundante (já temos audit_logs e login_logs)
];

try {
    $db = (new Database())->getConnection();
    $db->exec("SET FOREIGN_KEY_CHECKS = 0"); // Desativar checks para permitir remoção limpa

    echo "Analizando e limpando base de dados...\n";
    foreach ($tables_to_drop as $table) {
        $db->exec("DROP TABLE IF EXISTS `$table` CASCADE");
        echo "[-] Tabela removida: $table\n";
    }

    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\nLimpeza concluída com sucesso. Base de dados otimizada.\n";

} catch (Exception $e) {
    echo "Erro durante a limpeza: " . $e->getMessage() . "\n";
}
