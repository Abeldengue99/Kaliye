# 📊 GUIA COMPLETO: ANÁLISE E LIMPEZA DE TABELAS

## 🎯 Objetivo

Analisar completamente a base de dados PostgreSQL para:
- ✅ Identificar **tabelas duplicadas/redundantes**
- ✅ Localizar **tabelas órfãs** (sem uso no código)
- ✅ Encontrar **tabelas vazias** (sem dados)
- ✅ Calcular **espaço perdido**
- ✅ Gerar **recomendações de limpeza**

---

## 🚀 FERRAMENTAS CRIADAS

### 1️⃣ [analise_tabelas_database.php](analise_tabelas_database.php)
**Análise Completa de Tabelas**

- 📋 Lista TODAS as tabelas com tamanho e registos
- 📊 Ordena por tamanho para identificar "pesadas"
- 🚨 Destaca tabelas vazias
- 🔄 Detecta duplicatas por nome e estrutura
- 📝 Mostra colunas, tipos de dados e foreign keys
- 🔍 Procura referências no código PHP

**Acesso:**
```
http://localhost/aksanti/argumentos/analise_tabelas_database.php
```

### 2️⃣ [comparador_tabelas.php](comparador_tabelas.php)
**Comparador Detalhado**

- 🔍 Compara estrutura entre 2 tabelas
- 📊 Calcula % de similaridade
- ⚠️ Identifica diferenças estruturais
- 💡 Fornece recomendações de merge/eliminação

**Acesso:**
```
http://localhost/aksanti/argumentos/comparador_tabelas.php
```

**Uso:**
1. Seleccione tabela 1
2. Seleccione tabela 2
3. Clique "Comparar"
4. Revise similaridade e diferenças

### 3️⃣ [verificador_uso_tabelas.php](verificador_uso_tabelas.php)
**Verificador de Uso no Código**

- ✅ Identifica tabelas USADAS (referenciadas no PHP)
- ❌ Identifica tabelas ÓRFÃS (sem referências)
- 📚 Mostra em quais ficheiros cada tabela é usada
- 📍 Fornece números de linha onde é referenciada

**Acesso:**
```
http://localhost/aksanti/argumentos/verificador_uso_tabelas.php
```

---

## 📋 FLUXO DE ANÁLISE RECOMENDADO

### PASSO 1: Executar Análise Completa
```
1. Abra: analise_tabelas_database.php
2. Revise:
   - Tabelas por tamanho
   - Tabelas vazias
   - Potenciais duplicatas
   - Detalhes de cada tabela
```

**O que procurar:**
- ⚠️ Tabelas > 100 MB (desnecessariamente grandes?)
- 🗑️ Tabelas com 0 registos
- 🔄 Tabelas com nomes similares (user_old, user_backup, etc.)
- 📊 Estrutura duplicada entre tabelas

### PASSO 2: Verificar Uso no Código
```
1. Abra: verificador_uso_tabelas.php
2. Analise os 3 separadores:
   - "Todas" → visão completa
   - "Usadas" → tabelas críticas
   - "Órfãs" → candidatas a eliminação
```

**O que esperar:**
- ✅ 80-90% das tabelas devem estar USADAS
- ❌ 10-20% podem ser ÓRFÃS (mas confirme antes!)
- 📍 Cada tabela usada deve ter referências visíveis

### PASSO 3: Comparar Tabelas Suspeitas
```
1. Se encontrou tabelas similares no PASSO 1
2. Abra: comparador_tabelas.php
3. Compare pares suspeitos
4. Verifique % similaridade
5. Se > 90% = provavelmente redundante
```

---

## ⚠️ POTENCIAIS PROBLEMAS & SOLUÇÕES

### Problema 1: Tabelas Vazias
```
Sintoma: Muitas tabelas com 0 registos
Causa: Migração incompleta ou funcionalidade removida
Solução: SEGURO eliminar se não há referências no código
```

### Problema 2: Tabelas Duplicadas
```
Sintoma: Tabelas como "users", "users_backup", "users_old"
Causa: Múltiplas migração ou testes
Solução: Mesclar dados e manter apenas 1
```

### Problema 3: Tabelas Órfãs Grandes
```
Sintoma: Tabela com 1M registos mas sem referências
Causa: Funcionalidade removida mas dados não apagados
Solução: ARQUIVO (backup) antes de eliminar
```

### Problema 4: Tabelas Dinâmicas
```
Sintoma: Tabelas com nomes que parecem órfãs mas PM diz que usa
Causa: Código que constrói queries dinamicamente
Solução: Procurar por $table_name ou variáveis similares no código
```

---

## 🛡️ ANTES DE ELIMINAR - CHECKLIST OBRIGATÓRIO

### Backup
- [ ] Executado `pg_dump` da base de dados completa
- [ ] Backup armazenado em LOCAL SEGURO (fora do servidor)
- [ ] Testado restauro do backup

