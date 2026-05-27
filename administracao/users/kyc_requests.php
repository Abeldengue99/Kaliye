<?php
/**
 * admin/kyc_requests.php - Identity Verification
 * Refactored into a component-based structure.
 */
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('kyc')) {
    header("Location: index.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch pending requests (Priority for Mentors/Investors)
$requests = $db->query("SELECT * FROM users 
                        WHERE verification_status = 'pending' 
                        OR mentorship_status = 'pending' 
                        OR investor_status = 'pending' 
                        ORDER BY submitted_at ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script src="../../recursos/js/admin_ai_engine.js?v=<?= filemtime(__DIR__ . '/../../recursos/js/admin_ai_engine.js') ?>"></script>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">
    
    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Verificações KYC</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Validar identidade dos membros da comunidade.</p>
            </div>
        </header>

        <div class="admin-card-premium">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Utilizador</th>
                            <th>BI / ID</th>
                            <th>Submissão</th>
                            <th style="text-align: right;">Análise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="4" style="padding: 4rem; text-align: center; color: rgba(255,255,255,0.2);">
                                    <i class="fas fa-check-double" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: #10b981; opacity: 0.5;"></i>
                                    <p>Tudo em dia! Nenhuma verificação pendente.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($requests as $r): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php 
                                            $final_pic = $base_url . getUserAvatarUrl($r['user_type'], $r['mentorship_status'] ?? 'unsubmitted');
                                        ?>
                                        <img src="<?= $final_pic ?>" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); object-fit: cover;">
                                        <div>
                                            <div style="font-weight: 700; color: #fff;"><?= htmlspecialchars($r['full_name']) ?></div>
                                            <div style="font-size: 0.75rem; color: rgba(255,255,255,0.4);"><?= htmlspecialchars($r['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #60a5fa;">
                                    <?= htmlspecialchars($r['id_number']) ?>
                                </td>
                                <td style="font-size: 0.85rem; color: rgba(255,255,255,0.5);">
                                    <?= date('d M, Y H:i', strtotime($r['submitted_at'])) ?>
                                </td>
                                <td>
                                    <div style="display: flex; justify-content: flex-end;">
                                        <button onclick='openKYCReview(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8"); ?>)' class="btn-admin btn-admin-primary" style="padding: 0.5rem 1.25rem;">
                                            <i class="fas fa-magnifying-glass"></i> REVISAR
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Review Modal Component -->
    <div id="kycModal" class="admin-modal" style="display: none; position: fixed; inset: 0; background: rgba(5, 10, 21, 0.95); z-index: 3000;">
        <div class="admin-card-premium" style="width: 100%; max-width: 900px; max-height: 94vh; position: relative; margin: auto; display: flex; flex-direction: column; overflow: hidden; padding: 0;">
            <!-- Header Fixo -->
            <header style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(13, 22, 40, 0.5); backdrop-filter: blur(10px); z-index: 10;">
                <div>
                    <h2 id="modalName" style="margin: 0; color: #fff; font-size: 1.5rem;">Nome do Utilizador</h2>
                    <p id="modalId" style="color: #60a5fa; margin: 4px 0 0 0; font-family: monospace; font-size: 0.85rem;"></p>
                </div>
                <button onclick="closeKYCModal()" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: #fff; width: 40px; height: 40px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s;" onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                    <i class="fas fa-times"></i>
                </button>
            </header>

            <!-- Body Scrollable -->
            <div style="flex: 1; overflow-y: auto; padding: 2rem;">

            <div class="kyc-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div style="position: relative;">
                    <p style="font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.3); text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 1px;">Documento (Frente)</p>
                    <img id="imgFront" src="" style="width: 100%; height: 180px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); cursor: zoom-in;" onclick="window.open(this.src)">
                </div>
                <div style="position: relative;">
                    <p style="font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.3); text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 1px;">Documento (Verso)</p>
                    <img id="imgBack" src="" style="width: 100%; height: 180px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); cursor: zoom-in;" onclick="window.open(this.src)">
                </div>
                <div style="position: relative;">
                    <p style="font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.3); text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 1px;">Selfie de Verificação</p>
                    <img id="imgSelfie" src="" style="width: 100%; height: 180px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(255,148,29,0.3); cursor: zoom-in;" onclick="window.open(this.src)">
                </div>
            </div>

            <!-- DADOS DE PERFIL (Dinâmicos) -->
            <div id="roleDataBox" style="margin-bottom: 2rem; padding: 1.5rem; border-radius: 16px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                <div id="mentorData" style="display: none;">
                    <h4 id="mTitle" style="color: var(--elite-orange); margin: 0 0 10px 0;"><i class="fas fa-briefcase"></i> DADOS DE MENTORIA</h4>
                    <div style="display: flex; gap: 20px; font-size: 0.85rem; color: #fff;">
                        <div id="divSpec"><strong>Especialidade:</strong> <span id="mSpec">---</span></div>
                        <div id="divExp"><strong>Exp:</strong> <span id="mExp">---</span> anos</div>
                        <div><strong>LinkedIn:</strong> <a id="mLink" href="#" target="_blank" style="color: #60a5fa;">Ver Perfil</a></div>
                        <div><strong>Curriculum:</strong> <a id="mCV" href="#" target="_blank" style="color: #10b981;">Abrir PDF</a></div>
                        <div id="divTrans" style="display:none;"><strong>Histórico:</strong> <a id="mTrans" href="#" target="_blank" style="color: #f7941d;">Notas.pdf</a></div>
                    </div>
                </div>
                <div id="investorData" style="display: none;">
                    <h4 style="color: #10b981; margin: 0 0 10px 0;"><i class="fas fa-hand-holding-dollar"></i> ACREDITAÇÃO DE INVESTIDOR</h4>
                    <div style="display: flex; gap: 20px; font-size: 0.85rem; color: #fff;">
                        <div><strong>Rendimento:</strong> <span id="iIncome">---</span></div>
                        <div><strong>Origem:</strong> <span id="iSource">---</span></div>
                    </div>
                </div>
            </div>

            <!-- IA Result Section (Auditório) -->
            <div id="aiResultBox" style="display: none; margin-bottom: 2rem; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 16px; padding: 1.5rem;"></div>

            </div> <!-- Fim do Body Scrollable -->

            <!-- Footer Fixo -->
            <footer style="display: flex; gap: 1rem; border-top: 1px solid rgba(255,255,255,0.05); padding: 1.5rem 2.5rem; background: rgba(13, 22, 40, 0.8); z-index: 10;">
                <button onclick="runKYCIA()" class="btn-admin" style="flex: 1; background: rgba(139, 92, 246, 0.1); color: #a78bfa; border-color: rgba(139, 92, 246, 0.2);"><i class="fas fa-brain"></i> Auditoria IA</button>
                <button onclick="processKYC('approve')" class="btn-admin" style="flex: 1; background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: rgba(16, 185, 129, 0.2);"><i class="fas fa-check-circle"></i> Aprovar</button>
                <button onclick="rejectKYC()" class="btn-admin" style="flex: 1; background: rgba(244, 63, 94, 0.1); color: #f43f5e; border-color: rgba(244, 63, 94, 0.2);"><i class="fas fa-ban"></i> Rejeitar</button>
            </footer>
        </div>
    </div>

    <style>
    .admin-modal {
        position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 2000;
        display: flex; align-items: center; justify-content: center; padding: 2rem;
    }
    .btn-close { background: none; border: none; color: #94a3b8; font-size: 2rem; cursor: pointer; }
    .btn-close:hover { color: white; }
    .ai-btn:hover { background: #7c3aed !important; }
    </style>

    <script>
    let activeKYC = null;
    let aiFindings = [];

    function openKYCReview(user) {
        activeKYC = user;
        document.getElementById('modalName').innerText = user.full_name;
        document.getElementById('modalId').innerText = 'Nº Documento: ' + user.id_number;
        document.getElementById('imgFront').src = '../../' + user.bi_front_path;
        document.getElementById('imgBack').src = '../../' + (user.bi_back_path || user.bi_front_path);
        document.getElementById('imgSelfie').src = '../../' + user.selfie_path;
        
        // Dados de Perfil
        const mentorBox = document.getElementById('mentorData');
        const investorBox = document.getElementById('investorData');
        mentorBox.style.display = 'none';
        investorBox.style.display = 'none';

        if (user.user_type === 'mentor') {
            mentorBox.style.display = 'block';
            document.getElementById('mTitle').innerHTML = '<i class="fas fa-briefcase"></i> DADOS DE MENTORIA PROFISSIONAL';
            document.getElementById('divSpec').style.display = 'block';
            document.getElementById('divExp').style.display = 'block';
            document.getElementById('divTrans').style.display = 'none';
            document.getElementById('mSpec').innerText = user.specialty || 'Não informado';
            document.getElementById('mExp').innerText = user.experience_years || '0';
            document.getElementById('mLink').href = user.linkedin_url || '#';
            document.getElementById('mCV').href = '../../' + (user.cv_path || '#');
        } else if (user.user_type === 'univ_student' && user.is_peer_mentor == 1) {
            mentorBox.style.display = 'block';
            document.getElementById('mTitle').innerHTML = '<i class="fas fa-graduation-cap"></i> CANDIDATURA A PEER MENTOR';
            document.getElementById('divSpec').style.display = 'none';
            document.getElementById('divExp').style.display = 'none';
            document.getElementById('divTrans').style.display = 'block';
            document.getElementById('mLink').href = user.linkedin_url || '#';
            document.getElementById('mCV').href = '../../' + (user.cv_path || '#');
            document.getElementById('mTrans').href = '../../' + (user.academic_transcript_path || '#');
        } else if (user.user_type === 'investor') {
            investorBox.style.display = 'block';
            document.getElementById('iIncome').innerText = user.annual_income || '---';
            document.getElementById('iSource').innerText = user.source_of_funds || '---';
        }

        const box = document.getElementById('aiResultBox');
        if(box) box.style.display = 'none';
        document.getElementById('kycModal').style.display = 'flex';
    }

    function closeKYCModal() {
        document.getElementById('kycModal').style.display = 'none';
    }

    async function runKYCIA() {
        const box = document.getElementById('aiResultBox');
        box.style.display = 'block';
        box.innerHTML = '<div style="color: #8b5cf6;"><i class="fas fa-circle-notch fa-spin"></i> Auditando biometricamente...</div>';

        try {
            const result = await analyzeKYCLogic(
                '../../' + activeKYC.bi_front_path,
                '../../' + activeKYC.bi_back_path,
                '../../' + activeKYC.selfie_path,
                activeKYC.full_name,
                activeKYC.id_number
            );
            
            aiFindings = result.warnings;
            
            box.innerHTML = `
                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 1rem 0; color: #a78bfa;">Relatório IA KALIYE</h4>
                        <div style="font-size: 0.9rem;"><strong>Confiança:</strong> ${result.confidence}%</div>
                        <div style="height: 6px; background: rgba(0,0,0,0.2); border-radius: 3px; margin: 10px 0; overflow: hidden;">
                            <div style="width: ${result.confidence}%; height: 100%; background: #a78bfa;"></div>
                        </div>
                    </div>
                    <div style="flex: 2; border-left: 1px solid rgba(255,255,255,0.1); padding-left: 20px;">
                        <div style="color: #10b981; font-size: 0.8rem; margin-bottom: 5px;"><i class="fas fa-check"></i> ${result.matches.join(', ')}</div>
                        <div style="color: #ef4444; font-size: 0.8rem;"><i class="fas fa-exclamation-triangle"></i> ${result.warnings.length ? result.warnings.join(', ') : 'Nenhum risco detetado.'}</div>
                    </div>
                </div>
            `;
        } catch(e) {
            box.innerHTML = `<div style="color: #ef4444;">Erro na análise: ${e.message}</div>`;
        }
    }

    function processKYC(status, notes = '') {
        const footer = document.querySelector('#kycModal footer');
        const originalFooter = footer.innerHTML;
        footer.innerHTML = `<div style="flex:1;text-align:center;color:#a78bfa;"><i class="fas fa-circle-notch fa-spin"></i> PROCESSANDO E NOTIFICANDO UTILIZADOR...</div>`;

        const formData = new FormData();
        formData.append('user_id', activeKYC.user_id);
        formData.append('action', status);
        formData.append('notes', notes);

        fetch('../../interface_programacao/admin/admin_process_kyc.php', { method: 'POST', body: formData })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            if(data.success) {
                Swal.fire({ icon: 'success', title: 'Sucesso!', text: data.message, background: '#0d1628', color: '#fff' }).then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.message, background: '#0d1628', color: '#fff' });
                footer.innerHTML = originalFooter;
            }
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'Falha de Comunicação', text: 'Ocorreu um erro no servidor.', background: '#0d1628', color: '#fff' });
            footer.innerHTML = originalFooter;
        });
    }

    function rejectKYC() {
        const defaultNotes = aiFindings.length ? 'Inconsistências detectadas:\n- ' + aiFindings.join('\n- ') : '';
        Swal.fire({
            title: 'Rejeitar KYC',
            input: 'textarea',
            inputLabel: 'Motivo para o utilizador:',
            inputValue: defaultNotes,
            showCancelButton: true,
            confirmButtonColor: '#ef4444'
        }).then(res => {
            if(res.isConfirmed) processKYC('reject', res.value);
        });
    }
    </script>
</body>
</html>




