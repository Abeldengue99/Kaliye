# 🔐 Setup Google OAuth - Guia Rápido

## ✅ Status Atual
- ✅ Sistema OAuth2 implementado e funcional
- ✅ Banco de dados preparado com campos `google_sub`, `auth_provider`
- ✅ Fluxo de callback pronto
- ✅ UI com botões Google em login e registo
- ⏳ **FALTA:** Credenciais do Google Cloud Console

---

## 📋 Passo 1: Obter Credenciais Google

### 1.1 Criar Projeto no Google Cloud Console

1. Abra: [Google Cloud Console](https://console.cloud.google.com/)
2. Clique em **"Select a Project"** → **"NEW PROJECT"**
3. Nome: `KALIYE Google OAuth`
4. Criar projeto
5. Aguarde alguns segundos até ativar

### 1.2 Ativar Google+ API

1. No painel, procure **"APIs & Services"** → **"Enabled APIs & services"**
2. Clique em **"+ ENABLE APIS AND SERVICES"**
3. Procure por: **"Google+ API"**
4. Clique e selecione **"ENABLE"**

### 1.3 Criar Credenciais OAuth 2.0

1. Vá para **"Credentials"** (lado esquerdo)
2. Clique **"+ CREATE CREDENTIALS"**
3. Selecione **"OAuth client ID"**
4. Se pedir para configurar OAuth Consent Screen:
   - Clique **"Configure Consent Screen"**
   - Selecione **"External"** (para testes)
   - Preencha:
     - **App name:** KALIYE
     - **User support email:** seu-email@exemplo.com
     - **Developer contact:** seu-email@exemplo.com
   - Clique **"Save and Continue"**
   - Não é necessário adicionar Scopes (próxima página)
   - Clique **"Save and Continue"** novamente
   - Clique **"Back to Dashboard"**

5. Volte para **"Credentials"**
6. Clique **"+ CREATE CREDENTIALS"** novamente
7. Selecione **"OAuth client ID"**
8. Escolha **"Web application"**
9. Nome: `KALIYE Web`
10. Em **"Authorized redirect URIs"**, adicione:
    ```
    http://localhost/aksanti/autenticacao/google_callback.php
    https://seu-dominio.com/aksanti/autenticacao/google_callback.php
    ```
11. Clique **"CREATE"**
12. Copie:
    - **Client ID**
    - **Client Secret**

---

## 🔧 Passo 2: Configurar no Sistema KALIYE

### 2.1 Aceder ao Painel de Administração

1. Login como Super Admin (Abel Dengue)
2. Vá para: **Administração** → **Configurações do Sistema**
3. Localize a seção **"Cadastro com Google"** (cartão com ícone do Google)

### 2.2 Preencher Credenciais

Na seção Google OAuth:

```
☑️ Google ativo           (Marcar para ativar)
━━━━━━━━━━━━━━━━━━━━━━━━━
Google Client ID:        [Colar Client ID do Google]
Google Client Secret:    [Colar Client Secret do Google]
Google Redirect URI:     (deixar em branco - auto-calcula)
━━━━━━━━━━━━━━━━━━━━━━━━━
```

### 2.3 Guardar Configurações

1. Clique em **"Guardar Alterações"** (barra fixa no fundo)
2. Aguarde confirmação de sucesso

---

## ✨ Passo 3: Testar

### Teste de Login com Google

1. Vá para: `http://localhost/aksanti/autenticacao/entrar.php`
2. Procure o botão **"Entrar com Google"**
3. Clique e siga o fluxo:
   - Selecione conta Google
   - Autorize acesso (KALIYE quer: email, perfil)
   - Será redirecionado para o Dashboard

### Teste de Registo com Google

1. Vá para: `http://localhost/aksanti/autenticacao/registar.php`
2. Procure o botão **"Criar conta com Google"**
3. Clique e autorize
4. Se primeira vez: será pedido para **completar perfil** (data nasc., ID, telefone)
5. Conta criada! 🎉

---

## 🚀 Fluxos Automáticos

### Login com Google Existente
```
Usuario clica "Entrar com Google"
    ↓
Google OAuth verifica conta
    ↓
Inicia sessão automaticamente
    ↓
Redireciona para Dashboard
```

### Registo com Google (Novo)
```
Usuario clica "Criar conta com Google"
    ↓
Google OAuth cria novo utilizador
    ↓
Status: profile_completed = FALSE
    ↓
Redireciona para "Completar Perfil"
    ↓
Usuario preenche dados (telefone, ID, etc)
    ↓
Conta ativada! 
```

---

## 📊 Dados Armazenados

Quando um utilizador faz login/registo com Google:

| Campo | Valor |
|-------|-------|
| `auth_provider` | `google` |
| `google_sub` | ID único do Google |
| `is_verified` | `TRUE` (email verificado) |
| `email` | Email da conta Google |
| `profile_pic` | Avatar do Google |
| `password_hash` | Hash aleatório (não usado) |

---

## 🔍 Troubleshooting

### "Falha ao validar a conta Google"
- ✅ Verificar se Client Secret está correto
- ✅ Verificar se Redirect URI está autorizado no Google Console

### "Cadastro com Google indisponível"
- ✅ Google ativo está marcado?
- ✅ Client ID está preenchido?
- ✅ Client Secret está preenchido?

### Botão Google não aparece
- ✅ Desativar cache do navegador (Ctrl+Shift+Del)
- ✅ Verificar se `google_auth_enabled = 1` na BD

---

## 📝 Ficheiros Principais

| Ficheiro | Responsabilidade |
|----------|-----------------|
| `autenticacao/google_iniciar.php` | Inicia fluxo OAuth |
| `autenticacao/google_callback.php` | Processa callback Google |
| `inclusoes/GoogleOAuth.php` | Lógica principal OAuth |
| `administracao/system/settings.php` | Painel de configuração |
| `inclusoes/components/admin/settings_cards.php` | Formulário das credenciais |

---

## ✅ Checklist Final

- [ ] Projeto criado no Google Cloud Console
- [ ] Google+ API ativada
- [ ] OAuth 2.0 credenciais criadas
- [ ] Redirect URI adicionado no Google Console
- [ ] Client ID copiado
- [ ] Client Secret copiado
- [ ] Credenciais inseridas no painel KALIYE
- [ ] Google ativo marcado em "Cadastro com Google"
- [ ] Alterações guardadas
- [ ] Testado login com Google
- [ ] Testado registo com Google
- [ ] Perfil completado com sucesso

---

**Sistema pronto para usar! 🎉**
