<?php
// admin/chat_monitor.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/ChatSecurity.php';
requireAdmin();

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();
ChatSecurity::ensureSafetyTables($db);

// Get Selected Conversation
$selected_chat = null;
$messages = [];
if (isset($_GET['sender']) && isset($_GET['receiver'])) {
    $s_id = (int)$_GET['sender'];
    $r_id = (int)$_GET['receiver'];
    
    // Fetch user details for header
    $name_stmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $name_stmt->execute([$s_id]);
    $u1 = $name_stmt->fetchColumn();
    $name_stmt->execute([$r_id]);
    $u2 = $name_stmt->fetchColumn();
    $chat_title = "$u1 ↔ $u2";

    // Fetch messages
    $query = "SELECT * FROM messages 
              WHERE (sender_id = ? AND receiver_id = ?) 
                 OR (sender_id = ? AND receiver_id = ?) 
              ORDER BY sent_at ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$s_id, $r_id, $r_id, $s_id]);
    $messages = $stmt->fetchAll();
}

// Get Active Conversations List (Distinct Pairs)
// Logic: Get distinct pairs of sender/receiver. We use LEAST/GREATEST to normalize pairs like (1,2) and (2,1)
$conv_query = "
    SELECT 
        LEAST(sender_id, receiver_id) as user_1,
        GREATEST(sender_id, receiver_id) as user_2,
        MAX(sent_at) as last_msg_time,
        COUNT(*) as msg_count
    FROM messages
    GROUP BY user_1, user_2
    ORDER BY last_msg_time DESC
";
$conversations = $db->query($conv_query)->fetchAll();

