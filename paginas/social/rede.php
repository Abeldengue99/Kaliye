<?php
// paginas/social/rede.php - Gestão de Conexões (A Minha Rede)
session_start();
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Redireciona se não estiver logado
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "autenticacao/entrar.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

// Buscar Pedidos Recebidos Pendentes
// Quem pede é o requester_id, portanto eu sou o outro utilizador e o status = 'pending'
$pending_query = "
    SELECT u.user_id, u.full_name, u.user_type, u.verification_status, u.profile_pic as profile_picture_url, 
           c.created_at as request_date
    FROM user_connections c
    JOIN users u ON (c.requester_id = u.user_id)
    WHERE (c.user_id_1 = :uid OR c.user_id_2 = :uid)
      AND c.requester_id != :uid
      AND c.status = 'pending'
    ORDER BY c.created_at DESC
";
$pending_stmt = $db->prepare($pending_query);
$pending_stmt->execute([':uid' => $user_id]);
$pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar Minhas Conexões (Aceites)
// O status = 'accepted' e a outra pessoa é a conexão
$connections_query = "
    SELECT u.user_id, u.full_name, u.user_type, u.verification_status, u.profile_pic as profile_picture_url,
           c.created_at as connected_since
    FROM user_connections c
    JOIN users u ON (u.user_id = CASE WHEN c.user_id_1 = :uid THEN c.user_id_2 ELSE c.user_id_1 END)
    WHERE (c.user_id_1 = :uid OR c.user_id_2 = :uid)
      AND c.status = 'accepted'
    ORDER BY c.created_at DESC
";
$conn_stmt = $db->prepare($connections_query);
$conn_stmt->execute([':uid' => $user_id]);
$my_connections = $conn_stmt->fetchAll(PDO::FETCH_ASSOC);

// Função auxiliar para avatar
function getNetworkAvatar($url) {
    if (empty($url)) return '../../recursos/images/avatars/student.png';
    if (strpos($url, 'http') === 0) return $url;
    return '../../' . ltrim($url, '/');
}

$page_title = "A Minha Rede | KALIYE";
require_once '../../inclusoes/cabecalho.php';
?>

