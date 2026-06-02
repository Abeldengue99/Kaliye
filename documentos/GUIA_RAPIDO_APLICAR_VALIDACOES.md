# 🚀 GUIA RÁPIDO - APLICAR VALIDAÇÕES A NOVOS FORMULÁRIOS

**Data: 02 de Junho de 2026**

---

## ⚡ TL;DR (Resumo Ultrarrápido)

Para adicionar validações a **QUALQUER formulário** em 5 minutos:

### 1. No `<head>`
```html
<link rel="stylesheet" href="../recursos/css/validacoes.css">
```

### 2. Nos `<input>` e `<textarea>`
```html
<input type="email" name="email" 
       data-tipo="email" 
       data-obrigatorio="true"
       required>
```

### 3. Antes de `</body>`
```html
<script src="../recursos/js/validacoes_form.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    ValidadorFormulario.inicializarFormulario('seuFormularioID');
  });
</script>
```

### 4. No Backend (PHP)
```php
<?php
require_once '../interface_programacao/validacoes.php';

$validacao = ValidadorCampos::validarMultiplos([
    'Email' => ['valor' => $_POST['email'], 'regras' => ['email' => true]]
]);

if (!$validacao['valido']) {
    retornarErroValidacao('Erro', $validacao['erros']);
}

retornarSucesso('Sucesso!');
?>
```

---

## 📋 FORMULÁRIOS A FAZER HOJE

### 1. `paginas/conta/completar_perfil.php`

**O que encontrar:**
```html
<form id="formularioPerfil">
    <input type="text" name="full_name" ... />
    <input type="email" name="email" ... />
    <input type="tel" name="phone" ... />
    ...
</form>
```

**O que fazer:**

1. Adicionar CSS:
```php
// Antes de </head>
<link rel="stylesheet" href="../../recursos/css/validacoes.css">
```

2. Atualizar inputs:
```html
<!-- Nome -->
<input type="text" name="full_name" 
       data-tipo="letras-apenas"
       data-obrigatorio="true"
       required>

<!-- Email -->
<input type="email" name="email" 
       data-tipo="email"
       data-obrigatorio="true"
       required>

<!-- Telefone -->
<input type="tel" name="phone" 
       data-tipo="telefone"
       data-obrigatorio="true"
       required>
```

3. Adicionar JS:
```html
<!-- Antes de </body> -->
<script src="../../recursos/js/validacoes_form.js"></script>
<script>
  ValidadorFormulario.inicializarFormulario('formularioPerfil');
</script>
```

---

### 2. `inclusoes/components/doubt_modal.php`

**O que encontrar:**
```php
<form id="formularioDuvida">
    <input type="text" name="titulo" ... />
    <textarea name="descricao" ... ></textarea>
    <select name="categoria" ... ></select>
    ...
</form>
```

**O que fazer:**

1. Adicionar CSS (se não existir):
```php
<link rel="stylesheet" href="../../recursos/css/validacoes.css">
```

2. Atualizar campos:
```html
<!-- Título -->
<input type="text" name="titulo" 
       data-tipo="alfanumerico"
       data-obrigatorio="true"
       maxlength="100"
       required>

<!-- Descrição com limite -->
<textarea name="descricao" 
          data-tipo="comentario"
          data-tamanho-maximo="300"
          data-obrigatorio="true"
          maxlength="300"
          required></textarea>

<!-- Categoria (obrigatória) -->
<select name="categoria" 
        data-obrigatorio="true"
        required>
    <option value="">Selecciona uma categoria</option>
    <option value="programming">Programming</option>
    ...
</select>
```

3. Adicionar JS:
```html
<script src="../../recursos/js/validacoes_form.js"></script>
<script>
  ValidadorFormulario.inicializarFormulario('formularioDuvida');
</script>
```

---

### 3. `inclusoes/components/edit_profile_modal.php`

**O que encontrar:**
Modal com múltiplas abas: Perfil, Contacto, Académico

**O que fazer:**

1. Adicionar CSS:
```php
<link rel="stylesheet" href="../../recursos/css/validacoes.css">
```

2. Atualizar cada aba:

**Aba 1 - Perfil:**
```html
<!-- Nome -->
<input type="text" name="nome" 
       data-tipo="letras-apenas"
       data-obrigatorio="true"
       required>

<!-- Bio (texto longo) -->
<textarea name="bio" 
          data-tipo="comentario"
          data-tamanho-maximo="300"
          maxlength="300"></textarea>

<!-- Avatar (file) -->
<input type="file" name="avatar" 
       accept="image/*">
```

**Aba 2 - Contacto:**
```html
<!-- Email -->
<input type="email" name="email" 
       data-tipo="email"
       data-obrigatorio="true"
       required>

<!-- Telefone -->
<input type="tel" name="telefone" 
       data-tipo="telefone"
       required>

<!-- LinkedIn URL -->
<input type="url" name="linkedin" 
       data-tipo="url"
       placeholder="https://linkedin.com/...">
```

