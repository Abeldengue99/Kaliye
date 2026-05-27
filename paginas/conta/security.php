<?php
// paginas/conta/security.php
session_start();
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch 2FA Status
$stmt = $db->prepare("SELECT two_factor_enabled FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$is_2fa_enabled = $stmt->fetchColumn();

// If just activated via API, show success message
$success_msg = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo "<script>Swal.fire('Sucesso', 'Autenticação de Dois Fatores Ativada!', 'success');</script>";
}
?>

<div class="container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
    <div class="animate-fade-in" style="margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-shield-alt" style="color: var(--accent-orange);"></i>
            Segurança da Conta
        </h1>
        <p style="color: var(--text-secondary);">Gerencie as configurações de proteção da sua conta.</p>
    </div>

    <!-- 2FA Section -->
    <div class="glass" style="padding: 2rem; border-radius: 16px; border: 1px solid var(--glass-border); margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
            <div style="flex: 1;">
                <h3 style="margin: 0 0 0.5rem 0; color: white;">Autenticação de Dois Fatores (2FA)</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
                    Adicione uma camada extra de segurança à sua conta exigindo um código do seu aplicativo autenticador (Google Authenticator, Authy, etc.) ao fazer login.
                </p>
                
                <?php if ($is_2fa_enabled): ?>
                    <div style="margin-top: 1rem; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-check-circle"></i> ATIVO
                    </div>
                <?php else: ?>
                    <div style="margin-top: 1rem; color: var(--text-secondary); font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-times-circle" style="color: var(--text-secondary);"></i> DESATIVADO
                    </div>
                <?php endif; ?>
            </div>
            
            <div>
                <?php if ($is_2fa_enabled): ?>
                    <button onclick="disable2FA()" class="btn-primary" style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444;">
                        Desativar 2FA
                    </button>
                <?php else: ?>
                    <button onclick="start2FASetup()" class="btn-primary" style="background: var(--accent-blue);">
                        Ativar 2FA
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Setup Area (Hidden by default) -->
        <div id="setup-area" style="display: none; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--glass-border);">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: center;">
                <div style="text-align: center;">
                    <div id="qr-container" style="background: white; padding: 1rem; border-radius: 12px; display: inline-block;">
                        <!-- QR Code will be injected here -->
                    </div>
                    <p style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                        Escaneie este QR Code com seu app autenticador.<br>
                        Se não conseguir escanear, digite o código:<br>
                        <strong id="secret-code" style="color: var(--accent-orange); font-family: monospace; font-size: 1rem; letter-spacing: 1px;">LOADING...</strong>
                    </p>
                </div>
                
                <div>
                    <h4 style="margin-bottom: 1rem;">Verificar Código</h4>
                    <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
                        Após escanear, digite o código de 6 dígitos gerado pelo aplicativo para confirmar a ativação.
                    </p>
                    
                    <div class="input-group">
                        <input type="text" id="verify-code" placeholder="000 000" maxlength="6" style="font-size: 1.5rem; text-align: center; letter-spacing: 5px; width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                    </div>
                    
                    <button onclick="verifyAndEnable()" class="btn-primary" style="width: 100%; margin-top: 1rem;">
                        Verificar e Ativar
                    </button>
                    <button onclick="cancelSetup()" style="width: 100%; margin-top: 0.5rem; background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 0.5rem;">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Password Change Section (Quick Link) -->
    <div class="glass" style="padding: 2rem; border-radius: 16px; border: 1px solid var(--glass-border);">
        <h3 style="margin: 0 0 1rem 0; color: white;">Alterar Senha</h3>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Recomendamos alterar sua senha periodicamente.</p>
        <button onclick="location.href='profile.php?action=change_password'" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border);">
            Gerir Palavra-passe
        </button>
    </div>
</div>

<script>
let currentSecret = '';

