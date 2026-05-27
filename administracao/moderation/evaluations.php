<?php
/**
 * administracao/moderation/evaluations.php
 * Gestão e visualização de feedback dos utilizadores sobre a plataforma.
 */
session_start();
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';

// Verificação de Admin (Segurança)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'superadmin')) {
    header("Location: ../../index.php");
    exit();
}

$db = (new Database())->getConnection();

// --- Estatísticas ---
$stats = [
    'total' => 0,
    'avg' => 0,
    'stars' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]
];

try {
    $stats['total'] = $db->query("SELECT COUNT(*) FROM platform_evaluations")->fetchColumn();
    $stats['avg'] = $db->query("SELECT AVG(rating) FROM platform_evaluations")->fetchColumn() ?: 0;
    
    $stars_raw = $db->query("SELECT rating, COUNT(*) as count FROM platform_evaluations GROUP BY rating")->fetchAll();
    foreach ($stars_raw as $s) {
        $stats['stars'][$s['rating']] = $s['count'];
    }

    // --- Trend Data (Last 30 Days) ---
    $trend_stmt = $db->query("SELECT DATE(created_at) as date, AVG(rating) as avg_rating 
                             FROM platform_evaluations 
                             WHERE created_at >= CURRENT_DATE - INTERVAL '30 days' 
                             GROUP BY DATE(created_at) 
                             ORDER BY date ASC");
    $trend_data = $trend_stmt->fetchAll();
} catch (Exception $e) {}

// --- Lista de Avaliações (Paginação) ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $stmt = $db->prepare("SELECT e.*, u.full_name, u.email, u.profile_pic 
                          FROM platform_evaluations e 
                          LEFT JOIN users u ON e.user_id = u.user_id 
                          ORDER BY e.created_at DESC 
                          LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $evaluations = $stmt->fetchAll();
} catch (Exception $e) {
    $evaluations = [];
}

require_once '../../inclusoes/cabecalho.php';
?>

