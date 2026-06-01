# 📋 CHECKLIST - PROCESSO DE BACKUP E ELIMINAÇÃO

**Iniciado:** 1 de junho de 2026  
**Responsável:** [Seu Nome]  
**Fase Atual:** 1️⃣ BACKUP

---

## 🔐 FASE 1: BACKUP (STATUS: ⏳ PENDENTE)

```
❌ [  ] Confirmar que PostgreSQL está em execução
        Comando: Get-Service postgresql-x64-18 | Select-Object Status
        
❌ [  ] Confirmar que pg_dump existe
        Local: C:\Program Files\PostgreSQL\18\bin\pg_dump.exe
        
❌ [  ] Executar EXECUTAR_BACKUP.ps1
        Método: .\EXECUTAR_BACKUP.ps1 (em PowerShell)
        
❌ [  ] Aguardar conclusão (30-60 segundos)
        Visual: Barra de progresso verde
        
❌ [  ] Confirmar ficheiro foi criado
        Verificar: C:\Users\nee\Documents\Aksanti Referências\backups\
        Tamanho: deve ser > 1 MB
        
❌ [  ] Guardar backup em local seguro
        ☐ Pen drive
        ☐ OneDrive/Google Drive
        ☐ Email (se < 25 MB)
        ☐ Outro: _________________
        
❌ [  ] Testar restauração (OPCIONAL)
        Método: Restaurar backup em BD de teste
        Status: _______________
```

---

## 📊 FASE 2: ANÁLISE FINAL (STATUS: ⏸️ AGUARDANDO BACKUP)

```
Será ativada após FASE 1 estar completa

❌ [  ] Revisar lista de tabelas a eliminar
        ✓ test_table_1 (0 registos, 0 KB)
        ✓ test_table_2 (0 registos, 0 KB)
        ✓ user_activity_logs (200K registos, 25 MB)
        ✓ legacy_comments (100 registos, 50 KB)
        
❌ [  ] Obter aprovação de PM/Manager
        Por: _________________
        Data: _________________
        
❌ [  ] Planear downtime (se necessário)
        Data sugerida: _________________
        Duração: _________________
        
❌ [  ] Comunicar ao team
        Data de comunicação: _________________
        Canal: _________________
```

---

## 🗑️ FASE 3: ELIMINAÇÃO (STATUS: ⏸️ AGUARDANDO APROVAÇÃO)

```
Será ativada após FASE 2 estar aprovada

### Grupo A: Tabelas Vazias (ZERO RISCO)

❌ [  ] Eliminar test_table_1
        ✓ Backup: CONCLUÍDO
        ✓ Aprovação: NÃO NECESSÁRIA
        ✓ Referências: 0
        Comando: DROP TABLE IF EXISTS test_table_1;
        Data/Hora: _________________
        Status: ⏳ PENDENTE

❌ [  ] Testar plataforma após eliminação
        URL: http://localhost/aksanti/
        Status: ⏳ PENDENTE
        Problemas: _________________

❌ [  ] Eliminar test_table_2
        ✓ Backup: CONCLUÍDO
        ✓ Aprovação: NÃO NECESSÁRIA
        ✓ Referências: 0
        Comando: DROP TABLE IF EXISTS test_table_2;
        Data/Hora: _________________
        Status: ⏳ PENDENTE

❌ [  ] Testar plataforma após eliminação
        URL: http://localhost/aksanti/
        Status: ⏳ PENDENTE
        Problemas: _________________

### Grupo B: Tabelas Órfãs Pequenas

❌ [  ] Eliminar legacy_comments (50 KB)
        ✓ Backup: CONCLUÍDO
        ✓ Aprovação: SIM (data: ___)
        ✓ Referências: 0
        Comando: DROP TABLE IF EXISTS legacy_comments CASCADE;
        Data/Hora: _________________
        Status: ⏳ PENDENTE

❌ [  ] Testar plataforma após eliminação
        URL: http://localhost/aksanti/
        Status: ⏳ PENDENTE
        Problemas: _________________

### Grupo C: Tabelas Órfãs Grandes

❌ [  ] Arquivar user_activity_logs (25 MB)
        Comando: CREATE TABLE user_activity_logs_archive AS SELECT * FROM user_activity_logs;
        Data/Hora: _________________
        Status: ⏳ PENDENTE

❌ [  ] Eliminar user_activity_logs
        ✓ Backup: CONCLUÍDO
        ✓ Aprovação: SIM (data: ___)
        ✓ Arquivo: CRIADO
        ✓ Referências: 0
        Comando: DROP TABLE IF EXISTS user_activity_logs CASCADE;
        Data/Hora: _________________
        Status: ⏳ PENDENTE

❌ [  ] Testar plataforma após eliminação
        URL: http://localhost/aksanti/
        Status: ⏳ PENDENTE
        Problemas: _________________
```

