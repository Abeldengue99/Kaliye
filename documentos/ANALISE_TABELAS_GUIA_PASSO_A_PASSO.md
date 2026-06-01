# 🔍 ANÁLISE DE TABELAS - GUIA PASSO A PASSO

## ✅ O QUE FOI CRIADO

Três ferramentas integradas para análise completa:

### 1. **Análise Completa** 📊
Ficheiro: `argumentos/analise_tabelas_database.php`
- Lista TODAS as tabelas com tamanho e registos
- Ordena por tamanho (maiores primeiro)
- Identifica tabelas vazias
- Detecta potenciais duplicatas

### 2. **Comparador de Tabelas** 🔍
Ficheiro: `argumentos/comparador_tabelas.php`
- Compara 2 tabelas lado-a-lado
- Calcula % de similaridade
- Identifica diferenças estruturais
- Fornece recomendações

### 3. **Verificador de Uso** 📚
Ficheiro: `argumentos/verificador_uso_tabelas.php`
- Identifica tabelas USADAS no código PHP
- Identifica tabelas ÓRFÃS (sem referências)
- Mostra ficheiros que usam cada tabela
- Classifica por criticidade

---

## 🚀 COMO USAR

### PASSO 1: Executar Análise Completa

**URL:**
```
http://localhost/aksanti/argumentos/analise_tabelas_database.php
```

**O que procurar:**
```
SECÇÃO 1 - TABELAS POR TAMANHO
├─ Identifique as 3 maiores
├─ Procure por tamanho > 100 MB (porque tão grande?)
└─ Procure por tamanho < 1 KB (completa vazia?)

SECÇÃO 2 - TABELAS VAZIAS
├─ Se existem → candidatas a eliminação
├─ Verificar se têm foreign keys
└─ Confirmar se não são histórico/auditoria

SECÇÃO 3 - DUPLICATAS
├─ "Por Padrão de Nome" → user, user_old, user_backup
├─ "Por Estrutura Similar" → tabelas com mesmas colunas
└─ Anote tabelas suspeitas para análise posterior

SECÇÃO 4 - DETALHES COMPLETOS
├─ Clique em cada tabela
├─ Revise colunas e tipos
├─ Procure pela seção "Referências no Código"
└─ Se 0 referências → possível órfã
```

**Exemplo de Resultado:**
```
📋 Total de Tabelas: 17
📦 Espaço Total: 50 MB
📊 Total de Registos: 500.000
⚠️ Tabelas Vazias: 3

MAIORES CONSUMIDORAS:
1. user_activity_logs → 25 MB (200.000 registos)
2. notifications → 15 MB (150.000 registos)
3. projects → 5 MB (5.000 registos)

TABELAS VAZIAS:
- test_experiments (0 registos) ← Pode eliminar
- legacy_data (0 registos) ← Pode eliminar
```

---

### PASSO 2: Verificar Uso no Código

**URL:**
```
http://localhost/aksanti/argumentos/verificador_uso_tabelas.php
```

**Explore os 3 Separadores:**

#### Separador "Todas as Tabelas"
- Visão completa com status ✅ ou ❌
- Se ✅ USADA → clique em "Ver detalhes" para ver onde
- Se ❌ ÓRFÃ → candidata a eliminação

#### Separador "Usadas"
- Tabelas críticas para a plataforma
- NÃO elimine estas!
- Referências mostram quais ficheiros usam

#### Separador "Órfãs"
- ⚠️ POTENCIAIS CANDIDATAS A ELIMINAÇÃO
- Algumas podem ter dados importantes
- Recomendações de ação por tamanho

**Exemplo:**
```
TABELAS ÓRFÃS ENCONTRADAS:

❌ user_activity_logs (25 MB, 200K registos)
   → Recomendação: ARQUIVO primeiro, depois eliminar
   
❌ test_table_1 (0 KB, 0 registos)
   → Recomendação: SEGURO ELIMINAR - vazia
   
❌ legacy_comments (50 KB, 100 registos)
   → Recomendação: BACKUP primeiro, depois eliminar
```

---

### PASSO 3: Comparar Tabelas Suspeitas

**URL:**
```
http://localhost/aksanti/argumentos/comparador_tabelas.php
```

**Processo:**
1. Seleccione "Tabela 1" (ex: `users`)
2. Seleccione "Tabela 2" (ex: `users_backup`)
3. Clique "🔍 Comparar Tabelas"
4. Revise o resultado

**Resultado Esperado:**
```
Similaridade de Estrutura: 95%

INTERPRETAÇÃO:
- > 90% = Provavelmente redundante (mesclar?)
- 70-90% = Sobreposição significativa (avaliar)
- < 70% = Provavelmente diferentes (manter ambas)

DIFERENÇAS ENCONTRADAS:
❌ Apenas em users: created_date, last_login
✅ Apenas em users_backup: backup_date
```

---

## 📋 EXEMPLO COMPLETO DE ANÁLISE

### Cenário: Encontrou 3 Tabelas Suspeitas

