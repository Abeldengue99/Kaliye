# ✅ SISTEMA CENTRALIZADO DE TRATAMENTO DE ERROS
## Kaliye Platform - 02 de Junho de 2026

---

## 📋 VISÃO GERAL

Sistema completo de tratamento de erros que:
- ✅ Exibe modals visuais e profissionais ao utilizador
- ✅ NÃO expõe dados sensíveis da base de dados
- ✅ Registra erros com segurança (backend)
- ✅ É consistente em toda a plataforma
- ✅ Diferencia tipos de erro (validação, BD, rede, servidor, etc)

---

## 🎯 COMPONENTES IMPLEMENTADOS

### 1. **ErrorHandler** (`inclusoes/ErrorHandler.php`)
Classe centralizada para:
- Tratamento de exceções
- Geração de respostas JSON padronizadas
- Logging seguro de erros
- Mensagens amigáveis ao utilizador

**Tipos de erro suportados:**
```php
ErrorHandler::TYPE_VALIDATION       // Erro de validação (422)
ErrorHandler::TYPE_DATABASE         // Erro de BD (500)
ErrorHandler::TYPE_AUTHENTICATION   // Sem autenticação (401)
ErrorHandler::TYPE_AUTHORIZATION    // Sem permissão (403)
ErrorHandler::TYPE_NOT_FOUND        // Recurso não encontrado (404)
ErrorHandler::TYPE_CONFLICT         // Conflito de dados (409)
ErrorHandler::TYPE_SERVER           // Erro de servidor (500)
ErrorHandler::TYPE_EXTERNAL_API     // Erro de API externa (502)
ErrorHandler::TYPE_NETWORK          // Erro de rede (503)
```

### 2. **Modal de Erro** (`inclusoes/components/error_modal.php`)
Interface visual com:
- Modal responsivo e profissional
- Diferentes ícones por tipo de erro
- Possibilidade de expandir detalhes
- Botões de ação (Fechar, Tentar Novamente, Contactar Suporte)
- Código de referência para suporte

**Uso JavaScript:**
```javascript
// Erro genérico
ErrorUI.show('Mensagem de erro', { title: 'Erro' });

// Erro de validação
ErrorUI.showValidationErrors({ email: 'Email inválido' });

// Erro de rede
ErrorUI.showNetworkError();

// Erro de servidor
ErrorUI.showServerError();
```

### 3. **Configuração Global** (`interface_programacao/error_config.php`)
Arquivo de configuração que:
- Define handlers de exceção e erro
- Configura logging
- Fornece funções auxiliares

---

## 🚀 COMO IMPLEMENTAR

### PASSO 1: Incluir em Todos os Controllers

Em **TODOS** os arquivos `interface_programacao/auth/*.php`, `interface_programacao/user/*.php`, etc:

```php
<?php
require_once __DIR__ . '/error_config.php';
require_once __DIR__ . '/../ErrorHandler.php';

// Resto do código...
```

### PASSO 2: Incluir Modal em Layout Principal

Em `inclusoes/cabecalho.php` ou `inclusoes/rodape.php`:

```php
<?php
// Próximo do final do arquivo, antes de </body>
require_once __DIR__ . '/components/error_modal.php';
?>
```

### PASSO 3: Usar em Validação de Formulários

Exemplo:
```php
<?php
require_once __DIR__ . '/error_config.php';
require_once __DIR__ . '/../ErrorHandler.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar inputs
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $name = trim($_POST['name'] ?? '');
        
        $errors = [];
        if (!$email) $errors['email'] = 'Email inválido';
        if (!$name) $errors['name'] = 'Nome obrigatório';
        
        // Retornar erros de validação
        if (!empty($errors)) {
            ErrorHandler::respondValidationErrors($errors);
        }
        
        // Processar...
        $database = new Database();
        $db = $database->getConnection();
        
        // ... resto do código ...
        
        ErrorHandler::respondSuccess(['user_id' => $id], 'Utilizador criado com sucesso');
        
    } catch (PDOException $e) {
        ErrorHandler::handleDatabaseException($e, 'User registration');
    } catch (Exception $e) {
        ErrorHandler::handleException($e, 'Form processing');
    }
}
?>
```

### PASSO 4: Usar em Requisições AJAX

Frontend (JavaScript):
```javascript
async function criarConta(dados) {
    try {
        const response = await fetch('/interface_programacao/auth/register_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        
        const result = await response.json();
        
        if (!result.success) {
            ErrorUI.show(result.message, {
                title: 'Erro',
                type: result.type === 'validation' ? 'warning' : 'error',
                details: result.type === 'validation' ? JSON.stringify(result.errors) : null
            });
            return;
        }
        
        // Sucesso
        window.location.href = '/dashboard';
        
    } catch (error) {
        ErrorUI.showNetworkError();
    }
}
```

### PASSO 5: Verificação de Autenticação

Em páginas protegidas:
```php
<?php
require_once __DIR__ . '/error_config.php';
require_once __DIR__ . '/../ErrorHandler.php';

// Verificar se está autenticado
ErrorHandler::requireAuth();

// Resto da página...
?>
```

### PASSO 6: Verificação de Autorização

Em páginas administrativas:
```php
<?php
require_once __DIR__ . '/error_config.php';
require_once __DIR__ . '/../ErrorHandler.php';

ErrorHandler::requireAuth();
ErrorHandler::requireRole(['admin', 'moderator'], $_SESSION['user_role'] ?? null);

// Resto da página...
?>
```

---

## 📋 CHECKLIST DE IMPLEMENTAÇÃO

