# 📝 Lançamentos Financeiros Manuais - Guia Frontend

Este documento descreve as rotas e payloads necessários para implementar lançamentos financeiros manuais no frontend.

---

## 📋 Rotas Necessárias

### 1. Listar Categorias
**GET** `/api/financial/categories`

**Query Parameters:**
- `active` (opcional) - `true` ou `false`

**Resposta:**
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

### 2. Criar Categoria
**POST** `/api/financial/categories`

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

**Resposta (201 Created):**
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

### 3. Listar Métodos de Pagamento
**GET** `/api/financial/payment-methods`

**Query Parameters:**
- `active` (opcional) - `true` ou `false`

**Resposta:**
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

### 4. Criar Método de Pagamento
**POST** `/api/financial/payment-methods`

**Body (JSON):**
```json
{
  "name": "Cartão de Débito",  // obrigatório: string, máximo 255 caracteres
  "active": true                // opcional: boolean (padrão: true)
}
```

**Validações:**
- `name` - obrigatório, string, máximo 255 caracteres
- `active` - opcional, boolean (padrão: true)

**Resposta (201 Created):**
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

### 5. Criar Transação Manual
**POST** `/api/financial/transactions`

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

**Resposta (201 Created):**
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

---

## 🔄 Fluxo Recomendado

### 1. Ao Abrir o Formulário de Lançamento

1. **Buscar categorias ativas:**
   ```
   GET /api/financial/categories?active=true
   ```

2. **Buscar métodos de pagamento ativos:**
   ```
   GET /api/financial/payment-methods?active=true
   ```

### 2. Ao Criar um Lançamento

1. **Validar campos obrigatórios:**
   - `type` (IN ou OUT)
   - `amount` (mínimo 0.01)
   - `category_id` (deve existir)
   - `payment_method_id` (deve existir)

2. **Enviar requisição:**
   ```
   POST /api/financial/transactions
   ```

3. **Tratar resposta:**
   - **201 Created**: Lançamento criado com sucesso
   - **422 Unprocessable Entity**: Erro de validação (verificar mensagem de erro)
   - **404 Not Found**: Categoria ou método de pagamento não encontrado

### 3. Ao Criar uma Nova Categoria

1. **Validar campos:**
   - `name` (obrigatório, máximo 255 caracteres)
   - `is_operational` (opcional, boolean)
   - `active` (opcional, boolean)

2. **Enviar requisição:**
   ```
   POST /api/financial/categories
   ```

3. **Após criação bem-sucedida:**
   - Atualizar lista de categorias
   - Selecionar a categoria recém-criada no formulário (opcional)

### 4. Ao Criar um Novo Método de Pagamento

1. **Validar campos:**
   - `name` (obrigatório, máximo 255 caracteres)
   - `active` (opcional, boolean)

2. **Enviar requisição:**
   ```
   POST /api/financial/payment-methods
   ```

3. **Após criação bem-sucedida:**
   - Atualizar lista de métodos de pagamento
   - Selecionar o método recém-criado no formulário (opcional)

---

## 📊 Exemplos de Payloads

### Entrada Manual
```json
{
  "type": "IN",
  "amount": 500.00,
  "description": "Venda avulsa de produto",
  "category_id": 1,
  "payment_method_id": 1,
  "status": "CONFIRMED",
  "occurred_at": "2025-12-01 14:30:00"
}
```

### Saída Manual
```json
{
  "type": "OUT",
  "amount": 200.00,
  "description": "Pagamento de aluguel",
  "category_id": 2,
  "payment_method_id": 1,
  "status": "CONFIRMED",
  "occurred_at": "2025-12-01 14:30:00"
}
```

### Criar Nova Categoria
```json
{
  "name": "Taxas Bancárias",
  "is_operational": false,
  "active": true
}
```

### Criar Novo Método de Pagamento
```json
{
  "name": "Boleto Bancário",
  "active": true
}
```

---

## ⚠️ Observações Importantes

1. **Transações não podem ser editadas ou deletadas**: Se houver erro, crie uma transação compensatória (tipo oposto) e depois crie a transação correta.

2. **Tipo da Transação**: O campo `type` define se é entrada (`IN`) ou saída (`OUT`). Isso é independente da categoria.

3. **Categoria Operacional vs Manual**: 
   - `is_operational: true` - Para categorias de transações geradas automaticamente
   - `is_operational: false` - Para categorias de transações manuais (recomendado para lançamentos manuais)

4. **Status Padrão**: Se não informado, o status padrão é `PENDING`. Para lançamentos manuais, geralmente usa-se `CONFIRMED`.

5. **Data/Hora**: Se não informado `occurred_at`, será usado o momento atual. Para lançamentos retroativos, informe a data desejada.

---

## 🎯 Campos do Formulário Sugerido

### Campos Obrigatórios
- **Tipo**: Select (IN / OUT)
- **Valor**: Input numérico (mínimo 0.01)
- **Categoria**: Select (buscar de `/api/financial/categories`)
- **Método de Pagamento**: Select (buscar de `/api/financial/payment-methods`)

### Campos Opcionais
- **Descrição**: Textarea (máximo 1000 caracteres)
- **Status**: Select (PENDING / CONFIRMED / CANCELLED)
- **Data/Hora**: DateTime picker

### Ações Adicionais
- **Criar Nova Categoria**: Botão que abre modal/formulário para criar categoria
- **Criar Novo Método de Pagamento**: Botão que abre modal/formulário para criar método de pagamento
- **Filtrar Categorias**: Filtrar por `is_operational` se necessário

---

**Última atualização:** Dezembro 2025

