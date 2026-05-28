<?php
// servicos/social/get_notifications.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $query = "SELECT n.*, u.profile_pic as sender_pic, u.full_name as sender_name, u.user_type as sender_role,
                     uc.status as conn_status
              FROM notifications n 
              JOIN users me ON n.user_id = me.user_id
              LEFT JOIN users u ON n.sender_id = u.user_id 
              LEFT JOIN user_connections uc ON (
                  (uc.user_id_1 = LEAST(n.user_id, n.sender_id) AND uc.user_id_2 = GREATEST(n.user_id, n.sender_id))
              )
              WHERE n.user_id = :user_id AND n.created_at >= me.created_at
              ORDER BY n.created_at DESC 
              LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contagem de não lidas (Filtramos pela data de criação do usuário também)
    $count_query = "SELECT COUNT(*) FROM notifications n JOIN users u ON n.user_id = u.user_id WHERE n.user_id = :user_id AND CAST(n.is_read AS INTEGER) = 0 AND n.created_at >= u.created_at AND COALESCE(n.type, '') <> 'message'";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':user_id', $user_id);
    $count_stmt->execute();
    $unread_count = $count_stmt->fetchColumn();

    // Função auxiliar para limpar Mojibake (Encoding Fixer) - Versão Ultra-Precisão via Hex
    function fixEncoding($str) {
        if (!$str) return $str;
        
        // Mapeamento baseado em análise hexadecimal real da base de dados (Triple-Encoding Mojibake)
        $hex_map = [
            'c382c2adc386e28099c383c593c383e280a1' => '🚀', 
            'c382c2adc386e28099c383e2809ec383c2ab' => '🎂', 
            'c382c2adc386e28099c383e280a6c383c2a0' => '🏆', 
            'c3a2e2809dc593c383c2ba'               => 'ão',
            'c3a2e2809dc593c383c2ad'               => 'á', 
            'c3a2e2809dc593c382c2a1'               => 'í', 
            'c3a2e2809dc593c383c2a3'               => 'ã', 
            'c3a2e2809dc593c382c2a7'               => 'ç', 
            'c382c2ad'                             => '',  
            'c386e28099'                           => '',  
        ];

        foreach ($hex_map as $hex => $fix) {
            $str = str_replace(hex2bin($hex), $fix, $str);
        }

        // Mapeamento de texto direto (Double/Triple Mixed patterns)
        $text_map = [
            'â”œœú' => 'ã',
            'â”œú'  => 'ã',
            'â”œÂº'  => 'ç',
        ];
        $str = str_replace(array_keys($text_map), array_values($text_map), $str);

        return $str;
    }

    // Processar campos adicionais necessários pelo frontend e Regras de Privacidade (Anonimato de Investidor)
    foreach ($notifications as &$n) {
        $n['has_actions'] = ($n['type'] === 'connection_request' && $n['conn_status'] === 'pending');
        
        if (!empty($n['sender_id']) && $n['sender_id'] > 0 && !empty($n['sender_name'])) {
            $sender_name = $n['sender_name'];
            
        // Regra de Discrição: Ocultar dados de grandes Investidores
            if (isset($n['sender_role']) && $n['sender_role'] === 'investor') {
                $sender_name = 'Um Investidor';
                $n['sender_pic'] = 'default_profile.png';
            }
            
            // Reescrita Inteligente dos Textos (Retrocompatibilidade e Novos Dados)
            // Usamos verificações flexíveis para capturar títulos mesmo corrompidos
            $is_conn = ($n['type'] === 'connection_request' || stripos($n['title'], 'Conex') !== false || stripos($n['title'], 'pediu') !== false);
            
            if ($is_conn) {
                $n['title'] = '🤝 ' . $sender_name . ' pediu conexão';
                $n['content'] = 'Um novo membro quer conectar-se consigo na rede Aksanti. Clique para analisar o perfil.';
            } 
            else if ($n['type'] === 'project_like' || stripos($n['title'], 'Adorou') !== false || stripos($n['title'], 'curtida') !== false) {
                $n['title'] = '❤ ' . $sender_name . ' Adorou o seu Projecto!';
                if (strpos($n['content'], 'Alguém') !== false || strpos($n['content'], 'quer conectar-se') !== false) {
                    $n['content'] = $sender_name . ' reagiu de forma muito positiva a uma das suas inovações.';
                }
            }
        }

        // Lógica Global de Redirecionamento — FORÇA reescrita de link para notificações de projecto
        // Tipos reais na BD: 'comment', 'comment_reply', 'project_like', 'project_vote'
        if (!empty($n['reference_id'])) {
            $type = strtolower($n['type'] ?? '');
            $title = strtolower($n['title'] ?? '');
            
            if ($type === 'comment' || $type === 'comment_reply' || $type === 'project_comment' 
                || strpos($title, 'comentário') !== false || strpos($title, 'comment') !== false
                || strpos($title, 'responderam') !== false) {
                $n['link'] = 'index.php?project_id=' . $n['reference_id'];
            } elseif ($type === 'project_like' || $type === 'project_vote' 
                || strpos($title, 'projecto') !== false || strpos($title, 'voto') !== false 
                || strpos($title, 'adorou') !== false) {
                $n['link'] = 'index.php?project_id=' . $n['reference_id'];
            }
        }

        // APLICAÇÃO DO FIX DE ENCODING NO FINAL DO PROCESSAMENTO (Garante limpeza total)
        $n['title'] = fixEncoding($n['title']);
        $n['content'] = fixEncoding($n['content']);
    }

    // Garante que o output JSON não escape caracteres Unicode (preserva emojis)
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => (int)$unread_count
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no banco de dados']);
}

