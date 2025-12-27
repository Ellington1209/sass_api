# 📊 Módulo Financeiro - Documentação Completa

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Relatórios e Dashboard](#relatórios-e-dashboard)
3. [Transações Financeiras](#transações-financeiras)
4. [Comissões](#comissões)
5. [Configurações](#configurações)
   - [Categorias Financeiras](#categorias-financeiras)
   - [Métodos de Pagamento](#métodos-de-pagamento)
   - [Configurações de Comissão](#configurações-de-comissão)

---

## 🎯 Visão Geral

O módulo financeiro é um sistema centralizado e flexível para gerenciamento financeiro em um SaaS multi-tenant. Todas as transações financeiras (entradas e saídas) passam por uma única tabela de transações, garantindo rastreabilidade e auditoria completa.

### Princípios do Módulo

- **Centralização**: Todo dinheiro que entra ou sai passa por `financial_transactions`
- **Imutabilidade**: Transações não podem ser editadas ou deletadas. Erros são corrigidos criando transações compensatórias
- **Multi-tenant**: Todas as operações são isoladas por `tenant_id`
- **Auditoria**: Todas as transações registram quem criou (`created_by`) e quando ocorreu (`occurred_at`)

### Permissões Necessárias

Todas as rotas requerem autenticação (`auth:sanctum`) e as seguintes permissões:

- `financeiro.view` - Visualizar dashboard
- `financeiro.reports.view` - Visualizar relatórios
- `financeiro.transactions.view` - Visualizar transações
- `financeiro.transactions.create` - Criar transações
- `financeiro.commissions.view` - Visualizar comissões
- `financeiro.commissions.pay` - Pagar comissões
- `financeiro.commissions.cancel` - Cancelar comissões
- `financeiro.categories.view` - Visualizar categorias
- `financeiro.categories.create` - Criar categorias
- `financeiro.categories.edit` - Editar categorias
- `financeiro.categories.delete` - Deletar categorias
- `financeiro.payment_methods.view` - Visualizar métodos de pagamento
- `financeiro.payment_methods.create` - Criar métodos de pagamento
- `financeiro.payment_methods.edit` - Editar métodos de pagamento
- `financeiro.payment_methods.delete` - Deletar métodos de pagamento
- `financeiro.commission_configs.view` - Visualizar configurações de comissão
- `financeiro.commission_configs.create` - Criar configurações de comissão
- `financeiro.commission_configs.edit` - Editar configurações de comissão
- `financeiro.commission_configs.delete` - Deletar configurações de comissão

---

## 📈 Relatórios e Dashboard

### GET `/api/financial/reports/dashboard`

**O que faz:** Retorna dados consolidados do dashboard financeiro com totais de entradas, saídas, lucro e outras métricas.

**Permissão:** `financeiro.view`

**Query Parameters:**
- `start_date` (opcional) - Data inicial no formato `Y-m-d` (ex: `2025-01-01`)
- `end_date` (opcional) - Data final no formato `Y-m-d` (ex: `2025-12-31`)

**Exemplo de requisição:**
```
GET /api/financial/reports/dashboard?start_date=2025-01-01&end_date=2025-12-31
```

**Exemplo de resposta:**
```json
{
  "period": {
    "start": "2025-01-01 00:00:00",
    "end": "2025-12-31 23:59:59"
  },
  "summary": {
    "income": 50000.00,
    "expense": 30000.00,
    "balance": 20000.00,
    "commissions_pending": 5000.00,
    "commissions_paid": 15000.00
  },
  "income_by_category": [
    {
      "category": "Serviços",
      "total": 45000.00
    }
  ],
  "expense_by_category": [
    {
      "category": "Despesas Fixas",
      "total": 25000.00
    }
  ]
}
```

---

### GET `/api/financial/reports/cash-flow`

**O que faz:** Retorna o fluxo de caixa detalhado por dia de um mês específico.

**Permissão:** `financeiro.reports.view`

**Query Parameters:**
- `year` (obrigatório) - Ano (ex: `2025`)
- `month` (obrigatório) - Mês (1-12)

**Exemplo de requisição:**
```
GET /api/financial/reports/cash-flow?year=2025&month=12
```

**Exemplo de resposta:**
```json
{
  "year": 2025,
  "month": 12,
  "cash_flow": [
    {
      "date": "2025-12-01",
      "income": 1500.00,
      "expense": 800.00,
      "balance": 700.00,
      "accumulated_balance": 700.00
    },
    {
      "date": "2025-12-02",
      "income": 2000.00,
      "expense": 500.00,
      "balance": 1500.00,
      "accumulated_balance": 2200.00
    }
  ]
}
```

---

### GET `/api/financial/reports/commissions`

**O que faz:** Retorna relatório detalhado de comissões com filtros opcionais.

**Permissão:** `financeiro.reports.view`

**Query Parameters:**
- `provider_id` (opcional) - ID do profissional
- `status` (opcional) - Status da comissão: `PENDING`, `PAID`, `CANCELLED`
- `category_id` (opcional) - ID da categoria (filtra pela categoria da transação relacionada)
- `start_date` (opcional) - Data inicial no formato `Y-m-d`
- `end_date` (opcional) - Data final no formato `Y-m-d`

**Exemplo de requisição:**
```
GET /api/financial/reports/commissions?provider_id=1&status=PENDING&start_date=2025-01-01&end_date=2025-12-31
```

**Exemplo de resposta:**
```json
[
  {
    "provider_id": 1,
    "provider_name": "João Silva",
    "pending": {
      "quantity": 5,
      "total": 2000.00
    },
    "paid": {
      "quantity": 10,
      "total": 5000.00
    },
    "cancelled": {
      "quantity": 0,
      "total": 0
    },
    "total_quantity": 15,
    "total_amount": 7000.00
  }
]
```

---

## 💰 Transações Financeiras

### GET `/api/financial/transactions`

**O que faz:** Lista todas as transações financeiras com filtros opcionais.

**Permissão:** `financeiro.transactions.view`

**Query Parameters:**
- `type` (opcional) - Tipo: `IN` ou `OUT`
- `status` (opcional) - Status: `PENDING`, `CONFIRMED`, `CANCELLED`
- `category_id` (opcional) - ID da categoria
- `payment_method_id` (opcional) - ID do método de pagamento
- `start_date` (opcional) - Data inicial no formato `Y-m-d`
- `end_date` (opcional) - Data final no formato `Y-m-d`

**Exemplo de requisição:**
```
GET /api/financial/transactions?type=IN&status=CONFIRMED&start_date=2025-01-01&end_date=2025-12-31
```

**Exemplo de resposta:**
```json
[
  {
    "id": 1,
    "type": "IN",
    "amount": 150.00,
    "description": "Pagamento de aula prática",
    "category": {
      "id": 1,
      "name": "Serviços",
      "is_operational": true
    },
    "payment_method": {
      "id": 1,
      "name": "Pix"
    },
    "status": "CONFIRMED",
    "occurred_at": "2025-12-01T10:00:00.000000Z",
    "created_by": {
      "id": 1,
      "name": "Admin"
    },
    "reference_type": "Appointment",
    "reference_id": 123,
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-01T10:00:00.000000Z"
  }
]
```

---

### GET `/api/financial/transactions/{id}`

**O que faz:** Busca uma transação específica por ID.

**Permissão:** `financeiro.transactions.view`

**Exemplo de requisição:**
```
GET /api/financial/transactions/1
```

**Exemplo de resposta:**
```json
{
  "id": 1,
  "type": "IN",
  "amount": 150.00,
  "description": "Pagamento de aula prática",
  "category": {
    "id": 1,
    "name": "Serviços",
    "is_operational": true
  },
  "payment_method": {
    "id": 1,
    "name": "Pix"
  },
  "status": "CONFIRMED",
  "occurred_at": "2025-12-01T10:00:00.000000Z",
  "created_by": {
    "id": 1,
    "name": "Admin"
  },
  "reference_type": "Appointment",
  "reference_id": 123,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

### POST `/api/financial/transactions`

**O que faz:** Cria uma nova transação financeira manual.

**Permissão:** `financeiro.transactions.create`

**Body (JSON):**
```json
{
  "type": "IN",                              // obrigatório: "IN" ou "OUT"
  "amount": 150.00,                          // obrigatório: valor numérico, mínimo 0.01
  "description": "Pagamento de aula prática", // opcional: string, máximo 1000 caracteres
  "category_id": 1,                          // obrigatório: ID da categoria (deve existir)
  "payment_method_id": 1,                    // obrigatório: ID do método de pagamento (deve existir)
  "reference_type": "Appointment",           // opcional: string, máximo 50 caracteres
  "reference_id": 123,                       // opcional: integer
  "status": "CONFIRMED",                     // opcional: "PENDING", "CONFIRMED", "CANCELLED" (padrão: "PENDING")
  "occurred_at": "2025-12-01 10:00:00"       // opcional: data/hora (padrão: agora)
}
```

**Validações:**
- `type` - obrigatório, deve ser `IN` ou `OUT`
- `amount` - obrigatório, numérico, mínimo 0.01
- `description` - opcional, string, máximo 1000 caracteres
- `category_id` - obrigatório, deve existir em `financial_categories`
- `payment_method_id` - obrigatório, deve existir em `payment_methods`
- `reference_type` - opcional, string, máximo 50 caracteres
- `reference_id` - opcional, integer
- `status` - opcional, deve ser `PENDING`, `CONFIRMED` ou `CANCELLED`
- `occurred_at` - opcional, formato de data válido

**Exemplo de requisição:**
```json
POST /api/financial/transactions
{
  "type": "IN",
  "amount": 150.00,
  "description": "Pagamento de aula prática",
  "category_id": 1,
  "payment_method_id": 1,
  "status": "CONFIRMED",
  "occurred_at": "2025-12-01 10:00:00"
}
```

**Exemplo de resposta (201 Created):**
```json
{
  "id": 1,
  "type": "IN",
  "amount": 150.00,
  "description": "Pagamento de aula prática",
  "category": {
    "id": 1,
    "name": "Serviços",
    "is_operational": true
  },
  "payment_method": {
    "id": 1,
    "name": "Pix"
  },
  "status": "CONFIRMED",
  "occurred_at": "2025-12-01T10:00:00.000000Z",
  "created_by": {
    "id": 1,
    "name": "Admin"
  },
  "reference_type": null,
  "reference_id": null,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

**⚠️ Importante:** Transações não podem ser editadas ou deletadas. Se houver erro, crie uma transação compensatória:
- Se criou uma entrada errada, crie uma saída corrigindo o erro
- Se criou uma saída errada, crie uma entrada corrigindo o erro
- Depois, crie a transação correta

---

## 💵 Comissões

### GET `/api/financial/commissions`

**O que faz:** Lista todas as comissões com filtros opcionais.

**Permissão:** `financeiro.commissions.view`

**Query Parameters:**
- `provider_id` (opcional) - ID do profissional
- `status` (opcional) - Status: `PENDING`, `PAID`, `CANCELLED`

**Exemplo de requisição:**
```
GET /api/financial/commissions?provider_id=1&status=PENDING
```

**Exemplo de resposta:**
```json
[
  {
    "id": 1,
    "provider": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "transaction_id": 1,
    "category": {
      "id": 1,
      "name": "Serviços",
      "is_operational": true
    },
    "reference_type": "Appointment",
    "reference_id": 123,
    "base_amount": 1000.00,
    "commission_amount": 100.00,
    "status": "PENDING",
    "paid_at": null,
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-01T10:00:00.000000Z"
  }
]
```

---

### GET `/api/financial/commissions/{id}`

**O que faz:** Busca uma comissão específica por ID.

**Permissão:** `financeiro.commissions.view`

**Exemplo de requisição:**
```
GET /api/financial/commissions/1
```

**Exemplo de resposta:**
```json
{
  "id": 1,
  "provider": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "transaction_id": 1,
  "category": {
    "id": 1,
    "name": "Serviços",
    "is_operational": true
  },
  "reference_type": "Appointment",
  "reference_id": 123,
  "base_amount": 1000.00,
  "commission_amount": 100.00,
  "status": "PENDING",
  "paid_at": null,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

### POST `/api/financial/commissions/{id}/pay`

**O que faz:** Marca uma comissão como paga e cria uma transação de saída para o pagamento.

**Permissão:** `financeiro.commissions.pay`

**Body (JSON):**
```json
{
  "category_id": 2,             // obrigatório: ID da categoria (deve existir)
  "payment_method_id": 1,      // obrigatório: ID do método de pagamento (deve existir)
  "occurred_at": "2025-12-01 10:00:00"  // opcional: data/hora (padrão: agora)
}
```

**Validações:**
- `category_id` - obrigatório, deve existir em `financial_categories`
- `payment_method_id` - obrigatório, deve existir em `payment_methods`
- `occurred_at` - opcional, formato de data válido

**Exemplo de requisição:**
```json
POST /api/financial/commissions/1/pay
{
  "category_id": 2,
  "payment_method_id": 1,
  "occurred_at": "2025-12-01 10:00:00"
}
```

**Exemplo de resposta:**
```json
{
  "id": 1,
  "provider": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "transaction_id": 1,
  "category": {
    "id": 1,
    "name": "Serviços",
    "is_operational": true
  },
  "base_amount": 1000.00,
  "commission_amount": 100.00,
  "status": "PAID",
  "paid_at": "2025-12-01T10:00:00.000000Z",
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

### POST `/api/financial/commissions/{id}/cancel`

**O que faz:** Cancela uma comissão (apenas se ainda estiver pendente).

**Permissão:** `financeiro.commissions.cancel`

**Exemplo de requisição:**
```
POST /api/financial/commissions/1/cancel
```

**Exemplo de resposta:**
```json
{
  "id": 1,
  "provider": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "transaction_id": 1,
  "category": {
    "id": 1,
    "name": "Serviços",
    "is_operational": true
  },
  "base_amount": 1000.00,
  "commission_amount": 100.00,
  "status": "CANCELLED",
  "paid_at": null,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

### GET `/api/financial/commissions/totals/by-provider`

**O que faz:** Retorna totais de comissões agrupados por profissional.

**Permissão:** `financeiro.commissions.view`

**Query Parameters:**
- `status` (opcional) - Status: `PENDING`, `PAID`, `CANCELLED`
- `start_date` (opcional) - Data inicial no formato `Y-m-d`
- `end_date` (opcional) - Data final no formato `Y-m-d`

**Exemplo de requisição:**
```
GET /api/financial/commissions/totals/by-provider?status=PENDING&start_date=2025-01-01&end_date=2025-12-31
```

**Exemplo de resposta:**
```json
[
  {
    "provider_id": 1,
    "provider_name": "João Silva",
    "provider_email": "joao@example.com",
    "pending": 2000.00,
    "paid": 5000.00,
    "cancelled": 0.00,
    "total": 7000.00,
    "commissions_count": 15
  },
  {
    "provider_id": 2,
    "provider_name": "Maria Santos",
    "provider_email": "maria@example.com",
    "pending": 1500.00,
    "paid": 3000.00,
    "cancelled": 100.00,
    "total": 4600.00,
    "commissions_count": 10
  }
]
```

---

## ⚙️ Configurações

### Categorias Financeiras

Categorias são agrupamentos contábeis para relatórios. Elas definem o motivo do lançamento financeiro e podem ser:
- **Operacionais** (`is_operational: true`): Geradas automaticamente pelo sistema (aulas, serviços, atendimentos)
- **Manuais** (`is_operational: false`): Lançadas manualmente pelo tenant (despesas ou entradas avulsas)

O tipo da transação (IN ou OUT) é definido exclusivamente no campo `type` da transação, não na categoria.

#### GET `/api/financial/categories`

**O que faz:** Lista todas as categorias financeiras.

**Permissão:** `financeiro.categories.view`

**Query Parameters:**
- `active` (opcional) - Boolean: `true` ou `false`

**Exemplo de requisição:**
```
GET /api/financial/categories?active=true
```

**Exemplo de resposta:**
```json
[
  {
    "id": 1,
    "name": "Serviços",
    "is_operational": true,
    "active": true,
    "created_at": "2025-01-01T10:00:00.000000Z",
    "updated_at": "2025-01-01T10:00:00.000000Z"
  },
  {
    "id": 2,
    "name": "Despesas Fixas",
    "is_operational": false,
    "active": true,
    "created_at": "2025-01-01T10:00:00.000000Z",
    "updated_at": "2025-01-01T10:00:00.000000Z"
  }
]
```

---

#### POST `/api/financial/categories`

**O que faz:** Cria uma nova categoria financeira.

**Permissão:** `financeiro.categories.create`

**Body (JSON):**
```json
{
  "name": "Impostos",           // obrigatório: string, máximo 255 caracteres
  "is_operational": false,      // opcional: boolean (padrão: false)
  "active": true                // opcional: boolean (padrão: true)
}
```

**Validações:**
- `name` - obrigatório, string, máximo 255 caracteres
- `is_operational` - opcional, boolean (padrão: false)
- `active` - opcional, boolean (padrão: true)

**Exemplo de resposta (201 Created):**
```json
{
  "id": 3,
  "name": "Impostos",
  "is_operational": false,
  "active": true,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

#### PUT/PATCH `/api/financial/categories/{id}`

**O que faz:** Atualiza uma categoria financeira existente.

**Permissão:** `financeiro.categories.edit`

**Body (JSON):**
```json
{
  "name": "Impostos e Taxas",   // opcional: string, máximo 255 caracteres
  "is_operational": false,      // opcional: boolean
  "active": false               // opcional: boolean
}
```

**Validações:**
- `name` - opcional, string, máximo 255 caracteres
- `is_operational` - opcional, boolean
- `active` - opcional, boolean

**Exemplo de resposta:**
```json
{
  "id": 3,
  "name": "Impostos e Taxas",
  "is_operational": false,
  "active": false,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T11:00:00.000000Z"
}
```

---

#### DELETE `/api/financial/categories/{id}`

**O que faz:** Deleta (soft delete) uma categoria financeira.

**Permissão:** `financeiro.categories.delete`

**Exemplo de requisição:**
```
DELETE /api/financial/categories/3
```

**Exemplo de resposta:**
```json
{
  "message": "Categoria deletada com sucesso"
}
```

---

### Métodos de Pagamento

Métodos de pagamento definem como a transação foi realizada (Pix, Dinheiro, Crédito, Débito, etc.).

#### GET `/api/financial/payment-methods`

**O que faz:** Lista todos os métodos de pagamento.

**Permissão:** `financeiro.payment_methods.view`

**Query Parameters:**
- `active` (opcional) - Boolean: `true` ou `false`

**Exemplo de requisição:**
```
GET /api/financial/payment-methods?active=true
```

**Exemplo de resposta:**
```json
[
  {
    "id": 1,
    "name": "Pix",
    "active": true,
    "created_at": "2025-01-01T10:00:00.000000Z",
    "updated_at": "2025-01-01T10:00:00.000000Z"
  },
  {
    "id": 2,
    "name": "Dinheiro",
    "active": true,
    "created_at": "2025-01-01T10:00:00.000000Z",
    "updated_at": "2025-01-01T10:00:00.000000Z"
  },
  {
    "id": 3,
    "name": "Cartão de Crédito",
    "active": true,
    "created_at": "2025-01-01T10:00:00.000000Z",
    "updated_at": "2025-01-01T10:00:00.000000Z"
  }
]
```

---

#### POST `/api/financial/payment-methods`

**O que faz:** Cria um novo método de pagamento.

**Permissão:** `financeiro.payment_methods.create`

**Body (JSON):**
```json
{
  "name": "Cartão de Débito",  // obrigatório: string, máximo 255 caracteres
  "active": true                // opcional: boolean (padrão: true)
}
```

**Validações:**
- `name` - obrigatório, string, máximo 255 caracteres
- `active` - opcional, boolean

**Exemplo de resposta (201 Created):**
```json
{
  "id": 4,
  "name": "Cartão de Débito",
  "active": true,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

#### PUT/PATCH `/api/financial/payment-methods/{id}`

**O que faz:** Atualiza um método de pagamento existente.

**Permissão:** `financeiro.payment_methods.edit`

**Body (JSON):**
```json
{
  "name": "Débito Online",  // opcional: string, máximo 255 caracteres
  "active": false            // opcional: boolean
}
```

**Validações:**
- `name` - opcional, string, máximo 255 caracteres
- `active` - opcional, boolean

**Exemplo de resposta:**
```json
{
  "id": 4,
  "name": "Débito Online",
  "active": false,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T11:00:00.000000Z"
}
```

---

#### DELETE `/api/financial/payment-methods/{id}`

**O que faz:** Deleta (soft delete) um método de pagamento.

**Permissão:** `financeiro.payment_methods.delete`

**Exemplo de requisição:**
```
DELETE /api/financial/payment-methods/4
```

**Exemplo de resposta:**
```json
{
  "message": "Método de pagamento deletado com sucesso"
}
```

---

### Configurações de Comissão

Configurações de comissão definem a taxa de comissão para cada profissional, podendo ser específica por serviço. O sistema busca a configuração mais específica disponível seguindo uma hierarquia de prioridade.

#### GET `/api/financial/commission-configs`

**O que faz:** Lista todas as configurações de comissão.

**Permissão:** `financeiro.commission_configs.view`

**Query Parameters:**
- `provider_id` (opcional) - ID do profissional
- `service_id` (opcional) - ID do serviço
- `active` (opcional) - Boolean: `true` ou `false`
- `search` (opcional) - Busca por nome ou email do profissional (busca parcial com LIKE)

**Exemplo de requisição:**
```
GET /api/financial/commission-configs?provider_id=1&active=true&search=joão
```

**Exemplo de resposta:**
```json
[
  {
    "id": 1,
    "provider": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "service": {
      "id": 1,
      "name": "Aula Prática"
    },
    "commission_rate": 15.00,
    "active": true,
    "created_at": "2025-01-01T10:00:00.000000Z",
    "updated_at": "2025-01-01T10:00:00.000000Z"
  },
  {
    "id": 2,
    "provider": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "service": null,
    "commission_rate": 10.00,
    "active": true,
    "created_at": "2025-01-01T10:00:00.000000Z",
    "updated_at": "2025-01-01T10:00:00.000000Z"
  }
]
```

**Hierarquia de Prioridade:**
O sistema busca a configuração mais específica na seguinte ordem:
1. **Profissional + Serviço** (mais específica)
2. **Profissional apenas** (padrão geral)

---

#### POST `/api/financial/commission-configs`

**O que faz:** Cria uma nova configuração de comissão.

**Permissão:** `financeiro.commission_configs.create`

**Body (JSON):**
```json
{
  "provider_id": 1,           // obrigatório: ID do profissional (deve existir)
  "service_id": 1,            // opcional: ID do serviço (deve existir)
  "commission_rate": 15.00,   // obrigatório: numérico, entre 0 e 100
  "active": true              // opcional: boolean (padrão: true)
}
```

**Validações:**
- `provider_id` - obrigatório, deve existir em `providers`
- `service_id` - opcional, deve existir em `services`
- `commission_rate` - obrigatório, numérico, mínimo 0, máximo 100
- `active` - opcional, boolean

**Exemplo de requisição:**
```json
POST /api/financial/commission-configs
{
  "provider_id": 1,
  "service_id": 1,
  "commission_rate": 15.00,
  "active": true
}
```

**Exemplo de resposta (201 Created):**
```json
{
  "id": 1,
  "provider": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "service": {
    "id": 1,
    "name": "Aula Prática"
  },
  "commission_rate": 15.00,
  "active": true,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

#### PUT/PATCH `/api/financial/commission-configs/{id}`

**O que faz:** Atualiza uma configuração de comissão existente.

**Permissão:** `financeiro.commission_configs.edit`

**Body (JSON):**
```json
{
  "commission_rate": 20.00,  // opcional: numérico, entre 0 e 100
  "active": false            // opcional: boolean
}
```

**Validações:**
- `commission_rate` - opcional, numérico, mínimo 0, máximo 100
- `active` - opcional, boolean

**⚠️ Nota:** Não é possível alterar `provider_id` ou `service_id` após a criação. Para alterar esses campos, delete e crie uma nova configuração.

**Exemplo de resposta:**
```json
{
  "id": 1,
  "provider": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "service": {
    "id": 1,
    "name": "Aula Prática"
  },
  "commission_rate": 20.00,
  "active": false,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T11:00:00.000000Z"
}
```

---

#### DELETE `/api/financial/commission-configs/{id}`

**O que faz:** Deleta (soft delete) uma configuração de comissão.

**Permissão:** `financeiro.commission_configs.delete`

**Exemplo de requisição:**
```
DELETE /api/financial/commission-configs/1
```

**Exemplo de resposta:**
```json
{
  "message": "Configuração deletada com sucesso"
}
```

---

## 🔗 Rotas Auxiliares

Para criar transações e configurações de comissão, você precisará dos IDs de outras entidades. Use estas rotas para buscar:

### Serviços
```
GET /api/agenda/services
```

### Profissionais
```
GET /api/agenda/providers
```

### Categorias
```
GET /api/financial/categories
```

---

## 📝 Notas Importantes

### Sobre Transações

1. **Imutabilidade**: Transações não podem ser editadas ou deletadas. Se houver erro:
   - Crie uma transação compensatória (tipo oposto)
   - Crie a transação correta

2. **Status**: 
   - `PENDING`: Aguardando confirmação
   - `CONFIRMED`: Confirmada
   - `CANCELLED`: Cancelada

3. **Reference Type/ID**: Usado para vincular transações a eventos operacionais (ex: `Appointment`, `Service`)

4. **Categoria e Tipo**: A categoria define o motivo do lançamento (ex: "Serviços", "Despesas Fixas"). O tipo (IN/OUT) é definido exclusivamente no campo `type` da transação.

### Sobre Comissões

1. **Criação Automática**: Comissões são criadas automaticamente quando uma transação operacional é gerada e há configuração de comissão para o profissional.

2. **Pagamento**: Ao pagar uma comissão, uma transação de saída é criada automaticamente.

3. **Status**:
   - `PENDING`: Aguardando pagamento
   - `PAID`: Paga
   - `CANCELLED`: Cancelada

4. **Cálculo**: A taxa de comissão é obtida da configuração mais específica disponível (provider + service ou provider apenas). O valor da comissão é calculado e armazenado no momento da criação.

### Sobre Configurações de Comissão

1. **Hierarquia**: O sistema sempre busca a configuração mais específica disponível:
   - Primeiro tenta: provider + service
   - Se não encontrar, usa: provider apenas (padrão)

2. **Ativação/Desativação**: Use `active: false` para desativar temporariamente uma configuração sem deletá-la.

3. **Unicidade**: Não pode haver duas configurações ativas com os mesmos `provider_id` e `service_id` (considerando NULL como valor).

### Sobre Categorias

1. **Operacional vs Manual**: 
   - `is_operational: true` - Categorias para transações geradas automaticamente pelo sistema
   - `is_operational: false` - Categorias para transações lançadas manualmente

2. **Tipo da Transação**: O tipo (IN/OUT) é definido no campo `type` da transação, não na categoria. Uma mesma categoria pode ser usada tanto para entradas quanto para saídas.

---

## 🚨 Códigos de Erro Comuns

- **404**: Recurso não encontrado
- **422**: Erro de validação (dados inválidos)
- **401**: Não autenticado
- **403**: Sem permissão

---

## 📚 Documentação Relacionada

- [Configurações de Comissão - Detalhes](./CONFIGURACOES_COMISSAO.md)
- [Lançamentos Manuais - Guia Frontend](./LANCAMENTOS_MANUAIS_FRONTEND.md)

---

**Última atualização:** Dezembro 2025
