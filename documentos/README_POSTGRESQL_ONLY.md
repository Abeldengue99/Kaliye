# 🗄️ KALIYE - POLÍTICA POSTGRESQL ONLY

## 📌 SITUAÇÃO

A plataforma KALIYE **usa EXCLUSIVAMENTE PostgreSQL**.

Encontrou-se código com **sintaxe MySQL obsoleta** que foi **DESCONTINUADA**.

---

## 🎯 O QUE FOI FEITO

### ✅ Criado:
1. **`POLITICA_BASE_DADOS.md`** - Política oficial de base de dados
2. **`GUIA_MIGRACAO_MYSQL_POSTGRESQL.md`** - Guia técnico de conversão
3. **`REFERENCIA_RAPIDA.md`** - Guia rápido para utilizadores
4. **`argumentos/check_database_compliance.php`** - Verificador de conformidade
5. **`argumentos/POSTGRESQL_ONLY.php`** - Aviso visual

### ✅ Mantido:
- `configuracoes/base_dados.php` ✓ PostgreSQL correto
- `sql/init_database_postgresql.sql` ✓ Schema PostgreSQL
- `argumentos/init_database.php` ✓ Inicializador PostgreSQL
- `argumentos/quick_sync.php` ✓ Sincronizador PostgreSQL

### ⚠️ Descontinuado:
- `argumentos/restore_database.php` ❌ (Sintaxe MySQL)
- `argumentos/final_db_sync.php` ❌ (Sintaxe MySQL)
- `argumentos/migration_institution_features.php` ❌ (Sintaxe MySQL)
- `argumentos/create_social_tables.php` ❌ (Sintaxe MySQL)
- `argumentos/add_commission_system.php` ❌ (Sintaxe MySQL)

---

## 🚀 PRÓXIMAS AÇÕES

### 1. VERIFICAR CONFORMIDADE
```
http://localhost/aksanti/argumentos/check_database_compliance.php
```

Resultado esperado:
```
✅ SISTEMA EM CONFORMIDADE!
[17/17 verificações passaram]
```

### 2. INICIALIZAR (se necessário)
```
http://localhost/aksanti/argumentos/init_database.php
```

### 3. SINCRONIZAR
```
http://localhost/aksanti/argumentos/quick_sync.php
```

---

## 📋 TABELA DE REFERÊNCIA

| Ficheiro | Tipo | Status | Usar? |
|----------|------|--------|-------|
| configuracoes/base_dados.php | Config | ✅ PostgreSQL | ✓ SIM |
| sql/init_database_postgresql.sql | Schema | ✅ PostgreSQL | ✓ SIM |
| argumentos/init_database.php | Tool | ✅ PostgreSQL | ✓ SIM |
| argumentos/check_database_compliance.php | Tool | ✅ PostgreSQL | ✓ SIM |
| argumentos/quick_sync.php | Tool | ✅ PostgreSQL | ✓ SIM |
| argumentos/restore_database.php | Tool | ❌ MySQL | ✗ NÃO |
| argumentos/final_db_sync.php | Tool | ❌ MySQL | ✗ NÃO |
| argumentos/migration_institution_features.php | Tool | ❌ MySQL | ✗ NÃO |
| argumentos/create_social_tables.php | Tool | ❌ MySQL | ✗ NÃO |
| argumentos/add_commission_system.php | Tool | ❌ MySQL | ✗ NÃO |

---

## 🔧 CONFIGURAÇÃO ACTUAL

```
Base de dados: PostgreSQL 18
Host: 127.0.0.1
Porta: 5432
DB: kaliye
Utilizador: postgres
DSN: pgsql:host=127.0.0.1;port=5432;dbname='kaliye'
```

---

## 📚 DOCUMENTAÇÃO

| Documento | Propósito |
|-----------|-----------|
| `POLITICA_BASE_DADOS.md` | Política oficial e requisitos |
| `GUIA_MIGRACAO_MYSQL_POSTGRESQL.md` | Guia técnico de migração |
| `REFERENCIA_RAPIDA.md` | Guia rápido para utilizadores |
| `argumentos/README_SINCRONIZACAO.md` | Documentação de sincronização |

---

## ✅ VERIFICAÇÃO

### Passo 1: Conformidade
Execute `check_database_compliance.php`:
```
✅ Driver Correto: PostgreSQL
✅ PostgreSQL Versão: 18.x
✅ Tabelas Encontradas: 17
✅ Tabelas Críticas Presentes
✅ Tipos de Dados Corretos
```

### Passo 2: Funcionalidade
Teste a plataforma:
- ✅ Login funciona
- ✅ Chat funciona
- ✅ Projetos funcionam
- ✅ Sem erros de base de dados

### Passo 3: Limpeza
Remova ou desative ficheiros MySQL:
- ❌ Não use restore_database.php
- ❌ Não use final_db_sync.php
- ❌ Não use migration_institution_features.php
- ❌ Não use create_social_tables.php
- ❌ Não use add_commission_system.php

---

## 🎓 POLÍTICA

> **"A base de dados oficial para armazenamento de todas as informações deve ser a do postgresql e não mysql"**

Isto significa:
- ✅ PostgreSQL é a ÚNICA base de dados oficial
- ❌ MySQL não é suportado
- ❌ Nenhum código MySQL é tolerado
- ❌ Nenhuma mistura de sintaxes

---

## 📞 SUPORTE

### Se encontrar erro:

1. **Verificar conformidade:**
   ```
   argumentos/check_database_compliance.php
   ```

2. **Verificar logs:**
   - Navegador: `F12` → `Console`
   - Servidor: `/var/log/apache2/error.log`

3. **Reinicializar (se necessário):**
   ```
   argumentos/init_database.php
   ```

4. **Sincronizar:**
   ```
   argumentos/quick_sync.php
   ```

---

## 🎯 RESUMO

| Antes | Depois |
|-------|--------|
| ❌ Mistura MySQL + PostgreSQL | ✅ PostgreSQL Only |
| ❌ Código obsoleto ativo | ✅ Código MySQL descontinuado |
| ❌ Sem política clara | ✅ Política PostgreSQL Only |
| ❌ Sem verificação | ✅ Verificador de conformidade |
| ❌ Confusão de tipos de dados | ✅ Tipos PostgreSQL claros |

---

**Data:** 1 de junho de 2026  
**Versão:** 1.0  
**Status:** 🟢 PostgreSQL Only - Implementado

---

## 📖 LEITURA RECOMENDADA

1. Comece por: `REFERENCIA_RAPIDA.md`
2. Depois leia: `POLITICA_BASE_DADOS.md`
3. Para técnico: `GUIA_MIGRACAO_MYSQL_POSTGRESQL.md`
4. Para ferramentas: `argumentos/README_SINCRONIZACAO.md`
