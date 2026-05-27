<?php
// pages/legal/termos.php
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';
?>

<div class="container" style="<?php echo !isset($_SESSION['user_id']) ? 'margin: 2rem auto;' : 'margin-top: 3rem;'; ?> margin-bottom: 5rem; max-width: 900px;">
    <div class="glass" style="padding: 3rem; border-radius: 24px; border: 1px solid var(--glass-border);">
        <h1 style="color: var(--accent-orange); margin-bottom: 1rem;"><i class="fas fa-file-contract"></i> Termos e Condições de Uso</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2.5rem;">Versão 1.0 - Ãšltima atualização: <?php echo date('d \d\e F \d\e Y'); ?></p>

        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">1. Aceitação dos Termos</h2>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                Ao registar-se na plataforma KALIYE, o utilizador concorda expressamente com todos os termos e condições aqui descritos. 
                Estes termos regem o uso da plataforma de mentoria, investimento e rede social académica.
            </p>
        </section>
 
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">2. Originalidade e Propriedade Intelectual</h2>
            <div style="background: rgba(239, 68, 68, 0.05); border-left: 4px solid #ef4444; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                <p style="color: #ef4444; font-weight: 700; margin-bottom: 0.5rem;">PLÃGIO ZERO</p>
                <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6;">
                    A KALIYE utiliza auditoria por IA. O plágio resultará em suspensão imediata. O utilizador mantém a propriedade intelectual das suas ideias, 
                    mas concede à KALIYE o direito de as exibir para mentores e investidores conforme as definições de privacidade escolhidas.
                </p>
            </div>
        </section>
 
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">3. Verificação de Identidade (KYC)</h2>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                Para garantir a segurança da rede, a KALIYE exige a verificação de identidade (BI/Passaporte). O utilizador compromete-se a fornecer dados verídicos. 
                O uso de documentos falsos é crime punível por lei.
            </p>
        </section>
 
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">4. Atividade de Mentoria e Investimento</h2>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                A KALIYE é uma facilitadora. Acordos de investimento e mentoria pagos são de responsabilidade mútua das partes, 
                devendo ser regidos por contratos específicos fornecidos ou validados pela plataforma.
            </p>
        </section>

        <div style="text-align: center; margin-top: 3rem;">
            <button onclick="window.close()" class="btn-primary" style="padding: 1rem 2.5rem;">Fechar Página</button>
        </div>
    </div>
</div>

<?php require_once '../../inclusoes/rodape.php'; ?>


