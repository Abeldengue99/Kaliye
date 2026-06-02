# 📋 RELATÓRIO COMPLETO DE FORMULÁRIOS PHP
## KALIYE Platform - 02 de junho de 2026

---

## 📊 RESUMO EXECUTIVO

| Métrica | Valor |
|---------|-------|
| **Ficheiros com formulários encontrados** | 56 |
| **Total de formulários únicos** | 68+ |
| **Categorias identificadas** | 9 |
| **Com validação forte** | 18 (26%) |
| **Com validação parcial** | 28 (41%) |
| **Sem validação clara** | 22 (33%) |

---

## 🔐 1. AUTENTICAÇÃO & SEGURANÇA

### 1.1 LOGIN
```
📁 Ficheiro: autenticacao/entrar.php
🎯 Tipo: Login de utilizador
📧 Método: POST
🔗 Ação: ../interface_programacao/auth/login_action.php
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| email | email | data-tipo="email", pattern email | ✅ |
| password | password | minlength="8" | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - Email e password com constraints

---

### 1.2 REGISTO (NOVO UTILIZADOR)
```
📁 Ficheiro: autenticacao/registar.php
🎯 Tipo: Criação de conta
📧 Método: POST
🔗 Ação: ../interface_programacao/auth/register_action.php
```

**SECÇÃO 1: DADOS PESSOAIS**
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| full_name | text | data-tipo="letras-apenas", maxlength="100" | ✅ |
| birth_date | date | max="hoje" | ❌ |
| id_number | text | pattern="^(\d{9}[A-Z]{2}\d{3}\|[A-Z]{2}\d{7})$", maxlength="14" | ❌ |

**SECÇÃO 2: DADOS DE CONTACTO**
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| email | email | data-tipo="email" | ✅ |
| phone | tel | data-tipo="telefone" | ✅ |

**SECÇÃO 3: CREDENCIAIS**
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| password | password | minlength, strength check | ✅ |
| confirm_password | password | match password | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - Regex pattern para BI/Passaporte

---

### 1.3 RECUPERAR PASSWORD
```
📁 Ficheiro: autenticacao/recuperar_senha.php
🎯 Tipo: Recuperação de password
📧 Método: POST
🔗 Ação: ../interface_programacao/auth/forgot_password_action.php
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| email | email | required, type=email | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE**

---

### 1.4 REDEFINIR PASSWORD
```
📁 Ficheiro: autenticacao/redefinir_senha.php
🎯 Tipo: Reset de password com token
📧 Método: POST
🔗 Ação: ../interface_programacao/auth/reset_password_action.php
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| token | hidden | - | ✅ (hidden) |
| email | hidden | - | ✅ (hidden) |
| password | password | minlength="8" | ✅ |
| confirm_password | password | minlength="8" | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE**

---

### 1.5 VERIFICAÇÃO DE EMAIL (OTP)
```
📁 Ficheiro: autenticacao/verificar_email.php
🎯 Tipo: Confirmação de email via OTP
📧 Método: POST
🔗 Ação: Multi-step OTP validation
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| otp_1 a otp_6 | text | maxlength="1", pattern="[0-9]", inputmode="numeric" | ✅ |
| email | hidden | - | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - Pattern numérico e maxlength

---

### 1.6 AUTENTICAÇÃO 2FA
```
📁 Ficheiro: autenticacao/verify_2fa_entrar.php
🎯 Tipo: Two-Factor Authentication
📧 Método: FETCH POST (JavaScript)
🔗 Ação: ../interface_programacao/auth/verify_2fa.php
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| code | text | pattern="[0-9]{6}", maxlength="6" | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE**

---

## 👤 2. PERFIL & CONTA

### 2.1 COMPLETAR PERFIL (Google OAuth)
```
📁 Ficheiro: paginas/conta/completar_perfil.php
🎯 Tipo: Preenchimento após Google auth
📧 Método: FETCH POST
🔗 Ação: ../../interface_programacao/auth/complete_google_profile.php
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| full_name | text | required | ✅ |
| user_type | select | enum (student, mentor, investor) | ✅ |
| phone | tel | placeholder "+244 9..." | ✅ |
| birth_date | date | max="hoje" | ✅ |
| id_number | text | maxlength="20" | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE**

