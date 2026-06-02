# 🔧 GUIA TÉCNICO DE VALIDAÇÃO - FORMULÁRIOS PHP
## KALIYE Platform | 02 de junho de 2026

---

## 📌 QUICK REFERENCE

### Métodos de Validação por Tipo

#### EMAIL
```php
// HTML5
<input type="email" required>

// PHP Pattern
filter_var($email, FILTER_VALIDATE_EMAIL)

// Regex
^[^\s@]+@[^\s@]+\.[^\s@]+$

// Ficheiros: 7 formulários
// - autenticacao/entrar.php
// - autenticacao/registar.php
// - autenticacao/recuperar_senha.php
// - administracao/users/admins.php
// - administracao/marketing/form_ad.php
// - inclusoes/components/newsletter_section.php
// - inclusoes/components/landing_footer.php
```

#### NUMBER (Financeiro)
```php
// HTML5
<input type="number" min="1000" max="999999" step="0.01" required>

// PHP Validation
if (!is_numeric($value) || $value < 1000) {
    throw new ValidationException("Invalid amount");
}

// Ficheiros: 3
// - wallet_modals.php
// - invest_modal.php
// - form_ad.php
```

#### TELEFONE
```php
// Angola Format: +244 9xx xxx xxx
// Pattern: ^\+244\s?9\d{2}\s?\d{3}\s?\d{3}$

// PHP
$phone = preg_replace('/[^\d+]/', '', $_POST['phone']);
if (!preg_match('/^\+2449\d{8}$/', $phone)) {
    throw new ValidationException("Invalid Angola phone");
}

// Ficheiros: 2
// - autenticacao/registar.php
// - paginas/conta/completar_perfil.php
```

#### BI/PASSAPORTE
```php
// BI: 9 números + 2 letras + 3 números
// Passaporte: 2 letras + 7 números
// Pattern: ^(\d{9}[A-Z]{2}\d{3}|[A-Z]{2}\d{7})$

// PHP
$id = strtoupper($_POST['id_number']);
if (!preg_match('/^(\d{9}[A-Z]{2}\d{3}|[A-Z]{2}\d{7})$/', $id)) {
    throw new ValidationException("Invalid ID format");
}

// Ficheiros: 2
// - autenticacao/registar.php
// - paginas/conta/completar_perfil.php
```

#### IBAN
```php
// Angola IBAN: AO06 0040 0000 XXXXXXXXXX XX (24 chars)

// PHP
$iban = str_replace(' ', '', strtoupper($_POST['iban']));
if (!preg_match('/^AO\d{2}\d{4}\d{4}[A-Z0-9]{12}$/', $iban)) {
    throw new ValidationException("Invalid IBAN");
}

// Ficheiros: 1
// - wallet_modals.php (withdrawal)
```

#### URL
```php
// HTML5
<input type="url" required>

// PHP
filter_var($_POST['url'], FILTER_VALIDATE_URL)

// Ficheiros: 3
// - profile_settings_content.php (linkedin_url)
// - invest_modal.php (url handling)
// - form_ad.php (link_url)
```

#### PASSWORD
```php
// Requisitos:
// - Min 8 caracteres
// - Pelo menos 1 uppercase
// - Pelo menos 1 lowercase
// - Pelo menos 1 número
// - Pelo menos 1 carácter especial (opcional mas recomendado)

// PHP
function validate_password($pwd) {
    if (strlen($pwd) < 8) return false;
    if (!preg_match('/[A-Z]/', $pwd)) return false;
    if (!preg_match('/[a-z]/', $pwd)) return false;
    if (!preg_match('/[0-9]/', $pwd)) return false;
    return true;
}

// Frontend: data-tipo="senha" with maxlength="50"

// Ficheiros: 3
// - autenticacao/entrar.php
// - autenticacao/registar.php
// - autenticacao/redefinir_senha.php
```