**Aba 3 - Académico:**
```html
<!-- Instituição -->
<input type="text" name="instituicao" 
       data-tipo="letras-apenas">

<!-- Número de matrícula (números) -->
<input type="text" name="matricula" 
       data-tipo="numeros-apenas">

<!-- Curso (texto) -->
<input type="text" name="curso" 
       data-tipo="letras-apenas">
```

3. Adicionar JS:
```html
<script src="../../recursos/js/validacoes_form.js"></script>
<script>
  ValidadorFormulario.inicializarFormulario('formularioEditarPerfil');
</script>
```

---

## 🎯 PADRÕES RÁPIDOS

### Campo de Email (sempre assim)
```html
<input type="email" name="email" 
       data-tipo="email"
       data-obrigatorio="true"
       required>
<small class="invalid-feedback"></small>
```

### Campo de Nome (letras apenas)
```html
<input type="text" name="nome" 
       data-tipo="letras-apenas"
       data-obrigatorio="true"
       required>
<small class="invalid-feedback"></small>
```

### Campo de Número (números apenas)
```html
<input type="text" name="idade" 
       data-tipo="numeros-apenas"
       required>
<small class="invalid-feedback"></small>
```

### Campo de Telefone
```html
<input type="tel" name="telefone" 
       data-tipo="telefone"
       data-obrigatorio="true"
       required>
<small class="invalid-feedback"></small>
```

### Textarea com Limite
```html
<textarea name="descricao" 
          data-tipo="comentario"
          data-tamanho-maximo="250"
          data-obrigatorio="true"
          maxlength="250"
          rows="4"
          required></textarea>
<small class="contador-caracteres normal" id="descricao_contador">
  0/250 caracteres
</small>
```

### Label Obrigatório
```html
<label class="label-obrigatorio">Nome Completo</label>
<!-- Resultado: "Nome Completo *" em vermelho -->
```

---

## ✅ CHECKLIST POR FORMULÁRIO

Para cada formulário que você atualizar:

```
[ ] CSS incluído (antes de </head>)
[ ] Form tem ID (id="meuForm")
[ ] Cada campo tem data-tipo correto
[ ] Campos obrigatórios têm data-obrigatorio="true"
[ ] Campos obrigatórios têm required
[ ] Labels têm class="label-obrigatorio"
[ ] Textareas têm contador se tiverem limite
[ ] JS incluído (antes de </body>)
[ ] Inicializador do formulário presente
[ ] Testei: enviar vazio → erro
[ ] Testei: dados válidos → sucesso
```

---

## 🔄 ORDEM RECOMENDADA

1. **Hoje - Crítico** (JÁ FEITO):
   - ✅ entrar.php
   - ✅ registar.php

2. **Hoje - DEPOIS:**
   - [ ] doubt_modal.php (dúvidas)
   - [ ] completar_perfil.php (profile)
   - [ ] edit_profile_modal.php (editar)

3. **Se sobrar tempo:**
   - [ ] administracao/marketing/form_ad.php
   - [ ] administracao/users/admins.php
   - [ ] Outros formulários

---

## 🐛 DEBUG RÁPIDO

### "Nada valida"
```
1. F12 (DevTools) > Console
2. Procurar por erro: "Cannot find ValidadorFormulario"
3. Se sim: JS não foi carregado
   Solução: Copiar o <script> correto
```

### "Erros mostram BD"
```
1. No backend, procurar por: echo, die, PDOException
2. Substituir por: retornarErroGenerico()
3. Adicionar try-catch ao redor de BD
```

### "Botão submetido mas nada acontece"
```
1. Verificar F12 > Network
2. Ver se requisição foi enviada
3. Se sim, ver resposta (JSON?)
4. Se erro, usar retornarErroGenerico()
```

---

## 📞 FUNÇÕES PRONTAS (backend)

```php
// Validar múltiplos campos
ValidadorCampos::validarMultiplos($campos);

// Validar específico
ValidadorCampos::validarEmail($email, "Email");
ValidadorCampos::validarApenasNumeros($numero, "Idade");
ValidadorCampos::validarApenasLetras($nome, "Nome");

// Responder
retornarSucesso("Mensagem", ["dados" => "..."]);
retornarErroValidacao("Mensagem", ["campo" => "erro"]);
retornarErroGenerico("Mensagem"); // NÃO expõe BD

// Sanitizar
ValidadorCampos::sanitizar($valor);
```

---

## ⏱️ TEMPO ESTIMADO

- **Por formulário**: 5-10 minutos
- **3 formulários**: 15-30 minutos
- **Teste final**: 10-15 minutos

---

## 🎉 PRONTO!

Quando terminar:

```
cd c:\Users\nee\Documents\Aksanti Referências\Aksanti Referências
git add .
git commit -m "Validações críticas - lançamento 02/06/2026"
git push
```

---

**Sucesso! 🚀 Pronto para o lançamento de HOJE À NOITE!**
