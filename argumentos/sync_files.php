<?php
// SYNC script - Sincronizar arquivos manualmente
$files_to_copy = [
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\inclusoes\\components\\chat_scripts.php' => 'C:\\xampp\\htdocs\\kaliye\\inclusoes\\components\\chat_scripts.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\paginas\\social\\messages.php' => 'C:\\xampp\\htdocs\\kaliye\\paginas\\social\\messages.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\interface_programacao\\social\\get_mentor_students.php' => 'C:\\xampp\\htdocs\\kaliye\\interface_programacao\\social\\get_mentor_students.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\interface_programacao\\social\\delete_mentor_group.php' => 'C:\\xampp\\htdocs\\kaliye\\interface_programacao\\social\\delete_mentor_group.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\interface_programacao\\social\\update_mentor_group.php' => 'C:\\xampp\\htdocs\\kaliye\\interface_programacao\\social\\update_mentor_group.php',
    // Scripts de sincronização e diagnóstico
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\argumentos\\verify_users_table.php' => 'C:\\xampp\\htdocs\\kaliye\\argumentos\\verify_users_table.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\argumentos\\fix_users_table.php' => 'C:\\xampp\\htdocs\\kaliye\\argumentos\\fix_users_table.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\argumentos\\sync_database.php' => 'C:\\xampp\\htdocs\\kaliye\\argumentos\\sync_database.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\argumentos\\sync_data.php' => 'C:\\xampp\\htdocs\\kaliye\\argumentos\\sync_data.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\argumentos\\README_SINCRONIZACAO.md' => 'C:\\xampp\\htdocs\\kaliye\\argumentos\\README_SINCRONIZACAO.md',
    // Scripts de análise de tabelas
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\argumentos\\analise_tabelas_database.php' => 'C:\\xampp\\htdocs\\kaliye\\argumentos\\analise_tabelas_database.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\argumentos\\comparador_tabelas.php' => 'C:\\xampp\\htdocs\\kaliye\\argumentos\\comparador_tabelas.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\argumentos\\verificador_uso_tabelas.php' => 'C:\\xampp\\htdocs\\kaliye\\argumentos\\verificador_uso_tabelas.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\argumentos\\README_ANALISE_TABELAS.md' => 'C:\\xampp\\htdocs\\kaliye\\argumentos\\README_ANALISE_TABELAS.md',
    // Documentação de PostgreSQL Only
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\POLITICA_BASE_DADOS.md' => 'C:\\xampp\\htdocs\\kaliye\\POLITICA_BASE_DADOS.md',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\GUIA_MIGRACAO_MYSQL_POSTGRESQL.md' => 'C:\\xampp\\htdocs\\kaliye\\GUIA_MIGRACAO_MYSQL_POSTGRESQL.md',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\README_POSTGRESQL_ONLY.md' => 'C:\\xampp\\htdocs\\kaliye\\README_POSTGRESQL_ONLY.md',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\REFERENCIA_RAPIDA.md' => 'C:\\xampp\\htdocs\\kaliye\\REFERENCIA_RAPIDA.md',
];

$results = [];
foreach ($files_to_copy as $src => $dst) {
    if (file_exists($src)) {
        // Criar diretório se não existir
        $dst_dir = dirname($dst);
        if (!is_dir($dst_dir)) {
            mkdir($dst_dir, 0755, true);
        }
        
        if (copy($src, $dst)) {
            $results[] = "✅ " . basename($src);
        } else {
            $results[] = "❌ " . basename($src) . " (erro ao copiar)";
        }
    } else {
        $results[] = "⚠️  " . basename($src) . " (arquivo origem não encontrado)";
    }
}

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $results);
?>