#### FILE (Upload)
```php
// Validação COMPLETA necessária:
1. MIME type check
2. File size limit
3. Extension whitelist
4. Virus scan (opcional)
5. Store outside webroot

// PHP
$allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($_FILES['file']['type'], $allowed_types)) {
    throw new ValidationException("Invalid file type");
}
if ($_FILES['file']['size'] > $max_size) {
    throw new ValidationException("File too large");
}

// Verificar MIME type real (não extensão)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$real_type = finfo_file($finfo, $_FILES['file']['tmp_name']);
if (!in_array($real_type, $allowed_types)) {
    throw new ValidationException("Invalid file (MIME mismatch)");
}

// Ficheiros: 12
// - mentor_app_modal.php (CV - PDF only)
// - profile_kyc_content.php (images)
// - kyc_modal.php (images)
// - wallet_modals.php (image/pdf proof)
// - form_ad.php (images)
// - edit_profile_modal.php (images)
// - chat_area.php (files)
// - doubt_modal.php (images)
// - project_modal.php (files)
// - mentorship_modals.php (resources)
```

#### DATE
```php
// HTML5
<input type="date" max="<?php echo date('Y-m-d'); ?>">

// PHP - Birthday validation
$date = DateTime::createFromFormat('Y-m-d', $_POST['birth_date']);
if (!$date) {
    throw new ValidationException("Invalid date format");
}
if ($date > new DateTime()) {
    throw new ValidationException("Future date not allowed");
}
$age = (new DateTime())->diff($date)->y;
if ($age < 18) {
    throw new ValidationException("Must be 18+");
}

// Ficheiros: 3
// - autenticacao/registar.php
// - paginas/conta/completar_perfil.php
// - edit_profile_modal.php
```

#### SELECT (Enum)
```php
// HTML5 + Whitelist
<select name="category" required>
    <option value="programming">Programação</option>
    <option value="math">Matemática</option>
    ...
</select>

// PHP - ALWAYS VALIDATE server-side
$allowed_categories = ['programming', 'math', 'physics', 'chemistry', 
                       'languages', 'business', 'design', 'other'];
$category = $_POST['category'];

if (!in_array($category, $allowed_categories, true)) {
    throw new ValidationException("Invalid category");
}

// Ficheiros: 8
// - registar.php (user_type)
// - completar_perfil.php (user_type)
// - profile_settings_content.php (location)
// - kyc_modal.php (id_type)
// - form_ad.php (type, status)
// - invest_modal.php (currency, investment_type)
// - doubt_modal.php (category)
// - project_modal.php (project_stage)
```

#### TEXTAREA (Long Text)
```php
// XSS Prevention
$text = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');

// Ou melhor - usar DOMPurify (JavaScript)
const sanitizer = DOMPurify.sanitize(userInput);

// Max Length
<textarea maxlength="5000" required></textarea>

// PHP
if (strlen($_POST['description']) > 5000) {
    throw new ValidationException("Text too long");
}

// Ficheiros: 5
// - registar.php
// - profile_settings_content.php (bio)
// - invest_modal.php (motivation)
// - doubt_modal.php (description)
// - project_modal.php (description)
```

---

## 🛡️ SEGURANÇA ESSENCIAL

### CSRF Protection (Todos os formulários POST/FETCH)
```php
// Gerar token
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// HTML
<form method="POST">
    <input type="hidden" name="csrf_token" 
           value="<?php echo $_SESSION['csrf_token']; ?>">
    ...
</form>

// Validação
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die("CSRF token invalid");
}

// JavaScript (para FETCH)
const token = document.querySelector('input[name="csrf_token"]').value;
fetch(url, {
    method: 'POST',
    headers: {
        'X-CSRF-Token': token
    },
    body: formData
});
```

### SQL Injection Prevention
```php
// ❌ NUNCA:
$sql = "SELECT * FROM users WHERE email = '" . $_POST['email'] . "'";

// ✅ SEMPRE:
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_POST['email']]);

// Ou com named parameters:
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $_POST['email']]);
```

### XSS Prevention
```php
// Output encoding
<p><?php echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8'); ?></p>

// Content Security Policy header
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'");

// JavaScript sanitization
<script src="https://cdn.jsdelivr.net/npm/dompurify@2.4.0/dist/purify.min.js"></script>
<script>
const clean = DOMPurify.sanitize(userInput);
element.innerHTML = clean;
</script>
```

