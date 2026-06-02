# 🚀 CHECKLIST PRÉ-LANÇAMENTO - VALIDAÇÕES CRÍTICAS
**Data: 02 de Junho de 2026**  
**Hora de Lançamento: HOJE À NOITE**

---

## ✅ O QUE JÁ FOI IMPLEMENTADO

### 1. **Backup Seguro**
- ✅ `BACKUP_CRITICO_20260602_152246` criado em `c:\Users\nee\Documents\BACKUPS\`
- ✅ Cópia completa do projeto pronta para recuperação

### 2. **Bibliotecas Centralizadas**
- ✅ [interface_programacao/validacoes.php](../interface_programacao/validacoes.php)
  - Classe `ValidadorCampos` com 12+ métodos
  - Funções de resposta JSON seguras
  - Sem exposição de BD

- ✅ [recursos/js/validacoes_form.js](../recursos/js/validacoes_form.js)
  - Classe `ValidadorFormulario` 
  - Validação em tempo real
  - Notificações elegantes
  - Contador de caracteres

- ✅ [recursos/css/validacoes.css](../recursos/css/validacoes.css)
  - Sinalização visual de campos obrigatórios
  - Estados is-valid / is-invalid
  - Notificações bonitas
  - Responsivo + modo escuro

### 3. **Formulários já Atualizados**
- ✅ [autenticacao/entrar.php](../autenticacao/entrar.php)
  - Email: validação obrigatória + tipo email
  - Password: validação obrigatória + minlength 8
  - Labels com sinalização obrigatória
  - CSS e JS incluídos

- ✅ [autenticacao/registar.php](../autenticacao/registar.php)
  - Nome: validação letras-apenas obrigatória
  - Email: validação email obrigatória
  - Telefone: validação telefone obrigatória
  - Password: minlength 8, obrigatória
  - CSS e JS incluídos

### 4. **Exemplos e Documentação**
- ✅ [inclusoes/components/exemplo_campos_validacao.php](../inclusoes/components/exemplo_campos_validacao.php)
  - 9 exemplos práticos de uso
  - Copiar-colar ready

- ✅ [interface_programacao/exemplo_validacao_backend.php](../interface_programacao/exemplo_validacao_backend.php)
  - 5 exemplos de validação no backend
  - Como tratar erros seguramente

- ✅ [documentos/GUIA_IMPLEMENTACAO_VALIDACOES.md](../documentos/GUIA_IMPLEMENTACAO_VALIDACOES.md)
  - Guia completo passo-a-passo
  - Todos os tipos de validação
  - Boas práticas de segurança

---

## 📋 PRÓXIMOS PASSOS - ANTES DO LANÇAMENTO

### ⚠️ **CRÍTICO - FAZER AGORA:**

#### 1️⃣ Aplicar Validações aos Formulários Prioritários
```
Prioridade 1 (HOJE):
- [x] autenticacao/entrar.php
- [x] autenticacao/registar.php
- [ ] paginas/conta/completar_perfil.php
- [ ] inclusoes/components/doubt_modal.php

Prioridade 2 (Se houver tempo):
- [ ] inclusoes/components/edit_profile_modal.php
- [ ] administracao/marketing/form_ad.php
- [ ] administracao/users/admins.php
```

#### 2️⃣ Atualizar os Endpoints de Processamento
```
Arquivos a atualizar:
- interface_programacao/auth/login_action.php
- interface_programacao/auth/register_action.php
- interface_programacao/auth/api_*.php
```

**O que fazer:**
1. Adicionar `require_once '/interface_programacao/validacoes.php';`
2. Substituir validações manuais por `ValidadorCampos::validarMultiplos()`
3. Garantir que erros usam `retornarErroGenerico()` (sem BD)
4. Testar cada endpoint

#### 3️⃣ Testar Formulários Críticos
```
TESTE PARA CADA FORMULÁRIO:

[ ] Campos obrigatórios aparecem marcados com *
[ ] Tentar enviar sem dados → mensagem de erro aparece
[ ] Email inválido → erro "Email inválido"
[ ] Número com letras → erro "Apenas números"
[ ] Limite de caracteres funciona
[ ] Contador de caracteres atualiza em tempo real
[ ] Notificações de sucesso aparecem
[ ] Notificações de erro aparecem (sem BD)
[ ] Responsivo em mobile (testar width < 480px)
```

#### 4️⃣ Testar Segurança

```
[ ] Tentar SQL injection no email → mensagem genérica
[ ] Tentar ver source HTML → sem detalhes BD
[ ] Abrir console → erros não expõem senhas
[ ] Logout e tentar acesso → redirecionado
```

---

## 🔧 IMPLEMENTAÇÃO RÁPIDA PARA QUALQUER FORMULÁRIO

### Passo 1: Adicionar no HEAD
```html
<link rel="stylesheet" href="/recursos/css/validacoes.css">
```

### Passo 2: Adicionar em cada INPUT
```html
<input 
    type="email" 
    name="email"
    data-tipo="email"
    data-obrigatorio="true"
    required
