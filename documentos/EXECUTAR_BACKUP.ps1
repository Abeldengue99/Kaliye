# Script de Backup PostgreSQL - KALIYE AKSANTI
# Data: 1 de junho de 2026

$backupDir = "C:\Users\nee\Documents\Aksanti Referências\backups"
$pgHost = "127.0.0.1"
$pgPort = 5432
$pgUser = "postgres"
$pgPassword = "5850"
$dbName = "kaliye"
$pgPath = "C:\Program Files\PostgreSQL\18\bin\pg_dump.exe"

if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
}

if (-not (Test-Path $pgPath)) {
    Write-Host "ERRO: pg_dump nao encontrado" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "====================================================" -ForegroundColor Cyan
Write-Host "BACKUP POSTGRESQL - KALIYE AKSANTI" -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan
Write-Host ""

$testConnection = Test-NetConnection -ComputerName $pgHost -Port $pgPort -WarningAction SilentlyContinue
if (-not $testConnection.TcpTestSucceeded) {
    Write-Host "ERRO: Nao conseguiu conectar PostgreSQL" -ForegroundColor Red
    exit 1
}

Write-Host "Conexao OK" -ForegroundColor Green
Write-Host ""

$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$backupFile = Join-Path $backupDir "kaliye_backup_$timestamp.sql"

Write-Host "Executando backup..." -ForegroundColor Yellow
Write-Host ""

$env:PGPASSWORD = $pgPassword
& $pgPath -h $pgHost -p $pgPort -U $pgUser -F p $dbName | Out-File -Encoding UTF8 -FilePath $backupFile
$result = $LASTEXITCODE

if ($result -eq 0 -and (Test-Path $backupFile)) {
    $fileSize = (Get-Item $backupFile).Length
    $fileSizeMB = [Math]::Round($fileSize / 1MB, 2)
    
    if ($fileSize -gt 1000) {
        Write-Host "SUCESSO!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Ficheiro: $(Split-Path -Leaf $backupFile)" -ForegroundColor Green
        Write-Host "Tamanho: $fileSizeMB MB" -ForegroundColor Green
        Write-Host "Caminho: $backupFile" -ForegroundColor Green
    } else {
        Write-Host "ERRO: Ficheiro vazio" -ForegroundColor Red
    }
} else {
    Write-Host "ERRO ao executar backup" -ForegroundColor Red
}

Write-Host ""
Write-Host "====================================================" -ForegroundColor Cyan
