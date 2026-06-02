# 📋 LISTA RÁPIDA DE FORMULÁRIOS - KALIYE Platform
## 02 de junho de 2026

---

## LEGENDAS
- ✅ = Validação Forte
- ⚠️ = Validação Parcial
- ❌ = Sem Validação
- 📚 = Referência/Exemplo

---

## TABELA RÁPIDA - TODOS OS FORMULÁRIOS

| # | Ficheiro | Localização | Tipo | Campos | Estado | Método | Destinação |
|---|----------|-------------|------|--------|--------|--------|-----------|
| 1 | entrar.php | autenticacao/ | LOGIN | email, password | ✅ | POST | login_action.php |
| 2 | registar.php | autenticacao/ | REGISTO | 11 campos | ✅ | POST | register_action.php |
| 3 | recuperar_senha.php | autenticacao/ | FORGOT | email | ✅ | POST | forgot_password_action.php |
| 4 | redefinir_senha.php | autenticacao/ | RESET | password, confirm | ✅ | POST | reset_password_action.php |
| 5 | verificar_email.php | autenticacao/ | OTP | 6x OTP | ✅ | POST | verification |
| 6 | verify_2fa_entrar.php | autenticacao/ | 2FA | code (6dig) | ✅ | FETCH | verify_2fa.php |
| 7 | completar_perfil.php | paginas/conta/ | COMPLETE GOOGLE | 5 campos | ✅ | FETCH | complete_google_profile.php |
| 8 | edit_profile_modal.php | inclusoes/components/ | EDIT PROFILE | 9 campos | ⚠️ | POST | internal |
| 9 | profile_settings_content.php | inclusoes/components/ | SETTINGS | 5 campos | ✅ | FETCH | update_profile.php |
| 10 | profile_kyc_content.php | inclusoes/components/ | KYC UPLOAD | 5 campos | ⚠️ | Multipart | internal |
| 11 | kyc_modal.php | inclusoes/components/ | KYC WIZARD | 4 campos | ✅ | Multipart | internal |
| 12 | admins.php | administracao/users/ | CREATE ADMIN | full_name, email, password, permissions | ✅ | POST | create_admin.php |
| 13 | settings.php | administracao/system/ | ADMIN SETTINGS | [vários] | ✅ | POST | admin_save_settings.php |
| 14 | form_ad.php | administracao/marketing/ | CREATE AD | 15+ campos | ✅ | POST | save_ad.php |
| 15 | broadcast.php | administracao/newsletter/ | BROADCAST | newsletter data | ⚠️ | POST | internal |
| 16 | support.php | administracao/moderation/ | SUPPORT FILTER | GET filters | ⚠️ | GET | internal |
| 17 | doubts.php | administracao/moderation/ | MODERATE DOUBTS | [doubt edit] | ⚠️ | FETCH | internal |
| 18 | retention_management.php | administracao/system/ | RETENTION | [retention data] | ⚠️ | POST | internal |
| 19 | content_audit.php | administracao/system/ | CONTENT AUDIT | filters | ⚠️ | GET | internal |
| 20 | announcements.php | administracao/marketing/ | ANNOUNCEMENTS | [announce data] | ⚠️ | POST | internal |
| 21 | finances.php | administracao/finance/ | DISTRIBUTE CAPITAL | amount, target | ⚠️ | POST | admin_distribute_capital.php |
| 22 | mentor_app_modal.php | inclusoes/components/ | MENTOR APPLICATION | specialty, exp, linkedin, cv | ✅ | Multipart | submit_mentor_application.php |
| 23 | mentorship_modals.php | inclusoes/components/ | ADD TASK | task_name, desc, date | ⚠️ | FETCH | internal |
| 24 | mentorship_modals.php | inclusoes/components/ | ADD SLOT | slot_date, time, duration | ⚠️ | FETCH | internal |
| 25 | mentorship_modals.php | inclusoes/components/ | FEEDBACK | feedback_text, rating | ⚠️ | FETCH | internal |
| 26 | mentorship_modals.php | inclusoes/components/ | OFFER MENTORSHIP | mentorship_data | ⚠️ | FETCH | internal |
| 27 | mentorship_modals.php | inclusoes/components/ | ADD RESOURCE | resource_file | ⚠️ | Multipart | internal |
| 28 | mentorship_modals.php | inclusoes/components/ | ADD NOTICE | title, description | ⚠️ | FETCH | internal |
| 29 | free_mentorship_requests.php | paginas/mentoria/ | MENTORIA REQUEST | [request data] | ⚠️ | FETCH | internal |
| 30 | wallet_modals.php | inclusoes/components/ | DEPOSIT | amount | ✅ | FETCH | internal |
| 31 | wallet_modals.php | inclusoes/components/ | WITHDRAWAL | amount, iban | ✅ | FETCH | internal |
| 32 | wallet_modals.php | inclusoes/components/ | UPLOAD PROOF | investment_id, proof_doc | ✅ | Multipart | internal |
| 33 | invest_modal.php | inclusoes/components/ | INVESTMENT (STEP1) | amount, currency, type, equity/return | ✅ | FETCH | internal |
| 34 | invest_modal.php | inclusoes/components/ | INVESTMENT (STEP2) | motivation | ✅ | FETCH | internal |
| 35 | doubt_modal.php | inclusoes/components/ | CREATE DOUBT | title, desc, category, image, tags | ✅ | FETCH | post_doubt.php |
| 36 | doubts_scripts.php | inclusoes/components/ | COMMENT | comment_text | ⚠️ | FETCH | internal |
| 37 | chat_area.php | inclusoes/components/ | CHAT MESSAGE | message, file | ⚠️ | Multipart | internal |
| 38 | chat_area.php | inclusoes/components/ | CHAT SAFETY | reason, description | ⚠️ | FETCH | internal |
| 39 | project_modal.php | inclusoes/components/ | PROJECT (MULTI-STEP) | 10+ campos | ✅ | Multipart | post_project.php |
| 40 | project_filter.php | inclusoes/components/ | PROJECT FILTER | GET filters | ⚠️ | GET | internal |
| 41 | booking_modal.php | inclusoes/components/ | BOOKING MENTORSHIP | booking_data | ⚠️ | POST | book_session.php |
| 42 | review_modal.php | inclusoes/components/ | REVIEW | review_text, rating | ⚠️ | POST | post_review.php |
| 43 | masterclass_modal.php | inclusoes/components/ | MASTERCLASS | masterclass_data | ⚠️ | POST | create_masterclass.php |
| 44 | edit_availability_modal.php | inclusoes/components/ | EDIT AVAILABILITY | availability_slots | ⚠️ | POST | update_availability.php |
| 45 | add_skill_modal.php | inclusoes/components/ | ADD SKILL | skill_name, level | ⚠️ | FETCH | internal |
| 46 | expertise_system.php | inclusoes/components/ | ADD EXPERTISE | expertise_name, level | ⚠️ | FETCH | internal |
| 47 | newsletter_section.php | inclusoes/components/ | NEWSLETTER | email | ✅ | FETCH | internal |
| 48 | landing_footer.php | inclusoes/components/ | FOOTER NEWSLETTER | email | ✅ | FETCH | internal |
| 49 | rodape.php | inclusoes/ | FOOTER FORM | [footer data] | ✅ | POST | internal |
| 50 | messages.php | paginas/social/ | EDIT GROUP | group_name | ⚠️ | FETCH | internal |
| 51 | investor_dashboard_sidebar.php | inclusoes/components/ | INVESTOR FILTER | GET filters | ⚠️ | GET | internal |
| 52 | profile_edit_modal.php | inclusoes/components/ | PROFILE EDIT MODAL | profile_data | ⚠️ | FETCH | internal |
| 53 | index.php | raiz | FEED FILTERS | GET filters | ⚠️ | GET | internal |
| 54 | exemplo_campos_validacao.php | inclusoes/components/ | EXEMPLO VALIDAÇÃO | [example fields] | 📚 | N/A | REFERENCE |
| 55 | comparador_tabelas.php | argumentos/ | COMPARADOR TABELAS | form_data | ⚠️ | POST | internal |
| 56 | [divesos] | [vários] | [outros] | [vários] | ⚠️ | [vários] | [vários] |