>
```

### Passo 3: Adicionar no BODY (final)
```html
<script src="/recursos/js/validacoes_form.js"></script>
<script>
    ValidadorFormulario.inicializarFormulario('meuFormulario');
</script>
```

### Passo 4: Backend - Validar e responder
```php
<?php
require_once '/interface_programacao/validacoes.php';

$validacao = ValidadorCampos::validarMultiplos([
    'Email' => ['valor' => $_POST['email'], 'regras' => ['email' => true]]
]);

if (!$validacao['valido']) {
    retornarErroValidacao('Erro', $validacao['erros']);
}

// Processar...
retornarSucesso('Sucesso!');
?>
```

---

## 📊 TIPOS DE VALIDAÇÃO IMPLEMENTADOS

| Tipo | Uso | Exemplo |
|------|-----|---------|
| `email` | Emails | `data-tipo="email"` |
| `numeros-apenas` | ID, idade, telefone | `data-tipo="numeros-apenas"` |
| `letras-apenas` | Nomes | `data-tipo="letras-apenas"` |
| `alfanumerico` | Códigos | `data-tipo="alfanumerico"` |
| `telefone` | Telefones | `data-tipo="telefone"` |
| `url` | Websites | `data-tipo="url"` |
| `comentario` | Textos 250-300 chars | `data-tipo="comentario"` |
| `senha` | Passwords | `data-tipo="senha"` |
| `obrigatorio` | Qualquer campo | `data-obrigatorio="true"` |

---

## 🎨 SINALIZAÇÃO VISUAL

### Campos Obrigatórios
```html
<label class="label-obrigatorio">Nome *</label>
<!-- Resultado: "Nome *" em vermelho -->
```

### Mensagens de Erro
```
✓ Aparece abaixo do campo em VERMELHO
✓ Não expõe detalhes técnicos
✓ Mensagens amigáveis
```

### Contador de Caracteres
```
✓ Atualiza em tempo real (0/250)
✓ Muda cor quando próximo do limite
✓ Barra de progresso visual
```

---

## ⚠️ SEGURANÇA - CHECKLIST

```
[ ] Nunca expor mensagens de erro do PDO/Exception
[ ] Sempre usar ValidadorCampos::sanitizar()
[ ] Validar no backend SEMPRE (não confiar no frontend)
[ ] Usar prepared statements (PDO)
[ ] Hash de passwords com PASSWORD_BCRYPT
[ ] Não mostrar se email existe ou não (padrão "Email/Password incorretos")
[ ] Registar erros com error_log(), não die() ou echo
[ ] Testar com inputs maliciosos
```

---

## 📱 RESPONSIVIDADE - VERIFICAR

```
[ ] Desktop (1920×1080): tudo funciona
[ ] Tablet (768×1024): layout se adapta
[ ] Mobile (375×667): campos têm espaço, botões clicáveis
[ ] Notificações aparecem bem em mobile
[ ] Contador de caracteres visível em mobile
```

---

## 🧪 TESTE MANUAL RÁPIDO

### Teste do Login:
1. Abrir [autenticacao/entrar.php](../autenticacao/entrar.php)
2. Tentar enviar vazio → **erro**
3. Email inválido → **erro**
4. Password < 8 caracteres → **erro**
5. Credenciais corretas → **sucesso com redirecionamento**

### Teste do Registro:
1. Abrir [autenticacao/registar.php](../autenticacao/registar.php)
2. Nome com números → **erro**
3. Email já existente → **erro customizado**
4. Telefone inválido → **erro**
5. Tudo correto → **sucesso**

---

## 📞 EM CASO DE PROBLEMA

### Erro: "Campos não validam"
```
Solução:
1. Verificar se JS está carregado: F12 > Console > erros?
2. Verificar se data-tipo está correto
3. Verificar se ValidadorFormulario foi inicializado
```

### Erro: "Mensagens expostas"
```
Solução:
1. Usar retornarErroGenerico() em vez de echo/die
2. Envolver BD em try-catch
3. Usar error_log() para debug
```

### Erro: "Limite de caracteres não funciona"
```
Solução:
1. Adicionar data-tamanho-maximo="250" no textarea
2. Verificar se data-tipo="comentario"
3. Inicializar ValidadorFormulario
```

---

## ✅ FINAL - PRONTO PARA LANÇAMENTO?

**Assine quando tudo estiver pronto:**

- [ ] Todos os formulários têm validações
- [ ] Todos os endpoints validam no backend
- [ ] Teste manual passou 100%
- [ ] Sem erros técnicos expostos
- [ ] Backup confirmado
- [ ] Responsivo testado

---

**Data de Lançamento: HOJE - 02 de Junho de 2026**  
**Status: EM PROGRESSO - Aguardando sua confirmação** ✅

