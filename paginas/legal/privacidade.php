<?php
// pages/legal/privacidade.php
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';
?>

<div class="container" style="<?php echo !isset($_SESSION['user_id']) ? 'margin: 2rem auto;' : 'margin-top: 3rem;'; ?> margin-bottom: 5rem; max-width: 900px;">
    <div class="glass" style="padding: 3rem; border-radius: 24px; border: 1px solid var(--glass-border);">
        <h1 style="color: var(--accent-orange); margin-bottom: 1rem;"><i class="fas fa-user-shield"></i> Política de Privacidade</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2.5rem;">Ãšltima atualização: <?php echo date('d \d\e F \d\e Y'); ?></p>

        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">1. Coleta de Dados</h2>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                Coletamos dados necessários para a sua identificação e operação na plataforma, incluindo: Nome, Email, Telefone, Documento de Identificação, 
                e dados de navegação/IP para fins de segurança e auditoria legal.
            </p>
        </section>

        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">2. Uso das Informações</h2>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                Os seus dados são utilizados para:
                <ul style="margin-top: 10px; padding-left: 1.5rem;">
                    <li>Verificação de conta e segurança.</li>
                    <li>Matchmaking inteligente com mentores e investidores.</li>
                    <li>Comunicações oficiais do sistema via Email e SMS.</li>
                    <li>Rastreio de aceitação de termos legais.</li>
                </ul>
            </p>
        </section>

        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">3. Partilha de Dados</h2>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                Não vendemos dados a terceiros. As suas informações de projeto são partilhadas apenas com mentores e investidores autorizados por si 
                através das interações na plataforma.
            </p>
        </section>

        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">4. Direitos do Utilizador</h2>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                O utilizador tem direito a solicitar o acesso, retificação ou eliminação dos seus dados pessoais, ciente de que a eliminação de dados 
                críticos poderá impossibilitar o uso da plataforma.
            </p>
        </section>

        <div style="text-align: center; margin-top: 3rem;">
            <button onclick="window.close()" class="btn-primary" style="padding: 1rem 2.5rem;">Fechar Página</button>
        </div>
    </div>
</div>

<?php require_once '../../inclusoes/rodape.php'; ?>


