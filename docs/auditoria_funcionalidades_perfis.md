# Auditoria de funcionalidades por perfil

Data: 2026-05-26

## Perfis e direitos esperados

- Estudante: publicar ideias, comentar, votar, abrir duvidas, pedir mentoria, gerir perfil/KYC, conversar quando houver conexao ou mentoria ativa.
- Mentor: aceder a central de mentoria, gerir disponibilidade, recursos, avisos, tarefas, grupos e progresso de mentorados; so deve agir como mentor com `mentorship_status = approved` ou perfil `mentor`.
- Investidor: aceder ao dashboard de investimento, ver projectos publicos aprovados, iniciar propostas de investimento apenas com KYC verificado, assinar acordos e enviar comprovativos.
- Admin: gerir KYC, utilizadores, mentores, projectos, finanças, marketing, legal, moderação, suporte, logs e monitor de chat conforme permissoes RBAC.

## Corrigido nesta passada

- Compatibilidade com PHP 7.4: removido uso de `str_starts_with` e `str_contains` em `inclusoes/ChatSecurity.php` e `inclusoes/DeviceDetector.php`.
- CSRF: token global exposto no header e `fetch` do mesmo dominio passa a enviar `X-CSRF-Token` automaticamente em pedidos mutaveis.
- CSRF: validacao aplicada em acoes sensiveis de chat, conexoes, KYC, perfil, duvidas, comentarios, votos, likes, investimento e candidatura a mentor.
- RBAC admin: `isAdmin()` agora cobre `admin` e `superadmin`; endpoints/telas sensiveis foram alinhados com permissoes finas (`ads`, `users`, `finances`, `finance_docs`, `moderation`, `legal`, `settings`, `audit`, `mentor_assignment`).
- Dashboard do investidor: corrigidas rotas JS que apontavam para `../servicos/...`, inexistente neste projecto, para `../../interface_programacao/...`.
- Dashboard do investidor: corrigidos links internos para mensagens/perfil e paths de media carregada.
- Dashboard do investidor: evitado erro JavaScript quando pagamentos estao desativados e o modal de investimento nao e renderizado.
- Investimento: `interface_programacao/projects/invest_project.php` agora aceita JSON e FormData, exige perfil `investor`, KYC verificado, projecto publico/aprovado, impede investimento no proprio projecto e respeita minimo/maximo.
- Comprovativos: `interface_programacao/projects/upload_investment_proof.php` agora exige investidor, valida upload por MIME/tamanho e grava em `carregamentos/investments`.
- Notificacoes: `mark_notification_read.php` agora tambem aceita JSON com `project_id`, como o dashboard do investidor envia.
- Endpoint temporario: `debug_upload.php` ficou restrito a admin.
- Uploads: duvidas com imagem, comprovativos de investimento e CV de candidatura a mentor passaram a usar `Security::storeUploadedFile`.
- Erros internos: endpoints sensiveis deixam de devolver mensagens SQL/excecoes ao cliente; detalhes ficam no `error_log`.

## Lacunas restantes

- CSRF: ainda falta cobrir algumas paginas admin standalone que nao carregam o header global; usar `getCSRFHiddenInput()` nos formularios antigos.
- RBAC admin: a maior parte dos pontos sensiveis foi alinhada, mas ainda vale revisar exports antigos com ficheiros em encoding legado.
- Uploads antigos: ainda existem uploads por extensao em anuncios, legal e alguns fluxos grandes de projecto. Recomendado migrar todos para `Security::storeUploadedFile`.
- Exposicao de erros: foi reduzida nos fluxos sensiveis tocados; ainda existem endpoints legados para limpar em passada dedicada.
- Pagamentos: `pagamentos.php` esta com `payments_enabled = false`; por isso o fluxo real de investimento fica bloqueado por configuracao, mesmo com os bugs corrigidos.
- Codificacao: ha muitos textos com caracteres corrompidos (`Ã...`). Recomendado normalizar encoding para UTF-8.

## Verificacao executada

- `php -l` nos ficheiros alterados.
- `node --check recursos/js/pages/investor_dashboard.js`.
- Busca por funcoes PHP 8 restantes em codigo PHP principal.
