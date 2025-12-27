# 💰 CONFIGURAÇÕES DE COMISSÃO - Documentação

## 📋 Visão Geral

As configurações de comissão permitem definir diferentes taxas de comissão para profissionais, com flexibilidade para aplicar por serviço específico, origem financeira ou como padrão geral.

## 🎯 Hierarquia de Prioridade

O sistema busca a configuração **mais específica** disponível na seguinte ordem:

1. **Provider + Service + Origin** (mais específica)
   - Exemplo: João + Corte de Cabelo + Atendimento = 40%
   - A origem vem da transação financeira (`transaction.origin_id`)

2. **Provider + Service** (sem origin)
   - Exemplo: João + Corte de Cabelo = 35%

3. **Provider + Origin** (sem service)
   - Exemplo: João + Atendimento = 30%
   - A origem vem da transação financeira (`transaction.origin_id`)

4. **Provider apenas** (padrão geral)
   - Exemplo: João = 25%

**Regra:** Quando uma transação é criada, o sistema busca a configuração mais específica que se aplica e usa aquela taxa.

**Importante:** 
- As **configurações** (`provider_commission_configs`) podem ter `origin_id` para definir comissões diferentes por origem
- As **comissões** (`commissions`) não armazenam `origin_id` diretamente - a origem é acessada via `commission.transaction.origin_id`

---

## 📊 Estrutura da Tabela

**Tabela:** `provider_commission_configs`

**Campos:**
- `id` - ID único
- `tenant_id` - FK para tenants (multi-tenant)
- `provider_id` - FK para providers (obrigatório)
- `service_id` - FK para services (opcional - NULL = aplica para todos)
- `origin_id` - FK para financial_origins (opcional - NULL = aplica para todas)
- `commission_rate` - Taxa de comissão em % (DECIMAL 5,2 - ex: 40.00)
- `active` - Ativo/Inativo (BOOLEAN)
- `created_at`, `updated_at` - Timestamps
- `deleted_at` - Soft delete

**Constraint única:**
- `unique(tenant_id, provider_id, service_id, origin_id)`
- Um provider só pode ter uma configuração por combinação de service/origin

---

## 🛣️ Rotas da API

### GET `/api/agenda/services`
**O que faz:** Lista todos os serviços disponíveis (necessário para configurar comissões por serviço).

**Query Parameters (opcionais):**
- `active` (boolean) - Filtrar apenas serviços ativos/inativos

**Resposta:** Array de serviços

**Exemplo de resposta:**
```json
[
  {
    "id": 5,
    "tenant_id": 1,
    "module_id": 2,
    "name": "Corte de Cabelo",
    "slug": "corte-de-cabelo",
    "duration_minutes": 30,
    "active": true,
    "module": {
      "id": 2,
      "key": "agenda",
      "name": "Agenda"
    },
    "price": {
      "id": 10,
      "price": 50.00,
      "currency": "BRL",
      "start_date": null,
      "end_date": null
    },
    "created_at": "2025-12-27T10:00:00.000000Z",
    "updated_at": "2025-12-27T10:00:00.000000Z"
  },
  {
    "id": 6,
    "tenant_id": 1,
    "module_id": 2,
    "name": "Barba",
    "slug": "barba",
    "duration_minutes": 20,
    "active": true,
    "module": {
      "id": 2,
      "key": "agenda",
      "name": "Agenda"
    },
    "price": {
      "id": 11,
      "price": 30.00,
      "currency": "BRL",
      "start_date": null,
      "end_date": null
    },
    "created_at": "2025-12-27T10:00:00.000000Z",
    "updated_at": "2025-12-27T10:00:00.000000Z"
  }
]
```

---

### GET `/api/financial/commission-configs`
**O que faz:** Lista todas as configurações de comissão.

**Query Parameters (opcionais):**
- `provider_id` (integer) - Filtrar por profissional específico
- `service_id` (integer) - Filtrar por serviço específico
- `active` (boolean) - Filtrar apenas ativas/inativas

