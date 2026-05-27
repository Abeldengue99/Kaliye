<?php
// meeting.php
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_type'] === 'admin');

// 1. Get Room from URL
$room_param = isset($_GET['room']) ? trim($_GET['room']) : null;
$error_msg = "";
$authorized = false;
$room_display_name = "Sala de Reunião";

if (!$room_param) {
    $error_msg = "ID da sala não fornecido.";
} else {
    // 2. Validate Room against Database (Security Check)
    try {
        // Allow ONLY letters, numbers, underscores to prevent injection/path issues
        // Jitsi rooms can handle underscores.
        $clean_room_param = preg_replace("/[^a-zA-Z0-9_]/", "", $room_param); 
        
        // Find the slot
        $stmt = $db->prepare("SELECT * FROM mentorship_slots WHERE meeting_room = ? LIMIT 1");
        $stmt->execute([$clean_room_param]);
        $slot = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$slot) {
             // For testing purposes or ad-hoc meetings not in DB:
             // Only Admins can access non-DB rooms
             if ($is_admin) {
                 $authorized = true;
                 $room_display_name = "Sala de Teste (Admin)";
             } else {
                 $error_msg = "Esta sala de reunião não existe ou expirou.";
             }
        } else {
            // Check Status
            if ($slot['status'] === 'completed' || $slot['status'] === 'cancelled') {
                 $error_msg = "Esta sessão já foi encerrada.";
            } else {
                // Check Participants
                // Allow: Mentor, Participant (if assigned), or Admin
                if ($is_admin || 
                    $slot['mentor_id'] == $current_user_id || 
                    $slot['participant_id'] == $current_user_id) {
                    
                    $authorized = true;
                    $room_display_name = "Mentoria #" . $slot['slot_id'];
                } else {
                    $error_msg = "Você não tem permissão para acessar esta sala.";
                }
            }
        }
    } catch (PDOException $e) {
        $error_msg = "Erro ao validar sala: " . $e->getMessage();
    }
}

// 3. Setup Jitsi Room Name
// We use the exact DB room name if validated
$final_room_name = $authorized ? $clean_room_param : '';
?>