### Fase 1: Infraestrutura (✅ Completo)
- [x] Classe `ErrorHandler.php` criada
- [x] Modal de erro `error_modal.php` criado
- [x] Configuração global `error_config.php` criada
- [x] Guia de implementação `GUIA_TRATAMENTO_ERROS.md` criado

### Fase 2: Controllers de Autenticação
- [ ] `interface_programacao/auth/register_action.php`
- [ ] `interface_programacao/auth/login_action.php`
- [ ] `interface_programacao/auth/forgot_password_action.php`
- [ ] `interface_programacao/auth/reset_password_action.php`
- [ ] `interface_programacao/auth/verify_2fa.php`

### Fase 3: Controllers de Utilizador
- [ ] `interface_programacao/user/update_profile.php`
- [ ] `interface_programacao/user/update_kyc.php`
- [ ] `interface_programacao/user/upload_avatar.php`

### Fase 4: Controllers Administrativos
- [ ] `interface_programacao/admin/create_admin.php`
- [ ] `interface_programacao/admin/admin_save_settings.php`
- [ ] `interface_programacao/admin/admin_distribute_capital.php`

### Fase 5: Controllers de Negócio
- [ ] `interface_programacao/mentorship/submit_mentor_application.php`
- [ ] `interface_programacao/projects/post_project.php`
- [ ] `interface_programacao/social/post_review.php`
- [ ] `servicos/doubts/post_doubt.php`
- [ ] `servicos/wallet/wallet_modals.php`

### Fase 6: Layout Global
- [ ] Incluir modal em `inclusoes/cabecalho.php`
- [ ] Incluir modal em layouts de página
- [ ] Testar em desktop e mobile

---

## 🔒 SEGURANÇA

### ✅ O QUE FAZER

1. **Sempre** usar `ErrorHandler::respondError()` em vez de `echo json_encode()`
2. **Sempre** validar inputs antes de usar em queries
3. **Sempre** usar prepared statements (PDOStatement)
4. **Sempre** fazer log de erros com `ErrorHandler::logError()`
5. **Sempre** verificar autenticação com `ErrorHandler::requireAuth()`

### ❌ O QUE NÃO FAZER

1. ❌ NÃO expor mensagens de erro do BD: "Column 'email' doesn't exist"
2. ❌ NÃO expor caminhos de servidor: "/var/www/html/kaliye/..."
3. ❌ NÃO expor queries SQL em mensagens de erro
4. ❌ NÃO usar `die()` ou `exit()` com mensagens sensíveis
5. ❌ NÃO ignorar erros com `@` operator

---

## 📊 EXEMPLOS DE MENSAGENS

### Validação (222)
```
Erro: "O email fornecido é inválido"
Detalhes: { email: "Email inválido" }
```

### Base de Dados (500)
```
Erro: "Ocorreu um erro ao processar a tua solicitação"
Log: "Duplicate key violation on users.email"
```

### Autenticação (401)
```
Erro: "A tua sessão expirou. Por favor, faz login novamente"
Redirecionar para: /autenticacao/entrar.php
```

### Autorização (403)
```
Erro: "Não tens permissão para realizar esta ação"
Detalhes: Sem permissão de admin
```

### Servidor (500)
```
Erro: "Ocorreu um erro no servidor. A equipa técnica foi notificada"
Referência: ERR_1717419600_ABC12DEF
```

---

## 🧪 TESTES

### Teste 1: Erro de Validação
```bash
curl -X POST http://localhost/kaliye/interface_programacao/auth/register_action.php \
  -d '{"email":"invalido","password":"123"}'
# Esperado: 422 com erros estruturados
```

### Teste 2: Erro de Autenticação
```bash
curl http://localhost/kaliye/administracao/users/admins.php
# Esperado: 401 com mensagem de sessão expirada
```

### Teste 3: Erro de BD (Email duplicado)
```bash
curl -X POST http://localhost/kaliye/interface_programacao/auth/register_action.php \
  -d '{"email":"existing@email.com","password":"password123"}'
# Esperado: 409 com mensagem amigável
```

---

## 📚 ARQUIVOS RELACIONADOS

| Arquivo | Função |
|---------|--------|
| `inclusoes/ErrorHandler.php` | Classe principal de tratamento |
| `inclusoes/components/error_modal.php` | Interface visual do erro |
| `interface_programacao/error_config.php` | Configuração global |
| `documentos/GUIA_TRATAMENTO_ERROS.md` | Guia completo de uso |
| `logs/errors_YYYY-MM-DD.log` | Arquivo de log de erros |
| `logs/operations_YYYY-MM-DD.log` | Arquivo de log de operações |

---

## 🔄 SINCRONIZAÇÃO COM XAMPP

Após implementação, sincronizar com XAMPP:

```powershell
$workspace = "c:\Users\nee\Documents\Aksanti Referências\Aksanti Referências"
$xampp = "C:\xampp\htdocs\kaliye"

Copy-Item "$workspace\inclusoes\ErrorHandler.php" "$xampp\inclusoes\" -Force
Copy-Item "$workspace\inclusoes\components\error_modal.php" "$xampp\inclusoes\components\" -Force
Copy-Item "$workspace\interface_programacao\error_config.php" "$xampp\interface_programacao\" -Force
```

---

## 📞 SUPORTE

Quando utilizador relata erro:
1. Pedir código de referência (ex: ERR_1717419600_ABC12DEF)
2. Verificar em `logs/errors_YYYY-MM-DD.log`
3. Correlacionar com `logs/operations_YYYY-MM-DD.log`
4. Resolver baseado em contexto completo

---

**STATUS: ✅ SISTEMA PRONTO PARA IMPLEMENTAÇÃO**

Próximo passo: Aplicar em todos os controllers conforme checklist acima.
