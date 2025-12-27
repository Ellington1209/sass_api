# 📋 API MÓDULO FINANCEIRO - Documentação para Frontend

## Base URL
Todas as rotas começam com: `/api/financial`

---

## 📊 DASHBOARD E RELATÓRIOS

### GET `/api/financial/reports/dashboard`
**O que faz:** Retorna dados do dashboard financeiro com resumo de entradas, saídas, saldo e comissões.

**Query Parameters (opcionais):**
- `start_date` (string, formato: YYYY-MM-DD HH:mm:ss) - Data inicial do período
- `end_date` (string, formato: YYYY-MM-DD HH:mm:ss) - Data final do período

**Resposta:** Objeto com:
- `period` - Período consultado
- `summary` - Resumo (income, expense, balance, commissions_pending, commissions_paid)
- `income_by_origin` - Entradas agrupadas por origem
- `expense_by_category` - Despesas agrupadas por categoria

---

### GET `/api/financial/reports/cash-flow`
**O que faz:** Retorna fluxo de caixa diário de um mês específico.

**Query Parameters (obrigatórios):**
- `year` (integer) - Ano (ex: 2025)
- `month` (integer) - Mês (1-12)

**Resposta:** Objeto com:
- `year` - Ano consultado
- `month` - Mês consultado
- `cash_flow` - Array com dados diários (date, income, expense, balance, accumulated_balance)

---

### GET `/api/financial/reports/commissions`
**O que faz:** Retorna relatório de comissões por profissional.

**Query Parameters (opcionais):**
- `provider_id` (integer) - Filtrar por profissional específico
- `status` (string) - Filtrar por status (PENDING, PAID, CANCELLED)
- `start_date` (string) - Data inicial
- `end_date` (string) - Data final

**Resposta:** Array com dados por profissional (provider_id, provider_name, pending, paid, cancelled, total_quantity, total_amount)

---

## 💰 TRANSAÇÕES

### GET `/api/financial/transactions`
**O que faz:** Lista todas as transações financeiras.

**Query Parameters (opcionais):**
- `type` (string) - Filtrar por tipo: `IN` ou `OUT`
- `status` (string) - Filtrar por status: `PENDING`, `CONFIRMED`, `CANCELLED`
- `origin_id` (integer) - Filtrar por origem
- `category_id` (integer) - Filtrar por categoria
- `payment_method_id` (integer) - Filtrar por método de pagamento
- `start_date` (string) - Data inicial
- `end_date` (string) - Data final

**Resposta:** Array de transações

---

### GET `/api/financial/transactions/{id}`
**O que faz:** Busca uma transação específica por ID.

**Parâmetros na URL:**
- `id` (integer) - ID da transação

**Resposta:** Objeto da transação com todos os detalhes e comissões relacionadas

---

### POST `/api/financial/transactions`
**O que faz:** Cria uma nova transação financeira.

**Body (JSON):**
```json
{
  "type": "IN",                    // obrigatório: "IN" ou "OUT"
  "amount": 150.00,               // obrigatório: número decimal (mínimo 0.01)
  "description": "Descrição...",   // opcional: string (máx 1000 caracteres)
  "origin_id": 1,                  // obrigatório: ID da origem financeira
  "category_id": 2,                // obrigatório: ID da categoria
  "payment_method_id": 3,          // obrigatório: ID do método de pagamento
  "reference_type": "appointment", // opcional: tipo da referência
  "reference_id": 10,              // opcional: ID da referência
  "service_price_id": 5,           // opcional: ID do preço do serviço
  "status": "CONFIRMED",           // opcional: "PENDING", "CONFIRMED", "CANCELLED"
  "occurred_at": "2025-12-27 14:30:00" // opcional: data/hora (padrão: agora)
}
```

**Resposta:** Objeto da transação criada (status 201)

---

### PUT `/api/financial/transactions/{id}` ou PATCH `/api/financial/transactions/{id}`
**O que faz:** Atualiza uma transação existente.

**Parâmetros na URL:**
- `id` (integer) - ID da transação

**Body (JSON) - todos os campos são opcionais:**
```json
{
  "amount": 200.00,
  "description": "Nova descrição",
  "origin_id": 2,
  "category_id": 3,
  "payment_method_id": 1,
  "status": "CONFIRMED",
  "occurred_at": "2025-12-27 15:00:00"
}
```

**Resposta:** Objeto da transação atualizada

---

### POST `/api/financial/transactions/{id}/cancel`
**O que faz:** Cancela uma transação (muda status para CANCELLED e cancela comissões pendentes relacionadas).

**Parâmetros na URL:**
- `id` (integer) - ID da transação

**Body:** Não precisa enviar body

**Resposta:** Objeto da transação cancelada

---

### DELETE `/api/financial/transactions/{id}`
**O que faz:** Deleta uma transação (soft delete).