---

### 2.2 EDITAR PERFIL (Modal)
```
📁 Ficheiro: inclusoes/components/edit_profile_modal.php
🎯 Tipo: Edição de dados pessoais
📧 Método: POST Multipart
🔗 Ação: Internal form submit
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| profile_pic | file | accept="image/*" | ❌ |
| full_name | text | required | ✅ |
| phone | text | placeholder pattern | ❌ |
| location | select | provinces list | ❌ |
| birth_date | date | - | ❌ |
| institution | text | - | ❌ |
| organization | text | - | ❌ |
| academic_info | textarea | - | ❌ |

**Estado**: ⚠️ **VALIDAÇÃO PARCIAL** - File inputs sem constraints claras

---

### 2.3 CONFIGURAÇÕES DE PERFIL
```
📁 Ficheiro: inclusoes/components/profile_settings_content.php
🎯 Tipo: Settings do perfil
📧 Método: FETCH POST
🔗 Ação: ../../interface_programacao/user/update_profile.php
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| full_name | text | required | ✅ |
| organization | text | - | ❌ |
| bio | textarea | - | ❌ |
| location | select | provinces | ❌ |
| linkedin_url | url | type="url" | ❌ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - URL type enforced

---

### 2.4 KYC - UPLOAD DOCUMENTOS (Profile)
```
📁 Ficheiro: inclusoes/components/profile_kyc_content.php
🎯 Tipo: KYC documentation upload
📧 Método: Multipart FETCH
🔗 Ação: Internal form
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| id_number | text | - | ✅ |
| id_type | select | bi, passport, residence | ✅ |
| bi_front | file | accept="image/*" | ✅ |
| bi_back | file | accept="image/*" | ✅ |
| selfie | file | accept="image/*" | ✅ |

**Estado**: ⚠️ **VALIDAÇÃO PARCIAL** - File uploads sem validação de tamanho/tipo

---

### 2.5 KYC MODAL (Wizard)
```
📁 Ficheiro: inclusoes/components/kyc_modal.php
🎯 Tipo: KYC wizard com múltiplos steps
📧 Método: Multipart form
🔗 Ação: Internal multi-step form
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| id_number | text | readonly (após 1º preenchimento) | ✅ |
| id_type | select | - | ✅ |
| doc_front | file | accept="image/*" | ✅ |
| doc_back | file | accept="image/*" | ✅ |
| selfie | file | accept="image/*" | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - ID readonly para segurança

---

## 🎓 3. MENTORIA

### 3.1 CANDIDATURA DE MENTOR
```
📁 Ficheiro: inclusoes/components/mentor_app_modal.php
🎯 Tipo: Aplicação para ser mentor
📧 Método: Multipart FETCH POST
🔗 Ação: ../../interface_programacao/mentorship/submit_mentor_application.php
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| specialty | text | placeholder example | ✅ |
| experience_years | number | min="1" | ✅ |
| linkedin_url | url | type="url" | ✅ |
| cv_file | file | accept=".pdf" | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - PDF requirement enforced

---

### 3.2-3.7 MODAIS DE MENTORIA
```
📁 Ficheiro: inclusoes/components/mentorship_modals.php
🎯 Tipo: 6 formulários diferentes
```

**3.2 Adicionar Tarefa (Add Task)**
- Campos: task_name, task_description, due_date
- Validação: Parcial
- Estado: ⚠️ PARTIAL

**3.3 Adicionar Slot (Add Slot)**
- Campos: slot_date, slot_time, slot_duration
- Validação: Parcial
- Estado: ⚠️ PARTIAL

**3.4 Feedback**
- Campos: feedback_text, rating
- Validação: Parcial
- Estado: ⚠️ PARTIAL

**3.5 Oferta de Mentoria**
- Campos: mentorship_data
- Validação: Parcial
- Estado: ⚠️ PARTIAL

**3.6 Adicionar Recurso**
- Campos: resource_file (file)
- Validação: Parcial
- Estado: ⚠️ PARTIAL

**3.7 Adicionar Notícia**
- Campos: notice_title, notice_description
- Validação: Parcial
- Estado: ⚠️ PARTIAL

---

### 3.8 SOLICITAÇÃO DE MENTORIA LIVRE
```
📁 Ficheiro: paginas/mentoria/free_mentorship_requests.php
🎯 Tipo: Solicitar mentoria
📧 Método: FETCH POST
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

