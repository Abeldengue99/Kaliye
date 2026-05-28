<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- LEGAL MODAL ELITE — TERMOS & PRIVACIDADE                       -->
<!-- Design Glassmorphism Premium com Suporte a Impressão           -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<?php
$legal_base_url = $base_url ?? './';
$legal_is_guest = empty($_SESSION['user_id']);
?>
<div id="legalModal" class="elite-modal-overlay" style="display: none; z-index: 99999; position: fixed; top: 0; left: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px);">
    <div class="elite-modal-card" style="max-width: 800px; padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; background: #0d1628; border: 1px solid rgba(255,255,255,0.05); border-radius: 24px; box-shadow: 0 30px 60px rgba(0,0,0,0.5);">
        
        <!-- Cabeçalho do Modal -->
        <div class="legal-modal-header" style="padding: 2.5rem; background: linear-gradient(to right, rgba(247,148,29,0.05), transparent); border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 1.25rem;">
                <div id="legalModalIcon" style="width: 50px; height: 50px; background: rgba(247,148,29,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(247,148,29,0.2);">
                    <i class="fas fa-file-contract" style="color: #f7941d; font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h2 id="legalModalTitle" style="margin: 0; color: #fff; font-size: 1.5rem; font-weight: 800; font-family: 'Outfit', sans-serif;">Dossiê Legal</h2>
                    <p id="legalModalSubtitle" style="margin: 0; color: rgba(255,255,255,0.4); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">KALIYE • v1.0</p>
                </div>
            </div>
            <button onclick="closeLegalModal()" style="background: rgba(255,255,255,0.05); border: none; color: #fff; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Corpo com Scroll -->
        <div id="legalModalBody" class="legal-print-area" style="padding: 2.5rem; overflow-y: auto; flex-grow: 1; scroll-behavior: smooth; color: rgba(255,255,255,0.8); font-size: 0.95rem; line-height: 1.7;">
            <!-- O conteúdo será injetado via JS -->
            <div style="text-align: center; padding: 4rem; opacity: 0.3;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 1rem;">Sincronizando diretrizes...</p>
            </div>
        </div>

        <!-- Rodapé de Acções -->
        <div class="legal-modal-footer" style="padding: 1.5rem 2.5rem; border-top: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2); display: flex; justify-content: flex-end; gap: 1rem;">
            <button onclick="downloadLegalPDF()" style="height: 50px; padding: 0 2rem; border-radius: 14px; font-weight: 800; display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; transition: 0.3s;" onmouseover="this.style.borderColor='#f7941d'; this.style.color='#f7941d'">
                <i class="fas fa-download"></i> BAIXAR EM PDF
            </button>
            <?php if ($legal_is_guest): ?>
                <a href="<?php echo $legal_base_url; ?>autenticacao/entrar.php" class="legal-quick-link legal-quick-secondary">
                    <i class="fas fa-right-to-bracket"></i> ENTRAR
                </a>
                <a href="<?php echo $legal_base_url; ?>autenticacao/registar.php" class="legal-quick-link legal-quick-primary">
                    <i class="fas fa-user-plus"></i> CRIAR CONTA
                </a>
            <?php endif; ?>
            <button onclick="closeLegalModal()" style="height: 50px; padding: 0 2rem; border-radius: 14px; font-weight: 800; background: #f7941d; color: #fff; border: none; cursor: pointer; box-shadow: 0 10px 20px rgba(247,148,29,0.2);">
                COMPREENDIDO
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos para impressão focados em beleza e clareza */
@media print {
    body * { visibility: hidden; }
    .legal-print-area, .legal-print-area * { visibility: visible; }
    .legal-print-area { 
        position: absolute; left: 0; top: 0; width: 100%; color: #000 !important; background: #fff !important; padding: 2cm !important; 
        font-family: 'Inter', sans-serif !important;
    }
    .legal-print-area h2 { color: #f7941d !important; margin-top: 1.5rem !important; margin-bottom: 0.75rem !important; }
    .legal-print-area .trust-note { background: #f8f9fa !important; border-left: 5px solid #ef4444 !important; color: #333 !important; }
}

.legal-section-card {
    background: rgba(255,255,255,0.01);
    border: 1px solid rgba(255,255,255,0.03);
    padding: 1.75rem;
    border-radius: 18px;
    margin-bottom: 2rem;
    transition: 0.3s ease;
}

.legal-section-card:hover {
    background: rgba(255,255,255,0.02);
    border-color: rgba(247,148,29,0.2);
}

.legal-section-title {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 800;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.legal-section-title i {
    color: #f7941d;
    font-size: 0.9rem;
    opacity: 0.7;
}

.legal-section-card p {
    margin: 0 0 0.9rem;
}

.legal-section-card p:last-child {
    margin-bottom: 0;
}

.trust-note {
    background: rgba(239, 68, 68, 0.05); 
    border-left: 4px solid #ef4444; 
    padding: 1.5rem; 
    border-radius: 12px; 
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.trust-note strong { color: #ef4444; text-transform: uppercase; letter-spacing: 1px; }

#legalModalBody::-webkit-scrollbar { width: 6px; }
#legalModalBody::-webkit-scrollbar-track { background: transparent; }
#legalModalBody::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

.legal-quick-link {
    height: 50px;
    padding: 0 1.4rem;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-size: 0.82rem;
    font-weight: 900;
    text-decoration: none;
    white-space: nowrap;
    transition: 0.25s ease;
}

.legal-quick-link:hover {
    transform: translateY(-2px);
}

.legal-quick-primary {
    background: #f7941d;
    color: #fff;
    box-shadow: 0 10px 20px rgba(247,148,29,0.2);
}

.legal-quick-secondary {
    background: rgba(255,255,255,0.05);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.1);
}

@media (max-width: 760px) {
    .legal-modal-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .legal-modal-footer > * {
        width: 100%;
    }
}
</style>

<script>
window.openLegalModal = function(type) {
    console.log("Abrindo Modal Legal: " + type);
    const modal = document.getElementById('legalModal');
    const title = document.getElementById('legalModalTitle');
    const icon = document.getElementById('legalModalIcon');
    const body = document.getElementById('legalModalBody');
    
    if(!modal || !body) {
        console.error("Modal ou corpo do modal legal não encontrado!");
        return;
    }

    modal.style.display = 'flex';
    body.scrollTop = 0;

    if (type === 'termos') {
        title.innerHTML = 'Termos e Condições';
        icon.innerHTML = '<i class="fas fa-file-contract" style="color:#f7941d; font-size:1.5rem;"></i>';
        body.innerHTML = `
            <div class="trust-note">
                <strong>Nota importante</strong><br>
                Estes Termos regulam a utilização da KALIYE como ecossistema digital de educação, mentoria, comunidade, projectos, oportunidades e intermediação tecnológica. O texto deve ser lido integralmente antes da criação de conta, publicação de conteúdo, candidatura, mentoria, investimento ou utilização da carteira digital.
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">1. Aceitação e âmbito da plataforma</div>
                <p>Ao aceder, criar conta ou utilizar a KALIYE, o utilizador declara que leu, compreendeu e aceita estes Termos e Condições, a Política de Privacidade, as regras comunitárias e quaisquer políticas específicas aplicáveis a mentoria, projectos, carteira digital, anúncios, pagamentos e verificação de identidade.</p>
                <p>A KALIYE é uma plataforma tecnológica que aproxima estudantes, profissionais, mentores, investidores, instituições, anunciantes e outros participantes. A plataforma facilita contactos, visibilidade, organização de informação, comunicação, candidaturas, pagamentos e gestão de oportunidades, mas não substitui aconselhamento jurídico, financeiro, académico, fiscal, médico ou profissional especializado.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">2. Elegibilidade, conta e responsabilidade do utilizador</div>
                <p>O utilizador deve fornecer informações verdadeiras, actuais e completas. É proibido criar contas falsas, utilizar identidade de terceiros, ocultar informação essencial, manipular perfis, burlar verificações, partilhar credenciais ou usar a conta para fins ilícitos.</p>
                <p>O utilizador é responsável por todas as actividades realizadas na sua conta, incluindo publicações, mensagens, candidaturas, propostas, uploads, transacções e interacções com outros membros. Em caso de suspeita de acesso indevido, deve alterar a palavra-passe e comunicar a KALIYE imediatamente.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">3. Verificação de identidade, KYC e segurança</div>
                <p>Determinadas funcionalidades exigem verificação de identidade, incluindo publicação avançada de projectos, candidaturas sensíveis, investimento, carteira, levantamento, mentoria formal, acesso a informação restrita ou participação em fluxos de maior risco. A KALIYE pode solicitar BI, passaporte, fotografia, contacto, comprovativos, dados bancários ou informação complementar necessária para validação.</p>
                <p>A utilização de documentos falsos, adulterados, pertencentes a terceiros ou obtidos de forma ilícita pode resultar em bloqueio, eliminação de conta, retenção preventiva de operações, comunicação às autoridades competentes e outras medidas permitidas por lei.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">4. Conduta comunitária e conteúdos proibidos</div>
                <p>O utilizador compromete-se a tratar os demais membros com respeito, transparência e boa-fé. São proibidos assédio, discriminação, ameaças, difamação, discurso de ódio, fraude, spam, phishing, engenharia social, manipulação de métricas, propostas enganosas, cobrança abusiva, conteúdo sexual explícito, violência gráfica, propaganda ilegal, violação de propriedade intelectual e qualquer actividade contrária à lei ou à ética da comunidade.</p>
                <p>A KALIYE pode moderar, ocultar, remover, sinalizar ou suspender conteúdos e contas que violem estes Termos, prejudiquem a confiança da comunidade ou exponham utilizadores a risco.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">5. Projectos, projectos e propriedade intelectual</div>
                <p>O utilizador mantém a titularidade dos seus projectos, projectos, textos, imagens, vídeos, pitch decks, relatórios, documentos e demais conteúdos que publicar, salvo acordo escrito em contrário. Ao submeter conteúdo à KALIYE, concede à plataforma uma licença limitada, não exclusiva e necessária para alojar, apresentar, organizar, processar, moderar, promover e disponibilizar esse conteúdo dentro do ecossistema.</p>
                <p>O utilizador garante que tem direito de publicar o conteúdo enviado e que este não viola direitos de terceiros. A KALIYE pode utilizar mecanismos manuais, automáticos ou assistidos por IA para detectar plágio, abuso, risco, violação de regras ou inconsistências.</p>
                <p>Publicações públicas podem ser vistas por outros membros. Documentos sensíveis ou detalhes privados devem ser partilhados apenas quando necessário e com atenção ao nível de visibilidade escolhido.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">6. Mentoria, sessões, recursos e responsabilidades</div>
                <p>A KALIYE facilita a ligação entre mentores e mentorados, gestão de disponibilidade, salas, tarefas, recursos, masterclasses, grupos e acompanhamento. Mentores devem agir com diligência, confidencialidade, respeito e honestidade sobre a sua experiência. Mentorados devem cumprir tarefas, horários, regras de participação e compromissos assumidos.</p>
                <p>A KALIYE não garante resultados específicos, aprovação de projectos, contratação, financiamento, bolsas, retorno financeiro ou evolução profissional. A qualidade dos resultados depende também da actuação dos participantes, contexto do mercado e factores externos.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">7. Investimentos, oportunidades e ausência de garantia financeira</div>
                <p>A KALIYE pode permitir que investidores conheçam projectos, que empreendedores apresentem projectos e que membros sinalizem interesse em colaboração ou financiamento. Qualquer investimento, parceria, contrato, promessa, proposta, participação societária, comissão, retorno, doação ou pagamento entre utilizadores deve ser avaliado com prudência e, quando aplicável, formalizado por contrato próprio.</p>
                <p>A KALIYE não é banco, corretora, consultora financeira, sociedade de investimento, seguradora ou entidade de garantia de retorno. Informações apresentadas por utilizadores não constituem recomendação de investimento. Cada participante assume a responsabilidade de fazer a sua própria análise, diligência, validação documental, fiscal, jurídica e financeira.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">8. Carteira digital, pagamentos, comissões e levantamentos</div>
                <p>Funcionalidades de carteira, pagamentos, comissões, depósitos, comprovativos e levantamentos devem ser utilizadas apenas para fins permitidos pela plataforma. A KALIYE pode solicitar validações adicionais, bloquear temporariamente operações suspeitas, corrigir erros, reter transacções para análise e rejeitar pedidos incompatíveis com as regras internas, prevenção de fraude ou legislação aplicável.</p>
                <p>Taxas, prazos, limites, métodos de pagamento, comissões e regras de levantamento podem variar conforme configuração da plataforma, parceiros de pagamento, verificação do utilizador e risco operacional. O utilizador é responsável por fornecer dados bancários correctos.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">9. Anúncios, oportunidades patrocinadas e comunicações</div>
                <p>A plataforma pode apresentar anúncios, campanhas, comunicados, newsletters, destaques e oportunidades patrocinadas. A KALIYE pode rastrear métricas de visualização e clique para fins de desempenho, segurança, prestação de contas e melhoria do serviço.</p>
                <p>Anunciantes e parceiros são responsáveis pela veracidade das informações que submetem. A KALIYE pode remover campanhas enganosas, ilegais, abusivas ou incompatíveis com a missão da plataforma.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">10. Limitação de responsabilidade</div>
                <p>Na medida permitida por lei, a KALIYE não se responsabiliza por perdas decorrentes de decisões tomadas exclusivamente com base em conteúdo publicado por terceiros, acordos fora da plataforma, promessas não formalizadas, erros de utilizador, indisponibilidade temporária, falhas de internet, actos de terceiros, caso fortuito, força maior ou utilização indevida da conta.</p>
                <p>A KALIYE empenha-se em manter a plataforma segura e funcional, mas não garante disponibilidade ininterrupta, ausência absoluta de erros ou compatibilidade permanente com todos os dispositivos.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">11. Suspensão, eliminação e medidas de protecção</div>
                <p>A KALIYE pode advertir, limitar, suspender, bloquear ou eliminar contas e conteúdos em caso de violação destes Termos, risco à comunidade, suspeita de fraude, ordem de autoridade competente, uso abusivo, inactividade relevante ou necessidade de protecção operacional.</p>
                <p>Quando razoável, a KALIYE poderá permitir esclarecimentos ou recurso interno. Determinadas situações graves podem exigir actuação imediata sem aviso prévio.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">12. Alterações aos Termos e lei aplicável</div>
                <p>A KALIYE pode actualizar estes Termos para reflectir novas funcionalidades, mudanças legais, melhorias de segurança ou evolução do modelo de negócio. A versão vigente será disponibilizada na plataforma, com indicação de versão.</p>
                <p>Estes Termos são orientados pelo ordenamento jurídico da República de Angola. Conflitos devem ser resolvidos preferencialmente por contacto directo e boa-fé; se necessário, poderão ser submetidos às entidades competentes em Angola.</p>
            </div>
        `;
    } else {
        title.innerHTML = 'Política de Privacidade';
        icon.innerHTML = '<i class="fas fa-user-shield" style="color:#f7941d; font-size:1.5rem;"></i>';
        body.innerHTML = `
            <div class="trust-note">
                <strong>Compromisso de privacidade</strong><br>
                A KALIYE trata dados pessoais com base em princípios de transparência, finalidade legítima, minimização, segurança e respeito pela vida privada, em alinhamento com a Lei n.º 22/11, de 17 de Junho, Lei da Protecção de Dados Pessoais de Angola, e orientações da Agência de Protecção de Dados.
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">1. Quem é responsável pelo tratamento</div>
                <p>A KALIYE é responsável pelo tratamento dos dados pessoais recolhidos e utilizados no âmbito da plataforma, incluindo dados fornecidos no registo, autenticação, perfil, KYC, mentoria, projectos, carteira, suporte, anúncios, newsletter e interacções comunitárias.</p>
                <p>Para questões de privacidade, suporte ou exercício de direitos, o utilizador pode contactar a equipa através dos canais disponibilizados na plataforma, incluindo o centro de suporte.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">2. Dados que podemos recolher</div>
                <p>Podemos recolher dados de identificação e contacto, como nome, email, telefone, data de nascimento, tipo de utilizador, instituição, área de estudo, especialidade, localização, biografia, fotografia de perfil, documentos de identificação, selfie, CV, dados bancários ou IBAN, comprovativos de pagamento, mensagens, notificações, projectos, comentários, ficheiros, candidaturas, avaliações, disponibilidade de mentoria, preferências e definições de conta.</p>
                <p>Também podemos recolher dados técnicos e de segurança, como endereço IP, dispositivo, navegador, registos de acesso, actividade, cookies ou identificadores semelhantes, métricas de anúncios, tentativas de login e sinais de risco.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">3. Finalidades do tratamento</div>
                <p>Tratamos dados para criar e gerir contas, verificar identidade, proteger a comunidade, permitir login seguro, apresentar perfis, publicar projectos, ligar mentores e mentorados, gerir grupos, mensagens, tarefas e recursos, processar carteira e pagamentos, responder a suporte, prevenir fraude, cumprir obrigações legais, melhorar a plataforma e personalizar a experiência.</p>
                <p>Também podemos usar dados para comunicações administrativas, avisos de segurança, newsletters consentidas, anúncios, relatórios internos, estatísticas agregadas, auditoria, moderação e desenvolvimento de funcionalidades.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">4. Bases de legitimidade</div>
                <p>O tratamento pode assentar no consentimento do titular, execução de contrato ou diligências pré-contratuais, cumprimento de obrigações legais, interesse legítimo da KALIYE em manter segurança, integridade e melhoria do serviço, prevenção de fraude, defesa de direitos e protecção dos utilizadores.</p>
                <p>Quando o tratamento depender de consentimento, o utilizador poderá retirá-lo, sem prejuízo da licitude do tratamento realizado antes da retirada e de dados que devam ser conservados por exigência legal, segurança ou defesa de direitos.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">5. Partilha de dados</div>
                <p>A KALIYE não vende dados pessoais. Podemos partilhar dados apenas quando necessário para operação da plataforma, por exemplo com prestadores de alojamento, email, SMS, processamento de pagamentos, ferramentas de segurança, análise técnica, suporte, parceiros envolvidos numa oportunidade, mentores, investidores ou administradores autorizados.</p>
                <p>Também poderemos divulgar dados quando exigido por lei, autoridade competente, ordem judicial, investigação de fraude, protecção de direitos, segurança dos utilizadores ou cumprimento de obrigações regulatórias.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">6. Visibilidade dentro da comunidade</div>
                <p>Alguns dados são naturalmente visíveis conforme a funcionalidade utilizada: nome, foto, tipo de perfil, biografia, projectos públicos, comentários, avaliações, competências, disponibilidade, grupos e mensagens enviadas aos destinatários. O utilizador deve evitar publicar dados sensíveis, segredos comerciais ou informação confidencial em áreas públicas.</p>
                <p>Investidores, mentores, administradores e outros perfis podem ter acesso diferenciado a conteúdos conforme regras de verificação, permissões e finalidade da interacção.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">7. Dados sensíveis, documentos e KYC</div>
                <p>Documentos de identidade, selfies, comprovativos, dados bancários e outros elementos de verificação são tratados com acesso restrito e finalidade de segurança, prevenção de fraude, validação de identidade, cumprimento de obrigações legais e controlo de risco.</p>
                <p>Estes dados não devem ser partilhados publicamente. A KALIYE aplica medidas administrativas, técnicas e organizacionais para limitar o acesso apenas a pessoas autorizadas e finalidades legítimas.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">8. Cookies, métricas e tecnologias semelhantes</div>
                <p>A plataforma pode utilizar cookies, armazenamento local e tecnologias semelhantes para manter sessão, proteger acesso, lembrar preferências, medir desempenho, compreender utilização, rastrear métricas de anúncios e melhorar a experiência.</p>
                <p>O utilizador pode gerir cookies no navegador, mas certas funcionalidades podem deixar de funcionar correctamente caso cookies essenciais sejam bloqueados.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">9. Segurança e conservação</div>
                <p>A KALIYE adopta medidas de protecção compatíveis com a natureza dos dados tratados, incluindo controlo de acessos, registos, encriptação quando aplicável, segregação de permissões, validações e monitorização. Nenhum sistema digital é absolutamente imune a incidentes, mas a KALIYE compromete-se a actuar de forma diligente perante riscos ou violações.</p>
                <p>Os dados são conservados pelo tempo necessário às finalidades que justificaram a recolha, cumprimento de obrigações legais, prevenção de fraude, auditoria, resolução de disputas, segurança e defesa de direitos. Quando já não forem necessários, poderão ser eliminados, anonimizados ou arquivados de forma restrita.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">10. Direitos do titular dos dados</div>
                <p>Nos termos da legislação aplicável, o titular pode solicitar acesso, rectificação, actualização, eliminação, oposição, limitação, informação sobre tratamento e, quando aplicável, retirada de consentimento. Algumas solicitações podem exigir prova de identidade e avaliação de obrigações legais ou interesses legítimos que impeçam eliminação imediata.</p>
                <p>A eliminação de dados essenciais de identidade, segurança ou conta pode limitar ou impossibilitar a utilização da plataforma.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">11. Menores, instituições e perfis académicos</div>
                <p>A KALIYE é orientada a crescimento académico e profissional. Utilizadores menores de idade devem utilizar a plataforma apenas quando permitido pela lei aplicável e, se necessário, com autorização ou supervisão adequada. Instituições e representantes devem garantir que têm legitimidade para gerir dados de estudantes, equipas ou membros.</p>
            </div>

            <div class="legal-section-card">
                <div class="legal-section-title">12. Transferências, actualizações e contacto</div>
                <p>Quando serviços técnicos ou fornecedores estiverem localizados fora de Angola, a KALIYE procurará assegurar salvaguardas adequadas para protecção dos dados, em conformidade com a legislação aplicável.</p>
                <p>Esta Política pode ser actualizada para reflectir novas funcionalidades, requisitos legais ou melhorias de segurança. A versão vigente estará disponível na plataforma. Dúvidas ou pedidos devem ser enviados pelos canais de suporte oficiais.</p>
            </div>
        `;
    }
};

window.closeLegalModal = function() {
    document.getElementById('legalModal').style.display = 'none';
};

window.downloadLegalPDF = function() {
    window.print();
};
</script>
