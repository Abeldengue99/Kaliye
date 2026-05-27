<?php
/**
 * administracao/newsletter/broadcast.php
 * KALIYE Broadcast Center - Envio de Mensagens em Massa
 */
session_start();
$base_url = '../../';

require_once $base_url . 'inclusoes/auth_check.php';
if (!isAdmin() || !hasPermission('ads')) {
    header("Location: " . $base_url . "index.php");
    exit();
}

require_once $base_url . 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

// Contagem de subscritores
$total_subs = $db->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>recursos/images/marca/apple-touch-icon-k.png">
    <title>Broadcast Center | KALIYE Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/dashboard-aksanti-elite.css">
    <style>
        .broadcast-card {
            background: var(--bg-1);
            border-radius: 24px;
            border: 1px solid var(--surface-10);
            padding: 2.5rem;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; color: var(--elite-orange); font-weight: 800; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.6rem; letter-spacing: 1px; }
        .form-control { 
            width: 100%; background: rgba(255,255,255,0.03); border: 1px solid var(--surface-10); 
            border-radius: 12px; padding: 1rem; color: #fff; font-size: 0.95rem; outline: none; transition: 0.3s;
        }
        .form-control:focus { border-color: var(--elite-orange); background: rgba(255,255,255,0.06); }
        .broadcast-stats {
            display: flex; gap: 1rem; margin-bottom: 2rem; padding: 1.2rem;
            background: rgba(247, 148, 29, 0.05); border-radius: 16px; border: 1px dashed rgba(247, 148, 29, 0.2);
        }
        .btn-broadcast {
            width: 100%; padding: 1.2rem; background: var(--elite-orange); color: #000;
            border: none; border-radius: 14px; font-weight: 900; font-family: 'Outfit';
            font-size: 1rem; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-broadcast:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(247,148,29,0.3); }
    </style>
</head>
<body class="admin-body">
    <?php include $base_url . 'inclusoes/cabecalho.php'; ?>

    <main class="main-content" style="padding-top: 100px;">
        <div class="container-secao">
            <div class="broadcast-card">
                <header style="margin-bottom: 2rem;">
                    <h1 style="color:#fff; font-family:'Outfit'; font-size: 2rem;">Broadcast Center</h1>
                    <p style="color:var(--surface-60);">Envia comunicados oficiais para toda a tua audiência.</p>
                </header>

                <div class="broadcast-stats">
                    <i class="fas fa-users" style="color:var(--elite-orange); font-size: 1.5rem;"></i>
                    <div>
                        <span style="display:block; color:#fff; font-weight:800; font-size:1.1rem;"><?php echo $total_subs; ?> Contactos</span>
                        <span style="font-size:0.75rem; color:var(--surface-40);">Prontos para receber a tua mensagem</span>
                    </div>
                </div>

                <form id="broadcastForm">
                    <div class="form-group">
                        <label class="form-label">Assunto do E-mail</label>
                        <input type="text" name="subject" class="form-control" placeholder="Ex: Novidades Incríveis na KALIYE!" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Corpo da Mensagem (HTML Suportado)</label>
                        <textarea name="message" class="form-control" rows="10" placeholder="Escreve aqui o conteúdo da tua newsletter..." required></textarea>
                    </div>

                    <button type="submit" class="btn-broadcast" id="sendBtn">
                        <i class="fas fa-paper-plane"></i> DISPARAR BROADCAST AGORA
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('broadcastForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Confirmar Disparo?',
                text: "Estás prestes a enviar este e-mail para <?php echo $total_subs; ?> subscritores. Esta ação não pode ser desfeita.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f7941d',
                cancelButtonColor: '#1e293b',
                confirmButtonText: 'Sim, disparar agora!',
                cancelButtonText: 'Revisar',
                background: '#0d1628',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    processBroadcast();
                }
            });
        });

        async function processBroadcast() {
            const btn = document.getElementById('sendBtn');
            const form = document.getElementById('broadcastForm');
            const fd = new FormData(form);

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> A ENVIAR MENSAGENS...';

            try {
                const response = await fetch('<?php echo $base_url; ?>interface_programacao/admin/send_broadcast.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Broadcast Concluído!',
                        text: data.message,
                        background: '#0d1628',
                        color: '#fff'
                    });
                    form.reset();
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro no Envio', text: data.message, background: '#0d1628', color: '#fff' });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Erro Técnico', text: 'Não foi possível completar o disparo.', background: '#0d1628', color: '#fff' });
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> DISPARAR BROADCAST AGORA';
            }
        }
    </script>

    <?php include $base_url . 'inclusoes/rodape.php'; ?>
</body>
</html>

