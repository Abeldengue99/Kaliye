# Plano de Implementação: Fluxo de Investimento em 2 Etapas

## Objetivo
Reorganizar o fluxo de investimento para:
1. Gerar referência Multicaixa PRIMEIRO
2. Depois do pagamento → Enviar comprovativo
3. Projetos já investidos (aprovados) aparecem em seção separada

## Mudanças Necessárias

### 1. Modal de Investimento (projects.php)
**Localização**: Adicionar antes do fechamento do body

**Estrutura**:
- Step 1: Formulário com valor + moeda → Gera referência Multicaixa
- Step 2: Mostrar dados de pagamento + Upload de comprovativo

**Fluxo**:
```
Usuário clica "Investir" 
→ Modal abre no Step 1
→ Preenche valor e moeda
→ Clica "Gerar Referência"
→ API cria investment com status='awaiting_payment'
→ Retorna referência Multicaixa
→ Modal avança para Step 2
→ Mostra dados de pagamento
→ Usuário faz pagamento
→ Upload comprovativo
→ Status muda para 'pending' (aguardando aprovação admin)
```

### 2. Nova API: generate_investment_reference.php
**Função**: Criar registro de investimento e gerar referência

**Campos**:
- project_id
- investor_id
- amount
- currency
- terms (opcional)
- status = 'awaiting_payment'
- payment_reference (gerado)

**Retorno**:
```json
{
  "success": true,
  "investment_id": 123,
  "reference": "728039094",
  "entity": "00001",
  "amount": "50000.00",
  "currency": "AOA"
}
```

### 3. Nova API: upload_investment_proof.php
**Função**: Fazer upload do comprovativo e atualizar status

**Campos**:
- investment_id
- proof_doc (file)

**Ação**:
- Upload do arquivo
- Atualizar `proof_document_path`
- Mudar status de 'awaiting_payment' para 'pending'
- Notificar admin

### 4. Filtrar Projetos Já Investidos
**Localização**: projects.php query

**Lógica**:
```sql
-- Adicionar ao WHERE clause se usuário for investidor
AND p.project_id NOT IN (
  SELECT project_id 
  FROM project_investments 
  WHERE investor_id = ? 
  AND status IN ('approved', 'paid')
)
```

### 5. Nova Seção: Meus Projetos Investidos
**Localização**: Nova página ou aba em investor_dashboard.php

**Query**:
```sql
SELECT p.*, pi.amount, pi.currency, pi.status, pi.created_at as investment_date
FROM projects p
JOIN project_investments pi ON p.project_id = pi.project_id
WHERE pi.investor_id = ?
AND pi.status IN ('approved', 'paid')
ORDER BY pi.created_at DESC
```

## Ordem de Implementação
1. ✅ Criar generate_investment_reference.php
2. ✅ Criar upload_investment_proof.php  
3. ✅ Atualizar modal em projects.php (2 steps)
4. ✅ Adicionar filtro para esconder projetos já investidos
5. ✅ Criar seção "Meus Projetos" no investor dashboard

## Notas
- Manter backward compatibility com investimentos antigos
- Adicionar coluna `payment_reference` na tabela `project_investments` se não existir
- Status possíveis: 'awaiting_payment', 'pending', 'approved', 'paid', 'rejected'
