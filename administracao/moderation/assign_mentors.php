<?php
// admin/assign_mentors.php
session_start();
$admin_base = '../';
require_once '../../configuracoes/base_dados.php';

// Auth check
require_once '../../inclusoes/auth_check.php';
if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

if (!hasPermission('mentor_assignment')) {
    header("Location: index.php"); 
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch projects pending mentor assignment
// We filter by mentorship_status = 'pending_assignment' OR (mentorship_status = 'none' AND is_public = 1 AND budget_collected > 0)
// For now, let's rely on the status update we added to admin_process_investment.php
$query = "SELECT p.*, u.full_name as owner_name, u.email as owner_email,
                 (SELECT amount FROM project_investments WHERE project_id = p.project_id AND status = 'approved' ORDER BY amount DESC LIMIT 1) as investment_amount
          FROM projects p 
          JOIN users u ON p.owner_id = u.user_id 
          WHERE p.mentorship_status = 'pending_assignment' 
          ORDER BY p.created_at DESC";
$pending_projects = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch all available mentors
$mentors_query = "SELECT user_id, full_name, user_type, bio, specialty as skills, 
                         (SELECT AVG(rating) FROM user_reviews WHERE mentor_id = users.user_id) as rating_avg,
                         (SELECT COUNT(*) FROM mentorship_contracts WHERE mentor_id = users.user_id AND status = 'active') as active_mentees
                  FROM users 
                  WHERE user_type = 'mentor' OR (user_type = 'univ_student' AND mentorship_status = 'approved')";
$all_mentors = $db->query($mentors_query)->fetchAll(PDO::FETCH_ASSOC);

// Helper function to calculate robust match score (Deterministic)
function calculateMatch($project, $mentor) {
    $score = 0;
    
    // 1. Exact Category Match (40 pts)
    $projCategory = strtolower($project['category'] ?? '');
    $mentorSpecialty = strtolower($mentor['skills'] ?? '');
    $mentorBio = strtolower($mentor['bio'] ?? '');
    
    if (!empty($projCategory)) {
        if (stripos($mentorSpecialty, $projCategory) !== false) {
            $score += 40;
        } elseif (stripos($mentorBio, $projCategory) !== false) {
            $score += 25;
        }
    }
    
    // 2. Keyword matching between Project and Mentor (Up to 30 pts)
    $keywords = explode(' ', $project['title'] . ' ' . ($project['category'] ?? ''));
    $matchCount = 0;
    foreach($keywords as $word) {
        if(strlen($word) < 4) continue;
        if(stripos($mentorSpecialty, $word) !== false || stripos($mentorBio, $word) !== false) {
            $matchCount++;
        }
    }
    $score += min(30, $matchCount * 5);
    
    // 3. Rating weight (Up to 20 pts)
    $rating = $mentor['rating_avg'] ?? 0;
    $score += ($rating * 4);
    
    // 4. Availability weight (10 pts if few active mentees)
    $activeMentees = $mentor['active_mentees'] ?? 0;
    if ($activeMentees == 0) $score += 10;
    elseif ($activeMentees < 3) $score += 5;
    
    return min(100, round($score, 2));
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Atribuição de Mentores | KALIYE Admin</title>
    <link rel='icon' type='image/png' href='../../recursos/images/marca/favicon-k-32x32.png'>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .project-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }
        
        .mentor-suggestion-card {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }
        
        .mentor-suggestion-card:hover {
            background: rgba(255,255,255,0.05);
            border-color: var(--accent-orange);
            transform: translateX(5px);
        }
        
        .match-badge {
            background: #10b981;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #1e293b;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            border-radius: 16px;
            padding: 2.5rem;
            position: relative;
            overflow-y: auto;
            border: 1px solid var(--glass-border);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body style="display: flex;">
    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem;"><i class="fas fa-chalkboard-teacher" style="color: var(--accent-orange);"></i> Atribuição de Mentores</h1>
                <p style="color: var(--text-secondary);">Projetos com investimento aprovado aguardando mentor.</p>
            </div>
        </div>

        <?php if(empty($pending_projects)): ?>
            <div class="glass" style="padding: 3rem; text-align: center; color: var(--text-secondary);">
                <i class="fas fa-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 1rem;"></i>
                <p>Nenhum projeto pendente de atribuição no momento.</p>
            </div>
        <?php else: ?>
            <?php foreach($pending_projects as $p): 
                // Calculate suggestions
                $suggestions = [];
                foreach($all_mentors as $m) {
                    $m['match_score'] = calculateMatch($p, $m);
                    $suggestions[] = $m;
                }
                // Sort by score
                usort($suggestions, function($a, $b) { return $b['match_score'] - $a['match_score']; });
                $top_suggestions = array_slice($suggestions, 0, 3);
            ?>
            <div class="project-card">
                <!-- Project Details -->
                <div>
                    <span style="background: var(--accent-orange); color: white; padding: 4px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">INVESTIDO</span>
                    <h2 style="margin: 0.5rem 0;"><?php echo htmlspecialchars($p['title']); ?></h2>
                    <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars(substr($p['description'], 0, 200)) . '...'; ?>
                    </p>
                    
                    <div style="display: flex; gap: 1rem; font-size: 0.8rem; color: #94a3b8; margin-bottom: 1rem;">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($p['owner_name']); ?></span>
                        <span><i class="fas fa-coins"></i> <?php echo number_format($p['investment_amount'] ?? 0, 2); ?> AOA</span>
                    </div>

                    <button onclick='viewProjectDetails(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8"); ?>)' style="background: none; border: none; color: var(--accent-orange); text-decoration: none; font-size: 0.8rem; cursor: pointer; padding: 0;">
                        Ver Projeto Completo <i class="fas fa-search-plus"></i>
                    </button>
                </div>

                <!-- Mentor Selection -->
                <div>
                    <h4 style="margin-top: 0; margin-bottom: 1rem; color: #94a3b8;">Mentores Recomendados (IA)</h4>
                    
                    <?php foreach($top_suggestions as $mentor): ?>
                    <div class="mentor-suggestion-card">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <div style="width: 40px; height: 40px; background: var(--secondary-bg); border-radius: 50%; overflow: hidden;">
                                <img src="../../recursos/images/default_profile.png" style="width: 100%;">
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($mentor['full_name']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                    <?php echo $mentor['active_mentees']; ?> mentorados ativos
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="match-badge"><?php echo $mentor['match_score']; ?>% Match</span>
                            <button onclick="assignMentor(<?php echo $p['project_id']; ?>, <?php echo $mentor['user_id']; ?>, '<?php echo htmlspecialchars($mentor['full_name']); ?>')" 
                                    style="display: block; margin-top: 5px; background: none; border: 1px solid var(--accent-orange); color: var(--accent-orange); border-radius: 4px; padding: 2px 8px; font-size: 0.7rem; cursor: pointer;">
                                Atribuir
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                        <label style="font-size: 0.8rem; color: #94a3b8; display: block; margin-bottom: 0.5rem;">Ou selecione manualmente:</label>
                        <select onchange="if(this.value) assignMentor(<?php echo $p['project_id']; ?>, this.value, this.options[this.selectedIndex].text)" style="width: 100%; background: rgba(0,0,0,0.3); color: white; border: 1px solid var(--glass-border); padding: 0.5rem; border-radius: 6px;">
                            <option value="">Buscar outro mentor...</option>
                            <?php foreach($all_mentors as $m): ?>
                                <option value="<?php echo $m['user_id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Modal para Detalhes do Projeto -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <button onclick="closeModal()" style="position: absolute; top: 1.5rem; right: 1.5rem; background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;"><i class="fas fa-times"></i></button>
            <div id="projectModalBody">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>

    <script>
        function viewProjectDetails(project) {
            const body = document.getElementById('projectModalBody');
            body.innerHTML = `
                <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <span style="background: var(--accent-orange); color: white; padding: 4px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; display: inline-block; margin-bottom: 1rem;">DETALHES DA IDEIA</span>
                        <h2 style="margin: 0 0 1rem 0; font-size: 2rem;">${project.title}</h2>
                        
                        <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; color: var(--text-secondary); font-size: 0.9rem;">
                            <span><i class="fas fa-user"></i> Autor: <b>${project.owner_name}</b></span>
                            <span><i class="fas fa-tag"></i> Categoria: <b>${project.category || 'N/A'}</b></span>
                        </div>

                        <h4 style="color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">Descrição do Projeto</h4>
                        <div style="line-height: 1.8; color: #cbd5e1; font-size: 1rem; margin-bottom: 2rem; white-space: pre-line;">
                            ${project.description}
                        </div>

                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                            <h4 style="margin-top: 0; color: #fff; font-size: 1rem; margin-bottom: 1rem;"><i class="fas fa-money-bill-wave" style="color: #10b981;"></i> Info de Investimento</h4>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">Investimento Aprovado</div>
                                    <div style="font-size: 1.5rem; font-weight: 800; color: #10b981;">${parseFloat(project.investment_amount || 0).toLocaleString('pt-AO')} AOA</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 0.75rem; color: #94a3b8;">Orçamento Necessário</div>
                                    <div style="font-size: 1.2rem; font-weight: 700;">${parseFloat(project.budget_needed || 0).toLocaleString('pt-AO')} AOA</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${project.image_url ? `
                    <div style="width: 250px;">
                        <img src="../${project.image_url}" style="width: 100%; border-radius: 12px; border: 1px solid var(--glass-border);">
                    </div>
                    ` : ''}
                </div>
                
                ${project.video_url ? `
                <div style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;">
                    <h4 style="margin-top: 0; color: #fff; margin-bottom:0.5rem;"><i class="fas fa-video"></i> Vídeo de Apresentação</h4>
                    <a href="${project.video_url}" target="_blank" style="color: var(--accent-orange); text-decoration: none; font-size: 0.9rem;">
                        <i class="fas fa-external-link-alt"></i> Assistir no YouTube/Vimeo
                    </a>
                </div>
                ` : ''}
            `;
            document.getElementById('projectModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('projectModal').style.display = 'none';
        }

        // Close on click outside
        window.onclick = function(event) {
            const modal = document.getElementById('projectModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        async function assignMentor(projectId, mentorId, mentorName) {
            const defaultTerms = `TERMOS DE COMPROMISSO DE MENTORIA - KALIYE\n\n1. O Mentor compromete-se a acompanhar o desenvolvimento do projeto através da plataforma KALIYE.\n2. Recomenda-se a realização de, no mínimo, 2 sessões mensais (virtuais ou presenciais).\n3. O Mentor deve validar as milestones e fornecer feedback técnico construtivo.\n4. Propriedade Intelectual: Todo o conhecimento gerado pertence ao estudante, sendo o mentor um facilitador.\n5. Confidencialidade: O mentor compromete-se a não divulgar informações sensíveis do projeto sem autorização.`;

            const { value: formValues } = await Swal.fire({
                title: 'Atribuição Profissional',
                html: `
                    <div style="text-align: left;">
                        <label style="font-size: 0.8rem; color: #94a3b8;">Mentor Selecionado:</label>
                        <div style="background: rgba(255,255,255,0.05); padding: 0.5rem; border-radius: 4px; margin-bottom: 1rem; border: 1px solid var(--accent-orange); color: #fff; font-weight: 700;">${mentorName}</div>
                        
                        <label style="font-size: 0.8rem; color: #94a3b8;">Tipo de Acordo:</label>
                        <select id="swal-contract-type" class="swal2-input" style="margin: 0.5rem 0 1rem 0; width: 100%; height: auto; padding: 0.8rem; background: #0f172a; color: #fff; border: 1px solid #334155;">
                            <option value="premium_invested">Premium (Projeto com Investimento)</option>
                            <option value="social_standard">Social (Aceleração de Talento)</option>
                            <option value="corporate_partnership">Parceria Corporativa</option>
                        </select>

                        <label style="font-size: 0.8rem; color: #94a3b8;">Termos e Condições Específicos:</label>
                        <textarea id="swal-terms" class="swal2-textarea" style="margin: 0.5rem 0; width: 100%; height: 180px; background: #0f172a; color: #fff; border: 1px solid #334155; font-size: 0.8rem; line-height: 1.5;">${defaultTerms}</textarea>

                        <label style="font-size: 0.8rem; color: #94a3b8;"><i class="fas fa-file-signature"></i> Anexar Contrato Assinado (Opcional - PDF/JPG):</label>
                        <input type="file" id="swal-file" class="swal2-input" style="margin: 0.5rem 0 1rem 0; width: 100%; font-size: 0.8rem; background: #0f172a; color: #fff; border: 1px solid #334155;">
                        
                        <label style="font-size: 0.8rem; color: #94a3b8;">Notas Administrativas (Privado):</label>
                        <textarea id="swal-admin-notes" class="swal2-textarea" style="margin: 0.5rem 0; width: 100%; height: 60px; background: #0f172a; color: #fff; border: 1px solid #334155; font-size: 0.8rem;"></textarea>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Enviar para Mentor',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981',
                background: '#1e293b',
                color: '#fff',
                width: '640px',
                preConfirm: () => {
                    return {
                        contract_type: document.getElementById('swal-contract-type').value,
                        terms: document.getElementById('swal-terms').value,
                        admin_notes: document.getElementById('swal-admin-notes').value,
                        file: document.getElementById('swal-file').files[0]
                    }
                }
            });

            if (formValues) {
                Swal.fire({ title: 'A processar...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

                const formData = new FormData();
                formData.append('project_id', projectId);
                formData.append('mentor_id', mentorId);
                formData.append('contract_type', formValues.contract_type);
                formData.append('contract_terms', formValues.terms);
                formData.append('admin_notes', formValues.admin_notes);
                if (formValues.file) {
                    formData.append('admin_signed_file', formValues.file);
                }

                fetch('../../interface_programacao/admin/admin_assign_mentor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('Sucesso!', 'Atribuição profissional concluída. O mentor foi notificado com os termos.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.message, 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error');
                });
            }
        }
    </script>
</body>
</html>





