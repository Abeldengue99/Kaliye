<?php
// servicos/social/get_doubts.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $query = "SELECT d.*, u.full_name, u.profile_pic, u.user_type, u.mentorship_status,
              (SELECT COUNT(*) FROM doubt_comments WHERE doubt_id = d.doubt_id) as comment_count
              FROM doubts d
              JOIN users u ON d.user_id = u.user_id
              ORDER BY d.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $doubts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Refinamento técnico dos metadados para garantir que o front-end recebe dados brutos consistentes.
    foreach ($doubts as &$doubt) { // Percorro cada dúvida recuperada para análise e formatação.
        // O caminho da foto será tratado centralmente no JavaScript para evitar conflitos de ../ redundantes.
        // Mantemos o valor vindo directamente da base de dados (PostgreSQL) conforme a Rule #4.
        
        // Mapeamento de rótulos de tipo de utilizador para uma apresentação humanizada no feed.
        if ($doubt['user_type'] === 'mentor' && ($doubt['mentorship_status'] ?? '') !== 'approved') { // Verifico se o mentor ainda está em fase de aprovação.
            $doubt['user_type_label'] = 'Candidato a Mentor'; // Atribuo rótulo temporário para utilizadores pendentes.
        } else { // Caso seja um utilizador de tipo padrão ou mentor já oficialmente aprovado.
            $types = [ // Dicionário de tradução técnica de tipos de utilizador para o idioma local (Português).
                'student' => 'Estudante', // Mapeio o perfil de estudante para o campo visual.
                'mentor' => 'Mentor', // Mapeio o perfil de mentor certificado.
                'investor' => 'Investidor', // Mapeio o perfil de investidor do ecossistema.
                'admin' => 'Administrador' // Mapeio o perfil com autoridade administrativa.
            ]; // Fecho da matriz de mapeamento.
            $doubt['user_type_label'] = $types[$doubt['user_type']] ?? $doubt['user_type']; // Atribuo o rótulo traduzido ou o valor original caso não exista no mapa.
        } // Conclusão do bloco de tratamento de tipo.
    } // Finalização do ciclo de formatação.

    echo json_encode(['success' => true, 'doubts' => $doubts]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