---

## AGRUPAMENTO POR ESTADO DE VALIDAÇÃO

### ✅ VALIDAÇÃO FORTE (18 formulários)
1. autenticacao/entrar.php - LOGIN
2. autenticacao/registar.php - REGISTRO
3. autenticacao/recuperar_senha.php - FORGOT
4. autenticacao/redefinir_senha.php - RESET
5. autenticacao/verificar_email.php - OTP
6. autenticacao/verify_2fa_entrar.php - 2FA
7. paginas/conta/completar_perfil.php - COMPLETE GOOGLE
8. inclusoes/components/profile_settings_content.php - SETTINGS
9. inclusoes/components/kyc_modal.php - KYC WIZARD
10. administracao/users/admins.php - CREATE ADMIN
11. administracao/system/settings.php - SETTINGS
12. administracao/marketing/form_ad.php - CREATE AD
13. inclusoes/components/mentor_app_modal.php - MENTOR APP
14. inclusoes/components/wallet_modals.php - DEPOSIT
15. inclusoes/components/wallet_modals.php - WITHDRAWAL
16. inclusoes/components/wallet_modals.php - UPLOAD PROOF
17. inclusoes/components/invest_modal.php - INVESTMENT
18. inclusoes/components/doubt_modal.php - CREATE DOUBT
19. inclusoes/components/project_modal.php - PROJECT
20. inclusoes/components/newsletter_section.php - NEWSLETTER
21. inclusoes/components/landing_footer.php - FOOTER NEWSLETTER

