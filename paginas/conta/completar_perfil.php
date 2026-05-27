<?php
session_start();
$base_url = '../../';

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

requireLogin();

$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT full_name, email, phone, id_number, birth_date, user_type FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([(int)$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ../../autenticacao/sair.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Perfil | KALIYE</title>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin: 0; min-height: 100vh; background: #070d1a; color: #fff; font-family: Inter, Arial, sans-serif; display: grid; place-items: center; padding: 2rem 1rem; }
        .profile-shell { width: 100%; max-width: 760px; background: rgba(15,23,42,0.92); border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 2rem; box-shadow: 0 24px 70px rgba(0,0,0,0.35); }
        .top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.75rem; }
        .badge { display: inline-flex; align-items: center; gap: .45rem; color: #34d399; background: rgba(52,211,153,.1); border: 1px solid rgba(52,211,153,.18); padding: .45rem .7rem; border-radius: 999px; font-size: .72rem; font-weight: 800; text-transform: uppercase; }
        h1 { margin: .75rem 0 .35rem; font-size: clamp(1.7rem, 4vw, 2.4rem); line-height: 1.05; }
        p { margin: 0; color: #94a3b8; line-height: 1.6; }
        form { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; }
        .field { display: flex; flex-direction: column; gap: .45rem; }
        .field.full { grid-column: 1 / -1; }
        label { color: #cbd5e1; font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        input, select { width: 100%; box-sizing: border-box; background: rgba(255,255,255,.045); color: #fff; border: 1px solid rgba(255,255,255,.09); border-radius: 10px; padding: .9rem 1rem; outline: none; }
        input:focus, select:focus { border-color: #f7941d; box-shadow: 0 0 0 3px rgba(247,148,29,.12); }
        select option { background: #111827; color: #fff; }
        .actions { grid-column: 1 / -1; display: flex; gap: .8rem; align-items: center; justify-content: flex-end; margin-top: .5rem; flex-wrap: wrap; }
        .btn { border: 0; border-radius: 10px; padding: .9rem 1.2rem; cursor: pointer; font-weight: 800; display: inline-flex; align-items: center; gap: .5rem; }
        .btn-primary { background: #f7941d; color: #111827; }
        .btn-light { background: rgba(255,255,255,.06); color: #e5e7eb; text-decoration: none; }
        .notice { grid-column: 1 / -1; display: none; padding: .85rem 1rem; border-radius: 10px; font-size: .88rem; }
        .notice.error { display: block; background: rgba(239,68,68,.1); color: #fca5a5; border: 1px solid rgba(239,68,68,.18); }
        .notice.success { display: block; background: rgba(16,185,129,.1); color: #6ee7b7; border: 1px solid rgba(16,185,129,.18); }
        @media (max-width: 640px) { .profile-shell { padding: 1.3rem; } form { grid-template-columns: 1fr; } .field.full, .actions, .notice { grid-column: 1; } .actions { justify-content: stretch; } .btn { justify-content: center; width: 100%; } }
    </style>
</head>
<body>
    <main class="profile-shell">
        <div class="top">
            <div>
                <span class="badge"><i class="fab fa-google"></i> Conta Google ligada</span>
                <h1>Completa o teu perfil</h1>
                <p>O email ja foi verificado pelo Google. Falta preencher os dados operacionais para perfil, KYC e acesso completo.</p>
            </div>
            <a href="../../autenticacao/sair.php" class="btn btn-light"><i class="fas fa-right-from-bracket"></i> Sair</a>
        </div>

        <form id="completeGoogleProfileForm">
            <?= getCSRFHiddenInput() ?>
            <div class="notice" id="formNotice"></div>

            <div class="field full">
                <label for="full_name">Nome completo</label>
                <input id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label for="user_type">Tipo de perfil</label>
                <select id="user_type" name="user_type" required>
                    <option value="" <?= empty($user['user_type']) ? 'selected' : '' ?>>Selecionar</option>
                    <option value="univ_student" <?= ($user['user_type'] ?? '') === 'univ_student' ? 'selected' : '' ?>>Estudante Universitario</option>
                    <option value="high_student" <?= ($user['user_type'] ?? '') === 'high_student' ? 'selected' : '' ?>>Estudante do Ensino Medio</option>
                    <option value="mentor" <?= ($user['user_type'] ?? '') === 'mentor' ? 'selected' : '' ?>>Mentor</option>
                    <option value="investor" <?= ($user['user_type'] ?? '') === 'investor' ? 'selected' : '' ?>>Investidor / Parceiro</option>
                </select>
            </div>

            <div class="field">
                <label for="phone">Telefone</label>
                <input id="phone" name="phone" type="tel" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+244 9..." required>
            </div>

            <div class="field">
                <label for="birth_date">Data de nascimento</label>
                <input id="birth_date" name="birth_date" type="date" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="field">
                <label for="id_number">Nº BI ou Passaporte</label>
                <input id="id_number" name="id_number" type="text" value="<?= htmlspecialchars($user['id_number'] ?? '') ?>" maxlength="20" required>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary" id="submitBtn"><i class="fas fa-check"></i> Guardar e verificar documentos</button>
            </div>
        </form>
    </main>

    <script>
        document.getElementById('id_number').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^0-9A-Z]/g, '');
        });

        document.getElementById('completeGoogleProfileForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const notice = document.getElementById('formNotice');
            const btn = document.getElementById('submitBtn');
            notice.className = 'notice';
            notice.textContent = '';
            btn.disabled = true;

            try {
                const response = await fetch('../../interface_programacao/auth/complete_google_profile.php', {
                    method: 'POST',
                    body: new FormData(this)
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Falha ao guardar.');
                notice.className = 'notice success';
                notice.textContent = data.message;
                window.location.href = data.redirect || '../../paginas/social/profile.php?tab=kyc';
            } catch (error) {
                notice.className = 'notice error';
                notice.textContent = error.message;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
