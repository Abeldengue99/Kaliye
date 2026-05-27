<?php
/**
 * inclusoes/rodape.php - Platform Footer
 */
?>
    </div> <!-- Final do main-content-wrapper aberto no cabecalho.php -->

    <?php
    // Para utilizadores logados, o rodapé só aparece no dashboard principal (index.php)
    $current_page_file = basename($_SERVER['PHP_SELF']);
    $show_footer = !isset($_SESSION['user_id']) || $current_page_file === 'index.php';
    if ($show_footer) {
        include __DIR__ . '/components/landing_footer.php';
    }
    if (false):
    ?>
    <footer class="plataforma-rodape">
        <div class="rodape-container">
            <!-- Marca e Missão -->
            <div class="rodape-bloco">
                <div class="rodape-logo">
                    <div class="logo-box">
                        <img src="<?php echo $base_url; ?>recursos/images/marca/logotipo.png" style="width: 100%; height: 100%; object-fit: cover;" alt="KALIYE">
                    </div>
                    <div class="marca-info">
                        <span class="marca-main">KALIYE</span>
                    </div>
                </div>
                <p class="rodape-texto">O teu acelerador de sucesso profissional.</p>
            </div>

            <!-- Newsletter Compacta -->
            <div class="rodape-newsletter">
                <h5>NEWSLETTER</h5>
                <p class="rodape-nl-text">Recebe as últimas novidades e oportunidades.</p>
                <form id="footerNewsletterForm" class="rodape-nl-form">
                    <input type="text" name="name" placeholder="O teu nome" class="rodape-nl-input" required>
                    <input type="email" name="email" placeholder="O teu e-mail" class="rodape-nl-input" required>
                    <button type="submit" id="footerNlBtn" class="rodape-nl-btn">
                        <i class="fas fa-paper-plane"></i> Subscrever
                    </button>
                </form>
            </div>
        </div>

        <div class="rodape-direitos">
            <p>&copy; <?php echo date('Y'); ?> KALIYE.</p>
            <div class="legal-links">
                <a href="javascript:void(0)" onclick="openLegalModal('termos')">Termos</a>
                <span>•</span>
                <a href="javascript:void(0)" onclick="openLegalModal('privacidade')">Privacidade</a>
            </div>
        </div>
    </footer>
    <style>
    .plataforma-rodape {
        margin-top: 4rem;
        background: #050a15;
        border-top: 1px solid rgba(255,255,255,0.06);
        padding-top: 2.75rem;
    }
    .rodape-container {
        width: min(1120px, calc(100% - 2rem));
        margin: 0 auto;
        padding: 0 0 2.75rem;
        display: grid;
        grid-template-columns: minmax(220px, 1fr) minmax(280px, 420px);
        align-items: center;
        gap: 2.5rem;
    }
    .rodape-bloco { min-width: 0; }
    .rodape-logo { display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.85rem; }
    .logo-box {
        width: 44px;
        height: 44px;
        background: #fff;
        border-radius: 8px;
        padding: 6px;
        flex: 0 0 auto;
    }
    .marca-info { display: flex; flex-direction: column; line-height: 1; }
    .marca-main { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.35rem; color: #fff; }
    .rodape-texto {
        color: rgba(255,255,255,0.5);
        font-size: 0.95rem;
        line-height: 1.65;
        margin: 0;
        max-width: 280px;
    }
    .rodape-newsletter {
        width: 100%;
        justify-self: end;
    }
    .rodape-newsletter h5 {
        color: #f7941d;
        font-size: 0.72rem;
        font-weight: 900;
        margin: 0 0 0.75rem;
        letter-spacing: 1.5px;
    }
    .rodape-nl-text {
        color: rgba(255,255,255,0.5);
        font-size: 0.88rem;
        margin: 0 0 1rem;
        line-height: 1.55;
    }
    .rodape-nl-form {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.7rem;
    }
    .rodape-nl-input {
        width: 100%;
        box-sizing: border-box;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        padding: 0.72rem 0.9rem;
        color: #fff;
        font-size: 0.88rem;
        outline: none;
        transition: border-color 0.2s, background 0.2s;
    }
    .rodape-nl-input::placeholder { color: rgba(255,255,255,0.3); }
    .rodape-nl-input:focus {
        background: rgba(255,255,255,0.075);
        border-color: rgba(247,148,29,0.55);
    }
    .rodape-nl-btn {
        background: #f7941d;
        color: #000;
        border: none;
        border-radius: 8px;
        padding: 0.78rem 1rem;
        font-size: 0.88rem;
        font-weight: 800;
        cursor: pointer;
        transition: 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        letter-spacing: 0.5px;
    }
    .rodape-nl-btn:hover { background: #e8830a; transform: translateY(-1px); }
    .rodape-nl-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
    .rodape-direitos {
        background: #030712 !important;
        padding: 1.45rem 1rem !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 1.25rem !important;
        font-size: 0.75rem !important;
        color: rgba(255,255,255,0.4) !important;
        font-weight: 500 !important;
        border-top: 1px solid rgba(255,255,255,0.05) !important;
        text-transform: none !important;
        letter-spacing: 0.5px !important;
    }
    .rodape-direitos p { margin: 0 !important; color: rgba(255,255,255,0.4) !important; }
    .legal-links { display: flex !important; gap: 1rem !important; align-items: center !important; }
    .legal-links a {
        color: rgba(255,255,255,0.6) !important;
        text-decoration: none !important;
        transition: 0.3s !important;
        font-weight: 700 !important;
        font-size: 0.7rem !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
    }
    .legal-links a:hover { color: #f7941d !important; }
    @media (max-width: 760px) {
        .plataforma-rodape {
            margin-top: 3rem;
            padding-top: 2rem;
        }
        .rodape-container {
            width: min(100% - 1.25rem, 440px);
            grid-template-columns: 1fr;
            gap: 1.75rem;
            text-align: center;
            padding-bottom: 2rem;
        }
        .rodape-logo { justify-content: center; }
        .rodape-texto {
            margin: 0 auto;
            font-size: 0.9rem;
        }
        .rodape-newsletter { justify-self: stretch; }
        .rodape-newsletter h5 { margin-bottom: 0.6rem; }
        .rodape-nl-text {
            max-width: 320px;
            margin-left: auto;
            margin-right: auto;
        }
        .rodape-direitos {
            flex-direction: column !important;
            gap: 0.85rem !important;
            text-align: center !important;
        }
    }
    </style>

    <script>
    (function() {
        const form = document.getElementById('footerNewsletterForm');
        if (!form) return;
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('footerNlBtn');
            const fd = new FormData(form);
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A enviar...';
            try {
                const res = await fetch('<?php echo $base_url; ?>interface_programacao/system/subscribe_newsletter.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Subscrito!';
                    btn.style.background = '#10b981';
                    btn.style.color = '#fff';
                    form.querySelector('input[name="name"]').value = '';
                    form.querySelector('input[name="email"]').value = '';
                } else {
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Subscrever';
                    btn.disabled = false;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'info', title: data.message || 'Erro', background: '#1e293b', color: '#fff', timer: 2500 });
                    } else { alert(data.message); }
                }
            } catch(err) {
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Subscrever';
                btn.disabled = false;
            }
        });
    })();
    </script>

    <?php endif; ?>

    <?php 
        // 1. Carregamento Universal dos Modais de Sistema (Caminhos Absolutos)
        $components_dir = __DIR__ . '/components/';
        
        $current_page_file = basename($_SERVER['PHP_SELF']);
        include_once $components_dir . 'project_modal.php';
        if ($current_page_file !== 'investor_dashboard.php') {
            include_once $components_dir . 'project_details_modal.php';
        }
        // Include investment modal only if payments are enabled
        $payments_config = require __DIR__ . '/../configuracoes/pagamentos.php';
        if (isset($payments_config['payments_enabled']) && $payments_config['payments_enabled'] === true) {
            include_once $components_dir . 'invest_modal.php';
        } else {
            // If payments are disabled, hide invest buttons and disable client flows
            echo "<script>document.addEventListener('DOMContentLoaded', function(){ try{ document.querySelectorAll('.btn-invest-elite').forEach(b=>{ b.style.display='none'; }); window.openInvestmentFlow = function(){ if(typeof Swal !== 'undefined') { Swal.fire({ icon:'info', title:'Investimentos desativados', text:'A funcionalidade de investimento monetário está desativada nesta versão.', background:'#0d1628', color:'#fff' }); } else { alert('Investimentos desativados nesta versão.'); } }; }catch(e){console.error('disable investments script error',e);} });</script>";
        }
        include_once $components_dir . 'ad_modal.php'; 
        include_once $components_dir . 'user_card_modal.php'; 
        include_once $components_dir . 'profile_edit_modal.php'; 
        include_once $components_dir . 'kyc_modal.php'; // Novo Wizard Multi-Etapas (Unificado)
        include_once $components_dir . 'legal_modal.php';
        include_once $components_dir . 'evaluation_modal.php'; // Sistema de Feedback da Plataforma

        // 2. Carregamento Universal dos Scripts do Dashboard e Módulos Elite
        include_once $components_dir . 'index_scripts.php';
        include_once $components_dir . 'project_scripts_v2.php'; // Motor v2 para validação de vídeos de Pitch
    ?>

    <!-- Scripts de Sistema (Optimizados) -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script> 
        AOS.init({ duration: 800, once: true }); 

        // Auto-trigger do Gate de Verificação para páginas de Acesso Restrito Direto
        // (No Dashboard, o gatilho é manual via botão, cumprindo a nova regra de UX)
        <?php if (isset($trigger_kyc_modal) && $trigger_kyc_modal && basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
        (function autoTriggerKYC() {
            if (typeof window.openKYCModal === 'function') {
                window.openKYCModal();
            } else {
                setTimeout(autoTriggerKYC, 100);
            }
        })();
        <?php endif; ?>

        // Auto-trigger do Modal de Candidatura a Mentor se via URL
        <?php if (isset($_GET['mentor_required']) && $_GET['mentor_required'] == 1): ?>
        window.addEventListener('DOMContentLoaded', () => {
             if (typeof openMentorAppModal === 'function') {
                 openMentorAppModal();
             }
        });
        <?php endif; ?>
    </script>
    <!-- Aksanti Modals V2: Sistema Standalone (override final) -->
    <script src="<?php echo $base_url; ?>recursos/js/aksanti_modals_v2.js"></script>
</body>
</html>
