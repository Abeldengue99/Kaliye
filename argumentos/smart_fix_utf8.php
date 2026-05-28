<?php
// processos/smart_fix_utf8.php
require_once __DIR__ . '/../configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();
$db->exec("set names utf8mb4");

$map = [
    'Música' => ['MÃƒÂºsica', 'M??sica', 'M%qsica%', 'M%sica%'],
    'Gestão de Projetos' => ['GestÃƒÂ£o de Projetos', 'Gest%o de Projetos%'],
    'Produção de Vídeo' => ['ProduÃƒÂ§ÃƒÂ£o%', 'Produ%o de V%deo%'],
    'Fotografia' => ['Fotografia'], // Pode estar ok, mas garante
    'Composição, Edição' => ['ComposiÃƒÂ§ÃƒÂ£o%', 'Composi%o%'],
    'Atuação, Dramaturgia' => ['AtuaÃƒÂ§ÃƒÂ£o%', 'Atua%o%'],
    'Finanças' => ['FinanÃƒÂ§as', 'Finan%as%'],
    'Design Arquitetônico' => ['Design ArquitetÃƒÂ´nico', 'Design Arquitet%nico%'],
    'Ciências' => ['CiÃƒÂªncias', 'Ci%ncias%'],
    'Línguas' => ['LÃƒnguas', 'L%nguas%'],
    'Comunicação' => ['ComunicaÃƒÂ§ÃƒÂ£o', 'Comunica%o%'],
    'Saúde' => ['SaÃƒÂºde', 'Sa%de%'],
    'Negócios' => ['NegÃƒÂ³cios', 'Neg%cios%']
];

echo "Iniciando Smart Fix...\n";

foreach ($map as $correct => $patterns) {
    // 1. Check if CORRECT exists
    $stmt = $db->prepare("SELECT area_id FROM knowledge_areas WHERE name = ?");
    $stmt->execute([$correct]);
    $correct_id = $stmt->fetchColumn();

    foreach ($patterns as $bad_pattern) {
        // Find bad ones
        $stmt_bad = $db->prepare("SELECT area_id, name FROM knowledge_areas WHERE name LIKE ? AND name != ?");
        $stmt_bad->execute([$bad_pattern, $correct]);
        $bad_rows = $stmt_bad->fetchAll();

        foreach ($bad_rows as $bad) {
            $bad_id = $bad['area_id'];
            echo "Encontrado incorreto: {$bad['name']} (ID: $bad_id)\n";

            if ($correct_id) {
                // Correct already exists, MERGE
                echo " -> Mesclando com correto (ID: $correct_id)...\n";
                try {
                    $db->prepare("UPDATE IGNORE user_expertises SET area_id = ? WHERE area_id = ?")->execute([$correct_id, $bad_id]);
                    $db->prepare("DELETE FROM knowledge_areas WHERE area_id = ?")->execute([$bad_id]);
                    echo " -> Mesclado e deletado.\n";
                } catch (Exception $e) {
                    echo " -> Erro ao mesclar: " . $e->getMessage() . "\n";
                }
            } else {
                // Correct doesn't exist, RENAME
                echo " -> Renomeando para $correct...\n";
                try {
                    $db->prepare("UPDATE knowledge_areas SET name = ? WHERE area_id = ?")->execute([$correct, $bad_id]);
                    $correct_id = $bad_id; // Now this is the correct one
                    echo " -> Renomeado.\n";
                } catch (Exception $e) {
                    echo " -> Erro ao renomear: " . $e->getMessage() . "\n";
                }
            }
        }
    }
}
echo "Concluído.";
?>