<div class="meeting-page-container">
    <!-- Header Section -->
    <div class="meeting-header" data-aos="fade-down">
        <div class="header-content">
             <div class="header-title">
                <div class="icon-badge">
                    <i class="fas fa-video"></i>
                </div>
                <div>
                    <span class="meeting-eyebrow">Mentoria ao vivo</span>
                    <h2><?php echo htmlspecialchars($room_display_name); ?></h2>
                    <p>Entre na sessão com segurança, contexto e acesso validado.</p>
                </div>
             </div>
             <div class="meeting-status-row">
                <span class="meeting-chip">
                    <i class="fas fa-shield-alt"></i>
                    Acesso verificado
                </span>
                <span class="meeting-chip">
                    <i class="fas fa-video"></i>
                    Sala segura
                </span>
             </div>
        </div>
        <a href="mentorship.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> <span>Voltar para Mentoria</span>
        </a>
    </div>

    <!-- Error Display -->
    <?php if (!$authorized): ?>
        <div class="access-state-panel" data-aos="zoom-in" data-aos-delay="100">
            <div class="access-state-visual">
                <div class="access-glow"></div>
                <div class="access-lock">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            <div class="access-state-copy">
                <span class="meeting-eyebrow danger">Sala indisponivel</span>
                <h3>Acesso nao autorizado</h3>
                <p><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
            <div class="access-actions">
                <a href="mentorship.php" class="access-primary-btn">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Painel
                </a>
                <a href="../explorar/explore_mentors.php" class="access-secondary-btn">
                    <i class="fas fa-user-graduate"></i>
                    Procurar mentor
                </a>
            </div>
            <div class="access-help">
                <div>
                    <i class="fas fa-link"></i>
                    Confirme se abriu o link completo da sessão.
                </div>
                <div>
                    <i class="fas fa-calendar-check"></i>
                    Verifique se a mentoria ainda está ativa.
                </div>
            </div>
        </div>
    <?php else: ?>

    <!-- Video Stage -->
    <div class="video-stage glass-panel" data-aos="zoom-in" data-aos-delay="100">
        <div id="meet" class="meet-container">
            <!-- Placeholder is hidden immediately via JS to show Prejoin Screen -->
            <div id="meet-placeholder" class="meet-loader" style="display: none;">
                <div class="loader-content">
                    <div class="pulse-ring"></div>
                    <div class="video-icon-wrapper">
                        <i class="fas fa-video"></i>
                    </div>
                    <h3>A carregar sala...</h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Info Bar -->
    <div class="meeting-info-bar" data-aos="fade-up" data-aos-delay="200">
        <div class="info-item">
            <i class="fas fa-shield-alt"></i>
            <span>Conexão Segura e Validada</span>
        </div>
        <div class="info-item">
            <i class="fas fa-clock"></i>
            <span>Sem Limite de Tempo</span>
        </div>
        <div class="info-item">
             <i class="fas fa-users"></i>
             <span>Acesso Exclusivo</span>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    /* Scoped Styles for Meeting Page */
    .meeting-page-container {
        padding: clamp(1.25rem, 3vw, 2.5rem) 0;
        display: flex;
        flex-direction: column;
        gap: 1.75rem;
        min-height: calc(100vh - 250px); /* Ensure it takes reasonable space but respects footer */
    }

    /* Header Styling */
    .meeting-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
        background:
            linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(8, 13, 26, 0.94)),
            radial-gradient(circle at 10% 0%, rgba(247, 148, 29, 0.16), transparent 34%);
        padding: clamp(1.25rem, 2.8vw, 2rem);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.09);
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.24);
        position: relative;
        overflow: hidden;
    }

    .meeting-header::after {
        content: "";
        position: absolute;
        inset: auto 2rem 0 auto;
        width: 220px;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(247, 148, 29, 0.7));
    }

    .header-content {
        display: flex;
        align-items: center;
        gap: clamp(1rem, 3vw, 2rem);
        flex-wrap: wrap;
        min-width: min(100%, 640px);
    }

    .header-title {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .icon-badge {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, rgba(247, 148, 29, 0.24), rgba(16, 185, 129, 0.12));
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-orange);
        font-size: 1.35rem;
        border: 1px solid rgba(247, 148, 29, 0.24);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1), 0 18px 36px rgba(0, 0, 0, 0.24);
        flex: 0 0 auto;
    }

    .meeting-eyebrow {
        display: inline-flex;
        align-items: center;
        margin-bottom: 0.35rem;
        color: #f59e0b;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .meeting-eyebrow.danger {
        color: #f87171;
    }

    .header-title h2 {
        font-size: clamp(1.45rem, 2.4vw, 2.1rem);
        margin: 0;
        color: var(--text-primary);
        line-height: 1.1;
    }

    .header-title p {
        margin: 0.4rem 0 0 0;
        font-size: 0.98rem;
        color: var(--text-secondary);
        max-width: 520px;
    }

    .meeting-status-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .meeting-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 36px;
        padding: 0 0.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.72);
        font-size: 0.78rem;
        font-weight: 800;
    }

    .meeting-chip i {
        color: #10b981;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        min-height: 48px;
        padding: 0 1.2rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.06);
        color: var(--text-primary);
        text-decoration: none;
        transition: all 0.3s ease;
        font-weight: 800;
        font-size: 0.9rem;
        position: relative;
        z-index: 1;
    }

    .back-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(-5px);
        color: var(--accent-orange);
        border-color: var(--accent-orange);
    }

    .access-state-panel {
        display: grid;
        grid-template-columns: minmax(180px, 0.32fr) minmax(280px, 1fr);
        align-items: center;
        gap: clamp(1.5rem, 4vw, 3rem);
        padding: clamp(1.5rem, 4vw, 3rem);
        min-height: 440px;
        border-radius: 28px;
        border: 1px solid rgba(255, 255, 255, 0.09);
        background:
            linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(5, 10, 22, 0.98)),
            radial-gradient(circle at 85% 20%, rgba(239, 68, 68, 0.14), transparent 28%);
        box-shadow: 0 30px 90px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        position: relative;
    }

    .access-state-panel::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
        background-size: 46px 46px;
        mask-image: linear-gradient(90deg, rgba(0,0,0,0.28), transparent 72%);
        pointer-events: none;
    }

    .access-state-visual,
    .access-state-copy,
    .access-actions,
    .access-help {
        position: relative;
        z-index: 1;
    }

    .access-state-visual {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 230px;
    }

    .access-glow {
        position: absolute;
        width: 170px;
        height: 170px;
        border-radius: 50%;
        background: rgba(239, 68, 68, 0.16);
        filter: blur(18px);
    }

    .access-lock {
        width: 132px;
        height: 132px;
        border-radius: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.22), rgba(247, 148, 29, 0.12));
        border: 1px solid rgba(248, 113, 113, 0.32);
        color: #f87171;
        font-size: 3.2rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.12), 0 26px 60px rgba(239, 68, 68, 0.14);
    }

    .access-state-copy h3 {
        margin: 0;
        color: #fff;
        font-size: clamp(2rem, 4vw, 3.5rem);
        line-height: 1;
    }

    .access-state-copy p {
        margin: 1rem 0 0;
        max-width: 660px;
        color: rgba(226, 232, 240, 0.72);
        font-size: clamp(1rem, 1.7vw, 1.25rem);
        line-height: 1.55;
    }

    .access-actions {
        grid-column: 2;
        display: flex;
        gap: 0.9rem;
        flex-wrap: wrap;
        margin-top: -1rem;
    }

    .access-primary-btn,
    .access-secondary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        min-height: 54px;
        padding: 0 1.3rem;
        border-radius: 16px;
        text-decoration: none;
        font-weight: 900;
        transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .access-primary-btn {
        background: linear-gradient(135deg, #f7941d, #fbbf24);
        color: #111827;
        box-shadow: 0 18px 40px rgba(247, 148, 29, 0.22);
    }

    .access-secondary-btn {
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .access-primary-btn:hover,
    .access-secondary-btn:hover {
        transform: translateY(-2px);
    }

    .access-secondary-btn:hover {
        border-color: rgba(247, 148, 29, 0.5);
        color: #fbbf24;
    }

    .access-help {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: 0.8rem;
        padding-top: 1.2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .access-help div {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        min-height: 48px;
        padding: 0 1rem;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.04);
        color: rgba(226, 232, 240, 0.68);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .access-help i {
        color: #f59e0b;
    }

    /* Video Stage */
    .video-stage {
        background: #000;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        position: relative;
        /* Replaced fixed height with responsive aspect ratio/min-height */
        width: 100%;
        aspect-ratio: 16 / 9;
        min-height: 550px; /* Base height for desktop */
        max-height: 85vh; /* Prevent it from being too tall on huge screens */
    }

    .meet-container {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Info Bar */
    .meeting-info-bar {
        display: flex;
        justify-content: center;
        gap: 3rem;
        flex-wrap: wrap;
        padding-top: 1rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .info-item i {
        color: #10b981; /* Green for trust */
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .meeting-page-container {
            padding: 1rem 0;
        }
        
        .meeting-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
            border-radius: 20px;
        }

        .header-content,
        .header-title {
            width: 100%;
        }

        .icon-badge {
            width: 56px;
            height: 56px;
            border-radius: 16px;
        }
        
        .back-btn {
            width: 100%;
            justify-content: center;
        }

        .access-state-panel {
            grid-template-columns: 1fr;
            text-align: center;
            min-height: auto;
            border-radius: 22px;
        }

        .access-state-visual {
            min-height: 150px;
        }

        .access-lock {
            width: 104px;
            height: 104px;
            border-radius: 26px;
            font-size: 2.55rem;
        }

        .access-actions {
            grid-column: 1;
            margin-top: 0;
        }

        .access-primary-btn,
        .access-secondary-btn {
            width: 100%;
        }

        .access-help {
            grid-template-columns: 1fr;
            text-align: left;
        }

        .video-stage {
            min-height: 400px;
            border-radius: 12px;
            aspect-ratio: auto;
            height: 60vh;
        }
        
        .meeting-info-bar {
            gap: 1rem;
            flex-direction: column;
            align-items: center;
        }
    }
</style>

<!-- Usamos o servidor oficial Jitsi (mais estável do mundo) -->
<?php if ($authorized): ?>
    
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const domain = 'meet.jit.si';
        const roomName = '<?php echo $final_room_name; ?>';
        const meetContainer = document.getElementById('meet');

        // Carregar o script do Jitsi dinamicamente para garantir que ele termina de carregar
        const script = document.createElement('script');
        script.src = 'https://meet.jit.si/external_api.js';
        script.onload = function() {
            initJitsi();
        };
        script.onerror = function() {
            showFallback();
        };
        document.head.appendChild(script);

        function initJitsi() {
            const options = {
                roomName: roomName,
                width: '100%',
                height: '100%',
                parentNode: meetContainer,
                userInfo: {
                    displayName: '<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : "Usuário"; ?>'
                },
                configOverwrite: { 
                    prejoinPageEnabled: true,
                    disableDeepLinking: true,
                    startWithAudioMuted: false, 
                    startWithVideoMuted: false,
                    theme: 'dark',
                    enableWelcomePage: false
                },
                interfaceConfigOverwrite: {
                    TOOLBAR_BUTTONS: [
                        'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                        'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
                        'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
                        'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
                        'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone',
                        'security'
                    ],
                    SHOW_JITSI_WATERMARK: false,
                    SHOW_WATERMARK_FOR_GUESTS: false
                }
            };

            try {
                if (typeof JitsiMeetExternalAPI !== 'undefined') {
                    const api = new JitsiMeetExternalAPI(domain, options);
                    
                    api.addEventListener('readyToClose', () => {
                        window.location.href = 'mentorship.php';
                    });
                } else {
                    showFallback();
                }
            } catch (error) {
                console.error("Jitsi Error:", error);
                showFallback();
            }
        }

        function showFallback() {
            console.error("Jitsi library not loaded or failed to initialize.");
            if(meetContainer) {
                meetContainer.innerHTML = `
                    <div style="text-align: center; color: white; padding: 2rem;">
                        <h3>O seu navegador bloqueou o carregamento automático da sala.</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Isto acontece frequentemente por causa de bloqueadores de anúncios (AdBlockers) ou definições de privacidade estritas.</p>
                        <a href="https://meet.jit.si/${roomName}" target="_blank" class="btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-external-link-alt"></i> ABRIR SALA NUMA NOVA JANELA
                        </a>
                    </div>
                `;
            }
        }
    });
</script>
<?php endif; ?>

<?php require_once '../../inclusoes/rodape.php'; 

