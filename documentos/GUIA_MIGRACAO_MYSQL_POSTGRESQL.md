# 🔄 GUIA DE TRANSIÇÃO: MySQL → PostgreSQL Only

## 📋 Situação Atual

O código contém **MISTURA de MySQL e PostgreSQL**:

### ✅ Correto (PostgreSQL):
- `configuracoes/base_dados.php` - Usa DSN `pgsql://`
- `sql/init_database_postgresql.sql` - Sintaxe PostgreSQL pura
- `argumentos/init_database.php` - Cria tabelas em PostgreSQL

### ❌ Descontinuado (MySQL):
- `argumentos/restore_database.php` - ❌ MySQL (AUTO_INCREMENT, TINYINT)
- `argumentos/final_db_sync.php` - ❌ MySQL
- `argumentos/migration_institution_features.php` - ❌ MySQL (AUTO_INCREMENT)
- `argumentos/create_social_tables.php` - ❌ MySQL (AUTO_INCREMENT)
- `argumentos/add_commission_system.php` - ❌ MySQL (AUTO_INCREMENT, TINYINT)

---

## 🚀 AÇÃO REQUERIDA

### Passo 1: Parar de Usar MySQL
**Nunca execute:**
```
✅ argumentos/restore_database.php
✅ argumentos/final_db_sync.php
✅ argumentos/migration_institution_features.php
✅ argumentos/create_social_tables.php
✅ argumentos/add_commission_system.php
```

### Passo 2: Usar Apenas PostgreSQL
**Use sempre:**
```
✅ argumentos/init_database.php
✅ sql/init_database_postgresql.sql
```

### Passo 3: Verificar Conformidade
```
✅ argumentos/check_database_compliance.php
```

---

## 📊 COMPARAÇÃO DE SINTAXE

### SÉRIE DE CHAVES PRIMÁRIAS

| MySQL | PostgreSQL |
|-------|-----------|
| `INT AUTO_INCREMENT PRIMARY KEY` | `SERIAL PRIMARY KEY` |
| `INT AUTO_INCREMENT UNIQUE` | `SERIAL UNIQUE` |

**Exemplo:**
```sql
-- ❌ MySQL
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    ...
);

-- ✅ PostgreSQL
CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    ...
);
```

### BOOLEANOS

| MySQL | PostgreSQL |
|-------|-----------|
| `TINYINT(1) DEFAULT 0` | `BOOLEAN DEFAULT FALSE` |
| `TINYINT(1) DEFAULT 1` | `BOOLEAN DEFAULT TRUE` |

**Exemplo:**
```sql
-- ❌ MySQL
ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1;

-- ✅ PostgreSQL
ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
```

### TIMESTAMPS

| MySQL | PostgreSQL |
|-------|-----------|
| `TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` |

**Exemplo:**
```sql
-- ❌ MySQL
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

-- ✅ PostgreSQL
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

---

## 🔧 COMO CONVERTER CÓDIGO

### Se Encontrar MySQL:

1. **Identificar o tipo de dado:**
   ```
   AUTO_INCREMENT → SERIAL
   TINYINT(1) → BOOLEAN
   INT → INTEGER
   ```

2. **Converter a sintaxe:**
   ```sql
   -- Antes (MySQL)
   CREATE TABLE exemple (
       id INT AUTO_INCREMENT PRIMARY KEY,
       active TINYINT(1) DEFAULT 1
   );
   
   -- Depois (PostgreSQL)
   CREATE TABLE exemple (
       id SERIAL PRIMARY KEY,
       active BOOLEAN DEFAULT TRUE
   );
   ```

3. **Testar no PostgreSQL:**
   - Verificar em pgAdmin 4
   - Confirmar que a tabela foi criada
   - Testar as queries

---

## 🎯 CHECKLIST DE MIGRAÇÃO

### Para Cada Ficheiro:
- [ ] Identificar se usa MySQL ou PostgreSQL
- [ ] Se MySQL: Marcar como ❌ DESCONTINUADO
- [ ] Se PostgreSQL: Validar sintaxe
- [ ] Testar em `check_database_compliance.php`

### Para Cada Query:
- [ ] Verificar se usa `SERIAL` (não `AUTO_INCREMENT`)
- [ ] Verificar se usa `BOOLEAN` (não `TINYINT`)
- [ ] Verificar se usa `INTEGER` (não `INT`)
- [ ] Verificar Foreign Keys com `ON DELETE CASCADE`

---

## ✅ VERIFICAÇÃO FINAL

Execute em ordem:

1. **Criar Tabelas:**
   ```
   argumentos/init_database.php
   ```

2. **Verificar Conformidade:**
   ```
   argumentos/check_database_compliance.php
   ```
   
   Resultado esperado:
   ```
   ✅ SISTEMA EM CONFORMIDADE!
   [17/17 verificações passaram]
   ```

3. **Sincronizar Dados:**
   ```
   argumentos/quick_sync.php
   ```

4. **Testar Plataforma:**
   - Recarregar navegador (Ctrl+F5)
   - Testar funcionalidades principais
   - Verificar logs de erro

---

## 📞 SUPORTE

### Se Encontrar Erro PostgreSQL:

**Erro:** "undefined column"
- Verificar se a coluna foi criada com o nome correto
- Confirmar que não está a misturar MySQL e PostgreSQL

**Erro:** "relation does not exist"
- Executar `init_database.php` para criar tabelas
- Verificar em pgAdmin se as tabelas existem

**Erro:** "FOREIGN KEY constraint failed"
- Confirmar que a tabela referenciada existe
- Verificar tipos de dados (ambas as colunas devem ser INTEGER/SERIAL)

---

## 📝 PRÓXIMOS PASSOS

1. ✅ Converter todos os ficheiros MySQL para PostgreSQL
2. ✅ Remover ou deprecar ficheiros descontinuados
3. ✅ Atualizar documentação
4. ✅ Testar todas as funcionalidades
5. ✅ Comunicar mudança a toda a equipa

---

**Data:** 1 de junho de 2026  
**Status:** 🔴 OBRIGATÓRIO - PostgreSQL ONLY  
**Próxima revisão:** Quando todas as migrações estiverem completas
