# RELATÓRIO DE INVESTIGAÇÃO - Campo Location

## Sumário Executivo

Investigação realizada em 31 de maio de 2026 sobre possíveis problemas de dados incorretos no campo `location` da tabela `users`.

### Conclusão Preliminar
**✓ Código está correto** - Nenhuma evidência de scripts de migração ou código que preencha `location` com dados de `focus_areas` ou `specialization_tags`.

---

## 1. Definição do Campo

- **Nome do Campo**: `location` 
- **Tipo de Dados**: `VARCHAR(160)`
- **Valores Esperados**: Nomes de províncias de Angola (Luanda, Benguela, Huíla, Cabinda, etc.)
- **Campo Obrigatório**: SIM (validação em update_profile.php)

---

## 2. Locais de Atualização do Campo

### Principal - [interface_programacao/user/update_profile.php](interface_programacao/user/update_profile.php)
- **Linha 188**: `$location = cleanProfileText($_POST['location'] ?? '', 160);`
- **Linha 205**: Validação - campo obrigatório
- **Linha 243**: SQL UPDATE: `location = :location` com dados de `$_POST['location']`
- **Status**: ✓ Correto

### Leitura de Dados
- [interface_programacao/user/get_my_profile.php](interface_programacao/user/get_my_profile.php) (Linha 64)
- [interface_programacao/user/get_user_card.php](interface_programacao/user/get_user_card.php) (Linha 49)
- Ambos retornam corretamente o valor de location

---

## 3. Formulários que Preenchem Location

### 1. Edit Profile Modal
- **Arquivo**: `inclusoes/components/edit_profile_modal.php` (Linhas 72-88)
- **Tipo**: Select dropdown com 18 províncias angolanas
- **Aba**: "Contacto"

### 2. Profile Settings
- **Arquivo**: `inclusoes/components/profile_settings_content.php` (Linhas 33-38)
- **Tipo**: Select dropdown com 4 províncias (Luanda, Benguela, Huíla, Cabinda)

---

## 4. Scripts de Migração Analisados

### Nenhum script encontrado que mexa com location incorretamente

| Script | Status | Observação |
|--------|--------|-----------|
| phase1_simplification.php | ✓ | Apenas remove colunas de gamificação |
| db_fix.php | ✓ | Apenas cria project_media |
| users_fix.php | ✓ | Verifica profile_pic e academic_info |
| restore_database.php | ✓ | Define location apenas em institutions |
| smart_fix_utf8.php | ✓ | Corrige encoding em knowledge_areas |
| cleanup_database.php | ✓ | Remove tabelas desnecessárias |

---

## 5. Campos Relacionados - SEPARADOS E CORRETOS

### specialization_tags (TEXT)
- **Armazena**: Skills/especialização (ex: "Python, JavaScript, Design")
- **Atualizado em**: update_profile.php linha 244 com valor correto
- **Lido em**: get_my_profile.php, get_user_card.php

### focus_areas (TEXT)
- **Armazena**: Áreas de foco (ex: "Inteligência Artificial, Finanças, Educação")
- **Atualizado em**: update_profile.php linha 252 com valor correto
- **Lido em**: get_my_profile.php, get_user_card.php

**✓ Nenhuma mistura de dados encontrada**

---

## 6. Possíveis Causas de Anomalias

Se há dados errados em `location`, as causas podem ser:

1. **Importação histórica** - Dados importados de sistema anterior
2. **Entrada manual errada** - Utilizadores preencheram o dropdown incorretamente
3. **SQL direto** - UPDATEs executadas diretamente sem passar pela API
4. **Scripts em lixo_temporarios** - Possível teste antigo

---

## 7. Scripts de Verificação Criados

### verify_location_data.php
**Localização**: `argumentos/debug/verify_location_data.php`

**Funcionalidades**:
1. Verifica estrutura da tabela users
2. Conta registos com dados preenchidos
3. Lista valores únicos em location
4. Procura anomalias (múltiplas categorias)
5. Compara location com focus_areas e specialization_tags
6. Mostra amostras de dados

**Como executar**:
```
http://localhost/aksanti/argumentos/debug/verify_location_data.php
```

---

## 8. Recomendações

### Imediatas
1. **Executar o script de verificação** para obter dados reais da tabela
2. **Se anomalias encontradas**: Executar script de correção

### Preventivas
1. Adicionar validação frontend para garantir que location é uma província válida
2. Implementar ENUM em location com províncias pré-definidas
3. Adicionar logs em update_profile.php para rastrear mudanças

### Scripts Adicionais Recomendados
- Script para corrigir dados errados (se necessário)
- Script para audit trail de mudanças em location
- Validação com lista branca de províncias

---

## Arquivos Consultados

### Código Principal
- `interface_programacao/user/update_profile.php` ✓
- `interface_programacao/user/get_my_profile.php` ✓
- `interface_programacao/user/get_user_card.php` ✓
- `inclusoes/components/edit_profile_modal.php` ✓
- `inclusoes/components/profile_settings_content.php` ✓

### Migrações
- Pasta: `argumentos/migrations/` (45 arquivos analisados)
- Pasta: `argumentos/` (scripts gerais analisados)

### Lixo Temporário
- `_lixo_temporarios_raiz_20260530/` (arquivos de teste ignorados)

---

## Próximos Passos

1. Executar **verify_location_data.php** para diagnosticar dados reais
2. Aguardar output para determinar se há anomalias
3. Se houver anomalias, criar script de correção específico
4. Implementar validações adicionais para evitar novos problemas

---

**Data**: 31 de maio de 2026  
**Analista**: GitHub Copilot  
**Status**: Investigação em Progresso - Aguardando Execução de Verificação