<div class="admin-evaluations-canvas" style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
    
    <div class="header-section" style="margin-bottom: 3rem; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 2.2rem; color: #fff; margin: 0;">Feedback da Plataforma</h1>
            <p style="color: rgba(255,255,255,0.5); font-size: 0.95rem; margin-top: 0.5rem;">Monitoriza o que os utilizadores pensam da KALIYE.</p>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <div class="avg-badge" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); padding: 1rem 2rem; border-radius: 20px; text-align: center;">
                <div style="font-size: 0.65rem; font-weight: 800; color: #f87171; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.4rem;">Sugestões Críticas</div>
                <div style="font-size: 2rem; font-weight: 900; color: #fff; line-height: 1;"><?php echo ($stats['stars'][1] + $stats['stars'][2]); ?></div>
                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); margin-top: 0.4rem;">Requerem atenção</div>
            </div>

            <div class="avg-badge" style="background: rgba(247, 148, 29, 0.1); border: 1px solid rgba(247, 148, 29, 0.2); padding: 1rem 2rem; border-radius: 20px; text-align: center;">
                <div style="font-size: 0.65rem; font-weight: 800; color: #f7941d; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.4rem;">Média Global</div>
                <div style="font-size: 2rem; font-weight: 900; color: #fff; line-height: 1;"><?php echo number_format($stats['avg'], 1); ?> <small style="font-size: 1rem; color: #f7941d;"><i class="fas fa-star"></i></small></div>
                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); margin-top: 0.4rem;"><?php echo $stats['total']; ?> avaliações</div>
            </div>
        </div>
    </div>

    <!-- Star Distribution -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 3rem;">
        <?php for($i=5; $i>=1; $i--): 
            $perc = $stats['total'] > 0 ? ($stats['stars'][$i] / $stats['total']) * 100 : 0;
        ?>
            <div class="stat-card glass-premium" style="padding: 1.25rem; border-radius: 16px; text-align: center;">
                <div style="font-size: 0.8rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;"><?php echo $i; ?> Estrelas</div>
                <div style="font-size: 1.2rem; font-weight: 900; color: #f7941d;"><?php echo $stats['stars'][$i]; ?></div>
                <div style="height: 4px; background: rgba(255,255,255,0.05); border-radius: 2px; margin-top: 0.75rem; position: relative; overflow: hidden;">
                    <div style="position: absolute; left: 0; top: 0; height: 100%; background: #f7941d; width: <?php echo $perc; ?>%;"></div>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Sentiment Analysis / Trend Chart -->
    <div class="trend-section glass-premium" style="padding: 2.5rem; border-radius: 24px; margin-bottom: 3.5rem;">
        <h3 style="font-family: 'Outfit', sans-serif; margin-top: 0; margin-bottom: 2rem; color: #fff;">
            <i class="fas fa-chart-line" style="color: #f7941d; margin-right: 10px;"></i> Tendência de Satisfação (30 dias)
        </h3>
        <div style="height: 300px; position: relative;">
            <canvas id="feedbackTrendChart"></canvas>
        </div>
    </div>

    <!-- Evaluations List -->
    <div class="evaluations-list">
        <?php if (empty($evaluations)): ?>
            <div style="text-align: center; padding: 5rem; background: rgba(255,255,255,0.02); border-radius: 24px; border: 1px dashed rgba(255,255,255,0.1);">
                <i class="fas fa-comment-slash" style="font-size: 3rem; color: rgba(255,255,255,0.1); margin-bottom: 1.5rem;"></i>
                <p style="color: rgba(255,255,255,0.3); font-size: 1rem;">Ainda não foram submetidas avaliações.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1.5rem;">
                <?php foreach ($evaluations as $e): ?>
                    <div class="eval-item glass-premium" style="padding: 1.5rem; border-radius: 20px; display: flex; gap: 1.5rem; align-items: flex-start;">
                        <div style="flex-shrink: 0;">
                            <?php 
                                $pic_raw = $e['profile_pic'] ?? '';
                                $final_pic = $base_url . 'recursos/images/default_profile.png';
                                
                                if (!empty($pic_raw) && $pic_raw !== 'default_profile.png') {
                                    if (strpos($pic_raw, 'http') === 0) {
                                        $final_pic = $pic_raw;
                                    } elseif (strpos($pic_raw, 'carregamentos/') === 0) {
                                        $final_pic = $base_url . $pic_raw;
                                    } else {
                                        $final_pic = $base_url . 'carregamentos/profiles/' . $pic_raw;
                                    }
                                }
                            ?>
                            <img src="<?= $final_pic ?>" 
                                 onerror="this.src='<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png'; this.style.padding='10px'; this.style.background='rgba(255,255,255,0.05)';"
                                 style="width: 50px; height: 50px; border-radius: 12px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                        
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <div>
                                    <h4 style="margin: 0; color: #fff; font-size: 1rem;"><?php echo htmlspecialchars(($e['full_name'] ?? '') ?: 'Usuário Anónimo'); ?></h4>
                                    <span style="font-size: 0.75rem; color: rgba(255,255,255,0.4);"><?php echo htmlspecialchars(($e['email'] ?? '') ?: '-'); ?></span>
                                </div>
                                <div style="text-align: right;">
                                    <div style="color: #f7941d; font-size: 0.9rem; margin-bottom: 0.2rem;">
                                        <?php for($s=1; $s<=5; $s++) echo '<i class="' . ($s <= ($e['rating'] ?? 0) ? 'fas' : 'far') . ' fa-star"></i>'; ?>
                                    </div>
                                    <span style="font-size: 0.7rem; color: rgba(255,255,255,0.3);"><?php echo date('d M Y, H:i', strtotime($e['created_at'] ?? 'now')); ?></span>
                                </div>
                            </div>
                            
                            <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; line-height: 1.6; margin: 0; padding: 1rem; background: <?php echo ($e['rating'] ?? 0) <= 2 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(0,0,0,0.1)'; ?>; border-radius: 12px; border: <?php echo ($e['rating'] ?? 0) <= 2 ? '1px solid rgba(239, 68, 68, 0.3)' : 'none'; ?>;">
                                <?php if (($e['rating'] ?? 0) <= 2): ?>
                                    <span style="display: block; font-size: 0.65rem; font-weight: 800; color: #f87171; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-circle"></i> SUGESTÃO DE MELHORIA</span>
                                <?php endif; ?>
                                <?php echo !empty($e['comment']) ? nl2br(htmlspecialchars($e['comment'])) : '<i style="opacity: 0.5;">Sem comentário adicional.</i>'; ?>
                            </p>

                            <?php if (!empty($e['admin_response'])): ?>
                                <div style="margin-top: 1rem; padding: 1rem; background: rgba(16, 185, 129, 0.05); border-radius: 12px; border-left: 3px solid #10b981;">
                                    <span style="display: block; font-size: 0.65rem; font-weight: 800; color: #10b981; text-transform: uppercase; margin-bottom: 0.5rem;">Resposta da Equipa (<?php echo date('d/m/Y', strtotime($e['responded_at'])); ?>):</span>
                                    <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin: 0;"><?php echo nl2br(htmlspecialchars($e['admin_response'])); ?></p>
                                </div>
                            <?php else: ?>
                                <button onclick="openResponseModal(<?php echo $e['id'] ?? 0; ?>, '<?php echo addslashes($e['full_name'] ?? 'Usuário Anónimo'); ?>', <?php echo $e['rating'] ?? 0; ?>)" style="margin-top: 1rem; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: 0.2s;">
                                    <i class="fas fa-reply"></i> Responder ao Utilizador
                                </button>
                                <?php if (($e['rating'] ?? 0) >= 4): ?>
                                    <button onclick="toggleFeatured(<?php echo $e['id'] ?? 0; ?>, this)" class="btn-feature <?php echo ($e['is_featured'] ?? false) ? 'active' : ''; ?>" style="margin-top: 1rem; margin-left: 0.5rem; background: <?php echo ($e['is_featured'] ?? false) ? '#f7941d' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo ($e['is_featured'] ?? false) ? '#000' : '#fff'; ?>; border: 1px solid rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: 0.2s;">
                                        <i class="fas fa-bullhorn"></i> <?php echo ($e['is_featured'] ?? false) ? 'Destacado no Portal' : 'Destacar no Portal'; ?>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginação -->
            <div style="margin-top: 3rem; display: flex; justify-content: center; gap: 0.5rem;">
                <?php 
                $total_pages = ceil($stats['total'] / $limit);
                for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>" 
                       style="padding: 0.5rem 1rem; border-radius: 8px; background: <?php echo $i == $page ? '#f7941d' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo $i == $page ? '#000' : '#fff'; ?>; text-decoration: none; font-weight: 700; font-size: 0.85rem;">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal de Resposta Admin -->
