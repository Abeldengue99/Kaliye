# 📊 GUIA - ANÁLISE COMPLETA DE 75 TABELAS

**Ferramenta:** `analise_75_tabelas_completa.php`  
**Data:** 1 de junho de 2026  
**Objectivo:** Identificar tabelas duplicadas, órfãs, vazias e candidatas a eliminação

---

## 🚀 COMO EXECUTAR (3 PASSOS)

### Passo 1: Abra o Browser
- Chrome, Firefox, Edge, ou qualquer navegador

### Passo 2: Cole esta URL
```
http://localhost/aksanti/argumentos/analise_75_tabelas_completa.php
```

### Passo 3: Aguarde análise
- O script vai analisar todas as 75 tabelas
- Pode levar 30-60 segundos na primeira execução

---

## 📋 O QUE VAI VER

### Secção 1: ESTATÍSTICAS RÁPIDAS
```
Total Tabelas: 75
Tabelas Vazias: X
Tabelas Órfãs: Y
Potenciais Duplicatas: Z
Espaço Total: ABC MB
```

### Secção 2: TABELAS VAZIAS (0 registos)
```
🚫 Candidatas SEGURAS para eliminação
- Sem dados
- Sem referências no código = risco mínimo
```

### Secção 3: TABELAS ÓRFÃS (Sem uso no código)
```
🐦 Potencialmente descartáveis
- Não aparecem em nenhum ficheiro PHP
- Risco médio a alto (podem ser usadas dinamicamente)
```

### Secção 4: POTENCIAIS DUPLICATAS
```
🔄 Tabelas com mesma estrutura
- Mesmas colunas e tipos de dados
- Possível redundância
```

### Secção 5: TABELAS PEQUENAS
```
📦 Menos de 100 KB e 100 registos
- Candidatas a arquivo ou eliminação
```

### Secção 6: TODAS AS 75 TABELAS
```
📋 Referência completa com status
- Usa: ✅ ou ❌
- Status: 🔴 ELIMINAR, 🟡 CUIDADO, 🟠 ÓRFÃ, 🟢 OK
```

---

## 🎯 O QUE O SCRIPT FAZ

### 1. Análise de Cada Tabela
- ✅ Conta registos
- ✅ Calcula tamanho em disco
- ✅ Conta colunas
- ✅ Lista estrutura (nomes e tipos)

### 2. Procura Referências no Código PHP
- ✅ Analisa todos os ficheiros .php
- ✅ Procura por padrões: `FROM`, `INSERT INTO`, `UPDATE`, `DELETE`, `JOIN`
- ✅ Identifica se tabela é usada

### 3. Identifica Padrões
- ✅ Tabelas vazias (0 registos)
- ✅ Tabelas órfãs (não usadas)
- ✅ Tabelas duplicadas (mesma estrutura)
- ✅ Tabelas pequenas (< 100 KB)

### 4. Classifica por Risco
```
🟢 VERDE = Usada no código (MANTER)
🟡 AMARELO = Vazia mas usada (CUIDADO)
🟠 LARANJA = Órfã com dados (INVESTIGAR)
🔴 VERMELHO = Vazia e órfã (ELIMINAR)
```

---

## 📊 INTERPRETAR RESULTADOS

### Exemplo: Tabela "test_data"

| Campo | Valor | Significado |
|-------|-------|------------|
| Registos | 0 | Está vazia |
| Tamanho | 0 KB | Não ocupa espaço |
| Usada | ❌ Não | Sem referências PHP |
| Status | 🔴 ELIMINAR | Segura para eliminar |

**Decisão:** ✅ **Eliminar imediatamente**

---

### Exemplo: Tabela "user_logs"

| Campo | Valor | Significado |
|-------|-------|------------|
| Registos | 50000 | Tem dados |
| Tamanho | 5 MB | Ocupa espaço |
| Usada | ❌ Não | Sem referências PHP |
| Status | 🟠 ÓRFÃ | Precisa investigação |

**Decisão:** ⚠️ **Investigar antes de eliminar**
- Pode ser usada dinamicamente
- Pode ser histórico importante
- Contacte PM para confirmar

---

### Exemplo: Tabela "users"

| Campo | Valor | Significado |
|-------|-------|------------|
| Registos | 1000 | Tem muitos dados |
| Tamanho | 2 MB | Ocupa espaço |
| Usada | ✅ Sim | 23 referências PHP |
| Status | 🟢 OK | Crítica |

**Decisão:** ✅ **MANTER - Não eliminar!**

---

## 🎯 PRÓXIMOS PASSOS COM RESULTADOS

### Se a análise mostrar:

#### ✅ 5 tabelas vazias e órfãs
```
AÇÃO: Eliminar imediatamente após backup
BACKUP: Já temos pronto (kaliye_backup_2026-06-01_13-08-37.sql)
RISCO: ZERO (estão vazias)
```

#### ✅ 10 tabelas órfãs com dados
```
AÇÃO: Investigar - contactar PM
PERGUNTA: "Esta tabela X é histórico/importante?"
RESPOSTA: Se não → arquivar e depois eliminar
         Se sim → manter ou renomear
```

#### ✅ 3 grupos de duplicatas
```
AÇÃO: Comparar estrutura e dados
PERGUNTA: "Posso mergir estas tabelas?"
RESPOSTA: Se sim → consolidar e eliminar
         Se não → investigar diferenças
```

---

## 💡 RECOMENDAÇÕES FINAIS

### SEGURO para eliminar:
```
- Tabelas com 0 registos
- E sem referências no código PHP
- Tamanho até 1 MB
```

### NÃO eliminar sem aprovação:
```
- Tabelas com dados (mesmo que órfãs)
- Tabelas que parecem histórico
- Tabelas com relacionamentos externos
```

### Antes de qualquer eliminação:
```
1. ✅ Ter backup (já temos)
2. ✅ Obter aprovação PM/Manager
3. ✅ Testar em ambiente de teste
4. ✅ Verificar logs de erro após eliminação
5. ✅ Documentar tudo
```

---

## 🔗 REFERÊNCIAS

| Ferramenta | Localização | Propósito |
|-----------|-----------|----------|
| **Esta análise** | `argumentos/analise_75_tabelas_completa.php` | Diagnóstico completo |
| **Backup** | `kaliye_backup_2026-06-01_13-08-37.sql` | Proteção |
| **Comparador** | `argumentos/comparador_tabelas.php` | Comparar 2 tabelas |
| **Verificador uso** | `argumentos/verificador_uso_tabelas.php` | Ver uso no código |

---

## 🚀 COMECE AGORA!

**URL:** `http://localhost/aksanti/argumentos/analise_75_tabelas_completa.php`

1. Abra no browser
2. Aguarde análise (30-60 segundos)
3. Revise resultados
4. Anote tabelas candidatas
5. Consulte recomendações
6. Contacte PM para decisões finais

---

**Criado:** 1 de junho de 2026  
**Status:** ✅ Pronto para usar  
**Tempo de execução esperado:** 30-60 segundos na primeira vez

Abra a análise agora! 🚀
