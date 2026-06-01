# 🔐 PLANO DE BACKUP E LIMPEZA DE TABELAS

**Data:** 1 de junho de 2026  
**Status:** ⏳ FASE 1 - BACKUP EM PREPARAÇÃO  
**Responsável:** Análise automática  

---

## 📋 RESUMO EXECUTIVO

| Métrica | Valor |
|---------|-------|
| **Total de Tabelas** | 17 |
| **Tabelas a Eliminar** | 4 |
| **Espaço a Libertar** | ~25 MB |
| **Risco** | ⚠️ MÉDIO (tabelas órfãs confirmadas) |

---

## 🎯 TABELAS CANDIDATAS A ELIMINAÇÃO

### ✅ CRÍTICO - Eliminar Primeiro (Risco ZERO)
Estas tabelas estão **vazias** e **não têm referências** no código:

| # | Tabela | Registos | Tamanho | Referências | Status |
|---|--------|----------|---------|-------------|--------|
| 1 | `test_table_1` | 0 | 0 KB | 0 | ✅ Seguro eliminar |
| 2 | `test_table_2` | 0 | 0 KB | 0 | ✅ Seguro eliminar |

**Ação:** Pode ser eliminada imediatamente após backup

---

### ⚠️ IMPORTANTE - Eliminar com Cuidado

| # | Tabela | Registos | Tamanho | Status | Notas |
|---|--------|----------|---------|--------|-------|
| 1 | `user_activity_logs` | 200.000 | 25 MB | ❌ ÓRFÃ | Sem referências no código - BACKUP OBRIGATÓRIO |
| 2 | `legacy_comments` | 100 | 50 KB | ❌ ÓRFÃ | Dados históricos - pode estar em use desconhecido |

**Ação:** Arquivar antes de eliminar

---

## 📊 TABELAS A MANTER (NÃO DELETAR)

| Tabela | Registos | Tamanho | Referências | Prioridade |
|--------|----------|---------|-------------|-----------|
| `users` | Variável | 10 MB | 23 | 🔴 CRÍTICA |
| `projects` | Variável | 8 MB | 15 | 🔴 CRÍTICA |
| `notifications` | Variável | 5 MB | 8 | 🟡 ALTA |
| `mentor_chat_groups` | Variável | 2 MB | 12 | 🟡 ALTA |
| `support_messages` | Variável | 1 MB | 6 | 🟢 MÉDIA |

---

## 🔄 FASE 1: BACKUP (👈 VOCÊ ESTÁ AQUI)

### Passo 1️⃣: Verificar Conectividade PostgreSQL

```powershell
# Teste de conexão
Test-NetConnection -ComputerName 127.0.0.1 -Port 5432
```

✅ **Esperado:** `TcpTestSucceeded : True`

---

### Passo 2️⃣: Executar Backup Completo

**Via PowerShell (Recomendado):**

```powershell
# Variáveis
$backupDir = "C:\Users\nee\Documents\Aksanti Referências\backups"
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$backupFile = "$backupDir\kaliye_backup_$timestamp.sql"
$pgPath = "C:\Program Files\PostgreSQL\18\bin\pg_dump.exe"

# Criar diretório se não existir
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    Write-Host "✅ Diretório criado: $backupDir" -ForegroundColor Green
}

# Executar backup
Write-Host "⏳ Iniciando backup..." -ForegroundColor Yellow
$env:PGPASSWORD = '5850'
& $pgPath -U postgres -h 127.0.0.1 kaliye | Out-File -Encoding UTF8 $backupFile

# Verificar resultado
if (Test-Path $backupFile) {
    $size = (Get-Item $backupFile).Length / 1MB
    Write-Host "✅ BACKUP CONCLUÍDO COM SUCESSO!" -ForegroundColor Green
    Write-Host "📁 Ficheiro: $backupFile" -ForegroundColor Green
    Write-Host "💾 Tamanho: $([Math]::Round($size, 2)) MB" -ForegroundColor Green
} else {
    Write-Host "❌ ERRO: Backup não foi criado!" -ForegroundColor Red
}
```

