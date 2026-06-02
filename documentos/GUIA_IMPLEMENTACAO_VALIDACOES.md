# 📋 GUIA DE IMPLEMENTAÇÃO - VALIDAÇÕES CRÍTICAS
**Lançamento: 02 de Junho de 2026**

---

## 📋 Resumo da Implementação

Este guia detalha como implementar o sistema de validações em todos os formulários do projeto.

**O que foi criado:**
1. ✅ [interface_programacao/validacoes.php](../../interface_programacao/validacoes.php) - Classe PHP ValidadorCampos
2. ✅ [recursos/js/validacoes_form.js](../../recursos/js/validacoes_form.js) - Classe JavaScript ValidadorFormulario
3. ✅ [recursos/css/validacoes.css](../../recursos/css/validacoes.css) - Estilos para sinalização
4. ✅ [inclusoes/components/exemplo_campos_validacao.php](exemplo_campos_validacao.php) - Exemplos práticos

---

## 🔧 Passo 1: Incluir as Bibliotecas

### No HEAD da página HTML/PHP:
```php
<!-- Incluir CSS de validações -->
<link rel="stylesheet" href="/recursos/css/validacoes.css">

<!-- Incluir Bootstrap (se não tiver) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
```

### Antes do fechamento do BODY:
```php
<!-- Incluir JS de validações -->
<script src="/recursos/js/validacoes_form.js"></script>

<!-- Incluir Bootstrap JS (se não tiver) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

### No PHP:
```php
<?php
require_once '/interface_programacao/validacoes.php';

// Usar as validações no backend
?>
```

---

## ✨ Passo 2: Usar a Sinalização de Campos Obrigatórios

### Opção A: Com Badge Visual
```html
<div class="form-group">
    <label for="email" class="label-obrigatorio">
        Email
        <span class="campo-obrigatorio-badge">Obrigatório</span>
    </label>
    <input 
        type="email" 
        class="form-control" 
        id="email" 
        name="email"
        required
    >
    <small class="invalid-feedback">Email é obrigatório</small>
</div>
```

### Opção B: Apenas com Asterisco
```html
<label for="nome" class="label-obrigatorio">Nome *</label>
<input 
    type="text" 
    class="form-control" 
    id="nome" 
    name="nome"
    required
>
```

---

## 📝 Passo 3: Implementar Validações de Tipo

### Exemplos por Tipo:

#### 1️⃣ **Campo Numérico (Só Números)**
```html
<input 
    type="text" 
    class="form-control" 
    id="idade" 
    name="idade" 
    placeholder="Apenas números"
    data-tipo="numeros-apenas"
    required
>
<small class="invalid-feedback">Digite apenas números</small>
```

#### 2️⃣ **Campo de Texto (Só Letras)**
```html
<input 
    type="text" 
    class="form-control" 
    id="nome" 
    name="nome" 
    placeholder="Apenas letras"
    data-tipo="letras-apenas"
    required
>
<small class="invalid-feedback">Apenas letras permitidas</small>
```

#### 3️⃣ **Campo Alfanumérico (Letras + Números)**
```html
<input 
    type="text" 
    class="form-control" 
    id="codigo" 
    name="codigo" 
    placeholder="Ex: PROJ-2026-001"
    data-tipo="alfanumerico"
    required
>
<small class="invalid-feedback">Caracteres especiais não permitidos</small>
```

#### 4️⃣ **Campo Email**
```html
<input 
    type="email" 
    class="form-control" 
    id="email" 
    name="email" 
    data-tipo="email"
    required
>
<small class="invalid-feedback">Email inválido</small>
```

#### 5️⃣ **Campo Telefone**
```html
<input 
    type="tel" 
    class="form-control" 
    id="telefone" 
    name="telefone" 
    placeholder="+244 923 456 789"
    data-tipo="telefone"
    required
>
<small class="invalid-feedback">Telefone inválido</small>
```

#### 6️⃣ **Campo URL**
```html
<input 
    type="url" 
    class="form-control" 
    id="site" 
    name="site" 
    placeholder="https://exemplo.com"
    data-tipo="url"
    required
>
<small class="invalid-feedback">URL inválida</small>
```

---

## 📏 Passo 4: Limitar Caracteres em Campos Longos

### Para Comentários de Motivação (250 caracteres):
```html
<div class="form-group">
    <label for="motivacao" class="label-obrigatorio">
        Comentário de Motivação
    </label>
    <textarea 
        class="form-control textarea-limitado" 
        id="motivacao" 
        name="motivacao"
        data-tipo="comentario"
        data-tamanho-maximo="250"
        placeholder="Máximo 250 caracteres"
        required
        rows="4"
    ></textarea>
    <div class="contador-caracteres normal" id="motivacao_contador">
        0/250 caracteres
    </div>
    <div class="progress-contador">
        <div class="progress-contador-bar normal" id="motivacao_progresso" style="width: 0%"></div>
    </div>
    <small class="invalid-feedback">Máximo de 250 caracteres</small>
</div>
```

### Para Descrições Longas (300 caracteres):
```html
<textarea 
    class="form-control textarea-limitado" 
    id="descricao" 
    name="descricao"
    data-tipo="comentario"
    data-tamanho-maximo="300"
    placeholder="Máximo 300 caracteres"
    required
    rows="5"
></textarea>
<small class="contador-caracteres normal" id="descricao_contador">
    0/300 caracteres
</small>
```

---

## ⚠️ Passo 5: Tratamento de Erros (Backend)

### Em seus APIs/endpoints (no arquivo de processamento):

```php
<?php
require_once '/interface_programacao/validacoes.php';

// Receber dados
$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$idade = $_POST['idade'] ?? '';
$motivacao = $_POST['motivacao'] ?? '';