**Resposta:** Array de configurações de comissão

**Exemplo de resposta:**
```json
[
  {
    "id": 1,
    "tenant_id": 1,
    "provider": {
      "id": 10,
      "name": "João Silva"
    },
    "service": {
      "id": 5,
      "name": "Corte de Cabelo"
    },
    "origin": null,
    "commission_rate": 40.00,
    "active": true,
    "created_at": "2025-12-27T10:00:00.000000Z",
    "updated_at": "2025-12-27T10:00:00.000000Z"
  }
]
```

---

### GET `/api/financial/origins`
**O que faz:** Lista todas as origens financeiras disponíveis (necessário para configurar comissões por origem).

**Query Parameters (opcionais):**
- `active` (boolean) - Filtrar apenas origens ativas/inativas
- `origin_type` (string) - Filtrar por tipo: `OPERATIONAL` ou `MANUAL`

**Resposta:** Array de origens financeiras

**Exemplo de resposta:**
```json
[
  {
    "id": 2,
    "tenant_id": 1,
    "name": "Atendimento",
    "origin_type": "OPERATIONAL",
    "active": true,
    "created_at": "2025-12-27T10:00:00.000000Z",
    "updated_at": "2025-12-27T10:00:00.000000Z"
  },
  {
    "id": 3,
    "tenant_id": 1,
    "name": "Atendimento Presencial",
    "origin_type": "OPERATIONAL",
    "active": true,
    "created_at": "2025-12-27T10:00:00.000000Z",
    "updated_at": "2025-12-27T10:00:00.000000Z"
  },
  {
    "id": 8,
    "tenant_id": 1,
    "name": "Pagamento de Comissão",
    "origin_type": "MANUAL",
    "active": true,
    "created_at": "2025-12-27T10:00:00.000000Z",
    "updated_at": "2025-12-27T10:00:00.000000Z"
  }
]
```

---

### GET `/api/agenda/providers`
**O que faz:** Lista todos os profissionais disponíveis (necessário para identificar o provider ao configurar comissões).

**Query Parameters (opcionais):**
- Nenhum parâmetro específico

**Resposta:** Array de profissionais

**Exemplo de resposta:**
```json
[
  {
    "id": 10,
    "tenant_id": 1,
    "person_id": 5,
    "user": {
      "id": 15,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "person": {
      "id": 5,
      "cpf": "12345678900",
      "phone": "11999999999",
      "address": {
        "street": "Rua Exemplo",
        "number": "123",
        "city": "São Paulo",
        "state": "SP"
      }
    },
    "photo_url": "https://...",
    "service_ids": [5, 6],
    "services": [
      {
        "id": 5,
        "name": "Corte de Cabelo",
        "slug": "corte-de-cabelo"
      },
      {
        "id": 6,
        "name": "Barba",
        "slug": "barba"
      }
    ],
    "created_at": "2025-12-27T10:00:00.000000Z",
    "updated_at": "2025-12-27T10:00:00.000000Z"
  }
]
```

---

### POST `/api/financial/commission-configs`
**O que faz:** Cria uma nova configuração de comissão.

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

**Validações:**
- `provider_id` deve existir na tabela `providers` (obter via `/api/agenda/providers`)
- `service_id` (se informado) deve existir na tabela `services` (obter via `/api/agenda/services`)
- `origin_id` (se informado) deve existir na tabela `financial_origins` (obter via `/api/financial/origins`)
- `commission_rate` deve ser entre 0 e 100

**Resposta:** Objeto da configuração criada (status 201)

---

### PUT `/api/financial/commission-configs/{id}` ou PATCH `/api/financial/commission-configs/{id}`
**O que faz:** Atualiza uma configuração de comissão existente.

**Parâmetros na URL:**
- `id` (integer) - ID da configuração

**Body (JSON) - todos os campos são opcionais:**
```json
{
  "commission_rate": 45.00,
  "active": false
}
```

**Nota:** Não é possível alterar `provider_id`, `service_id` ou `origin_id` após a criação. Para mudar, delete e crie uma nova.