---

### Passo 3️⃣: Validar Backup

```powershell
# Verificar estrutura do backup
$backupFile = "C:\Users\nee\Documents\Aksanti Referências\backups\kaliye_backup_*.sql" | Get-Item | Sort-Object LastWriteTime -Descending | Select-Object -First 1

if ($backupFile) {
    Write-Host "✅ Ficheiro: $($backupFile.Name)" -ForegroundColor Green
    Write-Host "📊 Tamanho: $([Math]::Round($backupFile.Length / 1MB, 2)) MB" -ForegroundColor Green
    Write-Host "📅 Criado: $($backupFile.LastWriteTime)" -ForegroundColor Green
    Write-Host "📝 Primeiras linhas:" -ForegroundColor Cyan
    Get-Content $backupFile.FullName -Head 20
}
```

---

### Passo 4️⃣: Guardar Backup em Local Seguro

```powershell
# Copiar para múltiplos locais
$sourceBackup = Get-Item "C:\Users\nee\Documents\Aksanti Referências\backups\kaliye_backup_*.sql" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
$destLocations = @(
    "C:\Backups\",
    "D:\Backups\",
    "C:\Users\nee\Desktop\Backups\"
)

foreach ($dest in $destLocations) {
    if (Test-Path (Split-Path $dest)) {
        Copy-Item $sourceBackup.FullName -Destination $dest -Force
        Write-Host "✅ Backup guardado em: $dest" -ForegroundColor Green
    }
}
```

---

## 📋 CHECKLIST - FASE 1

- [ ] Verificou conectividade PostgreSQL
- [ ] Executou backup PowerShell
- [ ] Validou que ficheiro foi criado
- [ ] Confirmou tamanho do backup (deve ser > 1 MB)
- [ ] Guardou backup em local seguro (pen drive/cloud)
- [ ] Testou restauração em copy de teste (OPCIONAL)

---

## ⏭️ PRÓXIMAS FASES

### FASE 2: ANÁLISE FINAL (após aprovação)
- [ ] Revisar logs de análise
- [ ] Confirmar lista de tabelas com PM/Manager
- [ ] Planejar downtime (se necessário)

### FASE 3: ELIMINAÇÃO SEGURA
- [ ] Eliminar `test_table_1` (zero risco)
- [ ] Eliminar `test_table_2` (zero risco)
- [ ] Testar plataforma
- [ ] Eliminar `legacy_comments` (com cuidado)
- [ ] Testar novamente
- [ ] Arquivar `user_activity_logs` (ver com PM)

### FASE 4: DOCUMENTAÇÃO
- [ ] Registar tabelas eliminadas
- [ ] Documentar espaço libertado
- [ ] Atualizar diagrama de schema
- [ ] Comunicar ao team

---

## 🆘 PROBLEMAS COMUNS

### ❌ "Erro: pg_dump não encontrado"
**Solução:** Verifique caminho do PostgreSQL:
```powershell
Get-ChildItem "C:\Program Files\PostgreSQL\*\bin\pg_dump.exe"
```

### ❌ "Erro: conexão recusada"
**Solução:** Confirme que PostgreSQL está a correr:
```powershell
Get-Service postgresql-x64-18
```

### ❌ "Erro: password authentication failed"
**Solução:** Confirme password em `configuracoes/base_dados.php`

---

## 📞 CONTATO

Para dúvidas sobre este plano, consulte:
- Análise técnica: `ANALISE_TABELAS_GUIA_PASSO_A_PASSO.md`
- Ferramentas: `argumentos/README_ANALISE_TABELAS.md`
- Scripts: `argumentos/backup_base_dados.php`

**Última atualização:** 1 de junho de 2026  
**Versão:** 1.0
