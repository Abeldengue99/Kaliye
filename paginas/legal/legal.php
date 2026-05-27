<?php
// pages/legal/legal.php
$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';
?>

<div class="container" style="margin-top: 3rem; margin-bottom: 5rem; max-width: 900px;">
    <div class="glass" style="padding: 3rem; border-radius: 24px; border: 1px solid var(--glass-border);">
        <h1 style="color: var(--accent-orange); margin-bottom: 1rem;"><i class="fas fa-gavel"></i> Termos de Uso e Proteção de Propriedade Intelectual</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2.5rem;">Ãšltima atualização: <?php echo date('d \d\e F \d\e Y'); ?></p>

        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">1. Compromisso de Originalidade (Plágio ZERO)</h2>
            <div style="background: rgba(239, 68, 68, 0.05); border-left: 4px solid #ef4444; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                <p style="color: #ef4444; font-weight: 700; margin-bottom: 0.5rem;">POLÃTICA DE TOLERÃ‚NCIA ZERO PARA PLÃGIO</p>
                <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6;">
                    A KALIYE utiliza sistemas avançados de auditoria por Inteligência Artificial para verificar a originalidade de cada projeto. 
                    Ao publicar uma ideia, o utilizador declara sob as penas da lei que é o autor original da mesma.
                </p>
            </div>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                Qualquer projeto identificado como plágio, cópia direta de ideias existentes ou conteúdo gerado sem valor acrescentado será:
                <ul style="margin-top: 10px; padding-left: 1.5rem;">
                    <li>Removido permanentemente da plataforma sem aviso prévio.</li>
                    <li>O autor terá o seu perfil marcado com um "Alerta de Integridade".</li>
                    <li>Em casos reincidentes, a conta será banida permanentemente.</li>
                </ul>
            </p>
        </section>
 
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">2. Proteção de Dados e Ideias</h2>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                A KALIYE compromete-se a proteger as informações estratégicas publicadas. Os investidores registados na plataforma assinam, 
                ao entrar, um termo de confidencialidade implícito. No entanto, recomendamos que não sejam publicados segredos industriais ou fórmulas 
                críticas antes de uma reunião direta com contrato de confidencialidade (NDA) assinado especificamente com o investidor.
            </p>
        </section>
 
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">3. Verificação por Vídeo (Pitch)</h2>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                A obrigatoriedade do vídeo de 2 a 5 minutos serve como prova de identidade e autoria. A recusa em fornecer o pitch visual 
                resultará na não aprovação do projeto para a visualização por investidores de elite.
            </p>
        </section>
 
        <section style="margin-bottom: 2.5rem;">
            <h2 style="color: white; font-size: 1.4rem; margin-bottom: 1rem;">4. Responsabilidade Financeira</h2>
            <p style="color: var(--text-secondary); line-height: 1.6;">
                A KALIYE facilita a conexão entre empreendedores e investidores. Não somos responsáveis pelo sucesso final do negócio ou 
                pelas decisões de investimento tomadas pelas partes. Todas as transações financeiras devem ser documentadas legalmente conforme as leis de Angola.
            </p>
        </section>
 
        <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 3rem 0;">
        
        <div style="text-align: center;">
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Ao utilizar a KALIYE, você concorda automaticamente com estes termos.</p>
            <a href="../../index.php" class="btn-primary" style="display: inline-block; margin-top: 1.5rem; text-decoration: none;">Entendido, Retornar</a>
        </div>
    </div>
</div>

<?php require_once '../../inclusoes/rodape.php'; ?>