### ⚠️ VALIDAÇÃO PARCIAL (28 formulários)
[Todos os restantes]

---

## AGRUPAMENTO POR CATEGORIA

### 🔐 AUTENTICAÇÃO & SEGURANÇA (6)
- entrar.php ✅
- registar.php ✅
- recuperar_senha.php ✅
- redefinir_senha.php ✅
- verificar_email.php ✅
- verify_2fa_entrar.php ✅

### 👤 PERFIL & CONTA (5)
- completar_perfil.php ✅
- edit_profile_modal.php ⚠️
- profile_settings_content.php ✅
- profile_kyc_content.php ⚠️
- kyc_modal.php ✅

### 🎓 MENTORIA (9)
- mentor_app_modal.php ✅
- mentorship_modals.php (6 forms) ⚠️
- free_mentorship_requests.php ⚠️

### 💰 CARTEIRA & INVESTIMENTO (5)
- wallet_modals.php (3 forms) ✅
- invest_modal.php (2 forms) ✅

### 🛠️ ADMINISTRAÇÃO (10)
- admins.php ✅
- settings.php ✅
- form_ad.php ✅
- broadcast.php ⚠️
- support.php ⚠️
- doubts.php ⚠️
- retention_management.php ⚠️
- content_audit.php ⚠️
- announcements.php ⚠️
- finances.php ⚠️

### 🗨️ DÚVIDAS & CHAT (4)
- doubt_modal.php ✅
- doubts_scripts.php ⚠️
- chat_area.php (2 forms) ⚠️

### 📱 PROJETOS (2)
- project_modal.php ✅
- project_filter.php ⚠️

### 🔗 COMPONENTES DIVERSOS (10)
- booking_modal.php ⚠️
- review_modal.php ⚠️
- masterclass_modal.php ⚠️
- edit_availability_modal.php ⚠️
- add_skill_modal.php ⚠️
- expertise_system.php ⚠️
- newsletter_section.php ✅
- landing_footer.php ✅
- investor_dashboard_sidebar.php ⚠️
- profile_edit_modal.php ⚠️

---

## CAMPOS CRÍTICOS A VALIDAR

### Tipo EMAIL
- autenticacao/entrar.php
- autenticacao/registar.php
- autenticacao/recuperar_senha.php
- administracao/users/admins.php
- administracao/marketing/form_ad.php
- inclusoes/components/newsletter_section.php
- inclusoes/components/landing_footer.php

### Tipo NUMBER (Valores Financeiros)
- inclusoes/components/wallet_modals.php (amount)
- inclusoes/components/invest_modal.php (amount, equity_percentage)
- administracao/marketing/form_ad.php (budget)

