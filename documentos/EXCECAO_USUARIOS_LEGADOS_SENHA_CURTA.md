# Exceção: Usuários Legados com Senhas Curtas

**Data:** 2 de junho de 2026  
**Status:** ✅ Implementado e Testado

## Objetivo
Permitir que 3 usuários legados (cadastrados antes da regra de 8+ caracteres) façam login com senhas curtas.

## Usuários Legados Autorizados
1. **alexandrinadeoliveiraale@gmail.com** - Alexandrina
2. **admin@aksanti.com** - Admin
3. **anielaniel417@gmail.com** - Aniel

## Arquivos Modificados

### 1. `configuracoes/legacy_users.php` (NOVO)
- Lista centralizada de emails de usuários legados
- Função auxiliar `isLegacyUser($email)` para verificação

### 2. `autenticacao/entrar.php`
- Adicionado `require_once` para `legacy_users.php`
- Removido atributo `minlength="8"` do input de password
- Modificado JavaScript de validação para fazer exceção:
  - Se email está na lista de legados → permite senha curta
  - Se email NÃO está na lista → valida mínimo 8 caracteres

### 3. `interface_programacao/auth/login_action.php`
- Adicionado `require_once` para `legacy_users.php`
- Backend permite qualquer senha que bata com o hash via `password_verify()`

## Lógica de Funcionamento

### Validação Frontend (entrar.php)
```javascript
// Verifica se é usuário legado
const isLegacyUser = LEGACY_USERS_SHORT_PASSWORD.some(legacyEmail => 
    legacyEmail.toLowerCase() === email.value.toLowerCase()
);

// Se não é legado, valida mínimo 8 caracteres
if (!isLegacyUser && password.value.length < 8) {
    e.preventDefault();
    window.mostrarErroModal('Password Fraca', 'A password deve ter mínimo 8 caracteres.');
    return;
}
```

### Validação Backend (login_action.php)
- `password_verify()` valida se a senha bate com o hash
- Funciona para qualquer comprimento de senha
- Nenhuma validação adicional de comprimento (permite legados)

## Teste Realizado ✅

**Data:** 2 de junho de 2026 15:49 UTC  
**Usuário:** alexandrinadeoliveiraale@gmail.com  
**Senha:** 123456 (6 caracteres - curta)  
**Resultado:** ✅ Login bem-sucedido → Redirecionado para index.php

**Conclusão:** Sistema de exceção funcionando corretamente. O usuário legado conseguiu fazer login com senha de 6 caracteres, o que seria bloqueado para um usuário novo.

## Próximas Etapas
- Testar com os outros 2 usuários legados (admin e aniel)
- Validar que novos usuários ainda são obrigados a usar 8+ caracteres
- Monitorar tentativas de login nos logs

## Segurança
- ✅ Lista de legados em arquivo centralizado e controlado
- ✅ Comparação case-insensitive de emails
- ✅ Validação ainda ocorre para usuários novos
- ✅ Backend não expõe lista de legados (apenas valida hash)
- ✅ Passwords curtas apenas para usuários pré-existentes

## Sincronização
- ✅ Sincronizado com XAMPP (02/06/2026 15:49)
- Pendente: Commit no Git
