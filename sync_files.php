<?php
// SYNC script - Sincronizar arquivos manualmente
$files_to_copy = [
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\inclusoes\\components\\chat_scripts.php' => 'C:\\xampp\\htdocs\\kaliye\\inclusoes\\components\\chat_scripts.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\paginas\\social\\messages.php' => 'C:\\xampp\\htdocs\\kaliye\\paginas\\social\\messages.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\interface_programacao\\social\\get_mentor_students.php' => 'C:\\xampp\\htdocs\\kaliye\\interface_programacao\\social\\get_mentor_students.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\interface_programacao\\social\\delete_mentor_group.php' => 'C:\\xampp\\htdocs\\kaliye\\interface_programacao\\social\\delete_mentor_group.php',
    'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências\\interface_programacao\\social\\update_mentor_group.php' => 'C:\\xampp\\htdocs\\kaliye\\interface_programacao\\social\\update_mentor_group.php',
];

$results = [];
foreach ($files_to_copy as $src => $dst) {
    if (copy($src, $dst)) {
        $results[] = "✅ " . basename($src);
    } else {
        $results[] = "❌ " . basename($src);
    }
}

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $results);
?>