**Parâmetros na URL:**
- `id` (integer) - ID da transação

**Resposta:** Mensagem de sucesso

---

## 💵 COMISSÕES

### GET `/api/financial/commissions`
**O que faz:** Lista todas as comissões.

**Query Parameters (opcionais):**
- `provider_id` (integer) - Filtrar por profissional
- `status` (string) - Filtrar por status: `PENDING`, `PAID`, `CANCELLED`
- `origin_id` (integer) - Filtrar por origem

**Resposta:** Array de comissões

---

### GET `/api/financial/commissions/{id}`
**O que faz:** Busca uma comissão específica por ID.

**Parâmetros na URL:**
- `id` (integer) - ID da comissão

**Resposta:** Objeto da comissão com todos os detalhes

---

### POST `/api/financial/commissions/{id}/pay`
**O que faz:** Marca uma comissão como paga e cria uma transação de saída para o pagamento.

**Parâmetros na URL:**
- `id` (integer) - ID da comissão

**Body (JSON):**
```json
{
  "origin_id": 8,                  // obrigatório: ID da origem (ex: "Pagamento de Comissão")
  "category_id": 4,                // obrigatório: ID da categoria (tipo OUT)
  "payment_method_id": 1,          // obrigatório: ID do método de pagamento
  "occurred_at": "2025-12-27 15:00:00" // opcional: data/hora do pagamento
}
```

**Resposta:** Objeto da comissão atualizada com status PAID

---

### POST `/api/financial/commissions/{id}/cancel`
**O que faz:** Cancela uma comissão (apenas se ainda estiver pendente).

**Parâmetros na URL:**
- `id` (integer) - ID da comissão

**Body:** Não precisa enviar body

**Resposta:** Objeto da comissão cancelada

---

### GET `/api/financial/commissions/totals/by-provider`
**O que faz:** Retorna totais de comissões agrupados por profissional.

**Query Parameters (opcionais):**
- `status` (string) - Filtrar por status
- `start_date` (string) - Data inicial
- `end_date` (string) - Data final

**Resposta:** Array com totais por profissional (provider_id, provider_name, pending, paid, cancelled, total)

---

## ⚙️ CONFIGURAÇÕES - ORIGENS FINANCEIRAS

### GET `/api/financial/origins`
**O que faz:** Lista todas as origens financeiras configuradas.

**Query Parameters (opcionais):**
- `active` (boolean) - Filtrar apenas ativas/inativas
- `origin_type` (string) - Filtrar por tipo: `OPERATIONAL` ou `MANUAL`

**Resposta:** Array de origens

---

### POST `/api/financial/origins`
**O que faz:** Cria uma nova origem financeira.

**Body (JSON):**
```json
{
  "name": "Aula Prática",          // obrigatório: nome da origem
  "origin_type": "OPERATIONAL",     // obrigatório: "OPERATIONAL" ou "MANUAL"
  "active": true                    // opcional: boolean (padrão: true)
}
```

**Resposta:** Objeto da origem criada (status 201)

---

### PUT `/api/financial/origins/{id}` ou PATCH `/api/financial/origins/{id}`
**O que faz:** Atualiza uma origem financeira.

**Parâmetros na URL:**
- `id` (integer) - ID da origem

**Body (JSON) - todos os campos são opcionais:**
```json
{
  "name": "Novo Nome",
  "origin_type": "MANUAL",
  "active": false
}
```

**Resposta:** Objeto da origem atualizada

---

### DELETE `/api/financial/origins/{id}`
**O que faz:** Deleta uma origem financeira.

**Parâmetros na URL:**
- `id` (integer) - ID da origem

**Resposta:** Mensagem de sucesso

---

## ⚙️ CONFIGURAÇÕES - CATEGORIAS

### GET `/api/financial/categories`
**O que faz:** Lista todas as categorias financeiras.

**Query Parameters (opcionais):**
- `active` (boolean) - Filtrar apenas ativas/inativas
- `type` (string) - Filtrar por tipo: `IN` ou `OUT`

**Resposta:** Array de categorias

---

### POST `/api/financial/categories`
**O que faz:** Cria uma nova categoria financeira.

**Body (JSON):**
```json
{
  "name": "Serviços",               // obrigatório: nome da categoria
  "type": "IN",                     // obrigatório: "IN" ou "OUT"
  "active": true                    // opcional: boolean (padrão: true)
}
```

**Resposta:** Objeto da categoria criada (status 201)

---

### PUT `/api/financial/categories/{id}` ou PATCH `/api/financial/categories/{id}`
**O que faz:** Atualiza uma categoria financeira.

**Parâmetros na URL:**
- `id` (integer) - ID da categoria