---

## 📝 FASE 4: DOCUMENTAÇÃO (STATUS: ⏸️ AGUARDANDO ELIMINAÇÕES)

```
Será ativada após FASE 3 estar completa

❌ [  ] Registar tabelas eliminadas
        Tabelas: _________________
        Data: _________________
        Razão: _________________
        
❌ [  ] Documentar espaço libertado
        Antes: ~50 MB total
        Depois: ___ MB total
        Libertado: ___ MB
        
❌ [  ] Atualizar diagrama de schema
        Ficheiro: DIAGRAMA_SCHEMA.md
        Status: _________________
        
❌ [  ] Comunicar conclusão ao team
        Data: _________________
        Método: _________________
        
❌ [  ] Arquivo: Backup guardado
        Local principal: _________________
        Local secundário: _________________
        Local terciário: _________________
```

---

## 📊 RESUMO DE PROGRESSO

```
FASE 1 - BACKUP                 [████░░░░░░░░░░░░] 25% ⏳
FASE 2 - ANÁLISE FINAL          [░░░░░░░░░░░░░░░░░] 0%  ⏸️
FASE 3 - ELIMINAÇÃO             [░░░░░░░░░░░░░░░░░] 0%  ⏸️
FASE 4 - DOCUMENTAÇÃO           [░░░░░░░░░░░░░░░░░] 0%  ⏸️

Progresso Geral:                [████░░░░░░░░░░░░] 25%
```

---

## ⏭️ PRÓXIMAS AÇÕES IMEDIATAS

### 🎯 AGORA (HOJE)
1. ✅ Leia `GUIA_RAPIDO_BACKUP.md`
2. ✅ Execute `EXECUTAR_BACKUP.ps1`
3. ✅ Confirme ficheiro foi criado
4. ✅ Guarde em local seguro

### 🎯 AMANHÃ/PRÓXIMOS DIAS
1. Revisar `PLANO_BACKUP_E_LIMPEZA.md`
2. Testar restauração de backup (opcional)
3. Comunicar ao PM/Manager para aprovação
4. Planear data/hora de eliminação

### 🎯 APÓS APROVAÇÃO
1. Executar eliminações em ordem
2. Testar plataforma após cada eliminação
3. Documentar processo
4. Comunicar conclusão

---

## 🆘 PROBLEMAS ENCONTRADOS

```
Problema 1: ___________________
Solução: ___________________
Resolvido: ☐ Sim ☐ Não
Data: _________________

Problema 2: ___________________
Solução: ___________________
Resolvido: ☐ Sim ☐ Não
Data: _________________

Problema 3: ___________________
Solução: ___________________
Resolvido: ☐ Sim ☐ Não
Data: _________________
```

---

## 📞 CONTACTOS

| Função | Nome | Email | Telefone |
|--------|------|-------|----------|
| PM/Manager | __________ | __________ | __________ |
| Tech Lead | __________ | __________ | __________ |
| DBA | __________ | __________ | __________ |
| Suporte | __________ | __________ | __________ |

---

## 📎 DOCUMENTOS DE REFERÊNCIA

- [x] `GUIA_RAPIDO_BACKUP.md` - Como executar o backup
- [x] `PLANO_BACKUP_E_LIMPEZA.md` - Plano completo
- [x] `EXECUTAR_BACKUP.ps1` - Script automatizado
- [x] `ANALISE_TABELAS_GUIA_PASSO_A_PASSO.md` - Análise técnica
- [x] `argumentos/README_ANALISE_TABELAS.md` - Referência técnica

---

**Data de Criação:** 1 de junho de 2026  
**Última Atualização:** 1 de junho de 2026  
**Próxima Revisão:** [APÓS BACKUP]  
**Status Geral:** 🟡 EM PROGRESSO - AGUARDANDO EXECUÇÃO BACKUP