<style>
.network-page-shell {
    padding-top: 100px;
    padding-bottom: 50px;
    min-height: 100vh;
    background: var(--bg-0);
}
.network-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 2rem;
}
.network-header {
    margin-bottom: 2rem;
}
.network-header h1 {
    font-family: 'Outfit', sans-serif;
    color: #fff;
    font-size: 2rem;
    font-weight: 800;
}
.network-header p {
    color: rgba(255,255,255,0.6);
    font-size: 1rem;
    margin-top: 5px;
}
.network-section {
    background: rgba(13, 22, 40, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}
.network-section-title {
    color: #f7941d;
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.network-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
}
.network-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 1.25rem;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
.network-card:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(247, 148, 29, 0.3);
    transform: translateY(-3px);
}
.nc-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 1rem;
    border: 2px solid rgba(247, 148, 29, 0.2);
}
.nc-name {
    color: #fff;
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 4px;
}
.nc-role {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8rem;
    margin-bottom: 1rem;
    text-transform: capitalize;
}
.nc-actions {
    display: flex;
    gap: 10px;
    width: 100%;
}
.nc-btn {
    flex: 1;
    padding: 0.6rem;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.nc-btn-accept {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.nc-btn-accept:hover { background: #10b981; color: #fff; }
.nc-btn-reject {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.nc-btn-reject:hover { background: #ef4444; color: #fff; }
.nc-btn-msg {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
}
.nc-btn-msg:hover { background: #3b82f6; color: #fff; }
.nc-btn-view {
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.1);
}
.nc-btn-view:hover { background: rgba(255, 255, 255, 0.1); }
.empty-state {
    text-align: center;
    padding: 2rem;
    color: rgba(255,255,255,0.4);
}
.empty-state i {
    font-size: 2rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>

<div class="network-page-shell">
    <div class="network-container">
        
        <div class="network-header">
            <h1>A Minha Rede</h1>
            <p>Gerencie as suas conexões e expanda a sua rede no ecossistema KALIYE.</p>
        </div>

        <!-- Pedidos Pendentes -->
        <div class="network-section">
            <h3 class="network-section-title">
                <i class="fas fa-user-clock"></i> Pedidos de Conexão Pendentes (<?php echo count($pending_requests); ?>)
            </h3>
            
            <?php if(count($pending_requests) > 0): ?>
                <div class="network-grid">
                    <?php foreach($pending_requests as $req): ?>
                        <div class="network-card" id="card-req-<?php echo $req['user_id']; ?>">
                            <img src="<?php echo getNetworkAvatar($req['profile_picture_url']); ?>" class="nc-avatar" alt="Avatar">
                            <div class="nc-name"><?php echo htmlspecialchars($req['full_name']); ?></div>
                            <div class="nc-role">
                                <?php echo htmlspecialchars($req['user_type']); ?> 
                                <?php if($req['verification_status'] === 'verified') echo '<i class="fas fa-check-circle" style="color:#10b981; margin-left:4px;"></i>'; ?>
                            </div>
                            <div class="nc-actions">
                                <button class="nc-btn nc-btn-accept" onclick="respondRequest(<?php echo $req['user_id']; ?>, 'accept')"><i class="fas fa-check"></i> Aceitar</button>
                                <button class="nc-btn nc-btn-reject" onclick="respondRequest(<?php echo $req['user_id']; ?>, 'reject')"><i class="fas fa-times"></i> Recusar</button>
                            </div>
                            <div style="margin-top:10px; width:100%;">
                                <button class="nc-btn nc-btn-view" style="width:100%;" onclick="openGlobalUserModal(<?php echo $req['user_id']; ?>)">Ver Perfil</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Não tem pedidos de conexão pendentes no momento.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Minhas Conexões -->
        <div class="network-section">
            <h3 class="network-section-title" style="color: #3b82f6;">
                <i class="fas fa-users"></i> As Minhas Conexões (<?php echo count($my_connections); ?>)
            </h3>
            
            <?php if(count($my_connections) > 0): ?>
                <div class="network-grid">
                    <?php foreach($my_connections as $conn): ?>
                        <div class="network-card" id="card-conn-<?php echo $conn['user_id']; ?>">
                            <img src="<?php echo getNetworkAvatar($conn['profile_picture_url']); ?>" class="nc-avatar" alt="Avatar">
                            <div class="nc-name"><?php echo htmlspecialchars($conn['full_name']); ?></div>
                            <div class="nc-role">
                                <?php echo htmlspecialchars($conn['user_type']); ?>
                                <?php if($conn['verification_status'] === 'verified') echo '<i class="fas fa-check-circle" style="color:#10b981; margin-left:4px;"></i>'; ?>
                            </div>
                            
                            <?php 
                                // Restrição de Mensagem: Se sou estudante e ele é mentor/investidor, o botão de msg não aparece
                                $show_msg = true;
                                $my_type = $_SESSION['user_type'] ?? '';
                                $is_me_student = in_array($my_type, ['student', 'univ_student', 'high_student']);
                                $is_them_authority = in_array($conn['user_type'], ['mentor', 'investor']);
                                if ($is_me_student && $is_them_authority) {
                                    $show_msg = false;
                                }
                            ?>

                            <div class="nc-actions">
                                <?php if($show_msg): ?>
                                    <button class="nc-btn nc-btn-msg" onclick="window.location.href='messages.php?user=<?php echo $conn['user_id']; ?>'"><i class="fas fa-comment-dots"></i> Mensagem</button>
                                <?php endif; ?>
                                <button class="nc-btn nc-btn-view" onclick="openGlobalUserModal(<?php echo $conn['user_id']; ?>)">Ver Perfil</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <p>Ainda não tem conexões na sua rede. Comece a interagir na plataforma!</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function respondRequest(targetId, action) {
    const btnAccept = document.querySelector('#card-req-' + targetId + ' .nc-btn-accept');
    const btnReject = document.querySelector('#card-req-' + targetId + ' .nc-btn-reject');
    
    if(btnAccept) btnAccept.disabled = true;
    if(btnReject) btnReject.disabled = true;

    const fd = new FormData();
    fd.append('target_id', targetId);
    fd.append('action', action);

    fetch('<?php echo $base_url; ?>interface_programacao/user/connection_action.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: action === 'accept' ? 'Conexão adicionada à sua rede!' : 'Pedido recusado.',
                background: '#0d1628',
                color: '#fff',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload(); // Atualiza a página para refletir as listas
            });
        } else {
            Swal.fire({ title: 'Erro', text: data.error || data.message, icon: 'error' });
            if(btnAccept) btnAccept.disabled = false;
            if(btnReject) btnReject.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        if(btnAccept) btnAccept.disabled = false;
        if(btnReject) btnReject.disabled = false;
    });
}
</script>

<?php require_once '../../inclusoes/rodape.php'; ?>