<div id="responseModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 10000; align-items: center; justify-content: center;">
    <div class="glass-premium" style="width: 100%; max-width: 500px; padding: 2.5rem; border-radius: 24px; position: relative;">
        <h3 style="margin-top: 0; font-family: 'Outfit', sans-serif;">Responder a <span id="respUserName" style="color: #f7941d;">-</span></h3>
        <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-bottom: 1.5rem;">O utilizador receberá uma notificação na plataforma com a tua resposta.</p>
        
        <input type="hidden" id="respEvalId">
        <input type="hidden" id="respRating">
        
        <div id="aiSuggestions" style="margin-bottom: 1.5rem;">
            <span style="display: block; font-size: 0.65rem; font-weight: 800; color: #f7941d; text-transform: uppercase; margin-bottom: 0.8rem;"><i class="fas fa-magic"></i> Assistente de Resposta (Elite Templates)</span>
            <div id="suggestionChips" style="display: flex; flex-wrap: wrap; gap: 8px;">
                <!-- Chips dynamically loaded -->
            </div>
        </div>

        <textarea id="respText" placeholder="Escreve aqui a tua resposta..." rows="6" style="width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1rem; color: #fff; outline: none; resize: none; margin-bottom: 1.5rem;"></textarea>
        
        <div id="meetingInviteArea" style="margin-bottom: 1.5rem; display: none;">
            <button onclick="insertMeetingInvite()" style="width: 100%; padding: 0.75rem; border-radius: 10px; background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px dashed #3b82f6; font-size: 0.8rem; font-weight: 700; cursor: pointer;">
                <i class="fas fa-calendar-alt"></i> Inserir Convite para Reunião de Melhoria
            </button>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button onclick="closeResponseModal()" style="flex: 1; padding: 0.9rem; border-radius: 12px; background: rgba(255,255,255,0.05); color: #fff; border: none; font-weight: 700; cursor: pointer;">Cancelar</button>
            <button id="btnSendResp" onclick="sendResponse()" style="flex: 2; padding: 0.9rem; border-radius: 12px; background: #f7941d; color: #000; border: none; font-weight: 800; cursor: pointer;">Enviar Resposta</button>
        </div>
    </div>
</div>

<script>
async function toggleFeatured(id, btn) {
    const fd = new FormData();
    fd.append('evaluation_id', id);
    try {
        const res = await fetch('../../interface_programacao/admin/toggle_evaluation_featured.php', { method: 'POST', body: fd });
        const data = await res.json();
        if(data.success) {
            btn.classList.toggle('active');
            const isActive = btn.classList.contains('active');
            btn.style.background = isActive ? '#f7941d' : 'rgba(255,255,255,0.05)';
            btn.style.color = isActive ? '#000' : '#fff';
            btn.innerHTML = isActive ? '<i class="fas fa-bullhorn"></i> Destacado no Portal' : '<i class="fas fa-bullhorn"></i> Destacar no Portal';
            Swal.fire({ icon: 'success', title: 'Atualizado', text: data.message, background: '#1e293b', color: '#fff', timer: 1500, showConfirmButton: false });
        }
    } catch(err) { console.error(err); }
}
function openResponseModal(id, name, rating) {
    document.getElementById('respEvalId').value = id;
    document.getElementById('respRating').value = rating;
    document.getElementById('respUserName').innerText = name;
    document.getElementById('respText').value = '';
    document.getElementById('responseModal').style.display = 'flex';
    
    // Mostra convite de reunião apenas para feedbacks negativos
    document.getElementById('meetingInviteArea').style.display = (rating <= 2) ? 'block' : 'none';
    
    renderSuggestions(rating);
}

