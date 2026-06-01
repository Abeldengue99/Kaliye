# 🗄️ POLÍTICA DE BASE DE DADOS - KALIYE

## 📌 DECISÃO OFICIAL

**A plataforma KALIYE usa EXCLUSIVAMENTE PostgreSQL.**

- ✅ **Motor de BD Oficial:** PostgreSQL 18
- ✅ **Host:** 127.0.0.1
- ✅ **Porta:** 5432
- ✅ **Base de dados:** kaliye
- ✅ **Utilizador:** postgres
- ✅ **Tipo de DSN:** pgsql://

---

## ❌ O QUE NÃO USAR

**MySQL, SQLite, ou qualquer outro motor NÃO são suportados.**

Ficheiros descontinuados com sintaxe MySQL:
- `argumentos/restore_database.php` ❌
- `argumentos/final_db_sync.php` ❌
- `argumentos/migration_institution_features.php` ❌
- `argumentos/create_social_tables.php` ❌
- `argumentos/add_commission_system.php` ❌

**NÃO EXECUTE ESTES FICHEIROS**

---

## ✅ COMO FUNCIONA O SISTEMA

### 1. Configuração de Conexão
**Ficheiro:** `configuracoes/base_dados.php`

```php
private string $host     = "127.0.0.1";
private string $port     = "5432";      // PostgreSQL
private string $db_name  = "kaliye";
private string $username = "postgres";
private string $password = "5850";

$dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname='" . $this->db_name . "'";
// ↑ Isto é PostgreSQL, não MySQL
```

### 2. Schema PostgreSQL
**Ficheiro:** `sql/init_database_postgresql.sql`

Características PostgreSQL:
- ✅ Usa `SERIAL` para auto-increment (não `AUTO_INCREMENT`)
- ✅ Usa `BOOLEAN` (não `TINYINT`)
- ✅ Usa `TIMESTAMP` com `DEFAULT CURRENT_TIMESTAMP`
- ✅ Suporta `FOREIGN KEY` com `ON DELETE CASCADE`
- ✅ Suporta índices avançados

### 3. Inicialização
**Ficheiro:** `argumentos/init_database.php`

Este script:
1. Conecta via PDO com DSN PostgreSQL
2. Executa `sql/init_database_postgresql.sql`
3. Cria todas as 17 tabelas com sintaxe PostgreSQL
4. Valida a estrutura

---

## 🚀 FLUXO CORRETO DE INICIALIZAÇÃO

### Passo 1: Criar Base de Dados
```powershell
# No PostgreSQL
createdb -U postgres kaliye
```

Ou no pgAdmin:
- Direito-clique em "Databases"
- Create → Database
- Nome: `kaliye`

### Passo 2: Inicializar Tabelas
```
http://seu-dominio.com/aksanti/argumentos/init_database.php
```

Isto irá:
1. Conectar ao PostgreSQL
2. Criar todas as tabelas
3. Configurar índices e foreign keys
4. Validar a estrutura

### Passo 3: Sincronizar Dados
```
http://seu-dominio.com/aksanti/argumentos/quick_sync.php
```

### Passo 4: Verificar Integridade
```
http://seu-dominio.com/aksanti/argumentos/verify_users_table.php
```

---

## 📊 TABELAS POSTGRESQL

Todas as tabelas usam:
- **Chaves primárias:** `SERIAL PRIMARY KEY` (gera auto-increment)
- **Booleanos:** `BOOLEAN` (não `TINYINT(1)`)
- **Timestamps:** `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
- **Integridade:** `FOREIGN KEY ... ON DELETE CASCADE`

### Exemplo de Tabela Correta (PostgreSQL):
```sql
CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,           -- Auto-increment
    email VARCHAR(255) UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,       -- PostgreSQL boolean
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### ❌ Sintaxe MySQL (NÃO USE):
```sql
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,    -- ❌ MySQL
    email VARCHAR(255) UNIQUE,
    is_active TINYINT(1) DEFAULT 1,           -- ❌ MySQL
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- ❌ MySQL
    FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

---

## 🔧 TROUBLESHOOTING

### Erro: "Unknown connection type 'mysql'"
**Causa:** Tentativa de usar MySQL com DSN PostgreSQL
**Solução:** Sempre use `pgsql:` no DSN, nunca `mysql:`

### Erro: "SERIAL not found"
**Causa:** Ficheiro SQL com sintaxe MySQL
**Solução:** Use `sql/init_database_postgresql.sql`, não `restore_database.php`

### Erro: "BOOLEAN type not found"
**Causa:** Código esperando `TINYINT(1)`
**Solução:** Use `BOOLEAN` em PostgreSQL

---

## 📝 CHECKLIST DE CONFORMIDADE

- [ ] Base de dados conecta via `pgsql://`
- [ ] Schema criado com `init_database_postgresql.sql`
- [ ] Todas as tabelas usam `SERIAL` (não `AUTO_INCREMENT`)
- [ ] Todos os booleanos usam `BOOLEAN` (não `TINYINT`)
- [ ] Nenhum ficheiro MySQL é executado
- [ ] Verificação com `verify_users_table.php` passa com sucesso
- [ ] pgAdmin mostra todas as 17 tabelas

---

## 📞 CONTACTO

**Se encontrar código MySQL na plataforma:**

1. Identifique o ficheiro
2. Reporte o problema
3. O código será convertido para PostgreSQL ou removido

**Nenhum código MySQL é tolerado.**

---

**Data:** 1 de junho de 2026  
**Versão:** 1.0  
**Status:** 🔴 OBRIGATÓRIO - PostgreSQL ONLY
