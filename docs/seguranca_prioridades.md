# Seguranca KALIYE: estado e prioridades

## Ja existe no codigo
- Prepared statements via PDO na maior parte dos endpoints.
- Hash de senhas com `password_hash` e validacao com `password_verify`.
- Verificacao de e-mail por OTP.
- 2FA opcional para utilizadores.
- CSRF helpers em `inclusoes/auth_check.php`.
- Rate limiting progressivo em login, registo, OTP, pagamentos e API geral.
- Logs de login com IP, dispositivo e localizacao aproximada.
- RBAC administrativo com permissoes granulares.
- KYC manual com documentos, selfie e fila de aprovacao.
- Headers basicos de seguranca: CSP, HSTS em HTTPS, X-Frame-Options, nosniff.

## Implementado agora
- Regeneracao de sessao apos login bem-sucedido.
- `last_auth_at` para exigir autenticacao recente em acoes sensiveis.
- Fingerprint basico do dispositivo na sessao.
- Score inicial de risco de login por mudanca de pais, cidade, IP e user-agent.
- Upload seguro centralizado com MIME real, extensoes permitidas, tamanho maximo e nomes aleatorios.
- KYC com autenticacao recente e validacao forte de ficheiros.
- Mensagens com upload de midia validado e separado por utilizador/mes.

## Prioridade 1: colocar antes de escalar usuarios
- Remover segredos do codigo e usar variaveis de ambiente ou cofre de segredos.
- Aplicar o helper de upload seguro em todos os endpoints com `move_uploaded_file`.
- Exigir CSRF em todos os POSTs autenticados.
- Fechar sessoes antigas e invalidar sessoes em mudanca de senha, 2FA e suspeita de risco.
- Ativar WAF/CDN com rate limit na borda, de preferencia Cloudflare.
- Scan de dependencias e segredos no pipeline.

## Prioridade 2: seguranca antifraude e trust
- Trust Score por usuario usando KYC, denuncias, avaliacoes, historico e comportamento.
- Device fingerprint persistente com tabela de dispositivos confiaveis.
- Step-up auth automatico quando o login tiver score de risco alto.
- Anti-bot com Cloudflare Turnstile em registo, login, recuperar senha e formularios publicos.
- Threat intelligence para IPs de proxy/VPN/botnet.
- Moderacao e score de golpes com IA para mensagens, projetos e investimentos.

## Prioridade 3: arquitetura e operacoes
- SIEM com Wazuh ou equivalente para logs de app, servidor e base de dados.
- Backups imutaveis e teste de restauracao.
- Pentests trimestrais e DAST automatizado.
- Separacao futura por servicos: auth, pagamentos, mensagens, uploads e IA.
- Criptografia end-to-end em mensagens, quando o produto exigir privacidade forte.

## Itens que ainda nao fazem sentido implementar direto no codigo PHP atual
- Honeypots inteligentes: melhor na borda/WAF ou em servicos isolados.
- Microservicos: e uma decisao de arquitetura, nao uma correcao rapida.
- SIEM, WAF, backups imutaveis e Vault: dependem da infraestrutura de producao.
- KYC facial/liveness real: precisa de fornecedor externo.
