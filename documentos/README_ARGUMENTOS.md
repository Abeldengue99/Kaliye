# Scripts de Manutenção e Debug

Esta pasta contém scripts auxiliares para manutenção da base de dados e debug.

## ⚠️ Importante
Estes scripts **NÃO** devem ser executados em produção sem supervisão.

## 📋 Scripts Disponíveis

### Verificação
- `check_ads.php` - Verifica conteúdo da tabela de anúncios
- `check_tables.php` - Lista todas as tabelas da BD
- `check_users.php` - Verifica utilizadores registados
- `count_projects.php` - Conta total de projetos
- `verify_db.php` - Validação geral da base de dados

### Correção de Schema
- `db_fix.php` - Cria tabela `project_media` se não existir
- `msg_fix.php` - Adiciona coluna `is_read` à tabela `messages`
- `users_fix.php` - Adiciona colunas `profile_pic` e `academic_info` a `users`
- `fix_admin.php` - Correções específicas do painel admin

## 🔧 Como Usar

1. Aceda via navegador:
```
http://localhost/Aksanti%20Mentorship/scripts/nome_do_script.php
```

2. Ou via CLI:
```bash
php scripts/nome_do_script.php
```

## 🗑️ Limpeza

Após executar os scripts de correção (`*_fix.php`), eles podem ser removidos se a BD estiver correta.

---
**Nota:** Sempre faça backup da base de dados antes de executar scripts de correção!