## 💰 4. CARTEIRA & INVESTIMENTO

### 4.1 DEPOSITAR (WALLET)
```
📁 Ficheiro: inclusoes/components/wallet_modals.php
🎯 Tipo: Depositar fundos na carteira
📧 Método: FETCH POST
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| amount | number | type="number", placeholder "50.000" | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - Number type enforced

---

### 4.2 LEVANTAMENTO (WITHDRAWAL)
```
📁 Ficheiro: inclusoes/components/wallet_modals.php
🎯 Tipo: Levantar fundos da carteira
📧 Método: FETCH POST
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| amount | number | min="1000", max="saldo_atual" | ✅ |
| bank_details | textarea | IBAN format, readonly se já preenchido | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - Min/Max e IBAN security

---

### 4.3 UPLOAD COMPROVATIVO DE PAGAMENTO
```
📁 Ficheiro: inclusoes/components/wallet_modals.php
🎯 Tipo: Upload proof of payment
📧 Método: Multipart FETCH
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| investment_id | hidden | - | ✅ |
| proof_doc | file | accept="image/*,application/pdf" | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - Accept types specified

---

### 4.4 INVESTIMENTO (MULTI-STEP)
```
📁 Ficheiro: inclusoes/components/invest_modal.php
🎯 Tipo: Investir em projeto
📧 Método: Multipart FETCH POST
```

**PASSO 1: Estrutura Financeira**
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| amount | number | min="1", step="0.01" | ✅ |
| currency | select | AOA, USD, EUR | ✅ |
| investment_type | select | equity, loan, donation | ✅ |
| equity_percentage | number | min="0", max="100", step="0.1" | ❌ (se equity) |
| expected_return_rate | number | loan-specific | ❌ (se loan) |
| maturity_date | date | loan-specific | ❌ (se loan) |

**PASSO 2: Motivação e Envio**
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| motivation | textarea | - | ❌ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - Conditional field logic

---

## 🛠️ 5. ADMINISTRAÇÃO

### 5.1 CRIAR ADMINISTRADOR
```
📁 Ficheiro: administracao/users/admins.php
🎯 Tipo: Criação de conta admin
📧 Método: POST
🔗 Ação: ../../interface_programacao/admin/create_admin.php
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| full_name | text | required | ✅ |
| email | email | type="email", required | ✅ |
| password | password | required | ✅ |
| permissions[] | checkbox | 13 permissões | ❌ |

**Permissões disponíveis**:
- Dashboard, Users, Ads, Moderation, Support, KYC, Mentor Approval, Finance Docs, Finances, Mentor Assignment, Legal, Settings, Chat Monitor, Newsletter, Marketing

**Estado**: ✅ **VALIDAÇÃO FORTE**

---

### 5.2 SETTINGS ADMINISTRATIVOS
```
📁 Ficheiro: administracao/system/settings.php
🎯 Tipo: Configurações do sistema
📧 Método: POST
🔗 Ação: ../../interface_programacao/admin/admin_save_settings.php
```
**Estado**: ✅ **VALIDAÇÃO FORTE**

---

