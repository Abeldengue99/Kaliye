---
description: Regras de organização de arquivos do projeto KALIYE
---

# Organização de Arquivos - KALIYE

## Regra Principal
**NUNCA criar arquivos soltos na raiz do projeto.** Todos os arquivos devem estar dentro das pastas apropriadas.

## Arquivos permitidos na raiz (APENAS estes):
- `index.php` — Ponto de entrada da aplicação
- `.htaccess` — Configuração do Apache
- `.gitignore` — Controle de versão
- `manifest.json` — Configuração PWA (obrigatório na raiz)
- `sw.js` — Service Worker PWA (obrigatório na raiz)
- `README.md` — Documentação principal

## Estrutura de Pastas

| Tipo de Arquivo | Pasta de Destino |
|---|---|
| Scripts de debug/verificação (`check_*.php`, `debug_*.php`) | `scripts/debug/` |
| Scripts de teste (`test_*.php`) | `scripts/debug/` |
| Scripts de migração de BD | `scripts/migrations/` |
| Scripts utilitários | `scripts/utils/` |
| Documentação `.md` (guias, análises) | `docs/` |
| Esquemas de BD (`.sql`, `.txt`, `.dbml`) | `database/` |
| Páginas da aplicação | `pages/` |
| APIs/endpoints | `api/{módulo}/` |
| Componentes PHP reutilizáveis | `includes/components/` |
| Arquivos CSS | `assets/css/pages/` |
| Arquivos JavaScript | `assets/js/pages/` |
| Imagens e media | `assets/images/` |
| Ficheiros de configuração PHP | `config/` |
| Uploads de utilizadores | `uploads/` |
| Backups | `backups/` |
| Páginas de administração | `admin/` |
| Páginas de autenticação | `auth/` |
| Ficheiros de idioma | `languages/` |

## Ao criar novos arquivos:
1. Identificar o tipo/propósito do arquivo
2. Colocá-lo na pasta correspondente da tabela acima
3. Se necessário, criar uma subpasta dentro da pasta de destino
4. **Nunca** deixar arquivos temporários, de debug ou de teste na raiz

## Ao finalizar uma tarefa:
1. Verificar se algum arquivo foi criado na raiz por engano
2. Mover imediatamente para a pasta correta
3. Confirmar que o funcionamento não foi afectado