$security_reports = $db->query("
    SELECT r.*, reporter.full_name AS reporter_name, reported.full_name AS reported_name,
           m.sender_id AS reported_message_sender, m.receiver_id AS reported_message_receiver
    FROM chat_reports r
    LEFT JOIN users reporter ON reporter.user_id = r.reporter_id
    LEFT JOIN users reported ON reported.user_id = r.reported_user_id
    LEFT JOIN messages m ON m.message_id = r.message_id
    ORDER BY CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END, r.created_at DESC
    LIMIT 12
")->fetchAll();

$active_blocks = $db->query("
    SELECT b.*, blocker.full_name AS blocker_name, blocked.full_name AS blocked_name
    FROM chat_blocks b
    LEFT JOIN users blocker ON blocker.user_id = b.blocker_id
    LEFT JOIN users blocked ON blocked.user_id = b.blocked_id
    ORDER BY b.created_at DESC
    LIMIT 8
")->fetchAll();

$security_logs = $db->query("
    SELECT l.*, u.full_name AS user_name, t.full_name AS target_name
    FROM chat_security_logs l
    LEFT JOIN users u ON u.user_id = l.user_id
    LEFT JOIN users t ON t.user_id = l.target_user_id
    ORDER BY l.created_at DESC
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Monitor - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>" style="background: #050a15; color: #fff; overflow: hidden;">
    
    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Monitor de Conversas</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Supervisão de interações privadas do ecossistema.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <!-- Contador Rápido -->
                <div class="stat-badge-premium" style="background: rgba(247, 148, 29, 0.1); border: 1px solid rgba(247, 148, 29, 0.2); padding: 0.6rem 1.2rem; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-satellite-dish" style="color: #f7941d; font-size: 0.8rem;"></i>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #fff; letter-spacing: 0.5px;">CONVERSAS ATIVAS: <span style="color: #f7941d;"><?php echo count($conversations); ?></span></span>
                </div>
            </div>
        </header>

        <section class="chat-security-grid">
            <article class="admin-card-premium chat-security-card">
                <div class="security-card-head">
                    <div><span>Denuncias</span><strong><?php echo count(array_filter($security_reports, fn($r) => ($r['status'] ?? '') === 'pending')); ?> pendentes</strong></div>
                    <i class="fas fa-flag"></i>
                </div>
                <div class="security-list compact">
                    <?php if (empty($security_reports)): ?>
                        <p class="security-empty">Sem denuncias registadas.</p>
                    <?php endif; ?>
                    <?php foreach ($security_reports as $report): ?>
                        <div class="security-row">
                            <div>
                                <b><?php echo htmlspecialchars($report['category']); ?></b>
                                <span><?php echo htmlspecialchars(($report['reporter_name'] ?? 'Utilizador') . ' -> ' . ($report['reported_name'] ?? 'Alvo')); ?></span>
                                <?php if (!empty($report['details'])): ?><small><?php echo htmlspecialchars($report['details']); ?></small><?php endif; ?>
                            </div>
                            <div class="security-actions">
                                <em class="status-<?php echo htmlspecialchars($report['status']); ?>"><?php echo htmlspecialchars($report['status']); ?></em>
                                <?php if (!empty($report['reported_message_sender']) && !empty($report['reported_message_receiver'])): ?>
                                    <a class="security-open-link" href="?sender=<?php echo (int)$report['reported_message_sender']; ?>&receiver=<?php echo (int)$report['reported_message_receiver']; ?>">Abrir</a>
                                <?php endif; ?>
                                <?php if (($report['status'] ?? '') === 'pending'): ?>
                                    <button onclick="chatAdminAction('resolve_report', <?php echo (int)$report['report_id']; ?>)">Analisar</button>
                                    <button onclick="chatAdminAction('dismiss_report', <?php echo (int)$report['report_id']; ?>)">Arquivar</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="admin-card-premium chat-security-card">
                <div class="security-card-head">
                    <div><span>Bloqueios</span><strong><?php echo count($active_blocks); ?> ativos</strong></div>
                    <i class="fas fa-ban"></i>
                </div>
                <div class="security-list compact">
                    <?php if (empty($active_blocks)): ?>
                        <p class="security-empty">Sem bloqueios no chat.</p>
                    <?php endif; ?>
                    <?php foreach ($active_blocks as $block): ?>
                        <div class="security-row">
                            <div>
                                <b><?php echo htmlspecialchars($block['reason'] ?: 'manual'); ?></b>
                                <span><?php echo htmlspecialchars(($block['blocker_name'] ?? 'Utilizador') . ' bloqueou ' . ($block['blocked_name'] ?? 'Alvo')); ?></span>
                            </div>
                            <div class="security-actions">
                                <button onclick="chatAdminAction('unblock', <?php echo (int)$block['block_id']; ?>)">Desbloquear</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="admin-card-premium chat-security-card">
                <div class="security-card-head">
                    <div><span>Logs</span><strong><?php echo count($security_logs); ?> recentes</strong></div>
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div class="security-list compact">
                    <?php if (empty($security_logs)): ?>
                        <p class="security-empty">Sem eventos recentes.</p>
                    <?php endif; ?>
                    <?php foreach ($security_logs as $log): ?>
                        <div class="security-row">
                            <div>
                                <b><?php echo htmlspecialchars($log['event_type']); ?></b>
                                <span><?php echo htmlspecialchars(($log['user_name'] ?? 'Sistema') . (($log['target_name'] ?? '') ? ' -> ' . $log['target_name'] : '')); ?></span>
                                <small><?php echo htmlspecialchars($log['severity']); ?> | <?php echo date('d/m H:i', strtotime($log['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <!-- Dynamic Split View -->
        <div class="chat-monitor-wrapper" style="display: grid; grid-template-columns: 350px 1fr; gap: 1.5rem; height: calc(100vh - 400px); min-height: 360px;">
            
            <!-- List: Conversations Sidebar -->
            <div class="admin-card-premium" style="display: flex; flex-direction: column; padding: 0; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);">
                <div class="sidebar-search-area" style="padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.01);">
                    <div class="search-box-premium" style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.2); font-size: 0.85rem;"></i>
                        <input type="text" id="userSearch" placeholder="Pesquisar utilizador..." 
                               style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 0.8rem 1rem 0.8rem 2.8rem; color: #fff; font-size: 0.85rem; outline: none; transition: 0.3s;"
                               onfocus="this.style.borderColor='rgba(247, 148, 29, 0.5)'; this.style.background='rgba(0,0,0,0.5)'"
                               onblur="this.style.borderColor='rgba(255,255,255,0.08)'; this.style.background='rgba(0,0,0,0.3)'">
                    </div>
                </div>
                
                <div id="conversationList" class="custom-scrollbar" style="overflow-y: auto; flex: 1;">
                    <?php foreach($conversations as $conv): 
                        $u1_data = $db->query("SELECT full_name FROM users WHERE user_id = " . $conv['user_1'])->fetch();
                        $u2_data = $db->query("SELECT full_name FROM users WHERE user_id = " . $conv['user_2'])->fetch();
                        $fullNameSearch = strtolower($u1_data['full_name'] . ' ' . $u2_data['full_name']);
                        $isActive = (isset($_GET['sender']) && (($_GET['sender'] == $conv['user_1'] && $_GET['receiver'] == $conv['user_2']) || ($_GET['sender'] == $conv['user_2'] && $_GET['receiver'] == $conv['user_1'])));
                    ?>
                    <a href="?sender=<?php echo $conv['user_1']; ?>&receiver=<?php echo $conv['user_2']; ?>" 
                       class="conversation-item-premium"
                       data-users="<?php echo $fullNameSearch; ?>"
                       style="display: block; text-decoration: none; padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.03); background: <?php echo $isActive ? 'rgba(247, 148, 29, 0.08)' : 'transparent'; ?>; transition: all 0.3s; position: relative; border-left: 3px solid <?= $isActive ? '#f7941d' : 'transparent' ?>;">
                        
                        <div style="display: flex; justify-content: space-between; font-size: 0.65rem; color: rgba(255,255,255,0.3); margin-bottom: 0.8rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.8px;">
                            <span style="display: flex; align-items: center; gap: 5px;"><i class="fas fa-bolt" style="color: <?= $isActive ? '#f7941d' : '#4ade80' ?>"></i> <?php echo $conv['msg_count']; ?> EVENTOS</span>
                            <span><?php echo date('d M • H:i', strtotime($conv['last_msg_time'])); ?></span>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 0.6rem; font-weight: 700; color: <?= $isActive ? '#fff' : 'rgba(255,255,255,0.7)' ?>; font-size: 0.95rem; letter-spacing: -0.2px;">
                            <span style="max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo explode(' ', $u1_data['full_name'])[0]; ?></span>
                            <div style="width: 24px; height: 1px; background: rgba(255,255,255,0.1);"></div>
                            <span style="max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo explode(' ', $u2_data['full_name'])[0]; ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- List: Chat Viewer Main Section -->
            <div class="admin-card-premium" style="display: flex; flex-direction: column; padding: 0; overflow: hidden; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05);">
                <?php if (!empty($messages)): ?>
                    <header class="chat-header-premium" style="padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.02); display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 48px; height: 48px; background: rgba(247, 148, 29, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #f7941d; border: 1px solid rgba(247, 148, 29, 0.2);">
                                <i class="fas fa-users-viewfinder" style="font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #fff;"><?php echo htmlspecialchars($chat_title); ?></h3>
                                <div style="font-size: 0.75rem; color: #4ade80; display: flex; align-items: center; gap: 6px; margin-top: 2px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <span class="pulse-green"></span> Sessão Segura sob Supervisão
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.75rem;">
                            <button onclick="location.reload()" class="btn-action info" style="width: 42px; height: 42px; border-radius: 10px;" title="Atualizar Fluxo"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </header>
                    
                    <div id="msgContainer" class="custom-scrollbar" style="flex: 1; overflow-y: auto; padding: 2.5rem; display: flex; flex-direction: column; gap: 2rem; background-image: radial-gradient(circle at 50% 50%, rgba(247, 148, 29, 0.03), transparent);">
                        <?php foreach($messages as $msg): 
                            $isSender = ($msg['sender_id'] == $_GET['sender']);
                            $align = $isSender ? 'flex-start' : 'flex-end';
                        ?>
                        <div class="message-group-admin" style="display: flex; flex-direction: column; align-items: <?php echo $align; ?>; max-width: 80%; align-self: <?php echo $align; ?>;">
                            <div style="font-size: 0.65rem; color: rgba(255,255,255,0.4); margin-bottom: 0.6rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 8px;">
                                <?php if(!$isSender): ?><span style="color: #f7941d;">●</span><?php endif; ?>
                                <?php echo htmlspecialchars($msg['sender_id'] == $s_id ? explode(' ', $u1)[0] : explode(' ', $u2)[0]); ?>
                                <span style="opacity: 0.4;">|</span>
                                <?php echo date('H:i', strtotime($msg['sent_at'])); ?>
                                <?php if($isSender): ?><span style="color: #4ade80;">●</span><?php endif; ?>
                            </div>
                            <div class="message-bubble-premium" style="background: <?php echo $isSender ? 'rgba(255,255,255,0.03)' : 'rgba(247, 148, 29, 0.12)'; ?>; padding: 1.2rem 1.5rem; border-radius: 18px; border: 1px solid <?php echo $isSender ? 'rgba(255,255,255,0.08)' : 'rgba(247, 148, 29, 0.2)'; ?>; color: #fff; font-size: 0.95rem; line-height: 1.7; box-shadow: 0 10px 30px rgba(0,0,0,0.1); position: relative;">
                                <?php echo nl2br(htmlspecialchars(ChatSecurity::revealContent($msg['content'] ?? ''))); ?>
                                
                                <?php if($msg['media_type'] && $msg['media_type'] != 'none'): ?>
                                    <div class="media-attachment-premium" style="margin-top: 1rem; padding: 1rem; background: rgba(0,0,0,0.4); border-radius: 14px; font-size: 0.75rem; display: flex; align-items: center; gap: 0.8rem; border: 1px solid rgba(255,255,255,0.08);">
                                        <div style="width: 32px; height: 32px; background: rgba(247, 148, 29, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #f7941d;">
                                            <i class="fas fa-paperclip"></i>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span style="color: rgba(255,255,255,0.4); font-weight: 800; text-transform: uppercase;">Arquivo Anexado</span>
                                            <span style="color: #fff; font-weight: 700;"><?php echo strtoupper($msg['media_type']); ?> DETETADO</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; padding: 4rem;">
                        <div style="width: 120px; height: 120px; background: linear-gradient(135deg, rgba(247, 148, 29, 0.05), rgba(247, 148, 29, 0.01)); border-radius: 40px; display: flex; align-items: center; justify-content: center; margin-bottom: 2.5rem; border: 1px solid rgba(247, 148, 29, 0.1); transform: rotate(-5deg);">
                            <i class="fas fa-tower-observation" style="font-size: 3rem; color: rgba(247, 148, 29, 0.2);"></i>
                        </div>
                        <h3 style="font-weight: 900; letter-spacing: -1px; font-size: 1.4rem; color: #fff;">Aguardando Seleção</h3>
                        <p style="font-size: 0.95rem; color: rgba(255,255,255,0.4); max-width: 320px; margin-top: 0.8rem; line-height: 1.6;">Use o painel lateral para navegar entre as interações. O monitor de segurança apresentará os dados criptografados aqui.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(247, 148, 29, 0.2); }
        
        .pulse-green {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 10px rgba(74, 222, 128, 0.5);
            animation: pulse-op 2s infinite;
        }
        @keyframes pulse-op {
            0% { opacity: 0.4; }
            50% { opacity: 1; }
            100% { opacity: 0.4; }
        }
        
        .conversation-item-premium:hover {
            background: rgba(255,255,255,0.02) !important;
            transform: translateX(5px);
        }
        .chat-security-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1.2rem;
        }
        .chat-security-card {
            padding: 1rem !important;
            min-height: 190px;
            overflow: hidden;
        }
        .security-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.85rem;
        }
        .security-card-head span {
            display: block;
            color: #f7941d;
            font-size: 0.65rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .security-card-head strong {
            display: block;
            margin-top: 0.2rem;
            font-size: 1rem;
            color: #fff;
        }
        .security-card-head i {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(247, 148, 29, 0.1);
            color: #f7941d;
        }
        .security-list {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            max-height: 132px;
            overflow-y: auto;
        }
        .security-row {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.65rem;
            border-radius: 12px;
            background: rgba(255,255,255,0.025);
            border: 1px solid rgba(255,255,255,0.04);
        }
        .security-row b,
        .security-row span,
        .security-row small {
            display: block;
        }
        .security-row b {
            color: #fff;
            font-size: 0.78rem;
        }
        .security-row span,
        .security-row small,
        .security-empty {
            color: rgba(255,255,255,0.45);
            font-size: 0.7rem;
            line-height: 1.35;
        }
        .security-actions {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            flex-direction: column;
            gap: 0.35rem;
            flex-shrink: 0;
        }
        .security-actions button,
        .security-open-link {
            border: 0;
            border-radius: 8px;
            background: rgba(247, 148, 29, 0.15);
            color: #f7941d;
            padding: 0.35rem 0.55rem;
            font-size: 0.65rem;
            font-weight: 900;
            cursor: pointer;
            text-decoration: none;
        }
        .security-actions em {
            font-style: normal;
            font-size: 0.62rem;
            color: rgba(255,255,255,0.35);
        }
        .status-pending { color: #fbbf24 !important; }
        @media (max-width: 1200px) {
            .chat-security-grid { grid-template-columns: 1fr; }
        }
    </style>

    <script>
        document.getElementById('userSearch').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.conversation-item-premium');
            
            items.forEach(item => {
                const users = item.getAttribute('data-users');
                if (users.includes(term)) item.style.display = 'block';
                else item.style.display = 'none';
            });
        });

        const container = document.getElementById('msgContainer');
        if (container) container.scrollTop = container.scrollHeight;

        function chatAdminAction(action, id) {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('id', id);
            fetch('../../interface_programacao/admin/admin_chat_security_action.php', {
                method: 'POST',
                body: fd
            }).then(r => r.json()).then(data => {
                alert(data.message || 'Acção processada.');
                if (data.success) location.reload();
            }).catch(() => alert('Falha ao processar acção.'));
        }
    </script>
</body>
</html>