function start2FASetup() {
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
    btn.disabled = true;

    fetch('../../interface_programacao/auth/enable_2fa.php')
        .then(res => {
            console.log('Response status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('2FA Setup Data:', data);
            
            if (data.success) {
                document.getElementById('setup-area').style.display = 'block';
                
                // Create QR Code image with error handling
                const qrImg = document.createElement('img');
                qrImg.src = data.qr_code_url;
                qrImg.style.width = '200px';
                qrImg.style.height = '200px';
                qrImg.alt = 'QR Code 2FA';
                
                qrImg.onerror = function() {
                    console.error('QR Code failed to load:', data.qr_code_url);
                    document.getElementById('qr-container').innerHTML = `
                        <div style="padding: 2rem; text-align: center; color: #ef4444;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>Erro ao carregar QR Code</p>
                            <p style="font-size: 0.85rem; margin-top: 0.5rem;">Use o código manual abaixo</p>
                        </div>
                    `;
                };
                
                qrImg.onload = function() {
                    console.log('QR Code loaded successfully');
                };
                
                document.getElementById('qr-container').innerHTML = '';
                document.getElementById('qr-container').appendChild(qrImg);
                
                document.getElementById('secret-code').innerText = data.secret;
                currentSecret = data.secret;
                
                // Hide button or change state
                btn.style.display = 'none';
            } else {
                console.error('2FA Setup Error:', data.error);
                Swal.fire('Erro', data.error || 'Erro ao iniciar configuração.', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error('Fetch Error:', err);
            Swal.fire('Erro', 'Erro de conexão: ' + err.message, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

function verifyAndEnable() {
    const code = document.getElementById('verify-code').value.replace(/\s/g, '');
    if (code.length < 6) {
        Swal.fire('Atenção', 'Digite o código de 6 dígitos.', 'warning');
        return;
    }

    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('code', code);
    
    // We are enabling, so verify_2fa handles logic
    fetch('../../interface_programacao/auth/verify_2fa.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Sucesso!',
                text: 'Autenticação de Dois Fatores ativada com segurança.',
                icon: 'success',
                confirmButtonColor: '#10b981'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro', data.message || 'Código inválido.', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Erro', 'Erro de conexão.', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function disable2FA() {
    Swal.fire({
        title: 'Desativar 2FA?',
        text: "Sua conta ficará menos segura.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#1e293b',
        confirmButtonText: 'Sim, desativar',
        background: '#1e293b',
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            // Need password confirmation in a real app, simplified here
             const formData = new FormData();
             formData.append('action', 'disable');
             
             // For simplicity, we create a dedicated endpoint or handle in verify_2fa
             // Let's create `servicos/disable_2fa.php` quickly or add logic to verify_2fa
             // Actually, verify_2fa logic I wrote earlier handles disable if NO session secret is present
             // But we need to pass a code? 
             // Logic in verify_2fa.php says: if (!session['temp']) -> check code -> disable.
             // So user must provide a code to disable? Good security practice.
             
             Swal.fire({
                title: 'Confirme com Código',
                text: 'Digite um código do autenticador para confirmar a desativação:',
                input: 'text',
                inputAttributes: {
                    autocapitalize: 'off',
                    maxlength: 6
                },
                showCancelButton: true,
                confirmButtonText: 'Desativar',
                showLoaderOnConfirm: true,
                preConfirm: (code) => {
                    const fd = new FormData();
                    fd.append('code', code);
                    return fetch('../../interface_programacao/auth/verify_2fa.php', { method: 'POST', body: fd })
                        .then(response => {
                            if (!response.ok) throw new Error(response.statusText);
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                },
                allowOutsideClick: () => !Swal.isLoading(),
                background: '#1e293b',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.success) {
                        Swal.fire('Desativado!', result.value.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro', result.value.message, 'error');
                    }
                }
            });
        }
    });
}

function cancelSetup() {
    document.getElementById('setup-area').style.display = 'none';
    // Ideally clear session temp secret via API
    location.reload();
}
</script>

<?php require_once '../../inclusoes/rodape.php'; ?>