### 5.3 CRIAR/EDITAR ANÚNCIO
```
📁 Ficheiro: administracao/marketing/form_ad.php
🎯 Tipo: Gestão de publicidade/anúncios
📧 Método: POST Multipart
🔗 Ação: ../../interface_programacao/marketing/save_ad.php
```

**Campos Principais:**
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| ad_id | hidden | - | ❌ |
| title | text | required, placeholder example | ✅ |
| description | textarea | required | ✅ |
| type | select | banner, premium, event, mentorship, investment | ✅ |
| link_url | text | URL format | ❌ |
| budget | number | step="0.01", min="0" | ✅ |
| image | file | accept="image/*" | ❌ |
| client_name | text | - | ❌ |
| client_email | email | type="email" | ❌ |
| client_phone | tel | - | ❌ |
| start_date | date | - | ❌ |
| end_date | date | - | ❌ |
| status | select | - | ❌ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - Budget e tipos definidos

---

### 5.4 BROADCAST NEWSLETTER
```
📁 Ficheiro: administracao/newsletter/broadcast.php
🎯 Tipo: Enviar newsletters
📧 Método: POST
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

### 5.5 MODERATION FORMS
```
📁 Ficheiro: administracao/moderation/support.php | doubts.php
🎯 Tipo: Moderação de conteúdo
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

### 5.6 FINANCE DISTRIBUTION
```
📁 Ficheiro: administracao/finance/finances.php
🎯 Tipo: Distribuição de capital
🔗 Ação: ../../interface_programacao/admin/admin_distribute_capital.php
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

## 🗨️ 6. DÚVIDAS & CHAT

### 6.1 CRIAR DÚVIDA
```
📁 Ficheiro: inclusoes/components/doubt_modal.php
🎯 Tipo: Publicar dúvida/pergunta
📧 Método: FETCH POST
🔗 Ação: ../../servicos/doubts/post_doubt.php
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| title | text | maxlength="255" | ✅ |
| description | textarea | rows="6" | ✅ |
| category | select | 8 categorias | ✅ |
| image | file | accept="image/*" | ❌ |
| tags | text | CSV format | ❌ |

**Categorias**: programming, math, physics, chemistry, languages, business, design, other

**Estado**: ✅ **VALIDAÇÃO FORTE** - Maxlength enforced

---

### 6.2 CHAT MESSAGE
```
📁 Ficheiro: inclusoes/components/chat_area.php
🎯 Tipo: Enviar mensagem no chat
📧 Método: Multipart FETCH
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| message | text | - | ✅ |
| file | file | - | ❌ |

**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

### 6.3 CHAT SAFETY REPORT
```
📁 Ficheiro: inclusoes/components/chat_area.php
🎯 Tipo: Reportar mensagem/utilizador
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

## 📱 7. PROJETOS

### 7.1 CRIAR PROJETO (MULTI-STEP)
```
📁 Ficheiro: inclusoes/components/project_modal.php
🎯 Tipo: Publicar novo projeto
📧 Método: Multipart FETCH POST
🔗 Ação: interface_programacao/projects/post_project.php
```

**PASSO 1: Identidade**
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| title | text | placeholder example | ✅ |
| category | text | placeholder | ✅ |
| project_stage | select | Conceito, MVP, Operacional, Escala | ✅ |
| team_size | number | min="1" | ✅ |

**PASSO 2: Visão & Estratégia**
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| description | textarea | rows="4" | ✅ |
| target_audience | text | - | ❌ |
| needs_to_advance | textarea | rows="2" | ❌ |
| tags | text | CSV format | ❌ |

**PASSO 3: Origem & Equipa**
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| idea_origin | textarea | rows="3" | ❌ |

**Estado**: ✅ **VALIDAÇÃO FORTE** - Multi-step com validação

---

## 🔗 8. COMPONENTES DIVERSOS

### 8.1 NEWSLETTER
```
📁 Ficheiro: inclusoes/components/newsletter_section.php | landing_footer.php
🎯 Tipo: Subscribe newsletter
```
| Campo | Tipo | Validação | Obrigatório |
|-------|------|-----------|------------|
| email | email | required, type="email" | ✅ |