// Criar array de validações
$campos = [
    'Nome' => [
        'valor' => $nome,
        'regras' => [
            'obrigatorio' => true,
            'letras' => true,
            'maximo' => 100
        ]
    ],
    'Email' => [
        'valor' => $email,
        'regras' => [
            'obrigatorio' => true,
            'email' => true
        ]
    ],
    'Idade' => [
        'valor' => $idade,
        'regras' => [
            'obrigatorio' => true,
            'numeros' => true
        ]
    ],
    'Motivação' => [
        'valor' => $motivacao,
        'regras' => [
            'obrigatorio' => true,
            'maximo' => 250
        ]
    ]
];

// Validar
$validacao = ValidadorCampos::validarMultiplos($campos);

if (!$validacao['valido']) {
    // Retornar erros SEM expor BD
    header('Content-Type: application/json');
    http_response_code(422);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Por favor, corrija os erros abaixo.',
        'erros' => $validacao['erros']
    ]);
    exit;
}

// Processar dados (sanitizar)
$nome = ValidadorCampos::sanitizar($nome);
$email = ValidadorCampos::sanitizar($email);

// Continuar com banco de dados...
try {
    // Suas operações de BD aqui
    
    retornarSucesso('Dados salvos com sucesso!', [
        'id' => $novoId
    ]);
} catch (Exception $e) {
    // IMPORTANTE: NÃO expor detalhes do erro
    retornarErroGenerico('Ocorreu um erro ao salvar os dados. Por favor, tente novamente.');
}
?>
```

---

## 🎨 Passo 6: Notificações Elegantes

### No JavaScript (após resposta da API):

```javascript
// Sucesso
ValidadorFormulario.mostrarNotificacao('Dados salvos com sucesso!', 'sucesso');

// Erro
ValidadorFormulario.mostrarNotificacao('Ocorreu um erro ao salvar.', 'erro');

// Aviso
ValidadorFormulario.mostrarNotificacao('Preencha todos os campos obrigatórios.', 'aviso');

// Info
ValidadorFormulario.mostrarNotificacao('Seus dados foram atualizados.', 'info');
```

### HTML Manual (Bootstrap):

```html
<div class="alerta-validacao erro">
    <div class="alerta-validacao-icone">⚠️</div>
    <div class="alerta-validacao-conteudo">
        <div class="alerta-validacao-titulo">Erro</div>
        <div class="alerta-validacao-mensagem">
            Ocorreu um erro ao processar sua solicitação.
        </div>
    </div>
    <button class="alerta-validacao-fechar" onclick="this.parentElement.style.display='none';">×</button>
</div>
```

---

## 🚀 Passo 7: Inicializar em Seu Formulário

### Adicione ao final de sua página:

```html
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Inicializa validações para o formulário
        ValidadorFormulario.inicializarFormulario('seuIdFormulario');
    });
</script>
```

### Ou use como helper PHP:

```php
<?php
echo "
<script>
    document.addEventListener('DOMContentLoaded', () => {
        ValidadorFormulario.inicializarFormulario('formularioCadastro');
    });
</script>
";
?>
```

---

## 📋 Checklist de Implementação

Para cada formulário, verificar:

- [ ] Formulário tem `id` definido
- [ ] CSS `/recursos/css/validacoes.css` está incluído
- [ ] JS `/recursos/js/validacoes_form.js` está incluído
- [ ] Campos obrigatórios têm `required` e badge "Obrigatório"
- [ ] Campos têm `data-tipo` correto (numeros, letras, alfanumerico, email, etc)
- [ ] Textareas têm contador de caracteres
- [ ] Backend valida com `ValidadorCampos`
- [ ] Erros não expõem detalhes do banco de dados
- [ ] Notificações usam `ValidadorFormulario.mostrarNotificacao()`
- [ ] Script de inicialização está presente

---

## 🔒 SEGURANÇA - IMPORTANTE!

### ❌ NUNCA FAÇA ISSO:
```php
// ERRADO - Expõe detalhes da BD
echo json_encode(['erro' => $e->getMessage()]);

// ERRADO - Mostra SQL
catch (PDOException $e) {
    die("Query error: " . $e->getMessage());
}
```

### ✅ FAÇA ASSIM:
```php
// CERTO - Mensagem genérica
retornarErroGenerico('Ocorreu um erro ao processar sua solicitação.');

// CERTO - Log no servidor
catch (PDOException $e) {
    error_log("BD Error: " . $e->getMessage());
    retornarErroGenerico('Erro ao processar a solicitação.');
}
```

---

## 📞 Suporte Rápido

### Problema: Campo não valida
**Solução:** Verifique se `data-tipo` está correto e JS está carregado

### Problema: Mensagens mostram código de BD
**Solução:** Use `retornarErroGenerico()` em vez de expor exceptions

### Problema: Limite de caracteres não funciona
**Solução:** Adicione `data-tamanho-maximo="XXX"` ao textarea

### Problema: Campo não marca como obrigatório
**Solução:** Adicione `required` e `class="label-obrigatorio"` ao label

---

## 📊 Resumo de Tipos de Campos

| Tipo | Validação | Exemplo |
|------|-----------|---------|
| `numeros-apenas` | Só números (0-9) | Idade, Telefone |
| `letras-apenas` | Só letras (a-z, A-Z, acentos) | Nome, Sobrenome |
| `alfanumerico` | Letras + números + alguns caracteres | Código, ID |
| `email` | Formato de email válido | Email |
| `telefone` | Formato de telefone | +244 923 456 789 |
| `url` | URL válida | Site, LinkedIn |
| `comentario` | Texto com limite máximo | Motivação, Descrição |

---

**Implementado com sucesso em 02/06/2026** ✅
