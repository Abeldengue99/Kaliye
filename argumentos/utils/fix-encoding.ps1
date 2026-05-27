#!/usr/bin/env pwsh
# Script para verificar e corrigir encoding UTF-8 em todos os arquivos PHP

$ErrorActionPreference = "Continue"

Write-Host "`n=== ANÁLISE PROFUNDA DE ENCODING UTF-8 ===" -ForegroundColor Cyan
Write-Host "Procurando problemas de encoding em arquivos PHP...`n" -ForegroundColor Yellow

# Padrões problemáticos comuns
$badPatterns = @{
    'Ã§Ã£o' = 'ção'
    'Ã§Ãµes' = 'ções'
    'Ã§Ã£' = 'çã'
    'VerificaÃ§Ãµes' = 'Verificações'
    'AprovaÃ§Ã£o' = 'Aprovação'
    'ModeraÃ§Ã£o' = 'Moderação'
    'GestÃ£o' = 'Gestão'
    'ConteÃºdo' = 'Conteúdo'
    'NÂº' = 'Nº'
    'Ã§' = 'ç'
    'Ã£' = 'ã'
    'Ãµ' = 'õ'
    'Ã©' = 'é'
    'Ã­' = 'í'
    'Ã³' = 'ó'
    'Ãº' = 'ú'
    'Ã¡' = 'á'
    'Ã¢' = 'â'
    'Ãª' = 'ê'
    'Ã´' = 'ô'
}

$filesWithIssues = @()
$filesFixed = 0
$totalIssues = 0

# Buscar todos os arquivos PHP
$phpFiles = Get-ChildItem -Path "." -Include "*.php" -Recurse | Where-Object { 
    $_.FullName -notmatch "vendor|node_modules|\.git" 
}

Write-Host "Analisando $($phpFiles.Count) arquivos PHP...`n" -ForegroundColor Green

foreach($file in $phpFiles) {
    try {
        $content = Get-Content $file.FullName -Raw -Encoding UTF8
        $originalContent = $content
        $fileHasIssues = $false
        $issuesInFile = 0
        
        foreach($pattern in $badPatterns.Keys) {
            if($content -match [regex]::Escape($pattern)) {
                $fileHasIssues = $true
                $matches = ([regex]::Matches($content, [regex]::Escape($pattern))).Count
                $issuesInFile += $matches
                $totalIssues += $matches
                $content = $content -replace [regex]::Escape($pattern), $badPatterns[$pattern]
                
                Write-Host "  ✗ $($file.Name): Encontrado '$pattern' ($matches ocorrências)" -ForegroundColor Red
            }
        }
        
        if($fileHasIssues) {
            $filesWithIssues += [PSCustomObject]@{
                Path = $file.FullName
                RelativePath = $file.FullName.Replace((Get-Location).Path, ".")
                Issues = $issuesInFile
            }
            
            # Salvar arquivo corrigido
            [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.UTF8Encoding]::new($false))
            $filesFixed++
            Write-Host "  ✓ CORRIGIDO: $($file.Name)" -ForegroundColor Green
        }
    }
    catch {
        Write-Host "  ! ERRO ao processar $($file.Name): $_" -ForegroundColor Yellow
    }
}

Write-Host "`n=== RELATÓRIO FINAL ===" -ForegroundColor Cyan
Write-Host "Arquivos analisados: $($phpFiles.Count)" -ForegroundColor White
Write-Host "Arquivos com problemas: $($filesWithIssues.Count)" -ForegroundColor $(if($filesWithIssues.Count -gt 0) {"Red"} else {"Green"})
Write-Host "Arquivos corrigidos: $filesFixed" -ForegroundColor Green
Write-Host "Total de problemas corrigidos: $totalIssues`n" -ForegroundColor Yellow

if($filesWithIssues.Count -gt 0) {
    Write-Host "Arquivos que foram corrigidos:" -ForegroundColor Cyan
    $filesWithIssues | ForEach-Object {
        Write-Host "  • $($_.RelativePath) ($($_.Issues) problemas)" -ForegroundColor White
    }
} else {
    Write-Host "✓ Nenhum problema de encoding encontrado! Todos os arquivos estão corretos." -ForegroundColor Green
}

Write-Host "`n=== ANÁLISE COMPLETA ===" -ForegroundColor Cyan