**Resposta:** Objeto da configuração atualizada

---

### DELETE `/api/financial/commission-configs/{id}`
**O que faz:** Deleta uma configuração de comissão (soft delete).

**Parâmetros na URL:**
- `id` (integer) - ID da configuração

**Resposta:** Mensagem de sucesso

---

## 📝 Fluxo de Configuração

**Passo a passo para configurar comissões:**

1. **Buscar profissionais disponíveis:**
   ```
   GET /api/agenda/providers
   ```
   Use o `id` retornado para identificar o profissional no `provider_id`.

2. **Buscar serviços disponíveis (opcional):**
   ```
   GET /api/agenda/services
   ```
   Use o `id` retornado para configurar comissão por serviço específico no `service_id`.

3. **Buscar origens financeiras (opcional):**
   ```
   GET /api/financial/origins
   ```
   Use o `id` retornado para configurar comissão por origem específica no `origin_id`.

4. **Criar configuração de comissão:**
   ```
   POST /api/financial/commission-configs
   ```
   Use os IDs obtidos nos passos anteriores:
   - `provider_id` (obrigatório) - do passo 1
   - `service_id` (opcional) - do passo 2
   - `origin_id` (opcional) - do passo 3
   - `commission_rate` (obrigatório) - taxa em %

---

## 📝 Exemplos de Uso

### Exemplo 1: Configuração Específica por Serviço

**Cenário:** João recebe 40% de comissão apenas para "Corte de Cabelo", e 30% para os demais serviços.

```json
// Configuração específica para Corte
POST /api/financial/commission-configs
{
  "provider_id": 10,
  "service_id": 5,        // ID do serviço "Corte de Cabelo" (obtido via GET /api/agenda/services)
  "origin_id": null,      // NULL = não especifica origem
  "commission_rate": 40.00
}

// Configuração padrão para outros serviços
POST /api/financial/commission-configs
{
  "provider_id": 10,
  "service_id": null,     // NULL = aplica para todos os serviços
  "origin_id": null,      // NULL = aplica para todas as origens
  "commission_rate": 30.00
}
```

**Resultado:**
- Quando João faz um "Corte de Cabelo" → usa 40%
- Quando João faz qualquer outro serviço → usa 30%

---

### Exemplo 2: Configuração por Origem

**Cenário:** Maria recebe 35% em "Atendimentos" e 25% em outras origens.

```json
// Configuração para Atendimentos
POST /api/financial/commission-configs
{
  "provider_id": 15,
  "service_id": null,     // NULL = todos os serviços
  "origin_id": 2,         // ID da origem "Atendimento"
  "commission_rate": 35.00
}

// Configuração padrão
POST /api/financial/commission-configs
{
  "provider_id": 15,
  "service_id": null,
  "origin_id": null,
  "commission_rate": 25.00
}
```

---

### Exemplo 3: Configuração Mais Específica (Service + Origin)

**Cenário:** Pedro recebe 50% apenas quando faz "Corte de Cabelo" em "Atendimento Presencial", e 35% para outros casos.

```json
// Configuração mais específica
POST /api/financial/commission-configs
{
  "provider_id": 20,
  "service_id": 5,        // Corte de Cabelo
  "origin_id": 3,         // Atendimento Presencial
  "commission_rate": 50.00
}

// Configuração padrão
POST /api/financial/commission-configs
{
  "provider_id": 20,
  "service_id": null,
  "origin_id": null,
  "commission_rate": 35.00
}
```

**Resultado:**
- Pedro faz "Corte de Cabelo" em "Atendimento Presencial" → usa 50%
- Pedro faz qualquer outra combinação → usa 35%

---

### Exemplo 4: Múltiplas Configurações com Hierarquia

**Cenário:** Ana tem diferentes comissões:
- Corte de Cabelo: 40%
- Barba: 35%
- Outros serviços: 30%

