# 🔄 Sincronização de Dados - KALIYE

## 📋 Scripts Disponíveis

### 1. **verify_users_table.php** - Verificador da Tabela Users
Diagnóstico da estrutura da tabela `users` e validação de colunas.

**Uso:**
```
http://seu-dominio.com/aksanti/argumentos/verify_users_table.php
```

**O que faz:**
- ✅ Verifica se tabela `users` existe
- ✅ Lista todas as colunas
- ✅ Procura por anomalias (coluna `id` em vez de `user_id`)
- ✅ Testa uma query simples
- ✅ Mostra estatísticas

---

### 2. **fix_users_table.php** - Corretor da Tabela Users
Corrige problemas estruturais na tabela `users`.

**Modo Relatório (Seguro):**
```
http://seu-dominio.com/aksanti/argumentos/fix_users_table.php?mode=report
```

**Modo Correção (Perigoso):**
```
http://seu-dominio.com/aksanti/argumentos/fix_users_table.php?mode=fix&confirm=yes
```

**Correções possíveis:**
- 🔧 Renomeia coluna `id` → `user_id`
- 🔧 Remove coluna `id` redundante se ambas existem

**⚠️ CUIDADO:** Faça backup antes de executar!

---

### 3. **sync_database.php** - Sincronização de Banco de Dados
Sincroniza e otimiza a base de dados.

**Modo Análise (Seguro):**
```
http://seu-dominio.com/aksanti/argumentos/sync_database.php?mode=report
```

**Operações disponíveis:**
```
?mode=repair&confirm=yes      # Remove registos órfãos
?mode=rebuild&confirm=yes     # Reconstrói índices
?mode=analyze&confirm=yes     # Analisa tabelas
?mode=all&confirm=yes         # Executa tudo
```

**O que faz:**
- ✅ Verifica integridade de tabelas críticas
- ✅ Valida Foreign Keys
- ✅ Procura por registos órfãos
- ✅ Reconstrói índices
- ✅ Otimiza tabelas

---

### 4. **sync_data.php** - Sincronização Completa
Sincroniza desenvolvimento com produção.

**Modo Análise:**
```
http://seu-dominio.com/aksanti/argumentos/sync_data.php?mode=report
```

**Sincronizar tudo:**
```
http://seu-dominio.com/aksanti/argumentos/sync_data.php?mode=all&confirm=yes
```

**Operações:**
- 📁 Sincroniza arquivos PHP
- 🗄️ Verifica banco de dados
- 🗑️ Limpa cache

---

## 🚀 Fluxo Recomendado

### Passo 1: Diagnóstico Inicial
```
1. Verificar: argumentos/verify_users_table.php
2. Se OK → Vá para Passo 3
3. Se problema → Vá para Passo 2
```

### Passo 2: Corrigir Tabela (Se Necessário)
```
1. Analisar: argumentos/fix_users_table.php?mode=report
2. Se OK → Vá para Passo 3
3. Se há problemas:
   - Fazer BACKUP do banco de dados
   - Executar: argumentos/fix_users_table.php?mode=fix&confirm=yes
```

### Passo 3: Sincronizar Banco de Dados
```
1. Analisar: argumentos/sync_database.php?mode=report
2. Se há registos órfãos ou índices ruins:
   - Executar: argumentos/sync_database.php?mode=all&confirm=yes
```

### Passo 4: Sincronizar Arquivos (Opcional)
```
1. Se desenvolvendo em máquina local:
   - Executar: argumentos/sync_data.php?mode=all&confirm=yes
2. Isto copia arquivos de desenvolvimento para produção
```

### Passo 5: Limpar e Testar
```
1. Limpar cache do navegador: Ctrl+Shift+Delete
2. Recarregar página: Ctrl+F5
3. Testar funcionalidade de mentorados no chat
4. Se erro persiste → Ir para Passo 1
```

---

## 🔧 Exemplos de Uso

### Exemplo 1: Problema Identificado (Coluna 'id' em vez de 'user_id')
```
1. http://localhost/aksanti/argumentos/verify_users_table.php
   → Resultado: ❌ Coluna 'id' encontrada

2. Backup: CREATE TABLE users_backup AS SELECT * FROM users;

3. http://localhost/aksanti/argumentos/fix_users_table.php?mode=report
   → Resultado: Renomear 'id' → 'user_id'

4. http://localhost/aksanti/argumentos/fix_users_table.php?mode=fix&confirm=yes
   → Resultado: ✅ Coluna renomeada

5. http://localhost/aksanti/argumentos/verify_users_table.php
   → Resultado: ✅ Tudo OK!
```

### Exemplo 2: Sincronização Completa
```
1. http://localhost/aksanti/argumentos/sync_data.php?mode=report
   → Analisa tudo

2. http://localhost/aksanti/argumentos/sync_data.php?mode=all&confirm=yes
   → Sincroniza tudo

3. Limpar cache e testar
```

### Exemplo 3: Otimizar Banco de Dados
```
1. http://localhost/aksanti/argumentos/sync_database.php?mode=report
   → Analisa integridade

2. http://localhost/aksanti/argumentos/sync_database.php?mode=all&confirm=yes
   → Repara tudo
```

---

## 📊 O Que Cada Script Faz

| Script | Função | Segurança | Tempo |
|--------|--------|-----------|-------|
| verify_users_table.php | Diagnóstico da tabela users | ✅ Seguro (leitura) | <5s |
| fix_users_table.php | Corrige estrutura da tabela | ⚠️ Perigoso | <10s |
| sync_database.php | Otimiza e valida BD | ⚠️ Moderado | 10-30s |
| sync_data.php | Sincroniza dev/prod | ✅ Seguro | <5s |

---

## ⚠️ Avisos Importantes

### Backup Obrigatório
Antes de usar `fix_users_table.php` ou `sync_database.php?mode=repair`:

```sql
CREATE TABLE users_backup_20260601 AS SELECT * FROM users;
```

### Desfazer Mudanças
Se algo correu mal:

```sql
DROP TABLE users;
ALTER TABLE users_backup_20260601 RENAME TO users;
```

### Cuidado com Produção
- ✅ `verify_users_table.php` - Seguro para qualquer altura
- ⚠️ `fix_users_table.php` - Usar fora de horários de pico
- ⚠️ `sync_database.php?mode=repair` - Usar com cuidado
- ✅ `sync_data.php` - Seguro

---

## 🐛 Troubleshooting

### Erro: "Coluna 'id' não existe"
```
1. Executar verify_users_table.php
2. Se encontrar 'id', executar fix_users_table.php?mode=fix&confirm=yes
3. Recarregar navegador (Ctrl+F5)
```

### Erro: "Foreign Key violation"
```
1. Executar sync_database.php?mode=report
2. Se houver órfãos, executar sync_database.php?mode=repair&confirm=yes
3. Testar novamente
```

### Erro: "Arquivo não sincronizado"
```
1. Executar sync_data.php?mode=report
2. Se arquivos ausentes, executar sync_data.php?mode=files&confirm=yes
3. Recarregar servidor PHP
```

---

## 📞 Suporte

Se persistir erro após seguir estes passos:

1. ✅ Executou verify_users_table.php?
2. ✅ Fez backup antes de corrigir?
3. ✅ Recarregou o navegador (Ctrl+F5)?
4. ✅ Limpou cache (/tmp, /var/cache)?

Se ainda houver problema, contacte o administrador com:
- Output de `verify_users_table.php`
- Output de `sync_database.php?mode=report`
- Logs do PostgreSQL

---

**Última atualização:** 1 de junho de 2026
**Versão:** 1.0