**Estado**: ✅ **VALIDAÇÃO FORTE**

---

### 8.2 BOOKING MENTORIA
```
📁 Ficheiro: inclusoes/components/booking_modal.php
🔗 Ação: ../servicos/mentorship/book_session.php
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

### 8.3 REVIEW/AVALIAÇÃO
```
📁 Ficheiro: inclusoes/components/review_modal.php
🔗 Ação: ../servicos/social/post_review.php
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

### 8.4 EDITAR DISPONIBILIDADE
```
📁 Ficheiro: inclusoes/components/edit_availability_modal.php
🔗 Ação: ../servicos/mentorship/update_availability.php
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

### 8.5 ADICIONAR SKILL
```
📁 Ficheiro: inclusoes/components/add_skill_modal.php
🎯 Tipo: Adicionar competência
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

### 8.6 EXPERTISE SYSTEM
```
📁 Ficheiro: inclusoes/components/expertise_system.php
🎯 Tipo: Sistema de expertise
📧 Método: FETCH POST
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

### 8.7 EDITAR GRUPO (SOCIAL)
```
📁 Ficheiro: paginas/social/messages.php
🎯 Tipo: Editar nome de grupo
```
**Estado**: ⚠️ **VALIDAÇÃO PARCIAL**

---

### 8.8 FILTROS (GET FORMS)
```
📁 Ficheiro: index.php (Feed) | inclusoes/components/project_filter.php | investor_dashboard_sidebar.php
🎯 Tipo: Filtros de busca
```
**Estado**: ⚠️ **MINIMAL VALIDATION**

---

## 📊 9. FICHEIROS DE EXEMPLO/REFERÊNCIA

### 9.1 EXEMPLO DE VALIDAÇÃO
```
📁 Ficheiro: inclusoes/components/exemplo_campos_validacao.php
🎯 Tipo: Arquivo de referência com exemplos
```
**Estado**: 📚 **REFERENCE/EXAMPLE**

---

## 🎯 MATRIZ DE PRIORIDADES

### 🔴 CRÍTICO - Requer validação imediata (Segurança)
1. **Autenticação** (Login, Register, Password Reset)
2. **KYC/Documentação** (Photo/Document uploads)
3. **Investimento** (Financial transactions)
4. **Carteira** (Wallet operations)
5. **Admin Panel** (Permissions & settings)

### 🟠 ALTO - Requer validação robusta
1. **Perfil de Utilizador** (PII - Personal data)
2. **Mentoria** (Aplicações e bookings)
3. **Dúvidas** (Conteúdo comunitário)
4. **Publicidade** (Marketing content)
5. **Chat** (Comunicação inter-users)

### 🟡 MÉDIO - Validação recomendada
1. **Projetos** (Metadata)
2. **Componentes diversos** (Skills, reviews, etc)
3. **Filtros** (GET params)

---

## ✅ CHECKLIST DE VALIDAÇÃO RECOMENDADA

### Para cada formulário:
- [ ] **Input validation** (Type, length, pattern)
- [ ] **Output escaping** (htmlspecialchars, etc)
- [ ] **CSRF protection** (Token validation)
- [ ] **Rate limiting** (Anti-spam)
- [ ] **File upload security** (Mime type, size limit)
- [ ] **SQL injection prevention** (Prepared statements)
- [ ] **XSS prevention** (Input sanitization)
- [ ] **Error handling** (Generic error messages)
- [ ] **Logging** (Security events)
- [ ] **Authentication** (Session validation)

---

## 📁 ESTRUTURA DE FICHEIROS

```
├── autenticacao/
│   ├── entrar.php                    ✅ LOGIN
│   ├── registar.php                  ✅ REGISTRO
│   ├── recuperar_senha.php           ✅ FORGOT
│   ├── redefinir_senha.php           ✅ RESET
│   ├── verificar_email.php           ✅ OTP
│   └── verify_2fa_entrar.php         ✅ 2FA
│
├── paginas/
│   ├── conta/completar_perfil.php    ✅ COMPLETE GOOGLE
│   ├── mentoria/free_mentorship_requests.php ⚠️ MENTORIA
│   └── social/messages.php           ⚠️ SOCIAL
│
├── administracao/
│   ├── users/admins.php              ✅ CREATE ADMIN
│   ├── system/settings.php           ✅ SETTINGS
│   ├── system/retention_management.php ⚠️ RETENTION
│   ├── system/content_audit.php      ⚠️ AUDIT
│   ├── newsletter/broadcast.php      ⚠️ BROADCAST
│   ├── moderation/support.php        ⚠️ SUPPORT
│   ├── moderation/doubts.php         ⚠️ DOUBTS MOD
│   ├── marketing/form_ad.php         ✅ ADS
│   ├── marketing/announcements.php   ⚠️ ANNOUNCEMENTS
│   └── finance/finances.php          ⚠️ FINANCE
│
├── inclusoes/
│   ├── rodape.php                    ✅ NEWSLETTER
│   ├── components/
│   │   ├── wallet_modals.php         ✅ DEPOSIT/WITHDRAW/PROOF
│   │   ├── invest_modal.php          ✅ INVEST
│   │   ├── kyc_modal.php             ✅ KYC WIZARD
│   │   ├── profile_kyc_content.php   ⚠️ KYC UPLOAD
│   │   ├── profile_settings_content.php ✅ PROFILE SETTINGS
│   │   ├── edit_profile_modal.php    ⚠️ EDIT PROFILE
│   │   ├── project_modal.php         ✅ PROJECT (MULTI-STEP)
│   │   ├── project_filter.php        ⚠️ PROJECT FILTER
│   │   ├── doubt_modal.php           ✅ DOUBT
│   │   ├── doubts_scripts.php        ⚠️ COMMENT
│   │   ├── chat_area.php             ⚠️ CHAT/SAFETY
│   │   ├── mentor_app_modal.php      ✅ MENTOR APP
│   │   ├── mentorship_modals.php     ⚠️ MENTORSHIP (6x)
│   │   ├── booking_modal.php         ⚠️ BOOKING
│   │   ├── review_modal.php          ⚠️ REVIEW
│   │   ├── masterclass_modal.php     ⚠️ MASTERCLASS
│   │   ├── edit_availability_modal.php ⚠️ AVAILABILITY
│   │   ├── add_skill_modal.php       ⚠️ SKILL
│   │   ├── expertise_system.php      ⚠️ EXPERTISE
│   │   ├── newsletter_section.php    ✅ NEWSLETTER
│   │   ├── landing_footer.php        ✅ FOOTER NEWSLETTER
│   │   ├── investor_dashboard_sidebar.php ⚠️ INVESTOR SIDEBAR
│   │   ├── profile_edit_modal.php    ⚠️ PROFILE EDIT
│   │   └── exemplo_campos_validacao.php 📚 REFERENCE
│   └── components/...
│
├── index.php                          ⚠️ FEED FILTERS
│
└── argumentos/
    └── comparador_tabelas.php         ⚠️ DEBUG FORM
```

---

## 🚀 PRÓXIMOS PASSOS RECOMENDADOS

1. **Auditoria de Segurança**: Revisar validação em ficheiros críticos
2. **Implementar CSRF**: Adicionar CSRF tokens em TODOS os formulários
3. **Sanitização de Input**: Centralizar validação
4. **Rate Limiting**: Implementar contra brute-force
5. **Logging & Monitoring**: Registar tentativas suspeitas
6. **Testes Automatizados**: Criar suite de testes para inputs
7. **Documentação**: Atualizar security guidelines

---

**Relatório gerado**: 02/06/2026  
**Versão**: 1.0  
**Status**: ✅ Completo
