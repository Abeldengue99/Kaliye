# ⚡ GUIA RÁPIDO - EXECUTAR BACKUP AGORA

**Status:** Pronto para executar  
**Data:** 1 de junho de 2026  
**Tabelas em risco:** 4 (aguardando backup)

---

## 🚀 INÍCIO RÁPIDO (5 MINUTOS)

### OPÇÃO A: Mais Fácil (Usar Script Automatizado)

**Passo 1:** Abra PowerShell como Administrador

```
⊞ Clique em ⊞ Windows
✓ Digite: PowerShell
⊡ Clique com direito em "Windows PowerShell"
⊙ Seleccione "Executar como administrador"
```

**Passo 2:** Mude para a pasta do projeto

```powershell
cd "C:\Users\nee\Documents\Aksanti Referências"
```

**Passo 3:** Execute o script de backup

```powershell
.\EXECUTAR_BACKUP.ps1
```

✅ **Pronto!** O script fará todo o backup automaticamente.

---

### OPÇÃO B: Mais Rápido (Comando Direto)

Se o script acima não funcionar, execute este comando em PowerShell (copie e cole):

```powershell
$env:PGPASSWORD = '5850'; & 'C:\Program Files\PostgreSQL\18\bin\pg_dump.exe' -U postgres -h 127.0.0.1 kaliye | Out-File -Encoding UTF8 "C:\Users\nee\Documents\Aksanti Referências\backups\kaliye_backup_$(Get-Date -Format 'yyyy-MM-dd_HH-mm-ss').sql"
```

**⏳ Aguarde:** Pode levar 30-60 segundos

**✅ Sucesso:** Deverá ver uma mensagem `Out-File` sem erros

---

### OPÇÃO C: Via pgAdmin (Interface Gráfica)

Se não se sente confortável com PowerShell:

1. Abra **pgAdmin 4**
2. Na esquerda, clique em **Servers** → **PostgreSQL**
3. Localize a base de dados **kaliye**
4. Clique com direito → **Backup...**
5. Deixe as definições por defeito
6. Clique **Backup**

---

## ✅ VERIFICAÇÃO - O Backup Funcionou?

Depois de executar um dos passos acima, **verifique**:

```powershell
# Liste os backups criados
Get-ChildItem "C:\Users\nee\Documents\Aksanti Referências\backups\kaliye_backup_*.sql" | 
    ForEach-Object { Write-Host "$($_.Name) - $([Math]::Round($_.Length/1MB, 2)) MB" }
```

**🎉 Se vir um ficheiro com tamanho > 1 MB - SUCESSO!**

---

## 🔐 GUARDAR BACKUP EM LOCAL SEGURO

Depois de confirmar que o backup foi criado:

### Via Pen Drive
1. Insira pen drive
2. Copie `C:\Users\nee\Documents\Aksanti Referências\backups\kaliye_backup_*.sql` para a pen
3. Guarde em local seguro

### Via OneDrive/Google Drive
1. Copie o ficheiro para `C:\Users\nee\OneDrive\Backups\` (cria se não existir)
2. Aguarde sincronização

### Via Email (Para Backups Pequenos)
```powershell
# Se tamanho < 25 MB, pode enviar por email
Get-ChildItem "C:\Users\nee\Documents\Aksanti Referências\backups\kaliye_backup_*.sql" | 
    Sort-Object LastWriteTime -Descending | 
    Select-Object -First 1 | 
    ForEach-Object { Write-Host "Ficheiro para arquivar: $($_.FullName)" }
```

---

## 📊 RESUMO DO BACKUP

| Item | Valor |
|------|-------|
| **Base de dados** | kaliye (PostgreSQL) |
| **Tamanho esperado** | 15-30 MB |
| **Tempo esperado** | 30-60 segundos |
| **Local** | `C:\Users\nee\Documents\Aksanti Referências\backups\` |
| **Formato** | SQL Plain Text (.sql) |

---

## ⏭️ DEPOIS DO BACKUP

✅ **1. Confirme o backup foi criado**
```powershell
Get-ChildItem "C:\Users\nee\Documents\Aksanti Referências\backups\kaliye_backup_*.sql"
```

✅ **2. Guarde em local seguro (pen drive ou cloud)**

✅ **3. Consulte:** `PLANO_BACKUP_E_LIMPEZA.md` para próximos passos

✅ **4. Aguarde aprovação** antes de eliminar tabelas

---

## 🆘 ALGO DEU ERRADO?

### Erro: "pg_dump: comando não encontrado"
→ Solução: PostgreSQL não está instalado ou caminho incorreto

### Erro: "FATAL: password authentication failed"
→ Solução: Password postgres está incorreta (verificar em `configuracoes/base_dados.php`)

### Erro: "psql: FATAL: Uh oh. Connection refused"
→ Solução: PostgreSQL não está em execução
```powershell
# Inicie o serviço
Start-Service postgresql-x64-18
```

### Ficheiro criado mas vazio (0 KB)
→ Solução: Verifique logs de erro, pode haver problema de permissões

---

## 📞 SUPORTE

Para problemas ou dúvidas:

1. Consulte `PLANO_BACKUP_E_LIMPEZA.md` (completo)
2. Consulte `argumentos/README_ANALISE_TABELAS.md` (técnico)
3. Execute novamente o script com `.\EXECUTAR_BACKUP.ps1`

---

**Última atualização:** 1 de junho de 2026  
**Versão:** 1.0  
**Status:** 🟢 PRONTO PARA EXECUTAR
