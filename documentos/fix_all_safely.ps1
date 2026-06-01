$path = "."
$extensions = "*.php","*.js","*.json","*.md","*.html"
$utf8NoBom = New-Object System.Text.UTF8Encoding $False

Get-ChildItem -Path $path -Recurse -Include $extensions | ForEach-Object {
    if ($_.FullName -notmatch "\\node_modules\\" -and $_.FullName -notmatch "\\\.git\\") {
        # CRITICAL: Read assuming UTF-8 to prevent ANSI fallback
        $content = [System.IO.File]::ReadAllText($_.FullName, $utf8NoBom)
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
        $content = [regex]::Replace($content, '\binformacao\b', 'informação')
        $content = [regex]::Replace($content, '\bInformacao\b', 'Informação')
        $content = [regex]::Replace($content, '\bpublicacao\b', 'publicação')
        $content = [regex]::Replace($content, '\bPublicacao\b', 'Publicação')
        $content = [regex]::Replace($content, '\bvalidacao\b', 'validação')
        $content = [regex]::Replace($content, '\bValidacao\b', 'Validação')
        $content = [regex]::Replace($content, '\bdescricao\b', 'descrição')
        $content = [regex]::Replace($content, '\bDescricao\b', 'Descrição')
        $content = [regex]::Replace($content, '\bhistorico\b', 'histórico')
        $content = [regex]::Replace($content, '\bHistorico\b', 'Histórico')
        $content = [regex]::Replace($content, '\brelatorio\b', 'relatório')
        $content = [regex]::Replace($content, '\bRelatorio\b', 'Relatório')
        $content = [regex]::Replace($content, '\bduvida\b', 'dúvida')
        $content = [regex]::Replace($content, '\bDuvida\b', 'Dúvida')
        $content = [regex]::Replace($content, '\bduvidas\b', 'dúvidas')
        $content = [regex]::Replace($content, '\bDuvidas\b', 'Dúvidas')
        $content = [regex]::Replace($content, '\bpossivel\b', 'possível')
        $content = [regex]::Replace($content, '\bPossivel\b', 'Possível')
        $content = [regex]::Replace($content, '\bpermissao\b', 'permissão')
        $content = [regex]::Replace($content, '\bPermissao\b', 'Permissão')
        $content = [regex]::Replace($content, '\bproprio\b', 'próprio')
        $content = [regex]::Replace($content, '\bProprio\b', 'Próprio')
        $content = [regex]::Replace($content, '\bseguranca\b', 'segurança')
        $content = [regex]::Replace($content, '\bSeguranca\b', 'Segurança')
        $content = [regex]::Replace($content, '\brapido\b', 'rápido')
        $content = [regex]::Replace($content, '\bRapido\b', 'Rápido')
        $content = [regex]::Replace($content, '\btecnico\b', 'técnico')
        $content = [regex]::Replace($content, '\bTecnico\b', 'Técnico')
        $content = [regex]::Replace($content, '\bacademico\b', 'académico')
        $content = [regex]::Replace($content, '\bAcademico\b', 'Académico')
        $content = [regex]::Replace($content, '\bproxima\b', 'próxima')
        $content = [regex]::Replace($content, '\bProxima\b', 'Próxima')
        $content = [regex]::Replace($content, '\bsessao\b', 'sessão')
        $content = [regex]::Replace($content, '\bSessao\b', 'Sessão')
        $content = [regex]::Replace($content, '\baprovacao\b', 'aprovação')
        $content = [regex]::Replace($content, '\bAprovacao\b', 'Aprovação')
        
        if ($original -cne $content) {
            [System.IO.File]::WriteAllText($_.FullName, $content, $utf8NoBom)
        }
    }
}