```
Passo 1 - Análise Completa (analise_tabelas_database.php)
├─ Encontrou: users (3 MB), users_backup (2 MB), users_old (1 MB)
├─ Estrutura parece similar
└─ Anotou para investigação

Passo 2 - Verificador de Uso (verificador_uso_tabelas.php)
├─ users → ✅ USADA (23 referências em 5 ficheiros)
├─ users_backup → ❌ ÓRFÃ (0 referências)
├─ users_old → ❌ ÓRFÃ (0 referências)
└─ Decision: Investigar users_backup e users_old

Passo 3 - Comparador (comparador_tabelas.php)
├─ Comparar: users vs users_backup
├─ Resultado: 95% similiar
├─ Diferença: users_backup tem 2 colunas a menos
├─ Conclusão: users_backup é antiga, redundante
│
├─ Comparar: users vs users_old
├─ Resultado: 98% similar
├─ Diferença: users_old é versão quase idêntica
└─ Conclusão: users_old é definitivamente redundante

AÇÃO FINAL:
1. users_backup → Eliminar (redundante, órfã)
2. users_old → Eliminar (redundante, órfã)
3. Ganho: 3 MB de espaço
```

---

## ⚠️ TABELA DE DECISÃO

Use isto para decidir se deve eliminar uma tabela:

| Situação | Tamanho | Registos | Referências | Decisão |
|----------|---------|----------|------------|---------|
| Vazia | < 1 MB | 0 | Nenhuma | ✅ ELIMINAR |
| Vazia | < 1 MB | 0 | Sim | ❓ VERIFICAR (pode ser setup) |
| Órfã | < 10 MB | 1-1000 | Nenhuma | ✅ ELIMINAR (backup 1º) |
| Órfã | > 10 MB | > 1000 | Nenhuma | ⚠️ ARQUIVO (depois eliminar) |
| Duplicada | Qualquer | Qualquer | Nenhuma | ✅ MESCLAR/ELIMINAR |
| Usada | Qualquer | Qualquer | Sim | ❌ MANTER! |

---

## 🛡️ CHECKLIST ANTES DE ELIMINAR

```
BACKUP
☐ Executado pg_dump completo
☐ Backup testado (consegue restaurar?)
☐ Backup armazenado em local seguro

VERIFICAÇÃO
☐ Tabela confirmada como ÓRFÃ
☐ Procurado no código por variáveis dinâmicas ($table_name)
☐ Verificado se é histórico/auditoria importante
☐ PM ou manager aprovaram a eliminação

TESTE
☐ Plataforma testada ANTES da eliminação
☐ Eliminação executada com sucesso
☐ Plataforma testada DEPOIS
☐ Logs revistos para erros

DOCUMENTAÇÃO
☐ Registado: qual tabela, quando, por quem
☐ Registado: motivo da eliminação
☐ Registado: backup executado
```

---

## 📊 RELATÓRIO RECOMENDADO

Depois de completar a análise, crie um relatório:

```
RELATÓRIO DE ANÁLISE DE TABELAS
Data: 1 de junho de 2026
Analista: [seu nome]

RESUMO EXECUTIVO:
- Total de tabelas: 17
- Tabelas órfãs encontradas: 5
- Tabelas vazias encontradas: 3
- Potencial de limpeza: 30 MB

TABELAS CANDIDATAS A ELIMINAÇÃO (Prioridade):

CRÍTICO (eliminar imediatamente):
1. test_table_1 (0 registos, 0 KB) - VAZIA
2. test_table_2 (0 registos, 0 KB) - VAZIA

IMPORTANTE (eliminar após backup):
1. user_activity_logs (200K registos, 25 MB) - ÓRFÃ
2. legacy_comments (100 registos, 50 KB) - ÓRFÃ

MANTER:
- users ✅ (usada 23 vezes)
- projects ✅ (usada 15 vezes)
- notifications ✅ (usada 8 vezes)

PRÓXIMAS AÇÕES:
1. Aprovação de PM
2. Backup completo
3. Eliminação (1 por 1 com testes)
4. Documentação final
```

---

## 🆘 PROBLEMAS COMUNS

### Problema: "Tabela parece órfã mas sei que é usada"
**Solução:** Procure no código por:
```php
// Referências dinâmicas
$table_name = 'users';
$db->query("SELECT * FROM $table_name");

// Arrays de tabelas
$tables = ['users', 'projects', 'notifications'];
foreach($tables as $t) { $db->query("SELECT * FROM $t"); }

// Switch/case
switch($type) { 
    case 'user': $t = 'users'; break;
    ...
}
```

### Problema: "Quanto espaço vou libertar?"
**Solução:** Soma dos tamanhos das tabelas a eliminar:
```
user_activity_logs: 25 MB
legacy_comments: 0.05 MB
test_data: 0.001 MB
─────────────────────
TOTAL: ~25 MB
```

### Problema: "Tenho medo de eliminar"
**Solução:** Incremental!
```
1. Elimine tabelas VAZIAS primeiro (zero risco)
2. Depois tabelas PEQUENAS órfãs (< 1 MB)
3. Depois tabelas GRANDES (com arquivo)
4. Teste a plataforma após cada eliminação
```

---

## ✅ CHECKLIST FINAL

Depois de completar a análise:

- [ ] Executou `analise_tabelas_database.php` ✓
- [ ] Executou `verificador_uso_tabelas.php` ✓
- [ ] Comparou tabelas suspeitas com `comparador_tabelas.php` ✓
- [ ] Identificou candidatas a eliminação ✓
- [ ] Criou documento com recomendações ✓
- [ ] Tem backup da base de dados ✓
- [ ] Aprovação de stakeholders ✓
- [ ] Pronto para executar limpeza ✓

---

**Documentação Completa:**
- Detalhes: [README_ANALISE_TABELAS.md](README_ANALISE_TABELAS.md)
- PostgreSQL Policy: [POLITICA_BASE_DADOS.md](../POLITICA_BASE_DADOS.md)

**Data:** 1 de junho de 2026
**Versão:** 1.0
