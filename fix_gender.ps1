$path = "."
$extensions = "*.php","*.js","*.json","*.md","*.html"
$utf8NoBom = New-Object System.Text.UTF8Encoding $False

Get-ChildItem -Path $path -Recurse -Include $extensions | ForEach-Object {
    if ($_.FullName -notmatch "\\node_modules\\" -and $_.FullName -notmatch "\\\.git\\") {
        $content = [System.IO.File]::ReadAllText($_.FullName, $utf8NoBom)
        $original = $content

        # Fix gender mismatches caused by 'ideia' -> 'projecto'
        
        # Singular
        $content = [regex]::Replace($content, '\buma projecto\b', 'um projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\buma boa projecto\b', 'um bom projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\ba tua projecto\b', 'o teu projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\btua projecto\b', 'teu projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\ba sua projecto\b', 'o seu projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bsua projecto\b', 'seu projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\ba minha projecto\b', 'o meu projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bminha projecto\b', 'meu projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\baquela projecto\b', 'aquele projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bda projecto\b', 'do projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bna projecto\b', 'no projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bnesta projecto\b', 'neste projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bessa projecto\b', 'esse projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\besta projecto\b', 'este projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bnova projecto\b', 'novo projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bprimeira projecto\b', 'primeiro projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\boutra projecto\b', 'outro projecto', 'IgnoreCase')
        
        # Specific phrasing
        $content = [regex]::Replace($content, '\bQual é a projecto\b', 'Qual é o projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\ba projecto\b', 'o projecto', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bPROJECTO / PROJECTO\b', 'PROJECTO', 'IgnoreCase')
        $content = [regex]::Replace($content, '\buma projecto não age sozinha\b', 'um projecto não age sozinho', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bUma projecto não age sozinha\b', 'Um projecto não age sozinho', 'IgnoreCase')
        
        # Plural
        $content = [regex]::Replace($content, '\bumas projectos\b', 'uns projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bas tuas projectos\b', 'os teus projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\btuas projectos\b', 'teus projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bas suas projectos\b', 'os seus projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bsuas projectos\b', 'seus projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bas minhas projectos\b', 'os meus projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bminhas projectos\b', 'meus projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\baquelas projectos\b', 'aqueles projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bdas projectos\b', 'dos projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bnas projectos\b', 'nos projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bnestas projectos\b', 'nestes projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bessas projectos\b', 'esses projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bestas projectos\b', 'estes projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bnovas projectos\b', 'novos projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bprimeiras projectos\b', 'primeiros projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\boutras projectos\b', 'outros projectos', 'IgnoreCase')
        $content = [regex]::Replace($content, '\bas projectos\b', 'os projectos', 'IgnoreCase')

        if ($original -cne $content) {
            [System.IO.File]::WriteAllText($_.FullName, $content, $utf8NoBom)
        }
    }
}
