# 📘 Módulo Agenda – Documentação Completa

Sistema de agendamento genérico para SaaS multi-tenant.  
Permite gerenciar serviços, profissionais e agendamentos de forma flexível para diferentes tipos de negócios.

---

## 📑 Índice

1. [Visão Geral](#visão-geral)
2. [Estrutura das Tabelas](#estrutura-das-tabelas)
3. [Models e Relacionamentos](#models-e-relacionamentos)
4. [Endpoints da API](#endpoints-da-api)
5. [Regras de Negócio](#regras-de-negócio)
6. [Permissões](#permissões)
7. [Exemplos Práticos](#exemplos-práticos)

## 📚 Documentação Detalhada

- [Services CRUD](./services-crud.md) - Documentação completa de Services
- [Providers CRUD](./providers-crud.md) - Documentação completa de Providers
- [Appointments CRUD](./appointments-crud.md) - Documentação completa de Appointments

---

## 🔎 Visão Geral

O módulo Agenda é 100% genérico e funciona para diferentes tipos de negócios:

- **Autoescola**: Aulas práticas, teóricas, exames
- **Barbearia**: Cortes, barbas, tratamentos
- **Salão Feminino**: Corte, escova, coloração, manicure
- **Prestadores de Serviços**: Consultas, atendimentos, serviços diversos

### Componentes Principais

1. **Services** – Serviços oferecidos (ex: aula prática, corte, manicure)
2. **Providers** – Profissionais que prestam os serviços (ex: instrutores, barbeiros)
3. **Appointments** – Agendamentos dos clientes
4. **Status Agenda** – Status dos agendamentos (agendado, confirmado, concluído, etc.)
5. **Tenant Business Hours** – Horários de funcionamento do estabelecimento (tenant)
6. **Professional Availabilities** – Horários de disponibilidade dos profissionais
7. **Professional Blocks** – Bloqueios de horários (folgas, almoços, indisponibilidades)

---

## 🧱 Estrutura das Tabelas

### ▸ services

| Campo            | Tipo    | Descrição                                    |
|------------------|---------|----------------------------------------------|
| id               | bigint  | Identificador                                 |
| tenant_id        | FK      | Referência ao tenant                          |
| module_id        | FK      | Referência ao módulo (ex: auto-escola, barbearia) |
| name             | string  | Nome do serviço (ex: "Aula Prática")         |
| slug             | string  | Identificador único (ex: "aula-pratica")      |
| duration_minutes | integer | Duração do serviço em minutos                 |
| active           | boolean | Se o serviço está ativo                       |
| created_at       | datetime| Data de criação                               |
| updated_at       | datetime| Data de atualização                           |

**Índices:**
- `tenant_id`, `active`
- `tenant_id`, `module_id`

**Observação:** Os serviços são filtrados automaticamente pelos módulos ativos do tenant em `tenant_modules`

---

### ▸ service_prices

| Campo       | Tipo     | Descrição                                    |
|-------------|----------|----------------------------------------------|
| id          | bigint   | Identificador                                 |
| tenant_id   | FK       | Referência ao tenant                          |
| service_id  | FK       | Referência ao serviço                         |
| price       | decimal  | Preço do serviço (10,2)                       |
| currency    | string   | Moeda (padrão: "BRL")                         |
| active      | boolean  | Se o preço está ativo                         |
| start_date  | date     | Data de início da vigência (opcional)         |
| end_date    | date     | Data de fim da vigência (opcional)            |
| created_at  | datetime | Data de criação                               |
| updated_at  | datetime | Data de atualização                           |

**Índices:**
- `tenant_id`, `service_id`, `active`
- `service_id`, `active`, `start_date`, `end_date`

**Observação:** Permite histórico de preços e variações por período (promoções, ajustes)

---

### ▸ providers

| Campo      | Tipo    | Descrição                                    |
|------------|---------|----------------------------------------------|
| id         | bigint  | Identificador                                 |
| tenant_id  | FK      | Referência ao tenant                          |
| person_id  | FK      | Referência à pessoa (person)                  |
| service_ids| json   | Array de IDs dos serviços que o profissional oferece |
| created_at | datetime| Data de criação                               |
| updated_at | datetime| Data de atualização                           |

**Índices:**
- `tenant_id`, `person_id`

**Observação:** 
- `service_ids` é um array JSON. Exemplo: `[1, 2, 3]`
- Provider está vinculado a Person, que está vinculado a User
- A foto (`photo_url`) é armazenada na tabela `persons`, não em `providers`
- Veja [Providers CRUD](./providers-crud.md) para documentação completa

---

### ▸ appointments

| Campo           | Tipo     | Descrição                                    |
|----------------|----------|----------------------------------------------|
| id              | bigint   | Identificador                                 |
| tenant_id      | FK       | Referência ao tenant                          |
| service_id     | FK       | Referência ao serviço                         |
| provider_id    | FK       | Referência ao profissional                    |
| client_id      | FK       | Referência ao cliente (user_id)               |
| date_start     | datetime | Data/hora de início                           |
| date_end       | datetime | Data/hora de término (calculado automaticamente) |
| status_agenda_id| FK      | Referência ao status do agendamento           |
| notes           | text     | Observações do agendamento (opcional)         |
| created_at     | datetime | Data de criação                               |
| updated_at     | datetime | Data de atualização                           |

**Índices:**
- `tenant_id`, `provider_id`, `date_start`
- `tenant_id`, `date_start`, `date_end`

**Observação:** `date_end` é calculado automaticamente: `date_start + duration_minutes` do serviço

---

### ▸ status_agenda

| Campo       | Tipo    | Descrição                                    |
|-------------|---------|----------------------------------------------|
| id          | bigint  | Identificador                                 |
| key         | string  | Chave única (ex: "agendado")                  |
| name        | string  | Nome do status (ex: "Agendado")               |
| description | text    | Descrição do status                           |
| order       | integer | Ordem de exibição                             |
| active      | boolean | Se o status está ativo                       |
| created_at  | datetime| Data de criação                               |
| updated_at  | datetime| Data de atualização                           |

**Status Padrão:**
- `agendado` – Agendamento confirmado
- `confirmado` – Agendamento confirmado pelo cliente
- `em-andamento` – Serviço em execução
- `concluido` – Serviço finalizado com sucesso
- `cancelado` – Agendamento cancelado
- `nao-compareceu` – Cliente não compareceu

---

### ▸ tenant_business_hours

| Campo      | Tipo    | Descrição                                    |
|------------|---------|----------------------------------------------|
| id         | bigint  | Identificador                                 |
| tenant_id  | FK      | Referência ao tenant                          |
| weekday    | integer | Dia da semana (0 = domingo, 6 = sábado)      |
| start_time | time    | Horário de início (ex: "09:00:00")           |
| end_time   | time    | Horário de término (ex: "19:00:00")          |
| active     | boolean | Se o horário está ativo                       |
| created_at | datetime| Data de criação                               |
| updated_at | datetime| Data de atualização                           |

**Índices:**
- `tenant_id`, `weekday` (único)
- `tenant_id`, `active`

**Observação:** Define o horário de funcionamento do estabelecimento. Nada pode acontecer fora deste horário.

**Exemplos:**
- Barbearia → 09:00–19:00
- Autoescola → 07:00–19:00

---

### ▸ professional_availabilities

| Campo      | Tipo    | Descrição                                    |
|------------|---------|----------------------------------------------|
| id         | bigint  | Identificador                                 |
| provider_id| FK      | Referência ao profissional                   |
| weekday    | integer | Dia da semana (0 = domingo, 6 = sábado)      |
| start_time | time    | Horário de início (ex: "08:00:00")           |
| end_time   | time    | Horário de término (ex: "18:00:00")          |
| active     | boolean | Se a disponibilidade está ativa               |
| created_at | datetime| Data de criação                               |
| updated_at | datetime| Data de atualização                           |

**Índices:**
- `provider_id`, `weekday` (único)
- `provider_id`, `active`

**Observação:** Define os horários de trabalho do profissional. O profissional não pode ultrapassar o horário do tenant.

---

### ▸ professional_blocks

| Campo      | Tipo     | Descrição                                    |
|------------|----------|----------------------------------------------|
| id         | bigint   | Identificador                                 |
| provider_id| FK       | Referência ao profissional                   |
| tenant_id  | FK       | Referência ao tenant                          |
| start_at   | datetime | Data/hora de início do bloqueio               |
| end_at     | datetime | Data/hora de término do bloqueio              |
| reason     | string   | Motivo do bloqueio (ex: "Almoço", "Folga")   |
| created_by | FK       | Referência ao usuário que criou o bloqueio    |
| created_at | datetime | Data de criação                               |
| updated_at | datetime | Data de atualização                           |

**Índices:**
- `provider_id`, `start_at`, `end_at`
- `tenant_id`, `start_at`, `end_at`

**Observação:** Bloqueios dinâmicos (pontuais ou recorrentes) que impedem agendamentos no período.

**Exemplos de uso:**
- Almoço
- Folga
- Horário que o profissional não quer atender mais
- Reunião
- Aula teórica

---

## 🔗 Models e Relacionamentos

### Service Model

**Localização:** `app/Models/Service.php`

**Relacionamentos:**
- `belongsTo(Tenant)` – Pertence a um tenant
- `belongsTo(Module)` – Pertence a um módulo
- `hasMany(Appointment)` – Tem muitos agendamentos
- `hasMany(ServicePrice)` – Tem muitos preços (histórico)
- `hasOne(ServicePrice, 'activePrice')` – Tem um preço ativo

**Casts:**
- `active` → boolean

---

### Provider Model

**Localização:** `app/Models/Provider.php`

**Relacionamentos:**
- `belongsTo(Tenant)` – Pertence a um tenant
- `belongsTo(Person)` – Pertence a uma pessoa
- `hasMany(Appointment)` – Tem muitos agendamentos
- `hasMany(ProfessionalAvailability)` – Tem muitas disponibilidades
- `hasMany(ProfessionalBlock)` – Tem muitos bloqueios

**Casts:**
- `service_ids` → array (JSON)

**Observação:** 
- Provider → Person → User (cadeia de relacionamentos)
- A foto é armazenada em `persons.photo_url`
- Veja [Providers CRUD](./providers-crud.md) para documentação completa

---

### Tenant Model

**Localização:** `app/Models/Tenant.php`

**Relacionamentos:**
- `hasMany(TenantBusinessHour)` – Tem muitos horários de funcionamento

**Observação:** 
- Define o horário de funcionamento do estabelecimento
- Nada pode acontecer fora do horário do tenant

---

### TenantBusinessHour Model

**Localização:** `app/Models/TenantBusinessHour.php`

**Relacionamentos:**
- `belongsTo(Tenant)` – Pertence a um tenant

**Casts:**
- `active` → boolean

---

### ProfessionalAvailability Model

**Localização:** `app/Models/ProfessionalAvailability.php`

**Relacionamentos:**
- `belongsTo(Provider)` – Pertence a um profissional

**Casts:**
- `active` → boolean

---

### ProfessionalBlock Model

**Localização:** `app/Models/ProfessionalBlock.php`

**Relacionamentos:**
- `belongsTo(Provider)` – Pertence a um profissional
- `belongsTo(Tenant)` – Pertence a um tenant
- `belongsTo(User, 'created_by')` – Usuário que criou o bloqueio

**Casts:**
- `start_at` → datetime
- `end_at` → datetime

---

### Appointment Model

**Localização:** `app/Models/Appointment.php`

**Relacionamentos:**
- `belongsTo(Tenant)` – Pertence a um tenant
- `belongsTo(Service)` – Pertence a um serviço
- `belongsTo(Provider)` – Pertence a um profissional
- `belongsTo(User, 'client_id')` – Pertence a um cliente (usuário)
- `belongsTo(StatusAgenda, 'status_agenda_id')` – Pertence a um status

**Casts:**
- `date_start` → datetime
- `date_end` → datetime

---

### StatusAgenda Model

**Localização:** `app/Models/StatusAgenda.php`

**Relacionamentos:**
- `hasMany(Appointment)` – Tem muitos agendamentos

**Casts:**
- `active` → boolean

---

## 🌐 Endpoints da API

Todas as rotas estão sob o prefixo `/api/agenda` e requerem autenticação (`auth:sanctum`).

### Services

#### Listar Serviços
**GET** `/api/agenda/services`

**Permissão:** `agenda.services.view`

**Query Parameters (opcionais):**
- `active` – Filtrar por serviços ativos (true/false)

**Resposta (200):**
```json
[
  {
    "id": 1,
    "tenant_id": 1,
    "module_id": 10,
    "name": "Aula Prática",
    "slug": "aula-pratica",
    "duration_minutes": 50,
    "active": true,
    "module": {
      "id": 10,
      "key": "auto-escola",
      "name": "Auto Escola"
    },
    "price": {
      "id": 1,
      "price": 150.00,
      "currency": "BRL",
      "start_date": "2025-01-01",
      "end_date": null
    },
    "created_at": "2025-12-03T10:00:00.000000Z",
    "updated_at": "2025-12-03T10:00:00.000000Z"
  }
]
```

**Observação:** 
- Apenas serviços dos módulos ativos do tenant (em `tenant_modules`) são retornados
- O campo `price` retorna o preço ativo e dentro da vigência (se houver datas). Se não houver preço, retorna `null`

---

#### Buscar Serviço por ID
**GET** `/api/agenda/services/{id}`

**Permissão:** `agenda.services.view`

**Resposta (200):** Mesmo formato do listar

**Erro (404):**
```json
{
  "message": "Serviço não encontrado"
}
```

---

#### Criar Serviço
**POST** `/api/agenda/services`

**Permissão:** `agenda.services.create`

**Payload:**
```json
{
  "module_id": 10,
  "name": "Aula Prática",
  "slug": "aula-pratica",
  "duration_minutes": 50,
  "active": true
}
```

**Validação:**
- `module_id` – obrigatório, deve existir em `modules` e estar ativo para o tenant
- `name` – obrigatório, string, max:255
- `slug` – obrigatório, string, max:255
- `duration_minutes` – obrigatório, integer, min:1
- `active` – opcional, boolean

**Regra:** O `module_id` deve estar na lista de módulos ativos do tenant em `tenant_modules`. Caso contrário, retorna erro 422.

**Resposta (201):** Mesmo formato do listar

---

#### Atualizar Serviço
**PUT/PATCH** `/api/agenda/services/{id}`

**Permissão:** `agenda.services.edit`

**Payload:** Mesmos campos do criar (todos opcionais com `sometimes`)

**Campos Adicionais:**
- `update_price` – opcional, boolean (se true, desativa preços antigos e cria novo)

**Regras:**
- Se `module_id` for alterado, deve estar na lista de módulos ativos do tenant
- Se `price` for informado e `update_price=true`, todos os preços ativos anteriores serão desativados e um novo será criado (mantém histórico)

**Resposta (200):** Mesmo formato do listar

---

#### Excluir Serviço
**DELETE** `/api/agenda/services/{id}`  
**DELETE** `/api/agenda/services/batch` (com body `{"ids": [1, 2, 3]}`)  
**DELETE** `/api/agenda/services` (com body `{"ids": [1, 2, 3]}`)

**Permissão:** `agenda.services.delete`

**Resposta (200):**
```json
{
  "message": "Serviço excluído com sucesso",
  "deleted": [1]
}
```

---

### Providers

**📖 Documentação Completa:** [Providers CRUD](./providers-crud.md)

#### Resumo das Rotas

- **GET** `/api/agenda/providers` - Lista providers (`agenda.providers.view`)
- **GET** `/api/agenda/providers/{id}` - Busca provider por ID (`agenda.providers.view`)
- **POST** `/api/agenda/providers` - Cria provider (`agenda.providers.create`)
- **PUT/PATCH** `/api/agenda/providers/{id}` - Atualiza provider (`agenda.providers.edit`)
- **DELETE** `/api/agenda/providers/{id}` - Remove provider (`agenda.providers.delete`)

**Características:**
- Criação completa: User → Person → Provider
- Upload de foto (armazenada em `persons.photo_url`)
- Permissões automáticas atribuídas ao criar
- Suporte a múltiplos serviços (`service_ids`)
- Resposta inclui dados completos de User, Person e Services

**Veja [Providers CRUD](./providers-crud.md) para detalhes completos, exemplos de payload e respostas.**

---

### Agenda Completa

#### Buscar Agenda Completa
**GET** `/api/agenda`

**Permissão:** `agenda.appointments.view`

**Query Parameters (obrigatórios):**
- `provider_id` – ID do profissional (integer)
- `start` – Data de início do período (opcional, formato: YYYY-MM-DD)
- `end` – Data de fim do período (opcional, formato: YYYY-MM-DD)

**Resposta (200):**
```json
{
  "tenant_business_hours": [
    {
      "id": 1,
      "tenant_id": 1,
      "weekday": 1,
      "start_time": "09:00:00",
      "end_time": "19:00:00",
      "active": true
    }
  ],
  "availabilities": [
    {
      "id": 1,
      "provider_id": 2,
      "weekday": 1,
      "start_time": "10:00:00",
      "end_time": "18:00:00",
      "active": true
    }
  ],
  "blocks": [
    {
      "id": 1,
      "provider_id": 2,
      "tenant_id": 1,
      "start_at": "2025-12-20T12:00:00",
      "end_at": "2025-12-20T13:00:00",
      "reason": "Almoço",
      "created_by": 1
    }
  ],
  "schedules": [
    {
      "id": 1,
      "date_start": "2025-12-20T14:30:00",
      "date_end": "2025-12-20T15:00:00",
      "service": {...},
      "client": {...}
    }
  ]
}
```

**Observação:** Retorna todos os dados necessários para montar a agenda: horários do tenant, disponibilidades do profissional, bloqueios e agendamentos.

---

### Horários de Funcionamento do Tenant

#### Listar Horários
**GET** `/api/tenants/{tenantId}/business-hours`

**Permissão:** `agenda.providers.view` (ou permissão de admin do tenant)

**Resposta (200):**
```json
[
  {
    "id": 1,
    "tenant_id": 1,
    "weekday": 1,
    "start_time": "09:00:00",
    "end_time": "19:00:00",
    "active": true,
    "created_at": "2025-12-21T10:00:00.000000Z",
    "updated_at": "2025-12-21T10:00:00.000000Z"
  }
]
```

---

#### Criar Horário
**POST** `/api/tenants/{tenantId}/business-hours`

**Permissão:** `agenda.providers.edit` (ou permissão de admin do tenant)

**Payload:**
```json
{
  "weekday": 1,
  "start_time": "09:00:00",
  "end_time": "19:00:00",
  "active": true
}
```

**Validação:**
- `weekday` – obrigatório, integer (0-6, onde 0 = domingo)
- `start_time` – obrigatório, formato H:i:s
- `end_time` – obrigatório, formato H:i:s, deve ser após `start_time`
- `active` – opcional, boolean (padrão: true)

---

#### Sincronizar Múltiplos Horários
**POST** `/api/tenants/{tenantId}/business-hours/sync`

**Permissão:** `agenda.providers.edit`

**Payload:**
```json
{
  "business_hours": [
    {
      "weekday": 1,
      "start_time": "09:00:00",
      "end_time": "19:00:00",
      "active": true
    },
    {
      "weekday": 2,
      "start_time": "09:00:00",
      "end_time": "19:00:00",
      "active": true
    }
  ]
}
```

**Observação:** Cria ou atualiza os horários conforme o `weekday`. Útil para configurar a semana toda de uma vez.

---

#### Atualizar Horário
**PUT/PATCH** `/api/tenants/{tenantId}/business-hours/{id}`

**Permissão:** `agenda.providers.edit`

**Payload:** Mesmos campos do criar (todos opcionais)

---

#### Excluir Horário
**DELETE** `/api/tenants/{tenantId}/business-hours/{id}`

**Permissão:** `agenda.providers.edit`

---

### Disponibilidades do Profissional

#### Listar Disponibilidades
**GET** `/api/agenda/providers/{providerId}/availabilities`

**Permissão:** `agenda.providers.view`

**Resposta (200):**
```json
[
  {
    "id": 1,
    "provider_id": 2,
    "weekday": 1,
    "start_time": "10:00:00",
    "end_time": "18:00:00",
    "active": true
  }
]
```

---

#### Criar Disponibilidade
**POST** `/api/agenda/providers/{providerId}/availabilities`

**Permissão:** `agenda.providers.edit`

**Payload:**
```json
{
  "weekday": 1,
  "start_time": "10:00:00",
  "end_time": "18:00:00",
  "active": true
}
```

**Validação:**
- `weekday` – obrigatório, integer (0-6)
- `start_time` – obrigatório, formato H:i:s
- `end_time` – obrigatório, formato H:i:s, deve ser após `start_time`
- `active` – opcional, boolean (padrão: true)

**Observação:** O horário do profissional não pode ultrapassar o horário do tenant.

---

#### Atualizar Disponibilidade
**PUT/PATCH** `/api/agenda/providers/{providerId}/availabilities/{id}`

**Permissão:** `agenda.providers.edit`

---

#### Excluir Disponibilidade
**DELETE** `/api/agenda/providers/{providerId}/availabilities/{id}`

**Permissão:** `agenda.providers.edit`

---

### Bloqueios do Profissional

#### Listar Bloqueios
**GET** `/api/agenda/providers/{providerId}/blocks`

**Permissão:** `agenda.providers.view`

**Query Parameters (opcionais):**
- `start` – Filtrar bloqueios a partir desta data
- `end` – Filtrar bloqueios até esta data

**Resposta (200):**
```json
[
  {
    "id": 1,
    "provider_id": 2,
    "tenant_id": 1,
    "start_at": "2025-12-20T12:00:00",
    "end_at": "2025-12-20T13:00:00",
    "reason": "Almoço",
    "created_by": 1,
    "created_at": "2025-12-20T10:00:00.000000Z",
    "updated_at": "2025-12-20T10:00:00.000000Z"
  }
]
```

---

#### Criar Bloqueio
**POST** `/api/agenda/providers/{providerId}/blocks`

**Permissão:** `agenda.providers.edit`

**Payload:**
```json
{
  "start_at": "2025-12-20 12:00:00",
  "end_at": "2025-12-20 13:00:00",
  "reason": "Almoço"
}
```

**Validação:**
- `start_at` – obrigatório, formato datetime
- `end_at` – obrigatório, formato datetime, deve ser após `start_at`
- `reason` – opcional, string, max:255

**Observação:** O `created_by` é preenchido automaticamente com o ID do usuário autenticado.

---

#### Atualizar Bloqueio
**PUT/PATCH** `/api/agenda/providers/{providerId}/blocks/{id}`

**Permissão:** `agenda.providers.edit`

---

#### Excluir Bloqueio
**DELETE** `/api/agenda/providers/{providerId}/blocks/{id}`

**Permissão:** `agenda.providers.edit`

---

### Appointments

**📖 Documentação Completa:** [Appointments CRUD](./appointments-crud.md)

#### Resumo das Rotas
**GET** `/api/agenda/appointments`

**Permissão:** `agenda.appointments.view`

**Query Parameters (opcionais):**
- `provider_id` – Filtrar por profissional
- `date_start` – Filtrar a partir desta data
- `date_end` – Filtrar até esta data

**Resposta (200):**
```json
[
  {
    "id": 1,
    "tenant_id": 1,
    "service_id": 1,
    "provider_id": 2,
    "client_id": 3,
    "date_start": "2025-12-15T14:30:00.000000Z",
    "date_end": "2025-12-15T15:00:00.000000Z",
    "status_agenda_id": 1,
    "notes": "Cliente prefere horário da manhã",
    "service": {
      "id": 1,
      "name": "Aula Prática",
      "slug": "aula-pratica",
      "duration_minutes": 30
    },
    "provider": {
      "id": 2,
      "name": "João Silva",
      "user": {
        "id": 5,
        "name": "João Silva",
        "email": "joao@example.com"
      }
    },
    "client": {
      "id": 3,
      "name": "Maria Santos",
      "email": "maria@example.com"
    },
    "status_agenda": {
      "id": 1,
      "key": "agendado",
      "name": "Agendado"
    },
    "created_at": "2025-12-03T10:00:00.000000Z",
    "updated_at": "2025-12-03T10:00:00.000000Z"
  }
]
```

---

#### Buscar Agendamento por ID
**GET** `/api/agenda/appointments/{id}`

**Permissão:** `agenda.appointments.view`

**Resposta (200):** Mesmo formato do listar

---

#### Criar Agendamento
**POST** `/api/agenda/appointments`

**Permissão:** `agenda.appointments.create`

**Payload:**
```json
{
  "service_id": 1,
  "provider_id": 2,
  "client_id": 3,
  "date_start": "2025-12-15 14:30:00",
  "status_agenda_id": 1,
  "notes": "Observações do agendamento"
}
```

**Validação:**
- `service_id` – obrigatório, deve existir em `services`
- `provider_id` – obrigatório, deve existir em `providers`
- `client_id` – obrigatório, deve existir em `users`
- `date_start` – obrigatório, formato datetime válido
- `status_agenda_id` – opcional, deve existir em `status_agenda`
- `notes` – opcional, string

**Regras Automáticas:**
1. `date_end` é calculado automaticamente: `date_start + duration_minutes` do serviço
2. Validação de conflito de horário: se o provider já tiver agendamento no mesmo horário, retorna erro 422
3. `tenant_id` é preenchido automaticamente pelo sistema

**Resposta (201):** Mesmo formato do listar

**Erro (422) – Conflito de horário:**
```json
{
  "message": "Conflito de horário detectado"
}
```

---

#### Atualizar Agendamento
**PUT/PATCH** `/api/agenda/appointments/{id}`

**Permissão:** `agenda.appointments.edit`

**Payload:** Mesmos campos do criar (todos opcionais)

**Regras Automáticas:**
- Se `service_id` ou `date_start` for alterado, `date_end` é recalculado
- Validação de conflito de horário (excluindo o próprio agendamento)

**Resposta (200):** Mesmo formato do listar

---

#### Excluir Agendamento
**DELETE** `/api/agenda/appointments/{id}`  
**DELETE** `/api/agenda/appointments/batch`  
**DELETE** `/api/agenda/appointments`

**Permissão:** `agenda.appointments.delete`

**Resposta (200):**
```json
{
  "message": "Agendamento excluído com sucesso",
  "deleted": [1]
}
```

---

## ⚙️ Regras de Negócio

### 1. Cálculo Automático de `date_end`

O campo `date_end` é calculado automaticamente quando um agendamento é criado ou atualizado:

```
date_end = date_start + duration_minutes (do serviço)
```

**Exemplo:**
- Serviço: "Aula Prática" (30 minutos)
- `date_start`: "2025-12-15 14:30:00"
- `date_end`: "2025-12-15 15:00:00" (calculado automaticamente)

---

### 2. Hierarquia de Validação de Agendamentos (REGRA MESTRE)

🧠 **CONCEITO-CHAVE:** Nada pode acontecer fora do horário do TENANT, mesmo que o profissional esteja disponível.

**Ordem de validação (hierarquia correta):**

```
HORÁRIO DO TENANT
   ↓
HORÁRIO DO PROFISSIONAL
   ↓
BLOQUEIOS
   ↓
AGENDAMENTO
```

#### 2.1. Validação do Horário do Tenant (PRIMEIRO)

**Validação:**
- Verifica se o horário do agendamento está dentro do horário de funcionamento do tenant
- Consulta a tabela `tenant_business_hours` pelo `weekday` (dia da semana)
- Compara apenas o horário (HH:mm:ss), ignorando a data

**Erro retornado:** 422 com mensagem "Fora do horário de funcionamento do estabelecimento"

**Exemplo:**
- Tenant funciona: 09:00–19:00
- Tentativa de agendamento: 08:00
- ❌ **ERRO:** Fora do horário do tenant

#### 2.2. Validação do Horário do Profissional

**Validação:**
- Verifica se o horário está dentro da disponibilidade do profissional
- Consulta a tabela `professional_availabilities` pelo `weekday`
- O profissional não pode ultrapassar o horário do tenant

**Erro retornado:** 422 com mensagem "Fora do horário de disponibilidade do profissional"

**Exemplo:**
- Tenant funciona: 09:00–19:00
- Profissional disponível: 10:00–18:00
- Tentativa de agendamento: 09:30
- ❌ **ERRO:** Fora do horário do profissional (mesmo estando dentro do tenant)

#### 2.3. Validação de Bloqueios

**Validação:**
- Verifica se há bloqueio no horário do agendamento
- Consulta a tabela `professional_blocks` por sobreposição de horários
- Considera bloqueios que se sobrepõem ao agendamento

**Erro retornado:** 422 com mensagem "Horário bloqueado"

**Exemplos de bloqueios:**
- Almoço
- Folga
- Reunião
- Aula teórica

#### 2.4. Validação de Conflito com Outros Agendamentos

**Validação:**
- Verifica se existe agendamento do mesmo `provider_id` que se sobreponha ao horário
- Considera sobreposição quando:
  - `date_start` do novo agendamento está entre `date_start` e `date_end` de outro
  - `date_end` do novo agendamento está entre `date_start` e `date_end` de outro
  - O novo agendamento engloba completamente outro agendamento

**Erro retornado:** 422 com mensagem "Conflito de horário detectado"

**⚠️ IMPORTANTE:** A ordem de validação NÃO pode ser invertida. Se inverter, vai dar bug.

---

### 3. Filtro por Tenant e Módulos

- Todos os dados são filtrados automaticamente por `tenant_id` do usuário logado
- **Serviços:** São filtrados pelos módulos ativos do tenant em `tenant_modules`
  - Apenas serviços com `module_id` presente em `tenant_modules` são retornados
  - Ao criar/atualizar, o `module_id` deve estar nos módulos ativos do tenant
- Super admin (`is_super_admin = true`) ignora o filtro e vê todos os dados de todos os tenants

---

### 4. Client ID

- `client_id` referencia a tabela `users`
- Pode ser um aluno (tabela `students`) ou cliente futuro
- O sistema não valida se o `client_id` está na tabela `students`, apenas se existe em `users`

---

### 5. Status Agenda

- `status_agenda_id` é opcional
- Se não informado, fica `null`
- Status padrão disponíveis são criados pelo `StatusAgendaSeeder`

---

## 🔐 Permissões

O módulo Agenda possui as seguintes permissões:

### Services
- `agenda.services.view` – Visualizar serviços
- `agenda.services.create` – Criar serviços
- `agenda.services.edit` – Editar serviços
- `agenda.services.delete` – Excluir serviços

### Providers
- `agenda.providers.view` – Visualizar profissionais
- `agenda.providers.create` – Criar profissionais
- `agenda.providers.edit` – Editar profissionais
- `agenda.providers.delete` – Excluir profissionais

### Appointments
- `agenda.appointments.view` – Visualizar agendamentos
- `agenda.appointments.create` – Criar agendamentos
- `agenda.appointments.edit` – Editar agendamentos
- `agenda.appointments.delete` – Excluir agendamentos

### Permissão Completa
- `agenda.manage` – Permissão completa (todas as ações acima)

---

## 💡 Exemplos Práticos

### Exemplo 1: Criar um Serviço

**Requisição:**
```bash
curl -X POST http://localhost:8080/api/agenda/services \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "module_id": 10,
    "name": "Aula Prática",
    "slug": "aula-pratica",
    "duration_minutes": 50,
    "active": true
  }'
```

**Resposta:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "module_id": 10,
  "name": "Aula Prática",
  "slug": "aula-pratica",
  "duration_minutes": 50,
  "active": true,
  "module": {
    "id": 10,
    "key": "auto-escola",
    "name": "Auto Escola"
  },
  "created_at": "2025-12-03T10:00:00.000000Z",
  "updated_at": "2025-12-03T10:00:00.000000Z"
}
```

---

### Exemplo 2: Criar um Profissional

**Requisição:**
```bash
curl -X POST http://localhost:8080/api/agenda/providers \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 5,
    "name": "João Silva",
    "service_ids": [1, 2, 3]
  }'
```

---

### Exemplo 3: Criar um Agendamento

**Requisição:**
```bash
curl -X POST http://localhost:8080/api/agenda/appointments \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 1,
    "provider_id": 2,
    "client_id": 3,
    "date_start": "2025-12-15 14:30:00",
    "status_agenda_id": 1,
    "notes": "Cliente prefere horário da manhã"
  }'
```

**Resposta:**
```json
{
  "id": 1,
  "tenant_id": 1,
  "service_id": 1,
  "provider_id": 2,
  "client_id": 3,
  "date_start": "2025-12-15T14:30:00.000000Z",
  "date_end": "2025-12-15T15:00:00.000000Z",
  "status_agenda_id": 1,
  "notes": "Cliente prefere horário da manhã",
  "service": {
    "id": 1,
    "name": "Aula Prática",
    "slug": "aula-pratica",
    "duration_minutes": 30
  },
  "provider": {
    "id": 2,
    "name": "João Silva",
    "user": {
      "id": 5,
      "name": "João Silva",
      "email": "joao@example.com"
    }
  },
  "client": {
    "id": 3,
    "name": "Maria Santos",
    "email": "maria@example.com"
  },
  "status_agenda": {
    "id": 1,
    "key": "agendado",
    "name": "Agendado"
  },
  "created_at": "2025-12-03T10:00:00.000000Z",
  "updated_at": "2025-12-03T10:00:00.000000Z"
}
```

---

### Exemplo 4: Listar Agendamentos com Filtros

**Requisição:**
```bash
curl -X GET "http://localhost:8080/api/agenda/appointments?provider_id=2&date_start=2025-12-15" \
  -H "Authorization: Bearer SEU_TOKEN"
```

---

### Exemplo 5: JavaScript/Fetch

```javascript
// Criar agendamento
const response = await fetch('http://localhost:8080/api/agenda/appointments', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    service_id: 1,
    provider_id: 2,
    client_id: 3,
    date_start: "2025-12-15 14:30:00",
    status_agenda_id: 1,
    notes: "Observações"
  })
});

const appointment = await response.json();
console.log(appointment);
```

---

### Exemplo 6: Axios

```javascript
import axios from 'axios';

// Criar agendamento
const appointment = await axios.post('/api/agenda/appointments', {
  service_id: 1,
  provider_id: 2,
  client_id: 3,
  date_start: "2025-12-15 14:30:00",
  status_agenda_id: 1,
  notes: "Observações"
}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

console.log(appointment.data);
```

---

## 🚨 Tratamento de Erros

### Erro 400 – Bad Request
```json
{
  "message": "Tenant não identificado"
}
```

### Erro 404 – Not Found
```json
{
  "message": "Serviço não encontrado"
}
```

### Erro 422 – Unprocessable Entity

**Validação:**
```json
{
  "message": "Erro na validação",
  "errors": {
    "service_id": ["O campo service_id é obrigatório."],
    "date_start": ["O campo date_start deve ser uma data válida."]
  }
}
```

**Fora do horário do tenant:**
```json
{
  "message": "Fora do horário de funcionamento do estabelecimento"
}
```

**Fora do horário do profissional:**
```json
{
  "message": "Fora do horário de disponibilidade do profissional"
}
```

**Horário bloqueado:**
```json
{
  "message": "Horário bloqueado"
}
```

**Conflito de horário:**
```json
{
  "message": "Conflito de horário detectado"
}
```

**Módulo não ativo para o tenant:**
```json
{
  "message": "Módulo não está ativo para este tenant"
}
```

---

## 📝 Observações Importantes

1. **Formato de Data:** Use `"YYYY-MM-DD HH:mm:ss"` ou ISO 8601 para `date_start`
2. **Client ID:** Deve ser um `user_id` válido (pode ser aluno ou cliente futuro)
3. **Status Agenda:** É opcional; se não informado, fica `null`
4. **Conflito de Horário:** O sistema valida automaticamente se o provider já tem agendamento no mesmo horário
5. **Super Admin:** Ignora `tenant_id` e vê todos os dados de todos os tenants
6. **Service IDs:** No provider, é um array JSON de IDs dos serviços que o profissional oferece
7. **Module ID:** Obrigatório ao criar serviços. Deve estar nos módulos ativos do tenant em `tenant_modules`
8. **Filtro de Serviços:** Apenas serviços dos módulos ativos do tenant são retornados na listagem

---

## 🔄 Fluxo Típico de Uso

1. **Criar Serviços** – Definir os serviços oferecidos (ex: "Aula Prática", "Corte")
2. **Criar Profissionais** – Cadastrar profissionais vinculados a usuários
3. **Criar Agendamentos** – Agendar clientes com profissionais e serviços
4. **Atualizar Status** – Alterar status dos agendamentos conforme o andamento
5. **Listar/Filtrar** – Buscar agendamentos por profissional, data, etc.

---

---

## 🌱 Seeders

### ServiceSeeder

Cria serviços padrão para diferentes tipos de negócios:

**Auto-escola (module_id = 10):**
- Aulas Práticas – 50 min
- Aulas Teóricas – 50 min

**Barbearia (module_id = 11):**
- Corte Masculino – 30 min
- Barba – 20 min
- Corte + Barba – 45 min
- Sobrancelha – 15 min
- Tratamento Capilar – 40 min

**Salão de Beleza (module_id = 12):**
- Corte Feminino – 45 min
- Escova – 60 min
- Coloração – 120 min
- Manicure – 45 min
- Pedicure – 50 min
- Manicure + Pedicure – 90 min
- Maquiagem – 60 min
- Design de Sobrancelhas – 30 min

**Execução:**
```bash
php artisan db:seed --class=ServiceSeeder
```

---

---

## 📅 Status Agenda

### Listar Status
**GET** `/api/status-agenda`

**Permissão:** Não requer (autenticação apenas)

**Resposta (200):**
```json
[
  {
    "id": 1,
    "key": "agendado",
    "name": "Agendado",
    "description": "Agendamento confirmado",
    "order": 1,
    "active": true
  }
]
```

---

### Buscar Status por ID
**GET** `/api/status-agenda/{id}`

**Permissão:** Não requer (autenticação apenas)

---

**Documentação atualizada em:** 2025-12-21