**Body (JSON) - todos os campos são opcionais:**
```json
{
  "name": "Novo Nome",
  "type": "OUT",
  "active": false
}
```

**Resposta:** Objeto da categoria atualizada

---

### DELETE `/api/financial/categories/{id}`
**O que faz:** Deleta uma categoria financeira.

**Parâmetros na URL:**
- `id` (integer) - ID da categoria

**Resposta:** Mensagem de sucesso

---

## ⚙️ CONFIGURAÇÕES - MÉTODOS DE PAGAMENTO

### GET `/api/financial/payment-methods`
**O que faz:** Lista todos os métodos de pagamento.

**Query Parameters (opcionais):**
- `active` (boolean) - Filtrar apenas ativos/inativos

**Resposta:** Array de métodos de pagamento

---

### POST `/api/financial/payment-methods`
**O que faz:** Cria um novo método de pagamento.

**Body (JSON):**
```json
{
  "name": "PIX",                    // obrigatório: nome do método
  "active": true                    // opcional: boolean (padrão: true)
}
```

**Resposta:** Objeto do método criado (status 201)

---

### PUT `/api/financial/payment-methods/{id}` ou PATCH `/api/financial/payment-methods/{id}`
**O que faz:** Atualiza um método de pagamento.

**Parâmetros na URL:**
- `id` (integer) - ID do método

**Body (JSON) - todos os campos são opcionais:**
```json
{
  "name": "Pix",
  "active": false
}
```

**Resposta:** Objeto do método atualizado

---

### DELETE `/api/financial/payment-methods/{id}`
**O que faz:** Deleta um método de pagamento.

**Parâmetros na URL:**
- `id` (integer) - ID do método

**Resposta:** Mensagem de sucesso

---

## ⚙️ CONFIGURAÇÕES - COMISSÕES

### GET `/api/financial/commission-configs`
**O que faz:** Lista todas as configurações de comissão por profissional.

**Query Parameters (opcionais):**
- `provider_id` (integer) - Filtrar por profissional específico
- `service_id` (integer) - Filtrar por serviço específico
- `active` (boolean) - Filtrar apenas ativas/inativas

**Resposta:** Array de configurações de comissão

---

### POST `/api/financial/commission-configs`
**O que faz:** Cria uma nova configuração de comissão para um profissional.

**Body (JSON):**
```json
{
  "provider_id": 10,                // obrigatório: ID do profissional
  "service_id": 5,                  // opcional: ID do serviço (NULL = aplica para todos)
  "origin_id": 2,                   // opcional: ID da origem (NULL = aplica para todas)
  "commission_rate": 40.00,         // obrigatório: taxa de comissão em % (0-100)
  "active": true                    // opcional: boolean (padrão: true)
}
```

**Hierarquia de Prioridade:**
1. **Mais específica**: `provider_id` + `service_id` + `origin_id` (ex: João + Corte + Atendimento = 40%)
2. **Média**: `provider_id` + `service_id` (ex: João + Corte = 35%)
3. **Média**: `provider_id` + `origin_id` (ex: João + Atendimento = 30%)
4. **Padrão**: Apenas `provider_id` (ex: João = 25%)

O sistema sempre usa a configuração mais específica disponível.

**Resposta:** Objeto da configuração criada (status 201)

---

### PUT `/api/financial/commission-configs/{id}` ou PATCH `/api/financial/commission-configs/{id}`
**O que faz:** Atualiza uma configuração de comissão.

**Parâmetros na URL:**
- `id` (integer) - ID da configuração

**Body (JSON) - todos os campos são opcionais:**
```json
{
  "commission_rate": 45.00,
  "active": false
}
```

**Resposta:** Objeto da configuração atualizada

---

### DELETE `/api/financial/commission-configs/{id}`
**O que faz:** Deleta uma configuração de comissão.

**Parâmetros na URL:**
- `id` (integer) - ID da configuração

**Resposta:** Mensagem de sucesso

---

## 📝 OBSERVAÇÕES IMPORTANTES

### Status de Transações
- `PENDING` - Pendente
- `CONFIRMED` - Confirmada
- `CANCELLED` - Cancelada

### Status de Comissões
- `PENDING` - Pendente
- `PAID` - Paga
- `CANCELLED` - Cancelada

### Tipos de Transação
- `IN` - Entrada (receita)
- `OUT` - Saída (despesa)

### Tipos de Origem
- `OPERATIONAL` - Gerada automaticamente pelo sistema
- `MANUAL` - Lançada manualmente

### Tipos de Categoria
- `IN` - Para entradas
- `OUT` - Para saídas

### Formato de Datas
- Use formato: `YYYY-MM-DD HH:mm:ss` (ex: `2025-12-27 14:30:00`)
- Ou apenas data: `YYYY-MM-DD` (ex: `2025-12-27`)

---

**Última atualização:** 27/12/2025