### Input Validation Framework
```php
class Validator {
    public static function email($value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Invalid email");
        }
        return $value;
    }
    
    public static function phone($value) {
        $value = preg_replace('/[^\d+]/', '', $value);
        if (!preg_match('/^\+2449\d{8}$/', $value)) {
            throw new ValidationException("Invalid phone");
        }
        return $value;
    }
    
    public static function id($value) {
        $value = strtoupper($value);
        if (!preg_match('/^(\d{9}[A-Z]{2}\d{3}|[A-Z]{2}\d{7})$/', $value)) {
            throw new ValidationException("Invalid ID");
        }
        return $value;
    }
    
    // ... mais validadores
}

// Uso:
try {
    $email = Validator::email($_POST['email']);
    $phone = Validator::phone($_POST['phone']);
} catch (ValidationException $e) {
    return ['error' => $e->getMessage()];
}
```

---

## 📋 FORMULÁRIOS POR PRIORIDADE

### 🔴 CRÍTICO (Implementar esta semana)
```
1. autenticacao/entrar.php (LOGIN)
   - CSRF token
   - Email validation
   - Rate limiting

2. autenticacao/registar.php (REGISTRO)
   - CSRF token
   - Email validation
   - ID pattern validation
   - Password strength
   - Duplicate email check

3. wallet_modals.php (WALLET)
   - CSRF token
   - Amount min/max
   - IBAN format
   - Authorization check

4. invest_modal.php (INVESTIMENTO)
   - CSRF token
   - Amount validation
   - Equity percentage bounds
   - Currency enum

5. kyc_modal.php (KYC)
   - CSRF token
   - File MIME type
   - File size limit
   - Virus scan
```

### 🟠 ALTO (Próximas 2 semanas)
```
6. doubt_modal.php (DÚVIDAS)
   - XSS prevention
   - Category enum
   - Image file validation
   - Maxlength constraints

7. project_modal.php (PROJETOS)
   - CSRF token
   - File upload validation
   - Enum validation
   - Number bounds

8. form_ad.php (PUBLICIDADE)
   - URL validation
   - Budget number validation
   - Image file validation
   - Enum for types

9. mentor_app_modal.php (MENTORIA)
   - URL validation
   - PDF file validation
   - Years number validation
   - File size limit
```

### 🟡 MÉDIO (Próximo mês)
```
10. profile_settings_content.php
11. edit_profile_modal.php
12. chat_area.php
13. booking_modal.php
14. review_modal.php
... e mais
```

---

## 📊 MATRIZ DE VALIDAÇÃO NECESSÁRIA

```
┌─────────────────────┬──────────┬───────────┬────────────┐
│ Tipo de Campo       │ HTML5    │ PHP       │ Frontend   │
├─────────────────────┼──────────┼───────────┼────────────┤
│ Email               │ required │ FILTER_   │ maxlength  │
│                     │ type     │ VALIDATE  │            │
├─────────────────────┼──────────┼───────────┼────────────┤
│ Password            │ required │ regex     │ pattern    │
│                     │ minlength│ strength  │ minlength  │
├─────────────────────┼──────────┼───────────┼────────────┤
│ Telefone            │ required │ regex     │ pattern    │
│                     │ tel      │ AO format │            │
├─────────────────────┼──────────┼───────────┼────────────┤
│ Number (Financeiro) │ required │ min/max   │ min/max    │
│                     │ type     │ numeric   │ type       │
├─────────────────────┼──────────┼───────────┼────────────┤
│ Select              │ required │ whitelist │ onchange   │
│                     │ enum     │ validation│ disable    │
├─────────────────────┼──────────┼───────────┼────────────┤
│ File                │ required │ MIME      │ accept     │
│                     │ accept   │ size      │ onChange   │
├─────────────────────┼──────────┼───────────┼────────────┤
│ Textarea            │ required │ XSS       │ maxlength  │
│                     │ maxlength│ sanitize  │ oninput    │
├─────────────────────┼──────────┼───────────┼────────────┤
│ Date                │ required │ format    │ type=date  │
│                     │ min/max  │ age calc  │ max="today"│
└─────────────────────┴──────────┴───────────┴────────────┘
```

---

## 🚀 IMPLEMENTAÇÃO PASSO A PASSO

