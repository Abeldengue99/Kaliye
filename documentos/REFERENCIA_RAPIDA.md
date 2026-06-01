# 🚀 REFERÊNCIA RÁPIDA - SISTEMA POSTGRESQL

## 🎯 OBJETIVO FINAL
**A plataforma KALIYE usa EXCLUSIVAMENTE PostgreSQL**

---

## 📊 STATUS ATUAL

| Item | Status | Ação |
|------|--------|------|
| Base de dados PostgreSQL | ✅ Disponível | 127.0.0.1:5432 |
| Schema PostgreSQL | ✅ Criado | `sql/init_database_postgresql.sql` |
| Inicialização | ✅ Pronta | `argumentos/init_database.php` |
| Verificação | ✅ Pronta | `argumentos/check_database_compliance.php` |
| Sincronização | ✅ Pronta | `argumentos/quick_sync.php` |
| Código MySQL | ❌ Descontinuado | **NÃO USE** estes ficheiros |

---

## 🚀 PASSO A PASSO

### 1️⃣ INICIALIZAR BASE DE DADOS (1ª vez)
```
URL: http://localhost/aksanti/argumentos/init_database.php
Resultado esperado: ✅ "Tabelas criadas com sucesso"
```

### 2️⃣ VERIFICAR CONFORMIDADE
```
URL: http://localhost/aksanti/argumentos/check_database_compliance.php
Resultado esperado: ✅ "SISTEMA EM CONFORMIDADE!"
```

### 3️⃣ SINCRONIZAR DADOS
```
URL: http://localhost/aksanti/argumentos/quick_sync.php
Resultado esperado: ✅ "Sincronização completa"
```

### 4️⃣ TESTAR PLATAFORMA
- Recarregar site: `Ctrl+F5`
- Testar login, chat, projetos, etc.
- Verificar se há erros no console

---

## ❌ O QUE NÃO FAZER

**NUNCA execute ou use estes ficheiros:**
- ❌ `argumentos/restore_database.php`
- ❌ `argumentos/final_db_sync.php`
- ❌ `argumentos/migration_institution_features.php`
- ❌ `argumentos/create_social_tables.php`
- ❌ `argumentos/add_commission_system.php`

**Conter sintaxe MySQL obsoleta.**

---

## 📁 FICHEIROS IMPORTANTES

```
✅ USAR:
├── configuracoes/base_dados.php          [Configuração PostgreSQL]
├── sql/init_database_postgresql.sql      [Schema 17 tabelas]
├── argumentos/init_database.php          [Inicializador]
├── argumentos/check_database_compliance.php [Verificador]
├── argumentos/quick_sync.php             [Sincronizador]
├── POLITICA_BASE_DADOS.md                [Política oficial]
├── GUIA_MIGRACAO_MYSQL_POSTGRESQL.md     [Guia técnico]
└── REFERENCIA_RAPIDA.md                  [Este ficheiro]

❌ NÃO USAR:
├── argumentos/restore_database.php       (MySQL)
├── argumentos/final_db_sync.php          (MySQL)
├── argumentos/migration_institution_features.php (MySQL)
├── argumentos/create_social_tables.php   (MySQL)
└── argumentos/add_commission_system.php  (MySQL)
```

---

## 🔍 TROUBLESHOOTING RÁPIDO

### Erro: "Undefined column: id"
```
❌ CAUSA: Código esperando coluna "id"
✅ SOLUÇÃO: Usar "user_id" em vez de "id"
```

### Erro: "relation does not exist"
```
❌ CAUSA: Tabela não foi criada
✅ SOLUÇÃO: Executar argumentos/init_database.php
```

### Erro: "FOREIGN KEY constraint failed"
```
❌ CAUSA: Tipo de dado incompatível
✅ SOLUÇÃO: Ambas colunas devem ser SERIAL/INTEGER
```

### Erro: "Unknown connection type 'mysql'"
```
❌ CAUSA: DSN usando "mysql://" em vez de "pgsql://"
✅ SOLUÇÃO: Verificar configuracoes/base_dados.php
```

---

## 📞 CONTACTOS

| Função | URL |
|--------|-----|
| Iniciar | `argumentos/init_database.php` |
| Verificar | `argumentos/check_database_compliance.php` |
| Sincronizar | `argumentos/quick_sync.php` |
| Política | `POLITICA_BASE_DADOS.md` |
| Guia Técnico | `GUIA_MIGRACAO_MYSQL_POSTGRESQL.md` |

---

## ✅ CHECKLIST DIÁRIO

Antes de usar a plataforma:
- [ ] Base de dados PostgreSQL está online
- [ ] Ficheiros MySQL não são usados
- [ ] Verificação de conformidade passa (`check_database_compliance.php`)
- [ ] Sem erros no console do navegador
- [ ] Funcionalidades funcionam correctamente

---

## 🎓 REGRA DE OURO

> **"PostgreSQL is the ONLY official database for KALIYE"**

- Nada de MySQL
- Nada de SQLite
- Nada de outras bases de dados

**PostgreSQL. Sempre. Apenas PostgreSQL.**

---

**Última atualização:** 1 de junho de 2026
**Versão:** 1.0 (PostgreSQL Only)