```json
// Corte de Cabelo
POST /api/financial/commission-configs
{
  "provider_id": 25,
  "service_id": 5,        // Corte
  "commission_rate": 40.00
}

// Barba
POST /api/financial/commission-configs
{
  "provider_id": 25,
  "service_id": 6,        // Barba
  "commission_rate": 35.00
}

// Padrão para outros
POST /api/financial/commission-configs
{
  "provider_id": 25,
  "service_id": null,
  "commission_rate": 30.00
}
```

---

## 🔍 Como o Sistema Busca a Configuração

Quando uma transação é criada e precisa calcular a comissão, o sistema:

1. **Identifica o contexto:**
   - `provider_id` (obrigatório)
   - `service_id` (se a transação veio de um appointment/service)
   - `origin_id` (obtido da transação financeira: `transaction.origin_id`)

2. **Busca na hierarquia:**
   ```sql
   -- 1. Tenta: provider + service + origin (da transação)
   WHERE provider_id = X AND service_id = Y AND origin_id = Z (da transaction.origin_id)
   
   -- 2. Se não encontrar, tenta: provider + service
   WHERE provider_id = X AND service_id = Y AND origin_id IS NULL
   
   -- 3. Se não encontrar, tenta: provider + origin (da transação)
   WHERE provider_id = X AND service_id IS NULL AND origin_id = Z (da transaction.origin_id)
   
   -- 4. Se não encontrar, usa: provider apenas
   WHERE provider_id = X AND service_id IS NULL AND origin_id IS NULL
   ```
   
   **Nota:** O `origin_id` usado na busca vem da transação financeira, não da comissão (que não armazena mais esse campo).

3. **Usa a primeira encontrada** (mais específica)

4. **Se não encontrar nenhuma:** Não cria comissão

---

## ⚠️ Regras Importantes

1. **Sempre deve existir uma configuração padrão** (`service_id = NULL` e `origin_id = NULL`) para garantir que o profissional sempre tenha comissão.

2. **Configurações mais específicas têm prioridade** sobre as genéricas.

3. **Não é possível ter duas configurações para a mesma combinação** (constraint única).

4. **Configurações inativas** (`active = false`) não são consideradas na busca.

5. **Soft delete:** Configurações deletadas não aparecem, mas não são removidas fisicamente do banco.

---

## 🎯 Casos de Uso Comuns

### Caso 1: Comissão Fixa para Todos os Serviços
```json
{
  "provider_id": 10,
  "service_id": null,
  "origin_id": null,
  "commission_rate": 30.00
}
```

### Caso 2: Comissão Diferente por Serviço
```json
// Serviço A: 40%
{"provider_id": 10, "service_id": 5, "commission_rate": 40.00}

// Serviço B: 35%
{"provider_id": 10, "service_id": 6, "commission_rate": 35.00}

// Outros: 30%
{"provider_id": 10, "service_id": null, "commission_rate": 30.00}
```

### Caso 3: Comissão Diferente por Origem
```json
// Origem A: 40%
{"provider_id": 10, "origin_id": 2, "commission_rate": 40.00}

// Outras: 30%
{"provider_id": 10, "origin_id": null, "commission_rate": 30.00}
```

### Caso 4: Comissão Específica por Combinação
```json
// Corte + Atendimento: 50%
{"provider_id": 10, "service_id": 5, "origin_id": 2, "commission_rate": 50.00}

// Corte (outras origens): 40%
{"provider_id": 10, "service_id": 5, "origin_id": null, "commission_rate": 40.00}

// Outros: 30%
{"provider_id": 10, "service_id": null, "origin_id": null, "commission_rate": 30.00}
```

---

## 📊 Resumo da Hierarquia

```
┌─────────────────────────────────────────┐
│ Provider + Service + Origin (50%)       │ ← Mais específica
├─────────────────────────────────────────┤
│ Provider + Service (40%)                 │
├─────────────────────────────────────────┤
│ Provider + Origin (35%)                  │
├─────────────────────────────────────────┤
│ Provider apenas (30%)                    │ ← Padrão
└─────────────────────────────────────────┘
```

**Última atualização:** 27/12/2025

