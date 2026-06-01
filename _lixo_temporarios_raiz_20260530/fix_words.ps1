$path = "."
$extensions = "*.php","*.js","*.json","*.md","*.html"

Get-ChildItem -Path $path -Recurse -Include $extensions | ForEach-Object {
    if ($_.FullName -notmatch "\\node_modules\\" -and $_.FullName -notmatch "\\\.git\\") {
        $content = Get-Content $_.FullName -Raw
        $original = $content

        # Replace Ideia with Projecto
        $content = [regex]::Replace($content, '\bIdeias\b', 'Projectos')
        $content = [regex]::Replace($content, '\bideias\b', 'projectos')
        $content = [regex]::Replace($content, '\bIDEIAS\b', 'PROJECTOS')
        $content = [regex]::Replace($content, '\bIdeia\b', 'Projecto')
        $content = [regex]::Replace($content, '\bideia\b', 'projecto')
        $content = [regex]::Replace($content, '\bIDEIA\b', 'PROJECTO')
        
        # Replace missing accents
        $content = [regex]::Replace($content, '\bnao\b', 'não')
        $content = [regex]::Replace($content, '\bNao\b', 'Não')
        $content = [regex]::Replace($content, '\bsao\b', 'são')
        $content = [regex]::Replace($content, '\bSao\b', 'São')
        $content = [regex]::Replace($content, '\btambem\b', 'também')
        $content = [regex]::Replace($content, '\bTambem\b', 'Também')
        $content = [regex]::Replace($content, '\bvoce\b', 'você')
        $content = [regex]::Replace($content, '\bVoce\b', 'Você')
        $content = [regex]::Replace($content, '\batraves\b', 'através')
        $content = [regex]::Replace($content, '\bAtraves\b', 'Através')
        $content = [regex]::Replace($content, '\bacao\b', 'acção')
        $content = [regex]::Replace($content, '\bAcao\b', 'Acção')
        $content = [regex]::Replace($content, '\bacoes\b', 'acções')
        $content = [regex]::Replace($content, '\bAcoes\b', 'Acções')
        $content = [regex]::Replace($content, '\bavancar\b', 'avançar')
        $content = [regex]::Replace($content, '\bAvancar\b', 'Avançar')
        $content = [regex]::Replace($content, '\bexperiencia\b', 'experiência')
        $content = [regex]::Replace($content, '\bExperiencia\b', 'Experiência')
        $content = [regex]::Replace($content, '\bconcluido\b', 'concluído')
        $content = [regex]::Replace($content, '\bConcluido\b', 'Concluído')
        $content = [regex]::Replace($content, '\bestrategia\b', 'estratégia')
        $content = [regex]::Replace($content, '\bEstrategia\b', 'Estratégia')
        $content = [regex]::Replace($content, '\binovacao\b', 'inovação')
        $content = [regex]::Replace($content, '\bInovacao\b', 'Inovação')
        $content = [regex]::Replace($content, '\bvisao\b', 'visão')
        $content = [regex]::Replace($content, '\bVisao\b', 'Visão')
        $content = [regex]::Replace($content, '\bmissao\b', 'missão')
        $content = [regex]::Replace($content, '\bMissao\b', 'Missão')
        
        if ($original -cne $content) {
            # Write back with original encoding
            Set-Content -Path $_.FullName -Value $content -Encoding UTF8
        }
    }
}