function renderSuggestions(rating) {
    const container = document.getElementById('suggestionChips');
    container.innerHTML = '';
    
    let templates = [];
    if (rating <= 2) {
        templates = [
            { label: 'Pedido de Desculpas', text: 'Lamentamos imenso que a tua experiência não tenha sido a melhor. A tua opinião é fundamental para corrigirmos as falhas. Poderias detalhar mais o problema?' },
            { label: 'Foco em Melhoria', text: 'Obrigado pelo feedback honesto. Já passámos as tuas observações para a nossa equipa técnica para análise imediata.' }
        ];
    } else if (rating === 3) {
        templates = [
            { label: 'Onde melhorar?', text: 'Obrigado pela avaliação. Gostávamos de saber o que falta para chegarmos às 5 estrelas na tua opinião.' },
            { label: 'Agradecimento Neutro', text: 'Agradecemos o teu feedback. Estamos a trabalhar diariamente para elevar a qualidade da KALIYE.' }
        ];
    } else {
        templates = [
            { label: 'Gratidão Elite', text: 'Uau! Ficamos radiantes com o teu apoio. É para utilizadores como tu que trabalhamos todos os dias!' },
            { label: 'Continuar a Evoluir', text: 'Muito obrigado pelas 5 estrelas! Continuaremos a inovar para te trazer a melhor experiência possível.' }
        ];
    }

    templates.forEach(t => {
        const chip = document.createElement('div');
        chip.style = 'padding: 6px 12px; background: rgba(247, 148, 29, 0.1); color: #f7941d; border: 1px solid rgba(247, 148, 29, 0.2); border-radius: 20px; font-size: 0.7rem; font-weight: 700; cursor: pointer; transition: 0.2s;';
        chip.innerText = t.label;
        chip.onclick = () => {
            document.getElementById('respText').value = t.text;
        };
        chip.onmouseover = () => chip.style.background = 'rgba(247, 148, 29, 0.2)';
        chip.onmouseout = () => chip.style.background = 'rgba(247, 148, 29, 0.1)';
        container.appendChild(chip);
    });
}

function insertMeetingInvite() {
    const inviteText = "\n\nGostávamos de te convidar para uma breve reunião de 10 minutos para ouvirmos as tuas sugestões em direto. Podes agendar aqui: [LINK_CALENDLY_OU_SISTEMA]";
    document.getElementById('respText').value += inviteText;
}

function closeResponseModal() {
    document.getElementById('responseModal').style.display = 'none';
}

async function sendResponse() {
    const id = document.getElementById('respEvalId').value;
    const text = document.getElementById('respText').value.trim();
    const btn = document.getElementById('btnSendResp');

    if (!text) return;

    btn.disabled = true;
    btn.innerText = 'A enviar...';

    const fd = new FormData();
    fd.append('evaluation_id', id);
    fd.append('response', text);

    try {
        const res = await fetch('../../interface_programacao/admin/respond_evaluation.php', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, background: '#1e293b', color: '#fff' }).then(() => {
                location.reload();
            });
        } else {
            btn.disabled = false;
            btn.innerText = 'Enviar Resposta';
            Swal.fire({ icon: 'error', title: 'Erro', text: data.message, background: '#1e293b', color: '#fff' });
        }
    } catch(err) {
        console.error(err);
        btn.disabled = false;
        btn.innerText = 'Enviar Resposta';
    }
}

// --- Gráfico de Tendência ---
const trendCtx = document.getElementById('feedbackTrendChart')?.getContext('2d');
if (trendCtx) {
    const trendData = <?php echo json_encode($trend_data); ?>;
    const labels = trendData.map(d => {
        const date = new Date(d.date);
        return date.toLocaleDateString('pt-PT', { day: '2-digit', month: 'short' });
    });
    const values = trendData.map(d => parseFloat(d.avg_rating).toFixed(2));

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Média Diária',
                data: values,
                borderColor: '#f7941d',
                backgroundColor: 'rgba(247, 148, 29, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#f7941d',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: 'rgba(255,255,255,0.5)' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: 'rgba(255,255,255,0.5)' }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1
                }
            }
        }
    });
}
</script>

<?php require_once '../../inclusoes/rodape.php'; ?>

