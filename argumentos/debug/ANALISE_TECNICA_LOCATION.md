# RESUMO TÉCNICO - Fluxo de Dados do Campo Location

## 1. ENTRADA DE DADOS - Frontend

### Formulário de Edição (edit_profile_modal.php)
```php
// Origem: inclusoes/components/edit_profile_modal.php [L72-88]
<select name="location" class="form-control" required>
    <!-- Dropdown com 18 províncias de Angola -->
    <option value="">Selecionar Localização...</option>
    <option value="Bengo">Bengo</option>
    <option value="Benguela">Benguela</option>
    <!-- ... 16 mais ... -->
</select>
```

### Dados Enviados
```
FormData {
    'location': 'Luanda'  // Valor do dropdown
    'full_name': '...',
    'level': '...',
    'skills': '...',
    'focus_areas': '...',
    // ... outros campos ...
}
```

## 2. PROCESSAMENTO - Backend (update_profile.php)

### Extração de Dados [L188]
```php
$location = cleanProfileText($_POST['location'] ?? '', 160);
```

### Validação de Obrigatoriedade [L205]
```php
if ($academic_level === '' || $location === '' || $skills === '' 
    || $bio === '' || $focus_areas === '') {
    echo json_encode([
        'success' => false, 
        'message' => 'Preencha nome, nivel/cargo, localizacao, skills, areas de foco e biografia.'
    ]);
    exit();
}
```

**Nota**: Se `location` estiver vazio, API retorna erro sem salvar dados.

### Limpeza de Dados
```php
function cleanProfileText(string $value, int $maxLength): string {
    $value = trim(preg_replace('/\s+/', ' ', $value));  // Remove espaços extras
    return mb_substr($value, 0, $maxLength);             // Limita a 160 caracteres
}
```

**Resultado esperado**: "Luanda" (sem espaços extras)

## 3. PERSISTÊNCIA - Database

### SQL UPDATE [L243, L261-275]
```php
$sql = "UPDATE users SET
    full_name = :full_name,
    academic_level = :academic_level,
    bio = :bio,
    location = :location,           // ← Campo correto
    specialization_tags = :skills,  // ← Recebe $_POST['skills']
    focus_areas = :focus_areas,     // ← Recebe $_POST['focus_areas']
    experience_summary = :experience_summary
    WHERE user_id = :user_id";

$params = [
    'location' => $location,              // ✓ Da $_POST['location']
    'skills' => $skills,                  // ✓ De $_POST['skills']
    'focus_areas' => $focus_areas,        // ✓ De $_POST['focus_areas']
    // ...
];

$stmt = $db->prepare($sql);
$stmt->execute($params);
```

**Estrutura de tabela garantida** [L19-43]:
```php
function ensureProfileColumns(PDO $db): void {
    $columns = [
        'location' => 'VARCHAR(160)',
        'specialization_tags' => 'TEXT',
        'focus_areas' => 'TEXT',
        // ...
    ];
    // Cria colunas se não existirem
}
```

## 4. LEITURA DE DADOS - APIs

### get_my_profile.php [L64]
```php
$sql = "SELECT full_name, profile_pic, bio, location, specialization_tags,
               linkedin_url, website_url, email, phone, user_type, academic_level,
               is_verified, verification_status, gender, mentorship_status, created_at,
               birth_date, institution, organization, focus_areas, experience_summary
        FROM users WHERE user_id = ?";

// Retorna JSON
'location' => $user['location'] ?? '',
'focus_areas' => $focus_areas,
'skills_str' => $skills_str,  // de specialization_tags
```

### get_user_card.php [L47-49]
```php
$sql = "SELECT full_name, user_type, profile_pic, is_verified, 
               verification_status, bio, specialization_tags,
               location, institution, organization, focus_areas, 
               experience_summary, website_url, linkedin_url
        FROM users WHERE user_id = ?";

// Retorna JSON
'location' => $user['location'] ?? 'Angola',
'focus_areas' => $user['focus_areas'] ?? '',
'skills' => $user['specialization_tags'] ?? '',
```

## 5. MAPEAMENTO DE CAMPOS

```
FRONTEND         →  POST PARAM      →  PHP VARIABLE    →  DB COLUMN
==========================================================================
Profile Dropdown → location          → $location         → users.location
Skills Input    → skills            → $skills           → users.specialization_tags
Focus Areas     → focus_areas        → $focus_areas      → users.focus_areas
```

## 6. POSSÍVEIS PONTOS DE FALHA

### ❌ Não Encontrado - Nenhum ponto identificado
- Nenhum UPDATE direto que misture campos
- Nenhuma migração que copie values errados
- Nenhum SELECT que combine incorretamente

### ✓ Confirmado - Código está correto
- Validações implementadas
- Limpeza de dados realizada
- Separação clara entre campos

## 7. CAMPO VALIDAÇÃO ENUM - RECOMENDAÇÃO

### Proposta para melhor integridade de dados
```sql
ALTER TABLE users MODIFY location ENUM(
    'Bengo', 'Benguela', 'Bié', 'Cabinda', 
    'Cuando Cubango', 'Cuanza Norte', 'Cuanza Sul', 
    'Cunene', 'Huambo', 'Huíla', 'Luanda', 
    'Lunda Norte', 'Lunda Sul', 'Malanje', 
    'Moxico', 'Namibe', 'Uíge', 'Zaire'
) DEFAULT NULL;
```

**Benefício**: Impossibilita valores inválidos no nível de banco

## 8. AUDIT TRAIL - PARA RASTREAMENTO

### Script sugerido
```php
// Adicionar após UPDATE bem-sucedido
$audit_sql = "INSERT INTO audit_logs (table_name, record_id, field_name, 
              old_value, new_value, user_id, changed_at) VALUES 
              (?, ?, ?, ?, ?, ?, NOW())";

// Registar mudança
$db->prepare($audit_sql)->execute([
    'users', $user_id, 'location', 
    $old_location, $new_location, 
    $_SESSION['user_id']
]);
```

---

**Conclusão**: Código de atualização de `location` está seguro e correto. 
Qualquer anomalia em dados existentes provavelmente vem de importação histórica ou entrada manual incorreta.