### Tipo FILE (Upload)
- autenticacao/registar.php (opcional)
- inclusoes/components/mentor_app_modal.php (cv_file - PDF)
- inclusoes/components/profile_kyc_content.php (bi_front, bi_back, selfie)
- inclusoes/components/kyc_modal.php (doc_front, doc_back, selfie)
- inclusoes/components/wallet_modals.php (proof_doc)
- administracao/marketing/form_ad.php (image)

### Tipo TEXTAREA (Long Text)
- autenticacao/registar.php
- inclusoes/components/profile_settings_content.php (bio)
- inclusoes/components/invest_modal.php (motivation)
- inclusoes/components/doubt_modal.php (description)
- inclusoes/components/project_modal.php (description)

### Tipo SELECT (Enum)
- autenticacao/registar.php (user_type)
- paginas/conta/completar_perfil.php (user_type)
- inclusoes/components/profile_settings_content.php (location)
- inclusoes/components/kyc_modal.php (id_type)
- administracao/marketing/form_ad.php (type, status)
- inclusoes/components/invest_modal.php (currency, investment_type)
- inclusoes/components/doubt_modal.php (category)
- inclusoes/components/project_modal.php (project_stage)

---

## URLS DE AÇÃO (DESTINO DOS FORMULÁRIOS)

### interface_programacao/auth/
- login_action.php
- register_action.php
- forgot_password_action.php
- reset_password_action.php
- complete_google_profile.php
- verify_2fa.php

### interface_programacao/admin/
- create_admin.php
- admin_save_settings.php
- admin_distribute_capital.php

### interface_programacao/user/
- update_profile.php
- update_kyc.php

### interface_programacao/marketing/
- save_ad.php

### interface_programacao/mentorship/
- submit_mentor_application.php

### interface_programacao/projects/
- post_project.php

### servicos/doubts/
- post_doubt.php

### servicos/mentorship/
- book_session.php
- create_masterclass.php
- update_availability.php

### servicos/social/
- post_review.php

---

## RECOMENDAÇÕES DE SEGURANÇA POR CATEGORIA

### 🔴 CRÍTICO - IMPLEMENTAR IMEDIATAMENTE
1. ✅ CSRF Protection em TODOS os POST/FETCH forms
2. ✅ Input sanitization para email, URL, numbers
3. ✅ File upload validation (tipo MIME, tamanho máximo)
4. ✅ SQL injection prevention (prepared statements)
5. ✅ Rate limiting em login/registration
6. ✅ Password strength validation
7. ✅ Session timeout
8. ✅ Error message masking (não revelar estrutura DB)

### 🟠 ALTO - IMPLEMENTAR NAS PRÓXIMAS SPRINTS
1. ⚠️ XSS prevention em textarea/long text
2. ⚠️ Validação de max file size
3. ⚠️ Whitelist de allowed extensions
4. ⚠️ CORS security headers
5. ⚠️ Content Security Policy (CSP)

### 🟡 MÉDIO - MONITORAR
1. ⚠️ Audit logging para operações admin
2. ⚠️ Anomaly detection em transações
3. ⚠️ Backup & disaster recovery
4. ⚠️ Penetration testing

---

## CHECKLIST DE IMPLEMENTAÇÃO SUGERIDA

### Fase 1 - CRÍTICO (Semana 1-2)
- [ ] Adicionar CSRF tokens em todos os forms
- [ ] Implementar email validation regex
- [ ] Validar file uploads (MIME type check)
- [ ] Implementar prepared statements
- [ ] Rate limiting em login

### Fase 2 - ALTO (Semana 3-4)
- [ ] XSS prevention em textareas
- [ ] File size limits
- [ ] Password strength requirements
- [ ] Session security (HTTPOnly, Secure flags)

### Fase 3 - MÉDIO (Semana 5+)
- [ ] Audit logging
- [ ] Security headers
- [ ] Penetration testing
- [ ] Documentation updates

---

**Data**: 02/06/2026  
**Gerado por**: Análise Automática  
**Status**: ✅ Completo
