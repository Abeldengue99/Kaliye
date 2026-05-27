<?php echo "<!-- SIDEBAR COMPONENT LOADED -->"; ?>
<?php
/**
 * inclusoes/components/dashboard_sidebar_widgets.php
 * Widgets em tempo real baseados no perfil do utilizador
 */
global $db, $header_user_id, $user_role, $base_url;

$_role = $user_role ?? 'student';
$_uid  = $header_user_id;

// Verificamos o status de mentoria para refinar os widgets
$_m_status = $_SESSION['mentor_status'] ?? 'unsubmitted';
$_is_mentor_approved = ($_role === 'mentor' && $_m_status === 'approved');

// ─────────────────────────────────────────────
// DADOS EM TEMPO REAL PARA CADA PERFIL
// ─────────────────────────────────────────────
$sessions = [];
$tasks    = [];
$invest_data = [];
$featured_evals = []; // Inicialização crucial


try {
    if (($_role === 'student' || $_role === 'univ_student' || $_role === 'high_student') || ($_role === 'mentor' && !$_is_mentor_approved)) {
        // SESSÕES AGENDADAS pelo mentor do estudante
        $s = $db->prepare("
            SELECT ms.start_time, ms.end_time, ms.status, ms.meeting_link,
                   u.full_name AS mentor_name
            FROM mentorship_slots ms
            JOIN users u ON ms.mentor_id = u.user_id
            WHERE ms.participant_id = ?
              AND ms.start_time >= NOW()
            ORDER BY ms.start_time ASC
            LIMIT 5
        ");
        $s->execute([$_uid]); $sessions = $s->fetchAll(PDO::FETCH_ASSOC);

        // TAREFAS atribuídas pelo mentor
        $t = $db->prepare("
            SELECT mt.task_name, mt.deadline, mt.status, u.full_name AS mentor_name
            FROM mentorship_tasks mt
            JOIN users u ON mt.mentor_id = u.user_id
            WHERE mt.mentee_id = ? AND mt.status = 'pending'
            ORDER BY mt.deadline ASC
            LIMIT 5
        ");
        $t->execute([$_uid]); $tasks = $t->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($_is_mentor_approved) {
        // SESSÕES que o mentor tem agendadas (com participantes)
        $s = $db->prepare("
            SELECT ms.start_time, ms.end_time, ms.status, ms.meeting_link,
                   u.full_name AS participant_name
            FROM mentorship_slots ms
            LEFT JOIN users u ON ms.participant_id = u.user_id
            WHERE ms.mentor_id = ?
              AND ms.start_time >= NOW()
            ORDER BY ms.start_time ASC
            LIMIT 5
        ");
        $s->execute([$_uid]); $sessions = $s->fetchAll(PDO::FETCH_ASSOC);

        // TAREFAS que o mentor atribuiu
        $t = $db->prepare("
            SELECT mt.task_name, mt.deadline, mt.status, u.full_name AS student_name
            FROM mentorship_tasks mt
            JOIN users u ON mt.mentee_id = u.user_id
            WHERE mt.mentor_id = ?
            ORDER BY mt.deadline ASC
            LIMIT 5
        ");
        $t->execute([$_uid]); $tasks = $t->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($_role === 'investor') {
        // INVESTIMENTOS ACTIVOS
        $i = $db->prepare("
            SELECT pi.amount, pi.status, pi.created_at,
                   p.title AS project_title, p.category
            FROM project_investments pi
            JOIN projects p ON pi.project_id = p.project_id
            WHERE pi.investor_id = ?
            ORDER BY pi.created_at DESC
            LIMIT 4
        ");
        $i->execute([$_uid]); $invest_data = $i->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { 
    echo "<!-- DASHBOARD DATA ERROR: " . htmlspecialchars($e->getMessage()) . " -->"; 
}

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────
function formatSlotTime($datetime) {
    if (!$datetime) return '--:--';
    return date('H:i', strtotime($datetime));
}
function formatDeadline($datetime) {
    if (!$datetime) return 'Sem prazo';
    $diff = (new DateTime($datetime))->diff(new DateTime());
    if ($diff->days == 0) return 'Hoje';
    if ($diff->days == 1) return 'Amanhã';
    return date('d/m', strtotime($datetime));
}
function statusColor($status) {
    switch($status) {
        case 'confirmed':
        case 'completed':
            return '#10b981';
        case 'pending':
            return '#f59e0b';
        default:
            return 'var(--surface-30)';
    }
}

// ─────────────────────────────────────────────
// WIDGET STYLES (inline)
// ─────────────────────────────────────────────
$card = 'background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 24px; padding: 2.5rem; backdrop-filter: blur(12px);';
$title_style = 'font-size: 0.75rem; font-weight: 900; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 2.5px; margin: 0;';
$icon_box = 'width: 38px; height: 38px; border-radius: 12px; background: rgba(247,148,29,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;';
$empty_style = 'text-align: center; padding: 2rem 0; color: var(--surface-15); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;';
?>

<div style="display: flex; flex-direction: column; gap: 2.5rem;">
    

    
<?php if ($_role === 'mentor' && !$_is_mentor_approved): ?>
    <!-- ── MENTOR PENDENTE: CARD DE ACÇÃO NECESSÁRIA ── -->
    <div style="<?= $card ?> background: linear-gradient(145deg, rgba(247,148,29,0.1) 0%, rgba(15,23,42,0.8) 100%); border-color: rgba(247,148,29,0.2);" data-aos="fade-left" data-aos-delay="200">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="<?= $title_style ?> color: #f7941d;">Estado do Perfil</h3>
            <div style="<?= $icon_box ?> background: rgba(247,148,29,0.2);"><i class="fas fa-hourglass-half" style="color: #f7941d; font-size: 1rem;"></i></div>
        </div>
        
        <p style="color: rgba(255,255,255,0.7); font-size: 0.85rem; line-height: 1.6; margin-bottom: 2rem;">
            O teu perfil de <b>Mentor</b> está em fase de ativação. Para começares a orientar projectos e receberes pedidos, deves concluir os seguintes passos:
        </p>

        <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fas <?= $_SESSION['is_verified'] ? 'fa-check-circle' : 'fa-circle' ?>" style="color: <?= $_SESSION['is_verified'] ? '#10b981' : 'rgba(255,255,255,0.1)' ?>; font-size: 1.1rem;"></i>
                <span style="font-size: 0.8rem; color: #fff;">Verificação de Identidade (BI)</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fas <?= $_m_status === 'pending' ? 'fa-check-circle' : ($_m_status === 'approved' ? 'fa-check-circle' : 'fa-circle') ?>" style="color: <?= $_m_status !== 'unsubmitted' ? '#10b981' : 'rgba(255,255,255,0.1)' ?>; font-size: 1.1rem;"></i>
                <span style="font-size: 0.8rem; color: #fff;">Candidatura a Mentor Elite</span>
            </div>
        </div>

        <button onclick="openMentorAppModal()" 
                style="width: 100%; padding: 1rem; background: #f7941d; color: #fff; border: none; border-radius: 14px; font-weight: 800; font-size: 0.75rem; letter-spacing: 1px; cursor: pointer; transition: 0.3s; text-transform: uppercase;"
                onmouseover="this.style.background='#ff9d2e'; this.style.transform='translateY(-2px)'"
                onmouseout="this.style.background='#f7941d'; this.style.transform='translateY(0)'">
            CONCLUIR PERFIL <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
        </button>
    </div>
<?php endif; ?>

<?php if ($_role === 'investor'): ?>

    <!-- ── INVESTIDOR: CARD DE INVESTIMENTOS ACTIVOS ── -->
    <div style="<?= $card ?>" data-aos="fade-left" data-aos-delay="300">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--surface-5);">
            <h3 style="<?= $title_style ?>">Meus Investimentos</h3>
            <div style="<?= $icon_box ?>"><i class="fas fa-chart-line" style="color: var(--elite-orange); font-size: 0.9rem;"></i></div>
        </div>

        <?php if (empty($invest_data)): ?>
            <div style="<?= $empty_style ?>"><i class="fas fa-inbox" style="font-size: 1.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>Nenhum investimento registado</div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.4rem;">
                <?php foreach ($invest_data as $inv): ?>
                <div style="display: flex; gap: 1rem; align-items: flex-start; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 14px; border: 1px solid rgba(255,255,255,0.04);">
                    <div style="flex: 1;">
                        <h5 style="margin: 0; font-size: 0.9rem; font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;"><?= htmlspecialchars($inv['project_title']) ?></h5>
                        <p style="margin: 5px 0 0; font-size: 0.65rem; color: var(--surface-30); text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars($inv['category'] ?? 'Projecto') ?></p>
                    </div>
                    <div style="text-align: right; flex-shrink: 0;">
                        <div style="font-size: 0.85rem; font-weight: 800; color: var(--elite-orange);"><?= number_format((float)$inv['amount'], 0, ',', '.') ?> Kz</div>
                        <div style="margin-top: 4px; font-size: 0.6rem; font-weight: 700; color: <?= statusColor($inv['status']) ?>; text-transform: uppercase; letter-spacing: 1px;"><?= $inv['status'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="<?= $base_url ?>paginas/investor/investor_dashboard.php" style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--surface-5); color: var(--elite-orange); text-decoration: none; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8; transition: 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                Ver Dashboard de Investidor <i class="fas fa-arrow-right" style="font-size: 0.7rem;"></i>
            </a>
        <?php endif; ?>
    </div>

<?php else: ?>

    <!-- ── ESTUDANTE / MENTOR: SESSÕES AGENDADAS ── -->
    <div style="<?= $card ?>" data-aos="fade-left" data-aos-delay="400">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--surface-5);">
            <h3 style="<?= $title_style ?>">Próximas Sessões</h3>
            <div style="<?= $icon_box ?>"><i class="fas fa-video" style="color: var(--elite-orange); font-size: 0.9rem;"></i></div>
        </div>

        <?php if (empty($sessions)): ?>
            <div style="<?= $empty_style ?>"><i class="fas fa-calendar-times" style="font-size: 1.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>Nenhuma sessão agendada</div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php foreach ($sessions as $sess): ?>
                <?php
                    $other = $_role === 'mentor'
                        ? ($sess['participant_name'] ?? 'Sem participante')
                        : ($sess['mentor_name'] ?? 'Mentor');
                    $has_link = !empty($sess['meeting_link']);
                ?>
                <div style="display: flex; gap: 1.2rem; align-items: flex-start;">
                    <div style="text-align: center; flex-shrink: 0;">
                        <div style="font-size: 0.8rem; font-weight: 900; color: var(--elite-orange); line-height: 1;"><?= formatSlotTime($sess['start_time']) ?></div>
                        <div style="font-size: 0.55rem; font-weight: 700; color: var(--surface-20); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px;"><?= date('d/m', strtotime($sess['start_time'])) ?></div>
                    </div>
                    <div style="flex: 1; border-left: 1px solid rgba(255,255,255,0.06); padding-left: 1rem;">
                        <h5 style="margin: 0; font-size: 0.88rem; font-weight: 700; color: #fff;">Sessão de Mentoria</h5>
                        <p style="margin: 4px 0 0; font-size: 0.65rem; color: var(--surface-30); text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars($other) ?></p>
                        <?php if ($has_link): ?>
                        <a href="<?= htmlspecialchars($sess['meeting_link']) ?>" target="_blank"
                           style="display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 0.6rem; font-weight: 800; color: #10b981; text-decoration: none; text-transform: uppercase; letter-spacing: 1px;">
                            <i class="fas fa-external-link-alt"></i> Entrar na Reunião
                        </a>
                        <?php endif; ?>
                    </div>
                    <div style="width: 8px; height: 8px; border-radius: 50%; background: <?= statusColor($sess['status']) ?>; flex-shrink: 0; margin-top: 5px;"></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="<?= $base_url ?>paginas/social/mentorship.php" style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--surface-5); color: var(--elite-orange); text-decoration: none; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8; transition: 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
            Ver Mentorias <i class="fas fa-arrow-right" style="font-size: 0.7rem;"></i>
        </a>
    </div>

    <!-- ── ESTUDANTE / MENTOR: TAREFAS ── -->
    <div style="<?= $card ?>" data-aos="fade-left" data-aos-delay="500">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--surface-5);">
            <h3 style="<?= $title_style ?>"><?= $_role === 'mentor' ? 'Tarefas Atribuídas' : 'Minhas Tarefas' ?></h3>
            <div style="<?= $icon_box ?>"><i class="fas fa-tasks" style="color: var(--elite-orange); font-size: 0.9rem;"></i></div>
        </div>

        <?php if (empty($tasks)): ?>
            <div style="<?= $empty_style ?>"><i class="fas fa-check-double" style="font-size: 1.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i><?= $_role === 'mentor' ? 'Nenhuma tarefa atribuída' : 'Sem tarefas pendentes' ?></div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                <?php foreach ($tasks as $task): ?>
                <?php
                    $person = $_role === 'mentor'
                        ? ($task['student_name'] ?? 'Estudante')
                        : ($task['mentor_name'] ?? 'Mentor');
                    $is_late = !empty($task['deadline']) && strtotime($task['deadline']) < time();
                    $deadline_label = formatDeadline($task['deadline']);
                ?>
                <div style="display: flex; gap: 1rem; align-items: flex-start; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 14px; border: 1px solid rgba(255,255,255,0.04); <?= $is_late ? 'border-color: rgba(239,68,68,0.2); background: rgba(239,68,68,0.03);' : '' ?>">
                    <div style="width: 32px; height: 32px; border-radius: 10px; background: <?= $is_late ? 'rgba(239,68,68,0.1)' : 'rgba(247,148,29,0.08)' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                        <i class="fas fa-<?= $task['status'] === 'completed' ? 'check' : 'clock' ?>" style="font-size: 0.7rem; color: <?= $task['status'] === 'completed' ? '#10b981' : ($is_late ? '#ef4444' : 'var(--elite-orange)') ?>;"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h5 style="margin: 0; font-size: 0.85rem; font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($task['task_name']) ?></h5>
                        <p style="margin: 4px 0 0; font-size: 0.62rem; color: var(--surface-30); text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars($person) ?></p>
                    </div>
                    <div style="flex-shrink: 0; text-align: right;">
                        <span style="font-size: 0.62rem; font-weight: 800; color: <?= $is_late ? '#ef4444' : 'var(--surface-25)' ?>; text-transform: uppercase; letter-spacing: 0.5px;"><?= $deadline_label ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

    <!-- ── ATALHOS RÁPIDOS (Modern Grid) ── -->
    <div style="<?= $card ?> padding: 1.5rem;" data-aos="fade-left" data-aos-delay="600">
        <h3 style="<?= $title_style ?> margin-bottom: 1.5rem;">Atalhos Rápidos</h3>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <?php
            $q_links = [
                ['name' => 'Novo Projeto', 'url' => 'javascript:void(0)', 'icon' => 'fa-plus', 'color' => '#10b981', 'onclick' => 'if(typeof openPostModal === "function") openPostModal();'],
                ['name' => 'Carteira', 'url' => $base_url . 'paginas/conta/wallet.php', 'icon' => 'fa-wallet', 'color' => '#f7941d', 'onclick' => ''],
                ['name' => 'Mensagens', 'url' => $base_url . 'paginas/social/messages.php', 'icon' => 'fa-comment-alt', 'color' => '#6366f1', 'onclick' => ''],
                ['name' => 'Definições', 'url' => $base_url . 'paginas/social/profile.php', 'icon' => 'fa-cog', 'color' => '#94a3b8', 'onclick' => ''],
            ];

            if ($_role === 'investor') {
                $q_links[0] = ['name' => 'Investir', 'url' => $base_url . 'paginas/explorar/explore_projects.php', 'icon' => 'fa-chart-pie', 'color' => '#10b981', 'onclick' => ''];
            }
            ?>

            <?php foreach ($q_links as $ql): ?>
            <a href="<?= $ql['url'] ?>" 
               onclick="<?= $ql['onclick'] ?>"
               class="shine-on-hover"
               style="text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.2rem 0.5rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; transition: 0.3s; gap: 8px; cursor: pointer;"
               onmouseover="this.style.background='rgba(255,255,255,0.06)'; this.style.transform='translateY(-3px)'; this.style.borderColor='<?= $ql['color'] ?>44'"
               onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.transform='translateY(0)'; this.style.borderColor='rgba(255,255,255,0.05)'">
                <div style="width: 32px; height: 32px; border-radius: 10px; background: <?= $ql['color'] ?>15; display: flex; align-items: center; justify-content: center;">
                    <i class="fas <?= $ql['icon'] ?>" style="color: <?= $ql['color'] ?>; font-size: 0.85rem;"></i>
                </div>
                <span style="font-size: 0.65rem; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: 0.5px;"><?= $ql['name'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>