### Verificação
- [ ] Tabela confirmada como ÓRFÃ em `verificador_uso_tabelas.php`
- [ ] Procurado no código por referências dinâmicas
- [ ] Verificado se há histórico/auditoria que referencia
- [ ] Confirmado com Product Manager que é seguro eliminar

### Teste
- [ ] Plataforma testada ANTES da eliminação (baseline)
- [ ] Eliminação executada
- [ ] Plataforma testada DEPOIS
- [ ] Logs revistos para erros

### Documentação
- [ ] Documentado qual tabela foi eliminada
- [ ] Registado por quem e quando
- [ ] Motivo da eliminação registado

---

## 📊 EXEMPLO DE ANÁLISE

### Cenário Típico:

```
TABELAS TOTAIS: 17
TAMANHO TOTAL: 50 MB
REGISTOS TOTAIS: 500.000

GRANDES CONSUMIDORAS:
1. user_activity_logs    → 25 MB  (200.000 registos)
2. notifications         → 15 MB  (150.000 registos)
3. projects             → 5 MB   (5.000 registos)
4. users                → 3 MB   (1.200 registos)

TABELAS VAZIAS ENCONTRADAS:
- test_experiments      → 0 registos ✓
- legacy_comments       → 0 registos ✓
- old_temp_data         → 0 registos ✓

TABELAS ÓRFÃS ENCONTRADAS:
- user_activity_logs (25 MB) ❌ → Não referenciada!
- test_table_1 (0 KB) ✓ → Vazia, segura eliminar

AÇÃO RECOMENDADA:
1. Eliminar test_table_1 (vazia, sem risco)
2. Eliminar test_experiments (vazia)
3. Eliminar legacy_comments (vazia)
4. ARQUIVO user_activity_logs para histórico (pode ter dados valioso)
5. Ganho de espaço: ~25 MB
```

---

## 🔄 TIPOS DE ELIMINAÇÃO

### Tipo 1: Eliminação Simples (SEGURA)
```sql
DROP TABLE tabela_pequena;
```
**Quando usar:**
- ✅ Tabela vazia
- ✅ Sem foreign keys
- ✅ Sem referências no código
- ✅ Tamanho < 1 MB

### Tipo 2: Eliminação com Backup (RECOMENDADO)
```sql
-- 1. Backup (fora do SQL)
pg_dump -t tabela_grande > /backup/tabela_grande.sql

-- 2. Eliminar
DROP TABLE tabela_grande;
```
**Quando usar:**
- ⚠️ Tabela com dados significativos
- ⚠️ Tamanho > 1 MB
- ⚠️ Dados históricos que podem ser valiosos

### Tipo 3: Mesclar Tabelas (COMPLEXO)
```sql
-- Se user e users_backup contêm dados diferentes
-- Mesclar dados em users e depois eliminar users_backup

-- 1. Inserir dados únicos de users_backup para users
INSERT INTO users (email, name, created_at)
SELECT email, name, created_at FROM users_backup
WHERE email NOT IN (SELECT email FROM users);

-- 2. Verificar dados
SELECT COUNT(*) FROM users;

-- 3. Eliminar backup
DROP TABLE users_backup;
```

---

## 📞 PRÓXIMOS PASSOS

### Imediatamente:
1. Executar `analise_tabelas_database.php`
2. Executar `verificador_uso_tabelas.php`
3. Documentar achados

### Depois:
1. Reunião com PM para discutir tabelas órfãs
2. Criar plano de eliminação com prioridades
3. Executar eliminações 1 por 1 (com testes entre cada)
4. Monitorar para erros

### Manutenção Futura:
1. Executar análises 1x por mês
2. Arquivo dados históricos antes de eliminar
3. Documentar cada eliminação
4. Manter índices optimizados

---

## ✅ INDICADORES DE SUCESSO

Depois de executar a limpeza:

| Métrica | Antes | Depois | Ganho |
|---------|-------|--------|-------|
| Tamanho BD | 50 MB | 30 MB | 40% |
| Tabelas | 17 | 14 | 3 removidas |
| Tabelas órfãs | 5 | 0 | 100% |
| Tempo query | 500ms | 350ms | 30% mais rápido |

---

## 📝 EXEMPLO DE COMANDO SQL (NÃO EXECUTAR AINDA!)

```sql
-- LISTAGEM DE TABELAS COM TAMANHO
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size,
    (SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public') as total_tables
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;

-- LISTAR FOREIGN KEYS
SELECT constraint_name, table_name, column_name, referenced_table_name
FROM information_schema.key_column_usage
WHERE table_schema = 'public' AND referenced_table_name IS NOT NULL;

-- LISTAR ÍNDICES
SELECT indexname, tablename, indexdef
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename;
```

---

**Data de Criação:** 1 de junho de 2026
**Versão:** 1.0
**Status:** 🟢 Pronto para Análise