### Passo 1: Framework de Validação
```php
// validators.php
class FormValidator {
    private $errors = [];
    
    public function validate($field, $rules, $value) {
        foreach ($rules as $rule) {
            $method = "validate_" . $rule;
            if (!$this->$method($value)) {
                $this->errors[$field] = "Validation failed for $rule";
                break;
            }
        }
        return empty($this->errors);
    }
    
    private function validate_email($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
    
    private function validate_required($value) {
        return !empty(trim($value));
    }
    
    // ... mais métodos
    
    public function getErrors() {
        return $this->errors;
    }
}
```

### Passo 2: Uso em Formulário
```php
$validator = new FormValidator();

$validator->validate('email', ['required', 'email'], $_POST['email']);
$validator->validate('phone', ['required', 'phone'], $_POST['phone']);
$validator->validate('password', ['required', 'password'], $_POST['password']);

if ($validator->validate()) {
    // Processar formulário
} else {
    // Retornar erros
    return json_encode(['errors' => $validator->getErrors()]);
}
```

### Passo 3: CSRF Token
```php
// middleware/csrf.php
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Em cada formulário
<input type="hidden" name="csrf_token" 
       value="<?php echo generate_csrf_token(); ?>">

// Verificação
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit("CSRF token invalid");
}
```

### Passo 4: Rate Limiting
```php
// middleware/rate_limit.php
function check_rate_limit($action, $limit = 5, $window = 900) {
    $key = "rate_limit_" . $action . "_" . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'until' => time() + $window];
    }
    
    if (time() > $_SESSION[$key]['until']) {
        $_SESSION[$key] = ['count' => 0, 'until' => time() + $window];
    }
    
    $_SESSION[$key]['count']++;
    
    if ($_SESSION[$key]['count'] > $limit) {
        http_response_code(429);
        exit("Too many requests. Try again later.");
    }
}

// Uso
check_rate_limit('login', 5, 900); // 5 tentativas por 15 minutos
```

---

## 📝 EXEMPLO COMPLETO

```php
<?php
// POST handler para formulário de login
session_start();
header('Content-Type: application/json');

try {
    // 1. CSRF Protection
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        throw new Exception("CSRF token invalid");
    }
    
    // 2. Rate Limiting
    check_rate_limit('login', 5, 900);
    
    // 3. Input Validation
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception("Invalid email");
    }
    
    $password = $_POST['password'] ?? '';
    if (strlen($password) < 8) {
        throw new Exception("Invalid password");
    }
    
    // 4. Database Query (Prepared Statement)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        throw new Exception("Invalid credentials");
    }
    
    // 5. Success
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Regenerate
    
    echo json_encode(['success' => true, 'redirect' => '/dashboard']);
    
} catch (Exception $e) {
    // Log securely (don't expose details)
    error_log("Login attempt failed: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Login failed. Please try again.'
    ]);
    http_response_code(400);
}
?>
```

---

## ✅ CHECKLIST PRÉ-LAUNCH

```
Security
□ CSRF tokens em todos POST/FETCH
□ SQL injection prevention (prepared statements)
□ XSS prevention (htmlspecialchars + DOMPurify)
□ File upload validation
□ Rate limiting em login/registration
□ Error message masking
□ Session security (HTTPOnly, Secure flags)
□ Password hashing (bcrypt)

Validation
□ Email validation
□ Phone validation
□ BI/Passaporte validation
□ Number bounds (min/max)
□ Enum whitelist validation
□ File MIME type check
□ File size limits
□ Maxlength constraints

Testing
□ Unit tests para validadores
□ Integration tests
□ Manual security testing
□ Cross-browser testing
□ Mobile testing
□ Penetration testing

Documentation
□ Security guidelines
□ Validation rules
□ Error handling
□ Logging strategy
```

---

## 📚 RECURSOS

- PHP Filter Functions: https://www.php.net/manual/en/function.filter-var.php
- OWASP Input Validation: https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html
- DOMPurify: https://github.com/cure53/DOMPurify
- PHP PDO Prepared Statements: https://www.php.net/manual/en/pdo.prepared-statements.php

---

**Versão**: 1.0  
**Data**: 02/06/2026  
**Status**: ✅ Pronto para Implementação
