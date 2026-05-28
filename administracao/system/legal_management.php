<?php
// admin/legal_management.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('legal')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch all agreements
$query = "SELECT la.*, u.full_name as user_name, u.user_type, p.title as project_title 
          FROM legal_agreements la 
          JOIN users u ON la.user_id = u.user_id 
          LEFT JOIN projects p ON la.project_id = p.project_id 
          ORDER BY la.created_at DESC";
$agreements = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch users for new agreement selection
$users_query = "SELECT user_id, full_name, user_type FROM users WHERE user_type IN ('univ_student', 'high_student', 'investor', 'mentor') ORDER BY full_name ASC";
$all_users = $db->query($users_query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch projects for selection
$projects_query = "SELECT project_id, title FROM projects ORDER BY title ASC";
$all_projects = $db->query($projects_query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Contratos | KALIYE Admin</title>
    <link rel='icon' type='image/png' href='../../recursos/images/marca/favicon-k-32x32.png'>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .agreement-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: grid;
            grid-template-columns: 1fr 150px 150px 180px;
            gap: 1.5rem;
            align-items: center;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-align: center;
        }
    </style>
</head>
<body style="display: flex;">
    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
            <div style="background: rgba(255, 255, 255, 0.03); padding: 0.75rem 1.5rem; border-radius: 12px; border: 1px solid var(--glass-border);">
                <span id="liveIndicator" style="display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981; animation: pulse 2s infinite; margin-right: 8px;"></span>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Total de Acordos:</span>
                <strong style="color: var(--accent-orange); margin-left: 0.5rem;"><?php echo count($agreements); ?></strong>
            </div>
        </div>

        <div class="glass" style="padding: 1.5rem; border-radius: 16px;">
            <div style="display: grid; grid-template-columns: 1fr 150px 150px 180px; gap: 1.5rem; padding: 0 1.5rem 1rem; border-bottom: 1px solid var(--glass-border); color: var(--text-secondary); font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">
                <div>Documento / Titular</div>
                <div>Tipo</div>
                <div>Estado</div>
                <div>Ações</div>
            </div>

            <div style="margin-top: 1rem;">
                <?php if (empty($agreements)): ?>
                    <p style="text-align:center; padding: 2rem; color: var(--text-secondary);">Sem contratos registados no momento.</p>
                <?php else: ?>
                    <?php foreach($agreements as $a): ?>
                        <div class="agreement-card">
                            <div>
                                <div style="font-weight: 700;"><?php echo $a['project_title'] ? 'Proj: '.$a['project_title'] : 'Acordo Geral'; ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">Titular: <b><?php echo htmlspecialchars($a['user_name']); ?></b></div>
                            </div>
                            <div style="font-size: 0.85rem; font-weight: 600;">
                                <?php 
                                    $labels = [
                                        'student_terms' => 'Estudante',
                                        'investor_terms' => 'Investidor',
                                        'mentor_terms' => 'Mentor'
                                    ];
                                    echo $labels[$a['agreement_type']] ?? $a['agreement_type'];
                                ?>
                            </div>
                            <div>
                                <?php 
                                    if ($a['status'] == 'pending') echo '<span class="status-badge" style="background: var(--accent-orange); color: white;">PENDENTE</span>';
                                    elseif ($a['status'] == 'signed') echo '<span class="status-badge" style="background: #10b981; color: white;">ASSINADO</span>';
                                    else echo '<span class="status-badge" style="background: var(--danger); color: white;">REJEITADO</span>';
                                ?>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <?php if($a['admin_signed_file']): ?>
                                    <a href="../<?php echo $a['admin_signed_file']; ?>" target="_blank" class="btn-primary" style="padding: 5px 10px; font-size: 0.7rem; background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid #3b82f6;">
                                        <i class="fas fa-signature"></i> Admin
                                    </a>
                                <?php endif; ?>
                                <?php if($a['user_signed_file']): ?>
                                    <a href="../<?php echo $a['user_signed_file']; ?>" target="_blank" class="btn-primary" style="padding: 5px 10px; font-size: 0.7rem; background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid #10b981;">
                                        <i class="fas fa-file-download"></i> User
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        const contractTemplates = {
            student_terms: `TERMOS DE INCUBAÇÃO E ACELERAÇÃO - KALIYE\n\n1. OBJETO: O Estudante compromete-se a participar do programa de aceleração KALIYE.\n2. PROPRIEDADE INTELECTUAL: Toda a PI gerada pertence ao Estudante, salvo acordo e contrato específico com investidores.\n3. COMPROMISSO: O estudante deve cumprir pelo menos 80% das milestones definidas pelo mentor.\n4. TRANSPARÊNCIA: Uso obrigatório da plataforma para registro de progresso.\n5. CONFIDENCIALIDADE: O estudante não deve divulgar segredos comerciais da plataforma KALIYE.`,
            investor_terms: `ACORDO DE INVESTIMENTO E APORTE DE CAPITAL\n\n1. APORTE: O Investidor confirma o investimento no projeto selecionado através da plataforma KALIYE.\n2. DISTRIBUIÇÃO: O capital será libertado conforme as milestones validadas pelo mentor.\n3. TAXA DE PLATAFORMA: A KALIYE retém [X]% do valor para custos operacionais e governança.\n4. RISCO: O investidor declara ciência de que o investimento em startups/projectos em estágio inicial envolve riscos.\n5. GOVERNANÇA: O investidor tem direito a relatórios mensais de progresso via dashboard.`,
            mentor_social: `COMPROMISSO DE MENTORIA SOCIAL (IMPACTO)\n\n1. O mentor atuará pro-bono para o desenvolvimento do talento angolano.\n2. Foco em validação de MVP e preparação para o mercado.\n3. Mínimo de 1 reunião mensal obrigatória.\n4. Relatório simplificado de desempenho do estudante.`
        };

        function loadTemplate(key) {
            const textarea = document.getElementById('swal-terms');
            if (contractTemplates[key]) {
                textarea.value = contractTemplates[key];
            }
        }

        async function openNewAgreementModal() {
            const { value: formValues } = await Swal.fire({
                title: 'Novo Contrato Profissional',
                html: `
                    <div style="text-align: left;">
                        <label style="font-size: 0.8rem; color: #94a3b8;">Carregar Modelo (Template):</label>
                        <select onchange="loadTemplate(this.value)" class="swal2-input" style="width: 100%; margin: 0.5rem 0 1rem 0; background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; padding: 0.5rem; font-weight: 700;">
                            <option value="">-- Escolha um Modelo Profissional --</option>
                            <option value="student_terms">Modelo: Incubação (Estudante)</option>
                            <option value="investor_terms">Modelo: Investimento (Investidor)</option>
                            <option value="mentor_social">Modelo: Mentoria Social</option>
                        </select>

                        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 1rem 0;"></div>

                        <label style="font-size: 0.8rem; color: #94a3b8;">Destinatário:</label>
                        <select id="swal-user-id" class="swal2-input" style="width: 100%; margin: 0.5rem 0 1rem 0; background: #0f172a; color: #fff; border: 1px solid #334155; padding: 0.8rem;">
                            <option value="">Selecione o Utilizador...</option>
                            <?php foreach($all_users as $u): ?>
                                <option value="<?php echo $u['user_id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> (<?php echo $u['user_type']; ?>)</option>
                            <?php endforeach; ?>
                        </select>

                        <label style="font-size: 0.8rem; color: #94a3b8;"><i class="fas fa-file-signature"></i> Carregar Documento Oficial (PDF/JPG):</label>
                        <input type="file" id="swal-file" class="swal2-input" style="width: 100%; margin: 0.5rem 0; background: #0f172a; color: #fff; border: 1px solid #334155; font-size: 0.8rem;">

                        <label style="font-size: 0.8rem; color: #94a3b8;">Tipo de Acordo:</label>
                        <select id="swal-agreement-type" class="swal2-input" style="width: 100%; margin: 0.5rem 0 1rem 0; background: #0f172a; color: #fff; border: 1px solid #334155; padding: 0.8rem;">
                            <option value="student_terms">Termos de Incubação (Estudante)</option>
                            <option value="investor_terms">Acordo de Investimento (Investidor)</option>
                            <option value="mentor_terms">Termos Específicos (Mentor)</option>
                        </select>

                        <label style="font-size: 0.8rem; color: #94a3b8;">Conteúdo ou Instruções (Opcional):</label>
                        <textarea id="swal-terms" class="swal2-textarea" style="width: 100%; height: 120px; background: #0f172a; color: #fff; border: 1px solid #334155; margin: 0.5rem 0; font-size: 0.8rem; line-height: 1.5;" placeholder="Se deixar vazio e carregar um ficheiro, o sistema usará o ficheiro como contrato principal."></textarea>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Enviar Contrato',
                confirmButtonColor: '#10b981',
                background: '#1e293b',
                color: '#fff',
                width: '640px',
                preConfirm: () => {
                   const userId = document.getElementById('swal-user-id').value;
                   const type = document.getElementById('swal-agreement-type').value;
                   if (!userId || !type) {
                       Swal.showValidationMessage('Selecione o destinatário e o tipo.');
                       return false;
                   }
                   return {
                       user_id: userId,
                       project_id: (document.getElementById('swal-project-id') ? document.getElementById('swal-project-id').value : ''),
                       agreement_type: type,
                       terms: document.getElementById('swal-terms').value,
                       file: document.getElementById('swal-file').files[0]
                   }
                }
            });

            if (formValues) {
                Swal.fire({ title: 'A enviar...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                
                const formData = new FormData();
                formData.append('user_id', formValues.user_id);
                formData.append('project_id', formValues.project_id);
                formData.append('agreement_type', formValues.agreement_type);
                formData.append('contract_terms', formValues.terms);
                if (formValues.file) formData.append('admin_signed_file', formValues.file);

                fetch('../../interface_programacao/system/send_legal_agreement.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('Enviado!', 'O utilizador foi notificado para assinar o documento.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.message, 'error');
                    }
                });
            }
        }
    </script>
</body>
</html>





