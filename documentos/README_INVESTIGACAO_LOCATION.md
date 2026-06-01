# INVESTIGAÇÃO - Problema com Campo Location

## 📋 Resumo Rápido

Investigação realizada sobre possíveis dados incorretos no campo `location` da tabela `users`.

**Status**: ✓ **Código está correto**  
**Achados**: Nenhuma evidência de scripts misturando dados  
**Ação Necessária**: Verificar dados reais no banco de dados

---

## 🔍 O Que Foi Feito

### 1. Análise de Código
- ✓ Analisados 45+ arquivos de migração
- ✓ Verificados formulários de entrada (edit_profile_modal.php)
- ✓ Analisado código de atualização (update_profile.php)
- ✓ Verificadas APIs de leitura (get_my_profile.php, get_user_card.php)
- ✓ Procurado por UPDATE direto em location

**Resultado**: Nenhum script fazendo UPDATE incorreto em location encontrado

### 2. Verificação de Campos Relacionados
- ✓ `specialization_tags` - Armazena skills (ex: "Python, JavaScript")
- ✓ `focus_areas` - Armazena áreas de foco (ex: "IA, Finanças")
- ✓ `location` - Deveria armazenar APENAS uma província

**Resultado**: Campos estão corretamente separados no código

---

## 📁 Arquivos Criados para Diagnóstico

### 1. **verify_location_data.php** ⭐ EXECUTAR PRIMEIRO
**Localização**: `argumentos/debug/verify_location_data.php`

**O que faz**:
- Verifica estrutura da tabela
- Lista valores únicos em location
- Procura anomalias (múltiplas categorias)
- Mostra amostras de 5 utilizadores
- Compara location com focus_areas

**Como usar**:
```
http://localhost/aksanti/argumentos/debug/verify_location_data.php
```

**Tempo de execução**: <5 segundos

---

### 2. **fix_location_data.php** ⚠️ USE COM CUIDADO
**Localização**: `argumentos/debug/fix_location_data.php`

**O que faz**:
- Modo REPORT: Apenas mostra problemas (seguro)
- Modo FIX: Corrige dados (perigoso!)

**Como usar**:

#### Modo Análise (SEGURO)
```
http://localhost/aksanti/argumentos/debug/fix_location_data.php?mode=report
```

#### Modo Correção (APENAS APÓS CONFIRMAR ANOMALIAS)
```
// Defini location = 'Luanda' para campos vazios
http://localhost/aksanti/argumentos/debug/fix_location_data.php?mode=fix&confirm=yes&fix_type=default

// Extrai primeira palavra válida de location
http://localhost/aksanti/argumentos/debug/fix_location_data.php?mode=fix&confirm=yes&fix_type=extract

// Limpa dados errados (perigoso!)
http://localhost/aksanti/argumentos/debug/fix_location_data.php?mode=fix&confirm=yes&fix_type=clear
```

---

### 3. Documentação
- **RELATORIO_INVESTIGACAO_LOCATION.md** - Relatório completo
- **ANALISE_TECNICA_LOCATION.md** - Análise técnica do fluxo de dados

---

## 🎯 Fluxo de Investigação Recomendado

### Passo 1: Diagnóstico
```
1. Executar: verify_location_data.php?mode=report
2. Analisar output
3. Se há anomalias → Passo 2
4. Se dados OK → Fim (sem problemas encontrados)
```

### Passo 2: Verificar Anomalias
```
1. Executar: verify_location_data.php?mode=report
2. Observar quantos registos têm:
   - location com múltiplas categorias (,)
   - location muito longo (>50 chars)
   - location vazio
```

### Passo 3: Análise Visual
```
1. Executar: fix_location_data.php?mode=report
2. Ver exemplos específicos de valores errados
3. Determinar tipo de correção necessária
```

### Passo 4: Correção (SE NECESSÁRIO)
```
1. Fazer BACKUP do banco de dados
2. Executar correção apropriada:
   - ?fix_type=default  (para vazios)
   - ?fix_type=extract  (para múltiplos)
   - ?fix_type=clear    (para dados muito errados)
3. Verificar novamente com verify_location_data.php
```

---

## 📊 Interpretação do Output

### Do verify_location_data.php

```
Total de utilizadores: 150
Location preenchido: 145        ← Bom (95% preenchido)
Focus areas preenchido: 120     ← Ok (80% preenchido)
Specialization preenchido: 100  ← Ok (67% preenchido)
```

### Valores ÚNICOS em location esperados
```
- Bengo
- Benguela
- Bié
- Cabinda
- Cuando Cubango
... (18 províncias total)
```

### ANOMALIAS A PROCURAR
❌ **Suspeito**:
- Location com vírgulas: "Python, JavaScript"
- Location muito longo: "Inteligência Artificial, Finanças, Educação, ..."
- Location vazio: NULL ou ""

✓ **OK**:
- Location com uma palavra: "Luanda"
- Location com 2-3 palavras: "Cuando Cubango"

---

## 🔧 Locais de Código Críticos

Se precisar modificar ou auditar, consulte:

| Arquivo | Linhas | O que faz |
|---------|--------|----------|
| update_profile.php | 188 | Extrai location do POST |
| update_profile.php | 205 | Valida se está preenchido |
| update_profile.php | 243 | UPDATE na BD |
| get_my_profile.php | 64 | SELECT de location |
| get_user_card.php | 49 | SELECT de location |
| edit_profile_modal.php | 72-88 | Dropdown de províncias |

---

## ✅ Checklist de Verificação

- [ ] Executei verify_location_data.php
- [ ] Verifiquei se location tem dados válidos (uma só palavra)
- [ ] Contei quantos registos têm location vazio
- [ ] Procurei por anomalias (múltiplas categorias)
- [ ] Se há anomalias, identifiquei o tipo (vazio vs errado)
- [ ] Fiz backup antes de executar correção
- [ ] Executei fix_location_data.php com correção apropriada
- [ ] Verifiquei novamente após correção

---

## ⚠️ IMPORTANTE

### Backup Antes de Corrigir
```sql
-- Fazer antes de executar fix_location_data.php
CREATE TABLE users_backup_20260531 AS SELECT * FROM users;
```

### Desfazer Mudanças
```sql
-- Se algo correu mal
RESTORE TABLE users FROM 'backup';
-- ou
UPDATE users u JOIN users_backup_20260531 b 
SET u.location = b.location WHERE u.user_id = b.user_id;
```

---

## 📞 Próximos Passos

1. ✅ Executar `verify_location_data.php` para diagnóstico
2. 📊 Analisar output
3. 🔧 Se necessário, executar `fix_location_data.php`
4. ✓ Verificar novamente para confirmar

**Dúvidas sobre o output?** Consulte a seção "Interpretação do Output" acima.

---

**Data**: 31 de maio de 2026  
**Versão**: 1.0  
**Status**: Pronto para Execução
